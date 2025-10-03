<?php
/**
 * Gerenciamento de Produtos
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

require_once 'config/config.php';
requireLogin();

$pageTitle = 'Produtos';
$db = Database::getInstance()->getConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $id = $_POST['id'] ?? null;
        $nome = sanitize($_POST['nome']);
        $descricao = sanitize($_POST['descricao']);
        $preco_venda = floatval($_POST['preco_venda']);
        $preco_custo = floatval($_POST['preco_custo']);
        $estoque_total = intval($_POST['estoque_total']);
        
        try {
            if ($action === 'create') {
                $stmt = $db->prepare("INSERT INTO produtos (nome, descricao, preco_venda, preco_custo, estoque_total) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $descricao, $preco_venda, $preco_custo, $estoque_total]);
                setFlashMessage('success', 'Produto cadastrado com sucesso!');
            } else {
                $stmt = $db->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco_venda = ?, preco_custo = ?, estoque_total = ? WHERE id = ?");
                $stmt->execute([$nome, $descricao, $preco_venda, $preco_custo, $estoque_total, $id]);
                setFlashMessage('success', 'Produto atualizado com sucesso!');
            }
            redirect('/produtos.php');
        } catch (PDOException $e) {
            error_log("Erro ao salvar produto: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao salvar produto.');
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        try {
            $stmt = $db->prepare("UPDATE produtos SET ativo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Produto removido com sucesso!');
            redirect('/produtos.php');
        } catch (PDOException $e) {
            error_log("Erro ao remover produto: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao remover produto.');
        }
    }
}

// Buscar produtos
$search = $_GET['search'] ?? '';
$whereClause = "WHERE ativo = 1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (nome LIKE ? OR descricao LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("
    SELECT 
        p.*,
        COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0) as quantidade_consignada,
        (p.estoque_total - COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0)) as estoque_disponivel
    FROM produtos p
    LEFT JOIN consignacao_itens ci ON p.id = ci.produto_id
    LEFT JOIN consignacoes c ON ci.consignacao_id = c.id AND c.status IN ('pendente', 'parcial')
    $whereClause
    GROUP BY p.id
    ORDER BY p.nome ASC
");
$stmt->execute($params);
$produtos = $stmt->fetchAll();

// Se for edição, buscar produto específico
$editProduto = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM produtos WHERE id = ? AND ativo = 1");
    $stmt->execute([$_GET['id']]);
    $editProduto = $stmt->fetch();
}

$showForm = isset($_GET['action']) && ($_GET['action'] === 'new' || $_GET['action'] === 'edit');

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Produtos</h1>
        <p class="text-gray-600 mt-1">Gerencie seus produtos consignados</p>
    </div>
    <?php if (!$showForm): ?>
        <a href="?action=new" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition shadow-md">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Novo Produto
        </a>
    <?php endif; ?>
</div>

<?php if ($showForm): ?>
    <!-- Formulário de Produto -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">
                <?php echo $editProduto ? 'Editar Produto' : 'Novo Produto'; ?>
            </h2>
            <a href="<?php echo url('/produtos.php'); ?>" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </a>
        </div>

        <form method="POST" action="<?php echo url('/produtos.php'); ?>" class="space-y-6">
            <input type="hidden" name="action" value="<?php echo $editProduto ? 'update' : 'create'; ?>">
            <?php if ($editProduto): ?>
                <input type="hidden" name="id" value="<?php echo $editProduto['id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome -->
                <div class="md:col-span-2">
                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome do Produto *
                    </label>
                    <input 
                        type="text" 
                        id="nome" 
                        name="nome" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo $editProduto ? sanitize($editProduto['nome']) : ''; ?>"
                        placeholder="Ex: Pipoca Gourmet - Caramelo"
                    >
                </div>

                <!-- Descrição -->
                <div class="md:col-span-2">
                    <label for="descricao" class="block text-sm font-medium text-gray-700 mb-2">
                        Descrição
                    </label>
                    <textarea 
                        id="descricao" 
                        name="descricao" 
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Descrição detalhada do produto"
                    ><?php echo $editProduto ? sanitize($editProduto['descricao']) : ''; ?></textarea>
                </div>

                <!-- Preço de Venda -->
                <div>
                    <label for="preco_venda_display" class="block text-sm font-medium text-gray-700 mb-2">
                        Preço de Venda (R$) *
                    </label>
                    <input 
                        type="text" 
                        id="preco_venda_display"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="R$ 0,00"
                        maxlength="20"
                    >
                    <input type="hidden" id="preco_venda" name="preco_venda" value="<?php echo $editProduto ? $editProduto['preco_venda'] : ''; ?>">
                </div>

                <!-- Preço de Custo -->
                <div>
                    <label for="preco_custo_display" class="block text-sm font-medium text-gray-700 mb-2">
                        Preço de Custo (R$)
                    </label>
                    <input 
                        type="text" 
                        id="preco_custo_display"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="R$ 0,00"
                        maxlength="20"
                    >
                    <input type="hidden" id="preco_custo" name="preco_custo" value="<?php echo $editProduto ? $editProduto['preco_custo'] : ''; ?>">
                </div>

                <!-- Estoque Total -->
                <div>
                    <label for="estoque_total" class="block text-sm font-medium text-gray-700 mb-2">
                        Estoque Total *
                    </label>
                    <input 
                        type="number" 
                        id="estoque_total" 
                        name="estoque_total" 
                        min="0"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo $editProduto ? $editProduto['estoque_total'] : ''; ?>"
                        placeholder="0"
                    >
                </div>
            </div>

            <!-- Botões -->
            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="/produtos.php" class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition">
                    <?php echo $editProduto ? 'Atualizar' : 'Cadastrar'; ?>
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Barra de Busca -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" action="<?php echo url('/produtos.php'); ?>" class="flex gap-4">
            <div class="flex-1">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Buscar por nome ou descrição..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    value="<?php echo sanitize($search); ?>"
                >
            </div>
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition">
                Buscar
            </button>
            <?php if (!empty($search)): ?>
                <a href="/produtos.php" class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Limpar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Lista de Produtos -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <?php if (empty($produtos)): ?>
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p class="text-gray-500 mb-4">Nenhum produto encontrado</p>
                <a href="?action=new" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Cadastrar Primeiro Produto
                </a>
            </div>
        <?php else: ?>
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preço Venda</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preço Custo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estoque Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consignado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disponível</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($produtos as $produto): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo sanitize($produto['nome']); ?></p>
                                        <?php if (!empty($produto['descricao'])): ?>
                                            <p class="text-sm text-gray-500"><?php echo sanitize($produto['descricao']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo formatMoney($produto['preco_venda']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo formatMoney($produto['preco_custo']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $produto['estoque_total']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $produto['quantidade_consignada']; ?></td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium <?php echo $produto['estoque_disponivel'] < 10 ? 'text-red-600' : ($produto['estoque_disponivel'] < 20 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                        <?php echo $produto['estoque_disponivel']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <a href="?action=edit&id=<?php echo $produto['id']; ?>" class="text-purple-600 hover:text-purple-900 mr-3">Editar</a>
                                    <button onclick="confirmDelete(<?php echo $produto['id']; ?>, '<?php echo addslashes($produto['nome']); ?>')" class="text-red-600 hover:text-red-900">Excluir</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden divide-y divide-gray-200">
                <?php foreach ($produtos as $produto): ?>
                    <div class="p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900"><?php echo sanitize($produto['nome']); ?></h3>
                                <?php if (!empty($produto['descricao'])): ?>
                                    <p class="text-sm text-gray-500 mt-1"><?php echo sanitize($produto['descricao']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                            <div>
                                <p class="text-gray-500">Preço Venda</p>
                                <p class="font-medium text-gray-900"><?php echo formatMoney($produto['preco_venda']); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Preço Custo</p>
                                <p class="font-medium text-gray-900"><?php echo formatMoney($produto['preco_custo']); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Estoque Total</p>
                                <p class="font-medium text-gray-900"><?php echo $produto['estoque_total']; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Consignado</p>
                                <p class="font-medium text-gray-900"><?php echo $produto['quantidade_consignada']; ?></p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-gray-500">Disponível</p>
                                <p class="font-medium <?php echo $produto['estoque_disponivel'] < 10 ? 'text-red-600' : ($produto['estoque_disponivel'] < 20 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                    <?php echo $produto['estoque_disponivel']; ?> unidades
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="?action=edit&id=<?php echo $produto['id']; ?>" class="flex-1 px-4 py-2 bg-purple-600 text-white text-center rounded-lg hover:bg-purple-700 transition text-sm font-medium">
                                Editar
                            </a>
                            <button onclick="confirmDelete(<?php echo $produto['id']; ?>, '<?php echo addslashes($produto['nome']); ?>')" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium">
                                Excluir
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Form de Delete (oculto) -->
<form id="deleteForm" method="POST" action="<?php echo url('/produtos.php'); ?>" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDelete(id, nome) {
    if (confirm('Tem certeza que deseja excluir o produto "' + nome + '"?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Máscara de dinheiro para Preço de Venda
const precoVendaDisplay = document.getElementById('preco_venda_display');
const precoVendaHidden = document.getElementById('preco_venda');

if (precoVendaDisplay) {
    // Formata valor inicial se existir (edição)
    if (precoVendaHidden.value) {
        const valorInicial = parseFloat(precoVendaHidden.value);
        precoVendaDisplay.value = valorInicial.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }
    
    // Formata enquanto digita
    precoVendaDisplay.addEventListener('input', function(e) {
        let value = e.target.value;
        
        // Remove tudo que não é número
        value = value.replace(/\D/g, '');
        
        // Converte para centavos
        value = (parseInt(value) || 0) / 100;
        
        // Formata como moeda
        const formatted = value.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        
        // Atualiza o campo visível
        e.target.value = formatted;
        
        // Atualiza o campo hidden com valor numérico
        precoVendaHidden.value = value.toFixed(2);
    });
}

// Máscara de dinheiro para Preço de Custo
const precoCustoDisplay = document.getElementById('preco_custo_display');
const precoCustoHidden = document.getElementById('preco_custo');

if (precoCustoDisplay) {
    // Formata valor inicial se existir (edição)
    if (precoCustoHidden.value) {
        const valorInicial = parseFloat(precoCustoHidden.value);
        precoCustoDisplay.value = valorInicial.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }
    
    // Formata enquanto digita
    precoCustoDisplay.addEventListener('input', function(e) {
        let value = e.target.value;
        
        // Remove tudo que não é número
        value = value.replace(/\D/g, '');
        
        // Converte para centavos
        value = (parseInt(value) || 0) / 100;
        
        // Formata como moeda
        const formatted = value.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        
        // Atualiza o campo visível
        e.target.value = formatted;
        
        // Atualiza o campo hidden com valor numérico
        precoCustoHidden.value = value.toFixed(2);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
