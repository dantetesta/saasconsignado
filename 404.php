<?php
/**
 * Página 404 - Não Encontrado
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

$pageTitle = '404 - Página Não Encontrada';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME ?? 'SaaS Sisteminha'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Card Principal -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 text-center">
            <!-- Ícone 404 -->
            <div class="mb-8">
                <svg class="w-32 h-32 mx-auto text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <!-- Título -->
            <h1 class="text-6xl md:text-8xl font-bold text-gray-800 mb-4">404</h1>
            <h2 class="text-2xl md:text-3xl font-semibold text-gray-700 mb-4">
                Página Não Encontrada
            </h2>
            
            <!-- Descrição -->
            <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">
                Ops! A página que você está procurando não existe ou foi movida para outro endereço.
            </p>

            <!-- URL Tentada -->
            <div class="bg-gray-100 rounded-lg p-4 mb-8 max-w-md mx-auto">
                <p class="text-sm text-gray-500 mb-1">URL tentada:</p>
                <code class="text-sm text-gray-700 break-all">
                    <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>
                </code>
            </div>

            <!-- Botões de Ação -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <!-- Botão Voltar -->
                <button 
                    onclick="window.history.back()" 
                    class="inline-flex items-center px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-colors duration-200"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar
                </button>

                <!-- Botão Página Inicial -->
                <a 
                    href="/" 
                    class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors duration-200"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Página Inicial
                </a>
            </div>

            <!-- Links Úteis -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <p class="text-gray-600 mb-4">Páginas úteis:</p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <?php if (defined('isLoggedIn') && function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <a href="/dashboard" class="text-indigo-600 hover:text-indigo-800 font-medium">Dashboard</a>
                        <span class="text-gray-300">•</span>
                        <a href="/produtos" class="text-indigo-600 hover:text-indigo-800 font-medium">Produtos</a>
                        <span class="text-gray-300">•</span>
                        <a href="/consignacoes" class="text-indigo-600 hover:text-indigo-800 font-medium">Consignações</a>
                        <span class="text-gray-300">•</span>
                        <a href="/relatorios" class="text-indigo-600 hover:text-indigo-800 font-medium">Relatórios</a>
                    <?php else: ?>
                        <a href="/login" class="text-indigo-600 hover:text-indigo-800 font-medium">Login</a>
                        <span class="text-gray-300">•</span>
                        <a href="/register" class="text-indigo-600 hover:text-indigo-800 font-medium">Cadastro</a>
                        <span class="text-gray-300">•</span>
                        <a href="/consulta" class="text-indigo-600 hover:text-indigo-800 font-medium">Consulta Pública</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-600">
            <p class="text-sm">
                Desenvolvido por <a href="https://dantetesta.com.br" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-medium">Dante Testa</a>
            </p>
        </div>
    </div>

    <!-- Script para animação -->
    <script>
        // Animação de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.bg-white');
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>
