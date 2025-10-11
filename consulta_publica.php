<?php
/**
 * Consulta Pública de Consignação
 * 
 * Permite que estabelecimentos visualizem suas consignações através de um link público
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.2.1
 */

// Carregar configurações (já inicia a sessão)
try {
    require_once 'config/config.php';
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die("ERRO DE CONFIGURAÇÃO: " . $e->getMessage() . "<br>Arquivo: " . $e->getFile() . "<br>Linha: " . $e->getLine());
} catch (Error $e) {
    die("ERRO FATAL: " . $e->getMessage() . "<br>Arquivo: " . $e->getFile() . "<br>Linha: " . $e->getLine());
}
$error = null;
$consignacao = null;
$estabelecimento = null;

// Obter token da URL
$token = $_GET['token'] ?? null;

if (!$token) {
    $error = "Link inválido. Verifique o link fornecido.";
} else {
    // Buscar estabelecimento pelo token e dados da empresa
    $stmt = $db->prepare("
        SELECT e.*, 
               u.whatsapp as whatsapp_empresa,
               u.logo as logo_empresa,
               u.nome_empresa,
               u.email_remetente as email_empresa
        FROM estabelecimentos e 
        LEFT JOIN usuarios u ON u.ativo = 1 
        WHERE e.token_acesso = ? AND e.ativo = 1
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $estabelecimento = $stmt->fetch();
    
    // DEBUG: Verificar dados carregados (remover em produção)
    // error_log("Logo empresa: " . ($estabelecimento['logo_empresa'] ?? 'NULL'));
    // error_log("Nome empresa: " . ($estabelecimento['nome_empresa'] ?? 'NULL'));
    // error_log("Email empresa: " . ($estabelecimento['email_empresa'] ?? 'NULL'));
    // error_log("WhatsApp empresa: " . ($estabelecimento['whatsapp_empresa'] ?? 'NULL'));
    
    if (!$estabelecimento) {
        $error = "Link inválido ou expirado.";
    } elseif (empty($estabelecimento['senha_acesso'])) {
        $error = "Este estabelecimento ainda não possui senha de acesso configurada. Entre em contato com o administrador.";
    }
}

// Processar recuperação de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'recuperar_senha') {
    $email_recuperacao = filter_var(trim($_POST['email_recuperacao']), FILTER_SANITIZE_EMAIL);
    
    // Verificar se o email corresponde ao estabelecimento
    if (strtolower($email_recuperacao) === strtolower($estabelecimento['email'])) {
        // Gerar nova senha de 4 dígitos
        $nova_senha = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        // Atualizar senha no banco
        $stmt = $db->prepare("UPDATE estabelecimentos SET senha_acesso = ? WHERE id = ?");
        $stmt->execute([$senha_hash, $estabelecimento['id']]);
        
        // Enviar email com a nova senha
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $assunto = "Recuperação de Senha - Consulta de Consignações";
        $mensagem = "
            <h2>Recuperação de Senha</h2>
            <p>Olá, {$estabelecimento['nome']}!</p>
            <p>Sua nova senha de acesso à consulta de consignações é:</p>
            <h1 style='font-size: 32px; color: #2563eb; letter-spacing: 4px;'>{$nova_senha}</h1>
            <p>Use esta senha para acessar suas consignações.</p>
            <p><a href='{$protocol}://{$host}/consulta_publica.php?token={$token}' style='background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>Acessar Agora</a></p>
        ";
        
        // Enviar email usando PHPMailer
        require_once __DIR__ . '/vendor/autoload.php';
        
        // Verificar se existe configuração de email
        if (file_exists(__DIR__ . '/config/email.php')) {
            require_once __DIR__ . '/config/email.php';
        }
        
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configurações SMTP
            $mail->isSMTP();
            $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
            $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
            $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
            $mail->CharSet = 'UTF-8';
            
            // Remetente
            $mail->setFrom(
                defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@sisteminha.com',
                defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Sistema de Consignados'
            );
            
            // Destinatário
            $mail->addAddress($estabelecimento['email'], $estabelecimento['nome']);
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $mensagem;
            
            $mail->send();
            $success_recuperacao = "Nova senha enviada para {$estabelecimento['email']}!";
        } catch (Exception $e) {
            error_log("Erro ao enviar email de recuperação: " . $e->getMessage());
            $error_recuperacao = "Erro ao enviar email. Verifique as configurações SMTP.";
        }
    } else {
        $error_recuperacao = "Email não corresponde ao cadastro deste estabelecimento.";
    }
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
    $senha = $_POST['senha'];
    
    if (password_verify($senha, $estabelecimento['senha_acesso'])) {
        $_SESSION['estabelecimento_autenticado'] = $estabelecimento['id'];
        $_SESSION['estabelecimento_token'] = $token;
        header("Location: " . (defined('BASE_PATH') ? BASE_PATH : '') . "/consulta_publica.php?token=" . $token);
        exit;
    } else {
        $error_login = "Senha incorreta. Tente novamente.";
    }
}

