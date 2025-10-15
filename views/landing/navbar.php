<!-- Navbar -->
<nav class="fixed top-0 w-full bg-white/95 backdrop-blur-sm shadow-sm z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <?php
            require_once __DIR__ . '/../../includes/system_branding.php';
            echo SystemBranding::renderBrand();
            ?>
            
            <!-- Menu Desktop -->
            <div class="hidden md:flex items-center gap-8">
                <a href="#recursos" class="text-gray-600 hover:text-blue-600 transition font-medium">Recursos</a>
                <a href="#como-funciona" class="text-gray-600 hover:text-blue-600 transition font-medium">Como Funciona</a>
                <a href="#precos" class="text-gray-600 hover:text-blue-600 transition font-medium">Preços</a>
                <a href="/login" class="text-gray-600 hover:text-blue-600 transition font-medium">Entrar</a>
                <a href="/register" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-semibold rounded-lg hover:shadow-lg transition transform hover:scale-105">
                    Começar Grátis
                </a>
            </div>
            
            <!-- Menu Mobile Button -->
            <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Menu Mobile Dropdown -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200">
        <div class="px-4 py-4 space-y-3">
            <a href="#recursos" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">Recursos</a>
            <a href="#como-funciona" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">Como Funciona</a>
            <a href="#precos" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">Preços</a>
            <a href="/login" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">Entrar</a>
            <a href="/register" class="block px-4 py-2 bg-gradient-to-r from-blue-600 to-emerald-600 text-white text-center font-semibold rounded-lg">
                Começar Grátis
            </a>
        </div>
    </div>
</nav>
