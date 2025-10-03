-- ============================================
-- Sistema de Controle de Consignados
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Versão: 1.2.5
-- Data: 2025-10-03 03:26:27
-- ============================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Tabela: consignacao_itens
DROP TABLE IF EXISTS `consignacao_itens`;
CREATE TABLE `consignacao_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consignacao_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade_consignada` int(11) NOT NULL,
  `quantidade_vendida` int(11) DEFAULT 0,
  `quantidade_devolvida` int(11) DEFAULT 0,
  `preco_unitario` decimal(10,2) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_consignacao` (`consignacao_id`),
  KEY `idx_produto` (`produto_id`),
  CONSTRAINT `consignacao_itens_ibfk_1` FOREIGN KEY (`consignacao_id`) REFERENCES `consignacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consignacao_itens_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: consignacoes
DROP TABLE IF EXISTS `consignacoes`;
CREATE TABLE `consignacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estabelecimento_id` int(11) NOT NULL,
  `data_consignacao` date NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `status` enum('pendente','parcial','finalizada','cancelada') DEFAULT 'pendente',
  `status_manual` tinyint(1) DEFAULT 0,
  `tipo` enum('pontual','continua') DEFAULT 'pontual',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_estabelecimento` (`estabelecimento_id`),
  KEY `idx_data_consignacao` (`data_consignacao`),
  KEY `idx_status` (`status`),
  CONSTRAINT `consignacoes_ibfk_1` FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `consignacoes`
INSERT INTO `consignacoes` (`id`, `estabelecimento_id`, `data_consignacao`, `data_vencimento`, `status`, `status_manual`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`) VALUES ('14', '1', '2025-10-02', '2025-10-29', 'finalizada', '0', 'continua', '', '2025-10-02 23:56:56', '2025-10-02 23:57:55');

