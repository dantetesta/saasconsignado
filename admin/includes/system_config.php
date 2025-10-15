<?php
/**
 * Configurações do Sistema
 * Carrega configurações dinâmicas do banco de dados
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

class SystemConfig {
    private static $config = null;
    
    /**
     * Carregar todas as configurações do sistema
     */
    public static function load() {
        if (self::$config !== null) {
            return self::$config;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT chave, valor FROM system_settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            self::$config = $settings;
            return self::$config;
            
        } catch (Exception $e) {
            // Fallback para valores padrão
            self::$config = [
                'sistema_nome' => 'SaaS Sisteminha',
                'sistema_logotipo' => ''
            ];
            return self::$config;
        }
    }
    
    /**
     * Obter uma configuração específica
     */
    public static function get($key, $default = null) {
        $config = self::load();
        return $config[$key] ?? $default;
    }
    
    /**
     * Obter nome do sistema
     */
    public static function getSystemName() {
        return self::get('sistema_nome', 'SaaS Sisteminha');
    }
    
    /**
     * Obter logotipo do sistema
     */
    public static function getSystemLogo() {
        return self::get('sistema_logotipo', '');
    }
    
    /**
     * Verificar se tem logotipo configurado
     */
    public static function hasLogo() {
        $logo = self::getSystemLogo();
        return !empty($logo) && file_exists('../' . $logo);
    }
}
