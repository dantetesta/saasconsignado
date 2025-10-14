<?php
/**
 * Lista de Consignações
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0 SaaS
 */

// Obter tenant_id
$tenant_id = getTenantId();

// Buscar consignações
$status_filter = $_GET['status'] ?? 'pendente'; // Padrão: pendente
$tipo_filter = $_GET['tipo'] ?? '';
$search = $_GET['search'] ?? '';
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

// Filtro de pesquisa
if (!empty($search)) {
    $whereClause .= " AND (e.nome LIKE ? OR e.telefone LIKE ? OR c.id LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
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
    <div class="flex items-center gap-3">
        <!-- Controles de Visualização -->
        <div class="flex items-center gap-2 bg-white rounded-lg border border-gray-200 p-1">
            <button 
                id="gridViewBtn" 
                onclick="setViewMode('grid')" 
                class="view-btn flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-all"
                title="Visualização em Cards"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                <span class="hidden sm:inline">Cards</span>
            </button>
            <button 
                id="listViewBtn" 
                onclick="setViewMode('list')" 
                class="view-btn flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-all"
                title="Visualização em Lista"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                <span class="hidden sm:inline">Lista</span>
            </button>
        </div>
        
        <a href="?action=new" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-emerald-700 transition shadow-md">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nova Consignação
        </a>
    </div>
</div>

<!-- Filtros e Pesquisa -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
        <!-- Filtros -->
        <div class="flex flex-wrap items-center gap-6">
            <!-- Filtros de Status -->
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-gray-500 uppercase mb-2">Status</span>
                <div class="flex flex-wrap gap-2">
                    <?php
                    // Calcular estatísticas para os status (considerando apenas filtro de tipo, não de status)
                    $status_query = "SELECT status, COUNT(*) as total FROM consignacoes WHERE tenant_id = ?";
                    $status_params = [$tenant_id];
                    
                    // Aplicar apenas filtro de tipo (se houver)
                    if (!empty($tipo_filter)) {
                        $status_query .= " AND tipo = ?";
                        $status_params[] = $tipo_filter;
                    }
                    
                    // Aplicar filtro de pesquisa (se houver)
                    if (!empty($search)) {
                        $status_query .= " AND (estabelecimento_nome LIKE ? OR estabelecimento_telefone LIKE ?)";
                        $status_params[] = "%{$search}%";
                        $status_params[] = "%{$search}%";
                    }
                    
                    $status_query .= " GROUP BY status";
                    
                    $stmt_status = $db->prepare($status_query);
                    $stmt_status->execute($status_params);
                    $status_result = $stmt_status->fetchAll();
                    
                    $status_counts = [
                        'todas' => 0,
                        'pendente' => 0,
                        'parcial' => 0,
                        'finalizada' => 0,
                        'cancelada' => 0
                    ];
                    
                    foreach ($status_result as $status_row) {
                        $status_counts[$status_row['status']] = (int)$status_row['total'];
                        $status_counts['todas'] += (int)$status_row['total'];
                    }
                    ?>
                    
                    <div class="flex flex-col items-center">
                        <a href="?status=todas<?php echo !empty($tipo_filter) ? '&tipo='.$tipo_filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1.5 <?php echo $status_filter === 'todas' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                            Todas
                        </a>
                        <span class="text-xs text-gray-500 mt-1"><?php echo $status_counts['todas']; ?></span>
                    </div>
                    
                    <div class="flex flex-col items-center">
                        <a href="?status=pendente<?php echo !empty($tipo_filter) ? '&tipo='.$tipo_filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1.5 <?php echo $status_filter === 'pendente' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                            Pendentes
                        </a>
                        <span class="text-xs text-gray-500 mt-1"><?php echo $status_counts['pendente']; ?></span>
                    </div>
                    
                    <div class="flex flex-col items-center">
                        <a href="?status=parcial<?php echo !empty($tipo_filter) ? '&tipo='.$tipo_filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1.5 <?php echo $status_filter === 'parcial' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                            Parciais
                        </a>
                        <span class="text-xs text-gray-500 mt-1"><?php echo $status_counts['parcial']; ?></span>
                    </div>
                    
                    <div class="flex flex-col items-center">
                        <a href="?status=finalizada<?php echo !empty($tipo_filter) ? '&tipo='.$tipo_filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1.5 <?php echo $status_filter === 'finalizada' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                            Finalizadas
                        </a>
                        <span class="text-xs text-gray-500 mt-1"><?php echo $status_counts['finalizada']; ?></span>
                    </div>
                    
                    <div class="flex flex-col items-center">
                        <a href="?status=cancelada<?php echo !empty($tipo_filter) ? '&tipo='.$tipo_filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1.5 <?php echo $status_filter === 'cancelada' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                            Canceladas
                        </a>
                        <span class="text-xs text-gray-500 mt-1"><?php echo $status_counts['cancelada']; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Separador -->
            <div class="hidden lg:block h-16 w-px bg-gray-300 mx-2"></div>
            
            <!-- Filtros de Tipo -->
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-gray-500 uppercase mb-2">Tipo</span>
                <div class="flex flex-wrap gap-2">
                    <?php
                    // Calcular estatísticas para os tipos (considerando apenas filtro de status, não de tipo)
                    $tipo_query = "SELECT tipo, COUNT(*) as total FROM consignacoes WHERE tenant_id = ?";
                    $tipo_params = [$tenant_id];
                    
                    // Aplicar apenas filtro de status (se houver)
                    if (!empty($status_filter) && $status_filter !== 'todas') {
                        $tipo_query .= " AND status = ?";
                        $tipo_params[] = $status_filter;
                    }
                    
                    // Aplicar filtro de pesquisa (se houver)
                    if (!empty($search)) {
                        $tipo_query .= " AND (estabelecimento_nome LIKE ? OR estabelecimento_telefone LIKE ?)";
                        $tipo_params[] = "%{$search}%";
                        $tipo_params[] = "%{$search}%";
                    }
                    
                    $tipo_query .= " GROUP BY tipo";
                    
                    $stmt_tipos = $db->prepare($tipo_query);
                    $stmt_tipos->execute($tipo_params);
                    $tipos_result = $stmt_tipos->fetchAll();
                    
                    $tipo_counts = [
                        'todos' => 0,
                        'pontual' => 0,
                        'continua' => 0
                    ];
                    
                    foreach ($tipos_result as $tipo_row) {
                        $tipo_counts[$tipo_row['tipo']] = (int)$tipo_row['total'];
                        $tipo_counts['todos'] += (int)$tipo_row['total'];
                    }
                    ?>
                    
                    <div class="flex flex-col items-center">
                        <a href="?<?php echo !empty($status_filter) ? 'status='.$status_filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1.5 <?php echo empty($tipo_filter) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                            Todos
                        </a>
                        <span class="text-xs text-gray-500 mt-1"><?php echo $tipo_counts['todos']; ?></span>
                    </div>
                    
                    <div class="flex flex-col items-center">
                        <a href="?tipo=pontual<?php echo !empty($status_filter) ? '&status='.$status_filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1.5 <?php echo $tipo_filter === 'pontual' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                            📦 Pontuais
                        </a>
                        <span class="text-xs text-gray-500 mt-1"><?php echo $tipo_counts['pontual']; ?></span>
                    </div>
                    
                    <div class="flex flex-col items-center">
                        <a href="?tipo=continua<?php echo !empty($status_filter) ? '&status='.$status_filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1.5 <?php echo $tipo_filter === 'continua' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium transition text-sm">
                            🔄 Contínuas
                        </a>
                        <span class="text-xs text-gray-500 mt-1"><?php echo $tipo_counts['continua']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Campo de Pesquisa -->
        <div class="w-full lg:w-auto lg:min-w-80">
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-gray-500 uppercase mb-2">Pesquise suas consignações</span>
                <form method="GET" action="" class="flex gap-2 w-full">
                    <!-- Preservar filtros atuais -->
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo_filter); ?>">
                    
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($search); ?>" 
                            placeholder="Estabelecimento, telefone..."
                            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        class="px-3 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm flex items-center"
                        title="Buscar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                    
                    <?php if (!empty($search)): ?>
                        <a 
                            href="?status=<?php echo urlencode($status_filter); ?>&tipo=<?php echo urlencode($tipo_filter); ?>" 
                            class="px-3 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition flex items-center"
                            title="Limpar pesquisa">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Indicador de Pesquisa Ativa -->
    <?php if (!empty($search)): ?>
        <div class="text-sm text-gray-600 border-t border-gray-200 pt-3">
            <span class="font-medium">Pesquisando por:</span> "<?php echo htmlspecialchars($search); ?>"
        </div>
    <?php endif; ?>
