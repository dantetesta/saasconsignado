<?php
/**
 * API: Deletar Notificação
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

session_start();
require_once '../config/database.php';
require_once '../classes/Notification.php';

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['tenant_id'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $notificationId = $data['notification_id'] ?? null;
    
    if (!$notificationId) {
        throw new Exception('ID da notificação não fornecido');
    }
    
    // Verificar se a notificação pertence ao tenant
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT tenant_id FROM notifications WHERE id = ?");
    $stmt->execute([$notificationId]);
    $notif = $stmt->fetch();
    
    if (!$notif || $notif['tenant_id'] != $_SESSION['tenant_id']) {
        throw new Exception('Notificação não encontrada ou sem permissão');
    }
    
    $notification = new Notification();
    
    if ($notification->delete($notificationId)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Erro ao deletar notificação']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
