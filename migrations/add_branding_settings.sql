-- ============================================
-- MIGRATION: Adicionar Configurações de Branding
-- Descrição: Nome do Software e Logotipo do Sistema
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Data: 15/01/2025 00:56
-- ============================================

-- Adicionar novas configurações de branding
INSERT INTO `system_settings` (`chave`, `valor`, `tipo`, `descricao`, `grupo`) VALUES
('sistema_nome', 'SaaS Sisteminha', 'string', 'Nome do Software', 'sistema'),
('sistema_logotipo', '', 'string', 'URL do Logotipo do Sistema', 'sistema')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- ============================================
-- Criar diretório para uploads (via PHP)
-- ============================================
-- O diretório /uploads/branding/ será criado automaticamente
