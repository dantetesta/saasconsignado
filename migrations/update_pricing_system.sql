-- ============================================
-- Atualização do Sistema de Preços
-- Garante que as configurações de preços estejam no banco
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Versão: 2.1.0
-- ============================================

-- Inserir/Atualizar configurações de preços se não existirem
INSERT INTO `system_settings` (`chave`, `valor`, `tipo`, `descricao`, `grupo`) VALUES
('plano_pro_preco', '20.00', 'number', 'Preço mensal do Plano Pro (R$)', 'planos'),
('plano_pro_dias', '30', 'number', 'Dias de validade do Plano Pro', 'planos'),
('plano_free_estabelecimentos', '5', 'number', 'Limite de estabelecimentos no Plano Free', 'planos'),
('plano_free_consignacoes', '5', 'number', 'Limite de consignações por estabelecimento no Plano Free', 'planos')
ON DUPLICATE KEY UPDATE 
    `descricao` = VALUES(`descricao`),
    `grupo` = VALUES(`grupo`),
    `tipo` = VALUES(`tipo`);

-- Verificar se a coluna updated_at existe na tabela system_settings
ALTER TABLE `system_settings` 
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Garantir que a tabela admin_logs existe com as colunas corretas
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
  KEY `idx_data` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de ações administrativas';

-- Adicionar índice para melhor performance nas consultas de preços
ALTER TABLE `system_settings` 
ADD INDEX IF NOT EXISTS `idx_grupo_chave` (`grupo`, `chave`);

-- Inserir log de instalação do sistema de preços
INSERT INTO `admin_logs` (`admin_id`, `acao`, `descricao`, `dados_novos`, `ip_address`, `criado_em`) 
SELECT 1, 'system_update', 'Instalação do sistema de gerenciamento de preços', 
       JSON_OBJECT('version', '2.1.0', 'feature', 'pricing_management'), 
       'system', NOW()
WHERE EXISTS (SELECT 1 FROM `super_admins` WHERE `id` = 1);
