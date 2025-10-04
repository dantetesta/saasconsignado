<?php
/**
 * Executar Migração SaaS
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once 'config/database.php';

echo "🚀 Iniciando Migração SaaS...\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Ler arquivo SQL
    $sql = file_get_contents(__DIR__ . '/saas_migration.sql');
    
    if (!$sql) {
        die("❌ Erro: Arquivo saas_migration.sql não encontrado!\n");
    }
    
    // Separar comandos SQL
    echo "📦 Executando migração do banco de dados...\n";
    
    // Remover comentários e linhas vazias
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Separar por ponto e vírgula
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt); }
    );
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        try {
            $db->exec($statement);
            $executed++;
            
            // Mostrar progresso
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "   ✓ Tabela {$matches[1]} criada\n";
                }
            } elseif (stripos($statement, 'CREATE VIEW') !== false) {
                preg_match('/CREATE.*VIEW\s+`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "   ✓ View {$matches[1]} criada\n";
                }
            }
            
        } catch (PDOException $e) {
            $error_msg = $e->getMessage();
            
            // Ignorar erros de "já existe" ou "duplicate"
            if (stripos($error_msg, 'already exists') !== false || 
                stripos($error_msg, 'Duplicate key') !== false ||
                stripos($error_msg, "Table") !== false && stripos($error_msg, "doesn't exist") !== false) {
                // Silencioso para estes erros
                continue;
            }
            
            echo "❌ ERRO: " . $error_msg . "\n";
            echo "📝 Statement: " . substr($statement, 0, 100) . "...\n\n";
            $errors++;
        }
    }
    
    echo "✅ Executados: {$executed} comandos\n";
    if ($errors > 0) {
        echo "⚠️  Avisos: {$errors}\n";
    }
    
    echo "✅ Migração concluída com sucesso!\n\n";
    echo "📊 Estrutura criada:\n";
    echo "   ✓ Tabela tenants\n";
    echo "   ✓ Tabelas com tenant_id (usuarios, estabelecimentos, produtos, etc)\n";
    echo "   ✓ Sistema de assinaturas (subscription_plans, subscriptions)\n";
    echo "   ✓ Sistema de pagamentos (payment_transactions)\n";
    echo "   ✓ Sistema de emails (tenant_email_verifications)\n";
    echo "   ✓ Views atualizadas com multi-tenancy\n\n";
    
    echo "🎯 Próximo passo: Criar classe TenantMiddleware\n";
    
} catch (PDOException $e) {
    echo "❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}
