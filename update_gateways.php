<?php
/**
 * Atualizar tabela payment_gateways
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once 'config/database.php';

echo "==============================================\n";
echo "   ATUALIZAÇÃO: PAYMENT_GATEWAYS\n";
echo "==============================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "✅ Conectado ao banco: " . DB_NAME . "\n";
    echo "✅ Host: " . DB_HOST . "\n\n";
    
    // 1. Adicionar coluna configuracao
    echo "1. Adicionando coluna 'configuracao'...\n";
    try {
        $db->exec("ALTER TABLE `payment_gateways` ADD COLUMN `configuracao` TEXT NULL COMMENT 'Configuração JSON (api_key, ambiente, etc)'");
        echo "   ✅ Coluna adicionada\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ️  Coluna já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Adicionar coluna configurado
    echo "\n2. Adicionando coluna 'configurado'...\n";
    try {
        $db->exec("ALTER TABLE `payment_gateways` ADD COLUMN `configurado` TINYINT(1) DEFAULT 0 COMMENT 'Se o gateway foi configurado'");
        echo "   ✅ Coluna adicionada\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ️  Coluna já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Adicionar coluna slug
    echo "\n3. Adicionando coluna 'slug'...\n";
    try {
        $db->exec("ALTER TABLE `payment_gateways` ADD COLUMN `slug` VARCHAR(50) NULL COMMENT 'Identificador único do gateway'");
        echo "   ✅ Coluna adicionada\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ️  Coluna já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Atualizar slugs
    echo "\n4. Atualizando slugs dos gateways...\n";
    $db->exec("UPDATE `payment_gateways` SET `slug` = 'pagou' WHERE `nome` = 'Pagou.com.br'");
    $db->exec("UPDATE `payment_gateways` SET `slug` = 'mercadopago' WHERE `nome` = 'Mercado Pago'");
    $db->exec("UPDATE `payment_gateways` SET `slug` = 'pagseguro' WHERE `nome` = 'PagSeguro'");
    $db->exec("UPDATE `payment_gateways` SET `slug` = 'stripe' WHERE `nome` = 'Stripe'");
    echo "   ✅ Slugs atualizados\n";
    
    // 5. Adicionar índice
    echo "\n5. Adicionando índice no slug...\n";
    try {
        $db->exec("ALTER TABLE `payment_gateways` ADD UNIQUE KEY `idx_slug` (`slug`)");
        echo "   ✅ Índice adicionado\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "   ℹ️  Índice já existe\n";
        }
    }
    
    echo "\n==============================================\n";
    echo "   🎉 ATUALIZAÇÃO CONCLUÍDA!\n";
    echo "==============================================\n\n";
    
    // Verificar
    $stmt = $db->query("SELECT * FROM payment_gateways");
    $gateways = $stmt->fetchAll();
    
    echo "Gateways cadastrados:\n";
    foreach ($gateways as $gw) {
        echo "  - {$gw['nome']} (slug: {$gw['slug']}) - ";
        echo $gw['ativo'] ? '✅ Ativo' : '❌ Inativo';
        echo $gw['configurado'] ? ' | ✓ Configurado' : ' | ⚠️  Não configurado';
        echo "\n";
    }
    
    echo "\n📋 Agora você pode configurar os gateways no painel admin!\n";
    echo "   Acesse: /admin/gateways.php\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}
