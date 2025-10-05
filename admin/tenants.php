<?php
/**
 * Gestão de Assinantes
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

$success = '';
$error = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenantId = $_POST['tenant_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    try {
        switch ($action) {
            case 'block':
                if ($superAdmin->blockTenant($tenantId, $_POST['motivo'] ?? null)) {
                    $success = 'Tenant bloqueado com sucesso!';
                }
                break;
                
            case 'unblock':
                if ($superAdmin->unblockTenant($tenantId)) {
                    $success = 'Tenant desbloqueado com sucesso!';
                }
                break;
                
            case 'change_plan':
                $novoPlano = $_POST['novo_plano'] ?? null;
                if ($superAdmin->changePlan($tenantId, $novoPlano)) {
                    $success = 'Plano alterado com sucesso!';
                }
                break;
                
            case 'extend':
                $dias = $_POST['dias'] ?? 30;
                if ($superAdmin->extendExpiration($tenantId, $dias)) {
                    $success = "Vencimento estendido em {$dias} dias!";
                }
                break;
            case 'delete':
                if ($superAdmin->deleteTenant($tenantId)) {
                    $success = 'Tenant excluído com sucesso!';
                }
                break;
            case 'send_notification':
                require_once '../classes/Notification.php';
                $notification = new Notification();
                
                $titulo = $_POST['titulo'] ?? null;
                $mensagem = $_POST['mensagem'] ?? null;
                $tipo = $_POST['tipo'] ?? 'info';
                $enviarEmail = isset($_POST['enviar_email']);
                
                if ($titulo && $mensagem) {
                    if ($notification->create($tenantId, $titulo, $mensagem, $tipo, $enviarEmail, $admin['id'])) {
                        $metodo = $enviarEmail ? 'email e notificação' : 'notificação';
                        $success = "Notificação enviada com sucesso via {$metodo}!";
                    } else {
                        $error = 'Erro ao enviar notificação';
                    }
                } else {
                    $error = 'Título e mensagem são obrigatórios';
                }
                break;
                
            case 'reset_password':
                $novaSenha = $_POST['nova_senha'] ?? null;
                if ($novaSenha && strlen($novaSenha) >= 6) {
                    $db = Database::getInstance()->getConnection();
                    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE tenant_id = ? LIMIT 1");
                    if ($stmt->execute([$senhaHash, $tenantId])) {
                        $success = "Senha resetada com sucesso! Nova senha: {$novaSenha}";
                        
                        // Log da ação
                        $stmt = $db->prepare("SELECT nome_empresa FROM tenants WHERE id = ?");
                        $stmt->execute([$tenantId]);
                        $tenant = $stmt->fetch();
                        
                        $superAdmin->logAction('reset_senha', $tenantId, "Senha resetada para tenant: {$tenant['nome_empresa']}");
                    } else {
                        $error = 'Erro ao resetar senha';
                    }
                } else {
                    $error = 'Senha deve ter no mínimo 6 caracteres';
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Erro: ' . $e->getMessage();
    }
}

// Filtros
$filters = [
    'plano' => $_GET['plano'] ?? '',
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$tenants = $superAdmin->listTenants($filters);

$pageTitle = 'Gestão de Assinantes';
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
                <a href="/admin/tenants.php" class="px-4 py-3 text-sm font-medium text-purple-600 border-b-2 border-purple-600 whitespace-nowrap">
                    👥 Assinantes
                </a>
                <a href="/admin/financeiro.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
                    💰 Financeiro
                </a>
                <a href="/admin/gateways.php" class="px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap">
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
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Gestão de Assinantes</h1>
            <p class="text-gray-600">Gerencie todos os clientes da plataforma</p>
        </div>

        <?php 
        // Incluir sistema de notificações flutuantes
        include 'includes/notifications.php'; 
        ?>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Busca -->
                <div class="md:col-span-2">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="🔍 Buscar por nome ou email..."
                        value="<?php echo htmlspecialchars($filters['search']); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                    >
                </div>

                <!-- Filtro Plano -->
                <select name="plano" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">Todos os planos</option>
                    <option value="free" <?php echo $filters['plano'] === 'free' ? 'selected' : ''; ?>>Free</option>
                    <option value="pro" <?php echo $filters['plano'] === 'pro' ? 'selected' : ''; ?>>Pro</option>
                </select>

                <!-- Filtro Status -->
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">Todos os status</option>
                    <option value="ativo" <?php echo $filters['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="suspenso" <?php echo $filters['status'] === 'suspenso' ? 'selected' : ''; ?>>Suspenso</option>
                    <option value="cancelado" <?php echo $filters['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    <option value="trial" <?php echo $filters['status'] === 'trial' ? 'selected' : ''; ?>>Trial</option>
                </select>

                <button type="submit" class="md:col-span-4 px-6 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition">
                    Filtrar
                </button>
            </form>
        </div>

        <!-- Listagem de Assinantes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Empresa</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Plano</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Uso</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Vencimento</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($tenants as $tenant): ?>
                            <tr class="hover:bg-gray-50">
                                <!-- Empresa -->
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($tenant['nome_empresa']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($tenant['email_principal']); ?></p>
                                        <p class="text-xs text-gray-400 mt-1">ID: <?php echo $tenant['id']; ?></p>
                                    </div>
                                </td>

                                <!-- Plano -->
                                <td class="px-6 py-4 text-center">
                                    <?php if ($tenant['plano'] === 'pro'): ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-bold">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            PRO
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                                            FREE
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 text-center">
                                    <?php
                                    $statusColors = [
                                        'ativo' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Ativo'],
                                        'suspenso' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'label' => 'Suspenso'],
                                        'cancelado' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Cancelado'],
                                        'trial' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Trial']
                                    ];
                                    $statusInfo = $statusColors[$tenant['status']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => $tenant['status']];
                                    ?>
                                    <span class="px-3 py-1 <?php echo $statusInfo['bg']; ?> <?php echo $statusInfo['text']; ?> rounded-full text-xs font-medium">
                                        <?php echo $statusInfo['label']; ?>
                                    </span>
                                </td>

                                <!-- Uso -->
                                <td class="px-6 py-4 text-center text-sm text-gray-600">
                                    <div class="flex flex-col gap-1">
                                        <span>🏢 <?php echo $tenant['total_estabelecimentos']; ?> estab.</span>
                                        <span>📋 <?php echo $tenant['total_consignacoes']; ?> consig.</span>
                                    </div>
                                </td>

                                <!-- Vencimento -->
                                <td class="px-6 py-4 text-center text-sm">
                                    <?php if ($tenant['data_vencimento']): ?>
                                        <?php
                                        $vencimento = new DateTime($tenant['data_vencimento']);
                                        $hoje = new DateTime();
                                        $diff = $hoje->diff($vencimento);
                                        $vencido = $vencimento < $hoje;
                                        ?>
                                        <span class="<?php echo $vencido ? 'text-red-600 font-bold' : 'text-gray-600'; ?>">
                                            <?php echo $vencimento->format('d/m/Y'); ?>
                                        </span>
                                        <?php if ($vencido): ?>
                                            <p class="text-xs text-red-500 mt-1">⚠️ Vencido</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Ações -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Bloquear/Desbloquear -->
                                        <?php if ($tenant['status'] === 'ativo'): ?>
                                            <button 
                                                onclick="blockTenant(<?php echo $tenant['id']; ?>, '<?php echo htmlspecialchars($tenant['nome_empresa']); ?>')"
                                                class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition"
                                                title="Bloquear"
                                            >
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="unblock">
                                                <input type="hidden" name="tenant_id" value="<?php echo $tenant['id']; ?>">
                                                <button 
                                                    type="submit"
                                                    class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition"
                                                    title="Desbloquear"
                                                >
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Alterar Plano -->
                                        <button 
                                            onclick="changePlan(<?php echo $tenant['id']; ?>, '<?php echo $tenant['plano']; ?>')"
                                            class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition"
                                            title="Alterar Plano"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>

                                        <!-- Estender Vencimento -->
                                        <?php if ($tenant['plano'] === 'pro'): ?>
                                            <button 
                                                onclick="extendExpiration(<?php echo $tenant['id']; ?>)"
                                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                                title="Estender Vencimento"
                                            >
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Resetar Senha -->
                                        <button 
                                            onclick="resetPassword(<?php echo $tenant['id']; ?>, '<?php echo htmlspecialchars($tenant['nome_empresa']); ?>')"
                                            class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                                            title="Resetar Senha"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>

                                        <!-- Excluir -->
                                        <button 
                                            onclick="deleteTenant(<?php echo $tenant['id']; ?>, '<?php echo htmlspecialchars($tenant['nome_empresa']); ?>')"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                            title="Excluir"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($tenants)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p>Nenhum tenant encontrado</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modals e Scripts -->
    <script>
    function blockTenant(id, nome) {
        const motivo = prompt(`Bloquear tenant "${nome}"?\n\nMotivo (opcional):`);
        if (motivo !== null) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="block">
                <input type="hidden" name="tenant_id" value="${id}">
                <input type="hidden" name="motivo" value="${motivo}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function changePlan(id, planoAtual) {
        const novoPlano = planoAtual === 'free' ? 'pro' : 'free';
        if (confirm(`Alterar plano para ${novoPlano.toUpperCase()}?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="change_plan">
                <input type="hidden" name="tenant_id" value="${id}">
                <input type="hidden" name="novo_plano" value="${novoPlano}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function extendExpiration(id) {
        const dias = prompt('Estender vencimento por quantos dias?', '30');
        if (dias && !isNaN(dias)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="extend">
                <input type="hidden" name="tenant_id" value="${id}">
                <input type="hidden" name="dias" value="${dias}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function resetPassword(id, nome) {
        const novaSenha = prompt(`🔑 Resetar Senha\n\nTenant: ${nome}\n\nDigite a nova senha (mínimo 6 caracteres):`);
        
        if (novaSenha) {
            if (novaSenha.length < 6) {
                alert('❌ Senha deve ter no mínimo 6 caracteres!');
                return;
            }
            
            const confirmar = confirm(`✅ Confirmar reset de senha?\n\nTenant: ${nome}\nNova senha: ${novaSenha}\n\n⚠️ Esta ação será registrada nos logs.`);
            
            if (confirmar) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="tenant_id" value="${id}">
                    <input type="hidden" name="nova_senha" value="${novaSenha}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    }

    function deleteTenant(id, nome) {
        if (confirm(`⚠️ ATENÇÃO!\n\nTem certeza que deseja EXCLUIR o tenant "${nome}"?\n\nEsta ação é IRREVERSÍVEL e apagará:\n- Todos os dados do tenant\n- Usuários\n- Estabelecimentos\n- Consignações\n- Produtos\n\nDigite "EXCLUIR" para confirmar:`)) {
            const confirmacao = prompt('Digite "EXCLUIR" para confirmar:');
            if (confirmacao === 'EXCLUIR') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="tenant_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    }
    </script>

</body>
</html>
