<?php
/**
 * Formulário de Nova Consignação
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

// Buscar estabelecimentos ativos (do tenant)
$tenant_id = getTenantId();
$stmt = $db->prepare("SELECT id, nome FROM estabelecimentos WHERE ativo = 1 AND tenant_id = ? ORDER BY nome");
$stmt->execute([$tenant_id]);
$estabelecimentos = $stmt->fetchAll();

// Buscar produtos ativos com estoque disponível (do tenant)
$stmt = $db->prepare("
    SELECT 
        p.id,
        p.nome as produto,
        p.preco_venda,
        p.estoque_total,
        COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0) as quantidade_consignada,
        (p.estoque_total - COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0)) as estoque_disponivel
    FROM produtos p
    LEFT JOIN consignacao_itens ci ON p.id = ci.produto_id AND ci.tenant_id = ?
    LEFT JOIN consignacoes c ON ci.consignacao_id = c.id AND c.status IN ('pendente', 'parcial') AND c.tenant_id = ?
    WHERE p.ativo = 1 AND p.tenant_id = ?
    GROUP BY p.id, p.nome, p.preco_venda, p.estoque_total
    HAVING estoque_disponivel > 0
    ORDER BY p.nome
");
$stmt->execute([$tenant_id, $tenant_id, $tenant_id]);
$produtos = $stmt->fetchAll();
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
        <a href="/consignacoes.php" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Nova Consignação</h1>
            <p class="text-gray-600 mt-1">Registre uma nova consignação de produtos</p>
        </div>
    </div>
</div>

<?php if (empty($estabelecimentos)): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <p class="text-yellow-700">
                Você precisa cadastrar pelo menos um estabelecimento antes de criar uma consignação.
                <a href="/estabelecimentos.php?action=new" class="font-semibold underline">Cadastrar agora</a>
            </p>
        </div>
    </div>
<?php elseif (empty($produtos)): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <p class="text-yellow-700">
                Não há produtos com estoque disponível para consignação.
                <a href="/produtos.php?action=new" class="font-semibold underline">Cadastrar produtos</a>
            </p>
        </div>
    </div>
<?php else: ?>

<!-- Formulário -->
<form method="POST" action="<?php echo url('/consignacoes.php'); ?>" id="consignacaoForm">
    <input type="hidden" name="action" value="create">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Coluna Principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Informações Básicas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informações da Consignação</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Estabelecimento -->
                    <div>
                        <label for="estabelecimento_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Estabelecimento *
                        </label>
                        <select 
                            id="estabelecimento_id" 
                            name="estabelecimento_id" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Selecione um estabelecimento</option>
                            <?php foreach ($estabelecimentos as $estab): ?>
                                <option value="<?php echo $estab['id']; ?>"><?php echo sanitize($estab['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tipo de Consignação -->
                    <div>
                        <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Consignação *
                        </label>
                        <select 
                            id="tipo" 
                            name="tipo" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            onchange="mostrarInfoTipo(this.value)"
                        >
                            <option value="pontual">📦 Pontual (Fecha quando finalizar)</option>
                            <option value="continua">🔄 Contínua (Sempre aberta)</option>
                        </select>
                        <div id="info-tipo" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm">
                            <p class="text-blue-800"><strong>Pontual:</strong> Ideal para eventos, feiras ou entregas únicas. Fecha quando todos os produtos forem vendidos/devolvidos.</p>
                        </div>
                    </div>

                    <!-- Data da Consignação -->
                    <div>
                        <label for="data_consignacao" class="block text-sm font-medium text-gray-700 mb-2">
                            Data da Consignação *
                        </label>
                        <input 
                            type="date" 
                            id="data_consignacao" 
                            name="data_consignacao" 
                            required
                            value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Data de Vencimento -->
                    <div>
                        <label for="data_vencimento" class="block text-sm font-medium text-gray-700 mb-2">
                            Data de Vencimento
                        </label>
                        <input 
                            type="date" 
                            id="data_vencimento" 
                            name="data_vencimento"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Observações -->
                    <div class="md:col-span-2">
                        <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-2">
                            Observações
                        </label>
                        <textarea 
                            id="observacoes" 
                            name="observacoes" 
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Informações adicionais sobre a consignação"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Produtos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Produtos</h2>
                    <button type="button" onclick="adicionarProduto()" class="px-3 py-1 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                        + Adicionar Produto
                    </button>
                </div>

                <div id="produtos-container" class="space-y-3">
                    <!-- Produtos serão adicionados aqui via JavaScript -->
                </div>

                <div id="empty-message" class="text-center py-8 text-gray-500">
                    Clique em "Adicionar Produto" para incluir itens na consignação
                </div>
            </div>
        </div>

        <!-- Coluna Lateral - Resumo -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Resumo</h2>
                
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Total de Itens:</span>
                        <span id="total-itens" class="font-semibold text-gray-900">0</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Quantidade Total:</span>
                        <span id="total-quantidade" class="font-semibold text-gray-900">0</span>
                    </div>
                    <div class="border-t border-gray-200 pt-3">
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-900">Valor Total:</span>
                            <span id="valor-total" class="text-xl font-bold text-blue-600">R$ 0,00</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-emerald-700 transition shadow-md">
                        Criar Consignação
                    </button>
                    <a href="/consignacoes.php" class="block w-full px-4 py-3 border border-gray-300 text-gray-700 text-center font-medium rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Dados dos produtos disponíveis
const produtosDisponiveis = <?php echo json_encode($produtos); ?>;
let contadorProdutos = 0;

function adicionarProduto() {
    const container = document.getElementById('produtos-container');
    const emptyMessage = document.getElementById('empty-message');
    
    const div = document.createElement('div');
    div.className = 'flex gap-3 items-start bg-gray-50 p-4 rounded-lg produto-item';
    div.id = `produto-${contadorProdutos}`;
    
    div.innerHTML = `
        <div class="flex-1">
            <select name="produtos[]" onchange="atualizarResumo()" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                <option value="">Selecione um produto</option>
                ${produtosDisponiveis.map(p => `
                    <option value="${p.id}" data-preco="${p.preco_venda}" data-estoque="${p.estoque_disponivel}">
                        ${p.produto} - R$ ${parseFloat(p.preco_venda).toFixed(2)} (Disp: ${p.estoque_disponivel})
                    </option>
                `).join('')}
            </select>
        </div>
        <div class="w-24">
            <input 
                type="number" 
                name="quantidades[]" 
                min="1" 
                value="1"
                onchange="atualizarResumo()"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="Qtd"
            >
        </div>
        <button type="button" onclick="removerProduto(${contadorProdutos})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    `;
    
    container.appendChild(div);
    emptyMessage.style.display = 'none';
    contadorProdutos++;
    atualizarResumo();
}

function removerProduto(id) {
    const elemento = document.getElementById(`produto-${id}`);
    elemento.remove();
    
    const container = document.getElementById('produtos-container');
    const emptyMessage = document.getElementById('empty-message');
    
    if (container.children.length === 0) {
        emptyMessage.style.display = 'block';
    }
    
    atualizarResumo();
}

function mostrarInfoTipo(tipo) {
    const infoDiv = document.getElementById('info-tipo');
    
    if (tipo === 'pontual') {
        infoDiv.innerHTML = '<p class="text-blue-800"><strong>📦 Pontual:</strong> Ideal para eventos, feiras ou entregas únicas. Fecha quando todos os produtos forem vendidos/devolvidos.</p>';
        infoDiv.className = 'mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm';
    } else {
        infoDiv.innerHTML = '<p class="text-green-800"><strong>🔄 Contínua:</strong> Perfeita para clientes fixos! Fica sempre aberta, pode adicionar mais produtos a qualquer momento e registrar vendas conforme acontecem.</p>';
        infoDiv.className = 'mt-2 p-3 bg-green-50 border border-green-200 rounded-lg text-sm';
    }
}

function atualizarResumo() {
    const items = document.querySelectorAll('.produto-item');
    let totalItens = 0;
    let totalQuantidade = 0;
    let valorTotal = 0;
    
    items.forEach(item => {
        const select = item.querySelector('select[name="produtos[]"]');
        const input = item.querySelector('input[name="quantidades[]"]');
        
        if (select.value && input.value) {
            const option = select.options[select.selectedIndex];
            const preco = parseFloat(option.dataset.preco) || 0;
            const quantidade = parseInt(input.value) || 0;
            
            if (preco > 0 && quantidade > 0) {
                totalItens++;
                totalQuantidade += quantidade;
                valorTotal += preco * quantidade;
            }
        }
    });
    
    document.getElementById('total-itens').textContent = totalItens;
    document.getElementById('total-quantidade').textContent = totalQuantidade;
    document.getElementById('valor-total').textContent = 'R$ ' + valorTotal.toFixed(2).replace('.', ',');
}

// Validação do formulário
document.getElementById('consignacaoForm').addEventListener('submit', function(e) {
    const items = document.querySelectorAll('.produto-item');
    if (items.length === 0) {
        e.preventDefault();
        alert('Adicione pelo menos um produto à consignação!');
        return false;
    }
});

// Adicionar primeiro produto automaticamente
adicionarProduto();
</script>

<?php endif; ?>
