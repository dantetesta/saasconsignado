<?php
/**
 * Sistema de Branding Global
 * Funções para carregar configurações de branding em qualquer parte do sistema
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

class SystemBranding {
    private static $config = null;
    
    /**
     * Carregar configurações do sistema
     */
    public static function load() {
        if (self::$config !== null) {
            return self::$config;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT chave, valor FROM system_settings WHERE chave IN ('sistema_nome', 'sistema_logotipo')");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            self::$config = [
                'nome' => $settings['sistema_nome'] ?? 'SaaS Sisteminha',
                'logotipo' => $settings['sistema_logotipo'] ?? '',
                'has_logo' => !empty($settings['sistema_logotipo']) && file_exists(__DIR__ . '/../' . $settings['sistema_logotipo'])
            ];
            
        } catch (Exception $e) {
            // Fallback para valores padrão
            self::$config = [
                'nome' => 'SaaS Sisteminha',
                'logotipo' => '',
                'has_logo' => false
            ];
        }
        
        return self::$config;
    }
    
    /**
     * Renderizar logo ou ícone padrão
     */
    public static function renderLogo($size = 'w-10 h-10', $iconClass = 'w-6 h-6', $containerClass = '') {
        $config = self::load();
        
        if ($config['has_logo']) {
            return '<img src="/' . htmlspecialchars($config['logotipo']) . '" alt="' . htmlspecialchars($config['nome']) . '" class="' . $size . ' object-contain ' . $containerClass . '">';
        } else {
            return '
            <div class="' . $size . ' bg-gradient-to-br from-blue-600 to-emerald-600 rounded-lg flex items-center justify-center ' . $containerClass . '">
                <svg class="' . $iconClass . ' text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>';
        }
    }
    
    /**
     * Obter nome do sistema
     */
    public static function getSystemName() {
        $config = self::load();
        return $config['nome'];
    }
    
    /**
     * Renderizar nome do sistema
     */
    public static function renderSystemName($class = 'text-xl font-bold bg-gradient-to-r from-blue-600 to-emerald-600 bg-clip-text text-transparent') {
        return '<span class="' . $class . '">' . htmlspecialchars(self::getSystemName()) . '</span>';
    }
    
    /**
     * Renderizar logo + nome completo
     */
    public static function renderBrand($logoSize = 'w-10 h-10', $iconSize = 'w-6 h-6', $textClass = 'text-xl font-bold bg-gradient-to-r from-blue-600 to-emerald-600 bg-clip-text text-transparent') {
        return '
        <div class="flex items-center gap-3">
            ' . self::renderLogo($logoSize, $iconSize) . '
            ' . self::renderSystemName($textClass) . '
        </div>';
    }
}
