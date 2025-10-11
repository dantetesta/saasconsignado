<?php
/**
 * Página de Conta Bloqueada
 * Exibe informações quando uma conta está bloqueada/suspensa
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.1.0
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se há informações de conta bloqueada na sessão
if (!isset($_SESSION['blocked_account'])) {
    header('Location: ' . url('/login.php'));
    exit;
}

$blockedInfo = $_SESSION['blocked_account'];
unset($_SESSION['blocked_account']); // Limpar da sessão após usar

// Buscar informações de contato do sistema
$db = Database::getInstance()->getConnection();
$stmt = $db->query("
    SELECT chave, valor 
    FROM system_settings 
    WHERE chave IN ('admin_email', 'admin_whatsapp', 'admin_phone', 'company_name')
");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['chave']] = $row['valor'];
}

// Valores padrão caso não existam nas configurações
$adminEmail = $settings['admin_email'] ?? 'admin@sisteminha.com.br';
$adminWhatsApp = $settings['admin_whatsapp'] ?? '5511999999999';
$adminPhone = $settings['admin_phone'] ?? '(11) 99999-9999';
$companyName = $settings['company_name'] ?? 'SaaS Sisteminha';

$pageTitle = 'Conta Bloqueada';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo $companyName; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 min-h-screen">

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            
            <!-- Logo/Header -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-gradient-to-r from-red-500 to-orange-500 rounded-full flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">Acesso Restrito</h2>
                <p class="mt-2 text-sm text-gray-600">Sua conta precisa de atenção</p>
            </div>

            <!-- Card Principal -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                
                <!-- Status da Conta -->
                <div class="bg-gradient-to-r from-red-50 to-orange-50 border-b border-red-200 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-red-900">Status da Conta</h3>
                            <p class="text-sm text-red-700 capitalize"><?php echo $blockedInfo['status']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Informações -->
                <div class="p-6 space-y-6">
                    
                    <!-- Mensagem Principal -->
                    <div class="text-center">
                        <h4 class="text-xl font-semibold text-gray-900 mb-2">
                            Olá, <?php echo htmlspecialchars($blockedInfo['tenant_name']); ?>
                        </h4>
                        <p class="text-gray-700 mb-4">
                            <?php echo htmlspecialchars($blockedInfo['message']); ?>
                        </p>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm text-yellow-800">
                                <strong>Para reativar sua conta,</strong> entre em contato conosco através dos canais abaixo. Nossa equipe está pronta para ajudar!
                            </p>
                        </div>
                    </div>

                    <!-- Canais de Contato -->
                    <div class="space-y-4">
                        <h5 class="font-semibold text-gray-900 text-center">📞 Entre em Contato</h5>
                        
                        <!-- WhatsApp -->
                        <a href="https://wa.me/<?php echo $adminWhatsApp; ?>?text=Olá! Minha conta <?php echo urlencode($blockedInfo['tenant_name']); ?> foi bloqueada e preciso de ajuda para reativar." 
                           target="_blank"
                           class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition group">
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-green-800">WhatsApp</p>
                                <p class="text-sm text-green-600"><?php echo $adminPhone; ?></p>
                            </div>
                            <svg class="w-5 h-5 text-green-600 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>

                        <!-- Email -->
                        <a href="mailto:<?php echo $adminEmail; ?>?subject=Conta Bloqueada - <?php echo urlencode($blockedInfo['tenant_name']); ?>&body=Olá,%0A%0AMinha conta <?php echo urlencode($blockedInfo['tenant_name']); ?> foi bloqueada e preciso de ajuda para reativar.%0A%0AStatus: <?php echo urlencode($blockedInfo['status']); ?>%0A%0AAguardo retorno.%0A%0AObrigado!" 
                           class="flex items-center gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition group">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-blue-800">E-mail</p>
                                <p class="text-sm text-blue-600"><?php echo $adminEmail; ?></p>
                            </div>
                            <svg class="w-5 h-5 text-blue-600 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    </div>

                    <!-- Informações Adicionais -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h6 class="font-medium text-gray-900 mb-2">ℹ️ Informações Importantes</h6>
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li>• Respondemos em até 24 horas</li>
                            <li>• Tenha em mãos seus dados de cadastro</li>
                            <li>• WhatsApp é o canal mais rápido</li>
                            <li>• Horário de atendimento: 8h às 18h</li>
                        </ul>
                    </div>

                </div>
            </div>

            <!-- Voltar ao Login -->
            <div class="text-center">
                <a href="<?php echo url('/login.php'); ?>" 
                   class="inline-flex items-center gap-2 text-blue-600 hover:text-purple-800 font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar ao Login
                </a>
            </div>

        </div>
    </div>

</body>
</html>
