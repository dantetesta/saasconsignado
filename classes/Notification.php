<?php
/**
 * Classe Notification - Sistema de Notificações
 * 
 * Gerencia notificações entre admin e clientes
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

class Notification {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Criar nova notificação
     */
    public function create($tenantId, $titulo, $mensagem, $tipo = 'info', $enviarEmail = false, $adminId = null) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (tenant_id, tipo, titulo, mensagem, enviado_por_email, admin_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $tenantId,
            $tipo,
            $titulo,
            $mensagem,
            $enviarEmail ? 1 : 0,
            $adminId
        ]);
        
        // Se deve enviar por email
        if ($enviarEmail && $result) {
            $this->sendEmail($tenantId, $titulo, $mensagem);
        }
        
        return $result;
    }
    
    /**
     * Obter notificações de um tenant
     */
    public function getByTenant($tenantId, $limit = 20, $apenasNaoLidas = false) {
        $where = $apenasNaoLidas ? 'AND lida = 0' : '';
        
        $stmt = $this->db->prepare("
            SELECT * FROM notifications
            WHERE tenant_id = ? {$where}
            ORDER BY criado_em DESC
            LIMIT ?
        ");
        
        $stmt->execute([$tenantId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Contar notificações não lidas
     */
    public function countUnread($tenantId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total
            FROM notifications
            WHERE tenant_id = ? AND lida = 0
        ");
        
        $stmt->execute([$tenantId]);
        return $stmt->fetch()['total'];
    }
    
    /**
     * Marcar como lida
     */
    public function markAsRead($notificationId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET lida = 1, lida_em = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$notificationId]);
    }
    
    /**
     * Marcar todas como lidas
     */
    public function markAllAsRead($tenantId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET lida = 1, lida_em = NOW()
            WHERE tenant_id = ? AND lida = 0
        ");
        
        return $stmt->execute([$tenantId]);
    }
    
    /**
     * Deletar notificação
     */
    public function delete($notificationId) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
        return $stmt->execute([$notificationId]);
    }
    
    /**
     * Enviar email (integração futura)
     */
    private function sendEmail($tenantId, $titulo, $mensagem) {
        // Buscar email do tenant
        $stmt = $this->db->prepare("SELECT email_principal FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $tenant = $stmt->fetch();
        
        if ($tenant) {
            // TODO: Integrar com sistema de email (Postmark, etc)
            // Por enquanto apenas registra que foi enviado
            
            // Criar notificação de que email foi enviado
            $stmt = $this->db->prepare("
                INSERT INTO notifications (tenant_id, tipo, titulo, mensagem, enviado_por_email)
                VALUES (?, 'email', ?, ?, 1)
            ");
            
            $stmt->execute([
                $tenantId,
                '📧 Email Enviado',
                'Você recebeu um email importante. Verifique sua caixa de entrada: ' . $tenant['email_principal']
            ]);
        }
    }
}
