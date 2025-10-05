<?php
/**
 * API: Marcar Notificação como Lida
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
    
    $notification = new Notification();
    
    if ($notification->markAsRead($notificationId)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Erro ao marcar como lida']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
