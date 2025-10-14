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
        
        // Não mostrar notificações tipo "email" (são apenas registros internos)
        $stmt = $this->db->prepare("
            SELECT * FROM notifications
            WHERE tenant_id = ? 
            AND tipo != 'email'
            {$where}
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
            WHERE tenant_id = ? 
            AND lida = 0
            AND tipo != 'email'
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
     * Enviar email via SMTP
     */
    private function sendEmail($tenantId, $titulo, $mensagem) {
        // Buscar email do tenant
        $stmt = $this->db->prepare("SELECT email_principal, nome_empresa FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $tenant = $stmt->fetch();
        
        if (!$tenant) return false;
        
        try {
            // Carregar configurações de email
            require_once __DIR__ . '/../config/email.php';
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configurações SMTP do config/email.php
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // Remetente e destinatário
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($tenant['email_principal'], $tenant['nome_empresa']);
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $titulo;
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h1 style='color: white; margin: 0;'>🔔 {$titulo}</h1>
                    </div>
                    <div style='background: white; padding: 30px; border: 1px solid #e5e7eb; border-radius: 0 0 10px 10px;'>
                        <p style='color: #374151; font-size: 16px; line-height: 1.6;'>{$mensagem}</p>
                        <hr style='margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;'>
                        <p style='color: #6b7280; font-size: 14px;'>
                            Esta é uma mensagem automática do sistema.<br>
                            Para acessar o sistema, <a href='https://seu-dominio.com' style='color: #667eea;'>clique aqui</a>.
                        </p>
                    </div>
                </div>
            ";
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            // Se falhar, apenas registra erro mas não bloqueia a notificação
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
}
