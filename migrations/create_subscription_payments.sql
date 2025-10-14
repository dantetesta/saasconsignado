-- ============================================
-- TABELA: subscription_payments
-- Descrição: Pagamentos de assinatura (recorrência mensal)
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Versão: 2.0.0
-- ============================================

CREATE TABLE IF NOT EXISTS `subscription_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `charge_id` VARCHAR(255) NOT NULL COMMENT 'UUID do PIX na API Pagou',
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 20.00,
  `status` ENUM('pending', 'paid', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data do pagamento REAL',
  `expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data de expiração do plano',
  `qrcode_data` TEXT NULL COMMENT 'Código PIX copia e cola',
  `qrcode_image` LONGTEXT NULL COMMENT 'QR Code em base64',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_charge` (`charge_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expires` (`expires_at`),
  
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pagamentos de assinatura mensal';

-- ============================================
-- Adicionar campos no tenants para controle de assinatura
-- ============================================

ALTER TABLE `tenants` 
ADD COLUMN IF NOT EXISTS `subscription_expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data de expiração do plano Pro',
ADD COLUMN IF NOT EXISTS `subscription_status` ENUM('active', 'expiring_soon', 'expired', 'cancelled') DEFAULT 'expired' COMMENT 'Status da assinatura',
ADD COLUMN IF NOT EXISTS `last_payment_id` INT NULL COMMENT 'ID do último pagamento',
ADD KEY `idx_subscription_expires` (`subscription_expires_at`),
ADD KEY `idx_subscription_status` (`subscription_status`);
