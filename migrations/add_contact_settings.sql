-- ============================================
-- Configurações de Contato do Administrador
-- Para página de conta bloqueada
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Versão: 2.1.1
-- ============================================

-- Inserir configurações de contato se não existirem
INSERT INTO `system_settings` (`chave`, `valor`, `tipo`, `descricao`, `grupo`) VALUES
('admin_email', 'admin@sisteminha.com.br', 'email', 'E-mail do administrador para contato', 'contato'),
('admin_whatsapp', '5511999999999', 'text', 'WhatsApp do administrador (formato: 5511999999999)', 'contato'),
('admin_phone', '(11) 99999-9999', 'text', 'Telefone do administrador formatado', 'contato'),
('company_name', 'SaaS Sisteminha', 'text', 'Nome da empresa/sistema', 'contato')
ON DUPLICATE KEY UPDATE 
    `descricao` = VALUES(`descricao`),
    `grupo` = VALUES(`grupo`),
    `tipo` = VALUES(`tipo`);
