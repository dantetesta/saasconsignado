-- ============================================
-- Sistema de Notificações Admin → Cliente
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Versão: 2.0.0
-- Data: 2025-10-04
-- ============================================

-- ============================================
-- TABELA: NOTIFICATIONS (Notificações)
-- ============================================

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `tipo` enum('info','success','warning','error','email') DEFAULT 'info',
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `enviado_por_email` tinyint(1) DEFAULT 0,
  `admin_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `lida_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_lida` (`lida`),
  KEY `idx_criado` (`criado_em`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `super_admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notificações do sistema';

-- ============================================
-- ÍNDICES PARA PERFORMANCE
-- ============================================

ALTER TABLE `notifications` ADD INDEX `idx_tenant_lida` (`tenant_id`, `lida`);
ALTER TABLE `notifications` ADD INDEX `idx_tenant_criado` (`tenant_id`, `criado_em`);
