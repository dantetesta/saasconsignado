<!-- CTA Final Section -->
<section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-blue-600 to-emerald-600">
    <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-6">
            Pronto para Transformar sua Gestão de Consignações?
        </h2>
        <p class="text-xl text-blue-100 mb-8 leading-relaxed">
            <?php
            require_once __DIR__ . '/../../includes/system_branding.php';
            $systemName = SystemBranding::getSystemName();
            ?>
            Junte-se a centenas de empresas que já simplificaram sua gestão com o <?php echo htmlspecialchars($systemName); ?>
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
            <a href="/register" class="px-8 py-4 bg-white text-blue-600 font-bold rounded-lg hover:shadow-2xl transition transform hover:scale-105 text-center">
                🚀 Começar Gratuitamente
            </a>
            <a href="/login" class="px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-lg hover:bg-white/20 transition border-2 border-white/30 text-center">
                Já tenho conta
            </a>
        </div>
        
        <p class="text-sm text-blue-100">
            ✓ Sem cartão de crédito &nbsp;&nbsp;•&nbsp;&nbsp; ✓ Cancele quando quiser &nbsp;&nbsp;•&nbsp;&nbsp; ✓ Suporte em português
        </p>
    </div>
</section>
