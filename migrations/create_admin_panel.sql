-- ============================================
-- Painel Administrativo do SaaS
-- Tabelas para gestão de tenants e configurações
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Versão: 2.0.0
-- Data: 2025-10-04
-- ============================================

-- ============================================
-- TABELA: SUPER_ADMINS (Donos do SaaS)
-- ============================================

CREATE TABLE IF NOT EXISTS `super_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Super administradores do SaaS';

-- ============================================
-- TABELA: ADMIN_LOGS (Logs de ações admin)
-- ============================================

CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `dados_anteriores` text DEFAULT NULL,
  `dados_novos` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_acao` (`acao`),
  KEY `idx_data` (`criado_em`),
  FOREIGN KEY (`admin_id`) REFERENCES `super_admins`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de ações administrativas';

-- ============================================
-- TABELA: PAYMENT_GATEWAYS (Gateways de Pagamento)
-- ============================================

CREATE TABLE IF NOT EXISTS `payment_gateways` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL UNIQUE,
  `descricao` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 0,
  `configuracoes` text DEFAULT NULL COMMENT 'JSON com API keys e configs',
  `metodos_disponiveis` text DEFAULT NULL COMMENT 'JSON: pix, boleto, cartao',
  `taxa_percentual` decimal(5,2) DEFAULT 0.00,
  `taxa_fixa` decimal(10,2) DEFAULT 0.00,
  `ordem` int(11) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_ordem` (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gateways de pagamento disponíveis';

-- ============================================
-- INSERIR SUPER ADMIN PADRÃO
-- ============================================
-- Senha padrão: admin123 (ALTERAR APÓS PRIMEIRO LOGIN!)
INSERT INTO `super_admins` (`nome`, `email`, `senha`, `ativo`) VALUES
('Administrador', 'admin@dantetesta.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- ============================================
-- INSERIR GATEWAYS PADRÃO (INATIVOS)
-- ============================================
INSERT INTO `payment_gateways` (`nome`, `slug`, `descricao`, `ativo`, `metodos_disponiveis`, `ordem`) VALUES
('Pagou.com.br', 'pagou', 'Gateway brasileiro com PIX, Boleto e Cartão', 0, '["pix","boleto","cartao"]', 1),
('Stripe', 'stripe', 'Gateway internacional com cartão de crédito', 0, '["cartao"]', 2),
('Mercado Pago', 'mercadopago', 'Gateway com PIX, Boleto e Cartão', 0, '["pix","boleto","cartao"]', 3),
('PagSeguro', 'pagseguro', 'Gateway brasileiro completo', 0, '["pix","boleto","cartao"]', 4),
('Asaas', 'asaas', 'Gateway brasileiro para recorrência', 0, '["pix","boleto","cartao"]', 5);

-- ============================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================

-- Índice composto para busca de tenants por plano e status
ALTER TABLE `tenants` ADD INDEX `idx_plano_status` (`plano`, `status`);

-- Índice para busca de assinaturas ativas
ALTER TABLE `subscriptions` ADD INDEX `idx_status_vencimento` (`status`, `data_vencimento`);

SET FOREIGN_KEY_CHECKS=1;
