<?php
/**
 * API: Verificar Pagamento PIX
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

session_start();
require_once '../config/database.php';
require_once '../classes/PagouAPI.php';
require_once '../classes/PaymentCache.php';

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['tenant_id'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

try {
    $chargeId = $_GET['charge_id'] ?? '';
    
    if (empty($chargeId)) {
        throw new Exception('ID do pagamento não fornecido');
    }
    
    $db = Database::getInstance()->getConnection();
    $tenantId = $_SESSION['tenant_id'];
    
    // Verificar se o pagamento pertence ao tenant
    $stmt = $db->prepare("SELECT id, status FROM subscription_payments WHERE charge_id = ? AND tenant_id = ?");
    $stmt->execute([$chargeId, $tenantId]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        throw new Exception('Pagamento não encontrado');
    }
    
    // Se já foi pago, retornar true
    if ($payment['status'] === 'paid') {
        echo json_encode(['pago' => true]);
        exit;
    }
    
    // Verificar cache primeiro
    $cachedResult = PaymentCache::get($chargeId);
    if ($cachedResult !== null) {
        echo json_encode($cachedResult);
        exit;
    }
    
    // Verificar na API Pagou com timeout reduzido
    $pagouAPI = new PagouAPI();
    
    // Definir timeout menor para evitar travamentos
    ini_set('max_execution_time', 10);
    
    try {
        $result = $pagouAPI->verificarPagamento($chargeId);
        
        // Armazenar resultado no cache
        PaymentCache::set($chargeId, ['pago' => $result['pago']]);
        
    } catch (Exception $apiError) {
        // Em caso de erro na API, retornar não pago e cachear por menos tempo
        error_log("Erro na API Pagou: " . $apiError->getMessage());
        
        $result = ['pago' => false];
        // Cache por apenas 10 segundos em caso de erro
        PaymentCache::set($chargeId, $result);
    }
    
    if ($result['pago']) {
        // PAGAMENTO CONFIRMADO!
        
        // Atualizar pagamento no banco
        $stmt = $db->prepare("
            UPDATE subscription_payments 
            SET status = 'paid', 
                paid_at = NOW(),
                expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$payment['id']]);
        
        // Atualizar tenant para plano Pro
        $stmt = $db->prepare("
            UPDATE tenants 
            SET plano = 'pro',
                subscription_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY),
                subscription_status = 'active',
                last_payment_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$payment['id'], $tenantId]);
        
        // Criar notificação de boas-vindas
        require_once '../classes/Notification.php';
        $notification = new Notification();
        $notification->create(
            $tenantId,
            'success',
            '🎉 Bem-vindo ao Plano Pro!',
            'Seu pagamento foi confirmado! Agora você tem acesso a todas as funcionalidades ilimitadas por 30 dias.',
            null, // admin_id
            false // não enviar email
        );
        
        echo json_encode([
            'pago' => true,
            'paid_at' => $result['paid_at']
        ]);
    } else {
        echo json_encode(['pago' => false]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'pago' => false
    ]);
}
