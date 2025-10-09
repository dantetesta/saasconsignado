<?php
/**
 * Menu de Navegação Padrão do Painel Administrativo
 * Template reutilizável para todas as páginas admin
 * 
 * Variável esperada:
 * - $currentPage: Identificador da página atual para highlight
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

$currentPage = $currentPage ?? '';

// Definir itens do menu
$menuItems = [
    'dashboard' => ['url' => '/admin/index.php', 'icon' => '📊', 'label' => 'Dashboard'],
    'tenants' => ['url' => '/admin/tenants.php', 'icon' => '👥', 'label' => 'Assinantes'],
    'financeiro' => ['url' => '/admin/financeiro.php', 'icon' => '💰', 'label' => 'Financeiro'],
    'pagamentos' => ['url' => '/admin/pagamentos.php', 'icon' => '💳', 'label' => 'Pagamentos'],
    'gateways' => ['url' => '/admin/gateways.php', 'icon' => '🔗', 'label' => 'Gateways'],
    'configuracoes' => ['url' => '/admin/configuracoes.php', 'icon' => '⚙️', 'label' => 'Configurações'],
    'logs' => ['url' => '/admin/logs.php', 'icon' => '📝', 'label' => 'Logs'],
    'monitor' => ['url' => '/admin/monitor_api.php', 'icon' => '🔍', 'label' => 'Monitor'],
];
?>

<!-- Menu de Navegação -->
<div class="bg-white border-b border-gray-200 sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4">
        <nav class="flex gap-1 overflow-x-auto">
            <?php foreach ($menuItems as $key => $item): ?>
                <?php 
                $isActive = ($currentPage === $key);
                $classes = $isActive 
                    ? 'px-4 py-3 text-sm font-medium text-purple-600 border-b-2 border-purple-600 whitespace-nowrap'
                    : 'px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 whitespace-nowrap';
                ?>
                <a href="<?php echo $item['url']; ?>" class="<?php echo $classes; ?>">
                    <?php echo $item['icon']; ?> <?php echo $item['label']; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
