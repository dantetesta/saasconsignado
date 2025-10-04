# 🚀 Sistema de Consignados SaaS

[![PHP](https://img.shields.io/badge/PHP-8.0+-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/Version-2.0.0-red.svg)](https://github.com/dantetesta/saasconsignado)

Sistema completo de gestão de consignações transformado em **plataforma SaaS multi-tenant** com planos Free e Pro, pagamentos recorrentes e emails personalizados.

## ✨ Funcionalidades

### 🆓 Plano Free (Grátis)
- ✅ Até 5 estabelecimentos
- ✅ 5 consignações por estabelecimento  
- ✅ Controle completo de produtos
- ✅ Relatórios básicos
- ✅ Suporte por email

### 💎 Plano Pro (R$ 20/mês)
- ✅ Estabelecimentos **ILIMITADOS**
- ✅ Consignações **ILIMITADAS**
- ✅ Emails personalizados com sua marca
- ✅ Relatórios avançados
- ✅ Suporte prioritário

### 🔐 Multi-Tenancy
- ✅ Isolamento total entre empresas
- ✅ Zero vazamento de dados
- ✅ Subdomínios personalizados
- ✅ Cadastro self-service

### 💳 Pagamentos
- ✅ Integração **Pagou.com.br**
- ✅ PIX (confirmação instantânea)
- ✅ Boleto (2 dias úteis)
- ✅ Renovação automática
- ✅ Webhooks de confirmação

### 📧 Emails
- ✅ Integração **Postmark**
- ✅ Identidade personalizada por empresa
- ✅ Reply-To customizado
- ✅ Templates responsivos

## 🛠️ Tecnologias

- **Backend:** PHP 8.0+ (Vanilla)
- **Frontend:** HTML5, TailwindCSS, JavaScript
- **Banco:** MySQL 8.0+
- **Pagamentos:** Pagou.com.br API
- **Emails:** Postmark API
- **Arquitetura:** Multi-tenant (Row-level)

## 📦 Instalação

### 1. Clone o repositório
```bash
git clone https://github.com/dantetesta/saasconsignado.git
cd saasconsignado
```

### 2. Configure o banco de dados
```bash
# Edite config/database.php com suas credenciais
cp config/database.example.php config/database.php
```

### 3. Execute a migração
```bash
php run_saas_migration.php
```

### 4. Configure as integrações
```bash
# Copie o arquivo de exemplo
cp config/integrations.example.php config/integrations.php

# Edite config/integrations.php com suas credenciais:
# - Pagou.com.br API Keys
# - Postmark Tokens
```

### 5. Configure o servidor web
```bash
# Desenvolvimento
php -S localhost:8080

# Produção: Configure Apache/Nginx
```

## 🎯 Uso Rápido

### 1. Primeiro Acesso
1. Acesse: `http://localhost:8080/register.php`
2. Crie sua conta grátis
3. Comece a usar o plano Free!

### 2. Upgrade para Pro
1. Acesse: **Fazer Upgrade** no menu
2. Escolha PIX ou Boleto
3. Complete o pagamento
4. Funcionalidades ilimitadas liberadas!

### 3. Fluxo Básico
```
Cadastrar Estabelecimento → Cadastrar Produtos → 
Criar Consignação → Enviar Link → Cliente Atualiza Vendas → 
Receber Pagamento → Finalizar
```

## 📊 Estrutura do Banco

### Tabelas Principais
- `tenants` - Empresas/Contas
- `usuarios` - Usuários (com tenant_id)
- `estabelecimentos` - Clientes (com tenant_id)
- `produtos` - Catálogo (com tenant_id)
- `consignacoes` - Operações (com tenant_id)

### Sistema de Assinaturas
- `subscription_plans` - Planos (Free/Pro)
- `subscriptions` - Assinaturas ativas
- `payment_transactions` - Histórico de pagamentos

### Multi-Tenancy
Todas as tabelas principais incluem `tenant_id` para isolamento completo.

## 🔧 Configuração

### Pagou.com.br
1. Crie conta em https://pagou.com.br
2. Obtenha API Keys (sandbox + produção)
3. Configure webhook: `https://seusite.com/webhooks/pagou.php`

### Postmark
1. Crie conta em https://postmarkapp.com
2. Verifique seu domínio
3. Obtenha Account + Server Tokens
4. Configure webhook: `https://seusite.com/webhooks/postmark.php`

## 🚀 Deploy

### Requisitos do Servidor
- PHP 8.0+
- MySQL 8.0+
- SSL/HTTPS (obrigatório)
- CURL habilitado

### Checklist de Produção
- [ ] Credenciais de produção configuradas
- [ ] HTTPS ativo
- [ ] Webhooks configurados
- [ ] Backup automático
- [ ] Monitoramento ativo

## 📈 Roadmap

### Versão 2.1 (Próxima)
- [ ] API REST completa
- [ ] Dashboard analytics
- [ ] Relatórios avançados (Pro)
- [ ] Exportação CSV/PDF

### Versão 2.2 (Futuro)
- [ ] App mobile (PWA)
- [ ] Notificações push
- [ ] Multi-idioma
- [ ] Programa de afiliados

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch: `git checkout -b feature/nova-funcionalidade`
3. Commit: `git commit -m 'Adiciona nova funcionalidade'`
4. Push: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para detalhes.

## 👨‍💻 Autor

**Dante Testa**
- Website: [dantetesta.com.br](https://dantetesta.com.br)
- GitHub: [@dantetesta](https://github.com/dantetesta)
- Email: contato@dantetesta.com.br

## 🎉 Agradecimentos

- Comunidade PHP
- TailwindCSS
- Pagou.com.br
- Postmark

---

**⭐ Se este projeto te ajudou, deixe uma estrela!**

**🚀 Transforme seu negócio com SaaS!**
