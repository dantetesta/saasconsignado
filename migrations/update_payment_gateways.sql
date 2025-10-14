-- ============================================
-- Atualizar tabela payment_gateways
-- Adicionar colunas para configuração
-- Autor: Dante Testa (https://dantetesta.com.br)
-- ============================================

-- Adicionar coluna configuracao (JSON)
ALTER TABLE `payment_gateways` 
ADD COLUMN IF NOT EXISTS `configuracao` TEXT NULL COMMENT 'Configuração JSON (api_key, ambiente, etc)';

-- Adicionar coluna configurado (boolean)
ALTER TABLE `payment_gateways` 
ADD COLUMN IF NOT EXISTS `configurado` TINYINT(1) DEFAULT 0 COMMENT 'Se o gateway foi configurado';

-- Adicionar coluna slug (identificador único)
ALTER TABLE `payment_gateways` 
ADD COLUMN IF NOT EXISTS `slug` VARCHAR(50) NULL COMMENT 'Identificador único do gateway';

-- Atualizar slugs dos gateways existentes
UPDATE `payment_gateways` SET `slug` = 'pagou' WHERE `nome` = 'Pagou.com.br';
UPDATE `payment_gateways` SET `slug` = 'mercadopago' WHERE `nome` = 'Mercado Pago';
UPDATE `payment_gateways` SET `slug` = 'pagseguro' WHERE `nome` = 'PagSeguro';
UPDATE `payment_gateways` SET `slug` = 'stripe' WHERE `nome` = 'Stripe';

-- Adicionar índice no slug
ALTER TABLE `payment_gateways` 
ADD UNIQUE KEY IF NOT EXISTS `idx_slug` (`slug`);
