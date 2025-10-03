<?php
/**
 * Envio de Email para Consignação
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.2.0
 */

require_once 'config/config.php';
require_once 'config/email.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

requireLogin();

$pageTitle = 'Enviar Email';
$db = Database::getInstance()->getConnection();

$consignacao_id = $_GET['consignacao_id'] ?? null;
$error = null;
$success = null;

if (!$consignacao_id) {
    setFlashMessage('error', 'Consignação não encontrada.');
    redirect('/consignacoes.php');
}

// Buscar dados da consignação
$stmt = $db->prepare("
    SELECT c.*, e.nome as estabelecimento, e.email, e.token_acesso
    FROM consignacoes c
    INNER JOIN estabelecimentos e ON c.estabelecimento_id = e.id
    WHERE c.id = ?
");
$stmt->execute([$consignacao_id]);
$consignacao = $stmt->fetch();

if (!$consignacao) {
    setFlashMessage('error', 'Consignação não encontrada.');
    redirect('/consignacoes.php');
}

// Buscar itens (verifica tipo de consignação)
if ($consignacao['tipo'] === 'continua') {
    // Para consignações contínuas, busca das movimentações
    $stmt = $db->prepare("
        SELECT 
            p.nome as produto,
            SUM(CASE WHEN m.tipo = 'entrega' THEN m.quantidade ELSE 0 END) as quantidade_consignada,
            m.preco_unitario
        FROM movimentacoes_consignacao m
        INNER JOIN produtos p ON m.produto_id = p.id
        WHERE m.consignacao_id = ? AND m.tipo = 'entrega'
        GROUP BY p.id, p.nome, m.preco_unitario
        ORDER BY p.nome
    ");
} else {
    // Para consignações normais, busca dos itens
    $stmt = $db->prepare("
        SELECT ci.*, p.nome as produto
        FROM consignacao_itens ci
        INNER JOIN produtos p ON ci.produto_id = p.id
        WHERE ci.consignacao_id = ?
        ORDER BY p.nome
    ");
}
$stmt->execute([$consignacao_id]);
$itens = $stmt->fetchAll();

// Processar envio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_destino = sanitize($_POST['email_destino']);
    $assunto = sanitize($_POST['assunto']);
    $mensagem_adicional = sanitize($_POST['mensagem_adicional']);
    
    if (empty($email_destino)) {
        $error = 'Email de destino é obrigatório.';
    } else {
        try {
            $mail = new PHPMailer(true);
            
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = SMTP_CHARSET;
            
            if (SMTP_DEBUG > 0) {
                $mail->SMTPDebug = SMTP_DEBUG;
                $mail->Debugoutput = 'html';
            }
            
            // Remetente e destinatário
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email_destino, $consignacao['estabelecimento']);
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            
            // Preparar dados para o template
            $produtos_html = '';
            $valor_total = 0;
            foreach ($itens as $item) {
                $subtotal = $item['quantidade_consignada'] * $item['preco_unitario'];
                $valor_total += $subtotal;
                $produtos_html .= '<li>
                    <span>' . htmlspecialchars($item['produto']) . ' (' . $item['quantidade_consignada'] . ' un)</span>
                    <span>' . formatMoney($subtotal) . '</span>
                </li>';
            }
            
            $link_consulta = SITE_URL . "/consulta_publica.php?token=" . $consignacao['token_acesso'] . "&id=" . $consignacao_id;
            
            $dados_email = [
                'consignacao_id' => $consignacao_id,
                'estabelecimento' => $consignacao['estabelecimento'],
                'data_consignacao' => formatDate($consignacao['data_consignacao']),
                'data_vencimento' => !empty($consignacao['data_vencimento']) ? formatDate($consignacao['data_vencimento']) : '',
                'produtos_html' => $produtos_html,
                'valor_total' => formatMoney($valor_total),
                'link_consulta' => $link_consulta,
                'observacoes' => $consignacao['observacoes']
            ];
            
            $mail->Body = getEmailTemplate($dados_email);
            
            // Texto alternativo para clientes que não suportam HTML
            $mail->AltBody = "Nova Consignação #$consignacao_id\n\n" .
                            "Estabelecimento: {$consignacao['estabelecimento']}\n" .
                            "Data: " . formatDate($consignacao['data_consignacao']) . "\n\n" .
                            "Acesse: $link_consulta";
            
            $mail->send();
            $success = 'Email enviado com sucesso!';
            
        } catch (Exception $e) {
            $error = $mail->ErrorInfo ? $mail->ErrorInfo : $e->getMessage();
            error_log("Erro ao enviar email: " . $e->getMessage());
            error_log("PHPMailer ErrorInfo: " . $mail->ErrorInfo);
        }
    }
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/consignacoes.php?action=view&id=' . $consignacao_id); ?>" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Enviar Email</h1>
            <p class="text-gray-600 mt-1">Consignação #<?php echo $consignacao_id; ?> - <?php echo sanitize($consignacao['estabelecimento']); ?></p>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-800">❌ <?php echo htmlspecialchars($error); ?></p>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <p class="text-sm text-green-800">✅ <?php echo htmlspecialchars($success); ?></p>
        <a href="<?php echo url('/consignacoes.php?action=view&id=' . $consignacao_id); ?>" class="text-green-700 hover:text-green-800 font-medium text-sm mt-2 inline-block">
            Voltar para a consignação →
        </a>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulário -->
    <div class="lg:col-span-2">
        <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            
            <!-- Email de Destino -->
            <div>
                <label for="email_destino" class="block text-sm font-medium text-gray-700 mb-2">
                    📧 Email de Destino *
                </label>
                <input 
                    type="email" 
                    id="email_destino" 
                    name="email_destino"
                    required
                    value="<?php echo !empty($consignacao['email']) ? htmlspecialchars($consignacao['email']) : ''; ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="cliente@email.com"
                >
                <p class="text-xs text-gray-500 mt-1">
                    <?php if (empty($consignacao['email'])): ?>
                        ⚠️ Este estabelecimento não possui email cadastrado. Digite o email manualmente.
                    <?php else: ?>
                        💡 Email do cadastro do estabelecimento
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Assunto -->
            <div>
                <label for="assunto" class="block text-sm font-medium text-gray-700 mb-2">
                    📝 Assunto do Email *
                </label>
                <input 
                    type="text" 
                    id="assunto" 
                    name="assunto"
                    required
                    value="Nova Consignação #<?php echo $consignacao_id; ?> - <?php echo sanitize($consignacao['estabelecimento']); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                >
            </div>
            
            <!-- Mensagem Adicional -->
            <div>
                <label for="mensagem_adicional" class="block text-sm font-medium text-gray-700 mb-2">
                    💬 Mensagem Adicional (Opcional)
                </label>
                <textarea 
                    id="mensagem_adicional" 
                    name="mensagem_adicional"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Digite uma mensagem personalizada (será incluída no email)"
                ></textarea>
            </div>
            
            <!-- Área de Feedback -->
            <div id="feedback-area" class="hidden">
                <!-- Loader -->
                <div id="loader" class="hidden p-6 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-blue-900">Enviando email...</p>
                            <p class="text-sm text-blue-700">Aguarde, estamos processando o envio.</p>
                            <div class="mt-2 w-full bg-blue-200 rounded-full h-2">
                                <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Erro -->
                <div id="error-message" class="hidden p-6 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="font-semibold text-red-900">❌ Erro ao enviar email</p>
                            <p class="text-sm text-red-700 mt-1" id="error-details">Verifique suas configurações SMTP em config/email.php</p>
                            <ul class="text-xs text-red-600 mt-2 space-y-1">
                                <li>• Verifique se o servidor SMTP está correto</li>
                                <li>• Confirme usuário e senha</li>
                                <li>• Teste a conexão com a internet</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botões -->
            <div class="flex gap-4 pt-4 border-t border-gray-200">
                <a href="<?php echo url('/consignacoes.php?action=view&id=' . $consignacao_id); ?>" id="btn-cancelar" class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" id="btn-enviar" class="flex-1 px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition">
                    📧 Enviar Email
                </button>
            </div>
        </form>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const btnEnviar = document.getElementById('btn-enviar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const feedbackArea = document.getElementById('feedback-area');
            const loader = document.getElementById('loader');
            const errorMessage = document.getElementById('error-message');
            const errorDetails = document.getElementById('error-details');
            const progressBar = document.getElementById('progress-bar');
            
            // Se houver erro PHP, mostrar
            <?php if ($error): ?>
                feedbackArea.classList.remove('hidden');
                errorMessage.classList.remove('hidden');
                errorDetails.textContent = "<?php echo addslashes($error); ?>";
                
                // Reabilitar botões
                btnEnviar.disabled = false;
                btnEnviar.classList.remove('opacity-50', 'cursor-not-allowed');
                btnCancelar.classList.remove('opacity-50', 'pointer-events-none');
            <?php endif; ?>
            
            form.addEventListener('submit', function(e) {
                // Mostrar loader
                feedbackArea.classList.remove('hidden');
                loader.classList.remove('hidden');
                errorMessage.classList.add('hidden');
                
                // Desabilitar botões
                btnEnviar.disabled = true;
                btnEnviar.classList.add('opacity-50', 'cursor-not-allowed');
                btnCancelar.classList.add('opacity-50', 'pointer-events-none');
                
                // Mudar texto do botão
                btnEnviar.innerHTML = '<svg class="animate-spin h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Enviando...';
                
                // Animar barra de progresso
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 5;
                    if (progress <= 90) {
                        progressBar.style.width = progress + '%';
                    }
                }, 200);
                
                // Limpar intervalo após 20 segundos (timeout)
                setTimeout(() => {
                    clearInterval(interval);
                }, 20000);
            });
        });
        </script>
    </div>
    
    <!-- Preview -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24">
            <h3 class="font-semibold text-gray-900 mb-4">📋 Resumo do Email</h3>
            
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-500">Consignação</p>
                    <p class="font-medium text-gray-900">#<?php echo $consignacao_id; ?></p>
                </div>
                
                <div>
                    <p class="text-gray-500">Estabelecimento</p>
                    <p class="font-medium text-gray-900"><?php echo sanitize($consignacao['estabelecimento']); ?></p>
                </div>
                
                <div>
                    <p class="text-gray-500">Total de Produtos</p>
                    <p class="font-medium text-gray-900"><?php echo count($itens); ?> itens</p>
                </div>
                
                <div class="pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-600">
                        ✅ Email com design profissional<br>
                        ✅ Link direto para consulta<br>
                        ✅ Lista completa de produtos<br>
                        ✅ Instruções de acesso
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
