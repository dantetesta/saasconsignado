<?php
/**
 * Instalar Sistema de Assinatura no Banco REMOTO
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once 'config/database.php';

echo "==============================================\n";
echo "   INSTALAÇÃO REMOTA: ASSINATURA\n";
echo "==============================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "✅ Conectado ao banco: " . DB_NAME . "\n";
    echo "✅ Host: " . DB_HOST . "\n\n";
    
    // 1. Criar tabela subscription_payments
    echo "1. Criando tabela subscription_payments...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `subscription_payments` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `tenant_id` INT NOT NULL,
          `charge_id` VARCHAR(255) NOT NULL COMMENT 'UUID do PIX na API Pagou',
          `amount` DECIMAL(10,2) NOT NULL DEFAULT 20.00,
          `status` ENUM('pending', 'paid', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
          `paid_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data do pagamento REAL',
          `expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data de expiração do plano',
          `qrcode_data` TEXT NULL COMMENT 'Código PIX copia e cola',
          `qrcode_image` LONGTEXT NULL COMMENT 'QR Code em base64',
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          
          KEY `idx_tenant` (`tenant_id`),
          KEY `idx_charge` (`charge_id`),
          KEY `idx_status` (`status`),
          KEY `idx_expires` (`expires_at`),
          
          FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pagamentos de assinatura mensal'
    ");
    echo "   ✅ Tabela criada!\n\n";
    
    // 2. Adicionar colunas em tenants
    echo "2. Adicionando colunas em tenants...\n";
    
    try {
        $db->exec("ALTER TABLE `tenants` ADD COLUMN `subscription_expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data de expiração do plano Pro'");
        echo "   ✅ Coluna subscription_expires_at adicionada\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ️  Coluna subscription_expires_at já existe\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $db->exec("ALTER TABLE `tenants` ADD COLUMN `subscription_status` ENUM('active', 'expiring_soon', 'expired', 'cancelled') DEFAULT 'expired' COMMENT 'Status da assinatura'");
        echo "   ✅ Coluna subscription_status adicionada\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ️  Coluna subscription_status já existe\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $db->exec("ALTER TABLE `tenants` ADD COLUMN `last_payment_id` INT NULL COMMENT 'ID do último pagamento'");
        echo "   ✅ Coluna last_payment_id adicionada\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ️  Coluna last_payment_id já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Adicionar índices
    echo "\n3. Adicionando índices...\n";
    
    try {
        $db->exec("ALTER TABLE `tenants` ADD KEY `idx_subscription_expires` (`subscription_expires_at`)");
        echo "   ✅ Índice idx_subscription_expires adicionado\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "   ℹ️  Índice idx_subscription_expires já existe\n";
        }
    }
    
    try {
        $db->exec("ALTER TABLE `tenants` ADD KEY `idx_subscription_status` (`subscription_status`)");
        echo "   ✅ Índice idx_subscription_status adicionado\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "   ℹ️  Índice idx_subscription_status já existe\n";
        }
    }
    
    echo "\n==============================================\n";
    echo "   🎉 INSTALAÇÃO CONCLUÍDA!\n";
    echo "==============================================\n\n";
    
    // Verificar
    $stmt = $db->query("SHOW TABLES LIKE 'subscription_payments'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Tabela 'subscription_payments' verificada\n";
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM subscription_payments");
        $count = $stmt->fetch()['total'];
        echo "✓ Pagamentos: {$count}\n\n";
    }
    
    echo "📋 Sistema de Assinatura Recorrente instalado!\n";
    echo "   - Pagamento via PIX (R$ 20/mês)\n";
    echo "   - Verificação automática\n";
    echo "   - Notificações de vencimento\n";
    echo "   - Renovação antecipada\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}
