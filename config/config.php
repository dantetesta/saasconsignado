<?php
/**
 * Configurações Gerais do Sistema
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.2.5
 */

// Detectar caminho base automaticamente
try {
    // Pega o diretório do script atual
    $scriptDir = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
    $basePath = ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') ? '' : $scriptDir;
    
    // Define BASE_PATH (pode ser sobrescrito manualmente se necessário)
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', $basePath);
    }
} catch (Exception $e) {
    // Fallback: define vazio se houver erro
    error_log("Erro ao detectar BASE_PATH: " . $e->getMessage());
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', '');
    }
}

// DEBUG: Log do BASE_PATH detectado (comentado para produção)
// error_log("BASE_PATH detectado: " . BASE_PATH);

// Forçar UTF-8 em todo o sistema
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Configurar sessão ANTES de iniciar
if (session_status() === PHP_SESSION_NONE) {
    // Configurações de segurança da sessão
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutos
    ini_set('default_charset', 'UTF-8');
    
    // HTTPS em produção
    $isProduction = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) && 
                    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1:') !== 0;
    if ($isProduction) {
        ini_set('session.cookie_secure', 1);
    }
    
    // Iniciar sessão
    session_start();
}

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Carregar configurações de segurança (CSRF, Rate Limiting, Headers)
require_once __DIR__ . '/security.php';

// Configurações do sistema
define('SITE_NAME', 'Sistema de Consignados');
// SITE_URL será construído dinamicamente com BASE_PATH
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', $protocol . '://' . $host . BASE_PATH);
define('VERSION', '2.0.0 SaaS');

// Configurações de paginação
define('ITEMS_PER_PAGE', 20);

// Inclui o arquivo de conexão com o banco
$database_file = __DIR__ . '/database.php';
if (!file_exists($database_file)) {
    die("ERRO: Arquivo de configuração do banco de dados não encontrado em: $database_file<br>Verifique se o arquivo config/database.php existe.");
}
require_once $database_file;

// Carregar configurações de integrações (Pagou, Postmark)
require_once __DIR__ . '/integrations.php';

// Carregar classe TenantMiddleware para multi-tenancy
require_once __DIR__ . '/../classes/TenantMiddleware.php';

// Inicializar tenant middleware
TenantMiddleware::initialize();

/**
 * Função auxiliar para redirecionar
 * 
 * Gerar URL com BASE_PATH
 */
function url($path = '') {
    return BASE_PATH . $path;
}

/**
 * Redirecionar para uma página
 */
function redirect($url) {
    // Se a URL não começar com http, adiciona o BASE_PATH
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = url($url);
    }
    header("Location: $url");
    exit;
}

/**
 * Função para verificar se o usuário está logado
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Função para verificar autenticação (redireciona se não estiver logado)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
    
    // Verificar se tenant está definido (SaaS)
    if (!TenantMiddleware::hasTenant()) {
        // Tentar recuperar da sessão
        if (isset($_SESSION['tenant_id'])) {
            try {
                $tenantResult = TenantMiddleware::setTenant($_SESSION['tenant_id']);
                
                // Se o tenant está bloqueado, fazer logout
                if (!$tenantResult['success']) {
                    session_destroy();
                    header('Location: ' . url('/login.php?blocked=1'));
                    exit;
                }
            } catch (Exception $e) {
                // Se falhar, fazer logout
                session_destroy();
                redirect('/login.php');
            }
        } else {
            // Sem tenant, fazer logout
            session_destroy();
            redirect('/login.php');
        }
    }
}

/**
 * Função para formatar valor em moeda brasileira
 * 
 * @param float $value Valor a ser formatado
 * @return string
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Função para formatar data brasileira
 * 
 * @param string $date Data no formato Y-m-d
 * @return string Data no formato d/m/Y
 */
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date('d/m/Y', strtotime($date));
}

/**
 * Função para formatar data e hora brasileira
 * 
 * @param string $datetime Data/hora no formato Y-m-d H:i:s
 * @return string Data/hora no formato d/m/Y H:i
 */
function formatDateTime($datetime) {
    if (empty($datetime)) {
        return '-';
    }
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Função para formatar telefone brasileiro
 * 
 * Formata números de telefone no padrão brasileiro:
 * - Celular: (19) 9 9802-1956
 * - Fixo: (19) 3234-5678
 * 
 * @param string $phone Telefone sem formatação
 * @return string Telefone formatado
 */
function formatPhone($phone) {
    if (empty($phone)) {
        return '-';
    }
    
    // Remove tudo que não é número
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Celular com 11 dígitos: (XX) 9 XXXX-XXXX
    if (strlen($phone) == 11) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 1) . ' ' . substr($phone, 3, 4) . '-' . substr($phone, 7, 4);
    }
    // Fixo com 10 dígitos: (XX) XXXX-XXXX
    elseif (strlen($phone) == 10) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
    }
    // Celular com 9 dígitos (sem DDD): 9 XXXX-XXXX
    elseif (strlen($phone) == 9) {
        return substr($phone, 0, 1) . ' ' . substr($phone, 1, 4) . '-' . substr($phone, 5, 4);
    }
    // Fixo com 8 dígitos (sem DDD): XXXX-XXXX
    elseif (strlen($phone) == 8) {
        return substr($phone, 0, 4) . '-' . substr($phone, 4, 4);
    }
    
    // Se não se encaixar em nenhum padrão, retorna como está
    return $phone;
}

