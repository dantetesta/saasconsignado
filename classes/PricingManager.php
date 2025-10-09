<?php
/**
 * Gerenciador de Preços e Configurações de Planos
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.1.0
 */

class PricingManager 
{
    private $db;
    private static $instance = null;
    private $cache = [];
    
    /**
     * Construtor privado para Singleton
     */
    private function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obter instância única (Singleton)
     */
    public static function getInstance(): PricingManager 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obter preço do Plano Pro
     * 
     * @param bool $formatted Se deve retornar formatado (R$ 20,00) ou valor bruto (20.00)
     * @return string|float
     */
    public function getProPrice(bool $formatted = false) 
    {
        $price = $this->getSetting('plano_pro_preco', '20.00');
        $priceFloat = floatval($price);
        
        if ($formatted) {
            return 'R$ ' . number_format($priceFloat, 2, ',', '.');
        }
        
        return $priceFloat;
    }
    
    /**
     * Obter dias de validade do Plano Pro
     * 
     * @return int
     */
    public function getProDays(): int 
    {
        return intval($this->getSetting('plano_pro_dias', '30'));
    }
    
    /**
     * Obter limite de estabelecimentos do Plano Free
     * 
     * @return int
     */
    public function getFreeEstablishmentLimit(): int 
    {
        return intval($this->getSetting('plano_free_estabelecimentos', '5'));
    }
    
    /**
     * Obter limite de consignações do Plano Free
     * 
     * @return int
     */
    public function getFreeConsignmentLimit(): int 
    {
        return intval($this->getSetting('plano_free_consignacoes', '5'));
    }
    
    /**
     * Verificar se usuário pode criar mais estabelecimentos (Plano Free)
     * 
     * @param int $tenantId ID do tenant
     * @return bool
     */
    public function canCreateEstablishment(int $tenantId): bool 
    {
        // Verificar plano do tenant
        $stmt = $this->db->prepare("SELECT plano FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $plano = $stmt->fetchColumn();
        
        // Se é Pro, pode criar ilimitado
        if ($plano === 'pro') {
            return true;
        }
        
        // Se é Free, verificar limite
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM estabelecimentos 
            WHERE tenant_id = ?
        ");
        $stmt->execute([$tenantId]);
        $count = $stmt->fetchColumn();
        
        return $count < $this->getFreeEstablishmentLimit();
    }
    
    /**
     * Verificar se estabelecimento pode ter mais consignações (Plano Free)
     * 
     * @param int $estabelecimentoId ID do estabelecimento
     * @return bool
     */
    public function canCreateConsignment(int $estabelecimentoId): bool 
    {
        // Buscar tenant do estabelecimento
        $stmt = $this->db->prepare("
            SELECT t.plano 
            FROM tenants t 
            JOIN estabelecimentos e ON e.tenant_id = t.id 
            WHERE e.id = ?
        ");
        $stmt->execute([$estabelecimentoId]);
        $plano = $stmt->fetchColumn();
        
        // Se é Pro, pode criar ilimitado
        if ($plano === 'pro') {
            return true;
        }
        
        // Se é Free, verificar limite
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM consignacoes 
            WHERE estabelecimento_id = ?
        ");
        $stmt->execute([$estabelecimentoId]);
        $count = $stmt->fetchColumn();
        
        return $count < $this->getFreeConsignmentLimit();
    }
    
    /**
     * Obter informações completas dos planos para exibição
     * 
     * @return array
     */
    public function getPlansInfo(): array 
    {
        return [
            'free' => [
                'name' => 'Plano Free',
                'price' => 0,
                'price_formatted' => 'Gratuito',
                'establishments_limit' => $this->getFreeEstablishmentLimit(),
                'consignments_limit' => $this->getFreeConsignmentLimit(),
                'features' => [
                    'Até ' . $this->getFreeEstablishmentLimit() . ' estabelecimentos',
                    'Até ' . $this->getFreeConsignmentLimit() . ' consignações por estabelecimento',
                    'Funcionalidades básicas'
                ]
            ],
            'pro' => [
                'name' => 'Plano Pro',
                'price' => $this->getProPrice(),
                'price_formatted' => $this->getProPrice(true),
                'days' => $this->getProDays(),
                'establishments_limit' => 'Ilimitado',
                'consignments_limit' => 'Ilimitado',
                'features' => [
                    'Estabelecimentos ilimitados',
                    'Consignações ilimitadas',
                    'Todas as funcionalidades',
                    'Suporte prioritário',
                    'Relatórios avançados'
                ]
            ]
        ];
    }
    
    /**
     * Obter configuração do sistema
     * 
     * @param string $key Chave da configuração
     * @param string $default Valor padrão
     * @return string
     */
    private function getSetting(string $key, string $default = ''): string 
    {
        // Verificar cache primeiro
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        
        $stmt = $this->db->prepare("SELECT valor FROM system_settings WHERE chave = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        
        if ($value === false) {
            $value = $default;
        }
        
        // Armazenar no cache
        $this->cache[$key] = $value;
        
        return $value;
    }
    
    /**
     * Validar se um valor de preço é válido
     * 
     * @param mixed $price Preço a validar
     * @return bool
     */
    public static function isValidPrice($price): bool 
    {
        if (!is_numeric($price)) {
            return false;
        }
        
        $priceFloat = floatval($price);
        return $priceFloat >= 0 && $priceFloat <= 999.99;
    }
    
    /**
     * Formatar preço para exibição
     * 
     * @param float $price Preço
     * @param bool $showCurrency Se deve mostrar símbolo da moeda
     * @return string
     */
    public static function formatPrice(float $price, bool $showCurrency = true): string 
    {
        $formatted = number_format($price, 2, ',', '.');
        return $showCurrency ? 'R$ ' . $formatted : $formatted;
    }
}
