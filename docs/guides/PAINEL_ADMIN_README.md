# 🎯 Painel Administrativo do SaaS

Sistema completo de gestão para o dono do SaaS gerenciar tenants, pagamentos e configurações.

**Desenvolvido por:** [Dante Testa](https://dantetesta.com.br)  
**Versão:** 2.0.0  
**Data:** 04/10/2025

---

## 📋 Índice

1. [Instalação](#instalação)
2. [Funcionalidades](#funcionalidades)
3. [Estrutura de Arquivos](#estrutura-de-arquivos)
4. [Como Usar](#como-usar)
5. [Segurança](#segurança)
6. [Troubleshooting](#troubleshooting)

---

## 🚀 Instalação

### Opção 1: Instalador Web (Recomendado)

1. Acesse: `https://seu-dominio.com/admin/install_admin.php`
2. Clique em "Instalar Painel Administrativo"
3. Aguarde a conclusão
4. Anote as credenciais fornecidas

### Opção 2: Linha de Comando

```bash
# Atualizar banco local
mysql -u root -p seu_banco < migrations/create_admin_panel.sql

# Atualizar banco remoto
php update_remote_db.php
```

### Credenciais Padrão

- **Email:** admin@dantetesta.com.br
- **Senha:** admin123
- ⚠️ **ALTERE A SENHA IMEDIATAMENTE APÓS O PRIMEIRO LOGIN!**

---

## ✨ Funcionalidades

### 📊 Dashboard (`/admin/index.php`)

**Métricas em Tempo Real:**
- Total de tenants cadastrados
- Distribuição Free vs Pro
- MRR (Monthly Recurring Revenue)
- Novos cadastros (últimos 30 dias)
- Tenants vencendo (próximos 7 dias)
- Taxa de conversão Free → Pro

**Ações Rápidas:**
- Acesso direto a todas as áreas
- Cards interativos com hover effects
- Visualização de status dos tenants

---

### 👥 Gestão de Tenants (`/admin/tenants.php`)

**Listagem Completa:**
- Nome da empresa
- Email principal
- Plano atual (Free/Pro)
- Status (Ativo/Suspenso/Cancelado/Trial)
- Uso (estabelecimentos e consignações)
- Data de vencimento

**Filtros Disponíveis:**
- 🔍 Busca por nome ou email
- 📦 Filtro por plano (Free/Pro/Todos)
- 🎯 Filtro por status (Ativo/Suspenso/Cancelado/Trial)

**Ações Administrativas:**

1. **🔒 Bloquear Tenant**
   - Suspende o acesso do tenant
   - Solicita motivo (opcional)
   - Registra em logs

2. **🔓 Desbloquear Tenant**
   - Reativa o acesso
   - Registra em logs

3. **⬆️ Alterar Plano**
   - Free ↔ Pro
   - Confirmação obrigatória
   - Registra em logs

4. **📅 Estender Vencimento**
   - Adiciona dias ao vencimento
   - Padrão: 30 dias
   - Personalizável

5. **🗑️ Excluir Tenant**
   - Confirmação dupla (IRREVERSÍVEL)
   - Apaga todos os dados relacionados
   - Registra em logs

---

### 💳 Gateways de Pagamento (`/admin/gateways.php`)

**Gateways Pré-Cadastrados:**

1. **Pagou.com.br**
   - PIX, Boleto, Cartão
   - Gateway brasileiro

2. **Stripe**
   - Cartão de crédito
   - Gateway internacional

3. **Mercado Pago**
   - PIX, Boleto, Cartão
   - Gateway brasileiro

4. **PagSeguro**
   - PIX, Boleto, Cartão
   - Gateway brasileiro

5. **Asaas**
   - PIX, Boleto, Cartão
   - Especializado em recorrência

**Funcionalidades:**
- ✅ Ativar/Desativar com switcher
- ⚙️ Área preparada para configuração de API keys
- 📊 Visualização de métodos disponíveis
- 🔄 Ordenação personalizável

**Status:**
- 🟢 Ativo: Gateway habilitado para uso
- ⚪ Inativo: Gateway desabilitado

---

### 💰 Relatório Financeiro (`/admin/financeiro.php`)

**Receita Mensal:**
- Gráfico de barras dos últimos 6 meses
- Total de pagamentos por mês
- Valor total arrecadado

**Inadimplência:**
- Contador de tenants com pagamento vencido
- Alertas de ação recomendada
- Visualização destacada

---

### 📝 Logs Administrativos (`/admin/logs.php`)

**Registro Completo:**
- Data e hora da ação
- Admin responsável
- Tipo de ação
- Tenant afetado
- Descrição detalhada
- IP e User Agent

**Ações Registradas:**
- Bloqueio de tenant
- Desbloqueio de tenant
- Alteração de plano
- Extensão de vencimento
- Exclusão de tenant

---

## 📁 Estrutura de Arquivos

```
/admin/
├── .htaccess              # Proteção da área admin
├── login.php              # Login do super admin
├── logout.php             # Logout
├── index.php              # Dashboard principal
├── tenants.php            # Gestão de tenants
├── gateways.php           # Configuração de gateways
├── financeiro.php         # Relatório financeiro
├── logs.php               # Logs de ações
└── install_admin.php      # Instalador

/classes/
└── SuperAdmin.php         # Classe de autenticação e gestão

/migrations/
└── create_admin_panel.sql # Migração das tabelas

update_remote_db.php       # Script para atualizar banco remoto
```

---

## 🔐 Segurança

### Autenticação
- Sessão separada dos tenants
- Senha criptografada com `password_hash()`
- Verificação em todas as páginas

### Logs
- Todas as ações são registradas
- IP e User Agent capturados
- Histórico completo de alterações

### Proteção
- `.htaccess` na pasta admin
- Verificação de autenticação em cada página
- Confirmações para ações críticas

### Boas Práticas
1. ✅ Altere a senha padrão imediatamente
2. ✅ Use senhas fortes (mínimo 12 caracteres)
3. ✅ Revise os logs regularmente
4. ✅ Faça backup antes de ações críticas
5. ✅ Não compartilhe credenciais

---

## 💡 Como Usar

### Primeiro Acesso

1. Acesse `/admin/install_admin.php`
2. Clique em "Instalar"
3. Acesse `/admin/login.php`
4. Entre com as credenciais padrão
5. **ALTERE A SENHA IMEDIATAMENTE**

### Gerenciar Tenants

1. Acesse "👥 Tenants" no menu
2. Use os filtros para encontrar tenants
3. Clique nos ícones de ação:
   - 🔒 Bloquear
   - 🔓 Desbloquear
   - 💳 Alterar plano
   - 📅 Estender vencimento
   - 🗑️ Excluir

### Configurar Gateways

1. Acesse "💳 Gateways" no menu
2. Use o switcher para ativar/desativar
3. Clique em "⚙️ Configurar" (em desenvolvimento)

### Ver Relatórios

1. Acesse "💰 Financeiro" no menu
2. Visualize receita mensal
3. Verifique inadimplência

### Consultar Logs

1. Acesse "📝 Logs" no menu
2. Visualize histórico de ações
3. Identifique quem fez o quê e quando

---

## 🔧 Troubleshooting

### Erro: "Tabelas não encontradas"

**Solução:**
```bash
php update_remote_db.php
# ou
mysql -u root -p seu_banco < migrations/create_admin_panel.sql
```

### Erro: "Email ou senha inválidos"

**Solução:**
1. Verifique se a instalação foi concluída
2. Use as credenciais padrão
3. Se necessário, redefina a senha no banco:

```sql
UPDATE super_admins 
SET senha = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'admin@dantetesta.com.br';
-- Senha: admin123
```

### Erro: "Acesso negado"

**Solução:**
1. Verifique permissões do `.htaccess`
2. Certifique-se de estar logado
3. Limpe cookies e tente novamente

### Página em branco

**Solução:**
1. Ative display_errors no PHP
2. Verifique logs de erro do servidor
3. Verifique se todas as classes estão incluídas

---

## 📞 Suporte

**Desenvolvedor:** Dante Testa  
**Website:** [https://dantetesta.com.br](https://dantetesta.com.br)  
**Email:** contato@dantetesta.com.br

---

## 📝 Changelog

### v2.0.0 (04/10/2025)
- ✨ Lançamento inicial do painel admin
- 📊 Dashboard com métricas
- 👥 Gestão completa de tenants
- 💳 Configuração de gateways
- 💰 Relatório financeiro
- 📝 Sistema de logs
- 🔐 Autenticação segura

---

## 📄 Licença

Desenvolvido exclusivamente para o SaaS de Consignados.  
Todos os direitos reservados © 2025 Dante Testa
