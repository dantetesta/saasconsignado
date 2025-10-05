<?php
/**
 * Relatório Financeiro
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
$report = $superAdmin->getFinancialReport();

$pageTitle = 'Relatório Financeiro';
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
                <a href="/admin/index.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    📊 Dashboard
                </a>
                <a href="/admin/tenants.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    👥 Assinantes
                </a>
                <a href="/admin/financeiro.php" class="px-4 py-3 text-sm font-medium text-purple-600 border-b-2 border-purple-600 whitespace-nowrap">
                    💰 Financeiro
                </a>
                <a href="/admin/gateways.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    💳 Gateways
                </a>
                <a href="/admin/logs.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    📝 Logs
                </a>
            </nav>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Relatório Financeiro</h1>
            <p class="text-gray-600">Análise de receitas e inadimplência</p>
        </div>

        <!-- Receita Mensal -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Receita dos Últimos 6 Meses</h3>
            
            <?php if (!empty($report['receita_mensal'])): ?>
                <div class="space-y-3">
                    <?php foreach ($report['receita_mensal'] as $mes): ?>
                        <?php
                        $mesFormatado = DateTime::createFromFormat('Y-m', $mes['mes'])->format('m/Y');
                        $receita = $mes['receita_total'];
                        $pagamentos = $mes['total_pagamentos'];
                        ?>
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-medium text-gray-700 w-20"><?php echo $mesFormatado; ?></span>
                            <div class="flex-1 bg-gray-200 rounded-full h-8 overflow-hidden">
                                <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-full flex items-center justify-end pr-3 text-white text-sm font-bold" style="width: <?php echo min(100, ($receita / 1000) * 100); ?>%">
                                    R$ <?php echo number_format($receita, 2, ',', '.'); ?>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500 w-24"><?php echo $pagamentos; ?> pagamento(s)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-8">Nenhuma receita registrada ainda</p>
            <?php endif; ?>
        </div>

        <!-- Inadimplência -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Inadimplência</h3>
            
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-3xl font-bold text-red-600"><?php echo $report['inadimplentes']; ?></p>
                    <p class="text-sm text-gray-600 mt-1">Assinantes com pagamento vencido</p>
                </div>
                <svg class="w-16 h-16 text-red-200" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>

            <?php if ($report['inadimplentes'] > 0): ?>
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-800">
                        ⚠️ Ação recomendada: Enviar lembretes de cobrança ou suspender acesso
                    </p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
