<?php
/**
 * Gerenciamento de Produtos
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0 SaaS
 */

require_once 'config/config.php';
require_once 'classes/ImageUploader.php';
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
        $estoque_minimo = intval($_POST['estoque_minimo'] ?? 10);
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        try {
            $tenant_id = getTenantId();
            $foto = null;
            
            // Processar imagem cropada em base64
            if (!empty($_POST['foto_cropped'])) {
                $uploader = new ImageUploader();
                $uploadResult = $uploader->uploadFromBase64($_POST['foto_cropped'], $tenant_id);
                
                if ($uploadResult['success']) {
                    $foto = $uploadResult['filename'];
                    
                    // Se for atualização, deletar foto antiga
                    if ($action === 'update' && !empty($_POST['foto_antiga'])) {
                        $uploader->delete($_POST['foto_antiga']);
                    }
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }
            
            if ($action === 'create') {
                // Criar produto com todos os novos campos
                $stmt = $db->prepare("INSERT INTO produtos (tenant_id, nome, descricao, foto, preco_venda, preco_custo, estoque_total, estoque_minimo, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tenant_id, $nome, $descricao, $foto, $preco_venda, $preco_custo, $estoque_total, $estoque_minimo, $ativo]);
                setFlashMessage('success', 'Produto cadastrado com sucesso!');
            } else {
                // Atualizar produto
                if ($foto) {
                    $stmt = $db->prepare("UPDATE produtos SET nome = ?, descricao = ?, foto = ?, preco_venda = ?, preco_custo = ?, estoque_total = ?, estoque_minimo = ?, ativo = ? WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$nome, $descricao, $foto, $preco_venda, $preco_custo, $estoque_total, $estoque_minimo, $ativo, $id, $tenant_id]);
                } else {
                    $stmt = $db->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco_venda = ?, preco_custo = ?, estoque_total = ?, estoque_minimo = ?, ativo = ? WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$nome, $descricao, $preco_venda, $preco_custo, $estoque_total, $estoque_minimo, $ativo, $id, $tenant_id]);
                }
                setFlashMessage('success', 'Produto atualizado com sucesso!');
            }
            redirect('/produtos.php');
        } catch (Exception $e) {
            error_log("Erro ao salvar produto: " . $e->getMessage());
            setFlashMessage('error', $e->getMessage());
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        try {
            // Excluir apenas produtos do tenant
            $tenant_id = getTenantId();
            $stmt = $db->prepare("UPDATE produtos SET ativo = 0 WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenant_id]);
            setFlashMessage('success', 'Produto removido com sucesso!');
            redirect('/produtos.php');
        } catch (PDOException $e) {
            error_log("Erro ao remover produto: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao remover produto.');
        }
    }
}

// Buscar produtos (filtrado por tenant)
$search = $_GET['search'] ?? '';
$tenant_id = getTenantId();
$whereClause = "WHERE p.ativo = 1 AND p.tenant_id = ?";
$params = [$tenant_id];

if (!empty($search)) {
    $whereClause .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("
    SELECT 
        p.*,
        COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0) as quantidade_consignada,
        (p.estoque_total - COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0)) as estoque_disponivel
    FROM produtos p
    LEFT JOIN consignacao_itens ci ON p.id = ci.produto_id AND ci.tenant_id = ?
    LEFT JOIN consignacoes c ON ci.consignacao_id = c.id AND c.status IN ('pendente', 'parcial') AND c.tenant_id = ?
    $whereClause
    GROUP BY p.id
    ORDER BY p.nome ASC
");
$params_with_tenant = array_merge([$tenant_id, $tenant_id], $params);
$stmt->execute($params_with_tenant);
$produtos = $stmt->fetchAll();

// Se for edição, buscar produto específico (do tenant)
$editProduto = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM produtos WHERE id = ? AND tenant_id = ? AND ativo = 1");
    $stmt->execute([$_GET['id'], $tenant_id]);
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

        <form method="POST" action="<?php echo url('/produtos.php'); ?>" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="action" value="<?php echo $editProduto ? 'update' : 'create'; ?>">
            <?php if ($editProduto): ?>
                <input type="hidden" name="id" value="<?php echo $editProduto['id']; ?>">
                <input type="hidden" name="foto_antiga" value="<?php echo $editProduto['foto'] ?? ''; ?>">
            <?php endif; ?>

            <!-- Upload de Foto -->
            <div class="bg-gray-50 rounded-lg p-6 border-2 border-dashed border-gray-300">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Foto do Produto (500x500px)
                </label>
                
                <div class="flex flex-col md:flex-row gap-6 items-start">
                    <!-- Preview da imagem -->
                    <div class="flex-shrink-0">
                        <div id="imagePreview" class="w-32 h-32 bg-gray-200 rounded-lg overflow-hidden border-2 border-gray-300 flex items-center justify-center">
                            <?php if ($editProduto && $editProduto['foto']): ?>
                                <img src="<?php echo ImageUploader::getImageUrl($editProduto['foto']); ?>" alt="Preview" class="w-full h-full object-cover">
                            <?php else: ?>
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Input de arquivo -->
                    <div class="flex-1">
                        <input 
                            type="file" 
                            id="fotoInput" 
                            accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                            class="hidden"
                            onchange="openCropModal(this)"
                        >
                        <input type="hidden" id="fotoCropped" name="foto_cropped">
                        <label for="fotoInput" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Escolher Imagem
                        </label>
                        <p class="text-xs text-gray-500 mt-2">
                            Formatos: JPEG, PNG, WEBP, GIF (máx 5MB)<br>
                            Você poderá ajustar o crop em formato quadrado 1:1
                        </p>
                    </div>
                </div>
            </div>

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

                <!-- Estoque Mínimo -->
                <div>
                    <label for="estoque_minimo" class="block text-sm font-medium text-gray-700 mb-2">
                        Estoque Mínimo *
                    </label>
                    <input 
                        type="number" 
                        id="estoque_minimo" 
                        name="estoque_minimo" 
                        min="0"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo $editProduto ? $editProduto['estoque_minimo'] : '10'; ?>"
                        placeholder="10"
                    >
                    <p class="text-xs text-gray-500 mt-1">Alerta quando estoque ficar abaixo deste valor</p>
                </div>

                <!-- Status Ativo/Inativo -->
                <div class="md:col-span-2">
                    <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Produto Ativo</span>
                            <p class="text-xs text-gray-500 mt-1">Produtos inativos não aparecem nas consignações</p>
                        </div>
                        <div class="relative inline-block w-12 h-6 transition duration-200 ease-in-out">
                            <input 
                                type="checkbox" 
                                id="ativo" 
                                name="ativo" 
                                class="sr-only peer"
                                <?php echo (!$editProduto || $editProduto['ativo']) ? 'checked' : ''; ?>
                            >
                            <label for="ativo" class="block h-6 w-12 rounded-full bg-gray-300 cursor-pointer peer-checked:bg-green-500 transition-colors"></label>
                            <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-6"></span>
                        </div>
                    </label>
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

    <!-- Modal de Crop -->
    <div id="cropModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Ajustar Imagem</h3>
                <button type="button" onclick="closeCropModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Crop Area -->
            <div class="p-6">
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">
                        📐 Posicione e redimensione a área de corte. A imagem será salva em 500x500px.
                    </p>
                </div>
                
                <div class="bg-gray-100 rounded-lg overflow-hidden" style="max-height: 60vh;">
                    <img id="cropImage" src="" alt="Crop" style="max-width: 100%; display: block;">
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                <div class="flex gap-2">
                    <button type="button" onclick="rotateCrop(-90)" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                    </button>
                    <button type="button" onclick="rotateCrop(90)" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6-6m6 6l-6 6"></path>
                        </svg>
                    </button>
                    <button type="button" onclick="flipCrop('horizontal')" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        ↔️
                    </button>
                    <button type="button" onclick="flipCrop('vertical')" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        ↕️
                    </button>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeCropModal()" class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="button" onclick="applyCrop()" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition">
                        Aplicar Crop
                    </button>
                </div>
            </div>
        </div>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estoque</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consignado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disponível</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($produtos as $produto): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <!-- Foto do produto -->
                                        <div class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                            <?php if ($produto['foto']): ?>
                                                <img src="<?php echo ImageUploader::getImageUrl($produto['foto']); ?>" alt="<?php echo sanitize($produto['nome']); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
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
                                    <span class="text-sm font-medium <?php echo $produto['estoque_disponivel'] < $produto['estoque_minimo'] ? 'text-red-600' : 'text-green-600'; ?>">
                                        <?php echo $produto['estoque_disponivel']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($produto['ativo']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Ativo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Inativo
                                        </span>
                                    <?php endif; ?>
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
                        <div class="flex gap-3 mb-3">
                            <!-- Foto do produto -->
                            <div class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                <?php if ($produto['foto']): ?>
                                    <img src="<?php echo ImageUploader::getImageUrl($produto['foto']); ?>" alt="<?php echo sanitize($produto['nome']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-medium text-gray-900"><?php echo sanitize($produto['nome']); ?></h3>
                                    <?php if ($produto['ativo']): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Ativo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            Inativo
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($produto['descricao'])): ?>
                                    <p class="text-sm text-gray-500"><?php echo sanitize($produto['descricao']); ?></p>
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

<!-- Cropper.js CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">

<!-- Cropper.js JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<script>
let cropper = null;
let currentFile = null;

// Abrir modal de crop
function openCropModal(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validar tamanho (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Arquivo muito grande! Máximo 5MB.');
            input.value = '';
            return;
        }
        
        // Validar tipo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('Tipo de arquivo não permitido! Use: JPEG, PNG, WEBP ou GIF');
            input.value = '';
            return;
        }
        
        currentFile = file;
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const cropImage = document.getElementById('cropImage');
            cropImage.src = e.target.result;
            
            // Destruir cropper anterior se existir
            if (cropper) {
                cropper.destroy();
            }
            
            // Inicializar Cropper.js
            cropper = new Cropper(cropImage, {
                aspectRatio: 1, // Quadrado 1:1
                viewMode: 2,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: true,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                minCropBoxWidth: 100,
                minCropBoxHeight: 100,
            });
            
            // Mostrar modal
            document.getElementById('cropModal').classList.remove('hidden');
        };
        
        reader.readAsDataURL(file);
    }
}

// Fechar modal
function closeCropModal() {
    document.getElementById('cropModal').classList.add('hidden');
    document.getElementById('fotoInput').value = '';
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
}

// Rotacionar imagem
function rotateCrop(degrees) {
    if (cropper) {
        cropper.rotate(degrees);
    }
}

// Espelhar imagem
function flipCrop(direction) {
    if (cropper) {
        if (direction === 'horizontal') {
            cropper.scaleX(cropper.getData().scaleX === -1 ? 1 : -1);
        } else {
            cropper.scaleY(cropper.getData().scaleY === -1 ? 1 : -1);
        }
    }
}

// Aplicar crop
function applyCrop() {
    if (!cropper) return;
    
    // Obter canvas cropado em 500x500
    const canvas = cropper.getCroppedCanvas({
        width: 500,
        height: 500,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });
    
    // Converter para blob
    canvas.toBlob(function(blob) {
        // Criar FormData para enviar
        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        
        // Atualizar preview
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '<img src="' + dataUrl + '" alt="Preview" class="w-full h-full object-cover">';
        
        // Salvar no campo hidden
        document.getElementById('fotoCropped').value = dataUrl;
        
        // Fechar modal
        closeCropModal();
    }, 'image/jpeg', 0.85);
}

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
