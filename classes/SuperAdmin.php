<?php
/**
 * Classe SuperAdmin - Gestão Administrativa do SaaS
 * 
 * Funcionalidades:
 * - Autenticação de super admins
 * - Gestão de tenants (bloquear, desbloquear, upgrade)
 * - Métricas e relatórios
 * - Logs de ações administrativas
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

class SuperAdmin {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Autenticar super admin
     */
    public function authenticate($email, $senha) {
        $stmt = $this->db->prepare("
            SELECT id, nome, email, senha, ativo 
            FROM super_admins 
            WHERE email = ? AND ativo = 1
        ");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($senha, $admin['senha'])) {
            // Atualizar último acesso
            $stmt = $this->db->prepare("UPDATE super_admins SET ultimo_acesso = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);
            
            // Iniciar sessão admin
            $_SESSION['super_admin_id'] = $admin['id'];
            $_SESSION['super_admin_nome'] = $admin['nome'];
            $_SESSION['super_admin_email'] = $admin['email'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar se está logado como admin
     */
    public static function isLoggedIn() {
        return isset($_SESSION['super_admin_id']);
    }
    
    /**
     * Obter dados do admin logado
     */
    public static function getCurrentAdmin() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['super_admin_id'],
            'nome' => $_SESSION['super_admin_nome'],
            'email' => $_SESSION['super_admin_email']
        ];
    }
    
    /**
     * Logout admin
     */
    public static function logout() {
        unset($_SESSION['super_admin_id']);
        unset($_SESSION['super_admin_nome']);
        unset($_SESSION['super_admin_email']);
    }
    
    /**
     * Obter métricas do dashboard
     */
    public function getDashboardMetrics() {
        // Total de tenants
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM tenants");
        $totalTenants = $stmt->fetch()['total'];
        
        // Tenants por plano
        $stmt = $this->db->query("
            SELECT 
                plano,
                COUNT(*) as total
            FROM tenants
            GROUP BY plano
        ");
        $planos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Tenants por status
        $stmt = $this->db->query("
            SELECT 
                status,
                COUNT(*) as total
            FROM tenants
            GROUP BY status
        ");
        $status = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // MRR (Monthly Recurring Revenue)
        $stmt = $this->db->query("
            SELECT COUNT(*) * 20 as mrr
            FROM tenants
            WHERE plano = 'pro' AND status = 'ativo'
        ");
        $mrr = $stmt->fetch()['mrr'];
        
        // Novos cadastros (últimos 30 dias)
        $stmt = $this->db->query("
            SELECT COUNT(*) as novos
            FROM tenants
            WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $novosCadastros = $stmt->fetch()['novos'];
        
        // Assinaturas vencendo (próximos 7 dias)
        $stmt = $this->db->query("
            SELECT COUNT(*) as vencendo
            FROM tenants
            WHERE plano = 'pro' 
            AND status = 'ativo'
            AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ");
        $vencendo = $stmt->fetch()['vencendo'];
        
        return [
            'total_tenants' => $totalTenants,
            'plano_free' => $planos['free'] ?? 0,
            'plano_pro' => $planos['pro'] ?? 0,
            'status_ativo' => $status['ativo'] ?? 0,
            'status_suspenso' => $status['suspenso'] ?? 0,
            'status_cancelado' => $status['cancelado'] ?? 0,
            'mrr' => $mrr,
            'novos_cadastros' => $novosCadastros,
            'vencendo_7dias' => $vencendo
        ];
    }
    
    /**
     * Listar todos os tenants com filtros
     */
    public function listTenants($filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['plano'])) {
            $where[] = "t.plano = ?";
            $params[] = $filters['plano'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(t.nome_empresa LIKE ? OR t.email_principal LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("
            SELECT 
                t.*,
                (SELECT COUNT(*) FROM usuarios WHERE tenant_id = t.id) as total_usuarios,
                (SELECT COUNT(*) FROM estabelecimentos WHERE tenant_id = t.id) as total_estabelecimentos,
                (SELECT COUNT(*) FROM consignacoes WHERE tenant_id = t.id) as total_consignacoes,
                s.status as subscription_status,
                s.data_vencimento as subscription_vencimento
            FROM tenants t
            LEFT JOIN subscriptions s ON t.id = s.tenant_id AND s.status = 'ativa'
            {$whereClause}
            ORDER BY t.criado_em DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Bloquear/Suspender tenant
     */
    public function blockTenant($tenantId, $motivo = null) {
        $stmt = $this->db->prepare("UPDATE tenants SET status = 'suspenso' WHERE id = ?");
        $result = $stmt->execute([$tenantId]);
        
        if ($result) {
            $this->logAction('bloquear_tenant', $tenantId, "Tenant bloqueado. Motivo: " . ($motivo ?? 'Não informado'));
        }
        
        return $result;
    }
    
    /**
     * Desbloquear tenant
     */
    public function unblockTenant($tenantId) {
        $stmt = $this->db->prepare("UPDATE tenants SET status = 'ativo' WHERE id = ?");
        $result = $stmt->execute([$tenantId]);
        
        if ($result) {
            $this->logAction('desbloquear_tenant', $tenantId, "Tenant desbloqueado");
        }
        
        return $result;
    }
    
    /**
     * Alterar plano do tenant
     */
    public function changePlan($tenantId, $novoPlano) {
        // Buscar dados atuais
        $stmt = $this->db->prepare("SELECT plano FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $planoAntigo = $stmt->fetch()['plano'];
        
        // Atualizar plano
        $stmt = $this->db->prepare("UPDATE tenants SET plano = ? WHERE id = ?");
        $result = $stmt->execute([$novoPlano, $tenantId]);
        
        if ($result) {
            $this->logAction('alterar_plano', $tenantId, "Plano alterado de {$planoAntigo} para {$novoPlano}");
        }
        
        return $result;
    }
    
    /**
     * Estender vencimento
     */
    public function extendExpiration($tenantId, $dias) {
        $stmt = $this->db->prepare("
            UPDATE tenants 
            SET data_vencimento = DATE_ADD(COALESCE(data_vencimento, CURDATE()), INTERVAL ? DAY)
            WHERE id = ?
        ");
        $result = $stmt->execute([$dias, $tenantId]);
        
        if ($result) {
            $this->logAction('estender_vencimento', $tenantId, "Vencimento estendido em {$dias} dias");
        }
        
        return $result;
    }
    
    /**
     * Excluir tenant
     */
    public function deleteTenant($tenantId) {
        // Buscar dados antes de excluir
        $stmt = $this->db->prepare("SELECT nome_empresa, email_principal FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $tenant = $stmt->fetch();
        
        $stmt = $this->db->prepare("DELETE FROM tenants WHERE id = ?");
        $result = $stmt->execute([$tenantId]);
        
        if ($result) {
            $this->logAction('excluir_tenant', $tenantId, "Tenant excluído: {$tenant['nome_empresa']} ({$tenant['email_principal']})");
        }
        
        return $result;
    }
    
    /**
     * Registrar log de ação administrativa
     */
    public function logAction($acao, $tenantId = null, $descricao = null) {
        $admin = self::getCurrentAdmin();
        
        if (!$admin) return false;
        
        $stmt = $this->db->prepare("
            INSERT INTO admin_logs (admin_id, tenant_id, acao, descricao, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $admin['id'],
            $tenantId,
            $acao,
            $descricao,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Obter logs recentes
     */
    public function getRecentLogs($limit = 50) {
        $stmt = $this->db->prepare("
            SELECT 
                l.*,
                a.nome as admin_nome,
                t.nome_empresa as tenant_nome
            FROM admin_logs l
            JOIN super_admins a ON l.admin_id = a.id
            LEFT JOIN tenants t ON l.tenant_id = t.id
            ORDER BY l.criado_em DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obter relatório financeiro
     */
    public function getFinancialReport() {
        // Verificar se tabela payments existe
        $stmt = $this->db->query("SHOW TABLES LIKE 'payments'");
        $paymentsExists = $stmt->rowCount() > 0;
        
        $receitaMensal = [];
        
        if ($paymentsExists) {
            // Receita mensal por mês (últimos 6 meses)
            $stmt = $this->db->query("
                SELECT 
                    DATE_FORMAT(p.data_pagamento, '%Y-%m') as mes,
                    COUNT(*) as total_pagamentos,
                    SUM(p.valor) as receita_total
                FROM payments p
                WHERE p.status = 'aprovado'
                AND p.data_pagamento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY mes
                ORDER BY mes DESC
            ");
            $receitaMensal = $stmt->fetchAll();
        } else {
            // Simular receita baseada em tenants Pro ativos
            $stmt = $this->db->query("
                SELECT 
                    DATE_FORMAT(criado_em, '%Y-%m') as mes,
                    COUNT(*) as total_pagamentos,
                    COUNT(*) * 20 as receita_total
                FROM tenants
                WHERE plano = 'pro' 
                AND status = 'ativo'
                AND criado_em >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY mes
                ORDER BY mes DESC
            ");
            $receitaMensal = $stmt->fetchAll();
        }
        
        // Inadimplência
        $stmt = $this->db->query("
            SELECT COUNT(*) as inadimplentes
            FROM tenants
            WHERE plano = 'pro' 
            AND status = 'ativo'
            AND data_vencimento IS NOT NULL
            AND data_vencimento < CURDATE()
        ");
        $inadimplentes = $stmt->fetch()['inadimplentes'];
        
        return [
            'receita_mensal' => $receitaMensal,
            'inadimplentes' => $inadimplentes
        ];
    }
}
