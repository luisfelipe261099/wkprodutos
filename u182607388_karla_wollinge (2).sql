-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 20/06/2025 às 15:15
-- Versão do servidor: 10.11.10-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u182607388_karla_wollinge`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamentos_entrega`
--

CREATE TABLE `agendamentos_entrega` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) DEFAULT NULL,
  `orcamento_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) NOT NULL,
  `data_hora_entrega` datetime NOT NULL,
  `endereco_entrega` varchar(255) NOT NULL,
  `status_entrega` enum('agendado','em_rota','entregue','cancelado') DEFAULT 'agendado',
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `nome_fantasia` varchar(255) DEFAULT NULL,
  `tipo_pessoa` enum('fisica','juridica') NOT NULL,
  `cpf_cnpj` varchar(22) DEFAULT NULL,
  `inscricao_estadual` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `ponto_referencia` varchar(255) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `nome_fantasia`, `tipo_pessoa`, `cpf_cnpj`, `inscricao_estadual`, `email`, `telefone`, `endereco`, `numero`, `ponto_referencia`, `cidade`, `estado`, `cep`, `data_cadastro`) VALUES
(2, 'HIAGO FELIPE BRAZ COSTA', 'Atostylus', 'juridica', '59723352000118', '', 'atostylusumnovotom@gmail.com', '47992352971', 'AV INDUSTRIAL, 906', '', '', 'Itaperocu', 'PR', '83566382', '2025-06-06 20:35:34'),
(3, 'Ellolimp Comércio de Produtos de limpeza e higiene Ltda', 'Ellolimp', 'juridica', '49547438000183', '', 'ellolimp@ellolimp.com.br', '4132822548', 'Pedro Aires da Rocha ,381', '', '', 'São José dos pinhais', 'PR', '', '2025-06-06 20:41:43'),
(5, 'Acttion do Brasil Ltda', 'Acttion', 'juridica', '27670815000134', '90749221-44', 'izidoro@acttion.com.br', '41991196343', '', NULL, NULL, '', '', '81350020', '2025-06-09 17:24:17'),
(6, 'Marlon wiechetek Padilha 04249384993', 'Mw limpeza', 'juridica', '42718399000190', '', 'marlonwiechetek@hotmail.com', '41998110270', 'R Engenheiro Costa Barros ,1943', '', '', 'Curitiba', 'Pr', '82940010', '2025-06-09 17:32:04'),
(7, 'V Cadari', 'Cadari', 'juridica', '47908704000120', '', 'ferragenscadari@gmail.com', '41996510804', 'R coronel Luiz José dos Santos,3401', '', '', 'Curitiba', 'Pr', '81710420', '2025-06-09 17:56:37'),
(13, 'ARTHUR ALVES FERREIRA DE SOUZA PRODUTOS DE LIMPEZA', 'ARTLIMP', 'juridica', '16812043000101', '9060674717', 'arthur@artlimpcwb.com.br', '4133482127', 'Celeste tortato gabardo', '871', '', 'Curitiba', 'Pr', '81900440', '2025-06-10 12:40:04'),
(14, 'BOCCHI ATACADO', '', 'juridica', '', '', '', '', '', '', '', '', '', '', '2025-06-10 12:58:28'),
(15, '52304671 JAIRO WAGNER', 'Mania da limpeza', 'juridica', '52304671000140', '', 'jairowagner21@gmail.com', '41996338344', 'R José Fabiano barcik', '94', '', 'Curitiba', 'Pr', '82940050', '2025-06-10 19:33:00'),
(16, 'Distribuidora de produtos de limpeza ao máximo ltda', 'LIMPEZA AO MÁXIMO', 'juridica', '53192041000193', '', 'gl25representacoes@gmail.com', '11954931510', 'R DA TRINDADE, Cajuru', '130', '', 'Curitiba', 'Pr', '82920120', '2025-06-12 21:13:40'),
(20, 'Jardim', '', 'juridica', '26568323000103', '', '', '41996526810', 'Luiz calegari, não', '98', '', 'São José dos Pinhais', 'Pr', '83040490', '2025-06-17 18:33:56'),
(21, 'Mappi odontologia centro São José', '', 'juridica', '41081960000100', '', '', '41987796138', 'Av Rui barbosa', '9002', '', 'São José dos Pinhais', '', '83005340', '2025-06-20 14:25:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas_representadas`
--

