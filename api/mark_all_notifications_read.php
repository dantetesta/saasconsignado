<?php
/**
 * API: Marcar Todas as Notificações como Lidas
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
    $notification = new Notification();
    $tenantId = $_SESSION['tenant_id'];
    
    if ($notification->markAllAsRead($tenantId)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Erro ao marcar notificações']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
