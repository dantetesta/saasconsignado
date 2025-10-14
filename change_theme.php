<?php
/**
 * Script para Mudança de Tema: Roxo/Rosa → Azul/Verde
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 */

// Mapeamento de cores
$colorMap = [
    // Gradientes principais
    'from-blue-600 to-emerald-600' => 'from-blue-600 to-emerald-600',
    'from-blue-700 to-emerald-700' => 'from-blue-700 to-emerald-700',
    
    // Cores hexadecimais (inline styles e emails)
    '#2563eb' => '#2563eb', // purple-600 → blue-600
    '#10b981' => '#10b981', // pink-600 → emerald-600
    'rgba(37, 99, 235' => 'rgba(37, 99, 235', // purple com transparência
    
    // Gradientes CSS inline
    'linear-gradient(135deg, #2563eb 0%, #10b981 100%)' => 'linear-gradient(135deg, #2563eb 0%, #10b981 100%)',
    
    // Background
    'bg-blue-50' => 'bg-blue-50',
    'bg-blue-100' => 'bg-blue-100',
    'bg-blue-600' => 'bg-blue-600',
    'bg-blue-700' => 'bg-blue-700',
    'bg-emerald-600' => 'bg-emerald-600',
    'bg-emerald-700' => 'bg-emerald-700',
    
    // Text
    'text-blue-50' => 'text-blue-50',
    'text-blue-100' => 'text-blue-100',
    'text-blue-200' => 'text-blue-200',
    'text-blue-600' => 'text-blue-600',
    'text-blue-700' => 'text-blue-700',
    'text-emerald-600' => 'text-emerald-600',
    
    // Border
    'border-blue-100' => 'border-blue-100',
    'border-blue-200' => 'border-blue-200',
    'border-blue-300' => 'border-blue-300',
    'border-blue-600' => 'border-blue-600',
    'border-emerald-600' => 'border-emerald-600',
    
    // Hover
    'hover:bg-blue-50' => 'hover:bg-blue-50',
    'hover:bg-blue-100' => 'hover:bg-blue-100',
    'hover:bg-blue-700' => 'hover:bg-blue-700',
    'hover:bg-emerald-700' => 'hover:bg-emerald-700',
    'hover:text-blue-700' => 'hover:text-blue-700',
    'hover:border-blue-300' => 'hover:border-blue-300',
    'hover:from-blue-700' => 'hover:from-blue-700',
    'hover:to-emerald-700' => 'hover:to-emerald-700',
    
    // Focus
    'focus:ring-blue-500' => 'focus:ring-blue-500',
    'focus:border-blue-500' => 'focus:border-blue-500',
];

// Diretórios para processar
$directories = [
    __DIR__,
    __DIR__ . '/admin',
    __DIR__ . '/views',
    __DIR__ . '/includes',
    __DIR__ . '/admin/includes',
    __DIR__ . '/config',
];

$filesProcessed = 0;
$replacements = 0;

echo "🎨 Iniciando mudança de tema...\n\n";

foreach ($directories as $dir) {
    if (!is_dir($dir)) continue;
    
    $files = glob($dir . '/*.php');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        foreach ($colorMap as $old => $new) {
            $count = 0;
            $content = str_replace($old, $new, $content, $count);
            $replacements += $count;
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $filesProcessed++;
            echo "✅ Atualizado: " . basename($file) . "\n";
        }
    }
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✨ Mudança de tema concluída!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📁 Arquivos processados: $filesProcessed\n";
echo "🔄 Substituições feitas: $replacements\n";
echo "🎨 Nova paleta: Azul + Verde Esmeralda\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
