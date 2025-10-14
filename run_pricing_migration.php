<?php
/**
 * Script para executar migração do sistema de preços
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.1.0
 */

// Forçar UTF-8
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>🚀 Executando Migração do Sistema de Preços</h2>\n";
    echo "<pre>\n";
    
    // Ler arquivo de migração
    $migrationFile = __DIR__ . '/migrations/update_pricing_system.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Arquivo de migração não encontrado: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    if (empty($sql)) {
        throw new Exception("Arquivo de migração está vazio");
    }
    
    echo "📁 Arquivo de migração carregado: update_pricing_system.sql\n";
    
    // Dividir por comandos SQL (separados por ;)
    $commands = array_filter(
        array_map('trim', explode(';', $sql)),
        function($cmd) {
            return !empty($cmd) && !preg_match('/^\s*--/', $cmd);
        }
    );
    
    echo "📋 Encontrados " . count($commands) . " comandos SQL\n\n";
    
    $db->beginTransaction();
    
    $executedCommands = 0;
    
    foreach ($commands as $i => $command) {
        if (empty(trim($command))) continue;
        
        try {
            echo "⚡ Executando comando " . ($i + 1) . "...\n";
            
            // Executar comando
            $stmt = $db->prepare($command);
            $stmt->execute();
            
            $executedCommands++;
            echo "✅ Comando executado com sucesso\n\n";
            
        } catch (Exception $e) {
            // Se for erro de "já existe", continuar
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "ℹ️  Comando ignorado (já existe): " . substr($e->getMessage(), 0, 100) . "...\n\n";
                continue;
            }
            
            throw new Exception("Erro no comando " . ($i + 1) . ": " . $e->getMessage());
        }
    }
    
    $db->commit();
    
    echo "🎉 MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "📊 Total de comandos executados: $executedCommands\n\n";
    
    // Verificar se as configurações foram inseridas
    $stmt = $db->query("SELECT COUNT(*) FROM system_settings WHERE grupo = 'planos'");
    $planSettings = $stmt->fetchColumn();
    
    echo "✅ Configurações de planos no banco: $planSettings\n";
    
    // Verificar preço atual
    $stmt = $db->query("SELECT valor FROM system_settings WHERE chave = 'plano_pro_preco'");
    $currentPrice = $stmt->fetchColumn();
    
    echo "💰 Preço atual do Plano Pro: R$ " . number_format($currentPrice, 2, ',', '.') . "\n";
    
    echo "\n🔗 Acesse o painel administrativo em: /admin/configuracoes.php\n";
    echo "🔗 Visualize a página de upgrade em: /upgrade.php\n";
    
    echo "</pre>\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo "<h2>❌ ERRO NA MIGRAÇÃO</h2>\n";
    echo "<pre style='color: red;'>\n";
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "</pre>\n";
}
?>
