<?php
/**
 * Listagem de Consignações
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

// Buscar consignações
$status_filter = $_GET['status'] ?? 'pendente'; // Padrão: pendente
$tipo_filter = $_GET['tipo'] ?? '';
$whereClause = "WHERE 1=1";
$params = [];

// Se status_filter for 'todas', não filtrar por status
if (!empty($status_filter) && $status_filter !== 'todas') {
    $whereClause .= " AND c.status = ?";
    $params[] = $status_filter;
}

if (!empty($tipo_filter)) {
    $whereClause .= " AND c.tipo = ?";
    $params[] = $tipo_filter;
}

$stmt = $db->prepare("
    SELECT 
        c.id,
        c.tipo,
        c.data_consignacao,
        c.data_vencimento,
        c.status,
        e.nome AS estabelecimento,
        e.telefone,
        CASE 
            WHEN c.tipo = 'continua' THEN (
                SELECT COALESCE(SUM(CASE WHEN tipo = 'entrega' THEN quantidade ELSE 0 END), 0)
                FROM movimentacoes_consignacao 
                WHERE consignacao_id = c.id
            )
            ELSE COALESCE(SUM(ci.quantidade_consignada), 0)
        END AS total_consignado,
        CASE 
            WHEN c.tipo = 'continua' THEN (
                SELECT COALESCE(SUM(CASE WHEN tipo = 'venda' THEN quantidade ELSE 0 END), 0)
                FROM movimentacoes_consignacao 
                WHERE consignacao_id = c.id
            )
            ELSE COALESCE(SUM(ci.quantidade_vendida), 0)
        END AS total_vendido,
        CASE 
            WHEN c.tipo = 'continua' THEN (
                SELECT COALESCE(SUM(CASE WHEN tipo = 'devolucao' THEN quantidade ELSE 0 END), 0)
                FROM movimentacoes_consignacao 
                WHERE consignacao_id = c.id
            )
            ELSE COALESCE(SUM(ci.quantidade_devolvida), 0)
        END AS total_devolvido,
        CASE 
            WHEN c.tipo = 'continua' THEN (
                SELECT COALESCE(SUM(m.quantidade * m.preco_unitario), 0)
                FROM movimentacoes_consignacao m
                WHERE m.consignacao_id = c.id AND m.tipo = 'venda'
            )
            ELSE COALESCE(SUM(ci.quantidade_vendida * ci.preco_unitario), 0)
        END AS valor_total_vendido,
        COALESCE((SELECT SUM(valor_pago) FROM pagamentos WHERE consignacao_id = c.id AND tenant_id = c.tenant_id), 0) AS valor_pago
    FROM consignacoes c
    INNER JOIN estabelecimentos e ON c.estabelecimento_id = e.id
    LEFT JOIN consignacao_itens ci ON c.id = ci.consignacao_id AND c.tipo = 'pontual'
    $whereClause
    GROUP BY c.id, c.tipo, c.data_consignacao, c.data_vencimento, c.status, e.nome, e.telefone
    ORDER BY c.data_consignacao DESC
");
$stmt->execute($params);
$consignacoes = $stmt->fetchAll();
?>

<!-- Page Header -->
<div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Consignações</h1>
        <p class="text-gray-600 mt-1">Gerencie todas as consignações</p>
    </div>
    <a href="?action=new" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition shadow-md">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Nova Consignação
    </a>
</div>

<!-- Filtros -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <div class="flex flex-wrap items-center gap-4">
        <!-- Filtros de Status -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-gray-500 uppercase mr-2">Status:</span>
            <a href="?status=todas<?php echo !empty($tipo_filter) ? '&tipo='.$tipo_filter : ''; ?>" class="px-3 py-1.5 <?php echo $status_filter === 'todas' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                Todas
            </a>
            <a href="?status=pendente<?php echo !empty($tipo_filter) ? '&tipo='.$tipo_filter : ''; ?>" class="px-3 py-1.5 <?php echo $status_filter === 'pendente' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                Pendentes
            </a>
            <a href="?status=parcial<?php echo !empty($tipo_filter) ? '&tipo='.$tipo_filter : ''; ?>" class="px-3 py-1.5 <?php echo $status_filter === 'parcial' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                Parciais
            </a>
            <a href="?status=finalizada<?php echo !empty($tipo_filter) ? '&tipo='.$tipo_filter : ''; ?>" class="px-3 py-1.5 <?php echo $status_filter === 'finalizada' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                Finalizadas
            </a>
            <a href="?status=cancelada<?php echo !empty($tipo_filter) ? '&tipo='.$tipo_filter : ''; ?>" class="px-3 py-1.5 <?php echo $status_filter === 'cancelada' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                Canceladas
            </a>
        </div>
        
        <!-- Separador -->
        <div class="hidden sm:block h-8 w-px bg-gray-300"></div>
        
        <!-- Filtros de Tipo -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-gray-500 uppercase mr-2">Tipo:</span>
            <a href="?<?php echo !empty($status_filter) ? 'status='.$status_filter : ''; ?>" class="px-3 py-1.5 <?php echo empty($tipo_filter) ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                Todos
            </a>
            <a href="?tipo=pontual<?php echo !empty($status_filter) ? '&status='.$status_filter : ''; ?>" class="px-3 py-1.5 <?php echo $tipo_filter === 'pontual' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                📦 Pontuais
            </a>
            <a href="?tipo=continua<?php echo !empty($status_filter) ? '&status='.$status_filter : ''; ?>" class="px-3 py-1.5 <?php echo $tipo_filter === 'continua' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                🔄 Contínuas
            </a>
        </div>
    </div>
</div>

<!-- Lista de Consignações -->
<?php if (empty($consignacoes)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <p class="text-gray-500 mb-4">Nenhuma consignação encontrada</p>
        <a href="?action=new" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Criar Primeira Consignação
        </a>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($consignacoes as $cons): 
            $saldo_pendente = $cons['valor_total_vendido'] - $cons['valor_pago'];
        ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Informações Principais -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2 flex-wrap">
                                <h3 class="text-lg font-semibold text-gray-900"><?php echo sanitize($cons['estabelecimento']); ?></h3>
                                <span class="inline-block px-3 py-1 text-xs font-medium rounded-full <?php echo getStatusBadgeClass($cons['status']); ?>">
                                    <?php echo translateStatus($cons['status']); ?>
                                </span>
                                <?php if ($cons['tipo'] === 'continua'): ?>
                                    <span class="inline-block px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 border border-green-300">
                                        🔄 Contínua
                                    </span>
                                <?php else: ?>
                                    <span class="inline-block px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 border border-blue-300">
                                        📦 Pontual
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                                <span>
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <?php echo formatDate($cons['data_consignacao']); ?>
                                </span>
                                <?php if (!empty($cons['telefone'])): ?>
                                    <span>
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                        <?php echo formatPhone($cons['telefone']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Estatísticas -->
                        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 lg:gap-4">
                            <div class="text-center">
                                <p class="text-xs text-gray-500 mb-1">Consignado</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo $cons['total_consignado']; ?></p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 mb-1">Vendido</p>
                                <p class="text-lg font-bold text-green-600"><?php echo $cons['total_vendido']; ?></p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 mb-1">Devolvido</p>
                                <p class="text-lg font-bold text-blue-600"><?php echo $cons['total_devolvido']; ?></p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 mb-1">Ainda Consignado</p>
                                <p class="text-lg font-bold <?php 
                                    $ainda_consignado = $cons['total_consignado'] - $cons['total_vendido'] - $cons['total_devolvido'];
                                    echo $ainda_consignado > 0 ? 'text-yellow-600' : 'text-gray-500'; 
                                ?>">
                                    <?php echo $ainda_consignado; ?>
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 mb-1">Saldo R$</p>
                                <p class="text-lg font-bold <?php echo $saldo_pendente > 0 ? 'text-orange-600' : 'text-green-600'; ?>">
                                    <?php echo formatMoney($saldo_pendente); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações -->
                <div class="bg-gray-50 px-6 py-3 flex flex-wrap justify-between items-center gap-3">
                    <div class="flex flex-wrap gap-2">
                        <a href="?action=view&id=<?php echo $cons['id']; ?>" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-100 transition">
                            Ver Detalhes
                        </a>
                        <?php if ($cons['status'] !== 'finalizada' && $cons['status'] !== 'cancelada'): ?>
                            <a href="?action=update&id=<?php echo $cons['id']; ?>" class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition">
                                Atualizar Vendas
                            </a>
                        <?php endif; ?>
                        <?php if ($saldo_pendente > 0 && $cons['status'] !== 'cancelada'): ?>
                            <a href="?action=view&id=<?php echo $cons['id']; ?>#pagamento" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                                Registrar Pagamento
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Botão Deletar -->
                        <button 
                            onclick="confirmarDelete(<?php echo $cons['id']; ?>, '<?php echo addslashes($cons['estabelecimento']); ?>')" 
                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            title="Deletar consignação"
                        >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Formulário oculto para deletar -->
<form id="deleteForm" method="POST" action="<?php echo url('/consignacoes.php'); ?>" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
    <input type="hidden" name="tipo_filter" value="<?php echo htmlspecialchars($tipo_filter); ?>">
</form>

<script>
function confirmarDelete(id, nome) {
    if (confirm('⚠️ Tem certeza que deseja deletar a consignação de "' + nome + '"?\n\nEsta ação não pode ser desfeita!')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
