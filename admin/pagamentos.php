<?php
/**
 * Página de Histórico de Pagamentos
 * Visualizar e gerenciar pagamentos dos assinantes
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.1.0
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
$superAdmin = new SuperAdmin(); // Instância para métodos não-estáticos

$success = '';
$error = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'refund_payment') {
            $paymentId = (int)$_POST['payment_id'];
            $reason = $_POST['reason'] ?? 'Solicitação do cliente';
            
            // Buscar dados do pagamento
            $stmt = $db->prepare("
                SELECT sp.*, t.nome_empresa as tenant_name 
                FROM subscription_payments sp 
                JOIN tenants t ON sp.tenant_id = t.id 
                WHERE sp.id = ? AND sp.status = 'paid'
            ");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();
            
            if (!$payment) {
                throw new Exception('Pagamento não encontrado ou não pode ser reembolsado');
            }
            
            // Processar reembolso via API Pagou
            require_once '../classes/PagouAPI.php';
            $pagouAPI = new PagouAPI();
            
            $refundResult = $pagouAPI->processarReembolso($payment['charge_id'], $payment['amount'], $reason);
            
            if ($refundResult['success']) {
                // Atualizar status do pagamento
                $stmt = $db->prepare("
                    UPDATE subscription_payments 
                    SET status = 'refunded', 
                        refund_reason = ?,
                        refund_id = ?,
                        refunded_at = NOW(),
                        refunded_amount = ?,
                        manual_refund = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $reason, 
                    $refundResult['refund_id'], 
                    $payment['amount'],
                    isset($refundResult['manual_process']) ? 1 : 0,
                    $paymentId
                ]);
                
                // Atualizar status do tenant
                $stmt = $db->prepare("
                    UPDATE tenants 
                    SET subscription_status = 'expired',
                        subscription_expires_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$payment['tenant_id']]);
                
                // Log da ação
                $superAdmin->logAction('refund_payment', $payment['tenant_id'], "Reembolso processado para pagamento #{$paymentId} - {$payment['tenant_name']} - Motivo: {$reason}");
                
                $success = 'Reembolso processado com sucesso!';
            } else {
                throw new Exception('Erro ao processar reembolso: ' . $refundResult['message']);
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Filtros
$status = $_GET['status'] ?? '';
$tenant = $_GET['tenant'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Construir query com filtros
$whereConditions = [];
$params = [];

if ($status) {
    $whereConditions[] = "sp.status = ?";
    $params[] = $status;
}

if ($tenant) {
    $whereConditions[] = "t.nome_empresa LIKE ?";
    $params[] = "%{$tenant}%";
}

if ($dateFrom) {
    $whereConditions[] = "DATE(sp.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $whereConditions[] = "DATE(sp.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Buscar pagamentos
$stmt = $db->prepare("
    SELECT 
        sp.*,
        t.nome_empresa as tenant_name,
        t.email_principal as tenant_email,
        t.plano as tenant_plan
    FROM subscription_payments sp
    JOIN tenants t ON sp.tenant_id = t.id
    {$whereClause}
    ORDER BY sp.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Estatísticas
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_payments,
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'refunded' THEN 1 END) as refunded_count,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) as total_refunded
    FROM subscription_payments
");
$stats = $stmt->fetch();

// Configurar variáveis para o template
$pageTitle = 'Pagamentos'; // Título da aba do navegador
$currentPage = 'pagamentos'; // Identificador para highlight no menu

// Incluir header e menu padrão
include 'includes/header.php';
include 'includes/menu.php';
?>

    <!-- Conteúdo -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <?php 
        // Incluir sistema de notificações flutuantes
        include 'includes/notifications.php'; 
        ?>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">💳 Histórico de Pagamentos</h1>
            <p class="text-gray-600">Visualize e gerencie todos os pagamentos dos assinantes</p>
        </div>

        <!-- Estatísticas -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total de Pagamentos</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_payments'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Receita Total</p>
                        <p class="text-2xl font-bold text-green-600">R$ <?php echo number_format($stats['total_revenue'] ?? 0, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Pendentes</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['pending_count'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Reembolsados</p>
                        <p class="text-2xl font-bold text-red-600">R$ <?php echo number_format($stats['total_refunded'] ?? 0, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🔍 Filtros</h3>
            
            <form method="GET" class="grid md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Todos</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Pago</option>
                        <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Reembolsado</option>
                        <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expirado</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assinante</label>
                    <input type="text" name="tenant" value="<?php echo htmlspecialchars($tenant); ?>" 
                           placeholder="Nome do assinante" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Pagamentos -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assinante</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expira</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($payments as $payment): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">#<?php echo $payment['id']; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($payment['tenant_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($payment['tenant_email']); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">R$ <?php echo number_format($payment['amount'], 2, ',', '.'); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusClasses = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'paid' => 'bg-green-100 text-green-800',
                                        'refunded' => 'bg-red-100 text-red-800',
                                        'expired' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $statusTexts = [
                                        'pending' => 'Pendente',
                                        'paid' => 'Pago',
                                        'refunded' => 'Reembolsado',
                                        'expired' => 'Expirado'
                                    ];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClasses[$payment['status']]; ?>">
                                        <?php echo $statusTexts[$payment['status']]; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $payment['expires_at'] ? date('d/m/Y', strtotime($payment['expires_at'])) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <?php if ($payment['status'] === 'paid'): ?>
                                            <button onclick="openRefundModal(<?php echo $payment['id']; ?>, '<?php echo addslashes($payment['tenant_name']); ?>', <?php echo $payment['amount']; ?>)" 
                                                    class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                Reembolsar
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="/admin/tenants.php?id=<?php echo $payment['tenant_id']; ?>" 
                                           class="text-blue-600 hover:text-purple-900 text-sm font-medium">
                                            Ver Assinante
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal de Reembolso -->
    <div id="refundModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Processar Reembolso</h3>
                        <p class="text-sm text-gray-600">Esta ação não pode ser desfeita</p>
                    </div>
                </div>
                
                <form method="POST" id="refundForm">
                    <input type="hidden" name="action" value="refund_payment">
                    <input type="hidden" name="payment_id" id="refundPaymentId">
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-700 mb-2">
                            <strong>Assinante:</strong> <span id="refundTenantName"></span><br>
                            <strong>Valor:</strong> R$ <span id="refundAmount"></span>
                        </p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Motivo do Reembolso</label>
                        <textarea name="reason" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" 
                                  rows="3" placeholder="Descreva o motivo do reembolso..."></textarea>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" onclick="closeRefundModal()" 
                                class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="flex-1 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition"
                                onclick="return confirm('Tem certeza que deseja processar este reembolso?')">
                            Reembolsar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRefundModal(paymentId, tenantName, amount) {
            document.getElementById('refundPaymentId').value = paymentId;
            document.getElementById('refundTenantName').textContent = tenantName;
            document.getElementById('refundAmount').textContent = amount.toFixed(2).replace('.', ',');
            document.getElementById('refundModal').classList.remove('hidden');
        }
        
        function closeRefundModal() {
            document.getElementById('refundModal').classList.add('hidden');
            document.getElementById('refundForm').reset();
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('refundModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRefundModal();
            }
        });
    </script>

<?php include 'includes/footer.php'; ?>
