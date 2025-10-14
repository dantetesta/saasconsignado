-- ============================================
-- Adicionar campos para reembolsos
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Versão: 2.1.0
-- ============================================

-- Adicionar novos status e campos de reembolso
ALTER TABLE `subscription_payments` 
MODIFY COLUMN `status` ENUM('pending', 'paid', 'expired', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
ADD COLUMN `refund_reason` TEXT NULL COMMENT 'Motivo do reembolso',
ADD COLUMN `refund_id` VARCHAR(255) NULL COMMENT 'ID do reembolso na API',
ADD COLUMN `refunded_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data do reembolso',
ADD COLUMN `refunded_amount` DECIMAL(10,2) NULL COMMENT 'Valor reembolsado',
ADD COLUMN `manual_refund` BOOLEAN DEFAULT FALSE COMMENT 'Reembolso manual (não automático)',
ADD KEY `idx_refund_status` (`status`, `refunded_at`),
ADD KEY `idx_refund_id` (`refund_id`);
