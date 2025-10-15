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

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $gatewayId = $_POST['gateway_id'] ?? null;
    
    if ($_POST['action'] === 'toggle') {
        // Ativar/Desativar gateway
        $stmt = $db->prepare("UPDATE payment_gateways SET ativo = NOT ativo WHERE id = ?");
        if ($stmt->execute([$gatewayId])) {
            $success = 'Status do gateway atualizado!';
        } else {
            $error = 'Erro ao atualizar gateway';
        }
    }
    elseif ($_POST['action'] === 'configure') {
        // Configurar credenciais
        $apiKey = trim($_POST['api_key'] ?? '');
        $currentApiKey = trim($_POST['current_api_key'] ?? '');
        $ambiente = $_POST['ambiente'] ?? 'production';
        
        // Se o campo estiver vazio, manter a chave atual (caso já configurada)
        if (empty($apiKey) && !empty($currentApiKey)) {
            $apiKey = $currentApiKey;
        }
        
        if (empty($apiKey)) {
            $error = 'API Key é obrigatória';
        } else {
            // Salvar configuração
            $config = [
                'api_key' => $apiKey,
                'ambiente' => $ambiente
            ];
            
            $stmt = $db->prepare("UPDATE payment_gateways SET configuracao = ?, configurado = 1 WHERE id = ?");
            if ($stmt->execute([json_encode($config), $gatewayId])) {
                $success = 'Gateway configurado com sucesso!';
            } else {
                $error = 'Erro ao salvar configuração';
            }
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
    <nav class="bg-gradient-to-r from-blue-600 to-emerald-600 text-white shadow-lg">
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
                <a href="/admin/financeiro.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    💰 Financeiro
                </a>
                <a href="/admin/pagamentos.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    💳 Pagamentos
                </a>
                <a href="/admin/gateways.php" class="px-4 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-600 whitespace-nowrap">
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
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Gateways de Pagamento</h1>
            <p class="text-gray-600">Configure os gateways que serão utilizados para cobranças das assinaturas</p>
        </div>

        <?php 
        // Incluir sistema de notificações flutuantes
        include 'includes/notifications.php'; 
        ?>

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
                            onclick="openConfigModal(<?php echo $gateway['id']; ?>, '<?php echo htmlspecialchars($gateway['nome']); ?>', <?php echo htmlspecialchars(json_encode($gateway['configuracao'])); ?>)"
                            class="float-right px-3 py-1 text-sm text-blue-600 hover:text-blue-700 font-medium hover:underline"
                        >
                            ⚙️ Configurar
                        </button>
                        
                        <?php if ($gateway['configurado']): ?>
                            <span class="float-right mr-3 px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                ✓ Configurado
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Informações Adicionais -->
        <div class="mt-8 bg-gradient-to-r from-purple-50 to-pink-50 border border-blue-200 rounded-xl p-6">
            <h3 class="font-bold text-purple-900 mb-3">📌 Próximos Passos:</h3>
            <ul class="space-y-2 text-sm text-purple-800">
                <li class="flex items-start gap-2">
                    <span class="text-blue-600">1.</span>
                    <span>Ative o gateway que deseja utilizar usando o switcher acima</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600">2.</span>
                    <span>Configure as credenciais de API (em desenvolvimento)</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600">3.</span>
                    <span>Teste a integração antes de disponibilizar para os clientes</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600">4.</span>
                    <span>Os métodos de pagamento serão exibidos automaticamente no checkout</span>
                </li>
            </ul>
        </div>

    </div>

    <!-- Modal de Configuração -->
    <div id="configModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <form method="POST" id="configForm">
                <input type="hidden" name="action" value="configure">
                <input type="hidden" name="gateway_id" id="modal_gateway_id">
                <input type="hidden" name="current_api_key" id="current_api_key">
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-emerald-600 text-white p-6 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold mb-1">Configurar Gateway</h2>
                            <p class="text-blue-100 text-sm" id="modal_gateway_name"></p>
                        </div>
                        <button type="button" onclick="closeConfigModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6">
                    <!-- API Key -->
                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">
                            🔑 API Key / Token
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="api_key" 
                                id="modal_api_key"
                                required
                                placeholder="Ex: 6476a737-7211-4e7c-ba1f-639eff09e270"
                                class="w-full px-4 py-3 pr-24 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                            >
                            <button 
                                type="button" 
                                onclick="toggleApiKeyVisibility()" 
                                class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded transition"
                                id="toggleApiKeyBtn"
                            >
                                👁️ Mostrar
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            💡 Obtenha sua API Key no painel do gateway de pagamento. A chave será ocultada após salvar.
                        </p>
                    </div>

                    <!-- Ambiente -->
                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">
                            🌍 Ambiente
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-500 transition">
                                <input 
                                    type="radio" 
                                    name="ambiente" 
                                    value="sandbox" 
                                    id="ambiente_sandbox"
                                    class="sr-only peer"
                                >
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">🧪 Sandbox (Teste)</div>
                                    <div class="text-xs text-gray-600">Para desenvolvimento</div>
                                </div>
                                <div class="hidden peer-checked:block">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </label>

                            <label class="relative flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-500 transition">
                                <input 
                                    type="radio" 
                                    name="ambiente" 
                                    value="production" 
                                    id="ambiente_production"
                                    checked
                                    class="sr-only peer"
                                >
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">🚀 Produção</div>
                                    <div class="text-xs text-gray-600">Pagamentos reais</div>
                                </div>
                                <div class="hidden peer-checked:block">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Aviso -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-yellow-800">
                                <strong>Atenção:</strong> Mantenha suas credenciais seguras. Nunca compartilhe sua API Key.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 rounded-b-2xl flex justify-end gap-3">
                    <button 
                        type="button"
                        onclick="closeConfigModal()"
                        class="px-6 py-2.5 text-gray-700 font-semibold hover:bg-gray-200 rounded-lg transition"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-emerald-700 transition"
                    >
                        💾 Salvar Configuração
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentApiKey = '';
        let isApiKeyMasked = false;
        
        function maskApiKey(apiKey) {
            if (!apiKey || apiKey.length < 8) return apiKey;
            // Mostrar apenas os últimos 4 caracteres
            const lastChars = apiKey.slice(-4);
            return '••••••••••••••••••••••••••••••••' + lastChars;
        }
        
        function toggleApiKeyVisibility() {
            const input = document.getElementById('modal_api_key');
            const btn = document.getElementById('toggleApiKeyBtn');
            
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈 Ocultar';
            } else {
                input.type = 'password';
                btn.textContent = '👁️ Mostrar';
            }
        }
        
        function openConfigModal(gatewayId, gatewayName, config) {
            document.getElementById('modal_gateway_id').value = gatewayId;
            document.getElementById('modal_gateway_name').textContent = gatewayName;
            
            const apiKeyInput = document.getElementById('modal_api_key');
            const currentApiKeyInput = document.getElementById('current_api_key');
            const toggleBtn = document.getElementById('toggleApiKeyBtn');
            
            // Preencher campos se já configurado
            if (config && config.api_key) {
                currentApiKey = config.api_key;
                isApiKeyMasked = true;
                
                // Salvar chave atual no campo hidden
                currentApiKeyInput.value = config.api_key;
                
                // Mostrar chave mascarada
                apiKeyInput.value = maskApiKey(config.api_key);
                apiKeyInput.type = 'text';
                apiKeyInput.placeholder = 'Chave configurada (oculta por segurança)';
                apiKeyInput.removeAttribute('required'); // Remover required quando já configurada
                toggleBtn.textContent = '🔄 Alterar';
                
                // Ao focar, limpar para permitir nova entrada
                apiKeyInput.addEventListener('focus', function clearOnFocus() {
                    if (isApiKeyMasked) {
                        apiKeyInput.value = '';
                        apiKeyInput.type = 'password';
                        apiKeyInput.placeholder = 'Digite a nova API Key ou deixe em branco para manter';
                        toggleBtn.textContent = '👁️ Mostrar';
                        isApiKeyMasked = false;
                    }
                    apiKeyInput.removeEventListener('focus', clearOnFocus);
                });
                
                if (config.ambiente === 'sandbox') {
                    document.getElementById('ambiente_sandbox').checked = true;
                } else {
                    document.getElementById('ambiente_production').checked = true;
                }
            } else {
                // Limpar campos
                currentApiKey = '';
                isApiKeyMasked = false;
                currentApiKeyInput.value = '';
                apiKeyInput.value = '';
                apiKeyInput.type = 'password';
                apiKeyInput.placeholder = 'Ex: 6476a737-7211-4e7c-ba1f-639eff09e270';
                apiKeyInput.setAttribute('required', 'required'); // Adicionar required quando nova
                toggleBtn.textContent = '👁️ Mostrar';
                document.getElementById('ambiente_production').checked = true;
            }
            
            document.getElementById('configModal').classList.remove('hidden');
        }
        
        function closeConfigModal() {
            document.getElementById('configModal').classList.add('hidden');
            // Reset
            const apiKeyInput = document.getElementById('modal_api_key');
            apiKeyInput.type = 'password';
            document.getElementById('toggleApiKeyBtn').textContent = '👁️ Mostrar';
            isApiKeyMasked = false;
        }
        
        // Fechar ao clicar fora
        document.getElementById('configModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeConfigModal();
            }
        });
        
        // Fechar com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeConfigModal();
            }
        });
    </script>

</body>
</html>
