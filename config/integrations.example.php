<?php
/**
 * Configurações de Integrações Externas - EXEMPLO
 * 
 * Copie este arquivo para integrations.php e configure suas credenciais
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

// ============================================
// PAGOU.COM.BR - Pagamentos
// ============================================

// Ambiente: 'sandbox' para testes, 'production' para produção
define('PAGOU_ENVIRONMENT', 'sandbox');

// API Keys (obter em https://pagou.com.br/configuracoes/api)
define('PAGOU_API_KEY_SANDBOX', 'sua_chave_sandbox_aqui');
define('PAGOU_API_KEY_PRODUCTION', 'sua_chave_producao_aqui');

// URL base da API
define('PAGOU_API_URL_SANDBOX', 'https://sandbox.pagou.com.br/api/v1');
define('PAGOU_API_URL_PRODUCTION', 'https://api.pagou.com.br/v1');

// Webhook URL (será chamada pelo Pagou quando houver confirmação de pagamento)
define('PAGOU_WEBHOOK_URL', SITE_URL . '/webhooks/pagou.php');

// ============================================
// POSTMARK - Email Transacional
// ============================================

// API Tokens (obter em https://postmarkapp.com)
define('POSTMARK_ACCOUNT_TOKEN', 'sua_account_token_aqui');
define('POSTMARK_SERVER_TOKEN', 'sua_server_token_aqui');

// Email remetente verificado no Postmark
define('POSTMARK_FROM_EMAIL', 'sistema@seudominio.com.br');
define('POSTMARK_FROM_NAME', 'Sistema de Consignados');

// URL base da API
define('POSTMARK_API_URL', 'https://api.postmarkapp.com');

// Webhook URL (será chamada quando email for verificado)
define('POSTMARK_WEBHOOK_URL', SITE_URL . '/webhooks/postmark.php');

// ============================================
// HELPERS
// ============================================

/**
 * Obter API Key do Pagou baseado no ambiente
 */
function getPagouApiKey() {
    return PAGOU_ENVIRONMENT === 'production' 
        ? PAGOU_API_KEY_PRODUCTION 
        : PAGOU_API_KEY_SANDBOX;
}

/**
 * Obter URL base da API Pagou baseado no ambiente
 */
function getPagouApiUrl() {
    return PAGOU_ENVIRONMENT === 'production' 
        ? PAGOU_API_URL_PRODUCTION 
        : PAGOU_API_URL_SANDBOX;
}

/**
 * Verificar se integrações estão configuradas
 */
function checkIntegrationConfig() {
    $errors = [];
    
    // Verificar Pagou
    if (getPagouApiKey() === 'sua_chave_sandbox_aqui' || getPagouApiKey() === 'sua_chave_producao_aqui') {
        $errors[] = 'API Key do Pagou.com.br não configurada';
    }
    
    // Verificar Postmark
    if (POSTMARK_SERVER_TOKEN === 'sua_server_token_aqui') {
        $errors[] = 'Server Token do Postmark não configurado';
    }
    
    return [
        'configured' => empty($errors),
        'errors' => $errors
    ];
}
