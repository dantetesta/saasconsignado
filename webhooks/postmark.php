<?php
/**
 * Webhook Postmark
 * 
 * Recebe notificações de verificação de sender signature
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

// Não requer sessão
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/MultiTenantEmailManager.php';

// Log de requisição
$log_file = __DIR__ . '/../logs/postmark_webhook.log';
$log_dir = dirname($log_file);
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Capturar payload
$payload_raw = file_get_contents('php://input');
$payload = json_decode($payload_raw, true);

// Logar requisição
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Webhook recebido:\n" . $payload_raw . "\n\n", FILE_APPEND);

// Verificar se é JSON válido
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    file_put_contents($log_file, "ERRO: JSON inválido\n\n", FILE_APPEND);
    exit('Invalid JSON');
}

try {
    // Processar webhook
    $emailManager = new MultiTenantEmailManager();
    $result = $emailManager->processWebhook($payload);
    
    if ($result) {
        http_response_code(200);
        file_put_contents($log_file, "SUCCESS: Webhook processado\n\n", FILE_APPEND);
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        file_put_contents($log_file, "ERROR: Falha ao processar webhook\n\n", FILE_APPEND);
        echo json_encode(['status' => 'error']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    file_put_contents($log_file, "EXCEPTION: " . $e->getMessage() . "\n\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
