<?php
/**
 * Gestão de Movimentações - Consignações Contínuas
 * 
 * Permite adicionar produtos, registrar vendas e devoluções
 * em consignações do tipo contínua
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0 SaaS
 */

require_once 'config/config.php';
requireLogin();
$pageTitle = 'Movimentações';
$db = Database::getInstance()->getConnection();

$consignacao_id = $_GET['consignacao_id'] ?? null;
$tipo_pre_selecionado = $_GET['tipo'] ?? ''; // entrega, venda, devolucao

if (!$consignacao_id) {
    setFlashMessage('error', 'Consignação não especificada.');
    redirect('/consignacoes.php');
}

// Buscar dados da consignação
$stmt = $db->prepare("
    SELECT c.*, e.nome as estabelecimento
    FROM consignacoes c
    INNER JOIN estabelecimentos e ON c.estabelecimento_id = e.id
    WHERE c.id = ? AND c.tipo = 'continua'
");
$stmt->execute([$consignacao_id]);
$consignacao = $stmt->fetch();

if (!$consignacao) {
    setFlashMessage('error', 'Consignação não encontrada ou não é do tipo contínua.');
    redirect('/consignacoes.php');
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_movimentacao') {
        $movimentacao_id = intval($_POST['movimentacao_id']);
        $consignacao_id = intval($_POST['consignacao_id']);
        
        try {
            $db->beginTransaction();
            
            // Buscar dados da movimentação antes de deletar (do tenant)
            $tenant_id = getTenantId();
            $stmt = $db->prepare("SELECT produto_id, tipo, quantidade FROM movimentacoes_consignacao WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$movimentacao_id, $tenant_id]);
            $mov = $stmt->fetch();
            
            if ($mov) {
                // Reverter o estoque
                if ($mov['tipo'] === 'entrega') {
                    // Entrega foi deletada: devolve ao estoque
                    $stmt = $db->prepare("UPDATE produtos SET estoque_total = estoque_total + ? WHERE id = ?");
                    $stmt->execute([$mov['quantidade'], $mov['produto_id']]);
                } elseif ($mov['tipo'] === 'devolucao') {
                    // Devolução foi deletada: verificar se tem estoque suficiente (do tenant)
                    $stmt = $db->prepare("SELECT estoque_total FROM produtos WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$mov['produto_id'], $tenant_id]);
                    $estoque_atual = $stmt->fetchColumn();
                    
                    if ($estoque_atual < $mov['quantidade']) {
                        throw new Exception("Não é possível deletar esta devolução. Estoque insuficiente para reverter.");
                    }
                    
                    // Remove do estoque
                    $stmt = $db->prepare("UPDATE produtos SET estoque_total = estoque_total - ? WHERE id = ?");
                    $stmt->execute([$mov['quantidade'], $mov['produto_id']]);
                }
                // Venda deletada: não altera estoque
                
                // Deletar movimentação
                $stmt = $db->prepare("DELETE FROM movimentacoes_consignacao WHERE id = ?");
                $stmt->execute([$movimentacao_id]);
            }
            
            $db->commit();
            setFlashMessage('success', 'Movimentação deletada com sucesso!');
            redirect('/movimentacoes.php?consignacao_id=' . $consignacao_id);
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erro ao deletar movimentação: " . $e->getMessage());
            setFlashMessage('error', $e->getMessage());
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Erro ao deletar movimentação: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao deletar movimentação.');
        }
    } elseif ($action === 'add_movimentacao') {
        $produto_id = intval($_POST['produto_id']);
        $tipo = $_POST['tipo']; // entrega, venda, devolucao
        $quantidade = intval($_POST['quantidade']);
        $data_movimentacao = $_POST['data_movimentacao'];
        $observacoes = sanitize($_POST['observacoes']);
        
        try {
            $db->beginTransaction();
            
            // Buscar preço do produto (do tenant)
            $tenant_id = getTenantId();
            $stmt = $db->prepare("SELECT preco_venda, estoque_total FROM produtos WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$produto_id, $tenant_id]);
            $produto = $stmt->fetch();
            $preco = $produto['preco_venda'];
            $estoque_geral = $produto['estoque_total'];
            
            // Buscar saldo atual no estabelecimento (estoque consignado)
            $stmt = $db->prepare("
                SELECT COALESCE(
                    SUM(CASE WHEN tipo = 'entrega' THEN quantidade ELSE 0 END) - 
                    SUM(CASE WHEN tipo = 'venda' THEN quantidade ELSE 0 END) - 
                    SUM(CASE WHEN tipo = 'devolucao' THEN quantidade ELSE 0 END), 
                0) as saldo_estabelecimento
                FROM movimentacoes_consignacao
                WHERE consignacao_id = ? AND produto_id = ?
            ");
            $stmt->execute([$consignacao_id, $produto_id]);
            $saldo_estabelecimento = $stmt->fetchColumn();
            
            // Validações por tipo de movimentação
            if ($tipo === 'entrega') {
                // Entrega: validar estoque geral da empresa
                if ($estoque_geral < $quantidade) {
                    throw new Exception("Estoque insuficiente na empresa. Disponível: {$estoque_geral} unidades");
                }
            } elseif ($tipo === 'venda') {
                // Venda: validar saldo no estabelecimento
                if ($saldo_estabelecimento < $quantidade) {
                    throw new Exception("Estoque insuficiente no estabelecimento. Disponível: {$saldo_estabelecimento} unidades");
                }
            } elseif ($tipo === 'devolucao') {
                // Devolução: validar saldo no estabelecimento
                if ($saldo_estabelecimento < $quantidade) {
                    throw new Exception("Estoque insuficiente no estabelecimento para devolução. Disponível: {$saldo_estabelecimento} unidades");
                }
            }
            
            // Inserir movimentação (com tenant_id)
            $tenant_id = getTenantId();
            $stmt = $db->prepare("
                INSERT INTO movimentacoes_consignacao 
                (tenant_id, consignacao_id, produto_id, tipo, quantidade, preco_unitario, data_movimentacao, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tenant_id, $consignacao_id, $produto_id, $tipo, $quantidade, $preco, $data_movimentacao, $observacoes]);
            
            // Atualizar estoque do produto (apenas do tenant)
            if ($tipo === 'entrega') {
                // Entrega: diminui estoque (produto sai para consignação)
                $stmt = $db->prepare("UPDATE produtos SET estoque_total = estoque_total - ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$quantidade, $produto_id, $tenant_id]);
            } elseif ($tipo === 'devolucao') {
                // Devolução: aumenta estoque (produto volta)
                $stmt = $db->prepare("UPDATE produtos SET estoque_total = estoque_total + ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$quantidade, $produto_id, $tenant_id]);
            }
            // Venda: não altera estoque (produto já estava consignado)
            
            $db->commit();
            
            // Atualizar status automático
            atualizarStatusAutomatico($db, $consignacao_id);
            
            $tipo_texto = [
                'entrega' => 'Entrega registrada',
                'venda' => 'Venda registrada',
                'devolucao' => 'Devolução registrada'
            ];
            
            setFlashMessage('success', $tipo_texto[$tipo] . ' com sucesso!');
            redirect('/consignacoes.php?action=view&id=' . $consignacao_id);
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erro ao registrar movimentação: " . $e->getMessage());
            setFlashMessage('error', 'Erro: ' . $e->getMessage());
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Erro ao registrar movimentação: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao registrar movimentação.');
        }
    }
}

// Buscar estoque atual (usando VIEW, do tenant)
$tenant_id = getTenantId();
$stmt = $db->prepare("
    SELECT * FROM vw_estoque_continuo 
    WHERE consignacao_id = ? AND tenant_id = ?
    ORDER BY produto
");
$stmt->execute([$consignacao_id, $tenant_id]);
$estoque_atual = $stmt->fetchAll();

// Buscar histórico de movimentações (do tenant)
$stmt = $db->prepare("
    SELECT m.*, p.nome as produto
    FROM movimentacoes_consignacao m
    INNER JOIN produtos p ON m.produto_id = p.id
    WHERE m.consignacao_id = ? AND m.tenant_id = ?
    ORDER BY m.data_movimentacao DESC, m.criado_em DESC
    LIMIT 50
");
$stmt->execute([$consignacao_id, $tenant_id]);
$movimentacoes = $stmt->fetchAll();

// Buscar produtos disponíveis com estoque (do tenant)
$stmt = $db->prepare("SELECT id, nome, preco_venda, estoque_total FROM produtos WHERE ativo = 1 AND tenant_id = ? ORDER BY nome");
$stmt->execute([$tenant_id]);
$produtos = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
        <a href="/consignacoes.php?action=view&id=<?php echo $consignacao_id; ?>" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Movimentações - Consignação #<?php echo $consignacao['id']; ?></h1>
            <p class="text-gray-600 mt-1"><?php echo sanitize($consignacao['estabelecimento']); ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Coluna Principal -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Nova Movimentação -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">📝 Registrar Movimentação</h2>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_movimentacao">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tipo de Movimentação -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimentação *</label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="relative flex items-center justify-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-green-500 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                                <input type="radio" name="tipo" value="entrega" <?php echo $tipo_pre_selecionado === 'entrega' ? 'checked' : ''; ?> required class="sr-only">
                                <div class="text-center">
                                    <div class="text-2xl mb-1">📦</div>
                                    <div class="text-sm font-medium">Entrega</div>
                                </div>
                            </label>
                            <label class="relative flex items-center justify-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                <input type="radio" name="tipo" value="venda" <?php echo $tipo_pre_selecionado === 'venda' ? 'checked' : ''; ?> required class="sr-only">
                                <div class="text-center">
                                    <div class="text-2xl mb-1">💰</div>
                                    <div class="text-sm font-medium">Venda</div>
                                </div>
                            </label>
                            <label class="relative flex items-center justify-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-orange-500 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50">
                                <input type="radio" name="tipo" value="devolucao" <?php echo $tipo_pre_selecionado === 'devolucao' ? 'checked' : ''; ?> required class="sr-only">
                                <div class="text-center">
                                    <div class="text-2xl mb-1">🔄</div>
                                    <div class="text-sm font-medium">Devolução</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Produto -->
                    <div>
                        <label for="produto_id" class="block text-sm font-medium text-gray-700 mb-2">Produto *</label>
                        <select 
                            id="produto_id" 
                            name="produto_id" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        >
                            <option value="">Selecione um produto</option>
                            <?php foreach ($produtos as $prod): ?>
                                <option value="<?php echo $prod['id']; ?>" data-preco="<?php echo $prod['preco_venda']; ?>" data-estoque="<?php echo $prod['estoque_total']; ?>">
                                    <?php echo sanitize($prod['nome']); ?> - <?php echo formatMoney($prod['preco_venda']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p id="estoque-info" class="text-xs mt-1 text-gray-500"></p>
                    </div>
                    
                    <!-- Quantidade -->
                    <div>
                        <label for="quantidade" class="block text-sm font-medium text-gray-700 mb-2">Quantidade *</label>
                        <input 
                            type="number" 
                            id="quantidade" 
                            name="quantidade" 
                            min="1"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="0"
                        >
                    </div>
                    
                    <!-- Data -->
                    <div>
                        <label for="data_movimentacao" class="block text-sm font-medium text-gray-700 mb-2">Data *</label>
                        <input 
                            type="date" 
                            id="data_movimentacao" 
                            name="data_movimentacao" 
                            required
                            value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        >
                    </div>
                    
                    <!-- Observações -->
                    <div class="md:col-span-2">
                        <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                        <textarea 
                            id="observacoes" 
                            name="observacoes" 
                            rows="2"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Informações adicionais (opcional)"
                        ></textarea>
                    </div>
                </div>
                
                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition">
                    ✓ Registrar Movimentação
                </button>
            </form>
        </div>
        
        <!-- Histórico de Movimentações -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">📋 Histórico de Movimentações</h2>
            </div>
            <div class="p-6">
                <?php if (empty($movimentacoes)): ?>
                    <p class="text-center text-gray-500 py-8">Nenhuma movimentação registrada ainda</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($movimentacoes as $mov): 
                            $tipo_config = [
                                'entrega' => ['icon' => '📦', 'color' => 'green', 'text' => 'Entrega', 'signal' => '+'],
                                'venda' => ['icon' => '💰', 'color' => 'blue', 'text' => 'Venda', 'signal' => '-'],
                                'devolucao' => ['icon' => '🔄', 'color' => 'orange', 'text' => 'Devolução', 'signal' => '-']
                            ];
                            $config = $tipo_config[$mov['tipo']];
                        ?>
                            <div class="flex items-start gap-4 p-4 bg-<?php echo $config['color']; ?>-50 border-l-4 border-<?php echo $config['color']; ?>-500 rounded-lg relative group">
                                <div class="text-3xl"><?php echo $config['icon']; ?></div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="font-semibold text-gray-900"><?php echo sanitize($mov['produto']); ?></h4>
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
                                        <p class="text-xs text-gray-600 mt-2"><?php echo sanitize($mov['observacoes']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Botão Deletar (canto inferior direito, aparece no hover) -->
                                <button 
                                    onclick="confirmarDeleteMovimentacao(<?php echo $mov['id']; ?>, '<?php echo addslashes($mov['produto']); ?>', '<?php echo $config['text']; ?>')" 
                                    class="absolute bottom-2 right-2 p-1.5 text-gray-400 hover:text-red-600 hover:bg-white rounded transition-all opacity-0 group-hover:opacity-100"
                                    title="Deletar movimentação"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar - Estoque Atual -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24">
            <h3 class="font-semibold text-gray-900 mb-4">📦 Estoque Atual</h3>
            
            <?php if (empty($estoque_atual)): ?>
                <p class="text-center text-gray-500 py-4 text-sm">Nenhum produto em estoque</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($estoque_atual as $est): ?>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <h4 class="font-medium text-gray-900 text-sm mb-2"><?php echo sanitize($est['produto']); ?></h4>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <p class="text-gray-500">Em estoque</p>
                                    <p class="font-bold text-lg text-gray-900"><?php echo $est['saldo_atual']; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Vendido</p>
                                    <p class="font-semibold text-green-600"><?php echo $est['total_vendido']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Formulário oculto para deletar movimentação -->
<form id="deleteMovimentacaoForm" method="POST" action="<?php echo url('/movimentacoes.php'); ?>" style="display: none;">
    <input type="hidden" name="action" value="delete_movimentacao">
    <input type="hidden" name="movimentacao_id" id="deleteMovimentacaoId">
    <input type="hidden" name="consignacao_id" value="<?php echo $consignacao_id; ?>">
</form>

<script>
function confirmarDeleteMovimentacao(id, produto, tipo) {
    if (confirm('⚠️ Tem certeza que deseja deletar esta movimentação?\n\n' + tipo + ' de "' + produto + '"\n\nEsta ação não pode ser desfeita e afetará o estoque!')) {
        document.getElementById('deleteMovimentacaoId').value = id;
        document.getElementById('deleteMovimentacaoForm').submit();
    }
}

// Validação de estoque em tempo real
const tipoRadios = document.querySelectorAll('input[name="tipo"]');
const produtoSelect = document.getElementById('produto_id');
const quantidadeInput = document.getElementById('quantidade');
const estoqueInfo = document.getElementById('estoque-info');

// Dados de estoque do estabelecimento
const estoqueEstabelecimento = <?php echo json_encode(array_column($estoque_atual, null, 'produto_id')); ?>;

function atualizarEstoqueInfo() {
    const tipoSelecionado = document.querySelector('input[name="tipo"]:checked')?.value;
    const produtoOption = produtoSelect.options[produtoSelect.selectedIndex];
    const estoqueGeral = produtoOption?.dataset.estoque || 0;
    
    if (!tipoSelecionado || !produtoSelect.value) {
        estoqueInfo.textContent = '';
        return;
    }
    
    if (tipoSelecionado === 'entrega') {
        estoqueInfo.className = 'text-xs mt-1 text-blue-600 font-medium';
        estoqueInfo.textContent = `📦 Estoque disponível na empresa: ${estoqueGeral} unidades`;
    } else {
        estoqueInfo.textContent = '';
    }
}

function atualizarLimiteQuantidade() {
    const tipoSelecionado = document.querySelector('input[name="tipo"]:checked')?.value;
    const produtoId = produtoSelect.value;
    const produtoOption = produtoSelect.options[produtoSelect.selectedIndex];
    const estoqueGeral = parseInt(produtoOption?.dataset.estoque || 0);
    
    if (!tipoSelecionado || !produtoId) {
        quantidadeInput.removeAttribute('max');
        quantidadeInput.nextElementSibling?.remove();
        return;
    }
    
    let maxQuantidade = null;
    let mensagem = '';
    
    if (tipoSelecionado === 'entrega') {
        // Para entrega, limitar ao estoque geral
        maxQuantidade = estoqueGeral;
        quantidadeInput.setAttribute('max', maxQuantidade);
        mensagem = maxQuantidade > 0 ? `Máximo: ${maxQuantidade} unidades` : 'Sem estoque disponível';
    } else if (tipoSelecionado === 'venda' || tipoSelecionado === 'devolucao') {
        // Para venda e devolução, limitar ao saldo no estabelecimento
        const estoque = estoqueEstabelecimento[produtoId];
        if (estoque) {
            maxQuantidade = estoque.saldo_atual;
            mensagem = `Disponível no estabelecimento: ${maxQuantidade} unidades`;
        } else {
            maxQuantidade = 0;
            mensagem = 'Produto não disponível no estabelecimento';
        }
        quantidadeInput.setAttribute('max', maxQuantidade);
    }
    
    // Adicionar/atualizar mensagem de ajuda
    let helpText = quantidadeInput.nextElementSibling;
    if (!helpText || !helpText.classList.contains('help-text')) {
        helpText = document.createElement('p');
        helpText.className = 'help-text text-xs mt-1';
        quantidadeInput.parentNode.appendChild(helpText);
    }
    
    if (maxQuantidade === 0) {
        helpText.className = 'help-text text-xs mt-1 text-red-600 font-medium';
    } else if (tipoSelecionado === 'entrega') {
        helpText.className = 'help-text text-xs mt-1 text-green-600';
    } else {
        helpText.className = 'help-text text-xs mt-1 text-blue-600';
    }
    
    helpText.textContent = mensagem;
    
    // Validar valor atual
    if (maxQuantidade !== null && parseInt(quantidadeInput.value) > maxQuantidade) {
        quantidadeInput.value = maxQuantidade;
    }
}

// Event listeners
tipoRadios.forEach(radio => {
    radio.addEventListener('change', () => {
        atualizarEstoqueInfo();
        atualizarLimiteQuantidade();
    });
});

produtoSelect.addEventListener('change', () => {
    atualizarEstoqueInfo();
    atualizarLimiteQuantidade();
});

quantidadeInput.addEventListener('input', function() {
    const max = this.getAttribute('max');
    if (max && parseInt(this.value) > parseInt(max)) {
        this.value = max;
    }
});

// Executar ao carregar
atualizarEstoqueInfo();
atualizarLimiteQuantidade();
</script>

<?php include 'includes/footer.php'; ?>
