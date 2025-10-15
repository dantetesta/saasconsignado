<?php
/**
 * Script para executar migration de configurações de branding
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🚀 Iniciando migration de branding...\n\n";
    
    // Ler arquivo SQL
    $sql = file_get_contents(__DIR__ . '/migrations/add_branding_settings.sql');
    
    // Executar SQL
    $db->exec($sql);
    
    echo "✅ Migration executada com sucesso!\n\n";
    echo "📋 Configurações adicionadas:\n";
    echo "   • sistema_nome: Nome do Software\n";
    echo "   • sistema_logotipo: URL do Logotipo do Sistema\n\n";
    
    // Criar diretório de uploads se não existir
    $uploadDir = __DIR__ . '/uploads/branding';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "📁 Diretório criado: /uploads/branding/\n";
    }
    
    echo "\n✨ Pronto! Acesse /admin/configuracoes.php para configurar.\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
