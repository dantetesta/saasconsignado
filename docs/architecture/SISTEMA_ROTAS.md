# 🚀 Sistema de Roteamento Avançado - URLs Amigáveis

Sistema completo de roteamento com URLs semânticas e SEO-friendly para o SaaS Sisteminha.

**Autor:** [Dante Testa](https://dantetesta.com.br)  
**Versão:** 2.0.0  
**Data:** Outubro 2025

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Arquitetura](#arquitetura)
3. [URLs Disponíveis](#urls-disponíveis)
4. [Como Usar](#como-usar)
5. [Adicionar Novas Rotas](#adicionar-novas-rotas)
6. [Exemplos Práticos](#exemplos-práticos)

---

## 🎯 Visão Geral

O sistema de roteamento permite criar URLs limpas, amigáveis e otimizadas para SEO, transformando:

### ❌ URLs Antigas (Não Amigáveis)
```
/produtos.php?id=123&action=edit
/relatorios.php?tipo=mensal&ano=2025&mes=01
/estabelecimentos.php?id=45
```

### ✅ URLs Novas (Amigáveis e SEO-Friendly)
```
/produto/123/editar
/relatorio/mensal/2025/01
/estabelecimento/45
```

---

## 🏗️ Arquitetura

### Arquivos Principais

```
sass-sisteminha/
├── .htaccess                    # Configuração Apache para roteamento
├── index.php                    # Ponto de entrada - executa o router
├── 404.php                      # Página de erro personalizada
├── classes/
│   └── Router.php              # Classe principal de roteamento
└── config/
    └── routes.php              # Definição de todas as rotas
```

### Fluxo de Execução

1. **Usuário acessa:** `/produto/123/editar`
2. **Apache (.htaccess):** Redireciona para `index.php`
3. **index.php:** Carrega o Router e as rotas
4. **Router:** Identifica a rota correspondente
5. **Callback:** Executa a função/arquivo associado
6. **Resposta:** Retorna o conteúdo para o usuário

---

## 🌐 URLs Disponíveis

### 🏠 Rotas Públicas

| URL | Descrição | Arquivo |
|-----|-----------|---------|
| `/` | Página inicial / Landing page | `home.php` |
| `/login` | Login de usuários | `login.php` |
| `/register` ou `/registro` | Cadastro de novos usuários | `register.php` |
| `/consulta` ou `/consulta-publica` | Consulta pública | `consulta_publica.php` |
| `/upgrade` ou `/planos` | Página de upgrade de plano | `upgrade.php` |
| `/renovar` | Renovação de assinatura | `renovar.php` |

### 🔐 Rotas Autenticadas

| URL | Descrição | Arquivo |
|-----|-----------|---------|
| `/dashboard` | Dashboard principal | `dashboard.php` |
| `/perfil` | Perfil do usuário | `perfil.php` |
| `/assinatura` | Gerenciar assinatura | `assinatura.php` |

### 📦 Módulo: Produtos

| URL | Descrição | Parâmetros |
|-----|-----------|------------|
| `/produtos` | Listar todos os produtos | - |
| `/produto/{id}` | Ver detalhes do produto | `id` = ID do produto |
| `/produto/{id}/editar` | Editar produto | `id` = ID do produto |
| `/produto/novo` | Criar novo produto | - |

**Exemplos:**
- `/produto/123` - Ver produto #123
- `/produto/123/editar` - Editar produto #123
- `/produto/novo` - Criar novo produto

### 🏪 Módulo: Estabelecimentos

| URL | Descrição | Parâmetros |
|-----|-----------|------------|
| `/estabelecimentos` | Listar estabelecimentos | - |
| `/estabelecimento/{id}` | Ver detalhes | `id` = ID do estabelecimento |
| `/estabelecimento/{id}/editar` | Editar | `id` = ID do estabelecimento |
| `/estabelecimento/novo` | Criar novo | - |

### 📋 Módulo: Consignações

| URL | Descrição | Parâmetros |
|-----|-----------|------------|
| `/consignacoes` | Listar consignações | - |
| `/consignacao/{id}` | Ver detalhes | `id` = ID da consignação |
| `/consignacao/{id}/editar` | Editar | `id` = ID da consignação |
| `/consignacao/nova` | Criar nova | - |

### 💰 Módulo: Movimentações

| URL | Descrição | Parâmetros |
|-----|-----------|------------|
| `/movimentacoes` | Listar movimentações | - |
| `/movimentacao/{id}` | Ver detalhes | `id` = ID da movimentação |

### 📊 Módulo: Relatórios

| URL | Descrição | Parâmetros |
|-----|-----------|------------|
| `/relatorios` | Página de relatórios | - |
| `/relatorio/{tipo}` | Relatório por tipo | `tipo` = mensal, anual, etc |
| `/relatorio/{tipo}/{ano}/{mes}` | Relatório específico | `tipo`, `ano`, `mes` |

**Exemplos:**
- `/relatorio/mensal` - Relatório mensal
- `/relatorio/mensal/2025/10` - Relatório de outubro/2025

### 👑 Rotas Administrativas (SuperAdmin)

| URL | Descrição |
|-----|-----------|
| `/admin` | Painel administrativo |
| `/admin/tenants` | Gerenciar tenants |
| `/admin/usuarios` | Gerenciar usuários |
| `/admin/planos` | Gerenciar planos |
| `/admin/pagamentos` | Gerenciar pagamentos |
| `/admin/monitor` | Monitor de API |
| `/admin/configuracoes` | Configurações do sistema |

### 🔌 Rotas de API

| URL | Método | Descrição |
|-----|--------|-----------|
| `/api/verificar-pagamento` | POST | Verificar status de pagamento |
| `/webhooks/pagou` | POST | Webhook da API Pagou |

---

## 💡 Como Usar

### Gerando URLs no Código

Use o método `url()` do Router para gerar URLs nomeadas:

```php
// Exemplo 1: URL simples
$router->url('produtos'); // Retorna: /produtos

// Exemplo 2: URL com parâmetros
$router->url('produto-detalhes', ['id' => 123]); // Retorna: /produto/123

// Exemplo 3: URL de edição
$router->url('produto-editar', ['id' => 123]); // Retorna: /produto/123/editar

// Exemplo 4: Relatório com múltiplos parâmetros
$router->url('relatorio-periodo', [
    'tipo' => 'mensal',
    'ano' => 2025,
    'mes' => 10
]); // Retorna: /relatorio/mensal/2025/10
```

### Usando em Links HTML

```php
<a href="<?php echo $router->url('produto-detalhes', ['id' => $produto['id']]); ?>">
    Ver Produto
</a>

<a href="<?php echo $router->url('produto-editar', ['id' => $produto['id']]); ?>">
    Editar
</a>
```

---

## ➕ Adicionar Novas Rotas

### 1. Edite o arquivo `config/routes.php`

```php
// Rota simples
$router->get('/minha-rota', function() {
    require 'minha_pagina.php';
}, 'minha-rota');

// Rota com parâmetro
$router->get('/usuario/{id}', function($id) {
    $_GET['id'] = $id;
    require 'usuario.php';
}, 'usuario-detalhes');

// Rota com múltiplos parâmetros
$router->get('/post/{categoria}/{slug}', function($categoria, $slug) {
    $_GET['categoria'] = $categoria;
    $_GET['slug'] = $slug;
    require 'post.php';
}, 'post-detalhes');

// Rota autenticada
$router->get('/area-restrita', function() {
    (new Router())->requireAuth();
    require 'area_restrita.php';
}, 'area-restrita');

// Rota admin
$router->get('/admin/secreta', function() {
    (new Router())->requireAdmin();
    require 'admin/secreta.php';
}, 'admin-secreta');
```

### 2. Tipos de Rotas

```php
// GET - Apenas requisições GET
$router->get('/rota', 'arquivo.php', 'nome');

// POST - Apenas requisições POST
$router->post('/rota', 'arquivo.php', 'nome');

// ANY - Aceita GET e POST
$router->any('/rota', 'arquivo.php', 'nome');
```

---

## 📚 Exemplos Práticos

### Exemplo 1: Criar Rota de Perfil de Usuário

```php
// Em config/routes.php
$router->get('/usuario/{id}/perfil', function($id) {
    (new Router())->requireAuth();
    $_GET['user_id'] = $id;
    require 'perfil_usuario.php';
}, 'usuario-perfil');

// Usar no código
echo $router->url('usuario-perfil', ['id' => 456]);
// Resultado: /usuario/456/perfil
```

### Exemplo 2: Rota de Download de Arquivo

```php
// Em config/routes.php
$router->get('/download/{tipo}/{id}', function($tipo, $id) {
    (new Router())->requireAuth();
    $_GET['tipo'] = $tipo;
    $_GET['id'] = $id;
    require 'download.php';
}, 'download');

// Usar no código
echo $router->url('download', ['tipo' => 'relatorio', 'id' => 789]);
// Resultado: /download/relatorio/789
```

### Exemplo 3: Rota de Busca

```php
// Em config/routes.php
$router->get('/busca/{termo}', function($termo) {
    (new Router())->requireAuth();
    $_GET['q'] = urldecode($termo);
    require 'busca.php';
}, 'busca');

// Usar no código
echo $router->url('busca', ['termo' => urlencode('produto teste')]);
// Resultado: /busca/produto%20teste
```

---

## 🔧 Configuração do .htaccess

O arquivo `.htaccess` já está configurado automaticamente. Principais regras:

```apache
# Remove trailing slash
RewriteCond %{REQUEST_URI} !^/$
RewriteCond %{REQUEST_URI} (.+)/$
RewriteRule ^ %1 [R=301,L]

# Permite arquivos estáticos
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{REQUEST_URI} \.(css|js|jpg|jpeg|png|gif|svg|ico)$ [NC]
RewriteRule ^ - [L]

# Redireciona tudo para index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

---

## ✅ Benefícios

### 🎯 SEO
- URLs limpas e descritivas
- Melhor indexação pelos motores de busca
- Keywords nas URLs

### 👥 UX
- URLs fáceis de lembrar
- URLs compartilháveis
- Navegação intuitiva

### 🛠️ Desenvolvimento
- Código organizado e centralizado
- Fácil manutenção
- Roteamento flexível
- Middlewares de autenticação

### 🔒 Segurança
- Validação centralizada
- Controle de acesso por rota
- Proteção contra acesso direto a arquivos

---

## 🐛 Troubleshooting

### Problema: Erro 404 em todas as rotas

**Solução:** Verifique se o módulo `mod_rewrite` está ativo no Apache:
```bash
# No terminal
sudo a2enmod rewrite
sudo service apache2 restart
```

### Problema: Rota não encontrada

**Solução:** Verifique se a rota está definida em `config/routes.php` e se o padrão está correto.

### Problema: Parâmetros não chegam

**Solução:** Certifique-se de que está usando `{nome}` no padrão e que o callback recebe os parâmetros na ordem correta.

---

## 📝 Changelog

### v2.0.0 (Outubro 2025)
- ✅ Sistema de roteamento completo implementado
- ✅ Classe Router com suporte a parâmetros
- ✅ URLs semânticas para todos os módulos
- ✅ Página 404 personalizada
- ✅ Middlewares de autenticação
- ✅ Documentação completa

---

## 👨‍💻 Autor

**Dante Testa**  
🌐 [dantetesta.com.br](https://dantetesta.com.br)  
📧 Contato através do site

---

## 📄 Licença

Este sistema faz parte do SaaS Sisteminha.  
Todos os direitos reservados © 2025 Dante Testa
