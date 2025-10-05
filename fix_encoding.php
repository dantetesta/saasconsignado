<?php
/**
 * Script para corrigir encoding UTF-8 dos arquivos
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

echo "==============================================\n";
echo "   CORREÇÃO DE ENCODING UTF-8\n";
echo "==============================================\n\n";

// Arquivos para verificar/corrigir
$files = [
    'upgrade.php',
    'assinatura.php',
    'includes/header.php',
    'index.php'
];

$fixed = 0;
$errors = 0;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "⚠️  Arquivo não encontrado: $file\n";
        continue;
    }
    
    echo "Verificando: $file\n";
    
    // Ler conteúdo
    $content = file_get_contents($file);
    
    // Detectar encoding atual
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    
    echo "  Encoding detectado: " . ($encoding ?: 'Desconhecido') . "\n";
    
    if ($encoding && $encoding !== 'UTF-8') {
        // Converter para UTF-8
        $content_utf8 = mb_convert_encoding($content, 'UTF-8', $encoding);
        
        // Salvar
        if (file_put_contents($file, $content_utf8)) {
            echo "  ✅ Convertido para UTF-8\n";
            $fixed++;
        } else {
            echo "  ❌ Erro ao salvar\n";
            $errors++;
        }
    } else {
        echo "  ✓ Já está em UTF-8\n";
    }
    
    echo "\n";
}

echo "==============================================\n";
echo "Arquivos corrigidos: $fixed\n";
echo "Erros: $errors\n";
echo "==============================================\n\n";

// Verificar banco de dados
echo "Verificando configuração do banco...\n";
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar charset da conexão
    $stmt = $db->query("SELECT @@character_set_client, @@character_set_connection, @@character_set_results");
    $charset = $stmt->fetch();
    
    echo "Character Set Client: " . $charset['@@character_set_client'] . "\n";
    echo "Character Set Connection: " . $charset['@@character_set_connection'] . "\n";
    echo "Character Set Results: " . $charset['@@character_set_results'] . "\n\n";
    
    if ($charset['@@character_set_client'] !== 'utf8mb4') {
        echo "⚠️  ATENÇÃO: Charset do banco não é utf8mb4!\n";
        echo "Execute: SET NAMES utf8mb4;\n\n";
    } else {
        echo "✅ Charset do banco está correto!\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao verificar banco: " . $e->getMessage() . "\n\n";
}

echo "==============================================\n";
echo "   CORREÇÃO CONCLUÍDA!\n";
echo "==============================================\n";
echo "\nRecarregue a página no navegador (Ctrl+F5)\n";
