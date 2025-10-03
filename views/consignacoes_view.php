<?php
/**
 * Visualização de Consignação
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

// Buscar dados da consignação
$stmt = $db->prepare("
    SELECT 
        c.*,
        e.nome as estabelecimento,
        e.responsavel,
        e.telefone,
        e.endereco
    FROM consignacoes c
    INNER JOIN estabelecimentos e ON c.estabelecimento_id = e.id
    WHERE c.id = ?
");
$stmt->execute([$consignacaoId]);
$consignacao = $stmt->fetch();

if (!$consignacao) {
    setFlashMessage('error', 'Consignação não encontrada.');
    redirect('/consignacoes.php');
}

// Buscar itens da consignação
if ($consignacao['tipo'] === 'continua') {
    // Para consignações contínuas, buscar todos os produtos com movimentação
    $stmt = $db->prepare("
        SELECT 
            m.produto_id,
            p.nome as produto,
            COALESCE(SUM(CASE WHEN m.tipo = 'entrega' THEN m.quantidade ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN m.tipo = 'devolucao' THEN m.quantidade ELSE 0 END), 0) as quantidade_consignada,
            COALESCE(SUM(CASE WHEN m.tipo = 'venda' THEN m.quantidade ELSE 0 END), 0) as quantidade_vendida,
            COALESCE(SUM(CASE WHEN m.tipo = 'devolucao' THEN m.quantidade ELSE 0 END), 0) as quantidade_devolvida,
            MAX(m.preco_unitario) as preco_unitario,
            0 as quantidade_inicial
        FROM movimentacoes_consignacao m
        INNER JOIN produtos p ON m.produto_id = p.id
        WHERE m.consignacao_id = ?
        GROUP BY m.produto_id, p.nome
        ORDER BY p.nome
    ");
    $stmt->execute([$consignacaoId]);
    $itens = $stmt->fetchAll();
} else {
    // Para consignações pontuais, usar tabela normal
    $stmt = $db->prepare("
        SELECT 
            ci.*,
            p.nome as produto
        FROM consignacao_itens ci
        INNER JOIN produtos p ON ci.produto_id = p.id
        WHERE ci.consignacao_id = ?
        ORDER BY p.nome
    ");
    $stmt->execute([$consignacaoId]);
    $itens = $stmt->fetchAll();
}

// Buscar pagamentos
$stmt = $db->prepare("
    SELECT * FROM pagamentos 
    WHERE consignacao_id = ? 
    ORDER BY data_pagamento DESC
");
$stmt->execute([$consignacaoId]);
$pagamentos = $stmt->fetchAll();

// Buscar movimentações (se for consignação contínua)
$movimentacoes = [];
if ($consignacao['tipo'] === 'continua') {
    $stmt = $db->prepare("
        SELECT m.*, p.nome as produto
        FROM movimentacoes_consignacao m
        INNER JOIN produtos p ON m.produto_id = p.id
        WHERE m.consignacao_id = ?
        ORDER BY m.data_movimentacao DESC, m.criado_em DESC
        LIMIT 20
    ");
    $stmt->execute([$consignacaoId]);
    $movimentacoes = $stmt->fetchAll();
}

// Calcular totais
if ($consignacao['tipo'] === 'continua') {
    // Para contínuas: calcular diretamente das movimentações
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN tipo = 'entrega' THEN quantidade ELSE 0 END), 0) as total_entregue,
            COALESCE(SUM(CASE WHEN tipo = 'venda' THEN quantidade ELSE 0 END), 0) as total_vendido,
            COALESCE(SUM(CASE WHEN tipo = 'devolucao' THEN quantidade ELSE 0 END), 0) as total_devolvido
        FROM movimentacoes_consignacao
        WHERE consignacao_id = ?
    ");
    $stmt->execute([$consignacaoId]);
    $totais = $stmt->fetch();
    
    $total_consignado = $totais['total_entregue'] - $totais['total_devolvido']; // Saldo atual em estoque
    $total_vendido = $totais['total_vendido'];
    $total_devolvido = $totais['total_devolvido'];
    $total_pendente = $total_consignado; // O que ainda está no estabelecimento
} else {
    // Para pontuais: cálculo normal
    $total_consignado = array_sum(array_column($itens, 'quantidade_consignada'));
    $total_vendido = array_sum(array_column($itens, 'quantidade_vendida'));
    $total_devolvido = array_sum(array_column($itens, 'quantidade_devolvida'));
    $total_pendente = $total_consignado - $total_vendido - $total_devolvido;
}

// Calcular valor total vendido
$valor_total = 0;
foreach ($itens as $item) {
    $valor_total += $item['quantidade_vendida'] * $item['preco_unitario'];
}

// Calcular saldo pendente (nunca negativo)
$valor_pago = array_sum(array_column($pagamentos, 'valor_pago'));
$saldo_pendente = max(0, $valor_total - $valor_pago);
?>

<!-- Page Header -->
<div class="mb-8">
    <!-- Mobile Layout -->
    <div class="md:hidden">
        <div class="flex items-center gap-3 mb-3">
            <a href="/consignacoes.php" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Consignação #<?php echo $consignacao['id']; ?></h1>
        </div>
        
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <?php if ($consignacao['tipo'] === 'continua'): ?>
                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 border border-green-300">
                        🔄 Contínua
                    </span>
                <?php else: ?>
                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 border border-blue-300">
                        📦 Pontual
                    </span>
                <?php endif; ?>
                <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo getStatusBadgeClass($consignacao['status']); ?>">
                    <?php echo translateStatus($consignacao['status']); ?>
                </span>
            </div>
            <button 
                onclick="toggleLinkPublico()" 
                class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" 
                title="Link Público"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900"><?php echo sanitize($consignacao['estabelecimento']); ?></h2>
            <?php if ($consignacao['tipo'] === 'continua' && $consignacao['status'] !== 'cancelada'): ?>
                <div class="flex gap-1.5">
                    <a href="/movimentacoes.php?consignacao_id=<?php echo $consignacao['id']; ?>&tipo=entrega" class="p-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition" title="Adicionar Produtos">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </a>
                    <a href="/movimentacoes.php?consignacao_id=<?php echo $consignacao['id']; ?>&tipo=venda" class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition" title="Registrar Venda">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </a>
                    <a href="/movimentacoes.php?consignacao_id=<?php echo $consignacao['id']; ?>&tipo=devolucao" class="p-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition" title="Registrar Devolução">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                    </a>
                </div>
            <?php elseif ($consignacao['tipo'] === 'pontual' && $consignacao['status'] !== 'finalizada' && $consignacao['status'] !== 'cancelada'): ?>
                <a href="/consignacoes.php?action=update&id=<?php echo $consignacao['id']; ?>" class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition" title="Atualizar Vendas">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Desktop Layout -->
    <div class="hidden md:block mb-4">
        <div class="flex items-center gap-4 mb-3">
            <a href="/consignacoes.php" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div class="flex-1">
                <div class="flex items-center gap-3 flex-wrap">
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
            </div>
            <div class="flex items-center gap-2">
                <!-- Botão Link Público -->
                <button 
                    onclick="toggleLinkPublico()" 
                    class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" 
                    title="Link Público"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                </button>
                <span class="px-4 py-2 text-sm font-medium rounded-full <?php echo getStatusBadgeClass($consignacao['status']); ?>">
                    <?php echo translateStatus($consignacao['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="flex items-center justify-between ml-10">
            <h2 class="text-xl font-semibold text-gray-800"><?php echo sanitize($consignacao['estabelecimento']); ?></h2>
            <?php if ($consignacao['tipo'] === 'continua' && $consignacao['status'] !== 'cancelada'): ?>
                <div class="flex gap-2">
                    <a href="/movimentacoes.php?consignacao_id=<?php echo $consignacao['id']; ?>&tipo=entrega" class="flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition" title="Adicionar Produtos">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Adicionar</span>
                    </a>
                    <a href="/movimentacoes.php?consignacao_id=<?php echo $consignacao['id']; ?>&tipo=venda" class="flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition" title="Registrar Venda">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Venda</span>
                    </a>
                    <a href="/movimentacoes.php?consignacao_id=<?php echo $consignacao['id']; ?>&tipo=devolucao" class="flex items-center gap-1.5 px-3 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition" title="Registrar Devolução">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        <span>Devolução</span>
                    </a>
                </div>
            <?php elseif ($consignacao['tipo'] === 'pontual' && $consignacao['status'] !== 'finalizada' && $consignacao['status'] !== 'cancelada'): ?>
                <a href="/consignacoes.php?action=update&id=<?php echo $consignacao['id']; ?>" class="flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    <span>Atualizar Vendas</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Off-canvas Link Público -->
    <?php
    // Buscar token do estabelecimento
    $stmt_token = $db->prepare("SELECT token_acesso, senha_acesso FROM estabelecimentos WHERE id = ?");
    $stmt_token->execute([$consignacao['estabelecimento_id']]);
    $estab_token = $stmt_token->fetch();
    
    if ($estab_token && $estab_token['token_acesso']):
        $link_publico = SITE_URL . "/consulta_publica.php?token=" . $estab_token['token_acesso'] . "&id=" . $consignacao['id'];
    ?>
    <!-- Overlay -->
    <div id="linkPublicoOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40" onclick="toggleLinkPublico()"></div>
    
    <!-- Off-canvas (desliza de cima) -->
    <div id="linkPublicoOffcanvas" class="fixed top-0 left-0 right-0 bg-gradient-to-r from-purple-50 to-pink-50 border-b-2 border-purple-200 p-6 transform -translate-y-full transition-transform duration-300 ease-in-out z-50 shadow-xl">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900 mb-2">🔗 Link Público de Consulta</h3>
                <p class="text-sm text-gray-600 mb-3">
                    Compartilhe este link com o estabelecimento para que ele possa acompanhar esta consignação em tempo real.
                    <?php if (empty($estab_token['senha_acesso'])): ?>
                        <span class="text-orange-600 font-medium">⚠️ Configure uma senha de acesso no cadastro do estabelecimento primeiro!</span>
                    <?php endif; ?>
                </p>
                <div class="space-y-3">
                    <!-- Link -->
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="link-publico" 
                            value="<?php echo $link_publico; ?>" 
                            readonly
                            class="flex-1 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-mono"
                        >
                        <button 
                            onclick="copiarLink(event)" 
                            class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            <span class="hidden sm:inline">Copiar</span>
                        </button>
                    </div>
                    
                    <!-- Botões de Compartilhamento -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <button 
                            onclick="enviarWhatsApp()" 
                            type="button"
                            class="px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <span>WhatsApp</span>
                        </button>
                        
                        <button 
                            onclick="enviarEmail()" 
                            type="button"
                            class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span>Enviar Email</span>
                        </button>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    💡 O estabelecimento precisará da senha de acesso configurada para visualizar.
                </p>
            </div>
        </div>
        
        <!-- Botão Fechar -->
        <button 
            onclick="toggleLinkPublico()" 
            class="absolute top-4 right-4 p-2 text-gray-500 hover:text-gray-700 hover:bg-white rounded-lg transition-colors"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    <?php endif; ?>
    
    <script>
    function toggleLinkPublico() {
        const offcanvas = document.getElementById('linkPublicoOffcanvas');
        const overlay = document.getElementById('linkPublicoOverlay');
        
        if (offcanvas.classList.contains('-translate-y-full')) {
            // Abrir
            offcanvas.classList.remove('-translate-y-full');
            offcanvas.classList.add('translate-y-0');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            // Fechar
            offcanvas.classList.add('-translate-y-full');
            offcanvas.classList.remove('translate-y-0');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
    
    function copiarLink(event) {
        event.preventDefault();
        const input = document.getElementById('link-publico');
        
        // Tentar usar a API moderna primeiro
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(() => {
                mostrarFeedback(event.target, 'Copiado!', 'green');
            }).catch(err => {
                // Fallback para método antigo
                copiarLinkFallback(input, event.target);
            });
        } else {
            // Fallback para navegadores antigos
            copiarLinkFallback(input, event.target);
        }
    }
    
    function copiarLinkFallback(input, btn) {
        input.select();
        input.setSelectionRange(0, 99999);
        
        try {
            document.execCommand('copy');
            mostrarFeedback(btn, 'Copiado!', 'green');
        } catch (err) {
            alert('Erro ao copiar. Selecione e copie manualmente (Ctrl+C).');
        }
    }
    
    function mostrarFeedback(element, texto, cor) {
        const btn = element.closest('button');
        const originalHTML = btn.innerHTML;
        const originalClasses = btn.className;
        
        btn.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="hidden sm:inline">${texto}</span>
        `;
        
        if (cor === 'green') {
            // Remover todas as classes de gradiente e adicionar verde sólido
            btn.classList.remove('bg-gradient-to-r', 'from-purple-600', 'to-pink-600', 'hover:from-purple-700', 'hover:to-pink-700');
            btn.classList.add('bg-green-600', 'hover:bg-green-700');
        }
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.className = originalClasses; // Restaurar todas as classes originais
        }, 2000);
    }
    
    function enviarWhatsApp() {
        const estabelecimento = "<?php echo addslashes($consignacao['estabelecimento']); ?>";
        const consignacaoId = "<?php echo $consignacao['id']; ?>";
        const dataConsignacao = "<?php echo formatDate($consignacao['data_consignacao']); ?>";
        const link = document.getElementById('link-publico').value;
        
        // Número de WhatsApp do estabelecimento
        const whatsappNumero = "<?php 
            // Buscar WhatsApp do estabelecimento
            $stmt_whats = $db->prepare("SELECT whatsapp FROM estabelecimentos WHERE id = ?");
            $stmt_whats->execute([$consignacao['estabelecimento_id']]);
            $whats_data = $stmt_whats->fetch();
            echo $whats_data && !empty($whats_data['whatsapp']) ? preg_replace('/\D/', '', $whats_data['whatsapp']) : '';
        ?>";
        
        // Montar mensagem enxuta
        let mensagem = `🍿 *Nova Consignação #${consignacaoId}*\n\n`;
        mensagem += `📍 ${estabelecimento}\n`;
        mensagem += `📅 ${dataConsignacao}\n\n`;
        mensagem += `*Produtos:*\n`;
        
        <?php foreach ($itens as $item): ?>
        mensagem += `• <?php echo addslashes($item['produto']); ?>: <?php echo $item['quantidade_consignada']; ?> un\n`;
        <?php endforeach; ?>
        
        mensagem += `\n💰 *Total:* <?php echo formatMoney($valor_total); ?>\n\n`;
        mensagem += `🔗 *Acompanhe em tempo real:*\n${link}\n\n`;
        mensagem += `🔐 Use sua senha de acesso para visualizar.`;
        
        // Abrir WhatsApp com número específico ou genérico
        let whatsappUrl;
        if (whatsappNumero && whatsappNumero.length >= 10) {
            // Adicionar DDI +55 se não tiver
            const numeroCompleto = whatsappNumero.startsWith('55') ? whatsappNumero : '55' + whatsappNumero;
            whatsappUrl = `https://wa.me/${numeroCompleto}?text=${encodeURIComponent(mensagem)}`;
        } else {
            // Se não tiver WhatsApp cadastrado, abre sem número
            whatsappUrl = `https://wa.me/?text=${encodeURIComponent(mensagem)}`;
            alert('⚠️ Este estabelecimento não possui WhatsApp cadastrado.\nVocê pode escolher o contato manualmente.');
        }
        
        window.open(whatsappUrl, '_blank');
    }
    
    function enviarEmail() {
        // Redirecionar para página de envio de email
        window.location.href = '<?php echo url('/enviar_email.php?consignacao_id=' . $consignacao['id']); ?>';
    }
    </script>
</div>

<div class="w-[90%] mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Coluna Principal -->
        <div class="lg:col-span-2 space-y-6">
        <!-- Informações do Estabelecimento -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Informações do Estabelecimento</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Nome</p>
                    <p class="font-medium text-gray-900"><?php echo sanitize($consignacao['estabelecimento']); ?></p>
                </div>
                <?php if (!empty($consignacao['responsavel'])): ?>
                <div>
                    <p class="text-sm text-gray-500">Responsável</p>
                    <p class="font-medium text-gray-900"><?php echo sanitize($consignacao['responsavel']); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($consignacao['telefone'])): ?>
                <div>
                    <p class="text-sm text-gray-500">Telefone</p>
                    <p class="font-medium text-gray-900"><?php echo formatPhone($consignacao['telefone']); ?></p>
                </div>
                <?php endif; ?>
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
                    <p class="text-gray-900"><?php echo nl2br(sanitize($consignacao['observacoes'])); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Produtos Consignados -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
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
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                                <?php echo $consignacao['tipo'] === 'continua' ? 'Em Estoque' : 'Consignado'; ?>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Vendido</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Devolvido</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                                <?php echo $consignacao['tipo'] === 'continua' ? 'Saldo Atual' : 'Ainda Consignado'; ?>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Unit.</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Vendido</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($itens as $item): 
                            if ($consignacao['tipo'] === 'continua') {
                                // Para contínuas: saldo_atual já vem calculado da VIEW
                                $pendente = $item['quantidade_consignada']; // Já é o saldo atual
                            } else {
                                // Para pontuais: calcular normalmente
                                $pendente = $item['quantidade_consignada'] - $item['quantidade_vendida'] - $item['quantidade_devolvida'];
                            }
                            $total_item = $item['quantidade_vendida'] * $item['preco_unitario'];
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo sanitize($item['produto']); ?></td>
                                <td class="px-6 py-4 text-sm text-center text-gray-900"><?php echo $item['quantidade_consignada']; ?></td>
                                <td class="px-6 py-4 text-sm text-center text-green-600 font-medium"><?php echo $item['quantidade_vendida']; ?></td>
                                <td class="px-6 py-4 text-sm text-center text-blue-600"><?php echo $item['quantidade_devolvida']; ?></td>
                                <td class="px-6 py-4 text-sm text-center <?php echo $pendente > 0 ? 'text-yellow-600 font-medium' : 'text-gray-500'; ?>">
                                    <?php echo $pendente; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900"><?php echo formatMoney($item['preco_unitario']); ?></td>
                                <td class="px-6 py-4 text-sm text-right font-medium text-gray-900"><?php echo formatMoney($total_item); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden divide-y divide-gray-200">
                <?php foreach ($itens as $item): 
                    $pendente = $item['quantidade_consignada'] - $item['quantidade_vendida'] - $item['quantidade_devolvida'];
                    $total_item = $item['quantidade_vendida'] * $item['preco_unitario'];
                ?>
                    <div class="p-4">
                        <h3 class="font-medium text-gray-900 mb-3"><?php echo sanitize($item['produto']); ?></h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-gray-500">Consignado</p>
                                <p class="font-medium text-gray-900"><?php echo $item['quantidade_consignada']; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Vendido</p>
                                <p class="font-medium text-green-600"><?php echo $item['quantidade_vendida']; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Devolvido</p>
                                <p class="font-medium text-blue-600"><?php echo $item['quantidade_devolvida']; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Pendente</p>
                                <p class="font-medium <?php echo $pendente > 0 ? 'text-yellow-600' : 'text-gray-500'; ?>">
                                    <?php echo $pendente; ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500">Valor Unitário</p>
                                <p class="font-medium text-gray-900"><?php echo formatMoney($item['preco_unitario']); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Total</p>
                                <p class="font-medium text-gray-900"><?php echo formatMoney($total_item); ?></p>
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
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">📋 Histórico de Movimentações</h2>
                    <a href="/movimentacoes.php?consignacao_id=<?php echo $consignacao['id']; ?>" class="text-sm text-green-600 hover:text-green-700 font-medium">
                        Ver todas →
                    </a>
                </div>
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
                        <div class="flex items-start gap-4 p-4 bg-<?php echo $config['color']; ?>-50 border-l-4 border-<?php echo $config['color']; ?>-500 rounded-lg relative group">
                            <div class="text-2xl"><?php echo $config['icon']; ?></div>
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
                                onclick="confirmarDeleteMovimentacaoView(<?php echo $mov['id']; ?>, '<?php echo addslashes($mov['produto']); ?>', '<?php echo $config['text']; ?>')" 
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
            </div>
        </div>
        <?php endif; ?>

        <!-- Histórico de Pagamentos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200" id="pagamento">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Histórico de Pagamentos</h2>
            </div>
            <div class="p-6">
                <?php if (empty($pagamentos)): ?>
                    <p class="text-center text-gray-500 py-4">Nenhum pagamento registrado</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($pagamentos as $pag): 
                            // Calcular quantos produtos esse valor representa
                            $quantidade_produtos = 0;
                            foreach ($itens as $item) {
                                if ($item['preco_unitario'] > 0) {
                                    $qtd_possivel = floor($pag['valor_pago'] / $item['preco_unitario']);
                                    if ($qtd_possivel > 0) {
                                        $quantidade_produtos += min($qtd_possivel, $item['quantidade_vendida']);
                                    }
                                }
                            }
                        ?>
                            <div class="bg-gradient-to-r from-gray-50 to-green-50 border-l-4 border-green-500 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <!-- Valor Principal -->
                                        <div class="flex items-center gap-3 mb-2">
                                            <p class="text-2xl font-bold text-gray-900"><?php echo formatMoney($pag['valor_pago']); ?></p>
                                            <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                                ✓ Pago
                                            </span>
                                        </div>
                                        
                                        <!-- Informações Detalhadas -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                                            <div class="flex items-center gap-2 text-gray-700">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <span><?php echo formatDate($pag['data_pagamento']); ?></span>
                                            </div>
                                            
                                            <div class="flex items-center gap-2 text-gray-700">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                </svg>
                                                <span class="font-medium"><?php echo ucfirst($pag['forma_pagamento']); ?></span>
                                            </div>
                                            
                                            <?php if ($valor_total > 0): ?>
                                            <div class="flex items-center gap-2 text-gray-700">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                                <span class="text-xs text-gray-600">
                                                    <?php 
                                                    $percentual = ($pag['valor_pago'] / $valor_total) * 100;
                                                    echo number_format($percentual, 1); 
                                                    ?>% do valor total vendido
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($pag['observacoes'])): ?>
                                            <div class="mt-3 p-2 bg-blue-50 rounded border-l-2 border-blue-300">
                                                <p class="text-xs text-blue-800">
                                                    <strong>Obs:</strong> <?php echo sanitize($pag['observacoes']); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Ícone de Check -->
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulário de Novo Pagamento -->
                <?php if ($saldo_pendente > 0 && $consignacao['status'] !== 'cancelada'): ?>
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="font-medium text-gray-900 mb-4">Registrar Novo Pagamento</h3>
                        <form method="POST" action="<?php echo url('/consignacoes.php'); ?>" class="space-y-4">
                            <input type="hidden" name="action" value="register_payment">
                            <input type="hidden" name="consignacao_id" value="<?php echo $consignacao['id']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Data do Pagamento *</label>
                                    <input 
                                        type="date" 
                                        name="data_pagamento" 
                                        required
                                        value="<?php echo date('Y-m-d'); ?>"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Valor (R$) *</label>
                                    <input 
                                        type="text" 
                                        id="valor_pago_display"
                                        required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        placeholder="R$ 0,00"
                                        maxlength="15"
                                    >
                                    <input type="hidden" name="valor_pago" id="valor_pago_hidden">
                                    <p class="text-xs text-gray-500 mt-1">Saldo pendente: <?php echo formatMoney($saldo_pendente); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Forma de Pagamento *</label>
                                    <select 
                                        name="forma_pagamento" 
                                        required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    >
                                        <option value="dinheiro">Dinheiro</option>
                                        <option value="pix">PIX</option>
                                        <option value="cartao_debito">Cartão de Débito</option>
                                        <option value="cartao_credito">Cartão de Crédito</option>
                                        <option value="transferencia">Transferência</option>
                                        <option value="outro">Outro</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                                    <input 
                                        type="text" 
                                        name="observacoes_pagamento"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        placeholder="Informações adicionais"
                                    >
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition">
                                Registrar Pagamento
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral - Resumo -->
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
                    <?php if ($total_pendente > 0): ?>
                        <div class="mt-2 p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-800">
                                💡 <strong>Dica:</strong> Estes itens ainda estão no estabelecimento e podem ser atualizados depois!
                            </p>
                        </div>
                    <?php endif; ?>
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

            <!-- Ações -->
            <div class="pt-6 border-t border-gray-200 space-y-2">
                <?php if ($consignacao['status'] !== 'finalizada' && $consignacao['status'] !== 'cancelada'): ?>
                    <a href="?action=update&id=<?php echo $consignacao['id']; ?>" class="block w-full px-4 py-2 bg-purple-600 text-white text-center font-medium rounded-lg hover:bg-purple-700 transition">
                        Atualizar Vendas
                    </a>
                <?php endif; ?>
                <a href="/consignacoes.php" class="block w-full px-4 py-2 border border-gray-300 text-gray-700 text-center font-medium rounded-lg hover:bg-gray-50 transition">
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Formulário oculto para deletar movimentação (na view) -->
<form id="deleteMovimentacaoViewForm" method="POST" action="<?php echo url('/consignacoes.php'); ?>" style="display: none;">
    <input type="hidden" name="action" value="delete_movimentacao_view">
    <input type="hidden" name="movimentacao_id" id="deleteMovimentacaoViewId">
    <input type="hidden" name="consignacao_id" value="<?php echo $consignacao['id']; ?>">
</form>
<script>
function confirmarDeleteMovimentacaoView(id, produto, tipo) {
    if (confirm('⚠️ Tem certeza que deseja deletar esta movimentação?\n\n' + tipo + ' de "' + produto + '"\n\nEsta ação não pode ser desfeita e afetará o estoque!')) {
        document.getElementById('deleteMovimentacaoViewId').value = id;
        document.getElementById('deleteMovimentacaoViewForm').submit();
    }
}

// Máscara de dinheiro para campo de pagamento
const valorPagoDisplay = document.getElementById('valor_pago_display');
const valorPagoHidden = document.getElementById('valor_pago_hidden');

if (valorPagoDisplay) {
    valorPagoDisplay.addEventListener('input', function(e) {
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
        valorPagoHidden.value = value.toFixed(2);
    });
    
    // Validação no submit
    valorPagoDisplay.closest('form').addEventListener('submit', function(e) {
        const valor = parseFloat(valorPagoHidden.value);
        const saldoPendente = <?php echo $saldo_pendente; ?>;
        
        if (isNaN(valor) || valor <= 0) {
            e.preventDefault();
            alert('Por favor, informe um valor válido.');
            valorPagoDisplay.focus();
            return false;
        }
        
        if (valor > saldoPendente) {
            e.preventDefault();
            alert('O valor não pode ser maior que o saldo pendente (R$ ' + saldoPendente.toFixed(2).replace('.', ',') + ').');
            valorPagoDisplay.focus();
            return false;
        }
    });
}
</script>
