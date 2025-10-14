<?php
/**
 * Gerenciador de Emails Multi-Tenant com Postmark
 * 
 * Permite enviar emails com identidade personalizada por tenant
 * (nome da empresa e email de resposta customizados)
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

class MultiTenantEmailManager {
    private $db;
    private $server_token;
    private $account_token;
    private $api_url;
    private $system_from_email;
    
    /**
     * Construtor
     */
    public function __construct() {
        require_once __DIR__ . '/../config/integrations.php';
        
        $this->db = Database::getInstance()->getConnection();
        $this->server_token = POSTMARK_SERVER_TOKEN;
        $this->account_token = POSTMARK_ACCOUNT_TOKEN;
        $this->api_url = POSTMARK_API_URL;
        $this->system_from_email = POSTMARK_FROM_EMAIL;
    }
    
    /**
     * Solicitar verificação de email para tenant (Plano Pro)
     * 
     * @param int $tenant_id ID do tenant
     * @param string $email_address Email a ser verificado
     * @param string $company_name Nome da empresa
     * @return array ['success' => bool, 'message' => string]
     */
    public function requestEmailVerification($tenant_id, $email_address, $company_name) {
        try {
            // Verificar se é plano Pro
            $tenant = $this->getTenantData($tenant_id);
            if ($tenant['plano'] !== 'pro') {
                throw new Exception("Emails personalizados disponíveis apenas no plano Pro");
            }
            
            // Criar Sender Signature via API Postmark
            $response = $this->createSenderSignature(
                $email_address,
                $company_name,
                "Verificação de email para {$company_name} no Sistema de Consignados. " .
                "Este email é necessário para que você possa enviar notificações com sua identidade."
            );
            
            if ($response['success']) {
                // Salvar no banco
                $stmt = $this->db->prepare("
                    INSERT INTO tenant_email_verifications 
                    (tenant_id, email_address, provider_signature_id, status) 
                    VALUES (?, ?, ?, 'pendente')
                    ON DUPLICATE KEY UPDATE 
                    provider_signature_id = VALUES(provider_signature_id),
                    status = 'pendente',
                    criado_em = NOW()
                ");
                $stmt->execute([
                    $tenant_id,
                    $email_address,
                    $response['signature_id']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Email de verificação enviado para ' . $email_address
                ];
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Erro ao solicitar verificação de email: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar email em nome do tenant
     * 
     * @param int $tenant_id ID do tenant
     * @param string $to Email destinatário
     * @param string $subject Assunto
     * @param string $html_body Corpo HTML
     * @param string|null $text_body Corpo texto (opcional)
     * @return array
     */
    public function sendTenantEmail($tenant_id, $to, $subject, $html_body, $text_body = null) {
        try {
            // Buscar dados do tenant
            $stmt = $this->db->prepare("
                SELECT nome_empresa_email, email_resposta, smtp_status 
                FROM tenants 
                WHERE id = ?
            ");
            $stmt->execute([$tenant_id]);
            $tenant = $stmt->fetch();
            
            if (!$tenant) {
                throw new Exception("Tenant não encontrado");
            }
            
            // Definir remetente e reply-to
            $from_name = $tenant['nome_empresa_email'] ?? POSTMARK_FROM_NAME;
            $reply_to = ($tenant['smtp_status'] === 'verificado' && $tenant['email_resposta']) 
                ? $tenant['email_resposta'] 
                : null;
            
            // Preparar dados do email
            $email_data = [
                'From' => $this->system_from_email,
                'FromName' => $from_name,
                'To' => $to,
                'Subject' => $subject,
                'HtmlBody' => $html_body,
                'TextBody' => $text_body ?: strip_tags($html_body),
                'TrackOpens' => true,
                'MessageStream' => 'outbound'
            ];
            
            // Adicionar Reply-To se email verificado
            if ($reply_to) {
                $email_data['ReplyTo'] = $reply_to;
            }
            
            return $this->sendViaPostmark($email_data);
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar email de notificação de consignação
     * 
     * @param int $tenant_id ID do tenant
     * @param string $to Email do estabelecimento
     * @param array $consignacao_data Dados da consignação
     * @return array
     */
    public function sendConsignacaoNotification($tenant_id, $to, $consignacao_data) {
        $subject = 'Nova Consignação #' . $consignacao_data['id'];
        
        $html_body = $this->getConsignacaoEmailTemplate($consignacao_data);
        
        return $this->sendTenantEmail($tenant_id, $to, $subject, $html_body);
    }
    
    /**
     * Processar webhook do Postmark
     * 
     * @param array $payload Dados do webhook
     * @return bool
     */
    public function processWebhook($payload) {
        try {
            $event = $payload['RecordType'] ?? '';
            
            if ($event === 'SubscriptionChange') {
                return $this->handleSenderVerification($payload);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao processar webhook Postmark: " . $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // MÉTODOS PRIVADOS
    // ============================================
    
    private function createSenderSignature($email, $name, $note) {
        $signature_data = [
            'FromEmail' => $email,
            'Name' => $name,
            'ReplyToEmail' => $email,
            'ConfirmationPersonalNote' => $note
        ];
        
        $response = $this->makeAccountRequest('POST', '/senders', $signature_data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'signature_id' => $response['data']['ID']
            ];
        }
        
        return [
            'success' => false,
            'message' => $response['error'] ?? 'Erro ao criar sender signature'
        ];
    }
    
    private function sendViaPostmark($email_data) {
        $response = $this->makeServerRequest('POST', '/email', $email_data);
        
        return [
            'success' => $response['success'],
            'message' => $response['success'] ? 'Email enviado com sucesso' : $response['error'],
            'data' => $response['data']
        ];
    }
    
    private function handleSenderVerification($payload) {
        $email = $payload['Email'] ?? null;
        $signature_id = $payload['SignatureID'] ?? null;
        
        if (!$email || !$signature_id) {
            return false;
        }
        
        // Atualizar verificação no banco
        $stmt = $this->db->prepare("
            UPDATE tenant_email_verifications 
            SET status = 'verificado',
                verificado_em = NOW()
            WHERE email_address = ? AND provider_signature_id = ?
        ");
        $stmt->execute([$email, $signature_id]);
        
        // Atualizar tenant
        $stmt = $this->db->prepare("
            UPDATE tenants t
            INNER JOIN tenant_email_verifications v ON t.id = v.tenant_id
            SET t.smtp_status = 'verificado',
                t.email_resposta = v.email_address
            WHERE v.email_address = ? AND v.provider_signature_id = ?
        ");
        $stmt->execute([$email, $signature_id]);
        
        return true;
    }
    
    private function getTenantData($tenant_id) {
        $stmt = $this->db->prepare("SELECT * FROM tenants WHERE id = ?");
        $stmt->execute([$tenant_id]);
        return $stmt->fetch();
    }
    
    private function makeServerRequest($method, $endpoint, $data = null) {
        return $this->makeRequest($this->server_token, $method, $endpoint, $data);
    }
    
    private function makeAccountRequest($method, $endpoint, $data = null) {
        return $this->makeRequest($this->account_token, $method, $endpoint, $data, true);
    }
    
    private function makeRequest($token, $method, $endpoint, $data = null, $is_account = false) {
        $url = $this->api_url . $endpoint;
        
        $curl = curl_init();
        
        $header_key = $is_account ? 'X-Postmark-Account-Token' : 'X-Postmark-Server-Token';
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                $header_key . ': ' . $token
            ],
            CURLOPT_TIMEOUT => 30
        ];
        
        if ($method === 'POST' && $data) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
        
        if ($curl_error) {
            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $curl_error
            ];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            'success' => $http_code >= 200 && $http_code < 300,
            'data' => $decoded,
            'error' => $http_code >= 400 ? ($decoded['Message'] ?? 'Erro desconhecido') : null
        ];
    }
    
    private function getConsignacaoEmailTemplate($data) {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nova Consignação</h1>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>Uma nova consignação foi criada para seu estabelecimento:</p>
            <p><strong>Número:</strong> #' . $data['id'] . '<br>
            <strong>Data:</strong> ' . date('d/m/Y', strtotime($data['data_consignacao'])) . '<br>
            <strong>Produtos:</strong> ' . $data['total_itens'] . ' itens</p>
            <a href="' . SITE_URL . '/cliente_consignacao.php?id=' . $data['id'] . '" class="button">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Sistema de Consignados - Desenvolvido por <a href="https://dantetesta.com.br">Dante Testa</a></p>
        </div>
    </div>
</body>
</html>';
    }
}
