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
                    
                    // Contar notificações não lidas
                    require_once __DIR__ . '/../classes/Notification.php';
                    $notification = new Notification();
                    $unreadCount = $notification->countUnread($_SESSION['tenant_id']);
                    ?>
                    
                    <!-- Notificações -->
                    <button 
                        onclick="openNotifications()"
                        class="relative p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                        title="Notificações"
                    >
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                        <?php if ($unreadCount > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center animate-pulse">
                                <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    
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

    <!-- Modal de Notificações -->
    <div id="notificationsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-start justify-center pt-20 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                        <h3 class="text-xl font-bold">Notificações</h3>
                    </div>
                    <button onclick="closeNotifications()" class="hover:bg-white hover:bg-opacity-20 rounded-lg p-1 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Lista de Notificações -->
            <div id="notificationsList" class="overflow-y-auto max-h-[calc(80vh-120px)] p-6">
                <div class="text-center py-8">
                    <div class="animate-spin w-8 h-8 border-4 border-purple-600 border-t-transparent rounded-full mx-auto"></div>
                    <p class="text-gray-500 mt-3">Carregando...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Abrir modal de notificações
    function openNotifications() {
        document.getElementById('notificationsModal').classList.remove('hidden');
        loadNotifications();
    }

    // Fechar modal
    function closeNotifications() {
        document.getElementById('notificationsModal').classList.add('hidden');
    }

    // Carregar notificações via AJAX
    function loadNotifications() {
        fetch('/api/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('notificationsList');
                
                if (data.notifications && data.notifications.length > 0) {
                    container.innerHTML = data.notifications.map(notif => {
                        const tipoColors = {
                            'info': 'bg-blue-50 border-blue-200 text-blue-900',
                            'success': 'bg-green-50 border-green-200 text-green-900',
                            'warning': 'bg-yellow-50 border-yellow-200 text-yellow-900',
                            'error': 'bg-red-50 border-red-200 text-red-900',
                            'email': 'bg-purple-50 border-purple-200 text-purple-900'
                        };
                        
                        const tipoIcons = {
                            'info': 'ℹ️',
                            'success': '✅',
                            'warning': '⚠️',
                            'error': '❌',
                            'email': '📧'
                        };
                        
                        const color = tipoColors[notif.tipo] || tipoColors['info'];
                        const icon = tipoIcons[notif.tipo] || tipoIcons['info'];
                        const lida = notif.lida == 1;
                        
                        return `
                            <div class="mb-3 p-4 border ${color} rounded-lg ${lida ? 'opacity-60' : ''} transition-all duration-300" data-notif-id="${notif.id}">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">${icon}</span>
                                        <h4 class="font-bold">${notif.titulo}</h4>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        ${!lida ? '<span class="bg-red-500 w-2 h-2 rounded-full"></span>' : ''}
                                        ${!lida ? `
                                            <button onclick="markAsRead(${notif.id})" class="text-gray-400 hover:text-green-600" title="Marcar como lida">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        ` : ''}
                                        <button onclick="deleteNotification(${notif.id})" class="text-gray-400 hover:text-red-600" title="Deletar">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm mb-2">${notif.mensagem}</p>
                                <p class="text-xs opacity-75">${notif.data_formatada}</p>
                            </div>
                        `;
                    }).join('');
                    
                    // Botão marcar todas como lidas
                    if (data.unread_count > 0) {
                        container.innerHTML += `
                            <button 
                                onclick="markAllAsRead()"
                                class="w-full mt-4 px-4 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition"
                            >
                                Marcar todas como lidas
                            </button>
                        `;
                    }
                } else {
                    container.innerHTML = `
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <p class="text-gray-500">Nenhuma notificação</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar notificações:', error);
                document.getElementById('notificationsList').innerHTML = `
                    <div class="text-center py-12 text-red-600">
                        <p>Erro ao carregar notificações</p>
                    </div>
                `;
            });
    }

    // Marcar como lida (sem fechar modal)
    function markAsRead(notifId) {
        fetch('/api/mark_notification_read.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({notification_id: notifId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar visualmente sem recarregar
                const notifElement = document.querySelector(`[data-notif-id="${notifId}"]`);
                if (notifElement) {
                    notifElement.classList.add('opacity-60');
                    const badge = notifElement.querySelector('.bg-red-500');
                    if (badge) badge.remove();
                }
                
                // Atualizar badge do header
                updateBadgeCount();
            }
        });
    }

    // Deletar notificação
    function deleteNotification(notifId) {
        if (!confirm('Deletar esta notificação?')) return;
        
        fetch('/api/delete_notification.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({notification_id: notifId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remover elemento com animação
                const notifElement = document.querySelector(`[data-notif-id="${notifId}"]`);
                if (notifElement) {
                    notifElement.style.opacity = '0';
                    notifElement.style.transform = 'translateX(100%)';
                    setTimeout(() => notifElement.remove(), 300);
                }
                
                // Atualizar badge
                updateBadgeCount();
            }
        });
    }

    // Marcar todas como lidas
    function markAllAsRead() {
        fetch('/api/mark_all_notifications_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
                updateBadgeCount();
            }
        });
    }

    // Atualizar contador do badge
    function updateBadgeCount() {
        fetch('/api/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.animate-pulse');
                if (data.unread_count > 0) {
                    if (badge) {
                        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    }
                } else {
                    if (badge) badge.remove();
                }
            });
    }

    // Fechar ao clicar fora
    document.getElementById('notificationsModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeNotifications();
        }
    });
    </script>

    <!-- Main Content -->
    <main class="w-[90%] mx-auto py-8">

    <script>
        // Toggle mobile menu
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
