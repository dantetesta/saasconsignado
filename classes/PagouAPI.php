<?php
/**
 * Classe para integração com API Pagou
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 * 
 * Baseado no manual: manual-pagou.md
 */

class PagouAPI {
    private $apiKey;
    private $apiUrl;
    private $ambiente;
    
    /**
     * Construtor
     * Busca configuração do banco de dados
     */
    public function __construct() {
        $this->loadConfig();
    }
    
    /**
     * Carregar configuração do banco
     */
    private function loadConfig() {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Buscar configuração do gateway Pagou
            $stmt = $db->query("
                SELECT configuracao, ativo 
                FROM payment_gateways 
                WHERE slug = 'pagou' 
                LIMIT 1
            ");
            $gateway = $stmt->fetch();
            
            if (!$gateway) {
                throw new Exception('Gateway Pagou não encontrado no banco de dados');
            }
            
            if (!$gateway['ativo']) {
                throw new Exception('Gateway Pagou está desativado. Ative-o no painel admin.');
            }
            
            $config = json_decode($gateway['configuracao'], true);
            
            if (empty($config['api_key'])) {
                throw new Exception('API Key do Pagou não configurada. Configure no painel admin.');
            }
            
            $this->apiKey = $config['api_key'];
            $this->ambiente = $config['ambiente'] ?? 'production';
            
            // URL baseada no ambiente
            if ($this->ambiente === 'sandbox') {
                $this->apiUrl = 'https://sandbox.pagou.com.br/api';
            } else {
                $this->apiUrl = 'https://api.pagou.com.br';
            }
            
        } catch (PDOException $e) {
            throw new Exception('Erro ao carregar configuração do gateway: ' . $e->getMessage());
        }
    }
    
    /**
     * Criar PIX para assinatura mensal (R$ 20,00)
     * 
     * @param string $tenantId ID do tenant
     * @param string $nome Nome completo
     * @param string $cpf CPF (11 dígitos)
     * @param string $email Email
     * @return array Dados do PIX criado
     */
    public function criarPixAssinatura($tenantId, $nome, $cpf, $email) {
        // Validar se CPF foi fornecido
        if (empty($cpf)) {
            throw new Exception('CPF/CNPJ não cadastrado. Por favor, atualize seu perfil antes de fazer o upgrade.');
        }
        
        // Remove caracteres não numéricos do CPF
        $cpfLimpo = preg_replace('/\D/', '', $cpf);
        
        // Valida CPF (11 dígitos) ou CNPJ (14 dígitos)
        if (strlen($cpfLimpo) !== 11 && strlen($cpfLimpo) !== 14) {
            throw new Exception('CPF deve ter 11 dígitos ou CNPJ deve ter 14 dígitos. Por favor, atualize seu perfil.');
        }
        
        // Monta payload
        $payload = [
            'amount' => 20.00, // Plano Pro: R$ 20/mês
            'description' => 'Assinatura Plano Pro - Sistema de Consignados',
            'expiration' => 3600, // 1 hora para pagar
            'payer' => [
                'name' => $nome,
                'document' => $cpfLimpo
            ]
        ];
        
        // Faz requisição
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '/v1/pix',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->apiKey,
                'Content-Type: application/json',
                'User-Agent: SistemaConsignados/2.0'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            $errorMsg = 'Erro ao criar PIX';
            if ($response) {
                $responseData = json_decode($response, true);
                $errorMsg .= ': ' . ($responseData['message'] ?? $response);
            }
            throw new Exception($errorMsg);
        }
        
        $data = json_decode($response, true);
        
        // Validar resposta
        if (!isset($data['id']) || !isset($data['payload'])) {
            throw new Exception('Resposta inválida da API Pagou: ' . json_encode($data));
        }
        
        // Retorna dados formatados
        return [
            'charge_id' => $data['id'],
            'qrcode_data' => $data['payload']['data'] ?? '', // Código copia e cola
            'qrcode_image' => $data['payload']['image'] ?? '', // Base64 (sem prefixo)
            'amount' => $data['amount'] ?? 20.00,
            'expiration' => $data['expiration'] ?? 3600,
            'status' => $data['status'] ?? 0,
            'paid_at' => $data['paid_at'] ?? null
        ];
    }
    
    /**
     * Verificar se PIX foi pago
     * 
     * IMPORTANTE: Valida apenas paid_at (não status)
     * Conforme manual: status pode ser fake em sandbox
     * 
     * @param string $chargeId UUID do PIX
     * @return array ['pago' => bool, 'paid_at' => string|null]
     */
    public function verificarPagamento($chargeId) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . "/v1/pix/{$chargeId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Erro ao verificar pagamento');
        }
        
        $data = json_decode($response, true);
        
        // ✅ VALIDAÇÃO CORRETA (conforme manual)
        // Ignora status, só verifica paid_at
        $pago = isset($data['paid_at']) && !empty($data['paid_at']);
        
        return [
            'pago' => $pago,
            'paid_at' => $data['paid_at'] ?? null,
            'status' => $data['status'] ?? null,
            'expired_at' => $data['expired_at'] ?? null
        ];
    }
    
    /**
     * Obter detalhes completos do PIX
     * 
     * @param string $chargeId UUID do PIX
     * @return array Dados completos
     */
    public function obterDetalhes($chargeId) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . "/v1/pix/{$chargeId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Erro ao obter detalhes do PIX');
        }
        
        return json_decode($response, true);
    }
}
