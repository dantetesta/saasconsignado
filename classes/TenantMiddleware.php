<?php
/**
 * Middleware de Isolamento Multi-Tenant
 * 
 * Responsável por gerenciar o contexto do tenant atual e
 * garantir isolamento de dados entre diferentes empresas/contas
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

class TenantMiddleware {
    private static $current_tenant_id = null;
    private static $tenant_data = null;
    private static $initialized = false;
    
    /**
     * Inicializar tenant baseado no subdomínio ou sessão
     * 
     * @return void
     */
    public static function initialize() {
        if (self::$initialized) {
            return;
        }
        
        // Verificar se já está logado e tem tenant na sessão
        if (isset($_SESSION['tenant_id']) && !empty($_SESSION['tenant_id'])) {
            try {
                self::setTenant($_SESSION['tenant_id']);
                self::$initialized = true;
                return;
            } catch (Exception $e) {
                // Se falhar, limpar sessão e continuar
                unset($_SESSION['tenant_id']);
            }
        }
        
        // Detectar tenant pelo subdomínio (se configurado)
        $subdomain = self::extractSubdomain();
        if ($subdomain && $subdomain !== 'www') {
            $tenant = self::getTenantBySubdomain($subdomain);
            if ($tenant) {
                self::setTenant($tenant['id']);
                self::$initialized = true;
                return;
            }
        }
        
        self::$initialized = true;
    }
    
    /**
     * Definir tenant atual
     * 
     * @param int $tenant_id ID do tenant
     * @throws Exception Se tenant não encontrado ou inativo
     * @return void
     */
    public static function setTenant($tenant_id) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT * FROM tenants 
            WHERE id = ? AND status IN ('ativo', 'trial')
        ");
        $stmt->execute([$tenant_id]);
        $tenant = $stmt->fetch();
        
        if (!$tenant) {
            throw new Exception("Tenant não encontrado ou inativo");
        }
        
        self::$current_tenant_id = $tenant_id;
        self::$tenant_data = $tenant;
        $_SESSION['tenant_id'] = $tenant_id;
        $_SESSION['tenant_data'] = $tenant;
    }
    
    /**
     * Obter ID do tenant atual
     * 
     * @return int|null
     */
    public static function getTenantId() {
        return self::$current_tenant_id;
    }
    
    /**
     * Obter dados completos do tenant atual
     * 
     * @return array|null
     */
    public static function getTenantData() {
        return self::$tenant_data;
    }
    
    /**
     * Verificar se há um tenant definido
     * 
     * @return bool
     */
    public static function hasTenant() {
        return self::$current_tenant_id !== null;
    }
    
    /**
     * Limpar tenant da sessão (logout)
     * 
     * @return void
     */
    public static function clearTenant() {
        self::$current_tenant_id = null;
        self::$tenant_data = null;
        self::$initialized = false;
        unset($_SESSION['tenant_id']);
        unset($_SESSION['tenant_data']);
    }
    
    /**
     * Verificar limites do plano
     * 
     * @param string $resource Recurso a verificar (estabelecimentos, consignacoes)
     * @param int $current_count Contagem atual
     * @return bool True se dentro do limite
     */
    public static function checkLimit($resource, $current_count) {
        if (!self::hasTenant()) {
            return false;
        }
        
        $tenant = self::$tenant_data;
        
        // Plano Pro é ilimitado
        if ($tenant['plano'] === 'pro') {
            return true;
        }
        
        // Plano Free tem limites
        switch ($resource) {
            case 'estabelecimentos':
                $limit = $tenant['limite_estabelecimentos'];
                return $limit === null || $current_count < $limit;
                
            case 'consignacoes':
                $limit = $tenant['limite_consignacoes_por_estabelecimento'];
                return $limit === null || $current_count < $limit;
                
            default:
                return true;
        }
    }
    
    /**
     * Verificar se pode criar novo estabelecimento
     * 
     * @return bool
     * @throws Exception Se exceder limite
     */
    public static function canCreateEstabelecimento() {
        if (!self::hasTenant()) {
            throw new Exception("Tenant não definido");
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) as total 
            FROM estabelecimentos 
            WHERE tenant_id = ? AND ativo = 1
        ");
        $stmt->execute([self::$current_tenant_id]);
        $count = $stmt->fetchColumn();
        
        if (!self::checkLimit('estabelecimentos', $count)) {
            throw new Exception(
                "Limite de estabelecimentos atingido no plano Free. " .
                "Faça upgrade para o plano Pro para adicionar mais estabelecimentos."
            );
        }
        
        return true;
    }
    
    /**
     * Verificar se pode criar nova consignação para estabelecimento
     * 
     * @param int $estabelecimento_id ID do estabelecimento
     * @return bool
     * @throws Exception Se exceder limite
     */
    public static function canCreateConsignacao($estabelecimento_id) {
        if (!self::hasTenant()) {
            throw new Exception("Tenant não definido");
        }
        
        $tenant = self::$tenant_data;
        
        // Pro é ilimitado
        if ($tenant['plano'] === 'pro') {
            return true;
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) as total 
            FROM consignacoes 
            WHERE tenant_id = ? 
              AND estabelecimento_id = ? 
              AND status IN ('pendente', 'parcial')
        ");
        $stmt->execute([self::$current_tenant_id, $estabelecimento_id]);
        $count = $stmt->fetchColumn();
        
        if (!self::checkLimit('consignacoes', $count)) {
            throw new Exception(
                "Limite de consignações por estabelecimento atingido no plano Free. " .
                "Faça upgrade para o plano Pro para adicionar mais consignações."
            );
        }
        
        return true;
    }
    
    /**
     * Obter informações do plano atual
     * 
     * @return array
     */
    public static function getPlanInfo() {
        if (!self::hasTenant()) {
            return null;
        }
        
        $tenant = self::$tenant_data;
        
        return [
            'plano' => $tenant['plano'],
            'nome_plano' => $tenant['plano'] === 'pro' ? 'Pro' : 'Free',
            'status' => $tenant['status'],
            'data_vencimento' => $tenant['data_vencimento'],
            'is_pro' => $tenant['plano'] === 'pro',
            'is_free' => $tenant['plano'] === 'free',
            'limites' => [
                'estabelecimentos' => $tenant['limite_estabelecimentos'],
                'consignacoes_por_estabelecimento' => $tenant['limite_consignacoes_por_estabelecimento']
            ]
        ];
    }
    
    /**
     * Extrair subdomínio da URL
     * 
     * @return string|null
     */
    private static function extractSubdomain() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Remover porta se houver
        $host = explode(':', $host)[0];
        
        $parts = explode('.', $host);
        
        // Se tiver 3 ou mais partes, primeiro é subdomínio
        // Ex: app.seudominio.com.br -> app
        if (count($parts) >= 3) {
            return $parts[0];
        }
        
        return null;
    }
    
    /**
     * Buscar tenant por subdomínio
     * 
     * @param string $subdomain
     * @return array|null
     */
    private static function getTenantBySubdomain($subdomain) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT * FROM tenants 
            WHERE subdomain = ? AND status IN ('ativo', 'trial')
        ");
        $stmt->execute([$subdomain]);
        
        return $stmt->fetch();
    }
    
    /**
     * Aplicar filtro de tenant em query SQL
     * (Helper para queries manuais)
     * 
     * @param string $table_alias Alias da tabela (ex: 'c' para consignacoes)
     * @return string Condição WHERE para tenant
     */
    public static function getTenantCondition($table_alias = '') {
        $tenant_id = self::getTenantId();
        
        if (!$tenant_id) {
            throw new Exception("Tenant não definido");
        }
        
        $prefix = $table_alias ? $table_alias . '.' : '';
        return "{$prefix}tenant_id = {$tenant_id}";
    }
}
