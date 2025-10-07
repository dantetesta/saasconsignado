<?php
/**
 * Página de Upgrade - Plano Pro com PIX
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
        
        // Buscar dados do usuário (documento está em usuarios, não em tenants)
        $stmt = $db->prepare("
            SELECT u.nome_empresa, u.documento, u.email 
            FROM usuarios u 
            WHERE u.id = ? AND u.tenant_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $tenant['id']]);
        $tenantData = $stmt->fetch();
        
        // Se não encontrou, buscar do tenant
        if (!$tenantData) {
            $stmt = $db->prepare("SELECT nome_empresa, documento, email_principal as email FROM tenants WHERE id = ?");
            $stmt->execute([$tenant['id']]);
            $tenantData = $stmt->fetch();
        }
        
        // Debug: Verificar dados
        error_log("=== DEBUG UPGRADE ===");
        error_log("Tenant ID: " . $tenant['id']);
        error_log("Nome: " . ($tenantData['nome_empresa'] ?? 'NULL'));
        error_log("Documento: " . ($tenantData['documento'] ?? 'NULL'));
        error_log("Email: " . ($tenantData['email'] ?? 'NULL'));
        
        // Validar dados obrigatórios
        if (empty($tenantData['nome_empresa'])) {
            throw new Exception('Nome da empresa não cadastrado. Atualize seu perfil.');
        }
        if (empty($tenantData['email'])) {
            throw new Exception('Email não cadastrado. Atualize seu perfil.');
        }
        
        // Criar PIX
        $pix = $pagouAPI->criarPixAssinatura(
            $tenant['id'],
            $tenantData['nome_empresa'],
            $tenantData['documento'],
            $tenantData['email']
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

<div class="max-w-4xl mx-auto px-4 pb-12">
    
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Faça Upgrade para o Pro</h1>
        <p class="text-gray-600">Apenas R$ 20/mês • Cancele quando quiser</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-red-700 font-medium mb-2"><?php echo htmlspecialchars($error); ?></p>
                    <?php if (strpos($error, 'CPF') !== false || strpos($error, 'perfil') !== false): ?>
                        <a href="/perfil.php" class="inline-flex items-center gap-2 text-sm text-red-600 hover:text-red-800 font-semibold underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Ir para Meu Perfil
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($pixData): ?>
        <!-- Tela de Pagamento PIX -->
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <!-- Status -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Pague com PIX</h2>
                <p id="status-text" class="text-gray-600">Aguardando pagamento...</p>
            </div>

            <!-- QR Code -->
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-200 rounded-xl p-6 mb-6">
                <div class="text-center mb-4">
                    <img 
                        id="qrcode-img"
                        src="data:image/png;base64,<?php echo $pixData['qrcode_image']; ?>" 
                        alt="QR Code PIX"
                        class="inline-block w-64 h-64 border-4 border-white rounded-lg shadow-lg"
                    >
                </div>
                
                <!-- Código Copia e Cola -->
                <div class="bg-white rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ou copie o código PIX:</label>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            value="<?php echo htmlspecialchars($pixData['qrcode_data']); ?>" 
                            readonly
                            id="pix-code"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm font-mono"
                        >
                        <button 
                            onclick="copiarCodigo()"
                            class="px-6 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition"
                        >
                            📋 Copiar
                        </button>
                    </div>
                </div>
                
                <p class="text-sm text-purple-800 mt-4 text-center">
                    ⏱️ O pagamento via PIX é confirmado em poucos segundos
                </p>
            </div>

            <!-- Instruções -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="font-bold text-blue-900 mb-2">📱 Como pagar:</h3>
                <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Abra o app do seu banco</li>
                    <li>Escolha "Pagar com PIX"</li>
                    <li>Escaneie o QR Code ou cole o código</li>
                    <li>Confirme o pagamento de R$ 20,00</li>
                    <li>Aguarde a confirmação automática</li>
                </ol>
            </div>

            <!-- Informações -->
            <div class="text-center text-sm text-gray-600">
                <p>Após a confirmação do pagamento, seu plano será ativado automaticamente!</p>
                <p class="mt-2">Validade: 30 dias a partir da ativação</p>
            </div>
        </div>

        <!-- JavaScript para verificar pagamento -->
        <script>
            const chargeId = '<?php echo $pixData['charge_id']; ?>';
            let checkInterval = null;
            
            // Iniciar verificação após 5 segundos
            setTimeout(() => {
                verificarPagamento();
                checkInterval = setInterval(verificarPagamento, 5000); // A cada 5s
            }, 5000);
            
            async function verificarPagamento() {
                try {
                    const response = await fetch(`/api/verificar_pagamento.php?charge_id=${chargeId}`);
                    const data = await response.json();
                    
                    if (data.pago) {
                        // PAGAMENTO CONFIRMADO!
                        clearInterval(checkInterval);
                        
                        document.getElementById('status-text').innerHTML = 
                            '<span class="text-green-600 font-bold">✅ Pagamento Confirmado!</span>';
                        
                        document.getElementById('qrcode-img').style.opacity = '0.5';
                        
                        // Redirecionar após 2 segundos
                        setTimeout(() => {
                            window.location.href = '/index.php?upgraded=1';
                        }, 2000);
                    }
                } catch (error) {
                    console.error('Erro ao verificar pagamento:', error);
                }
            }
            
            function copiarCodigo() {
                const input = document.getElementById('pix-code');
                input.select();
                document.execCommand('copy');
                
                alert('✅ Código PIX copiado!');
            }
        </script>

    <?php else: ?>
        <!-- Tela Inicial - Comparação de Planos -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <!-- Comparação -->
            <div class="grid md:grid-cols-2 gap-6 p-8">
                <!-- Plano Free -->
                <div class="border-2 border-gray-200 rounded-xl p-6">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Free</h3>
                        <p class="text-gray-600 text-sm">Plano Atual</p>
                    </div>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400">❌</span>
                            <span class="text-sm text-gray-600">5 estabelecimentos</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400">❌</span>
                            <span class="text-sm text-gray-600">5 consignações por estabelecimento</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400">❌</span>
                            <span class="text-sm text-gray-600">Funcionalidades limitadas</span>
                        </li>
                    </ul>
                </div>

                <!-- Plano Pro -->
                <div class="border-2 border-purple-500 rounded-xl p-6 bg-gradient-to-br from-purple-50 to-pink-50 relative">
                    <div class="absolute top-0 right-0 bg-purple-600 text-white px-3 py-1 rounded-bl-lg text-xs font-bold">
                        RECOMENDADO
                    </div>
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-purple-900 mb-2">Pro</h3>
                        <p class="text-3xl font-bold text-purple-600">R$ 20<span class="text-lg">/mês</span></p>
                    </div>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-start gap-2">
                            <span class="text-green-500">✅</span>
                            <span class="text-sm text-gray-900 font-medium">Estabelecimentos ilimitados</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-500">✅</span>
                            <span class="text-sm text-gray-900 font-medium">Consignações ilimitadas</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-500">✅</span>
                            <span class="text-sm text-gray-900 font-medium">Todas as funcionalidades</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-500">✅</span>
                            <span class="text-sm text-gray-900 font-medium">Suporte prioritário</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Botão de Upgrade -->
            <div class="bg-gray-50 p-6 text-center">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <button 
                        type="submit"
                        name="criar_pix"
                        class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold text-lg rounded-xl hover:from-purple-700 hover:to-pink-700 transition transform hover:scale-105 shadow-lg"
                    >
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                        </svg>
                        Pagar com PIX - R$ 20,00
                    </button>
                    <p class="text-sm text-gray-600 mt-3">Pagamento único mensal • Cancele quando quiser</p>
                </form>
            </div>
        </div>

        <!-- Benefícios -->
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Ativação Imediata</h3>
                <p class="text-sm text-gray-600">Seu plano é ativado automaticamente após o pagamento</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Pagamento Seguro</h3>
                <p class="text-sm text-gray-600">Processado pela API Pagou com total segurança</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Sem Fidelidade</h3>
                <p class="text-sm text-gray-600">Cancele quando quiser, sem multas ou taxas</p>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
