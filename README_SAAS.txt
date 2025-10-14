===============================================
🚀 SISTEMA DE CONSIGNADOS - SAAS 2.0.0
===============================================

Sistema Multi-Tenant completo para gestão de consignações
com planos Free e Pro, pagamentos recorrentes e emails personalizados.

Desenvolvido por: Dante Testa (https://dantetesta.com.br)

===============================================
✨ FUNCIONALIDADES
===============================================

PLANO FREE (Grátis):
• 5 estabelecimentos
• 5 consignações por estabelecimento
• Gestão completa de produtos
• Relatórios básicos

PLANO PRO (R$ 20/mês):
• Estabelecimentos ILIMITADOS
• Consignações ILIMITADAS
• Emails personalizados com sua marca
• Relatórios avançados
• Suporte prioritário

===============================================
🔧 CONFIGURAÇÃO RÁPIDA
===============================================

1. BANCO DE DADOS:
   ✅ Já configurado e migrado

2. CREDENCIAIS (config/integrations.php):
   - Pagou.com.br → API Keys
   - Postmark → Tokens

3. WEBHOOKS:
   - Pagou: http://seusite.com/webhooks/pagou.php
   - Postmark: http://seusite.com/webhooks/postmark.php

===============================================
📂 ARQUIVOS PRINCIPAIS
===============================================

CLASSES:
• TenantMiddleware.php → Isolamento multi-tenant
• PagouIntegration.php → Pagamentos
• MultiTenantEmailManager.php → Emails

PÁGINAS:
• register.php → Cadastro self-service
• login.php → Login
• upgrade.php → Upgrade Free → Pro
• assinatura.php → Gerenciar plano

===============================================
🧪 TESTAR
===============================================

1. Acesse: http://seusite.com/register.php
2. Crie uma conta grátis
3. Teste as funcionalidades Free
4. Faça upgrade para Pro
5. Configure emails personalizados

===============================================
📚 DOCUMENTAÇÃO COMPLETA
===============================================

• SAAS_IMPLEMENTACAO.txt → Guia Etapa 1
• SAAS_COMPLETO.txt → Documentação completa
• README_SAAS.txt → Este arquivo

===============================================
✅ STATUS: PRONTO PARA PRODUÇÃO!
===============================================
