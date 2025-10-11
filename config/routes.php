<?php
/**
 * Rotas do Sistema - URLs Amigáveis e SEO-Friendly
 * 
 * Define todas as rotas do sistema de forma centralizada
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

// Criar instância do Router
$router = new Router();

// ============================================
// ROTAS PÚBLICAS (sem autenticação)
// ============================================

// Página inicial / Landing Page
$router->get('/', function() {
    if (isLoggedIn()) {
        redirect('/dashboard');
    } else {
        require 'home.php';
    }
}, 'home');

// Login
$router->any('/login', 'login.php', 'login');

// Registro
$router->any('/register', 'register.php', 'register');
$router->any('/registro', 'register.php', 'registro'); // Alias em português

// Consulta pública
$router->get('/consulta', 'consulta_publica.php', 'consulta');
$router->get('/consulta-publica', 'consulta_publica.php', 'consulta-publica');

// Conta bloqueada
$router->get('/conta-bloqueada', 'conta_bloqueada.php', 'conta-bloqueada');

// Upgrade de plano
$router->get('/upgrade', 'upgrade.php', 'upgrade');
$router->get('/planos', 'upgrade.php', 'planos'); // Alias

// Página de upgrade PIX
$router->get('/upgrade/pix', 'upgrade_pix.php', 'upgrade-pix');

// Renovação de assinatura
$router->get('/renovar', 'renovar.php', 'renovar');

// ============================================
// ROTAS AUTENTICADAS (requerem login)
// ============================================

// Dashboard
$router->get('/dashboard', function() {
    (new Router())->requireAuth();
    require 'dashboard.php';
}, 'dashboard');

// Perfil do usuário
$router->get('/perfil', function() {
    (new Router())->requireAuth();
    require 'perfil.php';
}, 'perfil');

// Assinatura
$router->get('/assinatura', function() {
    (new Router())->requireAuth();
    require 'assinatura.php';
}, 'assinatura');

// ============================================
// MÓDULO: CONSIGNAÇÕES
// ============================================

// Listar consignações
$router->get('/consignacoes', function() {
    (new Router())->requireAuth();
    require 'consignacoes.php';
}, 'consignacoes');

// Ver detalhes de uma consignação
$router->get('/consignacao/{id}', function($id) {
    (new Router())->requireAuth();
    $_GET['id'] = $id;
    $_GET['action'] = 'view';
    require 'consignacoes.php';
}, 'consignacao-detalhes');

// Editar consignação
$router->any('/consignacao/{id}/editar', function($id) {
    (new Router())->requireAuth();
    $_GET['id'] = $id;
    $_GET['action'] = 'edit';
    require 'consignacoes.php';
}, 'consignacao-editar');

// Nova consignação
$router->any('/consignacao/nova', function() {
    (new Router())->requireAuth();
    $_GET['action'] = 'new';
    require 'consignacoes.php';
}, 'consignacao-nova');

// ============================================
// MÓDULO: PRODUTOS
// ============================================

// Listar produtos
$router->get('/produtos', function() {
    (new Router())->requireAuth();
    require 'produtos.php';
}, 'produtos');

// Ver detalhes de um produto
$router->get('/produto/{id}', function($id) {
    (new Router())->requireAuth();
    $_GET['id'] = $id;
    $_GET['action'] = 'view';
    require 'produtos.php';
}, 'produto-detalhes');

// Editar produto
$router->any('/produto/{id}/editar', function($id) {
    (new Router())->requireAuth();
    $_GET['id'] = $id;
    $_GET['action'] = 'edit';
    require 'produtos.php';
}, 'produto-editar');

// Novo produto
$router->any('/produto/novo', function() {
    (new Router())->requireAuth();
    $_GET['action'] = 'new';
    require 'produtos.php';
}, 'produto-novo');

// ============================================
// MÓDULO: ESTABELECIMENTOS
// ============================================

// Listar estabelecimentos
$router->get('/estabelecimentos', function() {
    (new Router())->requireAuth();
    require 'estabelecimentos.php';
}, 'estabelecimentos');

// Ver detalhes de um estabelecimento
$router->get('/estabelecimento/{id}', function($id) {
    (new Router())->requireAuth();
    $_GET['id'] = $id;
    $_GET['action'] = 'view';
    require 'estabelecimentos.php';
}, 'estabelecimento-detalhes');

// Editar estabelecimento
$router->any('/estabelecimento/{id}/editar', function($id) {
    (new Router())->requireAuth();
    $_GET['id'] = $id;
    $_GET['action'] = 'edit';
    require 'estabelecimentos.php';
}, 'estabelecimento-editar');

// Novo estabelecimento
$router->any('/estabelecimento/novo', function() {
    (new Router())->requireAuth();
    $_GET['action'] = 'new';
    require 'estabelecimentos.php';
}, 'estabelecimento-novo');

// ============================================
// MÓDULO: MOVIMENTAÇÕES
// ============================================

// Listar movimentações
$router->get('/movimentacoes', function() {
    (new Router())->requireAuth();
    require 'movimentacoes.php';
}, 'movimentacoes');

// Ver detalhes de uma movimentação
$router->get('/movimentacao/{id}', function($id) {
    (new Router())->requireAuth();
    $_GET['id'] = $id;
    $_GET['action'] = 'view';
    require 'movimentacoes.php';
}, 'movimentacao-detalhes');

// ============================================
// MÓDULO: RELATÓRIOS
// ============================================

// Relatórios gerais
$router->get('/relatorios', function() {
    (new Router())->requireAuth();
    require 'relatorios.php';
}, 'relatorios');

// Relatório por tipo
$router->get('/relatorio/{tipo}', function($tipo) {
    (new Router())->requireAuth();
    $_GET['tipo'] = $tipo;
    require 'relatorios.php';
}, 'relatorio-tipo');

// Relatório por período
$router->get('/relatorio/{tipo}/{ano}/{mes}', function($tipo, $ano, $mes) {
    (new Router())->requireAuth();
    $_GET['tipo'] = $tipo;
    $_GET['ano'] = $ano;
    $_GET['mes'] = $mes;
    require 'relatorios.php';
}, 'relatorio-periodo');

// ============================================
// ROTAS ADMINISTRATIVAS (SuperAdmin)
// ============================================

// Painel Admin
$router->get('/admin', function() {
    (new Router())->requireAdmin();
    require 'admin/index.php';
}, 'admin');

// Gerenciar tenants
$router->get('/admin/tenants', function() {
    (new Router())->requireAdmin();
    require 'admin/tenants.php';
}, 'admin-tenants');

// Gerenciar usuários
$router->get('/admin/usuarios', function() {
    (new Router())->requireAdmin();
    require 'admin/usuarios.php';
}, 'admin-usuarios');

// Gerenciar planos
$router->any('/admin/planos', function() {
    (new Router())->requireAdmin();
    require 'admin/planos.php';
}, 'admin-planos');

// Gerenciar pagamentos
$router->get('/admin/pagamentos', function() {
    (new Router())->requireAdmin();
    require 'admin/pagamentos.php';
}, 'admin-pagamentos');

// Monitor de API
$router->get('/admin/monitor', function() {
    (new Router())->requireAdmin();
    require 'admin/monitor_api.php';
}, 'admin-monitor');

// Configurações do sistema
$router->any('/admin/configuracoes', function() {
    (new Router())->requireAdmin();
    require 'admin/configuracoes.php';
}, 'admin-configuracoes');

// ============================================
// ROTAS DE API
// ============================================

// Verificar pagamento
$router->post('/api/verificar-pagamento', 'api/verificar_pagamento.php', 'api-verificar-pagamento');

// Webhook Pagou
$router->post('/webhooks/pagou', 'webhooks/pagou.php', 'webhook-pagou');

// ============================================
// ROTAS UTILITÁRIAS
// ============================================

// Logout
$router->get('/logout', 'logout.php', 'logout');
$router->get('/sair', 'logout.php', 'sair'); // Alias em português

// Trocar tema
$router->post('/change-theme', 'change_theme.php', 'change-theme');

// Enviar e-mail
$router->post('/enviar-email', 'enviar_email.php', 'enviar-email');

// ============================================
// EXECUTAR ROTEAMENTO
// ============================================

return $router;