-- Tabela: estabelecimentos
DROP TABLE IF EXISTS `estabelecimentos`;
CREATE TABLE `estabelecimentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `senha_acesso` varchar(255) DEFAULT NULL,
  `token_acesso` varchar(64) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_acesso` (`token_acesso`),
  KEY `idx_nome` (`nome`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `estabelecimentos`
INSERT INTO `estabelecimentos` (`id`, `nome`, `responsavel`, `email`, `telefone`, `whatsapp`, `senha_acesso`, `token_acesso`, `endereco`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('1', 'Café Varanda', 'Marcela ou Anny', 'dante.testa@gmail.com', '(19) 99802-1956', '+5519998021956', '$2y$12$9oBX4tHNJJtnSwo7gCGJV.kE.cpeQaQ9/E9FPBm.v/sQxZwsT/Z46', '0c03d541bcbef7deffc2f095bf0499e1822463b3b7d5f87632eab2f7c677cf8f', 'Travessa Ermelinda 60', '', '1', '2025-10-02 18:11:57', '2025-10-03 00:07:59');
INSERT INTO `estabelecimentos` (`id`, `nome`, `responsavel`, `email`, `telefone`, `whatsapp`, `senha_acesso`, `token_acesso`, `endereco`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('2', 'TS Natação', 'Henrique e Milena', 'dantedanieltesta@gmail.com', '19 98779-2304', '+5519987792304', '$2y$12$VlP9/Z4Hz3P2mScRR2BMuebdRJdyEddA1pP5l1AiVlOJmGpTrQzVu', 'ce98ea9f2e79a0c878ce80a3beed502c49b19e3ae4c4c5aa94de517d0e791613', 'Rua Regente Feijó', '', '1', '2025-10-02 18:12:42', '2025-10-03 00:08:11');
INSERT INTO `estabelecimentos` (`id`, `nome`, `responsavel`, `email`, `telefone`, `whatsapp`, `senha_acesso`, `token_acesso`, `endereco`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('3', 'teste', '', NULL, '', NULL, NULL, 'fed5067a304a4b2d032185c3f625178f2c2d158ac3dbee075913ec2cdb02865c', '', '', '0', '2025-10-02 18:12:49', '2025-10-02 19:02:19');
INSERT INTO `estabelecimentos` (`id`, `nome`, `responsavel`, `email`, `telefone`, `whatsapp`, `senha_acesso`, `token_acesso`, `endereco`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('4', 'TESTE', 'Marcela ou Anny', '', '(19) 99802-1956', '', NULL, '443787c99324224facfe7be90f94f77e295138a92ede3327be81fb0fa350c4d2', 'Rua eduardo tozzi', '', '0', '2025-10-02 19:28:27', '2025-10-02 19:29:48');

-- Tabela: movimentacoes_consignacao
DROP TABLE IF EXISTS `movimentacoes_consignacao`;
CREATE TABLE `movimentacoes_consignacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consignacao_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `tipo` enum('entrega','venda','devolucao') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `data_movimentacao` date NOT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_consignacao` (`consignacao_id`),
  KEY `idx_produto` (`produto_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_data` (`data_movimentacao`),
  CONSTRAINT `movimentacoes_consignacao_ibfk_1` FOREIGN KEY (`consignacao_id`) REFERENCES `consignacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimentacoes_consignacao_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `movimentacoes_consignacao`
INSERT INTO `movimentacoes_consignacao` (`id`, `consignacao_id`, `produto_id`, `tipo`, `quantidade`, `preco_unitario`, `data_movimentacao`, `observacoes`, `criado_em`) VALUES ('34', '14', '1', 'entrega', '10', '15.00', '2025-10-02', 'Entrega inicial', '2025-10-02 23:56:56');
INSERT INTO `movimentacoes_consignacao` (`id`, `consignacao_id`, `produto_id`, `tipo`, `quantidade`, `preco_unitario`, `data_movimentacao`, `observacoes`, `criado_em`) VALUES ('35', '14', '1', 'venda', '5', '15.00', '2025-10-02', '', '2025-10-02 23:57:16');
INSERT INTO `movimentacoes_consignacao` (`id`, `consignacao_id`, `produto_id`, `tipo`, `quantidade`, `preco_unitario`, `data_movimentacao`, `observacoes`, `criado_em`) VALUES ('36', '14', '1', 'devolucao', '5', '15.00', '2025-10-02', '', '2025-10-02 23:57:28');

-- Tabela: pagamentos
DROP TABLE IF EXISTS `pagamentos`;
CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consignacao_id` int(11) NOT NULL,
  `data_pagamento` date NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `forma_pagamento` enum('dinheiro','pix','cartao_debito','cartao_credito','transferencia','outro') DEFAULT 'dinheiro',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_consignacao` (`consignacao_id`),
  KEY `idx_data_pagamento` (`data_pagamento`),
  CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`consignacao_id`) REFERENCES `consignacoes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: produtos
DROP TABLE IF EXISTS `produtos`;
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco_venda` decimal(10,2) NOT NULL,
  `preco_custo` decimal(10,2) DEFAULT 0.00,
  `estoque_total` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_nome` (`nome`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `produtos`
INSERT INTO `produtos` (`id`, `nome`, `descricao`, `preco_venda`, `preco_custo`, `estoque_total`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('1', 'Pipoca Gourmet 100g', 'Pipoca artesanal sabores diversos', '15.00', '8.00', '95', '1', '2025-10-02 18:03:34', '2025-10-02 23:57:28');
INSERT INTO `produtos` (`id`, `nome`, `descricao`, `preco_venda`, `preco_custo`, `estoque_total`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('2', 'Pipoca Gourmet - Chocolate', 'Pipoca artesanal sabor chocolate', '8.00', '4.50', '100', '0', '2025-10-02 18:03:34', '2025-10-02 22:12:33');
INSERT INTO `produtos` (`id`, `nome`, `descricao`, `preco_venda`, `preco_custo`, `estoque_total`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('3', 'Pipoca Gourmet - Morango', 'Pipoca artesanal sabor morango', '15.00', '8.00', '30', '0', '2025-10-02 18:03:34', '2025-10-02 22:12:29');
INSERT INTO `produtos` (`id`, `nome`, `descricao`, `preco_venda`, `preco_custo`, `estoque_total`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('4', 'Pipoca Gourmet - Manteiga', 'Pipoca artesanal sabor manteiga', '7.00', '4.00', '100', '0', '2025-10-02 18:03:34', '2025-10-02 18:15:01');
INSERT INTO `produtos` (`id`, `nome`, `descricao`, `preco_venda`, `preco_custo`, `estoque_total`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('5', 'Pipoca Gourmet - Doce de Leite', 'Pipoca artesanal sabor doce de leite', '9.00', '5.00', '100', '0', '2025-10-02 18:03:34', '2025-10-02 22:12:35');
INSERT INTO `produtos` (`id`, `nome`, `descricao`, `preco_venda`, `preco_custo`, `estoque_total`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('6', 'Pipoca Gourmet - Nutella', 'sabor nutella', '15.00', '8.00', '10', '0', '2025-10-02 18:15:24', '2025-10-02 22:12:27');
INSERT INTO `produtos` (`id`, `nome`, `descricao`, `preco_venda`, `preco_custo`, `estoque_total`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('7', 'teste', '', '55.00', '5.00', '3', '1', '2025-10-03 00:02:20', '2025-10-03 00:02:20');

-- Tabela: usuarios
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nome_empresa` varchar(255) DEFAULT NULL,
  `documento` varchar(18) DEFAULT NULL,
  `email_remetente` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- VIEWS (Visualizações)
-- ============================================

-- View: vw_estoque_consignado
DROP VIEW IF EXISTS `vw_estoque_consignado`;
CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `vw_estoque_consignado` AS select `p`.`id` AS `id`,`p`.`nome` AS `produto`,`p`.`estoque_total` AS `estoque_total`,coalesce(sum(`ci`.`quantidade_consignada` - `ci`.`quantidade_vendida` - `ci`.`quantidade_devolvida`),0) AS `quantidade_consignada`,`p`.`estoque_total` - coalesce(sum(`ci`.`quantidade_consignada` - `ci`.`quantidade_vendida` - `ci`.`quantidade_devolvida`),0) AS `estoque_disponivel` from ((`produtos` `p` left join `consignacao_itens` `ci` on(`p`.`id` = `ci`.`produto_id`)) left join `consignacoes` `c` on(`ci`.`consignacao_id` = `c`.`id` and `c`.`status` in ('pendente','parcial'))) where `p`.`ativo` = 1 group by `p`.`id`,`p`.`nome`,`p`.`estoque_total`;

-- View: vw_estoque_continuo
DROP VIEW IF EXISTS `vw_estoque_continuo`;
CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `vw_estoque_continuo` AS select `c`.`id` AS `consignacao_id`,`c`.`estabelecimento_id` AS `estabelecimento_id`,`e`.`nome` AS `estabelecimento`,`m`.`produto_id` AS `produto_id`,`p`.`nome` AS `produto`,sum(case when `m`.`tipo` = 'entrega' then `m`.`quantidade` when `m`.`tipo` = 'venda' then -`m`.`quantidade` when `m`.`tipo` = 'devolucao' then -`m`.`quantidade` else 0 end) AS `saldo_atual`,sum(case when `m`.`tipo` = 'venda' then `m`.`quantidade` else 0 end) AS `total_vendido`,sum(case when `m`.`tipo` = 'devolucao' then `m`.`quantidade` else 0 end) AS `total_devolvido`,max(`m`.`preco_unitario`) AS `preco_unitario` from (((`movimentacoes_consignacao` `m` join `consignacoes` `c` on(`m`.`consignacao_id` = `c`.`id`)) join `estabelecimentos` `e` on(`c`.`estabelecimento_id` = `e`.`id`)) join `produtos` `p` on(`m`.`produto_id` = `p`.`id`)) where `c`.`tipo` = 'continua' and `c`.`status` <> 'cancelada' group by `c`.`id`,`c`.`estabelecimento_id`,`e`.`nome`,`m`.`produto_id`,`p`.`nome` having `saldo_atual` > 0;

-- View: vw_relatorio_consignacoes
DROP VIEW IF EXISTS `vw_relatorio_consignacoes`;
CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `vw_relatorio_consignacoes` AS select `c`.`id` AS `id`,`c`.`data_consignacao` AS `data_consignacao`,`c`.`data_vencimento` AS `data_vencimento`,`c`.`status` AS `status`,`e`.`nome` AS `estabelecimento`,`e`.`telefone` AS `telefone`,count(`ci`.`id`) AS `total_itens`,sum(`ci`.`quantidade_consignada`) AS `total_consignado`,sum(`ci`.`quantidade_vendida`) AS `total_vendido`,sum(`ci`.`quantidade_devolvida`) AS `total_devolvido`,sum(`ci`.`quantidade_vendida` * `ci`.`preco_unitario`) AS `valor_total_vendido`,coalesce(sum(`p`.`valor_pago`),0) AS `valor_pago`,sum(`ci`.`quantidade_vendida` * `ci`.`preco_unitario`) - coalesce(sum(`p`.`valor_pago`),0) AS `saldo_pendente` from (((`consignacoes` `c` join `estabelecimentos` `e` on(`c`.`estabelecimento_id` = `e`.`id`)) left join `consignacao_itens` `ci` on(`c`.`id` = `ci`.`consignacao_id`)) left join `pagamentos` `p` on(`c`.`id` = `p`.`consignacao_id`)) group by `c`.`id`,`c`.`data_consignacao`,`c`.`data_vencimento`,`c`.`status`,`e`.`nome`,`e`.`telefone`;

-- View: vw_vendas_consolidadas
DROP VIEW IF EXISTS `vw_vendas_consolidadas`;
CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `vw_vendas_consolidadas` AS select `ci`.`consignacao_id` AS `consignacao_id`,`c`.`estabelecimento_id` AS `estabelecimento_id`,`e`.`nome` AS `estabelecimento`,`ci`.`produto_id` AS `produto_id`,`p`.`nome` AS `produto`,`ci`.`quantidade_vendida` AS `quantidade`,`ci`.`preco_unitario` AS `preco_unitario`,`ci`.`quantidade_vendida` * `ci`.`preco_unitario` AS `valor_total`,`c`.`data_consignacao` AS `data_venda`,'pontual' AS `origem` from (((`consignacao_itens` `ci` join `consignacoes` `c` on(`ci`.`consignacao_id` = `c`.`id`)) join `estabelecimentos` `e` on(`c`.`estabelecimento_id` = `e`.`id`)) join `produtos` `p` on(`ci`.`produto_id` = `p`.`id`)) where `ci`.`quantidade_vendida` > 0 union all select `m`.`consignacao_id` AS `consignacao_id`,`c`.`estabelecimento_id` AS `estabelecimento_id`,`e`.`nome` AS `estabelecimento`,`m`.`produto_id` AS `produto_id`,`p`.`nome` AS `produto`,`m`.`quantidade` AS `quantidade`,`m`.`preco_unitario` AS `preco_unitario`,`m`.`quantidade` * `m`.`preco_unitario` AS `valor_total`,`m`.`data_movimentacao` AS `data_venda`,'continua' AS `origem` from (((`movimentacoes_consignacao` `m` join `consignacoes` `c` on(`m`.`consignacao_id` = `c`.`id`)) join `estabelecimentos` `e` on(`c`.`estabelecimento_id` = `e`.`id`)) join `produtos` `p` on(`m`.`produto_id` = `p`.`id`)) where `m`.`tipo` = 'venda';

SET FOREIGN_KEY_CHECKS=1;
