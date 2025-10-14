<?php
/**
 * Executar Migração do Painel Admin
 * Usa a configuração existente do database.php
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

// Desabilitar limite de tempo
set_time_limit(0);

// Incluir configuração do banco
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migração do Painel Admin</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-50 p-8'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-xl shadow-lg p-8'>
            <h1 class='text-3xl font-bold text-gray-900 mb-6'>🚀 Migração do Painel Admin</h1>
";

try {
    // Conectar ao banco
    echo "<div class='mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded'>
            <p class='text-blue-800'>📡 Conectando ao banco de dados...</p>
            <p class='text-sm text-blue-600 mt-1'>Host: " . DB_HOST . "</p>
            <p class='text-sm text-blue-600'>Banco: " . DB_NAME . "</p>
          </div>";
    
    $db = Database::getInstance()->getConnection();
    
    echo "<div class='mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded'>
            <p class='text-green-800'>✅ Conectado com sucesso!</p>
          </div>";
    
    // Ler arquivo SQL
    $sqlFile = __DIR__ . '/migrations/create_admin_panel.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("❌ Arquivo de migração não encontrado: {$sqlFile}");
    }
    
    echo "<div class='mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded'>
            <p class='text-blue-800'>📄 Lendo arquivo de migração...</p>
            <p class='text-sm text-blue-600 mt-1'>{$sqlFile}</p>
          </div>";
    
    $sql = file_get_contents($sqlFile);
    
    // Verificar se tabelas já existem
    $stmt = $db->query("SHOW TABLES LIKE 'super_admins'");
    $tabelasExistem = $stmt->rowCount() > 0;
    
    if ($tabelasExistem) {
        echo "<div class='mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded'>
                <p class='text-yellow-800'>⚠️ Tabelas já existem! Pulando criação...</p>
              </div>";
    } else {
        // Executar SQL
        echo "<div class='mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded'>
                <p class='text-blue-800'>⚙️ Executando migração...</p>
              </div>";
        
        $db->exec($sql);
        
        echo "<div class='mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded'>
                <p class='text-green-800 font-bold'>✅ Migração executada com sucesso!</p>
              </div>";
    }
    
    // Verificar tabelas criadas
    echo "<div class='mb-4 p-4 bg-gray-50 border-l-4 border-gray-400 rounded'>
            <p class='font-bold text-gray-800 mb-3'>🔍 Verificando tabelas criadas:</p>
            <ul class='space-y-2'>";
    
    $tables = ['super_admins', 'admin_logs', 'payment_gateways'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "<li class='text-green-600'>✓ <code class='bg-gray-100 px-2 py-1 rounded'>{$table}</code></li>";
        } else {
            echo "<li class='text-red-600'>✗ <code class='bg-gray-100 px-2 py-1 rounded'>{$table}</code> (não encontrada)</li>";
        }
    }
    
    echo "</ul></div>";
    
    // Verificar super admin criado
    echo "<div class='mb-4 p-4 bg-gray-50 border-l-4 border-gray-400 rounded'>
            <p class='font-bold text-gray-800 mb-3'>👤 Verificando super admin:</p>";
    
    $stmt = $db->query("SELECT id, nome, email FROM super_admins LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<div class='bg-green-100 p-3 rounded'>
                <p class='text-green-800'>✓ Super admin criado com sucesso!</p>
                <p class='text-sm text-green-700 mt-1'>ID: {$admin['id']}</p>
                <p class='text-sm text-green-700'>Nome: {$admin['nome']}</p>
                <p class='text-sm text-green-700'>Email: {$admin['email']}</p>
              </div>";
    } else {
        echo "<p class='text-red-600'>✗ Nenhum super admin encontrado</p>";
    }
    
    echo "</div>";
    
    // Verificar gateways
    echo "<div class='mb-4 p-4 bg-gray-50 border-l-4 border-gray-400 rounded'>
            <p class='font-bold text-gray-800 mb-3'>💳 Verificando gateways:</p>";
    
    $stmt = $db->query("SELECT nome, ativo FROM payment_gateways ORDER BY ordem");
    $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($gateways)) {
        echo "<ul class='space-y-1'>";
        foreach ($gateways as $gateway) {
            $status = $gateway['ativo'] ? '🟢 Ativo' : '⚪ Inativo';
            echo "<li class='text-gray-700'>✓ {$gateway['nome']} ({$status})</li>";
        }
        echo "</ul>";
        echo "<p class='text-sm text-gray-600 mt-2'>Total: " . count($gateways) . " gateways cadastrados</p>";
    } else {
        echo "<p class='text-red-600'>✗ Nenhum gateway encontrado</p>";
    }
    
    echo "</div>";
    
    // Sucesso final
    echo "<div class='p-6 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl text-white mb-6'>
            <h2 class='text-2xl font-bold mb-3'>🎉 Instalação Concluída com Sucesso!</h2>
            <p class='mb-4'>O painel administrativo foi instalado e está pronto para uso.</p>
          </div>";
    
    // Credenciais
    echo "<div class='p-6 bg-blue-50 border-2 border-blue-200 rounded-xl mb-6'>
            <h3 class='font-bold text-blue-900 mb-3'>🔑 Credenciais de Acesso:</h3>
            <div class='bg-white p-4 rounded-lg space-y-2'>
                <p class='text-gray-800'><strong>URL:</strong> <a href='/admin/login.php' class='text-blue-600 hover:underline'>/admin/login.php</a></p>
                <p class='text-gray-800'><strong>Email:</strong> <code class='bg-gray-100 px-2 py-1 rounded'>admin@dantetesta.com.br</code></p>
                <p class='text-gray-800'><strong>Senha:</strong> <code class='bg-gray-100 px-2 py-1 rounded'>admin123</code></p>
            </div>
            <div class='mt-4 p-3 bg-red-50 border border-red-200 rounded'>
                <p class='text-red-800 font-bold'>⚠️ IMPORTANTE: Altere a senha após o primeiro login!</p>
            </div>
          </div>";
    
    // Botões de ação
    echo "<div class='flex gap-4'>
            <a href='/admin/login.php' class='flex-1 bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-bold py-3 px-6 rounded-lg hover:from-blue-700 hover:to-emerald-700 transition text-center'>
                Acessar Painel Admin
            </a>
            <a href='/' class='px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition text-center'>
                Voltar ao Site
            </a>
          </div>";
    
} catch (PDOException $e) {
    echo "<div class='p-6 bg-red-50 border-l-4 border-red-500 rounded'>
            <h3 class='font-bold text-red-900 mb-2'>❌ Erro de Banco de Dados</h3>
            <p class='text-red-700'>{$e->getMessage()}</p>
            <details class='mt-3'>
                <summary class='cursor-pointer text-red-600 text-sm'>Ver detalhes técnicos</summary>
                <pre class='mt-2 p-3 bg-red-100 rounded text-xs overflow-auto'>{$e->getTraceAsString()}</pre>
            </details>
          </div>";
    
    echo "<div class='mt-4'>
            <button onclick='location.reload()' class='px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition'>
                Tentar Novamente
            </button>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='p-6 bg-red-50 border-l-4 border-red-500 rounded'>
            <h3 class='font-bold text-red-900 mb-2'>❌ Erro</h3>
            <p class='text-red-700'>{$e->getMessage()}</p>
          </div>";
    
    echo "<div class='mt-4'>
            <button onclick='location.reload()' class='px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition'>
                Tentar Novamente
            </button>
          </div>";
}

echo "
            <div class='mt-8 text-center text-sm text-gray-500'>
                <p>Desenvolvido por <a href='https://dantetesta.com.br' target='_blank' class='text-blue-600 hover:underline'>Dante Testa</a></p>
            </div>
        </div>
    </div>
</body>
</html>";
