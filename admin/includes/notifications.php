<?php
/**
 * Sistema de Notificações Flutuantes para Admin
 * Toast notifications modernas
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

// Verificar se há mensagem de sucesso ou erro na sessão
$hasNotification = !empty($success) || !empty($error);

if ($hasNotification):
    $notificationType = !empty($success) ? 'success' : 'error';
    $notificationMessage = !empty($success) ? $success : $error;
    
    $bgColor = [
        'success' => 'bg-gradient-to-r from-green-500 to-emerald-600',
        'error' => 'bg-gradient-to-r from-red-500 to-pink-600',
        'warning' => 'bg-gradient-to-r from-yellow-500 to-orange-600',
        'info' => 'bg-gradient-to-r from-blue-500 to-indigo-600'
    ];
    
    $iconPath = [
        'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'error' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    ];
?>

<!-- Toast Container (Canto Superior Direito) -->
<div id="adminToast" class="fixed top-20 right-6 z-50 transform transition-all duration-500 ease-in-out opacity-0 translate-x-full" style="opacity: 0;">
    <div class="<?php echo $bgColor[$notificationType]; ?> text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 min-w-[320px] max-w-md backdrop-blur-sm">
        <!-- Ícone -->
        <div class="flex-shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $iconPath[$notificationType]; ?>"></path>
            </svg>
        </div>
        
        <!-- Mensagem -->
        <div class="flex-1">
            <p class="font-medium"><?php echo htmlspecialchars($notificationMessage); ?></p>
        </div>
        
        <!-- Botão Fechar -->
        <button onclick="closeAdminToast()" class="flex-shrink-0 hover:bg-white hover:bg-opacity-20 rounded-lg p-1 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>

<script>
// Mostrar toast com animação suave
setTimeout(() => {
    const toast = document.getElementById('adminToast');
    if (toast) {
        toast.style.opacity = '1';
        toast.classList.remove('translate-x-full');
        toast.classList.add('translate-x-0');
        
        // Auto-fechar após 5 segundos
        setTimeout(() => {
            closeAdminToast();
        }, 5000);
    }
}, 100);

function closeAdminToast() {
    const toast = document.getElementById('adminToast');
    if (toast) {
        toast.style.opacity = '0';
        toast.classList.remove('translate-x-0');
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            toast.remove();
        }, 500);
    }
}
</script>

<?php endif; ?>
