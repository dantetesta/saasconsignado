<?php
/**
 * Header Padrão do Painel Administrativo
 * Template reutilizável para todas as páginas admin
 * 
 * Variáveis esperadas:
 * - $pageTitle: Título da página
 * - $pageSubtitle: Subtítulo da página (opcional)
 * - $pageIcon: Ícone SVG da página (opcional)
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

// Garantir que temos um admin logado
if (!isset($admin) || !$admin) {
    $admin = SuperAdmin::getCurrentAdmin();
}

// Carregar configurações do sistema
require_once __DIR__ . '/system_config.php';

// Título da página para o <title> tag
$pageTitle = $pageTitle ?? 'Dashboard';
$systemName = SystemConfig::getSystemName();
$systemLogo = SystemConfig::getSystemLogo();
$hasLogo = SystemConfig::hasLogo();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
</head>
<body class="bg-gray-50">

    <!-- Header Admin Padrão -->
    <nav class="bg-gradient-to-r from-blue-600 to-emerald-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <?php if ($hasLogo): ?>
                        <!-- Logotipo Personalizado -->
                        <img 
                            src="/<?php echo htmlspecialchars($systemLogo); ?>" 
                            alt="<?php echo htmlspecialchars($systemName); ?>" 
                            class="h-8 w-auto object-contain"
                        >
                    <?php else: ?>
                        <!-- Ícone Padrão -->
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                        </svg>
                    <?php endif; ?>
                    <div>
                        <h1 class="text-lg font-bold"><?php echo htmlspecialchars($systemName); ?></h1>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <span class="text-sm hidden md:block">👋 <?php echo htmlspecialchars($admin['nome']); ?></span>
                    <a href="/admin/logout.php" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition text-sm font-medium">
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>
