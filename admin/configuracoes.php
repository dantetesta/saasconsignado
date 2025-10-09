<?php
/**
 * Configurações Gerais do Sistema
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

// Processar salvamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    try {
        $db->beginTransaction();
        
        // Atualizar cada configuração
        foreach ($_POST as $key => $value) {
            if ($key !== 'salvar') {
                $stmt = $db->prepare("UPDATE system_settings SET valor = ? WHERE chave = ?");
                $stmt->execute([$value, $key]);
            }
        }
        
        $db->commit();
        $success = 'Configurações salvas com sucesso!';
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Erro ao salvar: ' . $e->getMessage();
    }
}

// Buscar configurações
$stmt = $db->query("SELECT * FROM system_settings ORDER BY grupo, chave");
$allSettings = $stmt->fetchAll();

// Organizar por grupo
$settings = [];
foreach ($allSettings as $setting) {
    $settings[$setting['grupo']][] = $setting;
}

$pageTitle = 'Configurações do Sistema';
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
                <a href="/admin/financeiro.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    💰 Financeiro
                </a>
                <a href="/admin/pagamentos.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    💳 Pagamentos
                </a>
                <a href="/admin/gateways.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    🔗 Gateways
                </a>
                <a href="/admin/configuracoes.php" class="px-4 py-3 text-sm font-medium text-purple-600 border-b-2 border-purple-600 whitespace-nowrap">
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
            <h1 class="text-2xl font-bold text-gray-900 mb-2">⚙️ Configurações do Sistema</h1>
            <p class="text-gray-600">Ajuste os parâmetros gerais do SaaS</p>
        </div>

        <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-green-700 font-medium"><?php echo $success; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <p class="text-red-700 font-medium"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            
            <?php foreach ($settings as $grupo => $configs): ?>
                <!-- Card por Grupo -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    <!-- Header do Grupo -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-b border-purple-200 px-6 py-4">
                        <h2 class="text-xl font-bold text-purple-900">
                            <?php 
                            $icones = [
                                'planos' => '💎',
                                'notificacoes' => '🔔',
                                'email' => '📧',
                                'sistema' => '⚙️'
                            ];
                            echo ($icones[$grupo] ?? '📌') . ' ';
                            echo ucfirst($grupo); 
                            ?>
                        </h2>
                    </div>

                    <!-- Configurações -->
                    <div class="p-6 space-y-6">
                        <?php foreach ($configs as $config): ?>
                            <div class="flex items-start gap-4 pb-6 border-b border-gray-100 last:border-0 last:pb-0">
                                <div class="flex-1">
                                    <label class="block text-sm font-bold text-gray-900 mb-1">
                                        <?php echo htmlspecialchars($config['descricao']); ?>
                                    </label>
                                    <p class="text-xs text-gray-500 mb-3">
                                        Chave: <code class="bg-gray-100 px-2 py-0.5 rounded"><?php echo $config['chave']; ?></code>
                                    </p>
                                    
                                    <?php if ($config['tipo'] === 'boolean'): ?>
                                        <!-- Toggle Switch -->
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                name="<?php echo $config['chave']; ?>" 
                                                value="1"
                                                <?php echo $config['valor'] == '1' ? 'checked' : ''; ?>
                                                class="sr-only peer"
                                            >
                                            <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600"></div>
                                            <span class="ml-3 text-sm font-medium text-gray-700">
                                                <?php echo $config['valor'] == '1' ? 'Ativado' : 'Desativado'; ?>
                                            </span>
                                        </label>
                                        
                                    <?php elseif ($config['tipo'] === 'number'): ?>
                                        <!-- Input Numérico -->
                                        <div class="flex items-center gap-3">
                                            <?php if (strpos($config['chave'], 'preco') !== false): ?>
                                                <span class="text-lg font-bold text-gray-700">R$</span>
                                            <?php endif; ?>
                                            
                                            <input 
                                                type="number" 
                                                name="<?php echo $config['chave']; ?>" 
                                                value="<?php echo htmlspecialchars($config['valor']); ?>"
                                                step="<?php echo strpos($config['chave'], 'preco') !== false ? '0.01' : '1'; ?>"
                                                min="0"
                                                class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent font-bold text-lg"
                                            >
                                            
                                            <?php if (strpos($config['chave'], 'dias') !== false): ?>
                                                <span class="text-sm text-gray-600">dias</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                    <?php else: ?>
                                        <!-- Input Texto -->
                                        <input 
                                            type="text" 
                                            name="<?php echo $config['chave']; ?>" 
                                            value="<?php echo htmlspecialchars($config['valor']); ?>"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        >
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Botão Salvar -->
            <div class="flex justify-end gap-4">
                <a 
                    href="/admin/index.php" 
                    class="px-6 py-3 text-gray-700 font-semibold hover:bg-gray-100 rounded-lg transition"
                >
                    Cancelar
                </a>
                <button 
                    type="submit" 
                    name="salvar"
                    class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold rounded-lg hover:from-purple-700 hover:to-pink-700 transition shadow-lg"
                >
                    💾 Salvar Configurações
                </button>
            </div>
        </form>

        <!-- Informações -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex gap-3">
                <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Informações Importantes</h3>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• As alterações entram em vigor imediatamente para novos pagamentos</li>
                        <li>• Assinaturas ativas mantêm o valor contratado</li>
                        <li>• Notificações são enviadas automaticamente pelo cron job</li>
                        <li>• Limites do Plano Free são verificados em tempo real</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
