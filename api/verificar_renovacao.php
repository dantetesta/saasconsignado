<?php
/**
 * API: Verificar Renovação de Assinatura
 * 
 * IMPORTANTE: Adiciona 30 dias à data de expiração atual
 * (não sobrescreve, para não perder dias pagos)
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
        
        // Buscar data de expiração atual do tenant
        $stmt = $db->prepare("SELECT subscription_expires_at FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $tenant = $stmt->fetch();
        
        // Calcular nova data de expiração
        // IMPORTANTE: Adiciona 30 dias à data atual, não sobrescreve
        $currentExpires = $tenant['subscription_expires_at'];
        
        if ($currentExpires && strtotime($currentExpires) > time()) {
            // Ainda tem dias válidos: adicionar 30 dias à data de expiração atual
            $newExpires = date('Y-m-d H:i:s', strtotime($currentExpires . ' +30 days'));
        } else {
            // Já expirou: adicionar 30 dias a partir de agora
            $newExpires = date('Y-m-d H:i:s', strtotime('+30 days'));
        }
        
        // Atualizar pagamento no banco
        $stmt = $db->prepare("
            UPDATE subscription_payments 
            SET status = 'paid', 
                paid_at = NOW(),
                expires_at = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newExpires, $payment['id']]);
        
        // Atualizar tenant
        $stmt = $db->prepare("
            UPDATE tenants 
            SET plano = 'pro',
                subscription_expires_at = ?,
                subscription_status = 'active',
                last_payment_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$newExpires, $payment['id'], $tenantId]);
        
        // Criar notificação
        require_once '../classes/Notification.php';
        $notification = new Notification();
        
        $diasAdicionados = 30;
        if ($currentExpires && strtotime($currentExpires) > time()) {
            $diff = (strtotime($newExpires) - strtotime($currentExpires)) / 86400;
            $diasAdicionados = round($diff);
        }
        
        $notification->create(
            $tenantId,
            'success',
            '✅ Assinatura Renovada!',
            "Pagamento confirmado! Seu plano foi renovado por mais {$diasAdicionados} dias. Nova data de expiração: " . date('d/m/Y', strtotime($newExpires)),
            null,
            false
        );
        
        echo json_encode([
            'pago' => true,
            'paid_at' => $result['paid_at'],
            'new_expires_at' => $newExpires
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
