-- ============================================
-- Sistema de Consignados - Versão SaaS
-- Migração Multi-Tenant
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Versão: 2.0.0
-- Data: 2025-10-03
-- ============================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- TABELA PRINCIPAL: TENANTS (Empresas/Contas)
-- ============================================

DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_empresa` varchar(255) NOT NULL,
  `subdomain` varchar(50) UNIQUE,
  `documento` varchar(18) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email_principal` varchar(255) NOT NULL,
  
  -- Configurações de plano
  `plano` enum('free','pro') DEFAULT 'free',
  `status` enum('ativo','suspenso','cancelado','trial') DEFAULT 'trial',
  `data_vencimento` date DEFAULT NULL,
  
  -- Configurações de email personalizado
  `email_remetente` varchar(255) DEFAULT NULL,
  `nome_empresa_email` varchar(255) DEFAULT NULL,
  `email_resposta` varchar(255) DEFAULT NULL,
  `smtp_status` enum('pendente','verificado','rejeitado') DEFAULT 'pendente',
  
  -- Limites por plano (NULL = ilimitado)
  `limite_estabelecimentos` int(11) DEFAULT 5,
  `limite_consignacoes_por_estabelecimento` int(11) DEFAULT 5,
  
  -- Auditoria
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `subdomain` (`subdomain`),
  KEY `idx_status` (`status`),
  KEY `idx_plano` (`plano`),
  KEY `idx_email` (`email_principal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: USUARIOS (com tenant_id)
-- ============================================

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nome_empresa` varchar(255) DEFAULT NULL,
  `documento` varchar(18) DEFAULT NULL,
  `email_remetente` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_tenant` (`email`, `tenant_id`),
  KEY `idx_tenant_usuarios` (`tenant_id`),
  KEY `idx_email` (`email`),
  KEY `idx_ativo` (`ativo`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: ESTABELECIMENTOS (com tenant_id)
-- ============================================

DROP TABLE IF EXISTS `estabelecimentos`;
CREATE TABLE `estabelecimentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `senha_acesso` varchar(255) DEFAULT NULL,
  `token_acesso` varchar(64) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_acesso` (`token_acesso`),
  KEY `idx_tenant_estabelecimentos` (`tenant_id`, `ativo`),
  KEY `idx_nome` (`nome`),
  KEY `idx_ativo` (`ativo`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: PRODUTOS (com tenant_id)
-- ============================================

DROP TABLE IF EXISTS `produtos`;
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco_venda` decimal(10,2) NOT NULL,
  `preco_custo` decimal(10,2) DEFAULT 0.00,
  `estoque_total` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_produtos` (`tenant_id`, `ativo`),
  KEY `idx_nome` (`nome`),
  KEY `idx_ativo` (`ativo`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: CONSIGNACOES (com tenant_id)
-- ============================================

DROP TABLE IF EXISTS `consignacoes`;
CREATE TABLE `consignacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `estabelecimento_id` int(11) NOT NULL,
  `data_consignacao` date NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `status` enum('pendente','parcial','finalizada','cancelada') DEFAULT 'pendente',
  `status_manual` tinyint(1) DEFAULT 0,
  `tipo` enum('pontual','continua') DEFAULT 'pontual',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_consignacoes` (`tenant_id`, `status`),
  KEY `idx_estabelecimento` (`estabelecimento_id`),
  KEY `idx_data_consignacao` (`data_consignacao`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: CONSIGNACAO_ITENS (com tenant_id)
-- ============================================

DROP TABLE IF EXISTS `consignacao_itens`;
CREATE TABLE `consignacao_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `consignacao_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade_consignada` int(11) NOT NULL,
  `quantidade_vendida` int(11) DEFAULT 0,
  `quantidade_devolvida` int(11) DEFAULT 0,
  `preco_unitario` decimal(10,2) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_itens` (`tenant_id`),
  KEY `idx_consignacao` (`consignacao_id`),
  KEY `idx_produto` (`produto_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`consignacao_id`) REFERENCES `consignacoes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: MOVIMENTACOES_CONSIGNACAO (com tenant_id)
-- ============================================

DROP TABLE IF EXISTS `movimentacoes_consignacao`;
CREATE TABLE `movimentacoes_consignacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `consignacao_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `tipo` enum('entrega','venda','devolucao') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `data_movimentacao` date NOT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_movimentacoes` (`tenant_id`),
  KEY `idx_consignacao` (`consignacao_id`),
  KEY `idx_produto` (`produto_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_data` (`data_movimentacao`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`consignacao_id`) REFERENCES `consignacoes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: PAGAMENTOS (com tenant_id)
-- ============================================

DROP TABLE IF EXISTS `pagamentos`;
CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `consignacao_id` int(11) NOT NULL,
  `data_pagamento` date NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `forma_pagamento` enum('dinheiro','pix','cartao_debito','cartao_credito','transferencia','outro') DEFAULT 'dinheiro',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_pagamentos` (`tenant_id`),
  KEY `idx_consignacao` (`consignacao_id`),
  KEY `idx_data_pagamento` (`data_pagamento`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`consignacao_id`) REFERENCES `consignacoes`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SISTEMA DE ASSINATURAS
-- ============================================

DROP TABLE IF EXISTS `subscription_plans`;
CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `slug` varchar(50) UNIQUE NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `periodo_dias` int(11) NOT NULL DEFAULT 30,
  `limite_estabelecimentos` int(11) NULL COMMENT 'NULL = ilimitado',
  `limite_consignacoes_por_estabelecimento` int(11) NULL COMMENT 'NULL = ilimitado',
  `features` json DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir planos padrão
INSERT INTO `subscription_plans` (`nome`, `slug`, `preco`, `limite_estabelecimentos`, `limite_consignacoes_por_estabelecimento`, `features`) VALUES
('Free', 'free', 0.00, 5, 5, '["basic_features", "email_support"]'),
('Pro', 'pro', 20.00, NULL, NULL, '["unlimited_features", "priority_support", "custom_emails", "advanced_reports"]');

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('ativa','cancelada','suspensa','pendente') DEFAULT 'pendente',
  `data_inicio` date NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_cancelamento` date DEFAULT NULL,
  
  -- Integração Pagou.com.br
  `pagou_subscription_id` varchar(255) DEFAULT NULL,
  `pagou_customer_id` varchar(255) DEFAULT NULL,
  `ultimo_pagamento_id` varchar(255) DEFAULT NULL,
  `proximo_pagamento` date DEFAULT NULL,
  
  `valor_mensal` decimal(10,2) NOT NULL,
  `forma_pagamento` enum('pix','boleto','cartao') DEFAULT 'pix',
  
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_tenant_subscription` (`tenant_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payment_transactions`;
CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  
  `pagou_transaction_id` varchar(255) UNIQUE,
  `valor` decimal(10,2) NOT NULL,
  `status` enum('pendente','pago','cancelado','expirado') DEFAULT 'pendente',
  `forma_pagamento` enum('pix','boleto','cartao'),
  
  `data_vencimento` date DEFAULT NULL,
  `data_pagamento` timestamp NULL DEFAULT NULL,
  
  -- URLs e códigos de pagamento
  `payment_url` text DEFAULT NULL,
  `pix_code` text DEFAULT NULL,
  `barcode` text DEFAULT NULL,
  
  `webhook_data` json DEFAULT NULL,
  
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `pagou_transaction_id` (`pagou_transaction_id`),
  KEY `idx_subscription` (`subscription_id`),
  KEY `idx_tenant` (`tenant_id`),
  FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SISTEMA DE EMAIL PERSONALIZADO
-- ============================================

DROP TABLE IF EXISTS `tenant_email_verifications`;
CREATE TABLE `tenant_email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `provider_signature_id` varchar(255) DEFAULT NULL COMMENT 'ID retornado pelo Postmark',
  `status` enum('pendente','verificado','expirado') DEFAULT 'pendente',
  `verification_token` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `verificado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VIEWS (Visualizações com tenant_id)
-- ============================================

-- View: vw_estoque_consignado
DROP VIEW IF EXISTS `vw_estoque_consignado`;
CREATE VIEW `vw_estoque_consignado` AS 
SELECT 
    p.tenant_id,
    p.id,
    p.nome AS produto,
    p.estoque_total,
    COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0) AS quantidade_consignada,
    p.estoque_total - COALESCE(SUM(ci.quantidade_consignada - ci.quantidade_vendida - ci.quantidade_devolvida), 0) AS estoque_disponivel
FROM produtos p
LEFT JOIN consignacao_itens ci ON p.id = ci.produto_id
LEFT JOIN consignacoes c ON ci.consignacao_id = c.id AND c.status IN ('pendente', 'parcial')
WHERE p.ativo = 1
GROUP BY p.tenant_id, p.id, p.nome, p.estoque_total;

-- View: vw_estoque_continuo
DROP VIEW IF EXISTS `vw_estoque_continuo`;
CREATE VIEW `vw_estoque_continuo` AS
SELECT 
    c.tenant_id,
    c.id AS consignacao_id,
    c.estabelecimento_id,
    e.nome AS estabelecimento,
    m.produto_id,
    p.nome AS produto,
    SUM(CASE 
        WHEN m.tipo = 'entrega' THEN m.quantidade
        WHEN m.tipo = 'venda' THEN -m.quantidade
        WHEN m.tipo = 'devolucao' THEN -m.quantidade
        ELSE 0 
    END) AS saldo_atual,
    SUM(CASE WHEN m.tipo = 'venda' THEN m.quantidade ELSE 0 END) AS total_vendido,
    SUM(CASE WHEN m.tipo = 'devolucao' THEN m.quantidade ELSE 0 END) AS total_devolvido,
    MAX(m.preco_unitario) AS preco_unitario
FROM movimentacoes_consignacao m
JOIN consignacoes c ON m.consignacao_id = c.id
JOIN estabelecimentos e ON c.estabelecimento_id = e.id
JOIN produtos p ON m.produto_id = p.id
WHERE c.tipo = 'continua' AND c.status <> 'cancelada'
GROUP BY c.tenant_id, c.id, c.estabelecimento_id, e.nome, m.produto_id, p.nome
HAVING saldo_atual > 0;

-- View: vw_relatorio_consignacoes
DROP VIEW IF EXISTS `vw_relatorio_consignacoes`;
CREATE VIEW `vw_relatorio_consignacoes` AS
SELECT 
    c.tenant_id,
    c.id,
    c.data_consignacao,
    c.data_vencimento,
    c.status,
    e.nome AS estabelecimento,
    e.telefone,
    COUNT(ci.id) AS total_itens,
    SUM(ci.quantidade_consignada) AS total_consignado,
    SUM(ci.quantidade_vendida) AS total_vendido,
    SUM(ci.quantidade_devolvida) AS total_devolvido,
    SUM(ci.quantidade_vendida * ci.preco_unitario) AS valor_total_vendido,
    COALESCE(SUM(p.valor_pago), 0) AS valor_pago,
    SUM(ci.quantidade_vendida * ci.preco_unitario) - COALESCE(SUM(p.valor_pago), 0) AS saldo_pendente
FROM consignacoes c
JOIN estabelecimentos e ON c.estabelecimento_id = e.id
LEFT JOIN consignacao_itens ci ON c.id = ci.consignacao_id
LEFT JOIN pagamentos p ON c.id = p.consignacao_id
GROUP BY c.tenant_id, c.id, c.data_consignacao, c.data_vencimento, c.status, e.nome, e.telefone;

-- View: vw_vendas_consolidadas
DROP VIEW IF EXISTS `vw_vendas_consolidadas`;
CREATE VIEW `vw_vendas_consolidadas` AS
SELECT 
    ci.tenant_id,
    ci.consignacao_id,
    c.estabelecimento_id,
    e.nome AS estabelecimento,
    ci.produto_id,
    p.nome AS produto,
    ci.quantidade_vendida AS quantidade,
    ci.preco_unitario,
    ci.quantidade_vendida * ci.preco_unitario AS valor_total,
    c.data_consignacao AS data_venda,
    'pontual' AS origem
FROM consignacao_itens ci
JOIN consignacoes c ON ci.consignacao_id = c.id
JOIN estabelecimentos e ON c.estabelecimento_id = e.id
JOIN produtos p ON ci.produto_id = p.id
WHERE ci.quantidade_vendida > 0

UNION ALL

SELECT 
    m.tenant_id,
    m.consignacao_id,
    c.estabelecimento_id,
    e.nome AS estabelecimento,
    m.produto_id,
    p.nome AS produto,
    m.quantidade,
    m.preco_unitario,
    m.quantidade * m.preco_unitario AS valor_total,
    m.data_movimentacao AS data_venda,
    'continua' AS origem
FROM movimentacoes_consignacao m
JOIN consignacoes c ON m.consignacao_id = c.id
JOIN estabelecimentos e ON c.estabelecimento_id = e.id
JOIN produtos p ON m.produto_id = p.id
WHERE m.tipo = 'venda';

SET FOREIGN_KEY_CHECKS=1;

-- ============================================
-- FIM DA MIGRAÇÃO
-- ============================================
