<?php
/**
 * Sistema de Instalação - Consignados
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.2.5
 */

// Verificar se já foi instalado
if (file_exists(__DIR__ . '/.installed')) {
    die('
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema Já Instalado</title>
        <style>
            body { font-family: Arial, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f3f4f6; }
            .container { text-align: center; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            h1 { color: #dc2626; }
            a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Sistema Já Instalado</h1>
            <p>O sistema já foi instalado anteriormente.</p>
            <p>Para reinstalar, delete o arquivo <code>.installed</code> na raiz do projeto.</p>
            <a href="/login.php">Acessar Sistema</a>
        </div>
    </body>
    </html>
    ');
}

// Processar instalação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_nome = $_POST['admin_nome'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_senha = $_POST['admin_senha'] ?? '';
    
    try {
        // Conectar ao MySQL
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Criar banco de dados
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        
        // Executar dump do banco
        $sql = file_get_contents(__DIR__ . '/database_dump.sql');
        $pdo->exec($sql);
        
        // Criar usuário administrador
        $senha_hash = password_hash($admin_senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, ativo) VALUES (?, ?, ?, 1)");
        $stmt->execute([$admin_nome, $admin_email, $senha_hash]);
        
        // Atualizar configuração do banco
        $config_content = "<?php
/**
 * Configuração do Banco de Dados
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.2.5
 */

// Configurações do banco de dados
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_CHARSET', 'utf8mb4');

/**
 * Classe de conexão com o banco de dados usando PDO
 */
class Database {
    private static \$instance = null;
    private \$connection;
    
    private function __construct() {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;
            \$options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES \" . DB_CHARSET
            ];
            
            \$this->connection = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            error_log(\"Erro de conexão: \" . \$e->getMessage());
            die(\"Erro ao conectar com o banco de dados. Tente novamente mais tarde.\");
        }
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->connection;
    }
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception(\"Não é possível deserializar um singleton.\");
    }
}
";
        file_put_contents(__DIR__ . '/config/database.php', $config_content);
        
        // Marcar como instalado
        file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
        
        // Redirecionar para login (ajustado para subpasta)
        header('Location: /controle/login.php?installed=1');
        exit;
        
    } catch (PDOException $e) {
        $error = "Erro na instalação: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema de Consignados</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 600px; width: 100%; padding: 40px; }
        h1 { color: #1f2937; margin-bottom: 10px; font-size: 28px; }
        .subtitle { color: #6b7280; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; color: #374151; font-weight: 500; margin-bottom: 8px; font-size: 14px; }
        input { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #2563eb 0%, #10b981 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .section-title { color: #2563eb; font-weight: 600; margin-top: 30px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f3f4f6; }
        .help-text { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo svg { width: 60px; height: 60px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #2563eb;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
            </svg>
        </div>
        
        <h1>🚀 Instalação do Sistema</h1>
        <p class="subtitle">Configure o banco de dados e crie sua conta de administrador</p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="section-title">📊 Configuração do Banco de Dados</div>
            
            <div class="form-group">
                <label>Host do Banco de Dados</label>
                <input type="text" name="db_host" value="localhost" required>
                <div class="help-text">Geralmente: localhost, 127.0.0.1 ou IP do servidor</div>
            </div>
            
            <div class="form-group">
                <label>Nome do Banco de Dados</label>
                <input type="text" name="db_name" value="consignados" required>
                <div class="help-text">Nome do banco que será criado</div>
            </div>
            
            <div class="form-group">
                <label>Usuário do Banco</label>
                <input type="text" name="db_user" value="root" required>
            </div>
            
            <div class="form-group">
                <label>Senha do Banco</label>
                <input type="password" name="db_pass">
                <div class="help-text">Deixe em branco se não houver senha</div>
            </div>
            
            <div class="section-title">👤 Conta de Administrador</div>
            
            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" name="admin_nome" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="admin_email" required>
            </div>
            
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="admin_senha" minlength="6" required>
                <div class="help-text">Mínimo de 6 caracteres</div>
            </div>
            
            <button type="submit" class="btn">Instalar Sistema</button>
        </form>
    </div>
</body>
</html>
