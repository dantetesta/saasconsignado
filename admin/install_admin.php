<?php
/**
 * Instalador do Painel Administrativo
 * Executa a migração create_admin_panel.sql
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once '../config/database.php';

$success = false;
$error = '';
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Ler arquivo SQL
        $sqlFile = '../migrations/create_admin_panel.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception('Arquivo de migração não encontrado!');
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Executar SQL
        $db->exec($sql);
        
        $messages[] = '✅ Tabelas criadas: super_admins, admin_logs, payment_gateways';
        $messages[] = '✅ Super admin padrão criado';
        $messages[] = '✅ Gateways de pagamento cadastrados';
        $messages[] = '✅ Índices otimizados criados';
        
        $success = true;
        
    } catch (Exception $e) {
        $error = 'Erro na instalação: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Painel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-purple-900 to-pink-900 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-2xl">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                    <svg class="w-10 h-10 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Instalação do Painel Admin</h1>
                <p class="text-gray-600">Configure o painel administrativo do SaaS</p>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg mb-6">
                    <h3 class="font-bold text-green-900 mb-3">🎉 Instalação Concluída!</h3>
                    <ul class="space-y-2 text-sm text-green-800">
                        <?php foreach ($messages as $msg): ?>
                            <li><?php echo $msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <h3 class="font-bold text-blue-900 mb-3">🔑 Credenciais de Acesso:</h3>
                    <div class="space-y-2 text-sm text-blue-800">
                        <p><strong>Email:</strong> admin@dantetesta.com.br</p>
                        <p><strong>Senha:</strong> admin123</p>
                        <p class="text-red-600 font-bold mt-3">⚠️ ALTERE A SENHA APÓS O PRIMEIRO LOGIN!</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <a href="/admin/login.php" class="flex-1 bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-bold py-3 px-6 rounded-lg hover:from-blue-700 hover:to-emerald-700 transition text-center">
                        Acessar Painel Admin
                    </a>
                    <a href="/" class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition text-center">
                        Voltar ao Site
                    </a>
                </div>

            <?php elseif ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg mb-6">
                    <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <button onclick="location.reload()" class="w-full bg-gray-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-gray-700 transition">
                    Tentar Novamente
                </button>

            <?php else: ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <h3 class="font-bold text-blue-900 mb-3">📋 O que será instalado:</h3>
                    <ul class="space-y-2 text-sm text-blue-800">
                        <li>✓ Tabela <code class="bg-blue-100 px-2 py-1 rounded">super_admins</code> - Administradores do SaaS</li>
                        <li>✓ Tabela <code class="bg-blue-100 px-2 py-1 rounded">admin_logs</code> - Logs de ações</li>
                        <li>✓ Tabela <code class="bg-blue-100 px-2 py-1 rounded">payment_gateways</code> - Gateways de pagamento</li>
                        <li>✓ Super admin padrão (email: admin@dantetesta.com.br)</li>
                        <li>✓ 5 gateways pré-cadastrados (Pagou, Stripe, Mercado Pago, PagSeguro, Asaas)</li>
                    </ul>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-yellow-800">
                        ⚠️ <strong>Atenção:</strong> Esta instalação criará novas tabelas no banco de dados. 
                        Certifique-se de ter backup antes de continuar.
                    </p>
                </div>

                <form method="POST">
                    <button 
                        type="submit" 
                        name="install"
                        class="w-full bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-bold py-4 px-6 rounded-lg hover:from-blue-700 hover:to-emerald-700 transition transform hover:scale-105"
                    >
                        Instalar Painel Administrativo
                    </button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-4">
                    Desenvolvido por <a href="https://dantetesta.com.br" target="_blank" class="text-blue-600 hover:underline">Dante Testa</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
