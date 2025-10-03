<?php
/**
 * Perfil do Usuário
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

require_once 'config/config.php';
requireLogin();

$pageTitle = 'Meu Perfil';
$db = Database::getInstance()->getConnection();

// Processar atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $nome = sanitize($_POST['nome']);
        $email = sanitize($_POST['email']);
        $nome_empresa = sanitize($_POST['nome_empresa']);
        $documento = sanitize($_POST['documento']);
        $email_remetente = sanitize($_POST['email_remetente']);
        $whatsapp = sanitize($_POST['whatsapp']);
        
        try {
            $stmt = $db->prepare("UPDATE usuarios SET nome = ?, email = ?, nome_empresa = ?, documento = ?, email_remetente = ?, whatsapp = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $nome_empresa, $documento, $email_remetente, $whatsapp, $_SESSION['user_id']]);
            
            $_SESSION['user_name'] = $nome;
            $_SESSION['user_email'] = $email;
            
            setFlashMessage('success', 'Perfil atualizado com sucesso!');
            redirect('/perfil.php');
        } catch (PDOException $e) {
            error_log("Erro ao atualizar perfil: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao atualizar perfil.');
        }
    } elseif ($action === 'upload_logo') {
        // Receber imagem base64 do cropper
        $logoData = $_POST['logo_data'] ?? '';
        
        if (empty($logoData)) {
            setFlashMessage('error', 'Nenhuma imagem selecionada.');
            redirect('/perfil.php');
        }
        
        try {
            // Decodificar base64
            $logoData = str_replace('data:image/png;base64,', '', $logoData);
            $logoData = str_replace(' ', '+', $logoData);
            $imageData = base64_decode($logoData);
            
            // Gerar nome único
            $fileName = 'logo_' . $_SESSION['user_id'] . '_' . time() . '.png';
            $uploadDir = __DIR__ . '/uploads/logos/';
            
            // Criar diretório se não existir
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Salvar arquivo
            $filePath = $uploadDir . $fileName;
            file_put_contents($filePath, $imageData);
            
            // Deletar logo anterior se existir
            $stmt = $db->prepare("SELECT logo FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $oldLogo = $stmt->fetchColumn();
            
            if ($oldLogo && file_exists(__DIR__ . '/' . $oldLogo)) {
                unlink(__DIR__ . '/' . $oldLogo);
            }
            
            // Atualizar banco
            $logoPath = 'uploads/logos/' . $fileName;
            $stmt = $db->prepare("UPDATE usuarios SET logo = ? WHERE id = ?");
            $stmt->execute([$logoPath, $_SESSION['user_id']]);
            
            setFlashMessage('success', 'Logo atualizado com sucesso!');
            redirect('/perfil.php');
        } catch (Exception $e) {
            error_log("Erro ao fazer upload do logo: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao fazer upload do logo.');
        }
    } elseif ($action === 'change_password') {
        $senha_atual = $_POST['senha_atual'];
        $senha_nova = $_POST['senha_nova'];
        $senha_confirma = $_POST['senha_confirma'];
        
        if ($senha_nova !== $senha_confirma) {
            setFlashMessage('error', 'As senhas não coincidem.');
        } else {
            try {
                // Verificar senha atual
                $stmt = $db->prepare("SELECT senha FROM usuarios WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (password_verify($senha_atual, $user['senha'])) {
                    $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                    $stmt->execute([$senha_hash, $_SESSION['user_id']]);
                    
                    setFlashMessage('success', 'Senha alterada com sucesso!');
                    redirect('/perfil.php');
                } else {
                    setFlashMessage('error', 'Senha atual incorreta.');
                }
            } catch (PDOException $e) {
                error_log("Erro ao alterar senha: " . $e->getMessage());
                setFlashMessage('error', 'Erro ao alterar senha.');
            }
        }
    }
}

// Buscar dados do usuário
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

// Verificar se encontrou o usuário
if (!$usuario) {
    session_destroy();
    redirect('/login.php');
}

include 'includes/header.php';
?>

<!-- Biblioteca intl-tel-input para seletor de país com bandeiras -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.css">
<style>
    .iti { width: 100%; }
    .iti__flag-container { z-index: 10; }
</style>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Meu Perfil</h1>
    <p class="text-gray-600 mt-1">Gerencie suas informações pessoais e senha</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informações do Perfil -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Dados Pessoais -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Dados Pessoais</h2>
            
            <form method="POST" action="<?php echo url('/perfil.php'); ?>" class="space-y-4">
                <input type="hidden" name="action" value="update_profile">
                
                <div>
                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome Completo *
                    </label>
                    <input 
                        type="text" 
                        id="nome" 
                        name="nome" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo sanitize($usuario['nome']); ?>"
                    >
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email *
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo sanitize($usuario['email']); ?>"
                    >
                </div>

                <div>
                    <label for="nome_empresa" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome da Empresa *
                    </label>
                    <input 
                        type="text" 
                        id="nome_empresa" 
                        name="nome_empresa" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo sanitize($usuario['nome_empresa'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label for="documento" class="block text-sm font-medium text-gray-700 mb-2">
                        CPF/CNPJ
                    </label>
                    <input 
                        type="text" 
                        id="documento" 
                        name="documento" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo sanitize($usuario['documento'] ?? ''); ?>"
                        placeholder="000.000.000-00 ou 00.000.000/0000-00"
                    >
                </div>

                <div>
                    <label for="email_remetente" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Remetente (para envio de emails)
                    </label>
                    <input 
                        type="email" 
                        id="email_remetente" 
                        name="email_remetente" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo sanitize($usuario['email_remetente'] ?? ''); ?>"
                        placeholder="contato@suaempresa.com"
                    >
                    <p class="text-xs text-gray-500 mt-1">Este email será usado como remetente nos emails enviados pelo sistema</p>
                </div>

                <div>
                    <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-2">
                        WhatsApp
                    </label>
                    <input 
                        type="tel" 
                        id="whatsapp" 
                        name="whatsapp" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo sanitize($usuario['whatsapp'] ?? ''); ?>"
                    >
                    <p class="text-xs text-gray-500 mt-1">Será exibido na página pública de consulta</p>
                </div>

                <div class="pt-4">
                    <button type="submit" class="px-6 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>

        <!-- Alterar Senha -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Alterar Senha</h2>
            
            <form method="POST" action="<?php echo url('/perfil.php'); ?>" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                
                <div>
                    <label for="senha_atual" class="block text-sm font-medium text-gray-700 mb-2">
                        Senha Atual *
                    </label>
                    <input 
                        type="password" 
                        id="senha_atual" 
                        name="senha_atual" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    >
                </div>

                <div>
                    <label for="senha_nova" class="block text-sm font-medium text-gray-700 mb-2">
                        Nova Senha *
                    </label>
                    <input 
                        type="password" 
                        id="senha_nova" 
                        name="senha_nova" 
                        required
                        minlength="6"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    >
                    <p class="text-xs text-gray-500 mt-1">Mínimo de 6 caracteres</p>
                </div>

                <div>
                    <label for="senha_confirma" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirmar Nova Senha *
                    </label>
                    <input 
                        type="password" 
                        id="senha_confirma" 
                        name="senha_confirma" 
                        required
                        minlength="6"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    >
                </div>

                <div class="pt-4">
                    <button type="submit" class="px-6 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition">
                        Alterar Senha
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logo da Empresa -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Logo da Empresa</h2>
            
            <div class="text-center mb-6">
                <?php if (!empty($usuario['logo'])): ?>
                    <img src="<?php echo url('/' . $usuario['logo']); ?>" alt="Logo" class="w-32 h-32 mx-auto rounded-lg object-cover border-2 border-gray-200">
                <?php else: ?>
                    <div class="w-32 h-32 bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg flex items-center justify-center mx-auto">
                        <span class="text-white text-5xl font-bold">
                            <?php echo strtoupper(substr($usuario['nome_empresa'] ?? $usuario['nome'], 0, 1)); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="space-y-4">
                <input type="file" id="logoInput" accept="image/*" class="hidden">
                <button onclick="document.getElementById('logoInput').click()" class="w-full px-4 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition">
                    <?php echo !empty($usuario['logo']) ? 'Alterar Logo' : 'Adicionar Logo'; ?>
                </button>
                <p class="text-xs text-gray-500 text-center">Formato quadrado 500x500px</p>
            </div>

            <div class="space-y-3 pt-6 mt-6 border-t border-gray-200">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Membro desde:</span>
                    <span class="font-medium text-gray-900"><?php echo formatDate($usuario['criado_em']); ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Última atualização:</span>
                    <span class="font-medium text-gray-900"><?php echo formatDate($usuario['atualizado_em']); ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                        Ativo
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cropper -->
<div id="cropperModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Ajustar Logo</h3>
            <button onclick="fecharCropper()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="mb-4">
            <div class="max-h-96 overflow-hidden">
                <img id="imageToCrop" class="max-w-full">
            </div>
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="fecharCropper()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Cancelar
            </button>
            <button onclick="salvarLogo()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                Salvar Logo
            </button>
        </div>
    </div>
</div>

<!-- Cropper.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<script>
let cropper = null;

document.getElementById('logoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(event) {
        const img = document.getElementById('imageToCrop');
        img.src = event.target.result;
        
        document.getElementById('cropperModal').classList.remove('hidden');
        
        if (cropper) {
            cropper.destroy();
        }
        
        cropper = new Cropper(img, {
            aspectRatio: 1,
            viewMode: 1,
            minCropBoxWidth: 200,
            minCropBoxHeight: 200,
            autoCropArea: 1,
            responsive: true,
            guides: true,
            center: true,
            highlight: true,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
        });
    };
    reader.readAsDataURL(file);
});

function fecharCropper() {
    document.getElementById('cropperModal').classList.add('hidden');
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    document.getElementById('logoInput').value = '';
}

function salvarLogo() {
    if (!cropper) return;
    
    const canvas = cropper.getCroppedCanvas({
        width: 500,
        height: 500,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });
    
    canvas.toBlob(function(blob) {
        const reader = new FileReader();
        reader.onloadend = function() {
            const base64data = reader.result;
            
            // Enviar para o servidor
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo url('/perfil.php'); ?>';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'upload_logo';
            
            const logoInput = document.createElement('input');
            logoInput.type = 'hidden';
            logoInput.name = 'logo_data';
            logoInput.value = base64data;
            
            form.appendChild(actionInput);
            form.appendChild(logoInput);
            document.body.appendChild(form);
            form.submit();
        };
        reader.readAsDataURL(blob);
    });
}

// Máscara para documento
const documentoInput = document.getElementById('documento');

if (documentoInput) {
    documentoInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length <= 11) {
            // CPF: 000.000.000-00
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
            // CNPJ: 00.000.000/0000-00
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        }
        
        e.target.value = value;
    });
}
</script>

<!-- Biblioteca intl-tel-input JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>

<script>
// Inicializar intl-tel-input no campo WhatsApp
const whatsappInput = document.getElementById('whatsapp');

if (whatsappInput) {
    // Inicializar com bandeiras e seletor de país
    const iti = window.intlTelInput(whatsappInput, {
        initialCountry: "br", // Brasil como padrão
        preferredCountries: ["br", "us", "pt", "ar", "cl"], // Países preferidos
        separateDialCode: true, // Mostrar código do país separado
        nationalMode: false,
        autoPlaceholder: "aggressive",
        formatOnDisplay: true,
        customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
            return "Ex: " + selectedCountryPlaceholder;
        },
        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"
    });
    
    // Ao enviar o formulário, pegar número completo
    const form = whatsappInput.closest('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Pegar número completo com código do país
            const fullNumber = iti.getNumber();
            whatsappInput.value = fullNumber;
        });
    }
    
    // Se já tiver valor (edição), configurar
    if (whatsappInput.value) {
        iti.setNumber(whatsappInput.value);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
