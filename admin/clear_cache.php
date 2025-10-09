<?php
/**
 * Script para Limpeza de Cache do Sistema
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.1.0
 */

session_start();
require_once '../config/database.php';
require_once '../classes/SuperAdmin.php';
require_once '../classes/PaymentCache.php';

// Verificar autenticação
if (!SuperAdmin::isLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}

$message = '';
$messageType = '';

if ($_POST['action'] ?? '' === 'clear_cache') {
    try {
        // Limpar cache de pagamentos
        PaymentCache::clearAll();
        
        // Limpar cache de sessão se necessário
        if (isset($_POST['clear_sessions'])) {
            session_destroy();
            session_start();
        }
        
        // Limpar logs temporários
        if (isset($_POST['clear_logs'])) {
            $tempLogs = ['/tmp/php_errors.log', '/tmp/payment_cache.log'];
            foreach ($tempLogs as $logFile) {
                if (file_exists($logFile)) {
                    file_put_contents($logFile, '');
                }
            }
        }
        
        $message = '✅ Cache limpo com sucesso!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = '❌ Erro ao limpar cache: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$admin = SuperAdmin::getCurrentAdmin();
$pageTitle = 'Limpeza de Cache';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

    <!-- Header Admin -->
    <nav class="bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zM4 14a2 2 0 002-2h8a2 2 0 002 2H4zM4 16a2 2 0 002-2h8a2 2 0 002 2H4z"/>
                    </svg>
                    <div>
                        <h1 class="text-lg font-bold">Limpeza de Cache</h1>
                        <p class="text-xs opacity-90">Manutenção do Sistema</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <span class="text-sm hidden md:block">👋 <?php echo htmlspecialchars($admin['nome']); ?></span>
                    <a href="/admin/monitor_api.php" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition text-sm font-medium">
                        ← Monitor
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Conteúdo -->
    <div class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">🧹 Limpeza de Cache</h1>
            <p class="text-gray-600">Limpe o cache do sistema para resolver problemas de performance</p>
        </div>

        <!-- Mensagem -->
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-red-50 to-orange-50 border-b border-red-200 px-6 py-4">
                <h2 class="text-xl font-bold text-red-900">⚠️ Limpeza de Cache</h2>
                <p class="text-sm text-red-700 mt-1">Use apenas se estiver enfrentando problemas no sistema</p>
            </div>
            
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="clear_cache">
                
                <div class="space-y-4 mb-6">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="clear_payments" checked class="w-4 h-4 text-purple-600 rounded">
                        <div>
                            <span class="font-medium text-gray-900">Cache de Pagamentos</span>
                            <p class="text-sm text-gray-600">Limpa o cache de verificação de pagamentos (recomendado)</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="clear_sessions" class="w-4 h-4 text-purple-600 rounded">
                        <div>
                            <span class="font-medium text-gray-900">Sessões de Usuário</span>
                            <p class="text-sm text-gray-600">Força logout de todos os usuários (use com cuidado)</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="clear_logs" class="w-4 h-4 text-purple-600 rounded">
                        <div>
                            <span class="font-medium text-gray-900">Logs Temporários</span>
                            <p class="text-sm text-gray-600">Limpa arquivos de log temporários</p>
                        </div>
                    </label>
                </div>
                
                <div class="flex gap-4">
                    <button 
                        type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-red-600 to-orange-600 text-white font-bold rounded-lg hover:from-red-700 hover:to-orange-700 transition shadow-lg"
                        onclick="return confirm('Tem certeza que deseja limpar o cache? Esta ação não pode ser desfeita.')"
                    >
                        🧹 Limpar Cache
                    </button>
                    
                    <a 
                        href="/admin/monitor_api.php"
                        class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition"
                    >
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

        <!-- Informações -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-bold text-blue-900 mb-3">ℹ️ Quando usar a limpeza de cache?</h3>
            <ul class="space-y-2 text-sm text-blue-800">
                <li class="flex items-start gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mt-2"></span>
                    <span>Quando pagamentos não estão sendo verificados corretamente</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mt-2"></span>
                    <span>Após mudanças nas configurações de preços</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mt-2"></span>
                    <span>Se o sistema estiver apresentando lentidão</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mt-2"></span>
                    <span>Em caso de erros persistentes na API</span>
                </li>
            </ul>
        </div>

    </div>

</body>
</html>