/**
 * Função para sanitizar entrada de dados
 * 
 * @param string $data Dados a serem sanitizados
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Função para exibir mensagens flash
 * 
 * @param string $type Tipo da mensagem (success, error, warning, info)
 * @param string $message Mensagem a ser exibida
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Função para obter e limpar mensagem flash
 * 
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Função para traduzir status de consignação
 * 
 * @param string $status Status em inglês
 * @return string Status em português
 */
function translateStatus($status) {
    $translations = [
        'pendente' => 'Pendente',
        'parcial' => 'Parcial',
        'finalizada' => 'Finalizada',
        'cancelada' => 'Cancelada'
    ];
    return $translations[$status] ?? $status;
}

/**
 * Atualizar status automático da consignação
 * 
 * @param PDO $db Conexão com banco
 * @param int $consignacao_id ID da consignação
 */
function atualizarStatusAutomatico($db, $consignacao_id) {
    try {
        // Buscar dados da consignação
        $stmt = $db->prepare("SELECT tipo FROM consignacoes WHERE id = ?");
        $stmt->execute([$consignacao_id]);
        $consignacao = $stmt->fetch();
        
        if (!$consignacao) return;
        
        if ($consignacao['tipo'] === 'continua') {
            // Para contínuas: calcular das movimentações
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN tipo = 'entrega' THEN quantidade ELSE 0 END), 0) as total_entregue,
                    COALESCE(SUM(CASE WHEN tipo = 'venda' THEN quantidade ELSE 0 END), 0) as total_vendido,
                    COALESCE(SUM(CASE WHEN tipo = 'devolucao' THEN quantidade ELSE 0 END), 0) as total_devolvido,
                    COALESCE(SUM(CASE WHEN tipo = 'venda' THEN quantidade * preco_unitario ELSE 0 END), 0) as valor_vendido
                FROM movimentacoes_consignacao
                WHERE consignacao_id = ?
            ");
            $stmt->execute([$consignacao_id]);
            $totais = $stmt->fetch();
            
            $ainda_consignado = $totais['total_entregue'] - $totais['total_vendido'] - $totais['total_devolvido'];
            $valor_vendido = $totais['valor_vendido'];
        } else {
            // Para pontuais: calcular dos itens
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(quantidade_consignada), 0) as total_consignado,
                    COALESCE(SUM(quantidade_vendida), 0) as total_vendido,
                    COALESCE(SUM(quantidade_devolvida), 0) as total_devolvido,
                    COALESCE(SUM(quantidade_vendida * preco_unitario), 0) as valor_vendido
                FROM consignacao_itens
                WHERE consignacao_id = ?
            ");
            $stmt->execute([$consignacao_id]);
            $totais = $stmt->fetch();
            
            $ainda_consignado = $totais['total_consignado'] - $totais['total_vendido'] - $totais['total_devolvido'];
            $valor_vendido = $totais['valor_vendido'];
        }
        
        // Buscar valor pago (do tenant)
        $tenant_id = TenantMiddleware::getTenantId();
        $stmt = $db->prepare("SELECT COALESCE(SUM(valor_pago), 0) as valor_pago FROM pagamentos WHERE consignacao_id = ? AND tenant_id = ?");
        $stmt->execute([$consignacao_id, $tenant_id]);
        $valor_pago = $stmt->fetchColumn();
        
        $saldo_pendente = $valor_vendido - $valor_pago;
        
        // Determinar status baseado no saldo financeiro E produtos no estabelecimento
        $novo_status = 'pendente';
        
        if ($saldo_pendente == 0 && $ainda_consignado == 0 && $valor_vendido > 0) {
            // Saldo zerado E não tem mais produtos no estabelecimento = Finalizada
            $novo_status = 'finalizada';
        } elseif ($valor_vendido > 0 && $saldo_pendente > 0) {
            // Tem vendas mas ainda tem saldo pendente
            $novo_status = 'parcial';
        } elseif ($ainda_consignado > 0 || $valor_vendido == 0) {
            // Ainda tem produtos consignados ou não vendeu nada
            $novo_status = 'pendente';
        }
        
        // Atualizar status
        $stmt = $db->prepare("UPDATE consignacoes SET status = ? WHERE id = ?");
        $stmt->execute([$novo_status, $consignacao_id]);
        
    } catch (PDOException $e) {
        error_log("Erro ao atualizar status automático: " . $e->getMessage());
    }
}

/**
 * Função para obter classe CSS do badge de status
 * 
 * @param string $status Status da consignação
 * @return string Classe CSS
 */
function getStatusBadgeClass($status) {
    $classes = [
        'pendente' => 'bg-yellow-100 text-yellow-800',
        'parcial' => 'bg-blue-100 text-blue-800',
        'finalizada' => 'bg-green-100 text-green-800',
        'cancelada' => 'bg-red-100 text-red-800'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

/**
 * Funções Helper para Multi-Tenancy
 */

/**
 * Obter tenant atual
 * 
 * @return array|null
 */
function getCurrentTenant() {
    return TenantMiddleware::getTenantData();
}

/**
 * Obter ID do tenant atual
 * 
 * @return int|null
 */
function getTenantId() {
    return TenantMiddleware::getTenantId();
}

/**
 * Verificar se está no plano Pro
 * 
 * @return bool
 */
function isProPlan() {
    $tenant = getCurrentTenant();
    return $tenant && $tenant['plano'] === 'pro';
}

/**
 * Verificar limite do plano
 * 
 * @param string $resource Recurso a verificar
 * @param int $current_count Contagem atual
 * @return bool
 */
function checkTenantLimit($resource, $current_count) {
    return TenantMiddleware::checkLimit($resource, $current_count);
}

/**
 * Obter informações do plano
 * 
 * @return array|null
 */
function getPlanInfo() {
    return TenantMiddleware::getPlanInfo();
}
