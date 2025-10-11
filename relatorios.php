<?php
/**
 * Relatórios do Sistema
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

require_once 'config/config.php';
requireLogin();

$pageTitle = 'Relatórios';
$db = Database::getInstance()->getConnection();

// Filtros
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Relatório de Vendas por Período (USANDO VIEW CONSOLIDADA)
$stmt = $db->prepare("
    SELECT 
        produto,
        SUM(quantidade) as total_vendido,
        SUM(valor_total) as valor_total,
        AVG(preco_unitario) as preco_medio,
        COUNT(DISTINCT origem) as tipos_consignacao
    FROM vw_vendas_consolidadas
    WHERE data_venda BETWEEN ? AND ?
    GROUP BY produto_id, produto
    HAVING total_vendido > 0
    ORDER BY total_vendido DESC
");
$stmt->execute([$data_inicio, $data_fim]);
$vendas_por_produto = $stmt->fetchAll();

// Relatório de Vendas por Estabelecimento (USANDO VIEW CONSOLIDADA)
$stmt = $db->prepare("
    SELECT 
        e.nome as estabelecimento,
        COUNT(DISTINCT v.consignacao_id) as total_consignacoes,
        SUM(v.quantidade) as total_vendido,
        SUM(v.valor_total) as valor_total
    FROM estabelecimentos e
    LEFT JOIN vw_vendas_consolidadas v ON e.id = v.estabelecimento_id 
        AND v.data_venda BETWEEN ? AND ?
    WHERE e.ativo = 1
    GROUP BY e.id, e.nome
    ORDER BY valor_total DESC
");
$stmt->execute([$data_inicio, $data_fim]);
$vendas_por_estabelecimento = $stmt->fetchAll();

// Resumo Financeiro
$stmt = $db->prepare("
    SELECT 
        SUM(ci.quantidade_vendida * ci.preco_unitario) as total_vendas,
        COUNT(DISTINCT c.id) as total_consignacoes,
        SUM(ci.quantidade_vendida) as total_itens_vendidos
    FROM consignacoes c
    LEFT JOIN consignacao_itens ci ON c.id = ci.consignacao_id
    WHERE c.data_consignacao BETWEEN ? AND ?
");
$stmt->execute([$data_inicio, $data_fim]);
$resumo = $stmt->fetch();

// Total de Pagamentos Recebidos
$stmt = $db->prepare("
    SELECT 
        SUM(p.valor_pago) as total_recebido,
        COUNT(*) as total_pagamentos
    FROM pagamentos p
    INNER JOIN consignacoes c ON p.consignacao_id = c.id
    WHERE p.data_pagamento BETWEEN ? AND ?
");
$stmt->execute([$data_inicio, $data_fim]);
$pagamentos = $stmt->fetch();

// Consignações Pendentes (valores a receber)
$stmt = $db->query("
    SELECT 
        SUM(ci.quantidade_vendida * ci.preco_unitario) - 
        COALESCE((SELECT SUM(valor_pago) FROM pagamentos WHERE consignacao_id IN (SELECT id FROM consignacoes WHERE status IN ('pendente', 'parcial'))), 0) as valor_pendente
    FROM consignacao_itens ci
    INNER JOIN consignacoes c ON ci.consignacao_id = c.id
    WHERE c.status IN ('pendente', 'parcial')
");
$valor_pendente = $stmt->fetch()['valor_pendente'] ?? 0;

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Relatórios</h1>
    <p class="text-gray-600 mt-1">Análise de vendas e desempenho</p>
</div>

<!-- Filtros -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <form method="GET" action="<?php echo url('/relatorios.php'); ?>" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
            <input 
                type="date" 
                name="data_inicio" 
                value="<?php echo $data_inicio; ?>"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
            <input 
                type="date" 
                name="data_fim" 
                value="<?php echo $data_fim; ?>"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full md:w-auto px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Cards de Resumo -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total de Vendas</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo formatMoney($resumo['total_vendas'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Recebido</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo formatMoney($pagamentos['total_recebido'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">A Receber</p>
                <p class="text-2xl font-bold text-orange-600 mt-2"><?php echo formatMoney($valor_pendente); ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Consignações</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo $resumo['total_consignacoes'] ?? 0; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Relatórios -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Vendas por Produto -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Vendas por Produto</h2>
        </div>
        <div class="overflow-x-auto">
            <?php if (empty($vendas_por_produto)): ?>
                <div class="p-8 text-center text-gray-500">
                    Nenhuma venda no período selecionado
                </div>
            <?php else: ?>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qtd</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($vendas_por_produto as $venda): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo sanitize($venda['produto']); ?></td>
                                <td class="px-6 py-4 text-sm text-center font-medium text-gray-900"><?php echo $venda['total_vendido']; ?></td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900"><?php echo formatMoney($venda['valor_total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Vendas por Estabelecimento -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Vendas por Estabelecimento</h2>
        </div>
        <div class="overflow-x-auto">
            <?php if (empty($vendas_por_estabelecimento)): ?>
                <div class="p-8 text-center text-gray-500">
                    Nenhuma venda no período selecionado
                </div>
            <?php else: ?>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estabelecimento</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Consig.</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($vendas_por_estabelecimento as $venda): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo sanitize($venda['estabelecimento']); ?></td>
                                <td class="px-6 py-4 text-sm text-center font-medium text-gray-900"><?php echo $venda['total_consignacoes']; ?></td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900"><?php echo formatMoney($venda['valor_total'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
