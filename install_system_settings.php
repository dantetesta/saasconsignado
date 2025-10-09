<?php
/**
 * Instalar Configurações do Sistema
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once 'config/database.php';

echo "==============================================\n";
echo "   INSTALAÇÃO: CONFIGURAÇÕES DO SISTEMA\n";
echo "==============================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "✅ Conectado ao banco: " . DB_NAME . "\n";
    echo "✅ Host: " . DB_HOST . "\n\n";
    
    // Ler e executar SQL
    $sql = file_get_contents('migrations/create_system_settings.sql');
    
    // Executar cada statement separadamente
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            try {
                $db->exec($statement);
            } catch (PDOException $e) {
                // Ignora erros de "já existe"
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }
    }
    
    echo "✅ Tabela 'system_settings' criada!\n";
    echo "✅ Configurações padrão inseridas!\n\n";
    
    // Verificar
    $stmt = $db->query("SELECT * FROM system_settings ORDER BY grupo, chave");
    $settings = $stmt->fetchAll();
    
    echo "Configurações cadastradas:\n\n";
    
    $grupoAtual = '';
    foreach ($settings as $setting) {
        if ($setting['grupo'] !== $grupoAtual) {
            $grupoAtual = $setting['grupo'];
            echo "\n📁 Grupo: " . strtoupper($grupoAtual) . "\n";
        }
        echo "   • {$setting['chave']}: {$setting['valor']} ({$setting['tipo']})\n";
        echo "     {$setting['descricao']}\n";
    }
    
    echo "\n==============================================\n";
    echo "   🎉 INSTALAÇÃO CONCLUÍDA!\n";
    echo "==============================================\n\n";
    
    echo "📋 Configurações disponíveis:\n";
    echo "   - Preço do Plano Pro: R$ 20,00\n";
    echo "   - Validade: 30 dias\n";
    echo "   - Notificações: 5, 3, 1 dias antes\n";
    echo "   - Limites Plano Free\n\n";
    
    echo "🔧 Acesse o painel admin para ajustar:\n";
    echo "   /admin/configuracoes.php\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}