// Verificar se está autenticado
$autenticado = isset($_SESSION['estabelecimento_autenticado']) && 
               isset($_SESSION['estabelecimento_token']) &&
               $_SESSION['estabelecimento_token'] === $token;

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['estabelecimento_autenticado']);
    unset($_SESSION['estabelecimento_token']);
    header("Location: " . (defined('BASE_PATH') ? BASE_PATH : '') . "/consulta_publica.php?token=$token");
    exit;
}

// Buscar consignação específica ou listar todas
$consignacao_id = $_GET['id'] ?? null;

if ($autenticado && $estabelecimento) {
    if ($consignacao_id) {
        // Buscar consignação específica
        $stmt = $db->prepare("
            SELECT c.*, e.nome as estabelecimento
            FROM consignacoes c
            INNER JOIN estabelecimentos e ON c.estabelecimento_id = e.id
            WHERE c.id = ? AND c.estabelecimento_id = ?
        ");
        $stmt->execute([$consignacao_id, $estabelecimento['id']]);
        $consignacao = $stmt->fetch();
        
        if ($consignacao) {
            // Buscar itens (adaptar para tipo de consignação)
            if ($consignacao['tipo'] === 'continua') {
                // Para consignações contínuas, buscar todos os produtos com movimentação
                $stmt = $db->prepare("
                    SELECT 
                        m.produto_id,
                        p.nome as produto,
                        COALESCE(SUM(CASE WHEN m.tipo = 'entrega' THEN m.quantidade ELSE 0 END), 0) as total_entregue,
                        COALESCE(SUM(CASE WHEN m.tipo = 'entrega' THEN m.quantidade ELSE 0 END), 0) - 
                        COALESCE(SUM(CASE WHEN m.tipo = 'devolucao' THEN m.quantidade ELSE 0 END), 0) as quantidade_consignada,
                        COALESCE(SUM(CASE WHEN m.tipo = 'venda' THEN m.quantidade ELSE 0 END), 0) as quantidade_vendida,
                        COALESCE(SUM(CASE WHEN m.tipo = 'devolucao' THEN m.quantidade ELSE 0 END), 0) as quantidade_devolvida,
                        MAX(m.preco_unitario) as preco_unitario,
                        0 as quantidade_inicial
                    FROM movimentacoes_consignacao m
                    INNER JOIN produtos p ON m.produto_id = p.id
                    WHERE m.consignacao_id = ?
                    GROUP BY m.produto_id, p.nome
                    ORDER BY p.nome
                ");
                $stmt->execute([$consignacao_id]);
                $itens = $stmt->fetchAll();
                
                // Buscar movimentações para histórico
                $stmt = $db->prepare("
                    SELECT m.*, p.nome as produto
                    FROM movimentacoes_consignacao m
                    INNER JOIN produtos p ON m.produto_id = p.id
                    WHERE m.consignacao_id = ?
                    ORDER BY m.data_movimentacao DESC, m.criado_em DESC
                    LIMIT 50
                ");
                $stmt->execute([$consignacao_id]);
                $movimentacoes = $stmt->fetchAll();
            } else {
                // Para consignações pontuais, usar tabela normal
                $stmt = $db->prepare("
                    SELECT ci.*, p.nome as produto
                    FROM consignacao_itens ci
                    INNER JOIN produtos p ON ci.produto_id = p.id
                    WHERE ci.consignacao_id = ?
                    ORDER BY p.nome
                ");
                $stmt->execute([$consignacao_id]);
                $itens = $stmt->fetchAll();
                $movimentacoes = [];
            }
            
            // Buscar pagamentos
            $stmt = $db->prepare("
                SELECT * FROM pagamentos 
                WHERE consignacao_id = ? 
                ORDER BY data_pagamento DESC
            ");
            $stmt->execute([$consignacao_id]);
            $pagamentos = $stmt->fetchAll();
            
            // Calcular totais
            if ($consignacao['tipo'] === 'continua') {
                // Para contínuas: calcular diretamente das movimentações
                $stmt = $db->prepare("
                    SELECT 
                        COALESCE(SUM(CASE WHEN tipo = 'entrega' THEN quantidade ELSE 0 END), 0) as total_entregue,
                        COALESCE(SUM(CASE WHEN tipo = 'venda' THEN quantidade ELSE 0 END), 0) as total_vendido,
                        COALESCE(SUM(CASE WHEN tipo = 'devolucao' THEN quantidade ELSE 0 END), 0) as total_devolvido
                    FROM movimentacoes_consignacao
                    WHERE consignacao_id = ?
                ");
                $stmt->execute([$consignacao_id]);
                $totais = $stmt->fetch();
                
                $total_consignado = $totais['total_entregue']; // Total entregue (não o saldo)
                $total_vendido = $totais['total_vendido'];
                $total_devolvido = $totais['total_devolvido'];
                $total_pendente = $totais['total_entregue'] - $totais['total_vendido'] - $totais['total_devolvido']; // Ainda no estabelecimento
            } else {
                $total_consignado = array_sum(array_column($itens, 'quantidade_consignada'));
                $total_vendido = array_sum(array_column($itens, 'quantidade_vendida'));
                $total_devolvido = array_sum(array_column($itens, 'quantidade_devolvida'));
                $total_pendente = $total_consignado - $total_vendido - $total_devolvido;
            }
            
            $valor_total = 0;
            foreach ($itens as $item) {
                $valor_total += $item['quantidade_vendida'] * $item['preco_unitario'];
            }
            
            $valor_pago = array_sum(array_column($pagamentos, 'valor_pago'));
            $saldo_pendente = max(0, $valor_total - $valor_pago);
        }
    } else {
        // Listar todas as consignações do estabelecimento
        $stmt = $db->prepare("
            SELECT c.*, 
                   CASE 
                       WHEN c.tipo = 'continua' THEN (
                           SELECT COALESCE(SUM(CASE WHEN tipo = 'entrega' THEN quantidade ELSE 0 END) - 
                                          SUM(CASE WHEN tipo = 'devolucao' THEN quantidade ELSE 0 END), 0)
                           FROM movimentacoes_consignacao WHERE consignacao_id = c.id
                       )
                       ELSE (SELECT COALESCE(SUM(ci.quantidade_consignada), 0) FROM consignacao_itens ci WHERE ci.consignacao_id = c.id)
                   END as total_itens,
                   CASE 
                       WHEN c.tipo = 'continua' THEN (
                           SELECT COALESCE(SUM(quantidade * preco_unitario), 0)
                           FROM movimentacoes_consignacao WHERE consignacao_id = c.id AND tipo = 'venda'
                       )
                       ELSE (SELECT COALESCE(SUM(ci.quantidade_vendida * ci.preco_unitario), 0) FROM consignacao_itens ci WHERE ci.consignacao_id = c.id)
                   END as valor_total
            FROM consignacoes c
            WHERE c.estabelecimento_id = ?
            ORDER BY c.data_consignacao DESC
        ");
        $stmt->execute([$estabelecimento['id']]);
        $consignacoes = $stmt->fetchAll();
    }
}

// Todas as funções auxiliares já estão declaradas no config.php
// (formatMoney, formatDate, translateStatus, getStatusBadgeClass)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Pública - <?php echo $estabelecimento ? htmlspecialchars($estabelecimento['nome']) : 'Sistema de Consignados'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-emerald-50 to-cyan-50 min-h-screen">

<?php if ($error): ?>
    <!-- Tela de Erro -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Acesso Negado</h1>
            <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($error); ?></p>
            <p class="text-sm text-gray-500">
                Se você acredita que isso é um erro, entre em contato com o administrador do sistema.
            </p>
        </div>
    </div>

<?php elseif (!$autenticado): ?>
    <!-- Tela de Login -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-emerald-600 p-8 text-center">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-white mb-2">Consulta de Consignação</h1>
                    <p class="text-blue-100"><?php echo htmlspecialchars($estabelecimento['nome']); ?></p>
                </div>
                
                <!-- Form -->
                <div class="p-8">
                    <?php if (isset($_POST['senha']) && !$autenticado): ?>
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-800">❌ <?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">
                                🔐 Digite sua senha de acesso
                            </label>
                            <input 
                                type="password" 
                                id="senha" 
                                name="senha"
                                required
                                autofocus
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                                placeholder="••••••••"
                            >
                            <p class="text-xs text-gray-500 mt-2">
                                💡 Use a senha fornecida pelo administrador do sistema
                            </p>
                        </div>
                        
                        <button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-700 hover:to-emerald-700 transition transform hover:scale-[1.02] active:scale-[0.98]"
                        >
                            Acessar Consignações
                        </button>
                    </form>
                    
                    <!-- Link Esqueci a Senha -->
                    <div class="mt-4 text-center">
                        <button onclick="mostrarRecuperacao()" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                            Esqueci minha senha
                        </button>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-8 py-4 text-center border-t border-gray-200">
                    <p class="text-xs text-gray-600">
                        Desenvolvido por <a href="https://dantetesta.com.br" target="_blank" class="text-blue-600 hover:text-blue-700 font-medium">Dante Testa</a>
                    </p>
                </div>
            </div>
            </div>
        </div>
        
        <!-- Modal Recuperação de Senha -->
        <div id="modalRecuperacao" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Recuperar Senha</h3>
                    <button onclick="fecharRecuperacao()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <?php if (isset($success_recuperacao)): ?>
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800">✅ <?php echo $success_recuperacao; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_recuperacao)): ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-800">❌ <?php echo $error_recuperacao; ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="recuperar_senha">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Digite seu email cadastrado
                        </label>
                        <input 
                            type="email" 
                            name="email_recuperacao" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="seu@email.com"
                        >
                        <p class="text-xs text-gray-500 mt-1">Enviaremos uma nova senha para este email</p>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" onclick="fecharRecuperacao()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Recuperar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        function mostrarRecuperacao() {
            document.getElementById('modalRecuperacao').classList.remove('hidden');
        }
        
        function fecharRecuperacao() {
            document.getElementById('modalRecuperacao').classList.add('hidden');
        }
        
        <?php if (isset($success_recuperacao) || isset($error_recuperacao)): ?>
        // Abrir modal automaticamente se houver mensagem
        mostrarRecuperacao();
        <?php endif; ?>
        </script>

<?php elseif ($consignacao): ?>
    <!-- Visualização de Consignação Específica -->
    <?php include 'views/consulta_publica_view.php'; ?>

<?php else: ?>
    <!-- Listagem de Consignações -->
    <?php include 'views/consulta_publica_list.php'; ?>

<?php endif; ?>

</body>
</html>
