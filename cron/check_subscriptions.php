<?php
/**
 * Cron Job: Verificar Assinaturas e Enviar Notificações
 * 
 * Executar diariamente via crontab:
 * 0 9 * * * php /caminho/cron/check_subscriptions.php
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';

echo "==============================================\n";
echo "   VERIFICAÇÃO DE ASSINATURAS\n";
echo "==============================================\n";
echo date('Y-m-d H:i:s') . "\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $notification = new Notification();
    
    // 1. Verificar assinaturas que expiram em 5 dias
    echo "1. Verificando assinaturas que expiram em 5 dias...\n";
    $stmt = $db->query("
        SELECT t.id, t.nome_empresa, t.email_principal, t.subscription_expires_at
        FROM tenants t
        WHERE t.plano = 'pro'
        AND t.subscription_status = 'active'
        AND t.subscription_expires_at IS NOT NULL
        AND DATE(t.subscription_expires_at) = DATE_ADD(CURDATE(), INTERVAL 5 DAY)
    ");
    
    $expiring5 = $stmt->fetchAll();
    echo "   Encontradas: " . count($expiring5) . "\n";
    
    foreach ($expiring5 as $tenant) {
        // Atualizar status
        $db->prepare("UPDATE tenants SET subscription_status = 'expiring_soon' WHERE id = ?")
           ->execute([$tenant['id']]);
        
        // Criar notificação
        $notification->create(
            $tenant['id'],
            'warning',
            '⏰ Seu plano expira em 5 dias',
            'Seu Plano Pro expira em ' . date('d/m/Y', strtotime($tenant['subscription_expires_at'])) . '. Renove agora para continuar com acesso ilimitado!',
            null,
            false
        );
        
        echo "   ✓ Notificação enviada: {$tenant['nome_empresa']}\n";
    }
    
    // 2. Verificar assinaturas que expiram em 3 dias
    echo "\n2. Verificando assinaturas que expiram em 3 dias...\n";
    $stmt = $db->query("
        SELECT t.id, t.nome_empresa, t.subscription_expires_at
        FROM tenants t
        WHERE t.plano = 'pro'
        AND t.subscription_status IN ('active', 'expiring_soon')
        AND t.subscription_expires_at IS NOT NULL
        AND DATE(t.subscription_expires_at) = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ");
    
    $expiring3 = $stmt->fetchAll();
    echo "   Encontradas: " . count($expiring3) . "\n";
    
    foreach ($expiring3 as $tenant) {
        $notification->create(
            $tenant['id'],
            'warning',
            '⚠️ Seu plano expira em 3 dias!',
            'Faltam apenas 3 dias para seu Plano Pro expirar. Renove agora para não perder o acesso!',
            null,
            false
        );
        
        echo "   ✓ Notificação enviada: {$tenant['nome_empresa']}\n";
    }
    
    // 3. Verificar assinaturas que expiram amanhã
    echo "\n3. Verificando assinaturas que expiram amanhã...\n";
    $stmt = $db->query("
        SELECT t.id, t.nome_empresa, t.subscription_expires_at
        FROM tenants t
        WHERE t.plano = 'pro'
        AND t.subscription_status IN ('active', 'expiring_soon')
        AND t.subscription_expires_at IS NOT NULL
        AND DATE(t.subscription_expires_at) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ");
    
    $expiring1 = $stmt->fetchAll();
    echo "   Encontradas: " . count($expiring1) . "\n";
    
    foreach ($expiring1 as $tenant) {
        $notification->create(
            $tenant['id'],
            'error',
            '🚨 Seu plano expira AMANHÃ!',
            'Seu Plano Pro expira amanhã! Renove agora para continuar usando todas as funcionalidades.',
            null,
            false
        );
        
        echo "   ✓ Notificação enviada: {$tenant['nome_empresa']}\n";
    }
    
    // 4. Expirar assinaturas vencidas
    echo "\n4. Expirando assinaturas vencidas...\n";
    $stmt = $db->query("
        SELECT t.id, t.nome_empresa, t.subscription_expires_at
        FROM tenants t
        WHERE t.plano = 'pro'
        AND t.subscription_expires_at IS NOT NULL
        AND DATE(t.subscription_expires_at) < CURDATE()
    ");
    
    $expired = $stmt->fetchAll();
    echo "   Encontradas: " . count($expired) . "\n";
    
    foreach ($expired as $tenant) {
        // Voltar para plano Free
        $db->prepare("
            UPDATE tenants 
            SET plano = 'free',
                subscription_status = 'expired',
                subscription_expires_at = NULL
            WHERE id = ?
        ")->execute([$tenant['id']]);
        
        // Criar notificação
        $notification->create(
            $tenant['id'],
            'error',
            '❌ Seu plano expirou',
            'Seu Plano Pro expirou e você foi movido para o Plano Free. Renove agora para recuperar o acesso ilimitado!',
            null,
            false
        );
        
        echo "   ✓ Expirado: {$tenant['nome_empresa']}\n";
    }
    
    echo "\n==============================================\n";
    echo "   ✅ VERIFICAÇÃO CONCLUÍDA!\n";
    echo "==============================================\n";
    echo "Resumo:\n";
    echo "- Expirando em 5 dias: " . count($expiring5) . "\n";
    echo "- Expirando em 3 dias: " . count($expiring3) . "\n";
    echo "- Expirando amanhã: " . count($expiring1) . "\n";
    echo "- Expiradas: " . count($expired) . "\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}
