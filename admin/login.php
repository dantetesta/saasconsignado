<?php
/**
 * Login do Super Admin
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

session_start();
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../classes/SuperAdmin.php';

// Se já está logado, redirecionar
if (SuperAdmin::isLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Validar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Recarregue a página.';
    }
    // Validar Turnstile (apenas produção)
    elseif (TURNSTILE_ENABLED && !validateTurnstile($_POST['cf-turnstile-response'] ?? '')) {
        $error = 'Verificação de segurança falhou. Tente novamente.';
        recordAttempt('admin_login_' . $email);
    }
    // Rate limiting
    elseif (!checkRateLimit('admin_login_' . $email, 3, 900)) {
        $error = 'Muitas tentativas. Aguarde 15 minutos.';
    }
    elseif (empty($email) || empty($senha)) {
        $error = 'Preencha todos os campos';
    } else {
        $superAdmin = new SuperAdmin();
        if ($superAdmin->authenticate($email, $senha)) {
            // Regenerar session ID
            session_regenerate_id(true);
            createSessionFingerprint();
            
            header('Location: /admin/index.php');
            exit;
        } else {
            recordAttempt('admin_login_' . $email);
            $error = 'Email ou senha inválidos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SaaS Consignados</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-purple-900 to-pink-900 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-4">
                <svg class="w-10 h-10 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Painel Administrativo</h1>
            <p class="text-purple-200">Acesso restrito ao dono do SaaS</p>
        </div>

        <!-- Card de Login -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Login Admin</h2>
            
            <?php if ($error): ?>
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <?php echo csrfField(); ?>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="admin@exemplo.com"
                        autocomplete="email"
                    >
                </div>

                <!-- Senha -->
                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">
                        Senha
                    </label>
                    <input 
                        type="password" 
                        id="senha" 
                        name="senha" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="••••••••"
                        autocomplete="current-password"
                    >
                </div>

                <!-- Turnstile (apenas produção) -->
                <?php echo turnstileWidget(); ?>

                <!-- Botão -->
                <button 
                    type="submit"
                    class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-lg hover:from-purple-700 hover:to-pink-700 transition transform hover:scale-105"
                >
                    Entrar no Painel
                </button>
            </form>

            <!-- Aviso de Segurança -->
            <div class="mt-6 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-xs text-yellow-800 text-center">
                    🔒 Área restrita. Todas as ações são registradas em logs.
                </p>
            </div>
        </div>

        <!-- Link para site principal -->
        <div class="text-center mt-6">
            <a href="/" class="text-purple-200 hover:text-white text-sm transition">
                ← Voltar ao site
            </a>
        </div>
    </div>

</body>
</html>