</div>

<!-- Contador de Resultados Discreto -->
<div class="mb-4 text-sm text-gray-600">
    <?php 
    $total_consignacoes = count($consignacoes);
    if ($total_consignacoes === 0) {
        echo "Nenhuma consignação encontrada";
    } elseif ($total_consignacoes === 1) {
        echo "1 consignação encontrada";
    } else {
        echo "{$total_consignacoes} consignações encontradas";
    }
    
    // Adicionar filtros ativos se houver
    $filtros_ativos = [];
    
    if (!empty($status_filter) && $status_filter !== 'todas') {
        $status_names = [
            'pendente' => 'Pendentes',
            'parcial' => 'Parciais', 
            'finalizada' => 'Finalizadas',
            'cancelada' => 'Canceladas'
        ];
        $filtros_ativos[] = $status_names[$status_filter] ?? $status_filter;
    }
    
    if (!empty($tipo_filter)) {
        $tipo_names = [
            'pontual' => 'Pontuais',
            'continua' => 'Contínuas'
        ];
        $filtros_ativos[] = $tipo_names[$tipo_filter] ?? $tipo_filter;
    }
    
    if (!empty($search)) {
        $filtros_ativos[] = "Pesquisa: \"{$search}\"";
    }
    
    if (!empty($filtros_ativos)) {
        echo " • " . implode(" • ", $filtros_ativos);
    }
    ?>
