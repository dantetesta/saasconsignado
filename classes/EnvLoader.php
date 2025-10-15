<?php
/**
 * Carregador de Variáveis de Ambiente
 * 
 * Classe responsável por carregar e gerenciar variáveis de ambiente
 * de forma segura para o sistema SaaS
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.1.0
 */

class EnvLoader 
{
    private static $loaded = false;
    private static $variables = [];
    
    /**
     * Carregar variáveis do arquivo .env
     * 
     * @param string $envFile Caminho para o arquivo .env
     * @return bool
     */
    public static function load($envFile = null) 
    {
        if (self::$loaded) {
            return true;
        }
        
        // Definir caminho padrão do arquivo .env
        if ($envFile === null) {
            $envFile = __DIR__ . '/../.env';
        }
        
        // Verificar se arquivo existe
        if (!file_exists($envFile)) {
            error_log("Arquivo .env não encontrado: $envFile");
            return false;
        }
        
        try {
            // Ler arquivo linha por linha
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Ignorar comentários
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Processar linha no formato CHAVE=VALOR
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remover aspas se existirem
                    if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                        $value = $matches[2];
                    }
                    
                    // Definir variável de ambiente
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                    
                    // Armazenar internamente
                    self::$variables[$key] = $value;
                }
            }
            
            self::$loaded = true;
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao carregar .env: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter valor de variável de ambiente
     * 
     * @param string $key Chave da variável
     * @param mixed $default Valor padrão se não encontrar
     * @return mixed
     */
    public static function get($key, $default = null) 
    {
        // Tentar $_ENV primeiro
        if (isset($_ENV[$key])) {
            return self::parseValue($_ENV[$key]);
        }
        
        // Tentar getenv()
        $value = getenv($key);
        if ($value !== false) {
            return self::parseValue($value);
        }
        
        // Tentar variáveis internas
        if (isset(self::$variables[$key])) {
            return self::parseValue(self::$variables[$key]);
        }
        
        return $default;
    }
    
    /**
     * Verificar se variável existe
     * 
     * @param string $key
     * @return bool
     */
    public static function has($key) 
    {
        return isset($_ENV[$key]) || getenv($key) !== false || isset(self::$variables[$key]);
    }
    
    /**
     * Obter todas as variáveis carregadas
     * 
     * @return array
     */
    public static function all() 
    {
        return self::$variables;
    }
    
    /**
     * Converter string para tipo apropriado
     * 
     * @param string $value
     * @return mixed
     */
    private static function parseValue($value) 
    {
        // Valores booleanos
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }
        
        // Valores nulos
        if (strtolower($value) === 'null') {
            return null;
        }
        
        // Números
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        
        return $value;
    }
    
    /**
     * Validar se todas as variáveis obrigatórias estão definidas
     * 
     * @param array $required Lista de variáveis obrigatórias
     * @return array Lista de variáveis faltando
     */
    public static function validateRequired(array $required) 
    {
        $missing = [];
        
        foreach ($required as $key) {
            if (!self::has($key) || empty(self::get($key))) {
                $missing[] = $key;
            }
        }
        
        return $missing;
    }
    
    /**
     * Gerar arquivo .env baseado no .env.example
     * 
     * @param string $exampleFile
     * @param string $envFile
     * @return bool
     */
    public static function generateFromExample($exampleFile = null, $envFile = null) 
    {
        if ($exampleFile === null) {
            $exampleFile = __DIR__ . '/../.env.example';
        }
        
        if ($envFile === null) {
            $envFile = __DIR__ . '/../.env';
        }
        
        if (!file_exists($exampleFile)) {
            return false;
        }
        
        // Se .env já existe, não sobrescrever
        if (file_exists($envFile)) {
            return false;
        }
        
        return copy($exampleFile, $envFile);
    }
}
