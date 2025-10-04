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

// Produtos com baixo estoque (menos de 20 unidades, do tenant)
$stmt = $db->prepare("
    SELECT 
        p.id,
        p.nome,
        p.estoque_total,
        COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0) as quantidade_consignada,
        (p.estoque_total - COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0)) as estoque_disponivel
    FROM produtos p
    LEFT JOIN consignacao_itens ci ON p.id = ci.produto_id AND ci.tenant_id = ?
    LEFT JOIN consignacoes c ON ci.consignacao_id = c.id AND c.status IN ('pendente', 'parcial') AND c.tenant_id = ?
    WHERE p.ativo = 1 AND p.tenant_id = ?
    GROUP BY p.id, p.nome, p.estoque_total
    HAVING estoque_disponivel < 20
    ORDER BY estoque_disponivel ASC
    LIMIT 5
");
$stmt->execute([$tenant_id, $tenant_id, $tenant_id]);
$produtosBaixoEstoque = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-600 mt-1">Visão geral do sistema de consignados</p>
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
                    <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded">📦 <?php echo $consignacoesPontuais; ?></span>
                    <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded">🔄 <?php echo $consignacoesContinuas; ?></span>
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
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
        </div>
        <a href="/produtos.php" class="text-sm text-purple-600 hover:text-purple-700 font-medium mt-4 inline-block">
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
                    <a href="/consignacoes.php?action=new" class="inline-block mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
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
                <a href="/consignacoes.php" class="block text-center mt-4 text-sm text-purple-600 hover:text-purple-700 font-medium">
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
                <a href="/produtos.php" class="block text-center mt-4 text-sm text-purple-600 hover:text-purple-700 font-medium">
                    Ver todos os produtos →
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="mt-8 bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl shadow-lg p-8 text-white">
    <h2 class="text-2xl font-bold mb-4">Ações Rápidas</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="/consignacoes.php?action=new" class="bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm rounded-lg p-4 transition text-center">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <p class="font-medium">Nova Consignação</p>
        </a>
        <a href="/produtos.php?action=new" class="bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm rounded-lg p-4 transition text-center">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <p class="font-medium">Novo Produto</p>
        </a>
        <a href="/estabelecimentos.php?action=new" class="bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm rounded-lg p-4 transition text-center">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <p class="font-medium">Novo Estabelecimento</p>
        </a>
        <a href="/relatorios.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm rounded-lg p-4 transition text-center">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="font-medium">Relatórios</p>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
