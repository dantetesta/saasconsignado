<?php
/**
 * Dashboard de Assinatura
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once 'config/config.php';
requireLogin();

$tenant = getCurrentTenant();
$pageTitle = 'Minha Assinatura';

// Buscar informações da assinatura
require_once 'classes/PagouIntegration.php';
$pagou = new PagouIntegration();
$subscription = $pagou->getSubscriptionStatus($tenant['id']);

// Processar cancelamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    $result = $pagou->cancelSubscription($tenant['id']);
    
    if ($result['success']) {
        setFlashMessage('success', 'Assinatura cancelada com sucesso');
        header('Location: /assinatura.php');
        exit;
    } else {
        setFlashMessage('error', $result['error']);
    }
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Minha Assinatura</h1>
        <p class="text-gray-600 mt-1">Gerencie seu plano e pagamentos</p>
    </div>

    <!-- Status do Plano -->
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl shadow-lg p-8 text-white mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold">
                    Plano <?php echo $tenant['plano'] === 'pro' ? 'Pro' : 'Free'; ?>
                </h2>
                <p class="text-purple-100 mt-1">
                    <?php echo $tenant['nome_empresa']; ?>
                </p>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold">
                    R$ <?php echo $tenant['plano'] === 'pro' ? '20' : '0'; ?>
                </div>
                <div class="text-sm text-purple-100">por mês</div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 pt-6 border-t border-purple-400">
            <div>
                <div class="text-sm text-purple-100">Status</div>
                <div class="font-semibold">
                    <?php 
                    $status_labels = [
                        'ativo' => 'Ativo',
                        'trial' => 'Trial',
                        'suspenso' => 'Suspenso',
                        'cancelado' => 'Cancelado'
                    ];
                    echo $status_labels[$tenant['status']] ?? $tenant['status'];
                    ?>
                </div>
            </div>
            <?php if ($tenant['data_vencimento']): ?>
                <div>
                    <div class="text-sm text-purple-100">Próximo Pagamento</div>
                    <div class="font-semibold">
                        <?php echo date('d/m/Y', strtotime($tenant['data_vencimento'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recursos do Plano -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="font-bold text-lg mb-4">Recursos Inclusos</h3>
        
        <?php if ($tenant['plano'] === 'pro'): ?>
            <ul class="space-y-3">
                <li class="flex items-center text-gray-700">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Estabelecimentos ilimitados
                </li>
                <li class="flex items-center text-gray-700">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Consignações ilimitadas
                </li>
                <li class="flex items-center text-gray-700">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Emails personalizados
                </li>
                <li class="flex items-center text-gray-700">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Suporte prioritário
                </li>
            </ul>
        <?php else: ?>
            <ul class="space-y-3">
                <li class="flex items-center text-gray-700">
                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Até 5 estabelecimentos
                </li>
                <li class="flex items-center text-gray-700">
                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    5 consignações por estabelecimento
                </li>
                <li class="flex items-center text-gray-700">
                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Suporte por email
                </li>
            </ul>
            
            <div class="mt-6 pt-6 border-t border-gray-200">
                <a 
                    href="/upgrade.php" 
                    class="block w-full text-center bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-purple-700 hover:to-pink-700 transition"
                >
                    Fazer Upgrade para Pro
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($subscription && $tenant['plano'] === 'pro'): ?>
        <!-- Histórico de Pagamentos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h3 class="font-bold text-lg mb-4">Informações de Pagamento</h3>
            
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Forma de Pagamento:</span>
                    <span class="font-medium"><?php echo strtoupper($subscription['forma_pagamento'] ?? 'N/A'); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Início da Assinatura:</span>
                    <span class="font-medium">
                        <?php echo $subscription['data_inicio'] ? date('d/m/Y', strtotime($subscription['data_inicio'])) : 'N/A'; ?>
                    </span>
                </div>
                <?php if ($subscription['proximo_pagamento']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Próxima Cobrança:</span>
                        <span class="font-medium">
                            <?php echo date('d/m/Y', strtotime($subscription['proximo_pagamento'])); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cancelar Assinatura -->
        <div class="bg-red-50 border border-red-200 rounded-xl p-6">
            <h3 class="font-bold text-red-900 mb-2">Cancelar Assinatura</h3>
            <p class="text-sm text-red-700 mb-4">
                Ao cancelar, você será rebaixado para o plano Free e perderá acesso aos recursos Pro.
                Seus dados não serão excluídos.
            </p>
            
            <form method="POST" onsubmit="return confirm('Tem certeza que deseja cancelar sua assinatura?');">
                <button 
                    type="submit" 
                    name="cancel"
                    class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                >
                    Cancelar Minha Assinatura
                </button>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
