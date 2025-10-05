<?php
/**
 * Script para atualizar banco de dados remoto
 * Executa a migração create_admin_panel.sql no servidor remoto
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

// Configurações do banco remoto
$remoteConfig = [
    'host' => 'mysql.dantetesta.com.br',
    'dbname' => 'dantetesta_saas',
    'user' => 'dantetesta_admin',
    'pass' => '' // Será solicitado
];

echo "==============================================\n";
echo "   ATUALIZAÇÃO DO BANCO DE DADOS REMOTO\n";
echo "==============================================\n\n";

// Solicitar senha
echo "Digite a senha do banco remoto: ";
$remoteConfig['pass'] = trim(fgets(STDIN));

if (empty($remoteConfig['pass'])) {
    die("❌ Senha não pode ser vazia!\n");
}

try {
    // Conectar ao banco remoto
    echo "\n📡 Conectando ao banco remoto...\n";
    $dsn = "mysql:host={$remoteConfig['host']};dbname={$remoteConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $remoteConfig['user'], $remoteConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conectado com sucesso!\n\n";
    
    // Ler arquivo SQL
    $sqlFile = __DIR__ . '/migrations/create_admin_panel.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("❌ Arquivo de migração não encontrado: {$sqlFile}");
    }
    
    echo "📄 Lendo arquivo de migração...\n";
    $sql = file_get_contents($sqlFile);
    
    // Executar SQL
    echo "⚙️  Executando migração...\n\n";
    $pdo->exec($sql);
    
    echo "✅ Migração executada com sucesso!\n\n";
    
    // Verificar tabelas criadas
    echo "🔍 Verificando tabelas criadas:\n";
    $tables = ['super_admins', 'admin_logs', 'payment_gateways'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ {$table}\n";
        } else {
            echo "   ✗ {$table} (não encontrada)\n";
        }
    }
    
    // Verificar super admin criado
    echo "\n👤 Verificando super admin:\n";
    $stmt = $pdo->query("SELECT email FROM super_admins LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "   ✓ Super admin criado: {$admin['email']}\n";
    } else {
        echo "   ✗ Nenhum super admin encontrado\n";
    }
    
    // Verificar gateways
    echo "\n💳 Verificando gateways:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM payment_gateways");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ {$count['total']} gateways cadastrados\n";
    
    echo "\n==============================================\n";
    echo "   ✅ ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "==============================================\n\n";
    
    echo "🔑 Credenciais de acesso:\n";
    echo "   Email: admin@dantetesta.com.br\n";
    echo "   Senha: admin123\n";
    echo "   ⚠️  ALTERE A SENHA APÓS O PRIMEIRO LOGIN!\n\n";
    
    echo "🌐 Acesse: https://seu-dominio.com/admin/login.php\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Erro de conexão/execução: " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}
