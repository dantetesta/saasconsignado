<?php
/**
 * Configuração de Gateways de Pagamento
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
$db = Database::getInstance()->getConnection();

$success = '';
$error = '';

// Processar ativação/desativação de gateway
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $gatewayId = $_POST['gateway_id'] ?? null;
    
    if ($_POST['action'] === 'toggle') {
        $stmt = $db->prepare("UPDATE payment_gateways SET ativo = NOT ativo WHERE id = ?");
        if ($stmt->execute([$gatewayId])) {
            $success = 'Status do gateway atualizado!';
        } else {
            $error = 'Erro ao atualizar gateway';
        }
    }
}

// Buscar gateways
$stmt = $db->query("SELECT * FROM payment_gateways ORDER BY ordem ASC");
$gateways = $stmt->fetchAll();

$pageTitle = 'Gateways de Pagamento';
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
                    👥 Tenants
                </a>
                <a href="/admin/financeiro.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    💰 Financeiro
                </a>
                <a href="/admin/gateways.php" class="px-4 py-3 text-sm font-medium text-purple-600 border-b-2 border-purple-600 whitespace-nowrap">
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
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Gateways de Pagamento</h1>
            <p class="text-gray-600">Configure os gateways que serão utilizados para cobranças das assinaturas</p>
        </div>

        <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <p class="text-green-700"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Aviso -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6">
            <div class="flex gap-3">
                <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-blue-900 mb-1">Configuração em Desenvolvimento</h3>
                    <p class="text-sm text-blue-800">
                        Os gateways estão cadastrados mas ainda não configurados. 
                        Você poderá ativar/desativar cada um e configurar as credenciais de API posteriormente.
                    </p>
                </div>
            </div>
        </div>

        <!-- Lista de Gateways -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($gateways as $gateway): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 <?php echo $gateway['ativo'] ? 'ring-2 ring-green-500' : ''; ?>">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($gateway['nome']); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($gateway['descricao']); ?></p>
                        </div>
                        
                        <!-- Toggle Status -->
                        <form method="POST" class="ml-4">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="gateway_id" value="<?php echo $gateway['id']; ?>">
                            <label class="relative inline-block w-12 h-6">
                                <input 
                                    type="checkbox" 
                                    <?php echo $gateway['ativo'] ? 'checked' : ''; ?>
                                    onchange="this.form.submit()"
                                    class="sr-only peer"
                                >
                                <div class="block h-6 w-12 rounded-full bg-gray-300 peer-checked:bg-green-500 transition-colors cursor-pointer"></div>
                                <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-6"></span>
                            </label>
                        </form>
                    </div>

                    <!-- Métodos Disponíveis -->
                    <div class="mb-4">
                        <p class="text-xs font-medium text-gray-500 mb-2">Métodos disponíveis:</p>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                            $metodos = json_decode($gateway['metodos_disponiveis'], true);
                            $metodosLabels = [
                                'pix' => ['label' => 'PIX', 'color' => 'green'],
                                'boleto' => ['label' => 'Boleto', 'color' => 'blue'],
                                'cartao' => ['label' => 'Cartão', 'color' => 'purple']
                            ];
                            foreach ($metodos as $metodo): 
                                $info = $metodosLabels[$metodo] ?? ['label' => $metodo, 'color' => 'gray'];
                            ?>
                                <span class="px-2 py-1 bg-<?php echo $info['color']; ?>-100 text-<?php echo $info['color']; ?>-700 text-xs font-medium rounded-full">
                                    <?php echo $info['label']; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="pt-4 border-t border-gray-200">
                        <?php if ($gateway['ativo']): ?>
                            <span class="inline-flex items-center gap-1 text-sm font-medium text-green-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Ativo
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 text-sm font-medium text-gray-500">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Inativo
                            </span>
                        <?php endif; ?>
                        
                        <button 
                            onclick="alert('Configuração de credenciais será implementada em breve!')"
                            class="float-right px-3 py-1 text-sm text-purple-600 hover:text-purple-700 font-medium"
                        >
                            ⚙️ Configurar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Informações Adicionais -->
        <div class="mt-8 bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-6">
            <h3 class="font-bold text-purple-900 mb-3">📌 Próximos Passos:</h3>
            <ul class="space-y-2 text-sm text-purple-800">
                <li class="flex items-start gap-2">
                    <span class="text-purple-600">1.</span>
                    <span>Ative o gateway que deseja utilizar usando o switcher acima</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-purple-600">2.</span>
                    <span>Configure as credenciais de API (em desenvolvimento)</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-purple-600">3.</span>
                    <span>Teste a integração antes de disponibilizar para os clientes</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-purple-600">4.</span>
                    <span>Os métodos de pagamento serão exibidos automaticamente no checkout</span>
                </li>
            </ul>
        </div>

    </div>

</body>
</html>
