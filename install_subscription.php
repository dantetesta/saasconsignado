<?php
/**
 * Instalar Sistema de Assinatura Recorrente
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once 'config/database.php';

echo "==============================================\n";
echo "   INSTALAÇÃO: SISTEMA DE ASSINATURA\n";
echo "==============================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "✅ Conectado ao banco: " . DB_NAME . "\n\n";
    
    // Ler e executar SQL
    $sql = file_get_contents('migrations/create_subscription_payments.sql');
    
    // Executar cada statement separadamente
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            try {
                $db->exec($statement);
            } catch (PDOException $e) {
                // Ignora erros de "já existe"
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate column') === false) {
                    throw $e;
                }
            }
        }
    }
    
    echo "✅ Tabela 'subscription_payments' criada!\n";
    echo "✅ Campos adicionados em 'tenants'!\n\n";
    
    // Verificar
    $stmt = $db->query("SHOW TABLES LIKE 'subscription_payments'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Tabela verificada no banco\n";
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM subscription_payments");
        $count = $stmt->fetch()['total'];
        echo "✓ Pagamentos cadastrados: {$count}\n\n";
    }
    
    // Verificar colunas em tenants
    $stmt = $db->query("SHOW COLUMNS FROM tenants LIKE 'subscription_expires_at'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Coluna 'subscription_expires_at' adicionada\n";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM tenants LIKE 'subscription_status'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Coluna 'subscription_status' adicionada\n\n";
    }
    
    echo "==============================================\n";
    echo "   🎉 INSTALAÇÃO CONCLUÍDA!\n";
    echo "==============================================\n\n";
    
    echo "📋 Sistema de Assinatura Recorrente:\n";
    echo "   - Pagamento via PIX (R$ 20/mês)\n";
    echo "   - Verificação automática de pagamento\n";
    echo "   - Notificações de vencimento (5 dias antes)\n";
    echo "   - Renovação antecipada\n";
    echo "   - Gestão automática de expiração\n\n";
    
    echo "🔑 API Pagou configurada:\n";
    echo "   - Token: 6476a737-7211-4e7c-ba1f-639eff09e270\n";
    echo "   - Ambiente: PRODUÇÃO\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}
