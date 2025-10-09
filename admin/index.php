<?php
/**
 * Dashboard Administrativo do SaaS
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
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
$superAdmin = new SuperAdmin();
$metrics = $superAdmin->getDashboardMetrics();

$pageTitle = 'Dashboard Admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SaaS Consignados</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        <h1 class="text-lg font-bold">Painel Admin</h1>
                        <p class="text-xs opacity-90">Gestão do SaaS</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <span class="text-sm hidden md:block">👋 <?php echo htmlspecialchars($admin['nome']); ?></span>
                    <a href="/admin/logout.php" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition text-sm font-medium">
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Menu -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4">
            <nav class="flex gap-1 overflow-x-auto">
                <a href="/admin/index.php" class="px-4 py-3 text-sm font-medium text-purple-600 border-b-2 border-purple-600 whitespace-nowrap">
                    📊 Dashboard
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
                <a href="/admin/monitor_api.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    🔍 Monitor
                </a>
            </nav>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Métricas Principais -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Total de Assinantes -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-600">Total de Assinantes</h3>
                    <svg class="w-8 h-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo $metrics['total_tenants']; ?></p>
                <p class="text-xs text-gray-500 mt-1">Empresas cadastradas</p>
            </div>

            <!-- Plano Free -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-600">Plano Free</h3>
                    <svg class="w-8 h-8 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo $metrics['plano_free']; ?></p>
                <p class="text-xs text-gray-500 mt-1">Usuários gratuitos</p>
            </div>

            <!-- Plano Pro -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-600">Plano Pro</h3>
                    <svg class="w-8 h-8 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-purple-600"><?php echo $metrics['plano_pro']; ?></p>
                <p class="text-xs text-gray-500 mt-1">Assinantes pagantes</p>
            </div>

            <!-- MRR -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-sm p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium">MRR</h3>
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold">R$ <?php echo number_format($metrics['mrr'] ?? 0, 2, ',', '.'); ?></p>
                <p class="text-xs opacity-90 mt-1">Receita mensal recorrente</p>
            </div>

        </div>

        <!-- Métricas Secundárias -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <!-- Novos Cadastros -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $metrics['novos_cadastros']; ?></p>
                        <p class="text-sm text-gray-600">Novos (30 dias)</p>
                    </div>
                </div>
            </div>

            <!-- Assinantes Ativos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-green-600"><?php echo $metrics['status_ativo']; ?></p>
                        <p class="text-sm text-gray-600">Ativos</p>
                    </div>
                </div>
            </div>

            <!-- Vencendo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $metrics['vencendo_7dias']; ?></p>
                        <p class="text-sm text-gray-600">Vencendo (7 dias)</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Ações Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <a href="/admin/tenants.php" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition group">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Gerenciar Assinantes</p>
                        <p class="text-xs text-gray-500">Ver todos os clientes</p>
                    </div>
                </div>
            </a>

            <a href="/admin/gateways.php" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition group">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                            <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Gateways</p>
                        <p class="text-xs text-gray-500">Configurar pagamentos</p>
                    </div>
                </div>
            </a>

            <a href="/admin/financeiro.php" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition group">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Relatórios</p>
                        <p class="text-xs text-gray-500">Financeiro e métricas</p>
                    </div>
                </div>
            </a>

            <a href="/admin/logs.php" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition group">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Logs</p>
                        <p class="text-xs text-gray-500">Histórico de ações</p>
                    </div>
                </div>
            </a>

        </div>

        <!-- Status dos Assinantes -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Distribuição por Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status dos Assinantes</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                            <span class="text-sm text-gray-700">Ativos</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900"><?php echo $metrics['status_ativo']; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                            <span class="text-sm text-gray-700">Suspensos</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900"><?php echo $metrics['status_suspenso']; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                            <span class="text-sm text-gray-700">Cancelados</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900"><?php echo $metrics['status_cancelado']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Taxa de Conversão -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Taxa de Conversão</h3>
                <?php 
                $taxaConversao = $metrics['plano_free'] > 0 
                    ? ($metrics['plano_pro'] / ($metrics['plano_free'] + $metrics['plano_pro'])) * 100 
                    : 0;
                ?>
                <div class="text-center">
                    <p class="text-4xl font-bold text-purple-600"><?php echo number_format($taxaConversao, 1); ?>%</p>
                    <p class="text-sm text-gray-600 mt-2">Free → Pro</p>
                    <div class="mt-4 bg-gray-200 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-600 to-pink-600 h-full transition-all" style="width: <?php echo $taxaConversao; ?>%"></div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</body>
</html>
