<?php
/**
 * Gerenciamento de Estabelecimentos
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

require_once 'config/config.php';
requireLogin();

$pageTitle = 'Estabelecimentos';
$db = Database::getInstance()->getConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $id = $_POST['id'] ?? null;
        $nome = sanitize($_POST['nome']);
        $responsavel = sanitize($_POST['responsavel']);
        $email = sanitize($_POST['email']);
        $telefone = sanitize($_POST['telefone']);
        $whatsapp = sanitize($_POST['whatsapp']);
        $endereco = sanitize($_POST['endereco']);
        $observacoes = sanitize($_POST['observacoes']);
        $senha_acesso = $_POST['senha_acesso'] ?? '';
        
        try {
            if ($action === 'create') {
                // Gerar token único para o estabelecimento
                $token_acesso = bin2hex(random_bytes(32));
                
                // Hash da senha se foi fornecida
                $senha_hash = !empty($senha_acesso) ? password_hash($senha_acesso, PASSWORD_DEFAULT) : null;
                
                $stmt = $db->prepare("INSERT INTO estabelecimentos (nome, responsavel, email, telefone, whatsapp, senha_acesso, token_acesso, endereco, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $responsavel, $email, $telefone, $whatsapp, $senha_hash, $token_acesso, $endereco, $observacoes]);
                setFlashMessage('success', 'Estabelecimento cadastrado com sucesso!');
            } else {
                // Atualizar senha apenas se foi fornecida uma nova
                if (!empty($senha_acesso)) {
                    $senha_hash = password_hash($senha_acesso, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE estabelecimentos SET nome = ?, responsavel = ?, email = ?, telefone = ?, whatsapp = ?, senha_acesso = ?, endereco = ?, observacoes = ? WHERE id = ?");
                    $stmt->execute([$nome, $responsavel, $email, $telefone, $whatsapp, $senha_hash, $endereco, $observacoes, $id]);
                } else {
                    $stmt = $db->prepare("UPDATE estabelecimentos SET nome = ?, responsavel = ?, email = ?, telefone = ?, whatsapp = ?, endereco = ?, observacoes = ? WHERE id = ?");
                    $stmt->execute([$nome, $responsavel, $email, $telefone, $whatsapp, $endereco, $observacoes, $id]);
                }
                setFlashMessage('success', 'Estabelecimento atualizado com sucesso!');
            }
            redirect('/estabelecimentos.php');
        } catch (PDOException $e) {
            error_log("Erro ao salvar estabelecimento: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao salvar estabelecimento.');
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        try {
            $stmt = $db->prepare("UPDATE estabelecimentos SET ativo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Estabelecimento removido com sucesso!');
            redirect('/estabelecimentos.php');
        } catch (PDOException $e) {
            error_log("Erro ao remover estabelecimento: " . $e->getMessage());
            setFlashMessage('error', 'Erro ao remover estabelecimento.');
        }
    }
}

// Buscar estabelecimentos
$search = $_GET['search'] ?? '';
$whereClause = "WHERE e.ativo = 1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (e.nome LIKE ? OR e.responsavel LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("
    SELECT 
        e.*,
        COUNT(DISTINCT c.id) as total_consignacoes,
        COUNT(DISTINCT CASE WHEN c.status IN ('pendente', 'parcial') THEN c.id END) as consignacoes_ativas
    FROM estabelecimentos e
    LEFT JOIN consignacoes c ON e.id = c.estabelecimento_id
    $whereClause
    GROUP BY e.id
    ORDER BY e.nome ASC
");
$stmt->execute($params);
$estabelecimentos = $stmt->fetchAll();

// Se for edição, buscar estabelecimento específico
$editEstabelecimento = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM estabelecimentos WHERE id = ? AND ativo = 1");
    $stmt->execute([$_GET['id']]);
    $editEstabelecimento = $stmt->fetch();
}

$showForm = isset($_GET['action']) && ($_GET['action'] === 'new' || $_GET['action'] === 'edit');

include 'includes/header.php';
?>

<!-- Biblioteca intl-tel-input para seletor de país com bandeiras -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.css">
<style>
    .iti { width: 100%; }
    .iti__flag-container { z-index: 10; }
</style>

<!-- Page Header -->
<div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Estabelecimentos</h1>
        <p class="text-gray-600 mt-1">Gerencie os estabelecimentos parceiros</p>
    </div>
    <?php if (!$showForm): ?>
        <a href="?action=new" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition shadow-md">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Novo Estabelecimento
        </a>
    <?php endif; ?>
</div>

<?php if ($showForm): ?>
    <!-- Formulário de Estabelecimento -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">
                <?php echo $editEstabelecimento ? 'Editar Estabelecimento' : 'Novo Estabelecimento'; ?>
            </h2>
            <a href="<?php echo url('/estabelecimentos.php'); ?>" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </a>
        </div>

        <form method="POST" action="<?php echo url('/estabelecimentos.php'); ?>" class="space-y-6">
            <input type="hidden" name="action" value="<?php echo $editEstabelecimento ? 'update' : 'create'; ?>">
            <?php if ($editEstabelecimento): ?>
                <input type="hidden" name="id" value="<?php echo $editEstabelecimento['id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome -->
                <div class="md:col-span-2">
                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome do Estabelecimento *
                    </label>
                    <input 
                        type="text" 
                        id="nome" 
                        name="nome" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo $editEstabelecimento ? sanitize($editEstabelecimento['nome']) : ''; ?>"
                        placeholder="Ex: Padaria do João"
                    >
                </div>

                <!-- Responsável -->
                <div>
                    <label for="responsavel" class="block text-sm font-medium text-gray-700 mb-2">
                        Responsável
                    </label>
                    <input 
                        type="text" 
                        id="responsavel" 
                        name="responsavel"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo $editEstabelecimento && !empty($editEstabelecimento['responsavel']) ? sanitize($editEstabelecimento['responsavel']) : ''; ?>"
                        placeholder="Nome do responsável"
                    >
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        📧 Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo $editEstabelecimento && !empty($editEstabelecimento['email']) ? sanitize($editEstabelecimento['email']) : ''; ?>"
                        placeholder="contato@estabelecimento.com"
                    >
                    <p class="text-xs text-gray-500 mt-1">
                        💡 Usado para enviar notificações de consignações
                    </p>
                </div>

                <!-- Telefone -->
                <div>
                    <label for="telefone" class="block text-sm font-medium text-gray-700 mb-2">
                        📞 Telefone
                    </label>
                    <input 
                        type="text" 
                        id="telefone" 
                        name="telefone"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo $editEstabelecimento && !empty($editEstabelecimento['telefone']) ? sanitize($editEstabelecimento['telefone']) : ''; ?>"
                        placeholder="(11) 98765-4321"
                    >
                </div>

                <!-- WhatsApp com seletor de país -->
                <div>
                    <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-2">
                        💬 WhatsApp
                        <span class="text-xs font-normal text-gray-500 ml-2">(Com código do país)</span>
                    </label>
                    <input 
                        type="tel" 
                        id="whatsapp" 
                        name="whatsapp"
                        class="w-full px-4 py-2 border border-green-300 bg-green-50 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        value="<?php echo $editEstabelecimento && !empty($editEstabelecimento['whatsapp']) ? sanitize($editEstabelecimento['whatsapp']) : ''; ?>"
                        placeholder="11 98765-4321"
                    >
                    <input type="hidden" id="whatsapp_full" name="whatsapp_full">
                    <p class="text-xs text-gray-500 mt-1">
                        💡 Selecione o país pela bandeira e digite o número
                    </p>
                </div>

                <!-- Endereço -->
                <div class="md:col-span-2">
                    <label for="endereco" class="block text-sm font-medium text-gray-700 mb-2">
                        Endereço
                    </label>
                    <input 
                        type="text" 
                        id="endereco" 
                        name="endereco"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        value="<?php echo $editEstabelecimento && !empty($editEstabelecimento['endereco']) ? sanitize($editEstabelecimento['endereco']) : ''; ?>"
                        placeholder="Rua, número, bairro, cidade"
                    >
                </div>

                <!-- Senha de Acesso Público -->
                <div class="md:col-span-2">
                    <label for="senha_acesso" class="block text-sm font-medium text-gray-700 mb-2">
                        🔐 Senha de Acesso Público
                        <span class="text-xs text-gray-500 font-normal ml-2">(Para consulta pública de consignações)</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="senha_acesso" 
                            name="senha_acesso"
                            class="w-full px-4 py-2 border border-purple-300 bg-purple-50 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="<?php echo $editEstabelecimento ? 'Deixe em branco para manter a senha atual' : 'Digite uma senha para o cliente acessar'; ?>"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                        >
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">
                        💡 Esta senha permitirá que o estabelecimento visualize suas consignações através de um link público.
                    </p>
                </div>

                <!-- Observações -->
                <div class="md:col-span-2">
                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-2">
                        Observações
                    </label>
                    <textarea 
                        id="observacoes" 
                        name="observacoes" 
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Informações adicionais sobre o estabelecimento"
                    ><?php echo $editEstabelecimento && !empty($editEstabelecimento['observacoes']) ? sanitize($editEstabelecimento['observacoes']) : ''; ?></textarea>
                </div>
            </div>
            
            <script>
            function togglePassword() {
                const input = document.getElementById('senha_acesso');
                const icon = document.getElementById('eye-icon');
                if (input.type === 'password') {
                    input.type = 'text';
                } else {
                    input.type = 'password';
                }
            }
            
            // Máscaras de telefone (Brasil) - apenas para campo telefone
            function mascaraTelefone(input) {
                let valor = input.value.replace(/\D/g, '');
                
                if (valor.length <= 10) {
                    // Telefone fixo: (11) 1234-5678
                    valor = valor.replace(/^(\d{2})(\d)/g, '($1) $2');
                    valor = valor.replace(/(\d)(\d{4})$/, '$1-$2');
                } else {
                    // Celular: (11) 98765-4321
                    valor = valor.replace(/^(\d{2})(\d)/g, '($1) $2');
                    valor = valor.replace(/(\d)(\d{4})$/, '$1-$2');
                }
                
                input.value = valor;
            }
            
            // Aplicar máscaras
            document.addEventListener('DOMContentLoaded', function() {
                const telefone = document.getElementById('telefone');
                
                // Máscara apenas para telefone
                if (telefone) {
                    telefone.addEventListener('input', function() {
                        mascaraTelefone(this);
                    });
                }
            });
            </script>

            <!-- Botões -->
            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="/estabelecimentos.php" class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition">
                    <?php echo $editEstabelecimento ? 'Atualizar' : 'Cadastrar'; ?>
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Barra de Busca -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" action="<?php echo url('/estabelecimentos.php'); ?>" class="flex gap-4">
            <div class="flex-1">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Buscar por nome ou responsável..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    value="<?php echo sanitize($search); ?>"
                >
            </div>
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition">
                Buscar
            </button>
            <?php if (!empty($search)): ?>
                <a href="/estabelecimentos.php" class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Limpar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Lista de Estabelecimentos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($estabelecimentos)): ?>
            <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <p class="text-gray-500 mb-4">Nenhum estabelecimento encontrado</p>
                <a href="?action=new" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Cadastrar Primeiro Estabelecimento
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($estabelecimentos as $estab): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1"><?php echo sanitize($estab['nome']); ?></h3>
                                <?php if (!empty($estab['responsavel'])): ?>
                                    <p class="text-sm text-gray-600">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <?php echo sanitize($estab['responsavel']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>

                        <?php if (!empty($estab['telefone'])): ?>
                            <p class="text-sm text-gray-600 mb-2">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <?php echo formatPhone($estab['telefone']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($estab['endereco'])): ?>
                            <p class="text-sm text-gray-600 mb-4">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <?php echo sanitize($estab['endereco']); ?>
                            </p>
                        <?php endif; ?>

                        <div class="flex gap-4 pt-4 border-t border-gray-200">
                            <div class="text-center flex-1">
                                <p class="text-2xl font-bold text-gray-900"><?php echo $estab['total_consignacoes']; ?></p>
                                <p class="text-xs text-gray-500">Total</p>
                            </div>
                            <div class="text-center flex-1">
                                <p class="text-2xl font-bold text-purple-600"><?php echo $estab['consignacoes_ativas']; ?></p>
                                <p class="text-xs text-gray-500">Ativas</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex gap-2">
                        <a href="?action=edit&id=<?php echo $estab['id']; ?>" class="flex-1 text-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-100 transition">
                            Editar
                        </a>
                        <button onclick="confirmDelete(<?php echo $estab['id']; ?>, '<?php echo addslashes($estab['nome']); ?>')" class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                            Excluir
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Form de Delete (oculto) -->
<form id="deleteForm" method="POST" action="<?php echo url('/estabelecimentos.php'); ?>" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<!-- Biblioteca intl-tel-input JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>

<script>
function confirmDelete(id, nome) {
    if (confirm('Tem certeza que deseja excluir o estabelecimento "' + nome + '"?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Inicializar intl-tel-input no campo WhatsApp
document.addEventListener('DOMContentLoaded', function() {
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
        const currentValue = whatsappInput.value;
        if (currentValue) {
            iti.setNumber(currentValue);
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
