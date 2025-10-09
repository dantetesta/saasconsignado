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
    <!-- Grid de Cards Responsivo -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($consignacoes as $cons): 
            $saldo_pendente = $cons['valor_total_vendido'] - $cons['valor_pago'];
            $ainda_consignado = $cons['total_consignado'] - $cons['total_vendido'] - $cons['total_devolvido'];
        ?>
            <!-- Card Individual -->
            <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-100 hover:border-purple-300 hover:shadow-xl transition-all duration-300 overflow-hidden group">
                
                <!-- Header do Card -->
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-white font-bold text-lg truncate mb-1">
                                <?php echo sanitize($cons['estabelecimento']); ?>
                            </h3>
                            <div class="flex items-center gap-2 text-xs text-purple-100">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span><?php echo formatDate($cons['data_consignacao']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Botão Deletar -->
                        <button 
                            onclick="confirmarDelete(<?php echo $cons['id']; ?>, '<?php echo addslashes($cons['estabelecimento']); ?>')" 
                            class="p-1.5 text-white/70 hover:text-white hover:bg-white/20 rounded-lg transition-colors flex-shrink-0"
                            title="Deletar consignação"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Badges de Status e Tipo -->
                    <div class="flex flex-wrap gap-2 mt-3">
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadgeClass($cons['status']); ?>">
                            <?php echo translateStatus($cons['status']); ?>
                        </span>
                        <?php if ($cons['tipo'] === 'continua'): ?>
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-white/90 text-green-700">
                                🔄 Contínua
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-white/90 text-blue-700">
                                📦 Pontual
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Corpo do Card - Estatísticas -->
                <div class="p-5">
                    <!-- Grid de Métricas -->
                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <!-- Consignado -->
                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-200">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                                <p class="text-xs font-medium text-gray-600">Consignado</p>
                            </div>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $cons['total_consignado']; ?></p>
                        </div>
                        
                        <!-- Vendido -->
                        <div class="bg-green-50 rounded-xl p-3 border border-green-200">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                <p class="text-xs font-medium text-green-700">Vendido</p>
                            </div>
                            <p class="text-2xl font-bold text-green-600"><?php echo $cons['total_vendido']; ?></p>
                        </div>
                        
                        <!-- Devolvido -->
                        <div class="bg-blue-50 rounded-xl p-3 border border-blue-200">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                <p class="text-xs font-medium text-blue-700">Devolvido</p>
                            </div>
                            <p class="text-2xl font-bold text-blue-600"><?php echo $cons['total_devolvido']; ?></p>
                        </div>
                        
                        <!-- Ainda Consignado -->
                        <div class="bg-yellow-50 rounded-xl p-3 border border-yellow-200">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-2 h-2 rounded-full <?php echo $ainda_consignado > 0 ? 'bg-yellow-500' : 'bg-gray-400'; ?>"></div>
                                <p class="text-xs font-medium <?php echo $ainda_consignado > 0 ? 'text-yellow-700' : 'text-gray-600'; ?>">Pendente</p>
                            </div>
                            <p class="text-2xl font-bold <?php echo $ainda_consignado > 0 ? 'text-yellow-600' : 'text-gray-500'; ?>">
                                <?php echo $ainda_consignado; ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Saldo Financeiro Destacado -->
                    <div class="bg-gradient-to-r <?php echo $saldo_pendente > 0 ? 'from-orange-50 to-red-50 border-orange-200' : 'from-green-50 to-emerald-50 border-green-200'; ?> rounded-xl p-4 border-2 mb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold <?php echo $saldo_pendente > 0 ? 'text-orange-700' : 'text-green-700'; ?> uppercase tracking-wide mb-1">
                                    Saldo R$
                                </p>
                                <p class="text-3xl font-black <?php echo $saldo_pendente > 0 ? 'text-orange-600' : 'text-green-600'; ?>">
                                    <?php echo formatMoney($saldo_pendente); ?>
                                </p>
                            </div>
                            <div class="<?php echo $saldo_pendente > 0 ? 'text-orange-400' : 'text-green-400'; ?>">
                                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Telefone (se houver) -->
                    <?php if (!empty($cons['telefone'])): ?>
                        <div class="flex items-center gap-2 text-sm text-gray-600 mb-4 px-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <span class="font-medium"><?php echo formatPhone($cons['telefone']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Botões de Ação -->
                    <div class="flex flex-col gap-2">
                        <a href="?action=view&id=<?php echo $cons['id']; ?>" 
                           class="w-full px-4 py-2.5 bg-white border-2 border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all text-center">
                            👁️ Ver Detalhes
                        </a>
                        
                        <?php if ($cons['status'] !== 'finalizada' && $cons['status'] !== 'cancelada'): ?>
                            <a href="?action=update&id=<?php echo $cons['id']; ?>" 
                               class="w-full px-4 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all shadow-md hover:shadow-lg text-center">
                                ✏️ Atualizar Vendas
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($saldo_pendente > 0 && $cons['status'] !== 'cancelada'): ?>
                            <a href="?action=view&id=<?php echo $cons['id']; ?>#pagamento" 
                               class="w-full px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm font-semibold rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all shadow-md hover:shadow-lg text-center">
                                💰 Registrar Pagamento
                            </a>
                        <?php endif; ?>
                    </div>
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
