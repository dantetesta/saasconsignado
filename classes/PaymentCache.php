<?php
/**
 * Cache de Pagamentos para evitar consultas excessivas à API
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.1.0
 */

class PaymentCache 
{
    private static $cache = [];
    private static $cacheTimeout = 30; // 30 segundos
    
    /**
     * Verificar se existe cache válido para um charge_id
     * 
     * @param string $chargeId
     * @return array|null
     */
    public static function get(string $chargeId): ?array 
    {
        if (!isset(self::$cache[$chargeId])) {
            return null;
        }
        
        $cached = self::$cache[$chargeId];
        
        // Verificar se o cache expirou
        if (time() - $cached['timestamp'] > self::$cacheTimeout) {
            unset(self::$cache[$chargeId]);
            return null;
        }
        
        return $cached['data'];
    }
    
    /**
     * Armazenar resultado no cache
     * 
     * @param string $chargeId
     * @param array $data
     */
    public static function set(string $chargeId, array $data): void 
    {
        self::$cache[$chargeId] = [
            'data' => $data,
            'timestamp' => time()
        ];
    }
    
    /**
     * Limpar cache de um charge_id específico
     * 
     * @param string $chargeId
     */
    public static function clear(string $chargeId): void 
    {
        unset(self::$cache[$chargeId]);
    }
    
    /**
     * Limpar todo o cache
     */
    public static function clearAll(): void 
    {
        self::$cache = [];
    }
    
    /**
     * Verificar se um pagamento já foi confirmado no cache
     * 
     * @param string $chargeId
     * @return bool
     */
    public static function isPaid(string $chargeId): bool 
    {
        $cached = self::get($chargeId);
        return $cached && isset($cached['pago']) && $cached['pago'] === true;
    }
}
