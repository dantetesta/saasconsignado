<?php
/**
 * Header do Sistema
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

if (!isLoggedIn()) {
    redirect('/login.php');
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Controle de Consignados">
    <meta name="author" content="Dante Testa - https://dantetesta.com.br">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Animação suave para o menu mobile */
        @media (max-width: 768px) {
            .mobile-menu {
                transition: transform 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="w-[90%] mx-auto">
            <div class="flex justify-between items-center h-16">
                <!-- Logo e Nome -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <?php
                        // Buscar logo e nome da empresa
                        $stmt = $db->prepare("SELECT logo, nome_empresa FROM usuarios WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $empresa = $stmt->fetch();
                        ?>
                        
                        <?php if (!empty($empresa['logo'])): ?>
                            <img src="<?php echo url('/' . $empresa['logo']); ?>" alt="Logo" class="w-10 h-10 rounded-lg object-cover">
                        <?php else: ?>
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <span class="text-xl font-bold text-gray-800 hidden sm:block">
                            <?php echo sanitize($empresa['nome_empresa'] ?? 'Consignados'); ?>
                        </span>
                    </div>
                    
                    <!-- Menu Desktop -->
                    <div class="hidden md:ml-10 md:flex md:space-x-1">
                        <a href="<?php echo url('/index.php'); ?>" class="<?php echo $currentPage === 'index' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?> px-3 py-2 rounded-lg text-sm font-medium transition">
                            Dashboard
                        </a>
                        <a href="<?php echo url('/consignacoes.php'); ?>" class="<?php echo $currentPage === 'consignacoes' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?> px-3 py-2 rounded-lg text-sm font-medium transition">
                            Consignações
                        </a>
                        <a href="<?php echo url('/produtos.php'); ?>" class="<?php echo $currentPage === 'produtos' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?> px-3 py-2 rounded-lg text-sm font-medium transition">
                            Produtos
                        </a>
                        <a href="<?php echo url('/estabelecimentos.php'); ?>" class="<?php echo $currentPage === 'estabelecimentos' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?> px-3 py-2 rounded-lg text-sm font-medium transition">
                            Estabelecimentos
                        </a>
                        <a href="<?php echo url('/relatorios.php'); ?>" class="<?php echo $currentPage === 'relatorios' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?> px-3 py-2 rounded-lg text-sm font-medium transition">
                            Relatórios
                        </a>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <?php 
                    $plan_info = getPlanInfo();
                    $is_free = $plan_info && $plan_info['plano'] === 'free';
                    ?>
                    
                    <!-- Badge do Plano (Desktop) -->
                    <?php if ($is_free): ?>
                        <a href="/upgrade.php" class="hidden md:flex items-center gap-2 bg-gradient-to-r from-yellow-400 to-orange-400 text-white px-3 py-1 rounded-full text-xs font-bold hover:from-yellow-500 hover:to-orange-500 transition shadow-sm">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            FREE - Fazer Upgrade
                        </a>
                    <?php else: ?>
                        <a href="/assinatura.php" class="hidden md:flex items-center gap-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-3 py-1 rounded-full text-xs font-bold hover:from-purple-700 hover:to-pink-700 transition shadow-sm">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            PRO
                        </a>
                    <?php endif; ?>
                    
                    <div class="hidden md:block text-right">
                        <p class="text-sm font-medium text-gray-700"><?php echo sanitize($_SESSION['user_name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo sanitize($_SESSION['user_email']); ?></p>
                    </div>
                    <div class="relative group">
                        <button class="flex items-center space-x-2 bg-gray-100 hover:bg-gray-200 rounded-lg px-3 py-2 transition">
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-600 to-pink-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-semibold">
                                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <a href="<?php echo url('/perfil.php'); ?>" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-t-lg transition">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Meu Perfil
                                </div>
                            </a>
                            <a href="<?php echo url('/assinatura.php'); ?>" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    Minha Assinatura
                                </div>
                            </a>
                            <?php if ($is_free): ?>
                                <a href="<?php echo url('/upgrade.php'); ?>" class="block px-4 py-3 text-sm text-purple-600 hover:bg-purple-50 transition font-medium">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        Fazer Upgrade
                                    </div>
                                </a>
                            <?php endif; ?>
                            <hr class="border-gray-200">
                            <a href="<?php echo url('/logout.php'); ?>" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 rounded-b-lg transition">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    Sair
                                </div>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-button" class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 bg-white">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="<?php echo url('/index.php'); ?>" class="<?php echo $currentPage === 'index' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?> block px-3 py-2 rounded-lg text-base font-medium transition">
                    Dashboard
                </a>
                <a href="<?php echo url('/consignacoes.php'); ?>" class="<?php echo $currentPage === 'consignacoes' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?> block px-3 py-2 rounded-lg text-base font-medium transition">
                    Consignações
                </a>
                <a href="<?php echo url('/produtos.php'); ?>" class="<?php echo $currentPage === 'produtos' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?> block px-3 py-2 rounded-lg text-base font-medium transition">
                    Produtos
                </a>
                <a href="<?php echo url('/estabelecimentos.php'); ?>" class="<?php echo $currentPage === 'estabelecimentos' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?> block px-3 py-2 rounded-lg text-base font-medium transition">
                    Estabelecimentos
                </a>
                <a href="<?php echo url('/relatorios.php'); ?>" class="<?php echo $currentPage === 'relatorios' ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?> block px-3 py-2 rounded-lg text-base font-medium transition">
                    Relatórios
                </a>
            </div>
        </div>
    </nav>

    <!-- Toast Notifications (Flutuante) -->
    <?php
    $flash = getFlashMessage();
    if ($flash):
        $bgColor = [
            'success' => 'bg-green-500',
            'error' => 'bg-red-500',
            'warning' => 'bg-yellow-500',
            'info' => 'bg-blue-500'
        ];
        $iconPath = [
            'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'error' => 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z',
            'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        ];
    ?>
    <!-- Toast Container (Inferior Direito) -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-x-0 transition-all duration-500 ease-in-out opacity-0 translate-y-2" style="opacity: 0;">
        <div class="<?php echo $bgColor[$flash['type']]; ?> text-white px-6 py-4 rounded-lg shadow-2xl flex items-center gap-3 min-w-[320px] max-w-md">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $iconPath[$flash['type']]; ?>"></path>
            </svg>
            <p class="font-medium flex-1"><?php echo $flash['message']; ?></p>
            <button onclick="closeToast()" class="ml-2 hover:bg-white hover:bg-opacity-20 rounded p-1 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <script>
    // Mostrar toast com animação
    setTimeout(() => {
        const toast = document.getElementById('toast');
        if (toast) {
            toast.style.opacity = '1';
            toast.classList.remove('translate-y-2');
            toast.classList.add('translate-y-0');
            
            // Auto-fechar após 5 segundos
            setTimeout(() => {
                closeToast();
            }, 5000);
        }
    }, 100);
    
    function closeToast() {
        const toast = document.getElementById('toast');
        if (toast) {
            toast.style.opacity = '0';
            toast.classList.add('translate-y-2');
            setTimeout(() => {
                toast.remove();
            }, 500);
        }
    }
    </script>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="w-[90%] mx-auto py-8">

    <script>
        // Toggle mobile menu
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
