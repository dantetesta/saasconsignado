# 🔐 Implementação de Segurança de Credenciais

**Autor:** Dante Testa <https://dantetesta.com.br>  
**Versão:** 2.1.0  
**Data:** 14/10/2025

## 📋 Resumo da Implementação

Esta melhoria remove as credenciais hardcoded do código e implementa um sistema seguro de variáveis de ambiente usando arquivos `.env`.

## 🔧 Arquivos Criados/Modificados

### ✅ Novos Arquivos:
- `classes/EnvLoader.php` - Classe para carregar variáveis de ambiente
- `.env.example` - Template de configuração
- `setup_env.php` - Script de configuração inicial
- `SEGURANCA_CREDENCIAIS.md` - Esta documentação

### ✅ Arquivos Modificados:
- `config/database.php` - Atualizado para usar variáveis de ambiente

## 🚀 Como Implementar

### 1. Executar Script de Configuração
```bash
cd /caminho/para/projeto
php setup_env.php
```

### 2. Configurar Variáveis no .env
Edite o arquivo `.env` criado com suas credenciais reais:

```env
# Banco de dados
DB_HOST=seu_host_real
DB_USER=seu_usuario_real
DB_PASS=sua_senha_real
DB_NAME=seu_banco_real

# APIs
PAGOU_API_KEY=sua_api_key_real
POSTMARK_SERVER_TOKEN=seu_token_real

# Configurações
APP_URL=https://seudominio.com.br
ALERT_EMAIL=admin@seudominio.com.br
```

### 3. Testar Configuração
Acesse o sistema normalmente. Se houver erro:
- Verifique `logs/errors.log`
- Confirme que todas as variáveis obrigatórias estão definidas
- Verifique permissões do arquivo `.env`

## 🔒 Segurança Implementada

### ✅ Benefícios:
- **Credenciais fora do código** - Não aparecem no Git
- **Ambientes diferentes** - Dev/Prod com configurações distintas
- **Validação automática** - Sistema verifica variáveis obrigatórias
- **Parsing inteligente** - Converte strings para tipos corretos
- **Fallbacks seguros** - Valores padrão quando apropriado

### ✅ Proteções:
- Arquivo `.env` no `.gitignore`
- Validação de variáveis obrigatórias
- Logs de erro para debugging
- Parsing seguro de valores

## 📊 Variáveis Disponíveis

### 🗄️ Banco de Dados
- `DB_HOST` - Host do banco
- `DB_USER` - Usuário do banco
- `DB_PASS` - Senha do banco
- `DB_NAME` - Nome do banco
- `DB_CHARSET` - Charset (padrão: utf8mb4)

### 🌐 Aplicação
- `APP_ENV` - Ambiente (production/development)
- `APP_DEBUG` - Debug ativo (true/false)
- `APP_URL` - URL base da aplicação
- `APP_KEY` - Chave secreta da aplicação
- `CSRF_SECRET` - Chave para tokens CSRF

### 💳 APIs Externas
- `PAGOU_API_KEY` - Chave da API Pagou
- `PAGOU_ENVIRONMENT` - Ambiente Pagou (production/sandbox)
- `POSTMARK_SERVER_TOKEN` - Token do servidor Postmark
- `POSTMARK_ACCOUNT_TOKEN` - Token da conta Postmark

### 🛡️ Segurança
- `TURNSTILE_SITE_KEY` - Chave do site Cloudflare Turnstile
- `TURNSTILE_SECRET_KEY` - Chave secreta Turnstile

### 📊 Monitoramento
- `ALERT_EMAIL` - Email para alertas do sistema
- `SLACK_WEBHOOK_URL` - URL do webhook Slack

### 💾 Backup
- `BACKUP_ENABLED` - Backup ativo (true/false)
- `BACKUP_RETENTION_DAYS` - Dias de retenção
- `AWS_S3_BUCKET` - Bucket S3 para backup
- `AWS_ACCESS_KEY` - Chave de acesso AWS
- `AWS_SECRET_KEY` - Chave secreta AWS

## 🔧 Uso da Classe EnvLoader

### Carregar Variáveis
```php
// Carregar automaticamente
EnvLoader::load();

// Carregar arquivo específico
EnvLoader::load('/caminho/para/.env');
```

### Obter Valores
```php
// Valor simples
$host = EnvLoader::get('DB_HOST');

// Com valor padrão
$debug = EnvLoader::get('APP_DEBUG', false);

// Verificar se existe
if (EnvLoader::has('PAGOU_API_KEY')) {
    // Usar API
}
```

### Validar Configuração
```php
$required = ['DB_HOST', 'DB_USER', 'DB_PASS'];
$missing = EnvLoader::validateRequired($required);

if (!empty($missing)) {
    die('Variáveis faltando: ' . implode(', ', $missing));
}
```

## 🚨 Troubleshooting

### Erro: "Variáveis de ambiente não encontradas"
- Verifique se arquivo `.env` existe
- Confirme que variáveis obrigatórias estão definidas
- Execute `php setup_env.php` novamente

### Erro: "Erro ao conectar com o banco"
- Verifique credenciais no `.env`
- Teste conexão manual com as credenciais
- Confirme que servidor MySQL está ativo

### Arquivo .env não carrega
- Verifique permissões do arquivo (644)
- Confirme que não há caracteres especiais
- Verifique sintaxe: `CHAVE=valor` (sem espaços)

## 📈 Próximas Melhorias

### Versão 2.2:
- [ ] Criptografia de valores sensíveis no .env
- [ ] Rotação automática de chaves
- [ ] Integração com HashiCorp Vault
- [ ] Auditoria de acesso às variáveis

### Versão 2.3:
- [ ] Interface web para gerenciar configurações
- [ ] Backup automático de configurações
- [ ] Sincronização entre ambientes
- [ ] Validação de tipos mais robusta

## ✅ Status da Implementação

- [x] Classe EnvLoader criada
- [x] config/database.php atualizado
- [x] Script de setup criado
- [x] Template .env.example criado
- [x] Documentação completa
- [x] .gitignore atualizado
- [x] Validação de variáveis obrigatórias
- [x] Parsing de tipos automático
- [x] Sistema de fallbacks

**🎉 IMPLEMENTAÇÃO COMPLETA - PRONTA PARA USO!**
