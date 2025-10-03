<?php
/**
 * Gerador de Dump do Banco de Dados
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.2.5
 */

require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $dump = "-- ============================================\n";
    $dump .= "-- Sistema de Controle de Consignados\n";
    $dump .= "-- Autor: Dante Testa (https://dantetesta.com.br)\n";
    $dump .= "-- Versão: 1.2.5\n";
    $dump .= "-- Data: " . date('Y-m-d H:i:s') . "\n";
    $dump .= "-- ============================================\n\n";
    
    $dump .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $dump .= "SET time_zone = \"+00:00\";\n\n";
    
    // Buscar todas as tabelas (excluindo views)
    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "Exportando tabela: $table\n";
        
        // DROP TABLE
        $dump .= "-- Tabela: $table\n";
        $dump .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // CREATE TABLE
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $dump .= $create['Create Table'] . ";\n\n";
        
        // INSERT DATA (apenas para tabelas que não sejam de dados sensíveis)
        if (!in_array($table, ['usuarios', 'pagamentos'])) {
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rows) > 0) {
                $dump .= "-- Dados da tabela `$table`\n";
                
                foreach ($rows as $row) {
                    $columns = array_keys($row);
                    $values = array_map(function($value) use ($pdo) {
                        return $value === null ? 'NULL' : $pdo->quote($value);
                    }, array_values($row));
                    
                    $dump .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $dump .= "\n";
            }
        }
    }
    
    // Adicionar views no final
    $dump .= "\n-- ============================================\n";
    $dump .= "-- VIEWS (Visualizações)\n";
    $dump .= "-- ============================================\n\n";
    
    $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($views as $view) {
        echo "Exportando view: $view\n";
        
        $dump .= "-- View: $view\n";
        $dump .= "DROP VIEW IF EXISTS `$view`;\n";
        
        $create = $pdo->query("SHOW CREATE VIEW `$view`")->fetch(PDO::FETCH_ASSOC);
        if (isset($create['Create View'])) {
            // Remover DEFINER para evitar problemas de permissão
            $createView = $create['Create View'];
            $createView = preg_replace('/DEFINER=`[^`]+`@`[^`]+`/', '', $createView);
            $dump .= $createView . ";\n\n";
        }
    }
    
    $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Salvar arquivo
    file_put_contents(__DIR__ . '/database_dump.sql', $dump);
    
    echo "\n✅ Dump criado com sucesso: database_dump.sql\n";
    echo "Tamanho: " . number_format(strlen($dump) / 1024, 2) . " KB\n";
    echo "Tabelas exportadas: " . count($tables) . "\n";
    
} catch (PDOException $e) {
    die("❌ Erro: " . $e->getMessage() . "\n");
}
