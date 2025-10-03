<?php
/**
 * Configuração de Conexão com Banco de Dados
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

// Configurações do banco de dados
define('DB_HOST', '187.33.241.61');
define('DB_USER', 'amopipocagourmet_consignado');
define('DB_PASS', 'amopipocagourmet_consignado');
define('DB_NAME', 'amopipocagourmet_consignado');
define('DB_CHARSET', 'utf8mb4');

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
