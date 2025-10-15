<?php
/**
 * Script de Configuração de Variáveis de Ambiente
 * 
 * Este script cria o arquivo .env com as configurações atuais
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.1.0
 */

echo "🔧 Configurando variáveis de ambiente...\n\n";

// Verificar se .env já existe
if (file_exists(__DIR__ . '/.env')) {
    echo "⚠️  Arquivo .env já existe!\n";
    echo "Deseja sobrescrever? (s/N): ";
    $handle = fopen("php://stdin", "r");
    $response = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($response) !== 's') {
        echo "❌ Operação cancelada.\n";
        exit(1);
    }
}

// Configurações atuais do sistema (migrar do hardcode)
$currentConfig = [
    'DB_HOST' => '187.33.241.61',
    'DB_USER' => 'amopipocagourmet_saasconsignado',
    'DB_PASS' => 'amopipocagourmet_saasconsignado',
    'DB_NAME' => 'amopipocagourmet_saasconsignado',
    'DB_CHARSET' => 'utf8mb4',
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_URL' => 'https://seudominio.com.br',
    'APP_KEY' => bin2hex(random_bytes(16)),
    'CSRF_SECRET' => bin2hex(random_bytes(16)),
    'PAGOU_API_KEY' => 'sua_api_key_pagou',
    'PAGOU_ENVIRONMENT' => 'production',
    'POSTMARK_SERVER_TOKEN' => 'seu_server_token',
    'POSTMARK_ACCOUNT_TOKEN' => 'seu_account_token',
    'TURNSTILE_SITE_KEY' => '0x4AAAAAAB46BTyXNMqGjT81',
    'TURNSTILE_SECRET_KEY' => '0x4AAAAAAB46BRmKxNjsji2mSoclu37mcaw',
    'ALERT_EMAIL' => 'admin@seudominio.com.br',
    'SLACK_WEBHOOK_URL' => '',
    'BACKUP_ENABLED' => 'true',
    'BACKUP_RETENTION_DAYS' => '30',
    'AWS_S3_BUCKET' => '',
    'AWS_ACCESS_KEY' => '',
    'AWS_SECRET_KEY' => ''
];

// Gerar conteúdo do .env
$envContent = "# Configurações do Sistema SaaS Sisteminha\n";
$envContent .= "# Autor: Dante Testa <https://dantetesta.com.br>\n";
$envContent .= "# Versão: 2.1.0\n";
$envContent .= "# Gerado em: " . date('Y-m-d H:i:s') . "\n\n";

$envContent .= "# ============================================\n";
$envContent .= "# BANCO DE DADOS\n";
$envContent .= "# ============================================\n";
$envContent .= "DB_HOST={$currentConfig['DB_HOST']}\n";
$envContent .= "DB_USER={$currentConfig['DB_USER']}\n";
$envContent .= "DB_PASS={$currentConfig['DB_PASS']}\n";
$envContent .= "DB_NAME={$currentConfig['DB_NAME']}\n";
$envContent .= "DB_CHARSET={$currentConfig['DB_CHARSET']}\n\n";

$envContent .= "# ============================================\n";
$envContent .= "# AMBIENTE\n";
$envContent .= "# ============================================\n";
$envContent .= "APP_ENV={$currentConfig['APP_ENV']}\n";
$envContent .= "APP_DEBUG={$currentConfig['APP_DEBUG']}\n";
$envContent .= "APP_URL={$currentConfig['APP_URL']}\n\n";

$envContent .= "# ============================================\n";
$envContent .= "# SEGURANÇA\n";
$envContent .= "# ============================================\n";
$envContent .= "APP_KEY={$currentConfig['APP_KEY']}\n";
$envContent .= "CSRF_SECRET={$currentConfig['CSRF_SECRET']}\n\n";

$envContent .= "# ============================================\n";
$envContent .= "# PAGOU API\n";
$envContent .= "# ============================================\n";
$envContent .= "PAGOU_API_KEY={$currentConfig['PAGOU_API_KEY']}\n";
$envContent .= "PAGOU_ENVIRONMENT={$currentConfig['PAGOU_ENVIRONMENT']}\n\n";

$envContent .= "# ============================================\n";
$envContent .= "# POSTMARK EMAIL\n";
$envContent .= "# ============================================\n";
$envContent .= "POSTMARK_SERVER_TOKEN={$currentConfig['POSTMARK_SERVER_TOKEN']}\n";
$envContent .= "POSTMARK_ACCOUNT_TOKEN={$currentConfig['POSTMARK_ACCOUNT_TOKEN']}\n\n";

$envContent .= "# ============================================\n";
$envContent .= "# CLOUDFLARE TURNSTILE\n";
$envContent .= "# ============================================\n";
$envContent .= "TURNSTILE_SITE_KEY={$currentConfig['TURNSTILE_SITE_KEY']}\n";
$envContent .= "TURNSTILE_SECRET_KEY={$currentConfig['TURNSTILE_SECRET_KEY']}\n\n";

$envContent .= "# ============================================\n";
$envContent .= "# MONITORAMENTO\n";
$envContent .= "# ============================================\n";
$envContent .= "ALERT_EMAIL={$currentConfig['ALERT_EMAIL']}\n";
$envContent .= "SLACK_WEBHOOK_URL={$currentConfig['SLACK_WEBHOOK_URL']}\n\n";

$envContent .= "# ============================================\n";
$envContent .= "# BACKUP\n";
$envContent .= "# ============================================\n";
$envContent .= "BACKUP_ENABLED={$currentConfig['BACKUP_ENABLED']}\n";
$envContent .= "BACKUP_RETENTION_DAYS={$currentConfig['BACKUP_RETENTION_DAYS']}\n";
$envContent .= "AWS_S3_BUCKET={$currentConfig['AWS_S3_BUCKET']}\n";
$envContent .= "AWS_ACCESS_KEY={$currentConfig['AWS_ACCESS_KEY']}\n";
$envContent .= "AWS_SECRET_KEY={$currentConfig['AWS_SECRET_KEY']}\n";

// Salvar arquivo .env
if (file_put_contents(__DIR__ . '/.env', $envContent)) {
    echo "✅ Arquivo .env criado com sucesso!\n\n";
    
    echo "📋 PRÓXIMOS PASSOS:\n";
    echo "1. Edite o arquivo .env com suas credenciais reais\n";
    echo "2. Configure as APIs (Pagou, Postmark) no .env\n";
    echo "3. Defina o APP_URL correto\n";
    echo "4. Configure o email para alertas\n\n";
    
    echo "⚠️  IMPORTANTE:\n";
    echo "- O arquivo .env está no .gitignore (não será commitado)\n";
    echo "- Mantenha este arquivo seguro e privado\n";
    echo "- Faça backup das configurações em local seguro\n\n";
    
    echo "🔧 Para testar a configuração, acesse o sistema normalmente.\n";
    echo "Se houver erro, verifique os logs em logs/errors.log\n\n";
    
} else {
    echo "❌ Erro ao criar arquivo .env\n";
    echo "Verifique as permissões do diretório.\n";
    exit(1);
}

echo "🎉 Configuração de segurança implementada com sucesso!\n";
