<?php
/**
 * Logs de Ações Administrativas
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
$db = Database::getInstance()->getConnection();

$success = '';
$error = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    
    try {
        if ($action === 'delete_selected' && !empty($_POST['log_ids'])) {
            $logIds = $_POST['log_ids'];
            $placeholders = str_repeat('?,', count($logIds) - 1) . '?';
            $stmt = $db->prepare("DELETE FROM admin_logs WHERE id IN ($placeholders)");
            $stmt->execute($logIds);
            $success = count($logIds) . ' log(s) deletado(s) com sucesso!';
        }
        
        if ($action === 'delete_all') {
            $stmt = $db->query("DELETE FROM admin_logs");
            $count = $stmt->rowCount();
            $success = "Todos os logs foram deletados! ({$count} registros)";
        }
        
        if ($action === 'delete_by_date') {
            $dataInicio = $_POST['data_inicio'] ?? null;
            $dataFim = $_POST['data_fim'] ?? null;
            
            if ($dataInicio && $dataFim) {
                $stmt = $db->prepare("DELETE FROM admin_logs WHERE DATE(criado_em) BETWEEN ? AND ?");
                $stmt->execute([$dataInicio, $dataFim]);
                $count = $stmt->rowCount();
                $success = "{$count} log(s) deletado(s) no período selecionado!";
            }
        }
    } catch (Exception $e) {
        $error = 'Erro: ' . $e->getMessage();
    }
}

// Filtros
$filters = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'admin_id' => $_GET['admin_id'] ?? '',
    'acao' => $_GET['acao'] ?? ''
];

// Buscar logs com filtros
$where = [];
$params = [];

if (!empty($filters['data_inicio'])) {
    $where[] = "DATE(l.criado_em) >= ?";
    $params[] = $filters['data_inicio'];
}

if (!empty($filters['data_fim'])) {
    $where[] = "DATE(l.criado_em) <= ?";
    $params[] = $filters['data_fim'];
}

if (!empty($filters['admin_id'])) {
    $where[] = "l.admin_id = ?";
    $params[] = $filters['admin_id'];
}

if (!empty($filters['acao'])) {
    $where[] = "l.acao = ?";
    $params[] = $filters['acao'];
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT 
        l.*,
        a.nome as admin_nome,
        t.nome_empresa as tenant_nome
    FROM admin_logs l
    JOIN super_admins a ON l.admin_id = a.id
    LEFT JOIN tenants t ON l.tenant_id = t.id
    {$whereClause}
    ORDER BY l.criado_em DESC
    LIMIT 200
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = 'Logs Administrativos';
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
                <a href="/admin/gateways.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    🔗 Gateways
                </a>
                <a href="/admin/configuracoes.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    ⚙️ Configurações
                </a>
                <a href="/admin/logs.php" class="px-4 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-600 whitespace-nowrap">
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
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Logs de Ações Administrativas</h1>
            <p class="text-gray-600">Histórico de todas as ações realizadas no painel</p>
        </div>

        <?php 
        // Incluir sistema de notificações flutuantes
        include 'includes/notifications.php'; 
        ?>

        <!-- Filtros e Ações -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            
            <!-- Filtros -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">🔍 Filtrar Logs</h3>
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                            <input 
                                type="date" 
                                name="data_inicio" 
                                value="<?php echo htmlspecialchars($filters['data_inicio']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                            <input 
                                type="date" 
                                name="data_fim" 
                                value="<?php echo htmlspecialchars($filters['data_fim']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                            Filtrar
                        </button>
                        <a href="/admin/logs.php" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>

            <!-- Ações em Massa -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">🗑️ Deletar Logs</h3>
                
                <!-- Deletar Selecionados -->
                <button 
                    onclick="deleteSelected()"
                    class="w-full mb-3 px-4 py-2 bg-yellow-600 text-white font-medium rounded-lg hover:bg-yellow-700 transition"
                >
                    Deletar Selecionados
                </button>

                <!-- Deletar por Período -->
                <form method="POST" onsubmit="return confirm('⚠️ Deletar logs do período selecionado?')" class="mb-3">
                    <input type="hidden" name="action" value="delete_by_date">
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <input 
                            type="date" 
                            name="data_inicio" 
                            required
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            placeholder="De"
                        >
                        <input 
                            type="date" 
                            name="data_fim" 
                            required
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            placeholder="Até"
                        >
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-orange-600 text-white font-medium rounded-lg hover:bg-orange-700 transition">
                        Deletar por Período
                    </button>
                </form>

                <!-- Deletar Todos -->
                <form method="POST" onsubmit="return confirm('⚠️ ATENÇÃO!\n\nDeseja DELETAR TODOS OS LOGS?\n\nEsta ação é IRREVERSÍVEL!')">
                    <input type="hidden" name="action" value="delete_all">
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition">
                        Deletar Todos os Logs
                    </button>
                </form>
            </div>

        </div>

        <!-- Timeline de Logs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-center">
                                <input 
                                    type="checkbox" 
                                    id="selectAll" 
                                    onclick="toggleSelectAll(this)"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                                >
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Data/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Admin</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ação</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Assinante</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Descrição</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50">
                                <!-- Checkbox -->
                                <td class="px-6 py-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        name="log_checkbox" 
                                        value="<?php echo $log['id']; ?>"
                                        class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                                    >
                                </td>
                                
                                <!-- Data/Hora -->
                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                    <?php 
                                    $data = new DateTime($log['criado_em']);
                                    echo $data->format('d/m/Y H:i');
                                    ?>
                                </td>

                                <!-- Admin -->
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($log['admin_nome']); ?>
                                </td>

                                <!-- Ação -->
                                <td class="px-6 py-4">
                                    <?php
                                    $acaoLabels = [
                                        'bloquear_tenant' => ['label' => 'Bloqueio', 'color' => 'yellow'],
                                        'desbloquear_tenant' => ['label' => 'Desbloqueio', 'color' => 'green'],
                                        'alterar_plano' => ['label' => 'Alterar Plano', 'color' => 'purple'],
                                        'estender_vencimento' => ['label' => 'Estender', 'color' => 'blue'],
                                        'excluir_tenant' => ['label' => 'Exclusão', 'color' => 'red']
                                    ];
                                    $acaoInfo = $acaoLabels[$log['acao']] ?? ['label' => $log['acao'], 'color' => 'gray'];
                                    ?>
                                    <span class="px-2 py-1 bg-<?php echo $acaoInfo['color']; ?>-100 text-<?php echo $acaoInfo['color']; ?>-700 rounded text-xs font-medium">
                                        <?php echo $acaoInfo['label']; ?>
                                    </span>
                                </td>

                                <!-- Tenant -->
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo $log['tenant_nome'] ? htmlspecialchars($log['tenant_nome']) : '-'; ?>
                                </td>

                                <!-- Descrição -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo htmlspecialchars($log['descricao'] ?? '-'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p>Nenhum log registrado ainda</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
    // Selecionar/Desselecionar todos
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('input[name="log_checkbox"]');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }

    // Deletar logs selecionados
    function deleteSelected() {
        const checkboxes = document.querySelectorAll('input[name="log_checkbox"]:checked');
        
        if (checkboxes.length === 0) {
            alert('⚠️ Selecione pelo menos um log para deletar!');
            return;
        }

        if (!confirm(`⚠️ Deletar ${checkboxes.length} log(s) selecionado(s)?`)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete_selected">';
        
        checkboxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'log_ids[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }
    </script>

</body>
</html>