</div>

<!-- Conteúdo das Consignações -->
<div id="consignacoes-container">
    <?php if (empty($consignacoes)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-500 mb-4">Nenhuma consignação encontrada</p>
            <a href="?action=new" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Criar Primeira Consignação
            </a>
        </div>
    <?php else: ?>
    <!-- Visualização Grid (Cards) -->
    <div id="grid-view" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($consignacoes as $cons): 
            $saldo_pendente = $cons['valor_total_vendido'] - $cons['valor_pago'];
            $ainda_consignado = $cons['total_consignado'] - $cons['total_vendido'] - $cons['total_devolvido'];
        ?>
            <!-- Card Individual -->
            <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-100 hover:border-blue-300 hover:shadow-xl transition-all duration-300 overflow-hidden group">
                
                <!-- Header do Card -->
                <div class="bg-gradient-to-r from-blue-600 to-emerald-600 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-white font-bold text-lg truncate mb-1">
                                <?php echo sanitize($cons['estabelecimento']); ?>
                            </h3>
                            <div class="flex items-center gap-2 text-xs text-blue-100">
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
                               class="w-full px-4 py-2.5 bg-gradient-to-r from-blue-600 to-emerald-600 text-white text-sm font-semibold rounded-lg hover:from-blue-700 hover:to-emerald-700 transition-all shadow-md hover:shadow-lg text-center">
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
    
    <!-- Visualização Lista (Tabela) -->
    <div id="list-view" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" style="display: none;">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-auto min-w-32">Estabelecimento</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-20 min-w-20">Data</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-auto min-w-28">Tipo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Produtos</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider w-24 min-w-24">Saldo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($consignacoes as $cons): 
                        $saldo_pendente = $cons['valor_total_vendido'] - $cons['valor_pago'];
                        $ainda_consignado = $cons['total_consignado'] - $cons['total_vendido'] - $cons['total_devolvido'];
                    ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <!-- Estabelecimento -->
                            <td class="px-4 py-4 w-auto min-w-32">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-600 to-emerald-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">
                                            <?php echo strtoupper(substr($cons['estabelecimento'], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <div class="ml-3 min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-900 truncate">
                                            <?php echo sanitize($cons['estabelecimento']); ?>
                                        </div>
                                        <?php if (!empty($cons['telefone'])): ?>
                                            <div class="text-sm text-gray-500 truncate">
                                                <?php echo formatPhone($cons['telefone']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Data -->
                            <td class="px-4 py-4 text-sm text-gray-900 w-20 min-w-20 whitespace-nowrap">
                                <?php echo formatDate($cons['data_consignacao']); ?>
                            </td>
                            
                            <!-- Tipo -->
                            <td class="px-4 py-4 text-center w-auto min-w-28">
                                <?php if ($cons['tipo'] === 'continua'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 whitespace-nowrap">
                                        <span>🔄</span>
                                        <span>Contínua</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 whitespace-nowrap">
                                        <span>📦</span>
                                        <span>Pontual</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Status -->
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusBadgeClass($cons['status']); ?>">
                                    <?php echo translateStatus($cons['status']); ?>
                                </span>
                            </td>
                            
                            <!-- Produtos (Resumo) -->
                            <td class="px-4 py-4 text-center">
                                <div class="text-sm text-gray-900">
                                    <div class="flex items-center justify-center gap-4 text-xs">
                                        <span class="text-gray-600">📦 <?php echo $cons['total_consignado']; ?></span>
                                        <span class="text-green-600">✅ <?php echo $cons['total_vendido']; ?></span>
                                        <span class="text-blue-600">↩️ <?php echo $cons['total_devolvido']; ?></span>
                                        <span class="text-yellow-600">⏳ <?php echo $ainda_consignado; ?></span>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Saldo -->
                            <td class="px-4 py-4 text-right w-24 min-w-24">
                                <div class="text-sm font-bold whitespace-nowrap <?php echo $saldo_pendente > 0 ? 'text-orange-600' : 'text-green-600'; ?>">
                                    <?php echo formatMoney($saldo_pendente); ?>
                                </div>
                            </td>
                            
                            <!-- Ações -->
                            <td class="px-4 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="?action=view&id=<?php echo $cons['id']; ?>" 
                                       class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded hover:bg-gray-200 transition"
                                       title="Ver Detalhes">
                                        👁️
                                    </a>
                                    
                                    <?php if ($cons['status'] !== 'finalizada' && $cons['status'] !== 'cancelada'): ?>
                                        <a href="?action=update&id=<?php echo $cons['id']; ?>" 
                                           class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded hover:bg-blue-200 transition"
                                           title="Atualizar Vendas">
                                            ✏️
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($saldo_pendente > 0 && $cons['status'] !== 'cancelada'): ?>
                                        <a href="?action=view&id=<?php echo $cons['id']; ?>#pagamento" 
                                           class="inline-flex items-center px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded hover:bg-green-200 transition"
                                           title="Registrar Pagamento">
                                            💰
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button 
                                        onclick="confirmarDelete(<?php echo $cons['id']; ?>, '<?php echo addslashes($cons['estabelecimento']); ?>')" 
                                        class="inline-flex items-center px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition"
                                        title="Deletar">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Formulário oculto para deletar -->
<form id="deleteForm" method="POST" action="<?php echo url('/consignacoes.php'); ?>" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
    <input type="hidden" name="tipo_filter" value="<?php echo htmlspecialchars($tipo_filter); ?>">
</form>

<style>
/* Estilos para os botões de visualização */
.view-btn.active {
    background-color: #3b82f6;
    color: white;
}
.view-btn:not(.active) {
    background-color: transparent;
    color: #6b7280;
}
.view-btn:not(.active):hover {
    background-color: #f3f4f6;
    color: #374151;
}

/* Visualização em lista (oculta por padrão) */
#list-view {
    display: none;
}
</style>

<script>
// Sistema de Visualização Grid/Lista com localStorage
let currentViewMode = localStorage.getItem('consignacoes_view_mode') || 'grid';

// Inicializar visualização ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    setViewMode(currentViewMode);
});

function setViewMode(mode) {
    currentViewMode = mode;
    localStorage.setItem('consignacoes_view_mode', mode);
    
    const gridView = document.getElementById('grid-view');
    const listView = document.getElementById('list-view');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');
    
    // Atualizar botões
    gridBtn.classList.toggle('active', mode === 'grid');
    listBtn.classList.toggle('active', mode === 'list');
    
    // Alternar visualizações
    if (mode === 'grid') {
        if (gridView) gridView.style.display = 'grid';
        if (listView) listView.style.display = 'none';
    } else {
        if (gridView) gridView.style.display = 'none';
        if (listView) listView.style.display = 'block';
    }
}


function confirmarDelete(id, nome) {
    if (confirm('⚠️ Tem certeza que deseja deletar a consignação de "' + nome + '"?\n\nEsta ação não pode ser desfeita!')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
