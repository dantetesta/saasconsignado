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
    
    // Verificar na API Pagou
    $pagouAPI = new PagouAPI();
    $result = $pagouAPI->verificarPagamento($chargeId);
    
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
