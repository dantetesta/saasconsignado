<?php
/**
 * Atualização de Consignação (Vendas e Devoluções)
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

// Buscar dados da consignação
$stmt = $db->prepare("
    SELECT 
        c.*,
        e.nome as estabelecimento
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

// Nota: Consignações contínuas são redirecionadas antes do header em consignacoes.php

// Buscar itens da consignação
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

// Se não houver itens, mostrar aviso
if (empty($itens)) {
    setFlashMessage('warning', 'Esta consignação não possui produtos cadastrados. Adicione produtos primeiro.');
    redirect('/consignacoes.php?action=view&id=' . $consignacaoId);
}
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
        <a href="/consignacoes.php?action=view&id=<?php echo $consignacao['id']; ?>" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Atualizar Consignação #<?php echo $consignacao['id']; ?></h1>
            <p class="text-gray-600 mt-1"><?php echo sanitize($consignacao['estabelecimento']); ?> - <?php echo formatDate($consignacao['data_consignacao']); ?></p>
        </div>
    </div>
</div>

<form method="POST" action="<?php echo url('/consignacoes.php'); ?>">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="consignacao_id" value="<?php echo $consignacao['id']; ?>">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-200">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="font-semibold text-blue-900 mb-2">💡 Como funciona a atualização</h3>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>✅ <strong>Não precisa declarar tudo de uma vez!</strong> Atualize conforme as vendas acontecem.</li>
                        <li>✅ <strong>O que sobrar fica consignado</strong> e pode ser atualizado depois.</li>
                        <li>✅ <strong>Você pode fazer vários pagamentos</strong> em datas diferentes.</li>
                        <li>⚠️ <strong>Vendido + Devolvido</strong> não pode ultrapassar o total consignado.</li>
                    </ul>
                </div>
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
                    <?php foreach ($itens as $index => $item): ?>
                        <tr class="hover:bg-gray-50 item-row" data-index="<?php echo $index; ?>">
                            <input type="hidden" name="itens[]" value="<?php echo $item['id']; ?>">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo sanitize($item['produto']); ?></td>
                            <td class="px-6 py-4 text-sm text-center">
                                <span class="font-semibold text-gray-900 consignado-<?php echo $index; ?>"><?php echo $item['quantidade_consignada']; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <input 
                                    type="number" 
                                    name="vendidos[]" 
                                    min="0"
                                    max="<?php echo $item['quantidade_consignada']; ?>"
                                    value="<?php echo $item['quantidade_vendida']; ?>"
                                    class="w-20 px-3 py-2 border border-green-300 bg-green-50 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-center font-semibold vendido-input"
                                    data-index="<?php echo $index; ?>"
                                    data-consignado="<?php echo $item['quantidade_consignada']; ?>"
                                    data-ja-vendido="<?php echo $item['quantidade_vendida']; ?>"
                                    data-ja-devolvido="<?php echo $item['quantidade_devolvida']; ?>"
                                    onchange="calcularPendente(<?php echo $index; ?>)"
                                    placeholder="0"
                                >
                            </td>
                            <td class="px-6 py-4">
                                <input 
                                    type="number" 
                                    name="devolvidos[]" 
                                    min="0"
                                    max="<?php echo $item['quantidade_consignada']; ?>"
                                    value="<?php echo $item['quantidade_devolvida']; ?>"
                                    class="w-20 px-3 py-2 border border-red-300 bg-red-50 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-center font-semibold devolvido-input"
                                    data-index="<?php echo $index; ?>"
                                    data-consignado="<?php echo $item['quantidade_consignada']; ?>"
                                    data-ja-vendido="<?php echo $item['quantidade_vendida']; ?>"
                                    data-ja-devolvido="<?php echo $item['quantidade_devolvida']; ?>"
                                    onchange="calcularPendente(<?php echo $index; ?>)"
                                    placeholder="0"
                                >
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <span class="font-semibold pendente-<?php echo $index; ?>">
                                    <?php echo $item['quantidade_consignada'] - $item['quantidade_vendida'] - $item['quantidade_devolvida']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900"><?php echo formatMoney($item['preco_unitario']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden divide-y divide-gray-200">
            <?php foreach ($itens as $index => $item): ?>
                <div class="p-4 item-row-mobile" data-index="<?php echo $index; ?>">
                    <h3 class="font-medium text-gray-900 mb-4"><?php echo sanitize($item['produto']); ?></h3>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Consignado</p>
                            <p class="text-lg font-semibold text-gray-900 consignado-<?php echo $index; ?>"><?php echo $item['quantidade_consignada']; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Valor Unitário</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo formatMoney($item['preco_unitario']); ?></p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Vendido</label>
                            <input 
                                type="number" 
                                min="0"
                                max="<?php echo $item['quantidade_consignada']; ?>"
                                value="<?php echo $item['quantidade_vendida']; ?>"
                                class="w-full px-3 py-2 border border-green-300 bg-green-50 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent font-semibold vendido-input-mobile"
                                data-index="<?php echo $index; ?>"
                                data-consignado="<?php echo $item['quantidade_consignada']; ?>"
                                data-item-id="<?php echo $item['id']; ?>"
                                onchange="sincronizarCampos(<?php echo $index; ?>, 'vendido', this.value)"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Devolvido</label>
                            <input 
                                type="number" 
                                min="0"
                                max="<?php echo $item['quantidade_consignada']; ?>"
                                value="<?php echo $item['quantidade_devolvida']; ?>"
                                class="w-full px-3 py-2 border border-red-300 bg-red-50 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent font-semibold devolvido-input-mobile"
                                data-index="<?php echo $index; ?>"
                                data-consignado="<?php echo $item['quantidade_consignada']; ?>"
                                data-item-id="<?php echo $item['id']; ?>"
                                onchange="sincronizarCampos(<?php echo $index; ?>, 'devolvido', this.value)"
                            >
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-sm text-gray-500">Pendente</p>
                        <p class="text-xl font-bold text-gray-900 pendente-<?php echo $index; ?>">
                            <?php echo $item['quantidade_consignada'] - $item['quantidade_vendida'] - $item['quantidade_devolvida']; ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Card de Resumo -->
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border-2 border-blue-200 mb-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">📊 Resumo da Atualização</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Total Vendido</p>
                        <p class="text-2xl font-bold text-green-600" id="resumo-vendido">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Total Devolvido</p>
                        <p class="text-2xl font-bold text-red-600" id="resumo-devolvido">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Ainda Consignado</p>
                        <p class="text-2xl font-bold" id="resumo-consignado">0</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4 p-3 bg-blue-100 rounded-lg">
            <p class="text-sm text-blue-800">
                <strong>💡 Dica:</strong> O que ficar "Ainda Consignado" pode ser atualizado em outra data. Você pode fazer vários pagamentos conforme receber!
            </p>
        </div>
    </div>

    <!-- Botões -->
    <div class="flex flex-col sm:flex-row gap-4 justify-end">
        <a href="/consignacoes.php?action=view&id=<?php echo $consignacao['id']; ?>" class="px-6 py-3 border border-gray-300 text-gray-700 text-center font-medium rounded-lg hover:bg-gray-50 transition">
            Cancelar
        </a>
        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-emerald-700 transition shadow-md">
            💾 Salvar Alterações
        </button>
    </div>
</form>

<script>
// Sincronizar campos entre desktop e mobile
function sincronizarCampos(index, tipo, valor) {
    // Atualizar campo desktop correspondente
    const rows = document.querySelectorAll('.item-row');
    const row = rows[index];
    
    if (row) {
        if (tipo === 'vendido') {
            const inputDesktop = row.querySelector('.vendido-input');
            if (inputDesktop) inputDesktop.value = valor;
        } else if (tipo === 'devolvido') {
            const inputDesktop = row.querySelector('.devolvido-input');
            if (inputDesktop) inputDesktop.value = valor;
        }
    }
    
    // Recalcular pendente
    calcularPendente(index);
}

function calcularPendente(index) {
    const row = document.querySelector(`.item-row[data-index="${index}"]`);
    const vendidoInput = row.querySelector('.vendido-input');
    const devolvidoInput = row.querySelector('.devolvido-input');
    const consignado = parseInt(vendidoInput.dataset.consignado);
    
    let vendido = parseInt(vendidoInput.value) || 0;
    let devolvido = parseInt(devolvidoInput.value) || 0;
    
    // Validar se a soma não ultrapassa o consignado
    const total = vendido + devolvido;
    if (total > consignado) {
        // Mostrar alerta mais amigável
        const pendente = consignado - total;
        alert(`⚠️ Atenção!\n\nConsignado: ${consignado}\nVendido: ${vendido}\nDevolvido: ${devolvido}\nTotal: ${total}\n\nVocê está tentando declarar ${Math.abs(pendente)} unidade(s) a mais do que foi consignado!\n\nAjuste os valores para continuar.`);
        
        // Ajustar valores automaticamente
        if (vendido > consignado) {
            vendido = consignado;
            vendidoInput.value = vendido;
            devolvido = 0;
            devolvidoInput.value = 0;
        } else {
            const maxDevolvido = consignado - vendido;
            devolvido = maxDevolvido;
            devolvidoInput.value = devolvido;
        }
    }
    
    const aindaConsignado = consignado - vendido - devolvido;
    
    // Atualizar exibição do ainda consignado
    const pendenteElements = document.querySelectorAll(`.pendente-${index}`);
    pendenteElements.forEach(el => {
        el.textContent = aindaConsignado;
        
        // Atualizar cor e estilo baseado no valor
        el.classList.remove('text-gray-900', 'text-yellow-600', 'text-green-600', 'font-bold');
        if (aindaConsignado > 0) {
            el.classList.add('text-yellow-600', 'font-bold');
        } else {
            el.classList.add('text-green-600', 'font-bold');
        }
    });
    
    // Atualizar resumo geral
    atualizarResumoGeral();
}

function atualizarResumoGeral() {
    let totalVendido = 0;
    let totalDevolvido = 0;
    let totalConsignado = 0;
    
    document.querySelectorAll('.item-row').forEach(row => {
        const vendidoInput = row.querySelector('.vendido-input');
        const devolvidoInput = row.querySelector('.devolvido-input');
        const consignado = parseInt(vendidoInput.dataset.consignado);
        
        totalConsignado += consignado;
        totalVendido += parseInt(vendidoInput.value) || 0;
        totalDevolvido += parseInt(devolvidoInput.value) || 0;
    });
    
    const totalAindaConsignado = totalConsignado - totalVendido - totalDevolvido;
    
    // Atualizar elementos do resumo se existirem
    const resumoVendido = document.getElementById('resumo-vendido');
    const resumoDevolvido = document.getElementById('resumo-devolvido');
    const resumoConsignado = document.getElementById('resumo-consignado');
    
    if (resumoVendido) resumoVendido.textContent = totalVendido;
    if (resumoDevolvido) resumoDevolvido.textContent = totalDevolvido;
    if (resumoConsignado) {
        resumoConsignado.textContent = totalAindaConsignado;
        resumoConsignado.classList.remove('text-gray-900', 'text-yellow-600', 'text-green-600');
        if (totalAindaConsignado > 0) {
            resumoConsignado.classList.add('text-yellow-600');
        } else {
            resumoConsignado.classList.add('text-green-600');
        }
    }
}

// Calcular todos os pendentes ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach((row, index) => {
        calcularPendente(index);
    });
    
    // Validação e confirmação do formulário
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('📤 Formulário sendo enviado...');
            
            const vendidos = document.querySelectorAll('input[name="vendidos[]"]');
            const devolvidos = document.querySelectorAll('input[name="devolvidos[]"]');
            
            // Calcular totais para confirmação
            let totalVendido = 0, totalDevolvido = 0;
            vendidos.forEach(input => totalVendido += parseInt(input.value) || 0);
            devolvidos.forEach(input => totalDevolvido += parseInt(input.value) || 0);
            
            console.log('Total vendido:', totalVendido);
            console.log('Total devolvido:', totalDevolvido);
            
            // Confirmação antes de enviar
            const confirmMsg = `✅ Confirmar atualização?\n\nTotal Vendido: ${totalVendido}\nTotal Devolvido: ${totalDevolvido}\n\nDeseja salvar estas alterações?`;
            if (!confirm(confirmMsg)) {
                console.log('❌ Usuário cancelou');
                e.preventDefault();
                return false;
            }
            
            console.log('✓ Confirmado! Enviando...');
            
            // Mostrar loading
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.textContent;
                submitBtn.textContent = '💾 Salvando...';
                submitBtn.disabled = true;
                
                // Restaurar botão em caso de erro (timeout)
                setTimeout(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            }
            
            return true;
        });
    }
});
</script>
