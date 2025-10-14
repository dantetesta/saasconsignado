<?php
/**
 * Instalar Sistema de Notificações
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once 'config/database.php';

echo "==============================================\n";
echo "   INSTALAÇÃO: SISTEMA DE NOTIFICAÇÕES\n";
echo "==============================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "✅ Conectado ao banco: " . DB_NAME . "\n\n";
    
    // Ler e executar SQL
    $sql = file_get_contents('migrations/create_notifications.sql');
    $db->exec($sql);
    
    echo "✅ Tabela 'notifications' criada com sucesso!\n\n";
    
    // Verificar
    $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Tabela verificada no banco\n";
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM notifications");
        $count = $stmt->fetch()['total'];
        echo "✓ Notificações cadastradas: {$count}\n";
    }
    
    echo "\n==============================================\n";
    echo "   🎉 INSTALAÇÃO CONCLUÍDA!\n";
    echo "==============================================\n\n";
    
    echo "🔔 Sistema de notificações pronto para uso!\n";
    echo "   - Admin pode enviar notificações\n";
    echo "   - Cliente vê badge no header\n";
    echo "   - Modal com lista de notificações\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}
