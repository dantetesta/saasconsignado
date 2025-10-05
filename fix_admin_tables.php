<?php
/**
 * Corrigir e completar instalação do painel admin
 * - Inserir super admin se não existir
 * - Criar tabela payment_gateways
 * - Inserir gateways padrão
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

require_once 'config/database.php';

echo "==============================================\n";
echo "   CORREÇÃO DO PAINEL ADMINISTRATIVO\n";
echo "==============================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "✅ Conectado ao banco: " . DB_NAME . " (" . DB_HOST . ")\n\n";
    
    // ============================================
    // 1. VERIFICAR E INSERIR SUPER ADMIN
    // ============================================
    
    echo "👤 Verificando super admin...\n";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM super_admins");
    $count = $stmt->fetch()['total'];
    
    if ($count == 0) {
        echo "   ⚠️  Nenhum super admin encontrado. Inserindo...\n";
        
        $stmt = $db->prepare("
            INSERT INTO super_admins (nome, email, senha, ativo) 
            VALUES (?, ?, ?, 1)
        ");
        
        // Senha: admin123
        $senhaHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        
        $stmt->execute([
            'Administrador',
            'admin@dantetesta.com.br',
            $senhaHash
        ]);
        
        echo "   ✅ Super admin inserido com sucesso!\n";
        echo "      Email: admin@dantetesta.com.br\n";
        echo "      Senha: admin123\n";
    } else {
        echo "   ✅ Super admin já existe ({$count} registro(s))\n";
        
        // Mostrar dados
        $stmt = $db->query("SELECT id, nome, email FROM super_admins");
        while ($admin = $stmt->fetch()) {
            echo "      - ID: {$admin['id']}, Nome: {$admin['nome']}, Email: {$admin['email']}\n";
        }
    }
    
    echo "\n";
    
    // ============================================
    // 2. CRIAR TABELA PAYMENT_GATEWAYS
    // ============================================
    
    echo "💳 Verificando tabela payment_gateways...\n";
    
    $stmt = $db->query("SHOW TABLES LIKE 'payment_gateways'");
    
    if ($stmt->rowCount() == 0) {
        echo "   ⚠️  Tabela não existe. Criando...\n";
        
        $sql = "
        CREATE TABLE `payment_gateways` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `nome` varchar(100) NOT NULL,
          `slug` varchar(50) NOT NULL,
          `descricao` text DEFAULT NULL,
          `logo_url` varchar(255) DEFAULT NULL,
          `ativo` tinyint(1) DEFAULT 0,
          `configuracoes` text DEFAULT NULL COMMENT 'JSON com API keys e configs',
          `metodos_disponiveis` text DEFAULT NULL COMMENT 'JSON: pix, boleto, cartao',
          `taxa_percentual` decimal(5,2) DEFAULT 0.00,
          `taxa_fixa` decimal(10,2) DEFAULT 0.00,
          `ordem` int(11) DEFAULT 0,
          `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
          `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `slug_unique` (`slug`),
          KEY `idx_ativo` (`ativo`),
          KEY `idx_ordem` (`ordem`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gateways de pagamento disponíveis'
        ";
        
        $db->exec($sql);
        
        echo "   ✅ Tabela payment_gateways criada!\n";
    } else {
        echo "   ✅ Tabela payment_gateways já existe\n";
    }
    
    echo "\n";
    
    // ============================================
    // 3. INSERIR GATEWAYS PADRÃO
    // ============================================
    
    echo "🔧 Verificando gateways cadastrados...\n";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM payment_gateways");
    $count = $stmt->fetch()['total'];
    
    if ($count == 0) {
        echo "   ⚠️  Nenhum gateway cadastrado. Inserindo...\n";
        
        $gateways = [
            ['Pagou.com.br', 'pagou', 'Gateway brasileiro com PIX, Boleto e Cartão', 0, '["pix","boleto","cartao"]', 1],
            ['Stripe', 'stripe', 'Gateway internacional com cartão de crédito', 0, '["cartao"]', 2],
            ['Mercado Pago', 'mercadopago', 'Gateway com PIX, Boleto e Cartão', 0, '["pix","boleto","cartao"]', 3],
            ['PagSeguro', 'pagseguro', 'Gateway brasileiro completo', 0, '["pix","boleto","cartao"]', 4],
            ['Asaas', 'asaas', 'Gateway brasileiro para recorrência', 0, '["pix","boleto","cartao"]', 5]
        ];
        
        $stmt = $db->prepare("
            INSERT INTO payment_gateways (nome, slug, descricao, ativo, metodos_disponiveis, ordem) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($gateways as $gateway) {
            $stmt->execute($gateway);
            echo "      ✓ {$gateway[0]}\n";
        }
        
        echo "   ✅ {count($gateways)} gateways inseridos!\n";
    } else {
        echo "   ✅ Gateways já cadastrados ({$count} registro(s))\n";
        
        // Mostrar gateways
        $stmt = $db->query("SELECT nome, slug, ativo FROM payment_gateways ORDER BY ordem");
        while ($gateway = $stmt->fetch()) {
            $status = $gateway['ativo'] ? '🟢 Ativo' : '⚪ Inativo';
            echo "      - {$gateway['nome']} ({$gateway['slug']}) - {$status}\n";
        }
    }
    
    echo "\n";
    
    // ============================================
    // 4. VERIFICAÇÃO FINAL
    // ============================================
    
    echo "==============================================\n";
    echo "   ✅ VERIFICAÇÃO FINAL\n";
    echo "==============================================\n\n";
    
    // Contar registros
    $stmt = $db->query("SELECT COUNT(*) as total FROM super_admins");
    $totalAdmins = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM admin_logs");
    $totalLogs = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM payment_gateways");
    $totalGateways = $stmt->fetch()['total'];
    
    echo "📊 Estatísticas:\n";
    echo "   - Super Admins: {$totalAdmins}\n";
    echo "   - Logs: {$totalLogs}\n";
    echo "   - Gateways: {$totalGateways}\n\n";
    
    echo "==============================================\n";
    echo "   🎉 INSTALAÇÃO COMPLETA!\n";
    echo "==============================================\n\n";
    
    echo "🔑 Credenciais de acesso:\n";
    echo "   URL: http://seu-dominio.com/admin/login.php\n";
    echo "   Email: admin@dantetesta.com.br\n";
    echo "   Senha: admin123\n";
    echo "   ⚠️  ALTERE A SENHA APÓS O PRIMEIRO LOGIN!\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Erro de banco de dados:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Erro:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}
