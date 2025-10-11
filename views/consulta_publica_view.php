<?php
/**
 * Visualização Detalhada de Consignação - Consulta Pública
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.2.1
 */
?>

<!-- Header -->
<div class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="<?php echo defined('BASE_PATH') ? BASE_PATH : ''; ?>/consulta_publica.php?token=<?php echo $token; ?>" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div class="flex-1">
                <div class="flex items-center gap-3 flex-wrap mb-2">
                    <h1 class="text-3xl font-bold text-gray-900">Consignação #<?php echo $consignacao['id']; ?></h1>
                    <?php if ($consignacao['tipo'] === 'continua'): ?>
                        <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 border-2 border-green-300">
                            🔄 Contínua
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800 border-2 border-blue-300">
                            📦 Pontual
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-gray-600"><?php echo htmlspecialchars($consignacao['estabelecimento']); ?></p>
            </div>
            <span class="px-4 py-2 text-sm font-medium rounded-full <?php echo getStatusBadgeClass($consignacao['status']); ?>">
                <?php echo translateStatus($consignacao['status']); ?>
            </span>
        </div>
    </div>
</div>

<!-- Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Coluna Principal -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Informações -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informações da Consignação</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Data da Consignação</p>
                        <p class="font-medium text-gray-900"><?php echo formatDate($consignacao['data_consignacao']); ?></p>
                    </div>
                    <?php if (!empty($consignacao['data_vencimento'])): ?>
                    <div>
                        <p class="text-sm text-gray-500">Data de Vencimento</p>
                        <p class="font-medium text-gray-900"><?php echo formatDate($consignacao['data_vencimento']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($consignacao['observacoes'])): ?>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-500 mb-1">Observações</p>
                        <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($consignacao['observacoes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Produtos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-emerald-50">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Produtos Consignados</h2>
                        <?php if ($total_pendente > 0): ?>
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-full">
                                <?php echo $total_pendente; ?> ainda consignado(s)
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                                ✅ Tudo processado
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Consignado</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Vendido</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Devolvido</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ainda Consignado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Unit.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($itens as $item): 
                                if ($consignacao['tipo'] === 'continua') {
                                    $total_entregue = $item['total_entregue'] ?? 0;
                                    $pendente = $total_entregue - $item['quantidade_vendida'] - $item['quantidade_devolvida'];
                                } else {
                                    $pendente = $item['quantidade_consignada'] - $item['quantidade_vendida'] - $item['quantidade_devolvida'];
                                }
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['produto']); ?></td>
                                    <td class="px-6 py-4 text-sm text-center text-gray-900"><?php echo $consignacao['tipo'] === 'continua' ? ($item['total_entregue'] ?? 0) : $item['quantidade_consignada']; ?></td>
                                    <td class="px-6 py-4 text-sm text-center text-green-600 font-medium"><?php echo $item['quantidade_vendida']; ?></td>
                                    <td class="px-6 py-4 text-sm text-center text-blue-600"><?php echo $item['quantidade_devolvida']; ?></td>
                                    <td class="px-6 py-4 text-sm text-center <?php echo $pendente > 0 ? 'text-yellow-600 font-medium' : 'text-gray-500'; ?>">
                                        <?php echo $pendente; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900"><?php echo formatMoney($item['preco_unitario']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-gray-200">
                    <?php foreach ($itens as $item): 
                        if ($consignacao['tipo'] === 'continua') {
                            $total_entregue = $item['total_entregue'] ?? 0;
                            $pendente = $total_entregue - $item['quantidade_vendida'] - $item['quantidade_devolvida'];
                        } else {
                            $pendente = $item['quantidade_consignada'] - $item['quantidade_vendida'] - $item['quantidade_devolvida'];
                        }
                    ?>
                        <div class="p-4">
                            <h3 class="font-medium text-gray-900 mb-3"><?php echo htmlspecialchars($item['produto']); ?></h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-gray-500">Consignado</p>
                                    <p class="font-semibold text-gray-900"><?php echo $consignacao['tipo'] === 'continua' ? ($item['total_entregue'] ?? 0) : $item['quantidade_consignada']; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Vendido</p>
                                    <p class="font-semibold text-green-600"><?php echo $item['quantidade_vendida']; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Devolvido</p>
                                    <p class="font-semibold text-blue-600"><?php echo $item['quantidade_devolvida']; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Ainda Consignado</p>
                                    <p class="font-semibold <?php echo $pendente > 0 ? 'text-yellow-600' : 'text-gray-500'; ?>"><?php echo $pendente; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Histórico de Movimentações (Consignação Contínua) -->
            <?php if ($consignacao['tipo'] === 'continua' && !empty($movimentacoes)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">📋 Histórico de Movimentações</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <?php 
                        $tipo_config = [
                            'entrega' => ['icon' => '📦', 'color' => 'green', 'text' => 'Entrega', 'signal' => '+'],
                            'venda' => ['icon' => '💰', 'color' => 'blue', 'text' => 'Venda', 'signal' => '-'],
                            'devolucao' => ['icon' => '🔄', 'color' => 'orange', 'text' => 'Devolução', 'signal' => '-']
                        ];
                        foreach ($movimentacoes as $mov): 
                            $config = $tipo_config[$mov['tipo']];
                        ?>
                            <div class="flex items-start gap-4 p-4 bg-<?php echo $config['color']; ?>-50 border-l-4 border-<?php echo $config['color']; ?>-500 rounded-lg">
                                <div class="text-2xl"><?php echo $config['icon']; ?></div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($mov['produto']); ?></h4>
                                        <span class="text-lg font-bold text-<?php echo $config['color']; ?>-600">
                                            <?php echo $config['signal'] . $mov['quantidade']; ?> un
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-sm text-gray-600">
                                        <span><?php echo $config['text']; ?></span>
                                        <span class="hidden sm:inline">•</span>
                                        <span><?php echo formatDate($mov['data_movimentacao']); ?></span>
                                        <span class="hidden sm:inline">•</span>
                                        <span><?php echo formatMoney($mov['preco_unitario']); ?>/un</span>
                                    </div>
                                    <?php if (!empty($mov['observacoes'])): ?>
                                        <p class="text-xs text-gray-600 mt-2"><?php echo htmlspecialchars($mov['observacoes']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pagamentos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Histórico de Pagamentos</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($pagamentos)): ?>
                        <p class="text-center text-gray-500 py-4">Nenhum pagamento registrado</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($pagamentos as $pag): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo formatMoney($pag['valor_pago']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo formatDate($pag['data_pagamento']); ?> - <?php echo ucfirst($pag['forma_pagamento']); ?></p>
                                        <?php if (!empty($pag['observacoes'])): ?>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($pag['observacoes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar - Resumo -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24 space-y-6">
                <!-- Resumo de Quantidades -->
                <div>
                    <h3 class="font-semibold text-gray-900 mb-4">Resumo de Quantidades</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Consignado</span>
                            <span class="text-lg font-bold text-gray-900"><?php echo $total_consignado; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Vendido</span>
                            <span class="text-lg font-bold text-green-600"><?php echo $total_vendido; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Devolvido</span>
                            <span class="text-lg font-bold text-blue-600"><?php echo $total_devolvido; ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t-2 border-yellow-200 bg-yellow-50 -mx-6 px-6 py-3 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">🔄 Ainda Consignado</span>
                            <span class="text-2xl font-bold <?php echo $total_pendente > 0 ? 'text-yellow-600' : 'text-green-600'; ?>">
                                <?php echo $total_pendente; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Resumo Financeiro -->
                <div class="pt-6 border-t border-gray-200">
                    <h3 class="font-semibold text-gray-900 mb-4">Resumo Financeiro</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Valor Total</span>
                            <span class="font-semibold text-gray-900"><?php echo formatMoney($valor_total); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Valor Pago</span>
                            <span class="font-semibold text-green-600"><?php echo formatMoney($valor_pago); ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                            <span class="font-medium text-gray-900">Saldo Pendente</span>
                            <span class="text-xl font-bold <?php echo $saldo_pendente > 0 ? 'text-orange-600' : 'text-green-600'; ?>">
                                <?php echo formatMoney($saldo_pendente); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contato -->
    <?php if (!empty($estabelecimento['nome_empresa']) || !empty($estabelecimento['email_empresa']) || !empty($estabelecimento['whatsapp_empresa'])): ?>
    <div class="mt-8 bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Coluna Esquerda: Logo, Nome, Email -->
            <div class="flex items-center gap-4">
                <?php if (!empty($estabelecimento['logo_empresa'])): ?>
                    <img src="<?php echo (defined('BASE_PATH') ? BASE_PATH : '') . '/' . $estabelecimento['logo_empresa']; ?>" alt="Logo" class="w-16 h-16 rounded-lg object-cover">
                <?php else: ?>
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-emerald-600 rounded-lg flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">
                            <?php echo strtoupper(substr($estabelecimento['nome_empresa'] ?? 'C', 0, 1)); ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <div>
                    <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($estabelecimento['nome_empresa'] ?? 'Consignados'); ?></h3>
                    <?php if (!empty($estabelecimento['email_empresa'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($estabelecimento['email_empresa']); ?>" class="text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <?php echo htmlspecialchars($estabelecimento['email_empresa']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Coluna Direita: WhatsApp -->
            <?php if (!empty($estabelecimento['whatsapp_empresa'])): ?>
            <div class="flex items-center justify-center md:justify-end">
                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $estabelecimento['whatsapp_empresa']); ?>" target="_blank" class="flex items-center gap-3 px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    <div class="text-left">
                        <p class="text-xs opacity-90">Dúvidas? Fale conosco</p>
                        <p class="font-semibold"><?php echo $estabelecimento['whatsapp_empresa']; ?></p>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="mt-12 text-center">
        <p class="text-sm text-gray-600">
            Desenvolvido por <a href="https://dantetesta.com.br" target="_blank" class="text-blue-600 hover:text-blue-700 font-medium">Dante Testa</a>
        </p>
        <p class="text-xs text-gray-500 mt-2">Versão 1.2.0</p>
    </div>
</div>
