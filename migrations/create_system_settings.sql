-- ============================================
-- TABELA: system_settings
-- Descrição: Configurações gerais do sistema
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Versão: 2.0.0
-- ============================================

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `chave` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Chave da configuração',
  `valor` TEXT NULL COMMENT 'Valor da configuração',
  `tipo` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string' COMMENT 'Tipo do valor',
  `descricao` VARCHAR(255) NULL COMMENT 'Descrição da configuração',
  `grupo` VARCHAR(50) NULL COMMENT 'Grupo da configuração (planos, notificacoes, etc)',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  KEY `idx_chave` (`chave`),
  KEY `idx_grupo` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configurações gerais do sistema';

-- ============================================
-- Inserir configurações padrão
-- ============================================

INSERT INTO `system_settings` (`chave`, `valor`, `tipo`, `descricao`, `grupo`) VALUES
('plano_pro_preco', '20.00', 'number', 'Preço mensal do Plano Pro (R$)', 'planos'),
('plano_pro_dias', '30', 'number', 'Dias de validade do Plano Pro', 'planos'),
('notificacao_dias_5', '1', 'boolean', 'Notificar 5 dias antes de expirar', 'notificacoes'),
('notificacao_dias_3', '1', 'boolean', 'Notificar 3 dias antes de expirar', 'notificacoes'),
('notificacao_dias_1', '1', 'boolean', 'Notificar 1 dia antes de expirar', 'notificacoes'),
('plano_free_estabelecimentos', '5', 'number', 'Limite de estabelecimentos no Plano Free', 'planos'),
('plano_free_consignacoes', '5', 'number', 'Limite de consignações por estabelecimento no Plano Free', 'planos')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);
