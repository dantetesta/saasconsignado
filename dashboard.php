<?php
/**
 * Dashboard Principal
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0 SaaS
 */

require_once 'config/config.php';
requireLogin();

$pageTitle = 'Dashboard';

// Buscar estatísticas (filtradas por tenant)
$db = Database::getInstance()->getConnection();
$tenant_id = getTenantId();

// Total de consignações ativas (do tenant)
$stmt = $db->prepare("SELECT COUNT(*) as total FROM consignacoes WHERE status IN ('pendente', 'parcial') AND tenant_id = ?");
$stmt->execute([$tenant_id]);
$consignacoesAtivas = $stmt->fetch()['total'];

// Consignações por tipo (do tenant)
$stmt = $db->prepare("
    SELECT 
        tipo,
        COUNT(*) as total 
    FROM consignacoes 
    WHERE tenant_id = ?
    GROUP BY tipo
");
$stmt->execute([$tenant_id]);
$consignacoesPorTipo = [];
while ($row = $stmt->fetch()) {
    $consignacoesPorTipo[$row['tipo']] = $row['total'];
}

// Definir variáveis para os tipos específicos
$consignacoesPontuais = $consignacoesPorTipo['pontual'] ?? 0;
$consignacoesContinuas = $consignacoesPorTipo['continua'] ?? 0;

// Total de consignações (do tenant)
$stmt = $db->prepare("SELECT COUNT(*) as total FROM consignacoes WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$totalConsignacoes = $stmt->fetch()['total'];

// Total de produtos (do tenant)
$stmt = $db->prepare("SELECT COUNT(*) as total FROM produtos WHERE ativo = 1 AND tenant_id = ?");
$stmt->execute([$tenant_id]);
$totalProdutos = $stmt->fetch()['total'];

// Total de estabelecimentos (do tenant)
$stmt = $db->prepare("SELECT COUNT(*) as total FROM estabelecimentos WHERE ativo = 1 AND tenant_id = ?");
$stmt->execute([$tenant_id]);
$totalEstabelecimentos = $stmt->fetch()['total'];

// Valor total a receber (usando VIEW consolidada)
$stmt = $db->prepare("
    SELECT 
        COALESCE(SUM(valor_total), 0) - 
        COALESCE((SELECT SUM(valor_pago) FROM pagamentos p WHERE p.consignacao_id IN (SELECT id FROM consignacoes WHERE status IN ('pendente', 'parcial') AND tenant_id = ?) AND p.tenant_id = ?), 0) as total
    FROM vw_vendas_consolidadas
    WHERE tenant_id = ? AND consignacao_id IN (SELECT id FROM consignacoes WHERE status IN ('pendente', 'parcial') AND tenant_id = ?)
");
$stmt->execute([$tenant_id, $tenant_id, $tenant_id, $tenant_id]);
$valorAReceber = $stmt->fetch()['total'];

// Últimas consignações (com tipo, do tenant)
$stmt = $db->prepare("
    SELECT 
        c.id,
        c.tipo,
        c.data_consignacao,
        c.status,
        e.nome as estabelecimento,
        COALESCE((
            SELECT SUM(valor_total) 
            FROM vw_vendas_consolidadas v 
            WHERE v.consignacao_id = c.id AND v.tenant_id = ?
        ), 0) as valor_total
    FROM consignacoes c
    INNER JOIN estabelecimentos e ON c.estabelecimento_id = e.id
    WHERE c.tenant_id = ?
    ORDER BY c.data_consignacao DESC
    LIMIT 5
");
$stmt->execute([$tenant_id, $tenant_id]);
$ultimasConsignacoes = $stmt->fetchAll();

// Produtos com baixo estoque (usando estoque_minimo, do tenant)
$stmt = $db->prepare("
    SELECT 
        p.id,
        p.nome,
        p.estoque_total,
        p.estoque_minimo,
        COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0) as quantidade_consignada,
        (p.estoque_total - COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0)) as estoque_disponivel
    FROM produtos p
    LEFT JOIN consignacao_itens ci ON p.id = ci.produto_id AND ci.tenant_id = ?
    LEFT JOIN consignacoes c ON ci.consignacao_id = c.id AND c.status IN ('pendente', 'parcial') AND c.tenant_id = ?
    WHERE p.ativo = 1 AND p.tenant_id = ?
    GROUP BY p.id, p.nome, p.estoque_total, p.estoque_minimo
    HAVING estoque_disponivel < p.estoque_minimo
    ORDER BY estoque_disponivel ASC
    LIMIT 5
");
$stmt->execute([$tenant_id, $tenant_id, $tenant_id]);
$produtosBaixoEstoque = $stmt->fetchAll();

// Dados para gráfico: Consignações por mês (últimos 12 meses)
$stmt = $db->prepare("
    SELECT 
        DATE_FORMAT(data_consignacao, '%Y-%m') as mes,
        status,
        COUNT(*) as total
    FROM consignacoes 
    WHERE tenant_id = ? 
    AND data_consignacao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(data_consignacao, '%Y-%m'), status
    ORDER BY mes ASC
");
$stmt->execute([$tenant_id]);
$dadosPorMes = $stmt->fetchAll();

// Organizar dados por mês para o gráfico
$mesesLabels = [];
$dadosGraficoMes = [
    'pendente' => [],
    'parcial' => [],
    'finalizada' => [],
    'cancelada' => []
];

// Gerar últimos 12 meses
for ($i = 11; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $mesLabel = date('M/Y', strtotime("-$i months"));
    $mesesLabels[] = $mesLabel;
    
    // Inicializar com 0
    foreach ($dadosGraficoMes as $status => &$dados) {
        $dados[] = 0;
    }
    
    // Preencher com dados reais
    foreach ($dadosPorMes as $dado) {
        if ($dado['mes'] === $mes) {
            $statusIndex = array_search($mesLabel, $mesesLabels);
            if (isset($dadosGraficoMes[$dado['status']])) {
                $dadosGraficoMes[$dado['status']][$statusIndex] = (int)$dado['total'];
            }
        }
    }
}

// Dados para gráfico: Distribuição por status
$stmt = $db->prepare("
    SELECT 
        status,
        COUNT(*) as total
    FROM consignacoes 
    WHERE tenant_id = ?
    GROUP BY status
");
$stmt->execute([$tenant_id]);
$distribuicaoStatus = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-1">Visão geral do sistema de consignados</p>
    </div>
    
    <!-- Ações Rápidas Compactas -->
    <div class="flex flex-wrap gap-2">
        <a href="/consignacoes.php?action=new" class="inline-flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-blue-600 to-emerald-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-emerald-700 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span class="hidden sm:inline">Nova Consignação</span>
            <span class="sm:hidden">Consignação</span>
        </a>
        <a href="/produtos.php?action=new" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <span class="hidden sm:inline">Novo Produto</span>
            <span class="sm:hidden">Produto</span>
        </a>
        <a href="/estabelecimentos.php?action=new" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <span class="hidden sm:inline">Estabelecimento</span>
            <span class="sm:hidden">Local</span>
        </a>
        <a href="/relatorios.php" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="hidden sm:inline">Relatórios</span>
            <span class="sm:hidden">Dados</span>
        </a>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Consignações Ativas -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Consignações Ativas</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $consignacoesAtivas; ?></p>
                <div class="flex gap-2 mt-2">
                    <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded">📦 Pontual: <?php echo $consignacoesPontuais; ?></span>
                    <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded">🔄 Contínua: <?php echo $consignacoesContinuas; ?></span>
                </div>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
        </div>
        <a href="/consignacoes.php" class="text-sm text-blue-600 hover:text-blue-700 font-medium mt-4 inline-block">
            Ver todas →
        </a>
    </div>

    <!-- Valor a Receber -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Valor a Receber</p>
                <p class="text-3xl font-bold text-green-600 mt-2"><?php echo formatMoney($valorAReceber); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
        <a href="/relatorios.php" class="text-sm text-green-600 hover:text-green-700 font-medium mt-4 inline-block">
            Ver relatório →
        </a>
    </div>

    <!-- Total de Produtos -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Produtos Cadastrados</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalProdutos; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
        </div>
        <a href="/produtos.php" class="text-sm text-blue-600 hover:text-blue-700 font-medium mt-4 inline-block">
            Gerenciar →
        </a>
    </div>

    <!-- Total de Estabelecimentos -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Estabelecimentos</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalEstabelecimentos; ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
        </div>
        <a href="/estabelecimentos.php" class="text-sm text-orange-600 hover:text-orange-700 font-medium mt-4 inline-block">
            Gerenciar →
        </a>
    </div>
</div>

<!-- Grid de Conteúdo -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Últimas Consignações -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Últimas Consignações</h2>
        </div>
        <div class="p-6">
            <?php if (empty($ultimasConsignacoes)): ?>
                <div class="text-center py-8">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-gray-500">Nenhuma consignação cadastrada</p>
                    <a href="/consignacoes.php?action=new" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Nova Consignação
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($ultimasConsignacoes as $consignacao): ?>
                        <a href="/consignacoes.php?action=view&id=<?php echo $consignacao['id']; ?>" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="font-medium text-gray-900"><?php echo sanitize($consignacao['estabelecimento']); ?></p>
                                    <?php if ($consignacao['tipo'] === 'continua'): ?>
                                        <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded">🔄</span>
                                    <?php else: ?>
                                        <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded">📦</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-600"><?php echo formatDate($consignacao['data_consignacao']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900"><?php echo formatMoney($consignacao['valor_total']); ?></p>
                                <span class="inline-block px-2 py-1 text-xs font-medium rounded-full <?php echo getStatusBadgeClass($consignacao['status']); ?>">
                                    <?php echo translateStatus($consignacao['status']); ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="/consignacoes.php" class="block text-center mt-4 text-sm text-blue-600 hover:text-blue-700 font-medium">
                    Ver todas as consignações →
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Produtos com Baixo Estoque -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Produtos com Baixo Estoque</h2>
        </div>
        <div class="p-6">
            <?php if (empty($produtosBaixoEstoque)): ?>
                <div class="text-center py-8">
                    <svg class="w-16 h-16 text-green-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-500">Todos os produtos com estoque adequado!</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($produtosBaixoEstoque as $produto): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900"><?php echo sanitize($produto['nome']); ?></p>
                                <p class="text-sm text-gray-600">
                                    Consignado: <?php echo $produto['quantidade_consignada']; ?> un.
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold <?php echo $produto['estoque_disponivel'] < 10 ? 'text-red-600' : 'text-yellow-600'; ?>">
                                    <?php echo $produto['estoque_disponivel']; ?>
                                </p>
                                <p class="text-xs text-gray-500">disponível</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="/produtos.php" class="block text-center mt-4 text-sm text-blue-600 hover:text-blue-700 font-medium">
                    Ver todos os produtos →
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Gráficos Analíticos -->
<div class="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-6">
    <!-- Gráfico de Consignações por Mês -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Consignações por Mês</h2>
                <p class="text-sm text-gray-600">Últimos 12 meses por status</p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                    <span>Pendente</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 bg-blue-500 rounded"></div>
                    <span>Parcial</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 bg-green-500 rounded"></div>
                    <span>Finalizada</span>
                </div>
            </div>
        </div>
        <div class="relative h-80">
            <canvas id="consignacoesPorMesChart"></canvas>
        </div>
    </div>

    <!-- Gráfico de Distribuição por Status -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Status das Consignações</h2>
                <p class="text-sm text-gray-600">Distribuição atual</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-gray-900"><?php echo $totalConsignacoes; ?></div>
                <div class="text-xs text-gray-500">Total</div>
            </div>
        </div>
        <div class="relative h-80">
            <canvas id="statusDistribuicaoChart"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Aguardar window.onload para garantir que Chart.js está carregado
window.addEventListener('load', function() {
    console.log('=== INICIANDO GRÁFICOS ===');
    
    // Debug: Verificar se os dados estão chegando
    const mesesData = <?php echo json_encode($mesesLabels); ?>;
    const graficoData = <?php echo json_encode($dadosGraficoMes); ?>;
    const statusData = <?php echo json_encode($distribuicaoStatus); ?>;
    
    console.log('Dados dos meses:', mesesData);
    console.log('Dados do gráfico:', graficoData);
    console.log('Distribuição status:', statusData);
    
    // Verificar se Chart.js está carregado
    if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js NÃO foi carregado!');
        return;
    }
    console.log('✅ Chart.js carregado com sucesso');
    
    // Verificar se há dados suficientes
    if (!mesesData || mesesData.length === 0) {
        console.warn('⚠️ Nenhum dado de meses encontrado');
    }
    
    if (!statusData || statusData.length === 0) {
        console.warn('⚠️ Nenhum dado de status encontrado');
    }
    // Configuração do gráfico de consignações por mês
    const ctxMes = document.getElementById('consignacoesPorMesChart');
    if (!ctxMes) {
        console.error('Elemento consignacoesPorMesChart não encontrado!');
        return;
    }
    
    // Verificar se há dados para o gráfico de meses
    const temDadosMeses = graficoData && Object.values(graficoData).some(arr => arr.some(val => val > 0));
    console.log('Tem dados para gráfico de meses?', temDadosMeses);
    
    if (!temDadosMeses) {
        console.log('📊 Mostrando mensagem de "sem dados" para gráfico de meses');
        ctxMes.parentElement.innerHTML = '<div class="flex items-center justify-center h-80 text-gray-500"><div class="text-center"><svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg><p class="text-lg font-medium">Nenhuma consignação encontrada</p><p class="text-sm">Crie sua primeira consignação para ver os gráficos</p></div></div>';
    } else {
        console.log('📊 Criando gráfico de barras...');
        try {
            const consignacoesPorMesChart = new Chart(ctxMes, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($mesesLabels); ?>,
                    datasets: [
                        {
                            label: 'Pendente',
                            data: <?php echo json_encode($dadosGraficoMes['pendente']); ?>,
                            backgroundColor: 'rgba(234, 179, 8, 0.8)',
                            borderColor: 'rgb(234, 179, 8)',
                            borderWidth: 1
                        },
                        {
                            label: 'Parcial',
                            data: <?php echo json_encode($dadosGraficoMes['parcial']); ?>,
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1
                        },
                        {
                            label: 'Finalizada',
                            data: <?php echo json_encode($dadosGraficoMes['finalizada']); ?>,
                            backgroundColor: 'rgba(34, 197, 94, 0.8)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1
                        },
                        {
                            label: 'Cancelada',
                            data: <?php echo json_encode($dadosGraficoMes['cancelada']); ?>,
                            backgroundColor: 'rgba(239, 68, 68, 0.8)',
                            borderColor: 'rgb(239, 68, 68)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            console.log('✅ Gráfico de barras criado com sucesso!');
        } catch (error) {
            console.error('❌ Erro ao criar gráfico de barras:', error);
        }
    }

    // Configuração do gráfico de distribuição por status
    const ctxStatus = document.getElementById('statusDistribuicaoChart');
    if (!ctxStatus) {
        console.error('Elemento statusDistribuicaoChart não encontrado!');
        return;
    }
    
    // Verificar se há dados para o gráfico de status
    console.log('Tem dados para gráfico de status?', statusData && statusData.length > 0);
    
    if (!statusData || statusData.length === 0) {
        console.log('🥧 Mostrando mensagem de "sem dados" para gráfico de status');
        ctxStatus.parentElement.innerHTML = '<div class="flex items-center justify-center h-80 text-gray-500"><div class="text-center"><svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg><p class="text-lg font-medium">Nenhuma consignação encontrada</p><p class="text-sm">Crie sua primeira consignação para ver a distribuição</p></div></div>';
    } else {
        console.log('🥧 Criando gráfico de pizza...');
        try {
            const statusDataLocal = <?php echo json_encode($distribuicaoStatus); ?>;

            const statusLabels = [];
            const statusValues = [];
            const statusColors = [];

            statusDataLocal.forEach(item => {
                const statusNames = {
                    'pendente': 'Pendente',
                    'parcial': 'Parcial',
                    'finalizada': 'Finalizada',
                    'cancelada': 'Cancelada'
                };
                
                const colors = {
                    'pendente': 'rgba(234, 179, 8, 0.8)',
                    'parcial': 'rgba(59, 130, 246, 0.8)',
                    'finalizada': 'rgba(34, 197, 94, 0.8)',
                    'cancelada': 'rgba(239, 68, 68, 0.8)'
                };
                
                statusLabels.push(statusNames[item.status] || item.status);
                statusValues.push(parseInt(item.total));
                statusColors.push(colors[item.status] || 'rgba(156, 163, 175, 0.8)');
            });

            const statusDistribuicaoChart = new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusValues,
                        backgroundColor: statusColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
            console.log('✅ Gráfico de pizza criado com sucesso!');
        } catch (error) {
            console.error('❌ Erro ao criar gráfico de pizza:', error);
        }
    }
    
    console.log('=== GRÁFICOS FINALIZADOS ===');
});
</script>

<?php include 'includes/footer.php'; ?>
