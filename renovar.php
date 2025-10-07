<?php
/**
 * Página de Renovação Antecipada
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
$pageTitle = 'Renovar Assinatura';

// Se não é Pro, redirecionar para upgrade
if ($tenant['plano'] !== 'pro') {
    header('Location: /upgrade_pix.php');
    exit;
}

$error = '';
$pixData = null;

// Processar renovação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renovar'])) {
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

// Calcular dias restantes
$diasRestantes = 0;
$dataExpiracao = null;
if ($tenant['subscription_expires_at']) {
    $dataExpiracao = new DateTime($tenant['subscription_expires_at']);
    $hoje = new DateTime();
    $diff = $hoje->diff($dataExpiracao);
    $diasRestantes = $diff->days;
    if ($diff->invert) {
        $diasRestantes = 0; // Já expirou
    }
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 pb-12">
    
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Renovar Assinatura</h1>
        <p class="text-gray-600">Mantenha seu acesso ilimitado</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <p class="text-red-700 font-medium"><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($pixData): ?>
        <!-- Tela de Pagamento PIX (mesmo código do upgrade_pix.php) -->
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Pague com PIX</h2>
                <p id="status-text" class="text-gray-600">Aguardando pagamento...</p>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-200 rounded-xl p-6 mb-6">
                <div class="text-center mb-4">
                    <img 
                        id="qrcode-img"
                        src="data:image/png;base64,<?php echo $pixData['qrcode_image']; ?>" 
                        alt="QR Code PIX"
                        class="inline-block w-64 h-64 border-4 border-white rounded-lg shadow-lg"
                    >
                </div>
                
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
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-900 font-medium mb-2">
                    ℹ️ Renovação Antecipada
                </p>
                <p class="text-sm text-blue-800">
                    Você ainda tem <strong><?php echo $diasRestantes; ?> dias</strong> no seu plano atual.
                    Ao renovar agora, os dias serão somados: <strong>+30 dias</strong> a partir de 
                    <strong><?php echo $dataExpiracao->format('d/m/Y'); ?></strong>.
                </p>
            </div>
        </div>

        <script>
            const chargeId = '<?php echo $pixData['charge_id']; ?>';
            let checkInterval = null;
            
            setTimeout(() => {
                verificarPagamento();
                checkInterval = setInterval(verificarPagamento, 5000);
            }, 5000);
            
            async function verificarPagamento() {
                try {
                    const response = await fetch(`/api/verificar_renovacao.php?charge_id=${chargeId}`);
                    const data = await response.json();
                    
                    if (data.pago) {
                        clearInterval(checkInterval);
                        
                        document.getElementById('status-text').innerHTML = 
                            '<span class="text-green-600 font-bold">✅ Renovação Confirmada!</span>';
                        
                        setTimeout(() => {
                            window.location.href = '/assinatura.php?renovado=1';
                        }, 2000);
                    }
                } catch (error) {
                    console.error('Erro:', error);
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
        <!-- Tela Inicial -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Status Atual -->
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-200 rounded-xl p-6 mb-6">
                <h3 class="text-xl font-bold text-purple-900 mb-4">📊 Status da Assinatura</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Plano Atual</p>
                        <p class="text-2xl font-bold text-purple-600">Pro</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Dias Restantes</p>
                        <p class="text-2xl font-bold <?php echo $diasRestantes <= 5 ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo $diasRestantes; ?> dias
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Expira em</p>
                        <p class="text-lg font-semibold text-gray-900">
                            <?php echo $dataExpiracao ? $dataExpiracao->format('d/m/Y') : 'N/A'; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Valor Mensal</p>
                        <p class="text-lg font-semibold text-gray-900">R$ 20,00</p>
                    </div>
                </div>
            </div>

            <!-- Informações -->
            <div class="mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-3">✨ Ao renovar agora:</h3>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-green-500 mt-1">✅</span>
                        <span class="text-gray-700">Seus dias atuais serão mantidos</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-500 mt-1">✅</span>
                        <span class="text-gray-700">+30 dias serão adicionados após a data de expiração atual</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-500 mt-1">✅</span>
                        <span class="text-gray-700">Você não perde nenhum dia pago</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-500 mt-1">✅</span>
                        <span class="text-gray-700">Ativação imediata após confirmação</span>
                    </li>
                </ul>
            </div>

            <!-- Botão -->
            <form method="POST" class="text-center">
                <?php echo csrfField(); ?>
                <button 
                    type="submit"
                    name="renovar"
                    class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold text-lg rounded-xl hover:from-purple-700 hover:to-pink-700 transition transform hover:scale-105 shadow-lg"
                >
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                    </svg>
                    Renovar por R$ 20,00
                </button>
                <p class="text-sm text-gray-600 mt-3">Pagamento via PIX • Confirmação imediata</p>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
