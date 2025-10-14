<?php
/**
 * Landing Page Principal - SaaS Sisteminha
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

require_once 'config/config.php';

// Se já estiver logado, redireciona para o dashboard
if (isLoggedIn()) {
    redirect('/dashboard.php');
}

// Buscar preços dinâmicos (com tratamento de erro)
try {
    require_once __DIR__ . '/classes/PricingManager.php';
    $pricing = PricingManager::getPricing();
} catch (Exception $e) {
    // Valores padrão caso haja erro
    $pricing = [
        'pro_price' => 20.00,
        'pro_price_formatted' => 'R$ 20,00',
        'free_consignacoes_limit' => 5,
        'free_produtos_limit' => 20,
        'free_estabelecimentos_limit' => 5,
    ];
    error_log("Erro ao carregar preços: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema completo de gestão de consignações. Controle produtos, estabelecimentos e pagamentos. Teste grátis!">
    <title>SaaS Sisteminha - Gestão de Consignações Inteligente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
        .gradient-animate {
            background: linear-gradient(-45deg, #2563eb, #10b981, #06b6d4, #3b82f6);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-white">
    <?php include 'views/landing/navbar.php'; ?>
    <?php include 'views/landing/hero.php'; ?>
    <?php include 'views/landing/recursos.php'; ?>
    <?php include 'views/landing/como-funciona.php'; ?>
    <?php include 'views/landing/precos.php'; ?>
    <?php include 'views/landing/cta-final.php'; ?>
    <?php include 'views/landing/footer.php'; ?>
    
    <script>
        // Menu mobile toggle
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>