CREATE TABLE `empresas_representadas` (
  `id` int(11) NOT NULL,
  `nome_empresa` varchar(255) NOT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contato_responsavel` varchar(100) DEFAULT NULL,
  `telefone_responsavel` varchar(20) DEFAULT NULL,
  `email_responsavel` varchar(100) DEFAULT NULL,
  `logo_empresa` varchar(255) DEFAULT NULL COMMENT 'Caminho para o arquivo de logo da empresa representada',
  `comissao_padrao` decimal(5,2) DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `data_inicio_representacao` date DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `empresas_representadas`
--

INSERT INTO `empresas_representadas` (`id`, `nome_empresa`, `razao_social`, `cnpj`, `endereco`, `cidade`, `estado`, `cep`, `telefone`, `email`, `contato_responsavel`, `telefone_responsavel`, `email_responsavel`, `logo_empresa`, `comissao_padrao`, `observacoes`, `status`, `data_inicio_representacao`, `data_cadastro`, `data_atualizacao`) VALUES
(1, 'Empresa Exemplo 1', 'Empresa Exemplo 1 Ltda', '12.345.678/0001-90', NULL, 'São Paulo', 'SP', NULL, NULL, NULL, 'João Silva', NULL, NULL, 'uploads/logos_empresas/empresa_1_1749222145.png', 5.00, NULL, 'ativo', '2024-01-01', '2025-05-26 14:06:29', '2025-06-06 15:02:25'),
(2, 'Empresa Exemplo 2', 'Empresa Exemplo 2 S.A.', '98.765.432/0001-10', NULL, 'Rio de Janeiro', 'RJ', NULL, NULL, NULL, 'Maria Santos', NULL, NULL, 'uploads/logos_empresas/empresa_2_1749222151.png', 7.50, NULL, 'ativo', '2024-01-01', '2025-05-26 14:06:29', '2025-06-06 15:02:31'),
(3, 'TROPPEL', 'TROPPEL PRODUTOS DE LIMPEZA LTDA', '28.499.268/0001-39', 'Av. Des. Clotário Portugal, 771', 'Campo Largo', 'PR', '83601-320', '', '', '', '', '', 'uploads/logos_empresas/empresa_3_1749222713.jpg', 5.00, '', 'ativo', '2025-06-06', '2025-06-06 15:05:46', '2025-06-06 15:11:53'),
(5, 'A.F.P', 'AFP Comércio de Embalagens', '19.998.958/0001-41', 'Rua travessa chille,120', 'São José dos Pinhais', 'PR', '83035-200', '(41) 3588-1086', 'A.F.P.EMBALAGENS@HOTMAIL.COM', 'Adrielson', '(41) 99731-4876', '', 'uploads/logos_empresas/empresa_new_1749243286.jpeg', 5.00, 'Empresa de sacos de lixo e embalagens', 'ativo', '2025-06-06', '2025-06-06 20:54:46', '2025-06-06 20:54:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_orcamentos`
--

CREATE TABLE `historico_orcamentos` (
  `id` int(11) NOT NULL,
  `orcamento_id` int(11) NOT NULL,
  `acao` varchar(255) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `data_alteracao` datetime NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `status_anterior` varchar(255) DEFAULT NULL,
  `status_novo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_orcamento`
--

CREATE TABLE `itens_orcamento` (
  `id` int(11) NOT NULL,
  `orcamento_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `itens_orcamento`
--

INSERT INTO `itens_orcamento` (`id`, `orcamento_id`, `produto_id`, `quantidade`, `preco_unitario`) VALUES
(55, 22, 8, 10, 5.10),
(56, 22, 9, 10, 8.10),
(57, 22, 10, 10, 8.95),
(58, 22, 11, 10, 12.40),
(59, 22, 12, 10, 11.85),
(60, 22, 13, 1, 19.39),
(61, 22, 14, 10, 28.80),
(62, 22, 16, 10, 30.19),
(63, 22, 17, 10, 35.57),
(64, 22, 18, 10, 47.30),
(65, 22, 21, 10, 58.70),
(66, 22, 22, 10, 47.30),
(67, 22, 23, 10, 55.10),
(68, 22, 24, 10, 60.35),
(69, 22, 25, 10, 75.80),
(70, 22, 26, 10, 50.80),
(71, 22, 27, 10, 64.40),
(72, 22, 28, 10, 72.50),
(73, 22, 29, 10, 85.50),
(74, 22, 30, 10, 103.49),
(80, 24, 13, 10, 19.60),
(81, 24, 18, 10, 47.30),
(156, 23, 17, 1, 30.38),
(157, 23, 23, 1, 40.50),
(158, 23, 27, 1, 44.55),
(159, 23, 119, 1, 27.90),
(160, 31, 8, 1, 4.42),
(161, 31, 9, 1, 5.90),
(162, 31, 10, 1, 6.90),
(163, 31, 11, 1, 7.90),
(164, 31, 12, 1, 9.50),
(165, 31, 13, 1, 14.00),
(166, 31, 16, 1, 25.90),
(167, 31, 17, 1, 32.00),
(168, 31, 18, 1, 37.90);

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_venda`
--

CREATE TABLE `itens_venda` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `itens_venda`
--

INSERT INTO `itens_venda` (`id`, `venda_id`, `produto_id`, `quantidade`, `preco_unitario`) VALUES
(2, 2, 7, 40, 8.60),
(3, 3, 11, 20, 12.40),
(4, 3, 7, 20, 8.10),
(5, 3, 13, 15, 19.60),
(11, 7, 7, 10, 8.60),
(12, 7, 11, 6, 12.40),
(13, 7, 13, 6, 19.60),
(14, 7, 18, 6, 44.00),
(15, 7, 24, 2, 64.30),
(16, 8, 31, 50, 5.95),
(17, 8, 32, 30, 9.50),
(18, 8, 33, 4, 6.90),
(21, 10, 34, 12, 8.90),
(22, 10, 36, 6, 15.50),
(23, 10, 37, 6, 9.90),
(24, 10, 38, 6, 9.90),
(25, 10, 39, 6, 9.00),
(26, 10, 40, 6, 9.50),
(27, 10, 41, 6, 10.50),
(28, 10, 31, 10, 5.95),
(29, 10, 35, 2, 36.00),
(30, 9, 18, 50, 30.38),
(31, 9, 16, 200, 18.23),
(32, 11, 21, 3, 58.70),
(33, 11, 13, 3, 19.60),
(34, 11, 10, 5, 9.45),
(35, 11, 25, 3, 75.80),
(44, 14, 41, 25, 10.50),
(45, 14, 84, 25, 14.90),
(52, 19, 120, 1, 60.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `lembretes_acompanhamento`
--

CREATE TABLE `lembretes_acompanhamento` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `venda_id` int(11) DEFAULT NULL,
  `data_lembrete` date NOT NULL,
  `tipo_lembrete` varchar(50) NOT NULL COMMENT 'Ex: Recompra, Follow-up, Aniversário, Outro',
  `descricao` text NOT NULL,
  `status_lembrete` varchar(20) NOT NULL DEFAULT 'Pendente' COMMENT 'Ex: Pendente, Concluído, Adiado',
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `lembretes_acompanhamento`
--

INSERT INTO `lembretes_acompanhamento` (`id`, `cliente_id`, `venda_id`, `data_lembrete`, `tipo_lembrete`, `descricao`, `status_lembrete`, `data_criacao`, `data_atualizacao`, `usuario_id`) VALUES
(14, 3, 14, '2025-06-28', 'Follow-up', 'Realizar acompanhamento (follow-up) com o cliente Ellolimp Comércio de Produtos de limpeza e higiene Ltda referente à venda #14.\r\n\r\nNenhum produto nesta venda possui um ciclo de recompra definido. Verifique a necessidade do cliente.', 'Pendente', '2025-06-13 17:00:13', '2025-06-13 17:00:13', 2),
(15, 15, 10, '2025-06-25', 'Follow-up', 'Realizar acompanhamento (follow-up) com o cliente 52304671 JAIRO WAGNER referente à venda #10.\r\n\r\nNenhum produto nesta venda possui um ciclo de recompra definido. Verifique a necessidade do cliente.', 'Pendente', '2025-06-13 17:00:22', '2025-06-13 17:00:22', 2),
(16, 5, 9, '2025-06-24', 'Follow-up', 'Realizar acompanhamento (follow-up) com o cliente Acttion do Brasil Ltda referente à venda #9.\r\n\r\nNenhum produto nesta venda possui um ciclo de recompra definido. Verifique a necessidade do cliente.', 'Pendente', '2025-06-13 17:00:32', '2025-06-13 17:00:32', 2),
(17, 16, 11, '2025-06-27', 'Follow-up', 'Realizar acompanhamento (follow-up) com o cliente Distribuidora de produtos de limpeza ao máximo ltda referente à venda #11.\r\n\r\nNenhum produto nesta venda possui um ciclo de recompra definido. Verifique a necessidade do cliente.', 'Pendente', '2025-06-13 17:00:47', '2025-06-13 17:00:47', 2),
(18, 7, 8, '2025-06-24', 'Follow-up', 'Realizar acompanhamento (follow-up) com o cliente V Cadari referente à venda #8.\r\n\r\nNenhum produto nesta venda possui um ciclo de recompra definido. Verifique a necessidade do cliente.', 'Pendente', '2025-06-13 17:00:55', '2025-06-13 17:00:55', 2),
(19, 6, 7, '2025-06-24', 'Follow-up', 'Realizar acompanhamento (follow-up) com o cliente Marlon wiechetek Padilha 04249384993 referente à venda #7.\r\n\r\nNenhum produto nesta venda possui um ciclo de recompra definido. Verifique a necessidade do cliente.', 'Pendente', '2025-06-13 17:01:04', '2025-06-13 17:01:04', 2),
(20, 2, 3, '2025-06-22', 'Follow-up', 'Realizar acompanhamento (follow-up) com o cliente HIAGO FELIPE BRAZ COSTA referente à venda #3.\r\n\r\nNenhum produto nesta venda possui um ciclo de recompra definido. Verifique a necessidade do cliente.', 'Pendente', '2025-06-13 17:01:11', '2025-06-13 17:01:11', 2),
(21, 3, 2, '2025-06-21', 'Follow-up', 'Realizar acompanhamento (follow-up) com o cliente Ellolimp Comércio de Produtos de limpeza e higiene Ltda referente à venda #2.\r\n\r\nNenhum produto nesta venda possui um ciclo de recompra definido. Verifique a necessidade do cliente.', 'Pendente', '2025-06-13 17:01:17', '2025-06-13 17:01:17', 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `marketplace_carrinho`
--

CREATE TABLE `marketplace_carrinho` (
  `id` int(11) NOT NULL,
  `token_acesso` varchar(64) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `data_adicao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `marketplace_cliente_empresas`
--

CREATE TABLE `marketplace_cliente_empresas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `data_atualizacao` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `marketplace_configuracoes`
--

CREATE TABLE `marketplace_configuracoes` (
  `id` int(11) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_atualizacao` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `marketplace_configuracoes`
--

INSERT INTO `marketplace_configuracoes` (`id`, `chave`, `valor`, `descricao`, `data_atualizacao`) VALUES
(1, 'marketplace_ativo', '1', 'Marketplace ativo (1) ou inativo (0)', '2025-06-05 18:30:07'),
(2, 'titulo_marketplace', 'Karla Wollinger - Marketplace', 'Título do marketplace', '2025-06-05 18:30:07'),
(3, 'descricao_marketplace', 'Faça seus pedidos online de forma rápida e prática', 'Descrição do marketplace', '2025-06-05 18:30:07'),
(4, 'email_notificacoes', 'contato@karlawollinger.com', 'Email para receber notificações de pedidos', '2025-06-05 18:30:07'),
(5, 'prazo_entrega_padrao', '3', 'Prazo padrão de entrega em dias úteis', '2025-06-05 18:30:07'),
(6, 'valor_minimo_pedido', '0.00', 'Valor mínimo para pedidos', '2025-06-05 18:30:07'),
(7, 'permitir_agendamento', '1', 'Permitir agendamento de entrega (1) ou não (0)', '2025-06-05 18:30:07'),
(8, 'horario_funcionamento', '08:00-18:00', 'Horário de funcionamento para entregas', '2025-06-05 18:30:07'),
(9, 'dias_funcionamento', '1,2,3,4,5', 'Dias da semana que funciona (1=segunda, 7=domingo)', '2025-06-05 18:30:07'),
(10, 'permitir_selecao_empresas', '1', 'Permitir que administradores selecionem quais empresas cada cliente pode ver', '2025-06-07 16:00:40'),
(11, 'exibir_todas_empresas_padrao', '1', 'Por padrão, novos clientes podem ver todas as empresas (0 = não, 1 = sim)', '2025-06-07 16:00:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `marketplace_itens_pedido`
--

CREATE TABLE `marketplace_itens_pedido` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `marketplace_itens_pedido`
--

INSERT INTO `marketplace_itens_pedido` (`id`, `pedido_id`, `produto_id`, `quantidade`, `preco_unitario`, `subtotal`) VALUES
(1, 1, 31, 1, 5.95, 5.95);

-- --------------------------------------------------------

--
-- Estrutura para tabela `marketplace_links`
--

CREATE TABLE `marketplace_links` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `token_acesso` varchar(64) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `data_expiracao` datetime DEFAULT NULL,
  `ultimo_acesso` datetime DEFAULT NULL,
  `total_acessos` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `marketplace_links`
--

INSERT INTO `marketplace_links` (`id`, `cliente_id`, `token_acesso`, `ativo`, `data_criacao`, `data_expiracao`, `ultimo_acesso`, `total_acessos`) VALUES
(2, 3, 'b205a34682a604c03c919b413fe58ecb4fa748ecab9a9534bd80f2e5ba004884', 1, '2025-06-06 23:21:42', NULL, '2025-06-10 20:33:49', 20);

-- --------------------------------------------------------

--
-- Estrutura para tabela `marketplace_pedidos`
--

CREATE TABLE `marketplace_pedidos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `token_acesso` varchar(64) NOT NULL,
  `numero_pedido` varchar(20) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `status_pedido` enum('pendente','confirmado','preparando','entregue','cancelado') DEFAULT 'pendente',
  `tipo_faturamento` enum('avista','15_dias','20_dias','30_dias') DEFAULT 'avista',
  `data_vencimento` date DEFAULT NULL,
  `data_entrega_agendada` datetime DEFAULT NULL,
  `endereco_entrega` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_pedido` datetime DEFAULT current_timestamp(),
  `data_confirmacao` datetime DEFAULT NULL,
  `venda_id` int(11) DEFAULT NULL,
  `transacao_financeira_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `marketplace_pedidos`
--

INSERT INTO `marketplace_pedidos` (`id`, `cliente_id`, `token_acesso`, `numero_pedido`, `valor_total`, `status_pedido`, `tipo_faturamento`, `data_vencimento`, `data_entrega_agendada`, `endereco_entrega`, `observacoes`, `data_pedido`, `data_confirmacao`, `venda_id`, `transacao_financeira_id`) VALUES
(1, 3, 'b205a34682a604c03c919b413fe58ecb4fa748ecab9a9534bd80f2e5ba004884', '25060001', 5.95, 'cancelado', '15_dias', '2025-06-25', '2025-06-11 00:00:00', 'Pedro Aires da Rocha ,381, São José dos pinhais - PR', '', '2025-06-10 20:33:06', NULL, NULL, NULL);

--
-- Acionadores `marketplace_pedidos`
--
DELIMITER $$
CREATE TRIGGER `gerar_numero_pedido` BEFORE INSERT ON `marketplace_pedidos` FOR EACH ROW BEGIN
    DECLARE novo_numero VARCHAR(20);
    DECLARE contador INT;
    
    -- Buscar o próximo número sequencial
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_pedido, 4) AS UNSIGNED)), 0) + 1 
    INTO contador 
    FROM marketplace_pedidos 
    WHERE numero_pedido LIKE CONCAT(DATE_FORMAT(NOW(), '%y%m'), '%');
    
    -- Gerar número no formato YYMM0001
    SET novo_numero = CONCAT(DATE_FORMAT(NOW(), '%y%m'), LPAD(contador, 4, '0'));
    SET NEW.numero_pedido = novo_numero;
    
    -- Calcular data de vencimento baseada no tipo de faturamento
    IF NEW.tipo_faturamento = '15_dias' THEN
        SET NEW.data_vencimento = DATE_ADD(CURDATE(), INTERVAL 15 DAY);
    ELSEIF NEW.tipo_faturamento = '20_dias' THEN
        SET NEW.data_vencimento = DATE_ADD(CURDATE(), INTERVAL 20 DAY);
    ELSEIF NEW.tipo_faturamento = '30_dias' THEN
        SET NEW.data_vencimento = DATE_ADD(CURDATE(), INTERVAL 30 DAY);
    ELSE
        SET NEW.data_vencimento = CURDATE();
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `metas_vendas`
--

CREATE TABLE `metas_vendas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `meta_valor` decimal(10,2) NOT NULL,
  `meta_quantidade` int(11) DEFAULT 0,
  `valor_alcancado` decimal(10,2) DEFAULT 0.00,
  `quantidade_alcancada` int(11) DEFAULT 0,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_financeiras`
--

CREATE TABLE `movimentacoes_financeiras` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `categoria` enum('comissao','despesa','investimento','outros') NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_movimentacao` date NOT NULL,
  `venda_id` int(11) DEFAULT NULL,
  `orcamento_id` int(11) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `orcamentos`
--

CREATE TABLE `orcamentos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `data_orcamento` datetime DEFAULT current_timestamp(),
  `data_atualizacao` datetime DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `status_orcamento` enum('pendente','aprovado','rejeitado','convertido_venda') DEFAULT 'pendente',
  `forma_pagamento` enum('pix','debito','credito','dinheiro','faturamento') DEFAULT 'faturamento',
  `tipo_faturamento` enum('avista','15_dias','20_dias','30_dias','45_dias','60_dias','90_dias') DEFAULT 'avista',
  `data_vencimento` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `comissao_percentual` decimal(5,2) DEFAULT 0.00,
  `valor_comissao` decimal(10,2) DEFAULT 0.00,
  `data_criacao` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `orcamentos`
--

INSERT INTO `orcamentos` (`id`, `cliente_id`, `data_orcamento`, `data_atualizacao`, `valor_total`, `status_orcamento`, `forma_pagamento`, `tipo_faturamento`, `data_vencimento`, `observacoes`, `empresa_id`, `comissao_percentual`, `valor_comissao`, `data_criacao`) VALUES
(22, 13, '2025-06-10 12:56:03', NULL, 8641.39, 'pendente', 'faturamento', 'avista', NULL, '', NULL, 0.00, 0.00, '0000-00-00'),
(23, 14, '2025-06-10 13:04:20', NULL, 143.33, 'pendente', 'faturamento', 'avista', NULL, 'Faturo no boleto como preferir ,entrega até 10 dias úteis\r\nTabela prata', NULL, 0.00, 0.00, '0000-00-00'),
(24, 15, '2025-06-10 19:44:33', NULL, 669.00, 'pendente', 'faturamento', '30_dias', NULL, 'Boleto 35,42 dias', NULL, 0.00, 0.00, '0000-00-00'),
(31, 20, '2025-06-17 18:42:29', NULL, 144.42, 'pendente', 'faturamento', 'avista', NULL, 'Faturamento no boleto como ficar melhor\r\nEntrega até 7 dias uteis', NULL, 0.00, 0.00, '0000-00-00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `preco_venda` decimal(10,2) NOT NULL,
  `dias_recompra_sugerido` int(11) DEFAULT NULL,
  `percentual_lucro` decimal(5,2) NOT NULL DEFAULT 0.00,
  `quantidade_estoque` int(11) NOT NULL DEFAULT 0,
  `estoque_minimo` int(11) DEFAULT 5,
  `fornecedor` varchar(100) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  `empresa_id` int(11) DEFAULT NULL,
  `ativo_marketplace` tinyint(1) DEFAULT 1,
  `destaque_marketplace` tinyint(1) DEFAULT 0,
  `ordem_exibicao` int(11) DEFAULT 0,
  `imagem_url` varchar(255) DEFAULT NULL,
  `imagem_produto` varchar(255) DEFAULT NULL,
  `descricao_completa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `sku`, `preco_venda`, `dias_recompra_sugerido`, `percentual_lucro`, `quantidade_estoque`, `estoque_minimo`, `fornecedor`, `data_cadastro`, `empresa_id`, `ativo_marketplace`, `destaque_marketplace`, `ordem_exibicao`, `imagem_url`, `imagem_produto`, `descricao_completa`) VALUES
(7, 'Saco de lixo preto 20lts reforçado c/100un', '0.4 mc', '1001', 8.60, NULL, 5.00, 2147483577, 1, 'AFC', '2025-06-06 23:15:32', 5, 1, 0, 0, NULL, NULL, NULL),
(8, 'Saco de lixo preto 20lts c/100un', '2mc', '1002', 5.10, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-06 23:53:57', 5, 1, 0, 0, NULL, NULL, NULL),
(9, 'Saco de lixo preto 20lts reforçado c/100un', '3mc', '1003', 8.10, NULL, 5.00, 2147483647, 1, 'Afp', '2025-06-06 23:55:26', 5, 1, 0, 0, NULL, NULL, NULL),
(10, 'Saco de lixo preto 40lts c/100un', '2mc', '1004', 9.45, NULL, 5.00, 2147483642, 1, 'AFP', '2025-06-06 23:57:01', 5, 1, 0, 0, NULL, NULL, NULL),
(11, 'Saco de lixo preto 40lts reforçado c/100un', '3mc', '1005', 12.40, NULL, 5.00, 2147483621, 1, 'AFP', '2025-06-06 23:59:03', 5, 1, 0, 0, NULL, NULL, NULL),
(12, 'Saco de lixo preto 60lts c/100un', '2mc', '1006', 12.45, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-07 00:01:17', 5, 1, 0, 0, NULL, NULL, NULL),
(13, 'Saco de lixo preto 60lts reforçado c/100un', '4mc', '1007', 19.60, NULL, 5.00, 2147483623, 1, 'AFP', '2025-06-07 00:02:41', 5, 1, 0, 0, NULL, NULL, NULL),
(14, 'Saco de lixo preto 60lts 10mc c/100un', '6mc', '1008', 28.80, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-07 00:04:00', 5, 1, 0, 0, NULL, NULL, NULL),
(15, 'Saco de lixo preto 100lts c/100un', '3mc', '1009', 22.20, NULL, 5.00, 2147483647, 0, 'AFP', '2025-06-07 14:12:52', 5, 1, 0, 0, NULL, NULL, NULL),
(16, 'Saco de lixo preto 100lts 8mc c/100un', '4mc', '1010', 33.70, NULL, 5.00, 2147483447, 1, 'AFP', '2025-06-07 14:14:26', 5, 1, 0, 0, NULL, NULL, NULL),
(17, 'Saco de lixo preto 100lts 10mc c/100un', '5mc', '1011', 41.90, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-07 14:16:07', 5, 1, 0, 0, NULL, NULL, NULL),
(18, 'Saco de lixo preto 100lts 12mc c/100un', '6mc', '1012', 47.30, NULL, 5.00, 2147483591, 1, 'AFP', '2025-06-07 14:18:03', 5, 1, 0, 0, NULL, NULL, NULL),
(21, 'Saco de lixo preto 100lts 14mc c/100un', '7mc', '1013', 58.70, NULL, 5.00, 2147483644, 1, 'AFP', '2025-06-07 15:26:12', 5, 1, 0, 0, NULL, NULL, NULL),
(22, 'Saco de lixo preto 150lts 8mc c/100un', '4mc', '1014', 47.30, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-07 16:24:41', 5, 1, 0, 0, NULL, NULL, NULL),
(23, 'Saco de lixo preto 150lts 10mc c/100un', '5mc', '1015', 55.10, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-07 16:26:11', 5, 1, 0, 0, NULL, NULL, NULL),
(24, 'Saco de lixo preto 150lts 12mc c/100un', '6mc', '1016', 64.30, NULL, 5.00, 2147483645, 1, 'AFP', '2025-06-07 16:32:21', 5, 1, 0, 0, NULL, NULL, NULL),
(25, 'Saco de lixo preto 150lts 14mc c/100un', '7mc', '1017', 75.80, NULL, 5.00, 2147483644, 1, 'AFP', '2025-06-07 17:02:44', 5, 1, 0, 0, NULL, NULL, NULL),
(26, 'Saco de lixo preto 200lts 8mc c/100un', '4mc', '1018', 50.80, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-07 17:04:14', 5, 1, 0, 0, NULL, NULL, NULL),
(27, 'Saco de lixo preto 200lts 10mc c/100un', '5mc', '1019', 64.40, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-07 17:07:19', 5, 1, 0, 0, NULL, NULL, NULL),
(28, 'Saco de lixo preto 200lts 12mc c/100un', '6mc', '1020', 72.50, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-07 17:13:20', 5, 1, 0, 0, NULL, NULL, NULL),
(29, 'Saco de lixo preto 200lts 14mc c/100un', '7mc', '1021', 85.50, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-07 17:18:07', 5, 1, 0, 0, NULL, NULL, NULL),
(30, 'Saco de lixo preto 300lts 10mc c/100un', '6mc', '1022', 104.50, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-07 17:37:17', 5, 1, 0, 0, NULL, NULL, NULL),
(31, 'Água sanitária 5LTS Troppel', '', '100', 5.95, NULL, 3.50, 2147483587, 1, 'Troppel', '2025-06-09 17:58:00', 3, 1, 0, 0, NULL, 'produto_31_1749602676.jpeg', NULL),
(32, 'Detergente é top 5LTS embalagem branca', '', '102', 9.50, NULL, 5.00, 2147483617, 1, 'Troppel', '2025-06-09 17:59:41', 3, 1, 0, 0, NULL, NULL, NULL),
(33, 'Desinfetante É top cereja com avelã 5LTS Emb branca', '', '103', 6.90, NULL, 5.00, 2147483643, 1, 'Troppel', '2025-06-09 18:09:50', 3, 1, 0, 0, NULL, NULL, NULL),
(34, 'Multiuso limpeza mágica flotador com gatilho 500ml', '', '106', 8.90, NULL, 5.00, 2147483635, 1, 'Troppel', '2025-06-10 19:47:52', 3, 1, 0, 0, NULL, NULL, NULL),
(35, 'Limpeza mágica flotador 5LTS', '', '107', 36.00, NULL, 5.00, 999999997, 1, 'Troppel', '2025-06-10 19:51:15', 3, 1, 0, 0, NULL, NULL, NULL),
(36, 'Desintupidor de pias e ralos banheiro 1lt', '', '108', 15.50, NULL, 5.00, 2147483641, 1, 'Troppel', '2025-06-10 19:56:27', 3, 1, 0, 0, NULL, NULL, NULL),
(37, 'Pós obra 1lt', '', '109', 9.90, NULL, 5.00, 2147483641, 1, 'Troppel', '2025-06-10 19:58:19', 3, 1, 0, 0, NULL, NULL, NULL),
(38, 'Limpa tudo Tróppel 1lt', '', '110', 9.90, NULL, 5.00, 6666660, 9, 'Troppel', '2025-06-10 19:59:47', 3, 1, 0, 0, NULL, NULL, NULL),
(39, 'Limpa porcelanato 850ml', '', '111', 9.00, NULL, 5.00, 999999993, 1, 'Troppel', '2025-06-10 20:01:53', 3, 1, 0, 0, NULL, NULL, NULL),
(40, 'Limpador perfumado com cera lavanda (brilho) 2lts', '', '112', 9.50, NULL, 5.00, 2147483641, 1, 'Troppel', '2025-06-10 20:04:05', 3, 1, 0, 0, NULL, NULL, NULL),
(41, 'Alvejante sem cloro É top 5lts Emb Rosa', '', '1', 10.50, NULL, 5.00, 1000000018, 1, 'Troppel', '2025-06-10 20:06:13', 3, 1, 0, 0, NULL, NULL, NULL),
(42, 'Limpeza mágica flotador 5lts', '', '105', 36.00, NULL, 5.00, 99999999, 1, 'Troppel', '2025-06-10 20:12:32', 3, 1, 0, 0, NULL, NULL, NULL),
(43, 'ÁLCOOL PERFUMADO FLORAL É TOP 5LTS (EMB branca)', '', '101', 17.80, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-10 20:42:10', 3, 1, 0, 0, NULL, NULL, NULL),
(44, 'ÁLCOOL PERFUMADO ALGODÃO É TOP 5LTS (EMB BRANCA)', '', '104', 17.80, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-10 20:44:32', 3, 1, 0, 0, NULL, NULL, NULL),
(48, 'AMACIANTE SUNNY DAY ACONCHEGO É TOP 5L ( DE COR BRANCO)', '', '10', 10.90, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-10 20:55:53', 3, 1, 0, 0, NULL, 'produto_48_1749603345.jpeg', NULL),
(49, 'AMACIANTE SUNNY DAY ACONCHEGO É TOP 5L ( DE COR BRANCO)', '', '11', 6.35, NULL, 5.00, 99999999, 1, 'Troppel', '2025-06-10 20:57:20', 3, 1, 0, 0, NULL, NULL, NULL),
(50, 'AMACIANTE SUNNY DAY AZUL É TOP 5L (DE COR AZUL)', '', '12', 10.90, NULL, 5.00, 99999999, 1, 'Troppel', '2025-06-10 20:58:13', 3, 1, 0, 0, NULL, NULL, NULL),
(51, 'AMACIANTE SUNNY DAY AZUL É TOP 2L (DE COR AZUL)', '', '13', 6.35, NULL, 5.00, 999999999, 1, 'Troppel', '2025-06-10 20:58:58', 3, 1, 0, 0, NULL, NULL, NULL),
(52, 'AMACIANTE SUNNY DAY FLORAL É TOP 5L ( DE COR ROSA)', '', '14', 10.90, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-10 21:00:16', 3, 1, 0, 0, NULL, NULL, NULL),
(53, 'AMACIANTE SUNNY DAY FLORAL É TOP 2L ( DE COR ROSA)', '', '15', 6.35, NULL, 5.00, 9999999, 1, 'Troppel', '2025-06-10 21:00:50', 3, 1, 0, 0, NULL, NULL, NULL),
(54, 'AROMATIZANTES É TOP (SPARK, ZIK, ALGODÃO)', '', '16', 21.90, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-10 21:01:55', 3, 1, 0, 0, NULL, NULL, NULL),
(55, 'DESINFETANTE CEREJA COM AVELÃ É TOP 5L (EMBALAGEM BRANCA)', '', '17', 6.90, NULL, 5.00, 9999999, 1, 'Troppel', '2025-06-10 21:02:37', 3, 1, 0, 0, NULL, NULL, NULL),
(56, 'DESINFETANTE CEREJA COM AVELÃ É TOP 5L (EMBALAGEM TRANSPARENTE)', '', '18', 7.95, NULL, 5.00, 99999, 1, 'Troppel', '2025-06-10 21:03:24', 3, 1, 0, 0, NULL, NULL, NULL),
(57, 'DESINFETANTE CEREJA COM AVELÃ É TOP 2L', '', '19', 4.30, NULL, 5.00, 9999, 1, 'Troppel', '2025-06-10 21:04:29', 3, 1, 0, 0, NULL, NULL, NULL),
(58, 'DESINFETANTE EUCALIPTO É TOP 5L (EMBALAGEM BRANCA)', '', '20', 6.90, NULL, 5.00, 99999999, 1, 'Troppel', '2025-06-10 21:05:21', 3, 1, 0, 0, NULL, NULL, NULL),
(59, 'DESINFETANTE FLORAL É TOP 5L (EMBALAGEM TRANSPARENTE)', '', '21', 7.95, NULL, 5.00, 99999999, 1, 'Troppel', '2025-06-10 21:06:55', 3, 1, 0, 0, NULL, NULL, NULL),
(60, 'DESINFETANTE FLORAL É TOP 5L (EMBALAGEM BRANCA)', '', '22', 6.90, NULL, 5.00, 99999999, 1, 'Troppel', '2025-06-10 21:13:51', 3, 1, 0, 0, NULL, NULL, NULL),
(61, 'DESINFETANTE FLORAL É TOP 2L', '', '23', 4.30, NULL, 5.00, 99999999, 1, 'Troppel', '2025-06-10 21:14:25', 3, 1, 0, 0, NULL, NULL, NULL),
(62, 'DESINFETANTE FLORAL É TOP 2L', '', '24', 4.30, NULL, 5.00, 99999999, 1, 'Troppel', '2025-06-10 21:17:24', 3, 1, 0, 0, NULL, NULL, NULL),
(63, 'DESINFETANTE KAIAK É TOP 5L (EMBALAGEM TRANSPARENTE)', '', '25', 7.35, NULL, 5.00, 999999, 1, 'Troppel', '2025-06-10 21:18:13', 3, 1, 0, 0, NULL, NULL, NULL),
(64, 'DESINFETANTE KAIAK É TOP 2L', '', '26', 4.30, NULL, 5.00, 999999999, 1, 'Troppel', '2025-06-10 21:19:30', 3, 1, 0, 0, NULL, NULL, NULL),
(65, 'DESINFETANTE LAVANDA É TOP 5L (EMBALAGEM TRANSPARENTE)', '', '27', 7.95, NULL, 5.00, 99999999, 1, 'Troppel', '2025-06-10 21:20:24', 3, 1, 0, 0, NULL, NULL, NULL),
(66, 'DESINFETANTE LAVANDA É TOP 5L (EMBALAGEM BRANCA)', '', '28', 6.90, NULL, 5.00, 999999999, 1, 'Troppel', '2025-06-10 21:21:14', 3, 1, 0, 0, NULL, NULL, NULL),
(67, 'DESINFETANTE LAVANDA É TOP 2L', '', '29', 4.30, NULL, 5.00, 999999, 1, 'Troppel', '2025-06-10 21:22:10', 3, 1, 0, 0, NULL, NULL, NULL),
(68, 'DESINFETANTE MARINE É TOP 5L (EMBALAGEM BRANCA)', '', '30', 6.90, NULL, 5.00, 9999, 1, 'Troppel', '2025-06-10 21:22:49', 3, 1, 0, 0, NULL, NULL, NULL),
(69, 'DESINFETANTE MARINE É TOP 5L (EMBALAGEM TRANSPARENTE)', '', '31', 7.95, NULL, 5.00, 9999, 1, 'Troppel', '2025-06-10 21:25:35', 3, 1, 0, 0, NULL, NULL, NULL),
(70, 'DESINFETANTE TALCO É TOP 5L (EMBALAGEM TRANSPARENTE)', '', '32', 7.95, NULL, 5.00, 99999, 1, 'Troppel', '2025-06-10 21:26:16', 3, 1, 0, 0, NULL, NULL, NULL),
(72, 'DESINFETANTE TALCO É TOP 2L', '', '33', 4.30, NULL, 5.00, 9999, 1, 'Troppel', '2025-06-10 21:29:00', 3, 1, 0, 0, NULL, NULL, NULL),
(73, 'DESINFETANTE LIMA C/ CAPIM LIMÃO É TOP 5L (EMBALAGEM TRANSPARENTE)', '', '34', 7.95, NULL, 5.00, 99999, 1, 'Troppel', '2025-06-10 21:29:43', 3, 1, 0, 0, NULL, NULL, NULL),
(74, 'DETERGENTE NEUTRO É TOP 5L (EMB. TRANSPARENTE)', '', '35', 10.90, NULL, 5.00, 99999, 1, 'Troppel', '2025-06-10 21:30:21', 3, 1, 0, 0, NULL, NULL, NULL),
(76, 'DETERGENTE SUNNY DAY ( EMBALAGEM BRANCA) 5lts', '', '36', 8.50, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:14:20', 3, 1, 0, 0, NULL, NULL, NULL),
(77, 'LAVA ROUPAS SANNY DAY 5L (EMBALAGEM BRANCA)', '', '37', 10.90, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:15:21', 3, 1, 0, 0, NULL, NULL, NULL),
(78, 'LAVA ROUPAS É TOP 2L', '', '38', 7.50, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:17:27', 3, 1, 0, 0, NULL, NULL, NULL),
(79, 'LAVA ROUPAS É TOP 5L ( EMBALAGEM TRANSPARENTE)', '', '39', 13.90, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:18:22', 3, 1, 0, 0, NULL, NULL, NULL),
(80, 'LAVA ROUPAS DE COCO É TOP 2L', '', '40', 7.50, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:20:55', 3, 1, 0, 0, NULL, NULL, NULL),
(81, 'LAVA ROUPAS DE COCO É TOP 5L ( EMBALAGEM TRANSPARENTE)', '', '41', 13.90, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:21:50', 3, 1, 0, 0, NULL, NULL, NULL),
(82, 'LAVA ROUPAS ARIEL É TOP 5L', '', '42', 13.50, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:23:25', 3, 1, 0, 0, NULL, NULL, NULL),
(83, 'LIMPA PEDRAS É TOP 5L (EMBALAGEM BRANCA)', '', '43', 26.50, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:24:18', 3, 1, 0, 0, NULL, NULL, NULL),
(84, 'LIMPA PORCELANATOS É TOP 5L (EMBALAGEM BRANCA)', '', '45', 14.90, NULL, 5.00, 2147483647, 0, 'Tróppel', '2025-06-11 00:26:19', 3, 1, 0, 0, NULL, NULL, NULL),
(85, 'LIMPA VIDROS É TOP 5L (EMBALAGEM BRANCA)', '', '46', 12.50, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:30:20', 3, 1, 0, 0, NULL, NULL, NULL),
(86, 'LIMPADOR PARA SANITÁRIOS LAVANDA 5L (EMBALAGEM BRANCA)', '', '47', 6.00, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:31:22', 3, 1, 0, 0, NULL, 'produto_86_1749602879.jpeg', NULL),
(87, 'LIMPADOR PARA SANITÁRIOS FLORAL 5L (EMBALAGEM BRANCA)', '', '48', 6.00, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:32:15', 3, 1, 0, 0, NULL, 'produto_87_1749602938.jpeg', NULL),
(88, 'LIMPADOR PARA SANITÁRIOS MARINE 5L (EMBALAGEM BRANCA)', '', '49', 6.00, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:33:12', 3, 1, 0, 0, NULL, 'produto_88_1749602981.jpeg', NULL),
(89, 'MULTIUSO É TOP 5L (EMBALAGEM BRANCA)', '', '50', 8.50, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:34:11', 3, 1, 0, 0, NULL, 'produto_89_1749603505.jpeg', NULL),
(90, 'MULTIUSO SUNNY DAY 5L (EMBALAGEM BRANCA)', '', '51', 6.90, NULL, 5.00, 99999999, 1, 'Tróppel', '2025-06-11 00:35:18', 3, 1, 0, 0, NULL, NULL, NULL),
(91, 'REMOVEDOR DE CERA É TOP 5L (EMBALAGEM BRANCA)', '', '53', 23.90, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:36:24', 3, 1, 0, 0, NULL, NULL, NULL),
(92, 'INTERCAP É TOP 5lts', '', '54', 17.90, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:37:17', 3, 1, 0, 0, NULL, NULL, NULL),
(93, 'SOLUPAN É TOP 5L', '', '55', 15.90, NULL, 5.00, 2147483647, 1, 'Tróppel', '2025-06-11 00:38:14', 3, 1, 0, 0, NULL, NULL, NULL),
(94, 'ÁGUA SANITARIA TROPPEL 2L', '', '160', 3.99, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:08:03', 3, 1, 0, 0, NULL, NULL, NULL),
(95, 'ALCOOL PERFUMADO DELICATTO TROPPEL 850ML', '', '161', 9.30, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:09:41', 3, 1, 0, 0, NULL, NULL, NULL),
(96, 'ALCOOL PERFUMADO DELICATTO TROPPEL 5L', '', '162', 23.50, NULL, 5.00, 9999999, 1, 'Troppel', '2025-06-13 02:11:16', 3, 1, 0, 0, NULL, NULL, NULL),
(97, 'ALCOOL PERFUMADO FLORAL TROPPEL 850ML', '', '164', 9.30, NULL, 5.00, 999999999, 1, 'Troppel', '2025-06-13 02:12:04', 3, 1, 0, 0, NULL, NULL, NULL),
(98, 'ALCOOL PERFUMADO FLORAL TROPPEL 5L', '', '165', 23.50, NULL, 5.00, 2147483647, 0, 'Troppel', '2025-06-13 02:13:22', 3, 1, 0, 0, NULL, NULL, NULL),
(99, 'ALCOOL PERFUMADO ENCANTO DE ALGODÃO TROPPEL 850ML', '', '166', 9.30, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:14:40', 3, 1, 0, 0, NULL, NULL, NULL),
(100, 'ALCOOL PERFUMADO ENCANTO DE ALGODÃO TROPPEL 5L', '', '167', 23.50, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:15:38', 3, 1, 0, 0, NULL, NULL, NULL),
(101, 'ALCOOL PERFUMADO BRISA DE VERÃO TROPPEL 850ML', '', '168', 9.30, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:16:51', 3, 1, 0, 0, NULL, NULL, NULL),
(102, 'ALCOOL PERFUMADO BRISA DE VERÃO TROPPEL 5L', '', '169', 23.50, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:18:22', 3, 1, 0, 0, NULL, NULL, NULL),
(103, 'ALCOOL ISOPROPILICO 500ML', '', '170', 13.90, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:20:48', 3, 1, 0, 0, NULL, NULL, NULL),
(104, 'ALCOOL DE CEREAIS 850ML', '', '171', 13.30, NULL, 5.00, 999999999, 1, 'Troppel', '2025-06-13 02:21:44', 3, 1, 0, 0, NULL, NULL, NULL),
(105, 'ALVEJANTE SEM CLORO TRÓPPEL 2L', '', '172', 7.50, NULL, 5.00, 2147483647, 0, 'Troppel', '2025-06-13 02:22:49', 3, 1, 0, 0, NULL, NULL, NULL),
(106, 'ALVEJANTE SEM CLORO TRÓPPEL 5L', '', '173', 12.99, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:26:38', 3, 1, 0, 0, NULL, NULL, NULL),
(107, 'PASSA ROUPAS C/ GATILHO FLORAL 500ML', '', '174', 8.50, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:27:27', 3, 1, 0, 0, NULL, NULL, NULL),
(108, 'PASSA ROUPAS C/ GATILHO ROMÃ C/ BAUNILHA 500ML', '', '175', 8.50, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:28:54', 3, 1, 0, 0, NULL, NULL, NULL),
(109, 'AMACIANTE DELICATTO C/ CAPSULAS PERFUMADAS TRÓPPEL 2L', '', '176', 5.99, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:29:40', 3, 1, 0, 0, NULL, NULL, NULL),
(113, 'AMACIANTE DELICATTO C/ CAPSULAS PERFUMADAS TRÓPPEL 5L', '', '177', 11.90, NULL, 5.00, 999999999, 1, 'Troppel', '2025-06-13 02:38:03', 3, 1, 0, 0, NULL, NULL, NULL),
(114, 'AMACIANTE DOMINIO AZUL C/ CAPSULAS PERFUMADAS TRÓPPEL 2L', '', '178', 5.99, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:39:01', 3, 1, 0, 0, NULL, NULL, NULL),
(115, 'AMACIANTE DOMINIO AZUL C/ CAPSULAS PERFUMADAS TRÓPPEL 5L', '', '179', 11.90, NULL, 5.00, 999999999, 1, 'Troppel', '2025-06-13 02:44:06', 3, 1, 0, 0, NULL, NULL, NULL),
(116, 'AMACIANTE SUAVE C/ CAPSULAS PERFUMADAS TRÓPPEL 5L', '', '180', 11.90, NULL, 5.00, 2147483647, 1, 'Troppel', '2025-06-13 02:45:08', 3, 1, 0, 0, NULL, NULL, NULL),
(117, 'TIRA MANCHAS EM PÓ 400g', '', '181', 14.90, NULL, 5.00, 999999999, 1, 'Troppel', '2025-06-13 02:46:34', 3, 1, 0, 0, NULL, NULL, NULL),
(118, 'SACO DE LIXO AZUL 100LTS c/100un', '', '1100', 26.70, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-16 12:52:38', 5, 1, 0, 0, NULL, NULL, NULL),
(119, 'Saco de lixo azul 8mc c/100un', '4mc', '111111111', 39.00, NULL, 5.00, 2147483647, 1, 'AFP', '2025-06-17 17:17:04', 5, 1, 0, 0, NULL, NULL, NULL),
(120, 'Papel toalha extra luxo', '', '000001', 60.00, NULL, 1.00, 11110, 1, 'Seca bem', '2025-06-20 14:27:19', 5, 1, 0, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes_financeiras`
--

CREATE TABLE `transacoes_financeiras` (
  `id` int(11) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_transacao` datetime DEFAULT current_timestamp(),
  `descricao` varchar(255) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `tabela_referencia` varchar(50) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `transacoes_financeiras`
--

INSERT INTO `transacoes_financeiras` (`id`, `tipo`, `valor`, `data_transacao`, `descricao`, `categoria`, `referencia_id`, `tabela_referencia`, `empresa_id`) VALUES
(1, 'entrada', 344.00, '2025-06-06 23:18:26', 'Receita da Venda #2', 'Vendas', 2, 'vendas', NULL),
(2, 'entrada', 704.00, '2025-06-07 14:24:58', 'Receita da Venda #3', 'Vendas', 3, 'vendas', NULL),
(6, 'entrada', 670.60, '2025-06-09 18:41:57', 'Receita da Venda #7', 'Vendas', 7, 'vendas', NULL),
(7, 'entrada', 610.10, '2025-06-09 19:22:50', 'Receita da Venda #8', 'Vendas', 8, 'vendas', NULL),
(9, 'entrada', 624.10, '2025-06-10 20:16:55', 'Receita da Venda #10', 'Vendas', 10, 'vendas', NULL),
(10, 'entrada', 5165.00, '2025-06-10 20:30:31', 'Receita da Venda #9', 'Vendas', 9, 'vendas', NULL),
(11, 'entrada', 509.55, '2025-06-12 21:35:44', 'Receita da Venda #11', 'Vendas', 11, 'vendas', NULL),
(14, 'entrada', 635.00, '2025-06-13 11:49:24', 'Receita da Venda #14', 'Vendas', 14, 'vendas', NULL),
(15, 'entrada', 60.00, '2025-06-20 14:28:04', 'Receita da Venda #19', 'Vendas', 19, 'vendas', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('admin','colaborador') DEFAULT 'colaborador',
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  `ultimo_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel_acesso`, `ativo`, `data_cadastro`, `ultimo_login`) VALUES
(1, 'Karla Wollinger Admin', 'karla@empresa.com', '$2y$10$AzCrGk2hFGDwLvfePLpgFupKh.2D5HKM8SB3P3u6miKJUbaC/hjeW', 'admin', 1, '2025-05-26 12:47:55', NULL),
(2, 'Karla Wollinger ', 'karlawollinger02@gmail.com', '$2y$10$Y/qePhs0Egn.DCkE7WFSp.K26rwLvZR2g6kyeq.u2F8HQJrLXOyiu', 'admin', 1, '2025-05-26 12:47:55', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `data_venda` datetime DEFAULT current_timestamp(),
  `valor_total` decimal(10,2) NOT NULL,
  `forma_pagamento` enum('pix','debito','credito','dinheiro','faturamento','transferencia','boleto') NOT NULL,
  `status_venda` enum('pendente','concluida','cancelada') DEFAULT 'pendente',
  `empresa_id` int(11) DEFAULT NULL,
  `comissao_percentual` decimal(5,2) DEFAULT 0.00,
  `valor_comissao` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `vendas`
--

INSERT INTO `vendas` (`id`, `cliente_id`, `data_venda`, `valor_total`, `forma_pagamento`, `status_venda`, `empresa_id`, `comissao_percentual`, `valor_comissao`) VALUES
(2, 3, '2025-06-06 23:18:26', 344.00, 'boleto', 'concluida', NULL, 0.00, 0.00),
(3, 2, '2025-06-07 14:19:22', 704.00, 'pix', 'concluida', NULL, 0.00, 0.00),
(7, 6, '2025-06-09 18:41:57', 670.60, 'pix', 'concluida', NULL, 0.00, 0.00),
(8, 7, '2025-06-09 19:22:50', 610.10, 'pix', 'concluida', NULL, 0.00, 0.00),
(9, 5, '2025-06-09 20:11:58', 5165.00, 'pix', 'concluida', NULL, 0.00, 0.00),
(10, 15, '2025-06-10 20:16:55', 624.10, 'pix', 'concluida', NULL, 0.00, 0.00),
(11, 16, '2025-06-12 21:35:44', 509.55, 'pix', 'concluida', NULL, 0.00, 0.00),
(14, 3, '2025-06-13 11:49:24', 635.00, 'pix', 'concluida', NULL, 0.00, 0.00),
(19, 21, '2025-06-20 14:28:04', 60.00, 'dinheiro', 'concluida', NULL, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_estoque_por_empresa`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_estoque_por_empresa` (
`nome_empresa` varchar(255)
,`empresa_id` int(11)
,`total_produtos` bigint(21)
,`total_estoque` decimal(32,0)
,`produtos_criticos` decimal(22,0)
,`valor_estoque` decimal(42,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_marketplace_vendas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_marketplace_vendas` (
`id` int(11)
,`numero_pedido` varchar(20)
,`cliente_nome` varchar(255)
,`cliente_email` varchar(100)
,`valor_total` decimal(10,2)
,`status_pedido` enum('pendente','confirmado','preparando','entregue','cancelado')
,`tipo_faturamento` enum('avista','15_dias','20_dias','30_dias')
,`data_pedido` datetime
,`data_entrega_agendada` datetime
,`data_vencimento` date
,`total_itens` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_vendas_por_empresa`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_vendas_por_empresa` (
`nome_empresa` varchar(255)
,`empresa_id` int(11)
,`total_vendas` bigint(21)
,`valor_total_vendas` decimal(32,2)
,`total_comissoes` decimal(32,2)
,`media_comissao` decimal(9,6)
);

-- --------------------------------------------------------

--
-- Estrutura para view `vw_estoque_por_empresa`
--
DROP TABLE IF EXISTS `vw_estoque_por_empresa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u182607388_karla_wollinge`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_estoque_por_empresa`  AS SELECT `e`.`nome_empresa` AS `nome_empresa`, `e`.`id` AS `empresa_id`, count(`p`.`id`) AS `total_produtos`, sum(`p`.`quantidade_estoque`) AS `total_estoque`, sum(case when `p`.`quantidade_estoque` <= `p`.`estoque_minimo` then 1 else 0 end) AS `produtos_criticos`, sum(`p`.`quantidade_estoque` * `p`.`preco_venda`) AS `valor_estoque` FROM (`empresas_representadas` `e` left join `produtos` `p` on(`e`.`id` = `p`.`empresa_id`)) GROUP BY `e`.`id`, `e`.`nome_empresa` ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_marketplace_vendas`
--
DROP TABLE IF EXISTS `vw_marketplace_vendas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u182607388_karla_wollinge`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_marketplace_vendas`  AS SELECT `mp`.`id` AS `id`, `mp`.`numero_pedido` AS `numero_pedido`, `c`.`nome` AS `cliente_nome`, `c`.`email` AS `cliente_email`, `mp`.`valor_total` AS `valor_total`, `mp`.`status_pedido` AS `status_pedido`, `mp`.`tipo_faturamento` AS `tipo_faturamento`, `mp`.`data_pedido` AS `data_pedido`, `mp`.`data_entrega_agendada` AS `data_entrega_agendada`, `mp`.`data_vencimento` AS `data_vencimento`, count(`mip`.`id`) AS `total_itens` FROM ((`marketplace_pedidos` `mp` left join `clientes` `c` on(`mp`.`cliente_id` = `c`.`id`)) left join `marketplace_itens_pedido` `mip` on(`mp`.`id` = `mip`.`pedido_id`)) GROUP BY `mp`.`id` ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_vendas_por_empresa`
--
DROP TABLE IF EXISTS `vw_vendas_por_empresa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u182607388_karla_wollinge`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_vendas_por_empresa`  AS SELECT `e`.`nome_empresa` AS `nome_empresa`, `e`.`id` AS `empresa_id`, count(`v`.`id`) AS `total_vendas`, sum(`v`.`valor_total`) AS `valor_total_vendas`, sum(`v`.`valor_comissao`) AS `total_comissoes`, avg(`v`.`comissao_percentual`) AS `media_comissao` FROM (`empresas_representadas` `e` left join `vendas` `v` on(`e`.`id` = `v`.`empresa_id` and `v`.`status_venda` = 'concluida')) GROUP BY `e`.`id`, `e`.`nome_empresa` ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agendamentos_entrega`
--
ALTER TABLE `agendamentos_entrega`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `orcamento_id` (`orcamento_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`);

--
-- Índices de tabela `empresas_representadas`
--
ALTER TABLE `empresas_representadas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`);

--
-- Índices de tabela `historico_orcamentos`
--
ALTER TABLE `historico_orcamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orcamento_id` (`orcamento_id`),
  ADD KEY `idx_data_alteracao` (`data_alteracao`);

--
-- Índices de tabela `itens_orcamento`
--
ALTER TABLE `itens_orcamento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orcamento_id` (`orcamento_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `lembretes_acompanhamento`
--
ALTER TABLE `lembretes_acompanhamento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `idx_venda_id` (`venda_id`),
  ADD KEY `idx_data_lembrete` (`data_lembrete`),
  ADD KEY `idx_status_lembrete` (`status_lembrete`),
  ADD KEY `idx_usuario_id` (`usuario_id`);

--
-- Índices de tabela `marketplace_carrinho`
--
ALTER TABLE `marketplace_carrinho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token_carrinho` (`token_acesso`),
  ADD KEY `idx_produto_carrinho` (`produto_id`);

--
-- Índices de tabela `marketplace_cliente_empresas`
--
ALTER TABLE `marketplace_cliente_empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cliente_empresa` (`cliente_id`,`empresa_id`),
  ADD KEY `idx_cliente_empresas` (`cliente_id`),
  ADD KEY `idx_empresa_clientes` (`empresa_id`);

--
-- Índices de tabela `marketplace_configuracoes`
--
ALTER TABLE `marketplace_configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`),
  ADD KEY `idx_chave` (`chave`);

--
-- Índices de tabela `marketplace_itens_pedido`
--
ALTER TABLE `marketplace_itens_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedido_item` (`pedido_id`),
  ADD KEY `idx_produto_item` (`produto_id`);

--
-- Índices de tabela `marketplace_links`
--
ALTER TABLE `marketplace_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_acesso` (`token_acesso`),
  ADD KEY `idx_token` (`token_acesso`),
  ADD KEY `idx_cliente` (`cliente_id`);

--
-- Índices de tabela `marketplace_pedidos`
--
ALTER TABLE `marketplace_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_pedido` (`numero_pedido`),
  ADD KEY `idx_cliente_pedido` (`cliente_id`),
  ADD KEY `idx_token_pedido` (`token_acesso`),
  ADD KEY `idx_numero_pedido` (`numero_pedido`),
  ADD KEY `idx_status` (`status_pedido`),
  ADD KEY `idx_venda` (`venda_id`),
  ADD KEY `idx_transacao` (`transacao_financeira_id`);

--
-- Índices de tabela `metas_vendas`
--
ALTER TABLE `metas_vendas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_meta_empresa_mes_ano` (`empresa_id`,`mes`,`ano`);

--
-- Índices de tabela `movimentacoes_financeiras`
--
ALTER TABLE `movimentacoes_financeiras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `orcamento_id` (`orcamento_id`),
  ADD KEY `idx_movimentacoes_empresa` (`empresa_id`),
  ADD KEY `idx_movimentacoes_data` (`data_movimentacao`);

--
-- Índices de tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `idx_orcamentos_empresa` (`empresa_id`),
  ADD KEY `idx_orcamentos_forma_pagamento` (`forma_pagamento`),
  ADD KEY `idx_orcamentos_tipo_faturamento` (`tipo_faturamento`),
  ADD KEY `idx_orcamentos_data_vencimento` (`data_vencimento`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_produtos_empresa` (`empresa_id`),
  ADD KEY `idx_ativo_marketplace` (`ativo_marketplace`),
  ADD KEY `idx_destaque` (`destaque_marketplace`),
  ADD KEY `idx_empresa_ativo` (`empresa_id`,`ativo_marketplace`),
  ADD KEY `idx_produtos_imagem` (`imagem_produto`);

--
-- Índices de tabela `transacoes_financeiras`
--
ALTER TABLE `transacoes_financeiras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transacoes_empresa` (`empresa_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `vendas`
--
ALTER TABLE `vendas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `idx_vendas_empresa` (`empresa_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendamentos_entrega`
--
ALTER TABLE `agendamentos_entrega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `empresas_representadas`
--
ALTER TABLE `empresas_representadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `historico_orcamentos`
--
ALTER TABLE `historico_orcamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `itens_orcamento`
--
ALTER TABLE `itens_orcamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de tabela `lembretes_acompanhamento`
--
ALTER TABLE `lembretes_acompanhamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `marketplace_carrinho`
--
ALTER TABLE `marketplace_carrinho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `marketplace_cliente_empresas`
--
ALTER TABLE `marketplace_cliente_empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `marketplace_configuracoes`
--
ALTER TABLE `marketplace_configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `marketplace_itens_pedido`
--
ALTER TABLE `marketplace_itens_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `marketplace_links`
--
ALTER TABLE `marketplace_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `marketplace_pedidos`
--
ALTER TABLE `marketplace_pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `metas_vendas`
--
ALTER TABLE `metas_vendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `movimentacoes_financeiras`
--
ALTER TABLE `movimentacoes_financeiras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT de tabela `transacoes_financeiras`
--
ALTER TABLE `transacoes_financeiras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agendamentos_entrega`
--
ALTER TABLE `agendamentos_entrega`
  ADD CONSTRAINT `agendamentos_entrega_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`),
  ADD CONSTRAINT `agendamentos_entrega_ibfk_2` FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos` (`id`),
  ADD CONSTRAINT `agendamentos_entrega_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Restrições para tabelas `historico_orcamentos`
--
ALTER TABLE `historico_orcamentos`
  ADD CONSTRAINT `historico_orcamentos_ibfk_1` FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `itens_orcamento`
--
ALTER TABLE `itens_orcamento`
  ADD CONSTRAINT `itens_orcamento_ibfk_1` FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos` (`id`),
  ADD CONSTRAINT `itens_orcamento_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD CONSTRAINT `itens_venda_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`),
  ADD CONSTRAINT `itens_venda_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `lembretes_acompanhamento`
--
ALTER TABLE `lembretes_acompanhamento`
  ADD CONSTRAINT `fk_lembrete_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lembrete_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lembrete_venda` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `marketplace_carrinho`
--
ALTER TABLE `marketplace_carrinho`
  ADD CONSTRAINT `marketplace_carrinho_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `marketplace_cliente_empresas`
--
ALTER TABLE `marketplace_cliente_empresas`
  ADD CONSTRAINT `marketplace_cliente_empresas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_cliente_empresas_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_representadas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `marketplace_itens_pedido`
--
ALTER TABLE `marketplace_itens_pedido`
  ADD CONSTRAINT `marketplace_itens_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `marketplace_pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_itens_pedido_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `marketplace_links`
--
ALTER TABLE `marketplace_links`
  ADD CONSTRAINT `marketplace_links_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `marketplace_pedidos`
--
ALTER TABLE `marketplace_pedidos`
  ADD CONSTRAINT `marketplace_pedidos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_pedidos_ibfk_2` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `marketplace_pedidos_ibfk_3` FOREIGN KEY (`transacao_financeira_id`) REFERENCES `transacoes_financeiras` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `metas_vendas`
--
ALTER TABLE `metas_vendas`
  ADD CONSTRAINT `metas_vendas_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_representadas` (`id`);

--
-- Restrições para tabelas `movimentacoes_financeiras`
--
ALTER TABLE `movimentacoes_financeiras`
  ADD CONSTRAINT `movimentacoes_financeiras_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_representadas` (`id`),
  ADD CONSTRAINT `movimentacoes_financeiras_ibfk_2` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`),
  ADD CONSTRAINT `movimentacoes_financeiras_ibfk_3` FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos` (`id`);

--
-- Restrições para tabelas `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD CONSTRAINT `fk_orcamentos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_representadas` (`id`),
  ADD CONSTRAINT `orcamentos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produtos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_representadas` (`id`);

--
-- Restrições para tabelas `transacoes_financeiras`
--
ALTER TABLE `transacoes_financeiras`
  ADD CONSTRAINT `fk_transacoes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_representadas` (`id`);

--
-- Restrições para tabelas `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `fk_vendas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_representadas` (`id`),
  ADD CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
