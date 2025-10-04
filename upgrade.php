<?php
/**
 * Página de Upgrade - Plano Free para Pro
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once 'config/config.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$tenant = getCurrentTenant();
$pageTitle = 'Upgrade para Plano Pro';

// Se já é Pro, redirecionar
if ($tenant['plano'] === 'pro') {
    header('Location: /assinatura.php');
    exit;
}

$error = '';
$success = '';
$payment_data = null;

// Processar upgrade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade'])) {
    $forma_pagamento = $_POST['forma_pagamento'] ?? 'pix';
    
    if (!in_array($forma_pagamento, ['pix', 'boleto'])) {
        $error = 'Forma de pagamento inválida';
    } else {
        try {
            require_once 'classes/PagouIntegration.php';
            require_once 'config/integrations.php';
            
            $pagou = new PagouIntegration();
            $result = $pagou->createSubscription($tenant['id'], $forma_pagamento);
            
            if ($result['success']) {
                $payment_data = $result;
                $success = 'Assinatura criada com sucesso! Complete o pagamento abaixo.';
            } else {
                $error = $result['error'] ?? 'Erro ao processar upgrade';
            }
            
        } catch (Exception $e) {
            $error = 'Erro: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4">
    
    <!-- Header com Badge -->
    <div class="mb-10 text-center">
        <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-100 to-pink-100 rounded-full mb-4">
            <svg class="w-5 h-5 text-purple-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
            </svg>
            <span class="text-sm font-semibold text-purple-700">Upgrade Disponível</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">Desbloqueie Todo o Potencial</h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">Cresça sem limites com o Plano Pro e tenha acesso a recursos ilimitados</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success && $payment_data): ?>
        <!-- Dados de Pagamento -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Assinatura Criada!</h2>
                <p class="text-gray-600">Complete o pagamento para ativar o Plano Pro</p>
            </div>

            <?php if (isset($payment_data['pix_code'])): ?>
                <!-- Pagamento PIX -->
                <div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-6">
                    <h3 class="font-bold text-purple-900 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                        </svg>
                        Pagar com PIX
                    </h3>
                    <div class="bg-white p-4 rounded text-center mb-4">
                        <div class="bg-white border-2 border-gray-300 p-4 inline-block rounded">
                            <!-- QR Code seria gerado aqui -->
                            <div class="w-64 h-64 bg-gray-100 flex items-center justify-center">
                                <p class="text-gray-500">QR Code PIX</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ou copie o código PIX:</label>
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                value="<?php echo htmlspecialchars($payment_data['pix_code']); ?>" 
                                readonly
                                id="pix-code"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded bg-gray-50 text-sm font-mono"
                            >
                            <button 
                                onclick="copyPixCode()"
                                class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition"
                            >
                                Copiar
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-purple-800 mt-4">
                        ⏱️ O pagamento via PIX é confirmado em poucos segundos
                    </p>
                </div>
            <?php elseif (isset($payment_data['barcode'])): ?>
                <!-- Pagamento Boleto -->
                <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6">
                    <h3 class="font-bold text-blue-900 mb-4">Boleto Bancário</h3>
                    <div class="bg-white p-4 rounded mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código de barras:</label>
                        <input 
                            type="text" 
                            value="<?php echo htmlspecialchars($payment_data['barcode']); ?>" 
                            readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-50 font-mono text-sm"
                        >
                    </div>
                    <?php if (isset($payment_data['payment_url'])): ?>
                        <a 
                            href="<?php echo htmlspecialchars($payment_data['payment_url']); ?>" 
                            target="_blank"
                            class="block w-full text-center bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 transition"
                        >
                            Visualizar/Imprimir Boleto
                        </a>
                    <?php endif; ?>
                    <p class="text-sm text-blue-800 mt-4">
                        ⏱️ O boleto leva até 2 dias úteis para compensar
                    </p>
                </div>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Após a confirmação do pagamento, seu plano será automaticamente ativado!
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- Comparação de Planos Compacta -->
        <div class="grid md:grid-cols-2 gap-6 mb-10 max-w-4xl mx-auto">
            
            <!-- Plano Free -->
            <div class="bg-white rounded-2xl shadow-md hover:shadow-lg transition-shadow p-6 border border-gray-200 relative">
                <div class="absolute -top-3 left-6 bg-gray-100 px-3 py-1 rounded-full">
                    <span class="text-xs font-semibold text-gray-600">ATUAL</span>
                </div>
                
                <div class="text-center mb-5 pt-2">
                    <h3 class="text-xl font-bold text-gray-700 mb-2">Plano Free</h3>
                    <div class="text-3xl font-bold text-gray-900">
                        R$ 0
                        <span class="text-base font-normal text-gray-500">/mês</span>
                    </div>
                </div>
                
                <ul class="space-y-2.5 mb-5">
                    <li class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-600">5 estabelecimentos</span>
                    </li>
                    <li class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-600">5 consignações/estab.</span>
                    </li>
                    <li class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-600">Recursos básicos</span>
                    </li>
                </ul>
                
                <div class="pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 text-center">Perfeito para começar</p>
                </div>
            </div>

            <!-- Plano Pro -->
            <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl shadow-xl hover:shadow-2xl transition-shadow p-6 text-white relative overflow-hidden">
                <div class="absolute -top-3 right-6 bg-yellow-400 px-3 py-1 rounded-full">
                    <span class="text-xs font-bold text-purple-900">⭐ POPULAR</span>
                </div>
                
                <!-- Padrão decorativo -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-16 -mt-16"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-12 -mb-12"></div>
                
                <div class="text-center mb-5 pt-2 relative z-10">
                    <h3 class="text-xl font-bold mb-2">Plano Pro</h3>
                    <div class="text-4xl font-bold">
                        R$ 20
                        <span class="text-base font-normal opacity-90">/mês</span>
                    </div>
                    <p class="text-sm text-purple-100 mt-1">🚀 Sem limites!</p>
                </div>
                
                <ul class="space-y-2.5 mb-5 relative z-10">
                    <li class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-yellow-300 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span><strong>Ilimitado</strong> tudo</span>
                    </li>
                    <li class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-yellow-300 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Emails <strong>personalizados</strong></span>
                    </li>
                    <li class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-yellow-300 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Relatórios <strong>avançados</strong></span>
                    </li>
                    <li class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-yellow-300 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Suporte <strong>prioritário</strong></span>
                    </li>
                </ul>
                
                <div class="pt-4 border-t border-white border-opacity-20 relative z-10">
                    <p class="text-xs text-center opacity-90">💎 Melhor custo-benefício</p>
                </div>
            </div>
        </div>

        <!-- Formulário de Upgrade -->
        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">Escolha a forma de pagamento</h3>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <div class="space-y-3">
                            <label class="flex items-center bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 p-4 rounded-xl cursor-pointer hover:border-green-400 transition group">
                                <input type="radio" name="forma_pagamento" value="pix" checked class="w-5 h-5 text-green-600 mr-3">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 text-green-600 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                                        </svg>
                                        <span class="font-semibold text-gray-900">PIX</span>
                                        <span class="ml-auto text-xs bg-green-500 text-white px-2.5 py-1 rounded-full font-medium">⚡ Instantâneo</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 ml-8">Aprovação em segundos</p>
                                </div>
                            </label>
                            
                            <label class="flex items-center bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 p-4 rounded-xl cursor-pointer hover:border-blue-400 transition group">
                                <input type="radio" name="forma_pagamento" value="boleto" class="w-5 h-5 text-blue-600 mr-3">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 3h2v18H3zm4 0h2v18H7zm4 0h2v18h-2zm4 0h2v18h-2zm4 0h2v18h-2z"/>
                                        </svg>
                                        <span class="font-semibold text-gray-900">Boleto</span>
                                        <span class="ml-auto text-xs bg-blue-500 text-white px-2.5 py-1 rounded-full font-medium">📅 2 dias úteis</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 ml-8">Pague em qualquer banco</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        name="upgrade"
                        class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-4 px-6 rounded-xl hover:from-purple-700 hover:to-pink-700 transition transform hover:scale-105 shadow-xl flex items-center justify-center group"
                    >
                        <span>Fazer Upgrade Agora</span>
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </button>
                </form>
                
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-center text-xs text-gray-500">
                        🔒 Pagamento seguro • Cancele quando quiser
                    </p>
                </div>
            </div>
        </div>

        <!-- Garantia -->
        <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-xl p-6 text-center">
            <h3 class="font-bold text-gray-900 text-lg mb-2">💚 Garantia de 7 Dias</h3>
            <p class="text-gray-700">
                Não está satisfeito? Cancele nos primeiros 7 dias e receba 100% do seu dinheiro de volta.
            </p>
        </div>
    <?php endif; ?>

</div>

<script>
function copyPixCode() {
    const pixCode = document.getElementById('pix-code');
    pixCode.select();
    pixCode.setSelectionRange(0, 99999); // Mobile
    document.execCommand('copy');
    
    alert('Código PIX copiado!');
}
</script>

<?php include 'includes/footer.php'; ?>
