<!-- Recursos Section -->
<section id="recursos" class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4">
                Recursos Poderosos para Seu Negócio
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Tudo que você precisa para gerenciar suas consignações de forma profissional
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $recursos = [
                ['icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', 'color' => 'blue', 'titulo' => 'Gestão de Consignações', 'desc' => 'Controle completo de produtos consignados, entregas, vendas e devoluções. Consignações pontuais ou contínuas.'],
                ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'color' => 'emerald', 'titulo' => 'Estabelecimentos', 'desc' => 'Cadastre estabelecimentos, gere links de consulta pública e permita que clientes acompanhem suas consignações.'],
                ['icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'cyan', 'titulo' => 'Controle de Estoque', 'desc' => 'Gerencie produtos, preços, estoque disponível e consignado. Atualizações em tempo real.'],
                ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'purple', 'titulo' => 'Controle Financeiro', 'desc' => 'Registre pagamentos, acompanhe saldos pendentes e mantenha o controle financeiro completo.'],
                ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'pink', 'titulo' => 'Relatórios Detalhados', 'desc' => 'Visualize relatórios completos de vendas, devoluções, produtos mais vendidos e muito mais.'],
                ['icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'color' => 'orange', 'titulo' => 'Acesso Mobile', 'desc' => 'Interface responsiva que funciona perfeitamente em qualquer dispositivo. Gerencie de onde estiver.']
            ];
            
            foreach ($recursos as $r):
            ?>
            <div class="bg-white rounded-xl p-8 shadow-sm hover:shadow-xl transition border border-gray-100">
                <div class="w-14 h-14 bg-gradient-to-br from-<?php echo $r['color']; ?>-500 to-<?php echo $r['color']; ?>-600 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $r['icon']; ?>"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3"><?php echo $r['titulo']; ?></h3>
                <p class="text-gray-600 leading-relaxed"><?php echo $r['desc']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
