<?php
/**
 * Limpar Cache do PHP OPcache
 */

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache limpo com sucesso!<br>";
} else {
    echo "ℹ️ OPcache não está habilitado.<br>";
}

if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "✅ APC Cache limpo!<br>";
}

echo "<br><strong>Cache limpo! Agora teste novamente.</strong>";
echo "<br><br><a href='consulta_publica.php?token=0c03d541bcbef7deffc2f095bf0499e1822463b3b7d5f87632eab2f7c677cf8f&id=14'>Testar Consulta Pública</a>";
