# Sistema de Templates do Painel Administrativo

## 📋 Visão Geral

Sistema de componentes reutilizáveis para padronizar header, menu e footer em todas as páginas administrativas.

**Autor:** Dante Testa (https://dantetesta.com.br)  
**Versão:** 1.0.0

---

## 🎯 Benefícios

✅ **Manutenção Centralizada** - Altere uma vez, aplique em todas as páginas  
✅ **Consistência Visual** - Mesmo header e menu em todo o painel  
✅ **Código Limpo** - Menos duplicação, mais organização  
✅ **Fácil Atualização** - Adicionar novos itens ao menu é simples  

---

## 📁 Arquivos do Sistema

### 1. `header.php`
Header padrão **fixo** com logo "Painel Admin" e botão de logout.

**Variáveis esperadas:**
- `$pageTitle` (string, opcional) - Título da aba do navegador (padrão: "Dashboard")
- `$additionalHead` (string, opcional) - HTML adicional para o `<head>`

**Conteúdo fixo do header:**
- Logo: Ícone de prédio/escola
- Título: "Painel Admin"
- Subtítulo: "Gestão do SaaS"
- Botão: "Sair" (sempre visível)

### 2. `menu.php`
Menu de navegação horizontal com highlight da página atual.

**Variável esperada:**
- `$currentPage` (string) - Identificador da página atual

**Páginas disponíveis:**
- `dashboard` - Dashboard principal
- `tenants` - Gestão de assinantes
- `financeiro` - Relatórios financeiros
- `pagamentos` - Histórico de pagamentos
- `gateways` - Configuração de gateways
- `configuracoes` - Configurações gerais
- `logs` - Logs do sistema
- `monitor` - Monitor de API

### 3. `footer.php`
Footer padrão (fecha tags HTML).

### 4. `notifications.php`
Sistema de notificações flutuantes (já existente).

---

## 🔧 Como Usar

### Exemplo Completo

```php
<?php
/**
 * Minha Página Admin
 */

session_start();
require_once '../config/database.php';
require_once '../classes/SuperAdmin.php';

// Verificar autenticação
if (!SuperAdmin::isLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}

$admin = SuperAdmin::getCurrentAdmin();
$db = Database::getInstance()->getConnection();

// ... seu código PHP aqui ...

// ========================================
// CONFIGURAR VARIÁVEIS DO TEMPLATE
// ========================================
$pageTitle = 'Minha Página'; // Título da aba do navegador
$currentPage = 'dashboard'; // Identificador para highlight no menu

// Scripts/CSS adicionais no head (opcional)
$additionalHead = '<meta http-equiv="refresh" content="30">';

// ========================================
// INCLUIR HEADER E MENU
// ========================================
include 'includes/header.php';
include 'includes/menu.php';
?>

<!-- SEU CONTEÚDO AQUI -->
<div class="max-w-7xl mx-auto px-4 py-8">
    
    <?php include 'includes/notifications.php'; ?>
    
    <h1>Meu Conteúdo</h1>
    <!-- ... -->
    
</div>

<?php include 'includes/footer.php'; ?>
```

---

## 🔄 Convertendo Páginas Existentes

### Passo 1: Remover HTML Duplicado

**Remova:**
```php
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>...</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

    <!-- Header Admin -->
    <nav class="bg-gradient-to-r from-purple-600 to-pink-600...">
        ...
    </nav>

    <!-- Menu de Navegação -->
    <div class="bg-white border-b...">
        ...
    </div>
```

### Passo 2: Adicionar Variáveis do Template

**Adicione antes do HTML:**
```php
$pageTitle = 'Nome da Página'; // Título da aba do navegador
$currentPage = 'identificador'; // dashboard, tenants, financeiro, etc.

include 'includes/header.php';
include 'includes/menu.php';
```

### Passo 3: Substituir Footer

**Remova:**
```php
</body>
</html>
```

**Adicione:**
```php
<?php include 'includes/footer.php'; ?>
```

---

## 🎨 Personalizando o Menu

Para adicionar/remover itens do menu, edite o array `$menuItems` em `menu.php`:

```php
$menuItems = [
    'novo_item' => [
        'url' => '/admin/nova_pagina.php',
        'icon' => '🆕',
        'label' => 'Nova Página'
    ],
    // ...
];
```

---

## 📝 Checklist de Conversão

Páginas a converter:

- [x] `pagamentos.php` ✅ (Exemplo implementado)
- [ ] `index.php` (Dashboard)
- [ ] `tenants.php` (Assinantes)
- [ ] `financeiro.php`
- [ ] `gateways.php`
- [ ] `configuracoes.php`
- [ ] `logs.php`
- [ ] `monitor_api.php`

---

## 🐛 Troubleshooting

### Problema: Menu não aparece
**Solução:** Verifique se `$currentPage` está definido antes de incluir `menu.php`

### Problema: Ícone não aparece
**Solução:** Certifique-se de que `$pageIcon` contém HTML válido do SVG

### Problema: Título errado
**Solução:** Defina `$pageTitle` antes de incluir `header.php`

---

## 📞 Suporte

Para dúvidas ou melhorias, entre em contato:
- **Desenvolvedor:** Dante Testa
- **Site:** https://dantetesta.com.br
