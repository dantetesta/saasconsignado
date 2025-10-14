<?php
/**
 * API: Obter Notificações do Tenant
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
    
    // Buscar notificações
    $notifications = $notification->getByTenant($tenantId, 50);
    
    // Formatar datas
    foreach ($notifications as &$notif) {
        $data = new DateTime($notif['criado_em']);
        $notif['data_formatada'] = $data->format('d/m/Y H:i');
    }
    
    // Contar não lidas
    $unreadCount = $notification->countUnread($tenantId);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
