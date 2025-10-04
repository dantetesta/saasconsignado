-- Migração: Adicionar campos de imagem e estoque mínimo em produtos
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Data: 2025-10-04

-- Adicionar campo de foto
ALTER TABLE `produtos` 
ADD COLUMN `foto` VARCHAR(255) NULL AFTER `descricao`,
ADD COLUMN `estoque_minimo` INT(11) DEFAULT 10 AFTER `estoque_total`;

-- Atualizar comentário da tabela
ALTER TABLE `produtos` COMMENT = 'Produtos com suporte a imagens e controle de estoque mínimo';

-- Criar índice para busca por produtos ativos
ALTER TABLE `produtos` 
ADD INDEX `idx_ativo_tenant` (`ativo`, `tenant_id`);
