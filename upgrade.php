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

<div class="max-w-4xl mx-auto px-4">
    
    <!-- Header Compacto -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Faça Upgrade para o Pro</h1>
        <p class="text-gray-600">Apenas R$ 20/mês • Cancele quando quiser</p>
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
        <!-- Tabela de Comparação Compacta -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Recursos</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">Free<br><span class="text-xs font-normal">(Atual)</span></th>
                        <th class="px-6 py-4 text-center text-sm font-semibold bg-gradient-to-r from-purple-600 to-pink-600 text-white">Pro<br><span class="text-xs font-normal">R$ 20/mês</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-900">Estabelecimentos</td>
                        <td class="px-6 py-3 text-center text-sm text-gray-600">5</td>
                        <td class="px-6 py-3 text-center text-sm font-semibold text-purple-600">Ilimitado</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 text-sm text-gray-900">Consignações</td>
                        <td class="px-6 py-3 text-center text-sm text-gray-600">5 por estabelecimento</td>
                        <td class="px-6 py-3 text-center text-sm font-semibold text-purple-600">Ilimitado</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-900">Emails personalizados</td>
                        <td class="px-6 py-3 text-center text-sm text-gray-400">✗</td>
                        <td class="px-6 py-3 text-center text-sm text-green-600">✓</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 text-sm text-gray-900">Relatórios avançados</td>
                        <td class="px-6 py-3 text-center text-sm text-gray-400">✗</td>
                        <td class="px-6 py-3 text-center text-sm text-green-600">✓</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-900">Suporte</td>
                        <td class="px-6 py-3 text-center text-sm text-gray-600">Email</td>
                        <td class="px-6 py-3 text-center text-sm font-semibold text-purple-600">Prioritário</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Card de Upgrade -->
        <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl shadow-xl p-8 text-white">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-2xl font-bold text-center mb-6">Faça Upgrade Agora</h2>
                
                <form method="POST" class="space-y-6">
                    <!-- Formas de Pagamento -->
                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="flex items-center bg-white bg-opacity-20 p-4 rounded-lg cursor-pointer hover:bg-opacity-30 transition border-2 border-transparent hover:border-white">
                            <input type="radio" name="forma_pagamento" value="pix" checked class="w-5 h-5 mr-3">
                            <div class="flex-1">
                                <div class="font-semibold flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                                    </svg>
                                    PIX
                                </div>
                                <div class="text-xs opacity-90 mt-1">Aprovação instantânea</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center bg-white bg-opacity-20 p-4 rounded-lg cursor-pointer hover:bg-opacity-30 transition border-2 border-transparent hover:border-white">
                            <input type="radio" name="forma_pagamento" value="boleto" class="w-5 h-5 mr-3">
                            <div class="flex-1">
                                <div class="font-semibold flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M3 3h2v18H3zm4 0h2v18H7zm4 0h2v18h-2zm4 0h2v18h-2zm4 0h2v18h-2z"/>
                                    </svg>
                                    Boleto
                                </div>
                                <div class="text-xs opacity-90 mt-1">Compensação em 2 dias</div>
                            </div>
                        </label>
                    </div>

                    <!-- Botão de Upgrade -->
                    <button 
                        type="submit" 
                        name="upgrade"
                        class="w-full bg-white text-purple-600 font-bold py-4 px-6 rounded-lg hover:bg-purple-50 transition transform hover:scale-105 shadow-xl"
                    >
                        Confirmar Upgrade • R$ 20/mês
                    </button>

                    <p class="text-center text-sm opacity-90">
                        🔒 Pagamento seguro • Cancele quando quiser • Garantia de 7 dias
                    </p>
                </form>
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
