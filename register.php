<?php
/**
 * Página de Registro de Novos Tenants (SaaS)
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

// Não requer login
session_start();

// Se já estiver logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_empresa = trim($_POST['nome_empresa'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirm = $_POST['senha_confirm'] ?? '';
    $subdomain = strtolower(trim($_POST['subdomain'] ?? ''));
    
    // Validações
    if (empty($nome_empresa) || empty($email) || empty($senha) || empty($subdomain)) {
        $error = 'Todos os campos são obrigatórios.';
    } elseif ($senha !== $senha_confirm) {
        $error = 'As senhas não conferem.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } elseif (!preg_match('/^[a-z0-9-]+$/', $subdomain)) {
        $error = 'Subdomínio inválido. Use apenas letras minúsculas, números e hífen.';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Verificar se email já existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception("Este email já está cadastrado.");
            }
            
            // Verificar se subdomínio já existe
            $stmt = $db->prepare("SELECT id FROM tenants WHERE subdomain = ?");
            $stmt->execute([$subdomain]);
            if ($stmt->fetch()) {
                throw new Exception("Este subdomínio já está em uso. Escolha outro.");
            }
            
            // Iniciar transação
            $db->beginTransaction();
            
            // Criar tenant (conta)
            $stmt = $db->prepare("
                INSERT INTO tenants (
                    nome_empresa, 
                    subdomain, 
                    email_principal, 
                    plano, 
                    status,
                    limite_estabelecimentos,
                    limite_consignacoes_por_estabelecimento
                ) VALUES (?, ?, ?, 'free', 'trial', 5, 5)
            ");
            $stmt->execute([$nome_empresa, $subdomain, $email]);
            $tenant_id = $db->lastInsertId();
            
            // Criar usuário administrador
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO usuarios (
                    tenant_id, 
                    nome, 
                    email, 
                    nome_empresa,
                    senha, 
                    ativo
                ) VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$tenant_id, $nome_empresa, $email, $nome_empresa, $senha_hash]);
            $user_id = $db->lastInsertId();
            
            // Criar assinatura Free
            $stmt = $db->prepare("
                INSERT INTO subscriptions (
                    tenant_id,
                    plan_id,
                    status,
                    data_inicio,
                    data_vencimento,
                    valor_mensal
                ) 
                SELECT 
                    ?,
                    id,
                    'ativa',
                    CURDATE(),
                    DATE_ADD(CURDATE(), INTERVAL 365 DAY),
                    0.00
                FROM subscription_plans 
                WHERE slug = 'free'
            ");
            $stmt->execute([$tenant_id]);
            
            // Commit
            $db->commit();
            
            // Fazer login automático
            require_once 'classes/TenantMiddleware.php';
            TenantMiddleware::setTenant($tenant_id);
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $nome_empresa;
            $_SESSION['user_email'] = $email;
            
            // Redirecionar para dashboard
            header('Location: /index.php?welcome=1');
            exit;
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

// Gerar sugestão de subdomínio baseado no nome da empresa (se preenchido)
$suggested_subdomain = '';
if (!empty($_POST['nome_empresa'])) {
    $suggested_subdomain = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($_POST['nome_empresa'])));
    $suggested_subdomain = trim($suggested_subdomain, '-');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cadastre-se gratuitamente no Sistema de Consignados">
    <meta name="author" content="Dante Testa - https://dantetesta.com.br">
    <title>Criar Conta - Sistema de Consignados</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-orange-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl shadow-lg mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Criar Conta Grátis</h1>
            <p class="text-gray-600">Comece agora com o plano Free</p>
        </div>

        <!-- Card de Registro -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-red-700 text-sm font-medium"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <!-- Nome da Empresa -->
                <div>
                    <label for="nome_empresa" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome da Empresa *
                    </label>
                    <input 
                        type="text" 
                        id="nome_empresa" 
                        name="nome_empresa" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        placeholder="Minha Empresa"
                        value="<?php echo isset($_POST['nome_empresa']) ? htmlspecialchars($_POST['nome_empresa']) : ''; ?>"
                    >
                </div>

                <!-- Subdomínio -->
                <div>
                    <label for="subdomain" class="block text-sm font-medium text-gray-700 mb-2">
                        Escolha seu subdomínio *
                    </label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="text" 
                            id="subdomain" 
                            name="subdomain" 
                            required
                            pattern="[a-z0-9-]+"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                            placeholder="minha-empresa"
                            value="<?php echo isset($_POST['subdomain']) ? htmlspecialchars($_POST['subdomain']) : $suggested_subdomain; ?>"
                        >
                        <span class="text-gray-600">.seudominio.com.br</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">Apenas letras minúsculas, números e hífen</p>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email *
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        autocomplete="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        placeholder="seu@email.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>

                <!-- Senha -->
                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">
                        Senha *
                    </label>
                    <input 
                        type="password" 
                        id="senha" 
                        name="senha" 
                        required
                        minlength="6"
                        autocomplete="new-password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        placeholder="••••••••"
                    >
                    <p class="mt-2 text-sm text-gray-500">Mínimo de 6 caracteres</p>
                </div>

                <!-- Confirmar Senha -->
                <div>
                    <label for="senha_confirm" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirmar Senha *
                    </label>
                    <input 
                        type="password" 
                        id="senha_confirm" 
                        name="senha_confirm" 
                        required
                        minlength="6"
                        autocomplete="new-password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        placeholder="••••••••"
                    >
                </div>

                <!-- Plano Free Info -->
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-4">
                    <h3 class="font-semibold text-purple-900 mb-2">✨ Plano Free Inclui:</h3>
                    <ul class="space-y-1 text-sm text-purple-800">
                        <li>✓ Até 5 estabelecimentos</li>
                        <li>✓ 5 consignações por estabelecimento</li>
                        <li>✓ Controle completo de produtos</li>
                        <li>✓ Relatórios básicos</li>
                        <li>✓ Suporte por email</li>
                    </ul>
                    <p class="mt-3 text-xs text-purple-700">
                        Faça upgrade para o <strong>Plano Pro</strong> por apenas R$ 20/mês e tenha tudo ilimitado!
                    </p>
                </div>

                <!-- Botão de Registro -->
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-purple-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transform transition duration-200 hover:scale-[1.02] active:scale-[0.98]"
                >
                    Criar Conta Grátis
                </button>
            </form>

            <!-- Link para Login -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Já tem uma conta? 
                    <a href="/login.php" class="text-purple-600 hover:text-purple-700 font-medium">Faça login</a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-sm text-gray-600">
                Desenvolvido por <a href="https://dantetesta.com.br" target="_blank" class="text-purple-600 hover:text-purple-700 font-medium">Dante Testa</a>
            </p>
            <p class="text-xs text-gray-500 mt-2">Versão 2.0.0 SaaS</p>
        </div>
    </div>
</body>
</html>
