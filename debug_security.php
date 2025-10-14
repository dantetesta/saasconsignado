<?php
/**
 * Debug - Informações de Segurança
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

require_once 'config/config.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Segurança</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">🔒 Status de Segurança</h1>
            
            <!-- Ambiente -->
            <div class="mb-6 p-4 <?php echo IS_LOCAL ? 'bg-blue-50 border-blue-200' : 'bg-green-50 border-green-200'; ?> border-2 rounded-lg">
                <h2 class="text-xl font-bold mb-2">🌍 Ambiente</h2>
                <div class="space-y-2">
                    <p><strong>Host:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'N/A'; ?></p>
                    <p><strong>Ambiente:</strong> 
                        <span class="px-3 py-1 rounded-full <?php echo IS_LOCAL ? 'bg-blue-500' : 'bg-green-500'; ?> text-white font-bold">
                            <?php echo IS_LOCAL ? '🏠 LOCAL' : '🌐 PRODUÇÃO'; ?>
                        </span>
                    </p>
                    <p><strong>IS_LOCAL:</strong> <?php echo IS_LOCAL ? '✅ true' : '❌ false'; ?></p>
                    <p><strong>IS_PRODUCTION:</strong> <?php echo IS_PRODUCTION ? '✅ true' : '❌ false'; ?></p>
                </div>
            </div>

            <!-- Turnstile -->
            <div class="mb-6 p-4 <?php echo TURNSTILE_ENABLED ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'; ?> border-2 rounded-lg">
                <h2 class="text-xl font-bold mb-2">🤖 Cloudflare Turnstile</h2>
                <div class="space-y-2">
                    <p><strong>Status:</strong> 
                        <span class="px-3 py-1 rounded-full <?php echo TURNSTILE_ENABLED ? 'bg-green-500' : 'bg-gray-500'; ?> text-white font-bold">
                            <?php echo TURNSTILE_ENABLED ? '✅ ATIVO' : '⏸️ DESABILITADO'; ?>
                        </span>
                    </p>
                    <p><strong>TURNSTILE_ENABLED:</strong> <?php echo TURNSTILE_ENABLED ? '✅ true' : '❌ false'; ?></p>
                    <?php if (TURNSTILE_ENABLED): ?>
                        <p><strong>Site Key:</strong> <code class="bg-white px-2 py-1 rounded"><?php echo TURNSTILE_SITE_KEY; ?></code></p>
                    <?php else: ?>
                        <p class="text-sm text-gray-600">ℹ️ Turnstile está desabilitado em ambiente local para facilitar testes</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sessão -->
            <div class="mb-6 p-4 bg-blue-50 border-blue-200 border-2 rounded-lg">
                <h2 class="text-xl font-bold mb-2">🔐 Configurações de Sessão</h2>
                <div class="space-y-2 text-sm">
                    <p><strong>session.cookie_httponly:</strong> <?php echo ini_get('session.cookie_httponly') ? '✅ Ativo' : '❌ Inativo'; ?></p>
                    <p><strong>session.cookie_samesite:</strong> <?php echo ini_get('session.cookie_samesite') ?: 'Não definido'; ?></p>
                    <p><strong>session.use_strict_mode:</strong> <?php echo ini_get('session.use_strict_mode') ? '✅ Ativo' : '❌ Inativo'; ?></p>
                    <p><strong>session.gc_maxlifetime:</strong> <?php echo ini_get('session.gc_maxlifetime'); ?> segundos (<?php echo ini_get('session.gc_maxlifetime') / 60; ?> minutos)</p>
                    <p><strong>session.cookie_secure:</strong> <?php echo ini_get('session.cookie_secure') ? '✅ Ativo (HTTPS)' : '⚠️ Inativo (HTTP)'; ?></p>
                </div>
            </div>

            <!-- CSRF -->
            <div class="mb-6 p-4 bg-yellow-50 border-yellow-200 border-2 rounded-lg">
                <h2 class="text-xl font-bold mb-2">🛡️ Proteção CSRF</h2>
                <div class="space-y-2">
                    <p><strong>Token CSRF:</strong> 
                        <?php if (isset($_SESSION['csrf_token'])): ?>
                            <span class="text-green-600 font-bold">✅ Gerado</span>
                            <code class="block mt-2 bg-white px-2 py-1 rounded text-xs break-all"><?php echo $_SESSION['csrf_token']; ?></code>
                        <?php else: ?>
                            <span class="text-red-600 font-bold">❌ Não gerado</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Charset -->
            <div class="mb-6 p-4 bg-indigo-50 border-indigo-200 border-2 rounded-lg">
                <h2 class="text-xl font-bold mb-2">📝 Encoding UTF-8</h2>
                <div class="space-y-2 text-sm">
                    <p><strong>mb_internal_encoding:</strong> <?php echo mb_internal_encoding(); ?></p>
                    <p><strong>default_charset:</strong> <?php echo ini_get('default_charset'); ?></p>
                    <p><strong>Teste de caracteres:</strong> Faça, Consignações, mês, • ✅</p>
                </div>
            </div>

            <!-- Banco de Dados -->
            <div class="mb-6 p-4 bg-pink-50 border-pink-200 border-2 rounded-lg">
                <h2 class="text-xl font-bold mb-2">💾 Banco de Dados</h2>
                <div class="space-y-2 text-sm">
                    <?php
                    try {
                        $db = Database::getInstance()->getConnection();
                        $stmt = $db->query("SELECT @@character_set_client, @@character_set_connection, @@character_set_results");
                        $charset = $stmt->fetch();
                        ?>
                        <p><strong>character_set_client:</strong> <?php echo $charset['@@character_set_client']; ?></p>
                        <p><strong>character_set_connection:</strong> <?php echo $charset['@@character_set_connection']; ?></p>
                        <p><strong>character_set_results:</strong> <?php echo $charset['@@character_set_results']; ?></p>
                        <p class="text-green-600 font-bold">✅ Conexão OK</p>
                    <?php } catch (Exception $e) { ?>
                        <p class="text-red-600 font-bold">❌ Erro: <?php echo $e->getMessage(); ?></p>
                    <?php } ?>
                </div>
            </div>

            <!-- Ações -->
            <div class="mt-8 flex gap-4">
                <a href="/login.php" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition">
                    ← Voltar ao Login
                </a>
                <button onclick="location.reload()" class="px-6 py-3 bg-gray-600 text-white font-bold rounded-lg hover:bg-gray-700 transition">
                    🔄 Recarregar
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-600 text-sm">
            <p>🔒 Debug de Segurança - Sistema de Consignados</p>
            <p>Desenvolvido por <a href="https://dantetesta.com.br" class="text-blue-600 hover:underline">Dante Testa</a></p>
        </div>
    </div>
</body>
</html>
