<?php
/**
 * Listagem de Consignações - Consulta Pública
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.2.2
 */
?>

<!-- Header -->
<div class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Minhas Consignações</h1>
                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($estabelecimento['nome']); ?></p>
            </div>
            <a href="<?php echo defined('BASE_PATH') ? BASE_PATH : ''; ?>/consulta_publica.php?token=<?php echo $token; ?>&logout=1" class="text-sm text-gray-600 hover:text-gray-800">
                Sair
            </a>
        </div>
    </div>
</div>

<!-- Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Info Banner -->
    <div class="mb-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h3 class="font-semibold text-blue-900 mb-1">📋 Consulta em Tempo Real</h3>
                <p class="text-sm text-blue-800">
                    Aqui você pode acompanhar todas as suas consignações em tempo real. 
                    Os dados são atualizados automaticamente pelo administrador do sistema.
                </p>
            </div>
        </div>
    </div>

    <?php if (empty($consignacoes)): ?>
        <!-- Sem Consignações -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma consignação encontrada</h3>
            <p class="text-gray-600">Ainda não há consignações registradas para o seu estabelecimento.</p>
    <?php else: ?>
        <!-- Grid de Consignações -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($consignacoes as $cons): ?>
                <a href="<?php echo defined('BASE_PATH') ? BASE_PATH : ''; ?>/consulta_publica.php?token=<?php echo $token; ?>&id=<?php echo $cons['id']; ?>" 
                   class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition duration-200 group">
                    
                    <!-- Header do Card -->
                    <div class="bg-gradient-to-r from-blue-600 to-emerald-600 p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-white font-semibold text-lg">Consignação #<?php echo $cons['id']; ?></span>
                            <span class="px-3 py-1 bg-white bg-opacity-90 rounded-full text-sm font-semibold shadow-sm">
                                <?php echo translateStatus($cons['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Conteúdo do Card -->
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-sm"><?php echo formatDate($cons['data_consignacao']); ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div>
                                <p class="text-xs text-gray-500">Total de Itens</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $cons['total_itens'] ?? 0; ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Valor Total</p>
                                <p class="text-2xl font-bold text-blue-600"><?php echo formatMoney($cons['valor_total'] ?? 0); ?></p>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between text-sm text-blue-600 group-hover:text-blue-700 font-medium">
                                <span>Ver detalhes</span>
                                <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
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
        <p class="text-xs text-gray-500 mt-2">Versão 1.2.2</p>
    </div>
</div>
