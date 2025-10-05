<?php
/**
 * Configurações de Segurança
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

// ============================================
// AMBIENTE
// ============================================

// Detectar ambiente (local vs produção)
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
           strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1:') === 0;

define('IS_LOCAL', $isLocal);
define('IS_PRODUCTION', !$isLocal);

// ============================================
// CLOUDFLARE TURNSTILE (Apenas Produção)
// ============================================

define('TURNSTILE_ENABLED', IS_PRODUCTION);
define('TURNSTILE_SITE_KEY', '0x4AAAAAAB46BTyXNMqGjT81');
define('TURNSTILE_SECRET_KEY', '0x4AAAAAAB46BRmKxNjsji2mSoclu37mcaw');

// ============================================
// HEADERS DE SEGURANÇA
// ============================================
// Nota: Configurações de sessão foram movidas para config.php
// (devem ser definidas ANTES de session_start())

// UTF-8 (DEVE SER O PRIMEIRO HEADER)
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    
    // Forçar recarregamento sem cache (temporário para corrigir encoding)
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}

// Prevenir XSS
header("X-XSS-Protection: 1; mode=block");

// Prevenir clickjacking
header("X-Frame-Options: SAMEORIGIN");

// Prevenir MIME sniffing
header("X-Content-Type-Options: nosniff");

// Referrer Policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.tailwindcss.com challenges.cloudflare.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self' challenges.cloudflare.com;");

// HSTS (apenas em produção com HTTPS)
if (IS_PRODUCTION && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// ============================================
// PROTEÇÃO CSRF
// ============================================

/**
 * Gerar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Campo hidden CSRF para formulários
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Verificar CSRF em POST
 */
function checkCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            die('❌ Token CSRF inválido. Recarregue a página e tente novamente.');
        }
    }
}

// ============================================
// RATE LIMITING
// ============================================

/**
 * Verificar rate limit
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
    $db = Database::getInstance()->getConnection();
    
    // Criar tabela se não existir
    $db->exec("
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_identifier (identifier, attempted_at)
        ) ENGINE=InnoDB
    ");
    
    // Limpar registros antigos
    $db->exec("DELETE FROM rate_limits WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    
    // Verificar tentativas
    $stmt = $db->prepare("
        SELECT COUNT(*) as attempts 
        FROM rate_limits 
        WHERE identifier = ? 
        AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$identifier, $timeWindow]);
    $result = $stmt->fetch();
    
    return $result['attempts'] < $maxAttempts;
}

/**
 * Registrar tentativa
 */
function recordAttempt($identifier) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO rate_limits (identifier, ip_address) 
        VALUES (?, ?)
    ");
    $stmt->execute([$identifier, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
}

// ============================================
// CLOUDFLARE TURNSTILE
// ============================================

/**
 * Validar Turnstile (apenas produção)
 */
function validateTurnstile($token) {
    if (!TURNSTILE_ENABLED) {
        return true; // Pular em ambiente local
    }
    
    if (empty($token)) {
        return false;
    }
    
    $data = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return isset($result['success']) && $result['success'] === true;
}

/**
 * HTML do Turnstile (apenas produção)
 */
function turnstileWidget() {
    if (!TURNSTILE_ENABLED) {
        return ''; // Não mostrar em local
    }
    
    return '
        <div class="cf-turnstile mb-4" data-sitekey="' . TURNSTILE_SITE_KEY . '" data-theme="light"></div>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    ';
}

// ============================================
// VALIDAÇÃO DE SESSÃO SEGURA
// ============================================

/**
 * Validar integridade da sessão
 */
function validateSession() {
    // Verificar se sessão tem fingerprint
    if (!isset($_SESSION['user_fingerprint'])) {
        return true; // Primeira vez, criar fingerprint
    }
    
    // Criar fingerprint atual
    $currentFingerprint = md5(
        $_SERVER['HTTP_USER_AGENT'] ?? '' .
        $_SERVER['REMOTE_ADDR'] ?? ''
    );
    
    // Comparar
    if ($_SESSION['user_fingerprint'] !== $currentFingerprint) {
        // Possível session hijacking
        session_destroy();
        return false;
    }
    
    return true;
}

/**
 * Criar fingerprint da sessão
 */
function createSessionFingerprint() {
    $_SESSION['user_fingerprint'] = md5(
        $_SERVER['HTTP_USER_AGENT'] ?? '' .
        $_SERVER['REMOTE_ADDR'] ?? ''
    );
}

// ============================================
// SANITIZAÇÃO AVANÇADA
// ============================================

/**
 * Sanitizar input por tipo
 */
function sanitizeInput($value, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($value, FILTER_SANITIZE_EMAIL);
        
        case 'int':
            return intval($value);
        
        case 'float':
            return floatval($value);
        
        case 'url':
            return filter_var($value, FILTER_SANITIZE_URL);
        
        case 'string':
        default:
            return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validar força da senha
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Senha deve ter no mínimo 8 caracteres';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Senha deve conter pelo menos uma letra maiúscula';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Senha deve conter pelo menos uma letra minúscula';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Senha deve conter pelo menos um número';
    }
    
    // Lista de senhas comuns (top 100)
    $commonPasswords = ['123456', 'password', '12345678', 'qwerty', '123456789', 'admin', 'admin123'];
    if (in_array(strtolower($password), $commonPasswords)) {
        $errors[] = 'Senha muito comum. Escolha uma senha mais segura';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// ============================================
// CONFIGURAÇÃO DE ERROS (Produção vs Local)
// ============================================

if (IS_PRODUCTION) {
    // Produção: Ocultar erros
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
} else {
    // Local: Mostrar erros para debug
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Sempre logar erros
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errors.log');
