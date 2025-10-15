<?php
/**
 * Configuração de Conexão com Banco de Dados
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.1.0 - Implementação de variáveis de ambiente
 */

// Carregar classe EnvLoader
require_once __DIR__ . '/../classes/EnvLoader.php';

// Carregar variáveis de ambiente
EnvLoader::load();

// Validar variáveis obrigatórias
$required = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'];
$missing = EnvLoader::validateRequired($required);

if (!empty($missing)) {
    error_log("Variáveis de ambiente faltando: " . implode(', ', $missing));
    die("Erro de configuração: Variáveis de ambiente não encontradas. Verifique o arquivo .env");
}

// Configurações do banco de dados usando variáveis de ambiente
define('DB_HOST', EnvLoader::get('DB_HOST', 'localhost'));
define('DB_USER', EnvLoader::get('DB_USER', ''));
define('DB_PASS', EnvLoader::get('DB_PASS', ''));
define('DB_NAME', EnvLoader::get('DB_NAME', ''));
define('DB_CHARSET', EnvLoader::get('DB_CHARSET', 'utf8mb4'));

/**
 * Classe de conexão com o banco de dados usando PDO
 */
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Erro de conexão: " . $e->getMessage());
            die("Erro ao conectar com o banco de dados. Tente novamente mais tarde.");
        }
    }
    
    /**
     * Retorna a instância única da classe (Singleton)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Retorna a conexão PDO
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Previne clonagem da instância
     */
    private function __clone() {}
    
    /**
     * Previne deserialização da instância
     */
    public function __wakeup() {
        throw new Exception("Não é possível deserializar um singleton.");
    }
}
