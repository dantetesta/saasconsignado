<?php
/**
 * Integração com Pagou.com.br para Assinaturas Recorrentes
 * 
 * Gerencia pagamentos recorrentes mensais do plano Pro (R$ 20/mês)
 * Suporta PIX e Boleto
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

class PagouIntegration {
    private $api_key;
    private $base_url;
    private $db;
    
    /**
     * Construtor
     * 
     * @param string|null $api_key API Key (usa configuração padrão se null)
     * @param string|null $base_url URL base da API (usa configuração padrão se null)
     */
    public function __construct($api_key = null, $base_url = null) {
        require_once __DIR__ . '/../config/integrations.php';
        
        $this->api_key = $api_key ?? getPagouApiKey();
        $this->base_url = $base_url ?? getPagouApiUrl();
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Criar assinatura recorrente para upgrade Free → Pro
     * 
     * @param int $tenant_id ID do tenant
     * @param string $forma_pagamento 'pix' ou 'boleto'
     * @return array ['success' => bool, 'data' => array, 'error' => string]
     */
    public function createSubscription($tenant_id, $forma_pagamento = 'pix') {
        try {
            // Buscar dados do tenant
            $tenant = $this->getTenantData($tenant_id);
            if (!$tenant) {
                throw new Exception("Tenant não encontrado");
            }
            
            // Buscar plano Pro
            $plan = $this->getPlanData('pro');
            if (!$plan) {
                throw new Exception("Plano Pro não encontrado");
            }
            
            // Criar/obter customer no Pagou
            $customer = $this->getOrCreateCustomer($tenant);
            
            // Criar cobrança recorrente
            $charge_data = [
                'customer_id' => $customer['id'],
                'amount' => $plan['preco'] * 100, // Converter para centavos
                'description' => 'Assinatura Plano Pro - ' . $tenant['nome_empresa'],
                'payment_method' => $forma_pagamento,
                'recurrence' => [
                    'interval' => 'monthly',
                    'interval_count' => 1
                ],
                'metadata' => [
                    'tenant_id' => $tenant_id,
                    'plan_slug' => 'pro'
                ],
                'webhook_url' => PAGOU_WEBHOOK_URL
            ];
            
            $response = $this->makeRequest('POST', '/charges', $charge_data);
            
            if ($response['success']) {
                // Salvar assinatura no banco
                $subscription_id = $this->saveSubscription(
                    $tenant_id,
                    $plan['id'],
                    $response['data'],
                    $customer['id'],
                    $forma_pagamento
                );
                
                // Salvar transação
                $this->saveTransaction(
                    $subscription_id,
                    $tenant_id,
                    $response['data'],
                    $plan['preco'],
                    $forma_pagamento
                );
                
                return [
                    'success' => true,
                    'subscription_id' => $subscription_id,
                    'payment_url' => $response['data']['payment_url'] ?? null,
                    'pix_code' => $response['data']['pix_code'] ?? null,
                    'barcode' => $response['data']['barcode'] ?? null,
                    'due_date' => $response['data']['due_date'] ?? null
                ];
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Erro ao criar assinatura Pagou: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Processar webhook do Pagou.com.br
     * 
     * @param array $payload Dados recebidos do webhook
     * @return bool
     */
    public function processWebhook($payload) {
        try {
            $event = $payload['event'] ?? '';
            
            error_log("Pagou Webhook: " . $event);
            
            switch ($event) {
                case 'charge.paid':
                case 'charge.confirmed':
                    return $this->handlePaymentSuccess($payload['data']);
                    
                case 'charge.failed':
                case 'charge.expired':
                    return $this->handlePaymentFailed($payload['data']);
                    
                case 'charge.refunded':
                    return $this->handlePaymentRefunded($payload['data']);
                    
                case 'subscription.cancelled':
                    return $this->handleSubscriptionCancelled($payload['data']);
                    
                default:
                    error_log("Evento desconhecido: " . $event);
                    return false;
            }
            
        } catch (Exception $e) {
            error_log("Erro ao processar webhook Pagou: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancelar assinatura
     * 
     * @param int $tenant_id ID do tenant
     * @return array
     */
    public function cancelSubscription($tenant_id) {
        try {
            // Buscar assinatura ativa
            $stmt = $this->db->prepare("
                SELECT id, pagou_subscription_id 
                FROM subscriptions 
                WHERE tenant_id = ? AND status = 'ativa'
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$tenant_id]);
            $subscription = $stmt->fetch();
            
            if (!$subscription) {
                throw new Exception("Assinatura ativa não encontrada");
            }
            
            // Cancelar no Pagou (se tiver ID)
            if ($subscription['pagou_subscription_id']) {
                $this->makeRequest('DELETE', '/subscriptions/' . $subscription['pagou_subscription_id']);
            }
            
            // Atualizar no banco
            $stmt = $this->db->prepare("
                UPDATE subscriptions 
                SET status = 'cancelada', 
                    data_cancelamento = CURDATE() 
                WHERE id = ?
            ");
            $stmt->execute([$subscription['id']]);
            
            // Downgrade para Free
            $stmt = $this->db->prepare("
                UPDATE tenants 
                SET plano = 'free',
                    limite_estabelecimentos = 5,
                    limite_consignacoes_por_estabelecimento = 5
                WHERE id = ?
            ");
            $stmt->execute([$tenant_id]);
            
            return ['success' => true, 'message' => 'Assinatura cancelada'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obter status da assinatura
     * 
     * @param int $tenant_id ID do tenant
     * @return array|null
     */
    public function getSubscriptionStatus($tenant_id) {
        $stmt = $this->db->prepare("
            SELECT s.*, sp.nome as plan_name, sp.preco as plan_price
            FROM subscriptions s
            JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.tenant_id = ?
            ORDER BY s.id DESC LIMIT 1
        ");
        $stmt->execute([$tenant_id]);
        
        return $stmt->fetch();
    }
    
    // ============================================
    // MÉTODOS PRIVADOS
    // ============================================
    
    private function handlePaymentSuccess($data) {
        $metadata = $data['metadata'] ?? [];
        $tenant_id = $metadata['tenant_id'] ?? null;
        
        if (!$tenant_id) {
            error_log("Webhook sem tenant_id");
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Atualizar transação
            $stmt = $this->db->prepare("
                UPDATE payment_transactions 
                SET status = 'pago',
                    data_pagamento = NOW(),
                    webhook_data = ?
                WHERE pagou_transaction_id = ?
            ");
            $stmt->execute([
                json_encode($data),
                $data['id'] ?? null
            ]);
            
            // Atualizar assinatura
            $stmt = $this->db->prepare("
                UPDATE subscriptions 
                SET status = 'ativa',
                    data_vencimento = DATE_ADD(data_vencimento, INTERVAL 30 DAY),
                    ultimo_pagamento_id = ?,
                    proximo_pagamento = DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                WHERE tenant_id = ? AND status IN ('pendente', 'ativa')
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$data['id'] ?? null, $tenant_id]);
            
            // Ativar tenant no plano Pro
            $stmt = $this->db->prepare("
                UPDATE tenants 
                SET status = 'ativo',
                    plano = 'pro',
                    limite_estabelecimentos = NULL,
                    limite_consignacoes_por_estabelecimento = NULL,
                    data_vencimento = DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                WHERE id = ?
            ");
            $stmt->execute([$tenant_id]);
            
            $this->db->commit();
            
            // TODO: Enviar email de confirmação
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erro ao processar pagamento: " . $e->getMessage());
            return false;
        }
    }
    
    private function handlePaymentFailed($data) {
        $metadata = $data['metadata'] ?? [];
        $tenant_id = $metadata['tenant_id'] ?? null;
        
        if (!$tenant_id) return false;
        
        // Atualizar transação
        $stmt = $this->db->prepare("
            UPDATE payment_transactions 
            SET status = 'cancelado',
                webhook_data = ?
            WHERE pagou_transaction_id = ?
        ");
        $stmt->execute([
            json_encode($data),
            $data['id'] ?? null
        ]);
        
        // TODO: Enviar email de falha
        
        return true;
    }
    
    private function handlePaymentRefunded($data) {
        // Lógica de reembolso
        return true;
    }
    
    private function handleSubscriptionCancelled($data) {
        $metadata = $data['metadata'] ?? [];
        $tenant_id = $metadata['tenant_id'] ?? null;
        
        if (!$tenant_id) return false;
        
        return $this->cancelSubscription($tenant_id)['success'];
    }
    
    private function getOrCreateCustomer($tenant) {
        // Verificar se já existe
        $stmt = $this->db->prepare("
            SELECT pagou_customer_id 
            FROM subscriptions 
            WHERE tenant_id = ? AND pagou_customer_id IS NOT NULL
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$tenant['id']]);
        $existing = $stmt->fetch();
        
        if ($existing && $existing['pagou_customer_id']) {
            return ['id' => $existing['pagou_customer_id']];
        }
        
        // Criar novo customer
        $customer_data = [
            'name' => $tenant['nome_empresa'],
            'email' => $tenant['email_principal'],
            'document' => $tenant['documento'] ?? null,
            'phone' => $tenant['telefone'] ?? null
        ];
        
        $response = $this->makeRequest('POST', '/customers', $customer_data);
        
        if ($response['success']) {
            return ['id' => $response['data']['id']];
        }
        
        throw new Exception("Erro ao criar customer no Pagou");
    }
    
    private function saveSubscription($tenant_id, $plan_id, $pagou_data, $customer_id, $forma_pagamento) {
        $stmt = $this->db->prepare("
            INSERT INTO subscriptions (
                tenant_id,
                plan_id,
                status,
                data_inicio,
                data_vencimento,
                pagou_subscription_id,
                pagou_customer_id,
                valor_mensal,
                forma_pagamento,
                proximo_pagamento
            ) VALUES (?, ?, 'pendente', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, ?, 20.00, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY))
        ");
        
        $stmt->execute([
            $tenant_id,
            $plan_id,
            $pagou_data['id'] ?? null,
            $customer_id,
            $forma_pagamento
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function saveTransaction($subscription_id, $tenant_id, $pagou_data, $valor, $forma_pagamento) {
        $stmt = $this->db->prepare("
            INSERT INTO payment_transactions (
                subscription_id,
                tenant_id,
                pagou_transaction_id,
                valor,
                status,
                forma_pagamento,
                data_vencimento,
                payment_url,
                pix_code,
                barcode,
                webhook_data
            ) VALUES (?, ?, ?, ?, 'pendente', ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $subscription_id,
            $tenant_id,
            $pagou_data['id'] ?? null,
            $valor,
            $forma_pagamento,
            $pagou_data['due_date'] ?? null,
            $pagou_data['payment_url'] ?? null,
            $pagou_data['pix_code'] ?? null,
            $pagou_data['barcode'] ?? null,
            json_encode($pagou_data)
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function getTenantData($tenant_id) {
        $stmt = $this->db->prepare("SELECT * FROM tenants WHERE id = ?");
        $stmt->execute([$tenant_id]);
        return $stmt->fetch();
    }
    
    private function getPlanData($slug) {
        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->base_url . $endpoint;
        
        $curl = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
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
            'http_code' => $http_code,
            'error' => $http_code >= 400 ? ($decoded['message'] ?? 'Erro desconhecido') : null
        ];
    }
}
