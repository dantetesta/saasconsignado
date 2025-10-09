<?php
/**
 * Página de Login
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

require_once 'config/config.php';

// Se já estiver logado, redireciona para o dashboard
if (isLoggedIn()) {
    redirect('/index.php');
}

$error = '';
$info = '';

// Verificar se foi redirecionado por conta bloqueada
if (isset($_GET['blocked'])) {
    $info = 'Sua sessão foi encerrada porque sua conta foi bloqueada. Entre em contato conosco para mais informações.';
}

// Processa o login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    // Validar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Recarregue a página.';
    }
    // Validar Turnstile (apenas produção)
    elseif (TURNSTILE_ENABLED && !validateTurnstile($_POST['cf-turnstile-response'] ?? '')) {
        $error = 'Verificação de segurança falhou. Tente novamente.';
        recordAttempt('login_' . $email);
    }
    // Verificar rate limiting
    elseif (!checkRateLimit('login_' . $email, 5, 900)) {
        $error = 'Muitas tentativas de login. Aguarde 15 minutos e tente novamente.';
    }
    elseif (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT id, tenant_id, nome, email, senha 
                FROM usuarios 
                WHERE email = ? AND ativo = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                // Carregar classe TenantMiddleware
                require_once __DIR__ . '/classes/TenantMiddleware.php';
                
                // Definir tenant antes de fazer login
                $tenantResult = TenantMiddleware::setTenant($user['tenant_id']);
                
                // Verificar se o tenant está bloqueado ou inativo
                if (!$tenantResult['success']) {
                    $tenantData = $tenantResult['tenant_data'];
                    $status = $tenantResult['status'];
                    
                    // Definir mensagem baseada no status
                    $statusMessages = [
                        'bloqueado' => 'Sua conta foi temporariamente bloqueada.',
                        'suspenso' => 'Sua conta foi suspensa.',
                        'cancelado' => 'Sua conta foi cancelada.',
                        'inativo' => 'Sua conta está inativa.'
                    ];
                    
                    $statusMessage = $statusMessages[$status] ?? 'Sua conta não está ativa.';
                    
                    // Redirecionar para página de conta bloqueada
                    $_SESSION['blocked_account'] = [
                        'message' => $statusMessage,
                        'tenant_name' => $tenantData['nome'],
                        'status' => $status
                    ];
                    
                    header('Location: ' . url('/conta_bloqueada.php'));
                    exit;
                }
                
                // Regenerar session ID (prevenir session fixation)
                session_regenerate_id(true);
                
                // Criar fingerprint da sessão
                createSessionFingerprint();
                
                // Login bem-sucedido
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['login_time'] = time();
                
                redirect('/index.php');
            } else {
                // Registrar tentativa falha
                recordAttempt('login_' . $email);
                $error = 'Email ou senha incorretos.';
            }
        } catch (PDOException $e) {
            error_log("Erro no login: " . $e->getMessage());
            $error = 'Erro ao processar login. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Controle de Consignados - Login">
    <meta name="author" content="Dante Testa - https://dantetesta.com.br">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-orange-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl shadow-lg mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Sistema de Consignados</h1>
            <p class="text-gray-600">Faça login para continuar</p>
        </div>

        <!-- Card de Login -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-red-700 text-sm font-medium"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($info): ?>
                <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-blue-700 text-sm font-medium"><?php echo $info; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
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
                        autocomplete="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200"
                        placeholder="seu@email.com"
                        value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>"
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
                        autocomplete="current-password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200"
                        placeholder="••••••••"
                    >
                </div>

                <!-- Turnstile (apenas produção) -->
                <?php echo turnstileWidget(); ?>

                <!-- Botão de Login -->
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-purple-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transform transition duration-200 hover:scale-[1.02] active:scale-[0.98]"
                >
                    Entrar
                </button>
            </form>

            <!-- Link para Registro -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Não tem uma conta? 
                    <a href="/register.php" class="text-purple-600 hover:text-purple-700 font-medium">Cadastre-se grátis</a>
                </p>
            </div>

        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-sm text-gray-600">
                Desenvolvido por <a href="https://dantetesta.com.br" target="_blank" class="text-purple-600 hover:text-purple-700 font-medium">Dante Testa</a>
            </p>
            <p class="text-xs text-gray-500 mt-2">Versão <?php echo VERSION; ?></p>
        </div>
    </div>
</body>
</html>
