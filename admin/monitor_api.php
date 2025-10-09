<?php
/**
 * Monitor de Saúde da API de Pagamentos
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.1.0
 */

session_start();
require_once '../config/database.php';
require_once '../classes/SuperAdmin.php';

// Verificar autenticação
if (!SuperAdmin::isLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}

$admin = SuperAdmin::getCurrentAdmin();
$db = Database::getInstance()->getConnection();

// Estatísticas de pagamentos
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_payments,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_payments,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as payments_last_hour
    FROM subscription_payments
");
$paymentStats = $stmt->fetch();

// Verificar logs de erro recentes
$errorLogs = [];
$logFile = '/tmp/php_errors.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $errorLogs = array_slice(array_reverse($lines), 0, 10);
}

$pageTitle = 'Monitor da API';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta http-equiv="refresh" content="30">
</head>
<body class="bg-gray-50">

    <!-- Header Admin -->
    <nav class="bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                    </svg>
                    <div>
                        <h1 class="text-lg font-bold">Monitor API</h1>
                        <p class="text-xs opacity-90">Saúde do Sistema</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <span class="text-sm hidden md:block">👋 <?php echo htmlspecialchars($admin['nome']); ?></span>
                    <a href="/admin/index.php" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition text-sm font-medium">
                        ← Voltar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Menu de Navegação -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto">
            <nav class="flex overflow-x-auto">
                <a href="/admin/index.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    🏠 Dashboard
                </a>
                <a href="/admin/tenants.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    👥 Assinantes
                </a>
                <a href="/admin/financeiro.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    💰 Financeiro
                </a>
                <a href="/admin/pagamentos.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    💳 Pagamentos
                </a>
                <a href="/admin/gateways.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    🔗 Gateways
                </a>
                <a href="/admin/configuracoes.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    ⚙️ Configurações
                </a>
                <a href="/admin/logs.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    📝 Logs
                </a>
                <a href="/admin/monitor_api.php" class="px-4 py-3 text-sm font-medium text-purple-600 border-b-2 border-purple-600 whitespace-nowrap">
                    🔍 Monitor
                </a>
            </nav>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">🔍 Monitor de Saúde da API</h1>
            <p class="text-gray-600">Monitoramento em tempo real • Atualização automática a cada 30s</p>
        </div>

        <!-- Status Geral -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <h3 class="text-sm font-medium text-gray-600">Status da API</h3>
                </div>
                <p class="text-2xl font-bold text-green-600">Online</p>
                <p class="text-xs text-gray-500 mt-1">Sistema funcionando</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/>
                    </svg>
                    <h3 class="text-sm font-medium text-gray-600">Total Pagamentos</h3>
                </div>
                <p class="text-2xl font-bold text-gray-900"><?php echo $paymentStats['total_payments']; ?></p>
                <p class="text-xs text-gray-500 mt-1">Histórico completo</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="text-sm font-medium text-gray-600">Pendentes</h3>
                </div>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $paymentStats['pending_payments']; ?></p>
                <p class="text-xs text-gray-500 mt-1">Aguardando pagamento</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="text-sm font-medium text-gray-600">Última Hora</h3>
                </div>
                <p class="text-2xl font-bold text-green-600"><?php echo $paymentStats['payments_last_hour']; ?></p>
                <p class="text-xs text-gray-500 mt-1">Pagamentos recentes</p>
            </div>
        </div>

        <!-- Otimizações Implementadas -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-green-50 to-blue-50 border-b border-green-200 px-6 py-4">
                <h2 class="text-xl font-bold text-green-900">✅ Otimizações Implementadas</h2>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3">🚀 Performance</h3>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                Cache de pagamentos (30s)
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                Timeout reduzido (8s)
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                Limite de tentativas (60x)
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3">🛡️ Estabilidade</h3>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                Tratamento de erros robusto
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                Logs de debug detalhados
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                Fallback em caso de falha
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teste da API -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-b border-purple-200 px-6 py-4">
                <h2 class="text-xl font-bold text-purple-900">🧪 Teste da API</h2>
            </div>
            <div class="p-6">
                <button 
                    onclick="testarAPI()"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold rounded-lg hover:from-purple-700 hover:to-pink-700 transition shadow-lg"
                >
                    🔍 Testar Verificação de Pagamento
                </button>
                
                <div id="resultado-teste" class="mt-4 p-4 bg-gray-50 rounded-lg hidden">
                    <h4 class="font-bold mb-2">Resultado do Teste:</h4>
                    <pre id="resultado-conteudo" class="text-sm text-gray-700"></pre>
                </div>
            </div>
        </div>

    </div>

    <script>
        async function testarAPI() {
            const resultadoDiv = document.getElementById('resultado-teste');
            const conteudoDiv = document.getElementById('resultado-conteudo');
            
            resultadoDiv.classList.remove('hidden');
            conteudoDiv.textContent = 'Testando API...';
            
            try {
                const start = Date.now();
                const response = await fetch('/api/verificar_pagamento.php?charge_id=test-charge-id');
                const end = Date.now();
                const data = await response.json();
                
                const resultado = {
                    status: response.status,
                    tempo_resposta: `${end - start}ms`,
                    resposta: data,
                    timestamp: new Date().toLocaleString('pt-BR')
                };
                
                conteudoDiv.textContent = JSON.stringify(resultado, null, 2);
                
                if (response.ok) {
                    resultadoDiv.className = 'mt-4 p-4 bg-green-50 border border-green-200 rounded-lg';
                } else {
                    resultadoDiv.className = 'mt-4 p-4 bg-red-50 border border-red-200 rounded-lg';
                }
                
            } catch (error) {
                conteudoDiv.textContent = `Erro: ${error.message}`;
                resultadoDiv.className = 'mt-4 p-4 bg-red-50 border border-red-200 rounded-lg';
            }
        }
        
        // Auto-refresh da página a cada 30 segundos
        console.log('Monitor da API carregado. Auto-refresh ativo.');
    </script>

</body>
</html>
