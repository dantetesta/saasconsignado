<?php
/**
 * Página de Upgrade - Plano Free para Pro
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

// Forçar UTF-8
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

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
$pixData = null;

// Processar criação de PIX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_pix'])) {
    try {
        // Validar CSRF
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de segurança inválido');
        }
        
        require_once 'classes/PagouAPI.php';
        
        $pagouAPI = new PagouAPI();
        
        // Buscar dados do tenant
        $stmt = $db->prepare("SELECT nome_empresa, documento, email_principal FROM tenants WHERE id = ?");
        $stmt->execute([$tenant['id']]);
        $tenantData = $stmt->fetch();
        
        // Criar PIX
        $pix = $pagouAPI->criarPixAssinatura(
            $tenant['id'],
            $tenantData['nome_empresa'],
            $tenantData['documento'],
            $tenantData['email_principal']
        );
        
        // Salvar no banco
        $stmt = $db->prepare("
            INSERT INTO subscription_payments 
            (tenant_id, charge_id, amount, status, qrcode_data, qrcode_image, created_at)
            VALUES (?, ?, ?, 'pending', ?, ?, NOW())
        ");
        $stmt->execute([
            $tenant['id'],
            $pix['charge_id'],
            $pix['amount'],
            $pix['qrcode_data'],
            $pix['qrcode_image']
        ]);
        
        $pixData = $pix;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
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
        <!-- Comparação Desktop (Tabela) -->
        <div class="hidden md:block bg-white rounded-2xl shadow-xl overflow-hidden mb-8 border border-gray-100">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-8 py-6 text-left text-lg font-bold text-gray-900">
                            <div class="flex items-center gap-2">
                                <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Recursos
                            </div>
                        </th>
                        <th class="px-8 py-6 text-center text-base font-bold text-gray-700">
                            <div class="flex flex-col items-center">
                                <span class="text-lg">Free</span>
                                <span class="text-xs font-normal bg-gray-200 px-2 py-1 rounded-full mt-1">Atual</span>
                            </div>
                        </th>
                        <th class="px-8 py-6 text-center text-base font-bold bg-gradient-to-r from-purple-600 to-pink-600 text-white relative">
                            <div class="flex flex-col items-center">
                                <span class="text-lg flex items-center gap-1">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    Pro
                                </span>
                                <span class="text-sm font-normal opacity-90">R$ 20/mês</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-8 py-5 text-base font-medium text-gray-900">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/>
                                </svg>
                                Estabelecimentos
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center text-base text-gray-600">
                            <span class="bg-gray-100 px-3 py-1 rounded-full font-medium">5</span>
                        </td>
                        <td class="px-8 py-5 text-center text-base font-bold text-purple-600">
                            <span class="bg-purple-100 px-3 py-1 rounded-full">∞ Ilimitado</span>
                        </td>
                    </tr>
                    <tr class="bg-gray-50 hover:bg-gray-100 transition-colors">
                        <td class="px-8 py-5 text-base font-medium text-gray-900">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2h8a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 1a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                </svg>
                                Consignações
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center text-base text-gray-600">
                            <span class="bg-gray-100 px-3 py-1 rounded-full font-medium">5 por estabelecimento</span>
                        </td>
                        <td class="px-8 py-5 text-center text-base font-bold text-purple-600">
                            <span class="bg-purple-100 px-3 py-1 rounded-full">∞ Ilimitado</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-8 py-5 text-base font-medium text-gray-900">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                </svg>
                                Emails personalizados
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center text-base">
                            <span class="text-red-500 text-xl font-bold">✗</span>
                        </td>
                        <td class="px-8 py-5 text-center text-base">
                            <span class="text-green-500 text-xl font-bold">✓</span>
                        </td>
                    </tr>
                    <tr class="bg-gray-50 hover:bg-gray-100 transition-colors">
                        <td class="px-8 py-5 text-base font-medium text-gray-900">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                                </svg>
                                Relatórios avançados
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center text-base">
                            <span class="text-red-500 text-xl font-bold">✗</span>
                        </td>
                        <td class="px-8 py-5 text-center text-base">
                            <span class="text-green-500 text-xl font-bold">✓</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-8 py-5 text-base font-medium text-gray-900">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-1.106-1.106A6.003 6.003 0 004 10c0 .639.1 1.255.283 1.836l1.875-1.875zM10 4a6.01 6.01 0 012.754.668l-1.525 1.524a3.997 3.997 0 00-2.183-.078l-1.562-1.562A5.98 5.98 0 0110 4zm3.537 2.464a4 4 0 11-7.073 0L10 10l3.537-3.536z" clip-rule="evenodd"/>
                                </svg>
                                Suporte
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center text-base text-blue-600">
                            <span class="inline-flex items-center gap-1 bg-blue-100 px-3 py-1 rounded-full font-medium">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                </svg>
                                Email
                            </span>
                        </td>
                        <td class="px-8 py-5 text-center text-base font-bold text-green-600">
                            <span class="inline-flex items-center gap-1 bg-green-100 px-3 py-1 rounded-full font-medium">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/>
                                </svg>
                                WhatsApp
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Comparação Mobile (Cards) -->
        <div class="md:hidden space-y-4 mb-8">
            <!-- Plano Atual -->
            <div class="bg-gray-50 rounded-xl p-4 border-2 border-gray-200">
                <div class="text-center mb-3">
                    <span class="bg-gray-500 text-white px-3 py-1 rounded-full text-xs font-bold">SEU PLANO ATUAL</span>
                    <h3 class="text-lg font-bold text-gray-700 mt-2">Plano Free</h3>
                    <p class="text-2xl font-bold text-gray-900">R$ 0<span class="text-sm font-normal">/mês</span></p>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>🏢 Estabelecimentos</span>
                        <span class="font-medium">5</span>
                    </div>
                    <div class="flex justify-between">
                        <span>📋 Consignações</span>
                        <span class="font-medium">5 por estabelecimento</span>
                    </div>
                    <div class="flex justify-between">
                        <span>📧 Emails personalizados</span>
                        <span class="text-red-500">✗</span>
                    </div>
                    <div class="flex justify-between">
                        <span>📊 Relatórios avançados</span>
                        <span class="text-red-500">✗</span>
                    </div>
                    <div class="flex justify-between">
                        <span>🎯 Suporte</span>
                        <span class="font-medium">Email</span>
                    </div>
                </div>
            </div>

            <!-- Plano Pro -->
            <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl p-4 text-white relative">
                <div class="absolute -top-2 right-4 bg-yellow-400 text-purple-900 px-3 py-1 rounded-full text-xs font-bold">
                    ⭐ RECOMENDADO
                </div>
                <div class="text-center mb-3 mt-2">
                    <h3 class="text-lg font-bold">Plano Pro</h3>
                    <p class="text-3xl font-bold">R$ 20<span class="text-sm font-normal">/mês</span></p>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>🏢 Estabelecimentos</span>
                        <span class="font-bold">∞ Ilimitado</span>
                    </div>
                    <div class="flex justify-between">
                        <span>📋 Consignações</span>
                        <span class="font-bold">∞ Ilimitado</span>
                    </div>
                    <div class="flex justify-between">
                        <span>📧 Emails personalizados</span>
                        <span class="text-green-300 text-lg">✓</span>
                    </div>
                    <div class="flex justify-between">
                        <span>📊 Relatórios avançados</span>
                        <span class="text-green-300 text-lg">✓</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>🎯 Suporte</span>
                        <span class="bg-green-500 px-2 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/>
                            </svg>
                            WhatsApp
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Upgrade -->
        <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl shadow-xl p-8 text-white">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-2xl font-bold text-center mb-6">Faça Upgrade Agora</h2>
                
                <form method="POST" class="space-y-6">
                    <!-- Formas de Pagamento -->
                    <div class="grid md:grid-cols-3 gap-3">
                        <label class="flex flex-col items-center bg-white bg-opacity-20 p-4 rounded-lg cursor-pointer hover:bg-opacity-30 transition border-2 border-transparent hover:border-white">
                            <input type="radio" name="forma_pagamento" value="pix" checked class="w-5 h-5 mb-2">
                            <div class="text-center">
                                <div class="font-semibold flex items-center justify-center gap-2 mb-1">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                                    </svg>
                                    PIX
                                </div>
                                <div class="text-xs opacity-90">Aprovação instantânea</div>
                            </div>
                        </label>
                        
                        <label class="flex flex-col items-center bg-white bg-opacity-20 p-4 rounded-lg cursor-pointer hover:bg-opacity-30 transition border-2 border-transparent hover:border-white">
                            <input type="radio" name="forma_pagamento" value="cartao" class="w-5 h-5 mb-2">
                            <div class="text-center">
                                <div class="font-semibold flex items-center justify-center gap-2 mb-1">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zm0 3v2h16V7H4zm0 4v6h16v-6H4z"/>
                                    </svg>
                                    Cartão
                                </div>
                                <div class="text-xs opacity-90">Aprovação imediata</div>
                            </div>
                        </label>
                        
                        <label class="flex flex-col items-center bg-white bg-opacity-20 p-4 rounded-lg cursor-pointer hover:bg-opacity-30 transition border-2 border-transparent hover:border-white">
                            <input type="radio" name="forma_pagamento" value="boleto" class="w-5 h-5 mb-2">
                            <div class="text-center">
                                <div class="font-semibold flex items-center justify-center gap-2 mb-1">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M3 3h2v18H3zm4 0h2v18H7zm4 0h2v18h-2zm4 0h2v18h-2zm4 0h2v18h-2z"/>
                                    </svg>
                                    Boleto
                                </div>
                                <div class="text-xs opacity-90">Compensação em 2 dias</div>
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
        <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-xl p-6 text-center mt-8">
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
