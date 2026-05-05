-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 05/05/2026 às 12:10
-- Versão do servidor: 10.6.15-MariaDB
-- Versão do PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `enxosbqc_pdv_1`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `caixas`
--

CREATE TABLE `caixas` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `fundo_caixa` decimal(10,2) DEFAULT 0.00,
  `total_vendas` decimal(10,2) DEFAULT 0.00,
  `total_entradas` decimal(10,2) DEFAULT 0.00,
  `total_saidas` decimal(10,2) DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'fechado',
  `abertura` datetime DEFAULT current_timestamp(),
  `fechamento` datetime DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `caixas`
--

INSERT INTO `caixas` (`id`, `tenant_id`, `user_id`, `fundo_caixa`, `total_vendas`, `total_entradas`, `total_saidas`, `status`, `abertura`, `fechamento`, `observacoes`) VALUES
(1, 2, 2, 0.00, 10.00, 0.00, 0.00, 'aberto', '2026-05-04 13:14:39', NULL, NULL),
(2, 1, 1, 0.00, 10.00, 0.00, 0.00, 'aberto', '2026-05-05 01:07:19', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `caixa_movimentacoes`
--

CREATE TABLE `caixa_movimentacoes` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `caixa_id` int(11) DEFAULT NULL,
  `tipo` varchar(10) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `descricao` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(191) NOT NULL,
  `descricao` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `tenant_id`, `nome`, `descricao`, `created_at`) VALUES
(1, 1, 'Alimentos', NULL, '2026-05-04 12:08:54'),
(2, 1, 'Bebidas', NULL, '2026-05-04 12:08:54'),
(3, 1, 'Higiene', NULL, '2026-05-04 12:08:54'),
(4, 1, 'Limpeza', NULL, '2026-05-04 12:08:54'),
(5, 2, 'Alimentos', NULL, '2026-05-04 12:25:28'),
(6, 2, 'Bebidas', NULL, '2026-05-04 12:25:28'),
(7, 2, 'Higiene', NULL, '2026-05-04 12:25:28'),
(8, 2, 'Limpeza', NULL, '2026-05-04 12:25:28'),
(9, 3, 'Alimentos', NULL, '2026-05-05 11:20:19'),
(10, 3, 'Bebidas', NULL, '2026-05-05 11:20:19'),
(11, 3, 'Higiene', NULL, '2026-05-05 11:20:19'),
(12, 3, 'Limpeza', NULL, '2026-05-05 11:20:19');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `chave` varchar(80) NOT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `tenant_id`, `chave`, `valor`) VALUES
(1, 1, 'nome_empresa', 'Empresa Demo'),
(2, 1, 'cnpj', ''),
(3, 1, 'telefone', ''),
(4, 1, 'endereco', ''),
(5, 1, 'logo_url', ''),
(6, 1, 'pix_chave', ''),
(7, 1, 'pix_nome', ''),
(8, 1, 'pix_cidade', ''),
(9, 1, 'moeda_simbolo', 'R$'),
(10, 1, 'taxa_servico', '0'),
(11, 1, 'estoque_minimo_alerta', '5'),
(12, 1, 'impressora_largura', '80'),
(13, 1, 'tema', 'dark'),
(15, 2, 'nome_empresa', 'Zeider'),
(16, 2, 'cnpj', ''),
(17, 2, 'telefone', ''),
(18, 2, 'endereco', ''),
(19, 2, 'logo_url', ''),
(20, 2, 'pix_chave', ''),
(21, 2, 'pix_nome', ''),
(22, 2, 'pix_cidade', ''),
(23, 2, 'moeda_simbolo', 'R$'),
(24, 2, 'taxa_servico', '0'),
(25, 2, 'estoque_minimo_alerta', '5'),
(26, 2, 'impressora_largura', '80'),
(27, 2, 'tema', 'light'),
(34, 3, 'nome_empresa', 'Mercearia Gabriel'),
(35, 3, 'cnpj', ''),
(36, 3, 'telefone', ''),
(37, 3, 'endereco', ''),
(38, 3, 'logo_url', ''),
(39, 3, 'pix_chave', ''),
(40, 3, 'pix_nome', ''),
(41, 3, 'pix_cidade', ''),
(42, 3, 'moeda_simbolo', 'R$'),
(43, 3, 'taxa_servico', '0'),
(44, 3, 'estoque_minimo_alerta', '5'),
(45, 3, 'impressora_largura', '80'),
(46, 3, 'tema', 'light');

-- --------------------------------------------------------

--
-- Estrutura para tabela `config_plano`
--

CREATE TABLE `config_plano` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `rank_nome` varchar(30) DEFAULT 'Basic',
  `saldo_pago` tinyint(4) DEFAULT 1,
  `data_vencimento` varchar(20) DEFAULT '2099-12-31',
  `limite_produtos` int(11) DEFAULT 100,
  `limite_operadores` int(11) DEFAULT 10,
  `limite_gerentes` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `config_plano`
--

INSERT INTO `config_plano` (`id`, `tenant_id`, `rank_nome`, `saldo_pago`, `data_vencimento`, `limite_produtos`, `limite_operadores`, `limite_gerentes`) VALUES
(1, 1, 'SUPER', 1, '2099-12-31', -1, -1, -1),
(2, 2, 'PRO', 1, '2027-01-01', -1, 10, 1),
(3, 3, 'BASIC', 1, '2026-08-04', 200, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_log`
--

CREATE TABLE `email_log` (
  `id` int(11) NOT NULL,
  `para_email` text NOT NULL,
  `para_nome` text DEFAULT NULL,
  `assunto` text NOT NULL,
  `corpo` mediumtext DEFAULT NULL,
  `status` varchar(20) DEFAULT 'enviado',
  `erro` text DEFAULT NULL,
  `enviado_em` text DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `email_log`
--

INSERT INTO `email_log` (`id`, `para_email`, `para_nome`, `assunto`, `corpo`, `status`, `erro`, `enviado_em`) VALUES
(4, 'gaelgzus@gmail.com', 'SuperAdmin', '🔔 PDVo — Novo pagamento aguardando confirmação', '<!DOCTYPE html>\n<html lang=\"pt-BR\">\n<head><meta charset=\"UTF-8\"><title>Novo Pagamento</title></head>\n<body style=\"margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;\">\n  <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f3f4f6;padding:32px 16px;\">\n    <tr><td align=\"center\">\n      <table width=\"560\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);\">\n        <tr>\n          <td style=\"background:linear-gradient(135deg,#1e40af,#3b82f6);padding:28px 32px;\">\n            <h1 style=\"margin:0;color:#fff;font-size:24px;font-weight:900;\">PDVo</h1>\n            <p style=\"margin:4px 0 0;color:rgba(255,255,255,.7);font-size:11px;letter-spacing:.2em;text-transform:uppercase;\">SuperAdmin — Alerta de Pagamento</p>\n          </td>\n        </tr>\n        <tr>\n          <td style=\"padding:28px 32px;\">\n            <h2 style=\"margin:0 0 6px;color:#111827;font-size:18px;\">🔔 Pagamento PIX confirmado pelo cliente</h2>\n            <p style=\"margin:0 0 20px;color:#6b7280;font-size:13px;\">Um cliente confirmou o pagamento e está aguardando ativação da conta.</p>\n\n            <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:22px;\">\n              <tr><td style=\"padding:18px 22px;\">\n                <table width=\"100%\" cellpadding=\"4\">\n                  <tr><td style=\"color:#6b7280;font-size:12px;width:38%\">Empresa</td><td style=\"color:#111827;font-size:13px;font-weight:700;\">Mercearia Gabriel</td></tr>\n                  <tr><td style=\"color:#6b7280;font-size:12px;\">Responsável</td><td style=\"color:#111827;font-size:13px;\">Gabriel Sabino</td></tr>\n                  <tr><td style=\"color:#6b7280;font-size:12px;\">E-mail</td><td style=\"color:#111827;font-size:13px;\">sabino@adm.com</td></tr>\n                  <tr><td style=\"color:#6b7280;font-size:12px;\">WhatsApp</td><td style=\"color:#111827;font-size:13px;\">11 99918-6361</td></tr>\n                  <tr><td style=\"color:#6b7280;font-size:12px;\">Plano</td><td style=\"color:#2563eb;font-size:13px;font-weight:700;\">BASIC</td></tr>\n                  <tr><td style=\"color:#6b7280;font-size:12px;\">Valor</td><td style=\"color:#16a34a;font-size:14px;font-weight:800;\">R$ 10,00</td></tr>\n                  <tr><td style=\"color:#6b7280;font-size:12px;\">Referência</td><td style=\"color:#111827;font-size:12px;font-family:monospace;\">PDV69F9FC79CF422</td></tr>\n                </table>\n              </td></tr>\n            </table>\n\n            <p style=\"color:#374151;font-size:13px;margin:0 0 6px;\">Acesse o painel SuperAdmin para verificar o comprovante e ativar o acesso do cliente.</p>\n            <p style=\"color:#9ca3af;font-size:11px;margin:0;\">PDVo Sistema — Notificação automática</p>\n          </td>\n        </tr>\n      </table>\n    </td></tr>\n  </table>\n</body>\n</html>', 'enviado', NULL, '2026-05-05 11:19:47'),
(5, 'sabino@adm.com', 'Gabriel Sabino', 'PDVo — Sua conta Mercearia Gabriel foi ativada!', '<!DOCTYPE html>\n<html lang=\"pt-BR\">\n<head><meta charset=\"UTF-8\"><title>Conta PDVo Ativada</title></head>\n<body style=\"margin:0;padding:0;background:#f3f4f6;font-family:\'Inter\',Arial,sans-serif;\">\n  <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f3f4f6;padding:40px 20px;\">\n    <tr><td align=\"center\">\n      <table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);\">\n        <tr>\n          <td style=\"background:linear-gradient(135deg,#1e40af,#3b82f6);padding:36px 40px;text-align:center;\">\n            <h1 style=\"margin:0;color:#fff;font-size:32px;font-weight:900;letter-spacing:-1px;\">PDVo</h1>\n            <p style=\"margin:6px 0 0;color:rgba(255,255,255,.7);font-size:12px;letter-spacing:.2em;text-transform:uppercase;\">Sistema de Ponto de Venda</p>\n          </td>\n        </tr>\n        <tr>\n          <td style=\"padding:40px 40px 32px;\">\n            <h2 style=\"margin:0 0 8px;color:#111827;font-size:22px;font-weight:800;\">Sua conta foi ativada!</h2>\n            <p style=\"margin:0 0 24px;color:#6b7280;font-size:14px;\">Olá, <strong style=\"color:#111827;\">Gabriel Sabino</strong>! Seu pagamento foi confirmado.</p>\n            <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:24px;\">\n              <tr><td style=\"padding:20px 24px;\">\n                <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">\n                  <tr>\n                    <td style=\"padding:6px 0;font-size:13px;color:#6b7280;width:40%\">Empresa</td>\n                    <td style=\"padding:6px 0;font-size:13px;color:#111827;font-weight:700;\">Mercearia Gabriel</td>\n                  </tr>\n                  <tr>\n                    <td style=\"padding:6px 0;font-size:13px;color:#6b7280;\">Plano</td>\n                    <td style=\"padding:6px 0;font-size:13px;color:#2563eb;font-weight:700;\">BASIC</td>\n                  </tr>\n                  <tr>\n                    <td style=\"padding:6px 0;font-size:13px;color:#6b7280;\">Código da Empresa</td>\n                    <td style=\"padding:6px 0;font-size:13px;color:#111827;font-family:monospace;font-weight:700;\">mercearia_gabriel</td>\n                  </tr>\n                  <tr>\n                    <td style=\"padding:6px 0;font-size:13px;color:#6b7280;\">Senha Inicial</td>\n                    <td style=\"padding:6px 0;font-size:13px;color:#111827;font-family:monospace;\">sabino@adm.com</td>\n                  </tr>\n                </table>\n              </td></tr>\n            </table>\n            <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#fef3c7;border:1px solid #fde68a;border-radius:12px;margin-bottom:28px;\">\n              <tr><td style=\"padding:16px 20px;font-size:13px;color:#92400e;\">\n                <strong>Importante:</strong> Altere sua senha imediatamente após o primeiro login em <strong>Configurações → Usuários</strong>.\n              </td></tr>\n            </table>\n            <p style=\"margin:0;color:#6b7280;font-size:13px;\">\n              Acesse o sistema usando o <strong>Código da Empresa</strong> acima.<br>\n              <strong style=\"color:#111827;\">Equipe PDVo</strong>\n            </p>\n          </td>\n        </tr>\n        <tr>\n          <td style=\"background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 40px;text-align:center;\">\n            <p style=\"margin:0;color:#9ca3af;font-size:11px;\">&copy; 2026 PDVo · Sistema de PDV Online · Todos os direitos reservados</p>\n          </td>\n        </tr>\n      </table>\n    </td></tr>\n  </table>\n</body>\n</html>', 'enviado', NULL, '2026-05-05 11:20:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque_movimentacoes`
--

CREATE TABLE `estoque_movimentacoes` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `product_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `motivo` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `indicacoes`
--

CREATE TABLE `indicacoes` (
  `id` int(11) NOT NULL,
  `indicador_slug` varchar(60) NOT NULL,
  `indicador_email` varchar(191) DEFAULT NULL,
  `indicador_razao` text DEFAULT NULL,
  `indicado_email` varchar(191) NOT NULL,
  `indicado_razao` text DEFAULT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `pedido_ref` varchar(60) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `validated_at` text DEFAULT NULL,
  `created_at` text DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `indicacoes`
--

INSERT INTO `indicacoes` (`id`, `indicador_slug`, `indicador_email`, `indicador_razao`, `indicado_email`, `indicado_razao`, `pedido_id`, `pedido_ref`, `status`, `observacoes`, `validated_at`, `created_at`) VALUES
(1, 'demo', 'admin@pdvo.local', 'Empresa Demo', 'zeider@adm.com', 'Zeider', 1, 'PDV69F8BA012776C', 'validado', '', '2026-05-04 12:26:22', '2026-05-04 12:23:45'),
(2, 'demo', 'admin@pdvo.local', 'Empresa Demo', 'sabino@adm.com', 'Mercearia Gabriel', 2, 'PDV69F9FC79CF422', 'validado', '', '2026-05-05 11:26:33', '2026-05-05 11:19:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos_ativacao`
--

CREATE TABLE `pedidos_ativacao` (
  `id` int(11) NOT NULL,
  `tenant_slug` varchar(60) DEFAULT NULL,
  `razao_social` text NOT NULL,
  `nome` text NOT NULL,
  `email` varchar(191) NOT NULL,
  `whatsapp` varchar(30) NOT NULL,
  `plano_id` int(11) NOT NULL,
  `plano_nome` varchar(30) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status` varchar(30) DEFAULT 'pendente',
  `payload_pix` text DEFAULT NULL,
  `ref` varchar(60) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` text DEFAULT current_timestamp(),
  `updated_at` text DEFAULT current_timestamp(),
  `codigo_indicacao` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pedidos_ativacao`
--

INSERT INTO `pedidos_ativacao` (`id`, `tenant_slug`, `razao_social`, `nome`, `email`, `whatsapp`, `plano_id`, `plano_nome`, `valor`, `status`, `payload_pix`, `ref`, `observacoes`, `created_at`, `updated_at`, `codigo_indicacao`) VALUES
(1, 'zeider', 'Zeider', 'Felipe Zeider', 'zeider@adm.com', '11 99587-1339', 2, 'PRO', 25.00, 'confirmado', '00020126450014BR.GOV.BCB.PIX0123naoconfigurado@pdvo.app520400005303986540525.005802BR5912PDVo Sistema6009SAO PAULO62070503***6304802B', 'PDV69F8BA012776C', NULL, '2026-05-04 12:23:45', '2026-05-04 12:25:28', 'demo'),
(2, 'sabino', 'Mercearia Gabriel', 'Gabriel Sabino', 'sabino@adm.com', '11 99918-6361', 1, 'BASIC', 10.00, 'confirmado', '00020126470014BR.GOV.BCB.PIX0125idbritoteixeira@gmail.com520400005303986540510.005802BR5910Gael Jesus6008Caieiras62070503***6304C344', 'PDV69F9FC79CF422', NULL, '2026-05-05 11:19:37', '2026-05-05 11:20:19', 'demo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos_sistema`
--

CREATE TABLE `planos_sistema` (
  `id` int(11) NOT NULL,
  `nome` varchar(30) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `limite_produtos` int(11) DEFAULT 100,
  `limite_operadores` int(11) DEFAULT 1,
  `limite_gerentes` int(11) DEFAULT 0,
  `descricao` text DEFAULT NULL,
  `destaque` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `planos_sistema`
--

INSERT INTO `planos_sistema` (`id`, `nome`, `valor`, `limite_produtos`, `limite_operadores`, `limite_gerentes`, `descricao`, `destaque`) VALUES
(1, 'BASIC', 10.00, 200, 1, 0, 'Ideal para pequenos negócios', 0),
(2, 'PRO', 25.00, -1, 10, 1, 'Para negócios em crescimento', 1),
(3, 'SUPER', 109.99, -1, -1, -1, 'Ilimitado para grandes redes', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `codigo_barras` varchar(60) DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `custo` decimal(10,2) DEFAULT 0.00,
  `estoque` decimal(10,3) NOT NULL DEFAULT 0.000,
  `estoque_minimo` decimal(10,3) DEFAULT 5.000,
  `unidade` varchar(10) DEFAULT 'un',
  `categoria_id` int(11) DEFAULT NULL,
  `ativo` tinyint(4) NOT NULL DEFAULT 1,
  `imagem_url` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `products`
--

INSERT INTO `products` (`id`, `tenant_id`, `nome`, `descricao`, `codigo_barras`, `preco`, `custo`, `estoque`, `estoque_minimo`, `unidade`, `categoria_id`, `ativo`, `imagem_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'Produto Exemplo', NULL, '7891234567890', 10.00, 6.00, 49.000, 5.000, 'un', NULL, 1, NULL, '2026-05-04 12:08:54', '2026-05-05 01:07:34');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `caixa_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `numero_venda` varchar(30) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `taxa_servico` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `forma_pagamento` varchar(30) DEFAULT 'dinheiro',
  `valor_pago` decimal(10,2) DEFAULT 0.00,
  `troco` decimal(10,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'concluida',
  `observacoes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `sales`
--

INSERT INTO `sales` (`id`, `tenant_id`, `caixa_id`, `user_id`, `numero_venda`, `subtotal`, `desconto`, `taxa_servico`, `total`, `forma_pagamento`, `valor_pago`, `troco`, `status`, `observacoes`, `created_at`) VALUES
(1, 2, 1, 2, 'V202605048359', 10.00, 0.00, 0.00, 10.00, 'dinheiro', 10.00, 0.00, 'concluida', NULL, '2026-05-04 13:14:56'),
(2, 1, 2, 1, 'V202605058965', 10.00, 0.00, 0.00, 10.00, 'dinheiro', 10.00, 0.00, 'concluida', NULL, '2026-05-05 01:07:34');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `sale_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `desconto_item` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `sale_items`
--

INSERT INTO `sale_items` (`id`, `tenant_id`, `sale_id`, `product_id`, `quantidade`, `preco_unitario`, `desconto_item`, `subtotal`) VALUES
(1, 2, 1, 2, 1.000, 10.00, 0.00, 10.00),
(2, 1, 2, 1, 1.000, 10.00, 0.00, 10.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `subcategorias`
--

CREATE TABLE `subcategorias` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(191) NOT NULL,
  `descricao` text DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `superadmin_config`
--

CREATE TABLE `superadmin_config` (
  `chave` varchar(80) NOT NULL,
  `valor` text DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `superadmin_config`
--

INSERT INTO `superadmin_config` (`chave`, `valor`) VALUES
('app_aviso_ativo', '0'),
('app_aviso_mensagem', 'Tenha o sistema na palma da mão. Disponível para Android e iOS.'),
('app_aviso_titulo', 'Baixe nosso App!'),
('app_aviso_url_android', ''),
('app_aviso_url_ios', ''),
('email_notificacao', 'gaelgzus@gmail.com'),
('email_remetente', 'no-reply@enxos.online'),
('indicacao_ativo', '1'),
('indicacao_recompensa_desc', ''),
('indicacao_valor_recompensa', '5'),
('pix_chave', 'idbritoteixeira@gmail.com'),
('pix_cidade', 'Caieiras'),
('pix_nome', 'GABRIEL DE BRITO TEIXEIRA');

-- --------------------------------------------------------

--
-- Estrutura para tabela `superadmin_users`
--

CREATE TABLE `superadmin_users` (
  `id` int(11) NOT NULL,
  `nome` text NOT NULL,
  `email` varchar(191) NOT NULL,
  `senha` text NOT NULL,
  `created_at` text DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `superadmin_users`
--

INSERT INTO `superadmin_users` (`id`, `nome`, `email`, `senha`, `created_at`) VALUES
(1, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Z0adqai1YSMbEgOKCC7Zl.dfsHZKDpnFshUi1ZWfF63Stp6.T/qhy', '2026-05-04 12:08:54'),
(680, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$P/trQQ1AmTcD8Y.oIIufKeBG02eCjgjXh6Clok8BCdBSy1s1tJzU6', '2026-05-05 00:45:45'),
(681, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$a6/bhfNQ7OkM8fC2qktiXuumlmJNXzudh6AG5V4Jvu3Ixrgf8rKf6', '2026-05-05 00:45:45'),
(682, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$V.IcBI04oQt./LfP5JWN1eh7mEMV8MiKNaI3gVoSWvPjd21lmvOLG', '2026-05-05 00:45:45'),
(683, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$PsLNuQz/IIInRD3mc0B1E.IFPEjD/LJ5EsrQARAloEWASr6BNNcqS', '2026-05-05 00:45:45'),
(684, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$vMKNV22l4U1Y3Ho4BaWPe.ZEkeIeFItENY12UekEJTU5Cwjv1Fi..', '2026-05-05 00:45:46'),
(685, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$WdXQFPucUsl5LTD5v0p9v.37H0mZAIGbDHYjb7epU.StEez11YeCW', '2026-05-05 00:45:46'),
(686, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$4nz07wdgSk/8Z2VfjQwxvewzZpOrZ9dsHg.hFOUHctYwK.RKkiaQG', '2026-05-05 00:45:50'),
(687, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$SN195yVdhj9.icVOpudXgeQIPTCFBMmvR9XQtqngdjnDIKFpyvxZu', '2026-05-05 00:45:53'),
(688, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$5pWA8RENfkkNLHQm5Bzj1eDPyrD6.vUl55m5pgMQVsVafJiZzOR56', '2026-05-05 00:45:55'),
(689, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Aqkmu7XJe7IGHV2JLGykg.neFxAgbvlLMffSv2FZFCSxSEyGm6J9m', '2026-05-05 00:45:56'),
(690, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$lVrep.HYv2oGnTHuq9JKMuhuv1r1mqy7qUz5JvBqb207BMeAiWKIK', '2026-05-05 00:46:32'),
(691, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$dC7wc.LG/WpXTaTWsf1SFONmlaOBBLGxu5Z/OoU.rY60CzOiNswDq', '2026-05-05 00:46:32'),
(692, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$KRKHZkzXIVEHkVVpkthrIuSKJZmzjsRncIcHo/lsXQ8actM8Sy546', '2026-05-05 00:46:32'),
(693, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$teP6XdlWVcHOqQlFCUbZIeixeZ6BuEDlKfZKH8Cmqq.WY9FagphNC', '2026-05-05 00:46:32'),
(694, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$eEij4grN2XfeseHQHj2qUeiEiDc40.G3tFJMKx8p2rbXYhfyNkWUC', '2026-05-05 00:46:32'),
(695, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$UsqHwiLwtb7U2kFbJQawpOnhUQkY.zcTi4nt4OdXnMFkN..9tQ9q.', '2026-05-05 00:46:32'),
(696, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$llBn8UttIFikWqJBEZrlouQuV0n6ER0IeD2MdvB.Xd864TrfK21pC', '2026-05-05 00:46:32'),
(697, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$scclJcztuzRQTedQlroL5uogyP9X.N8shuWYxncFqdsr5tzyOrb5q', '2026-05-05 00:46:37'),
(698, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$emFIVF.HrCJvsm6rLG1F8uEhAdolEMYZSeTjI2w3Y/iUAicuhZgZK', '2026-05-05 00:46:42'),
(699, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$pediVaJSxeTyOp0jI3.Cre0ReRBuQKwmOufWFYzU5u3oMxvf0g7vq', '2026-05-05 00:46:47'),
(700, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$YtvCwKgWf61zdmsL4HOb5.ZpjprB.2FSpUAiWPXmjs/5QhakuMK0C', '2026-05-05 00:46:52'),
(701, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$n442hVhFkuSHQ3sN6oOOLuDz4IR7PzmGWI./uAA74AGA/c20pKT36', '2026-05-05 00:46:57'),
(702, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$BnfQUgxupidDzDZeVXXiIOFmVRwu206EtISTBtVE6iR/O5.JuDOTa', '2026-05-05 00:47:59'),
(703, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$zni8F8hLDauu.xIe1iotxug3ktdLjoSmFD9dsUOWnHyB8jLurqAge', '2026-05-05 00:47:59'),
(704, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$HMRKfh8V0./mCK/y9q/8RuIQrIRTMzg0zv9XfaC4F0L3BwEMdtWge', '2026-05-05 00:47:59'),
(705, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$.TlBZuAQsTt4cD6CMcaoL.NUhbEG5kYCHaAg4WxvB/E0vbhr7lUda', '2026-05-05 00:47:59'),
(706, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$E.A6N8N5GF77.VZ0dnlig.kCcndEJuFDjc/n4GT47Fji.vdfIBpi2', '2026-05-05 00:47:59'),
(707, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$ErtzA3BKRvN6k7VFgKd5CeImMSaKSk9Zi1aaMQF4De6OSN3L7u9WC', '2026-05-05 00:47:59'),
(708, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$GF2ZNF6z/LZNzslizif5ve3mTqZrdhL37w4gxMc5Dhfifskfg1uIC', '2026-05-05 00:47:59'),
(709, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$3TSzNogdxz86SjJd5xFIvusUWgf.0wsE64SQ4dy9H.AzZz1moxSUy', '2026-05-05 00:48:00'),
(710, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$/JuVGvMpWcXJec.xANykI.K54Qs8CQYDHLPWIK883yWDG.3iJKOyO', '2026-05-05 00:48:05'),
(711, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$dwb2zKVHqp29VQwfZgqkYuBEzf.mVLaf8J05d4XTk1cQuvs/kCjLS', '2026-05-05 00:48:11'),
(712, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$bJvGBYybSt3h0JUkFIzF2uoJxjPvWJ89n1B8PQaG4PSHN/3QydZZe', '2026-05-05 00:48:16'),
(713, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$P8x62vs6aTjivoxKUoWyI.As7FYP2MZ39QhLa0gczTL494EzQ8zLG', '2026-05-05 00:48:24'),
(714, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$15iuEgqT2HjTwYbEIQbQhOK00py5P8fuyayZ6uJv3aXPyZ.c1E0RK', '2026-05-05 00:48:24'),
(715, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$trxbJNDkL.J/otOO60A0D.TeS/SSJAbv4R9zxlkumxJZ8wW83TNAe', '2026-05-05 00:48:24'),
(716, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$yYNog6LRvy2umNA4Ib.QVO1h3H6VkSBFeEd6pv5n1Ak4EGGCd2sO2', '2026-05-05 00:48:26'),
(717, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Wu78J3c.YxpruiQ5LNOSXeAXR4GpRRoifAdx3OUpWdwKyTHZMlg6.', '2026-05-05 00:48:26'),
(718, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$GcfN/YS1NdC8T.7dXYsys.cG0LZsghUjD0i.U.tGnlozynquw6Z9e', '2026-05-05 00:48:26'),
(719, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$jokqBd0EPXXtqywn/tMQo.ej78GK8EFM4OC6PLpOxJmfBCXeNc6Ca', '2026-05-05 00:48:29'),
(720, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$.2P6dawPii1dbZ.etiYa2uYFyYMYTwchmklB8RFI9MeIzuZ1IvEYu', '2026-05-05 00:48:30'),
(721, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Lk2JGHYEmqukANP7BeEfBefqEuzOgeEWWHNpQ6TLmNRKzmZQ4wifC', '2026-05-05 00:48:31'),
(722, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$qw0dVvu5ZdCNOOWAdG.1LurUp7wrrL3fpj1nmw5q7QZLsJxNb30Fu', '2026-05-05 00:48:37'),
(723, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$XTFpggecy2t4sKaua0ldU.owgd1mxPIv3KqXAQ4iHakBDwPt13oR2', '2026-05-05 00:48:42'),
(724, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$lbL2MhBHa8yRmyf/yGbW6uzba.ObNTAOf.WRj3b1oCJoORYW.QTi2', '2026-05-05 00:48:47'),
(725, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$c/lk5lMbElYHkQOkcy/zR.dKKi9w7vYye6.hqf3L4p8xdgkbIjtna', '2026-05-05 00:48:49'),
(726, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$tL32RpVOeQA0HOv.9TjdH.tN1y./BA5xAhskk4V/bifZOHCfjfpDi', '2026-05-05 00:48:54'),
(727, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$GiDVBK5jmIV84r70oim1uugHUmhm0oZ5lZCK3HkgqxdzIzysbsDZu', '2026-05-05 00:49:09'),
(728, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Gj6i73E.x7exRJmsUzmrfeIwndrCGn6B29TlgvbnQmCIwfL2Bvl1.', '2026-05-05 00:49:14'),
(729, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$7rvvS..03p2uWNYFsDNkmeQUouN2zUcjqn8aBf43WiAwR37zXDBhS', '2026-05-05 00:49:19'),
(730, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$312PaLZsm2.Ms.u9iewAJeTcUQo.wS.nwZRzlCvoV6EsbeWJKLMd.', '2026-05-05 00:49:24'),
(731, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$YB0ErjdGwc3JGsE4iXpphOXBLxksY8G2AsQVm7gMHIH39G65MBj4K', '2026-05-05 00:49:29'),
(732, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$5m/Bl6cUAKH2XoMFqtDAG.nz7mu0Gsan2ciK2DJTmUS5bGqzTRT0O', '2026-05-05 00:49:34'),
(733, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$K4innBxuv6itlby93HVAy.2HQLHWwNJ5mG4peazW8h9w/xMhDfMWi', '2026-05-05 00:49:35'),
(734, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$3t.s1sViO.7AWpeWQb60qeHbyQdxPUdES4gd.LV0RXFCkCqioND/C', '2026-05-05 00:52:38'),
(735, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$/EhTSKCBwcQNI/aFmKHQBuvmtd66dJQKcY/SVvPHxvNuy2hdsxvR6', '2026-05-05 00:52:39'),
(736, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Y0.Yz2UL/8ncQtU.ivzCSuAV9is8bEzX7KeiY8Rz0F67xDN.6JlZK', '2026-05-05 00:52:41'),
(737, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$9lNfDV8Ai8vxC/lGgaYnN.SRNJSEWon7Pw3dgTZOxX3rHca2G/pOG', '2026-05-05 00:52:42'),
(738, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$/GFtkWRu/c5X5i/rfQTrkOoOjT4M8g/9BC.XvhOJ0dVEMM6wjDoQe', '2026-05-05 00:52:44'),
(739, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$3PkCUIDE6ep.jeNlZQurlexElGhn6wPhNsFOOH9KteXJ.zFsmsPWS', '2026-05-05 00:52:46'),
(740, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$La9R2o3yicdUxQU65jsnbupTiyKWd4WT3OsdFZx1LNTAUuz6r2VFa', '2026-05-05 00:52:47'),
(741, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$GTJK1MTxiUSOkSMZtlwJxOFofdGF.9LzhtohF0WaXCkoIQ6yf2eFa', '2026-05-05 00:52:49'),
(742, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$OzBGb73hSgw00exbChlWNOxZxogF7DdlcGqsaS.dgwhSFFSLJZJtC', '2026-05-05 00:52:54'),
(743, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$8WfaQpLLFNIiTl3LPGIgJuwFR/2TSQ/J9WvtES4Ve9QvyccrYEJiW', '2026-05-05 00:52:59'),
(744, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$U5axMCPw.w/tlQeWL6s8dOjX4Z/NEVFyCPF2D7H/0w7KnONR0HyzC', '2026-05-05 00:52:59'),
(745, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$lO2NnPF0DxYrcxlvgp6y/etOeML2Du3OwYqPNQTX8oVlQzjWEfAB6', '2026-05-05 00:53:14'),
(746, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$0OOgQTtbTFrjdVbqawNrru9JaPWHDBdtqg.Siv6dlydkrnPl9bchu', '2026-05-05 00:53:14'),
(747, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$j5ROmFq5hN2Qcp2tdfp2.Ovyz/WzOKE03XrEbTkLzO29lUSEBhiyO', '2026-05-05 00:53:47'),
(748, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$OjSLcPsbg1L1/ed6lA9QlOBIWQEHsF.xM8YmO0BWEGJ72.x3f83iO', '2026-05-05 00:54:09'),
(749, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$vlerJDuVH9YLgaFZrUpv6u3/C3f.q01wvkSJ80VPz6mQvDLDlTRIy', '2026-05-05 00:54:19'),
(750, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$3TKS7tPdmAoNtnK7SnPMjuspagqvTQ2HI1/4pXpRZ.XqgJaKftu2K', '2026-05-05 00:54:20'),
(751, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$FOVJSu1V5ZRh6qdGYpEPPOv/4wTRK/hDgAhaywDHB8FB3DFS.0iq2', '2026-05-05 00:54:26'),
(752, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$SWYnBKUCtGj4cFrXip9iSegW5mLgAdh98lxW5g3jl6VXuF99ec6R2', '2026-05-05 00:54:34'),
(753, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$.NSVTKdnMYnxLDQWeLPx8ujN99SRne8P7hyojPJSrtk1Vk88jVA3G', '2026-05-05 00:54:34'),
(754, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$6rAC3AqPPX9hyXxcADh8.exgn1CxFBrr.p7u.QOosf3D2RTOJJgyW', '2026-05-05 00:54:34'),
(755, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$kJwg3hMGppInAW7vMJV7TebgM7Ai9T0.k5PuflN7pXYrY5TAAJU/u', '2026-05-05 00:54:36'),
(756, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$PP7Il2DhOduZgSQqMB1nVuSGMLkLiynOVErRDKE76zInhHxcQjdrK', '2026-05-05 00:54:37'),
(757, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$viKmOUmYU.go0A5oY7R32.xxWZJAblzRCiYE/B46Hs4waP7EDmsNC', '2026-05-05 00:54:37'),
(758, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$lUkszZRZuoU6Fq/9Yrf94.9U27PBarCPjrGHaOS.BMz9/nl94tOjS', '2026-05-05 00:54:38'),
(759, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$w9CebG12KEsYt23iZQyxfuhD3UFeXjw1qxhxSRXv6eFgqzVCCdHWm', '2026-05-05 00:54:38'),
(760, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$nhDcNHyNRVC9fzbfORRdp.jaONTqGPherUENQgW/Fh7/IXgcO4Y9O', '2026-05-05 00:54:38'),
(761, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$jpGLAfzGsGa8eh/d5j6FJ.Cep6Jn9FlqK2qtCpR9T9yqZMnN0QCmC', '2026-05-05 00:54:39'),
(762, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$jNhY4EiD7BoQlfq2dmj2XOOayF7gWdlNzjJAm4qimcdHj0U.q.bpW', '2026-05-05 00:54:39'),
(763, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$CvjRmwaJuZWU/6yMJ8BB1.K1TUBiZItRH0cQn7RTiZWBj0r2TBSQO', '2026-05-05 00:54:39'),
(764, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$UtFAujB33U2PpIa75mac1OS5xrZk927UVRw2.kfzVuRWBp9ZNIcMq', '2026-05-05 00:54:40'),
(765, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$iOtkv9o1qUAqY4jCP6egbOgDDTJ8bzRnbEEVN2Zd7j3i4oZdwWvW.', '2026-05-05 00:54:40'),
(766, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$njZ8Bo.70g5VgX4CXVdri.4fONucQr2UbInfbDt1G6Ompd5sOZYjy', '2026-05-05 00:54:40'),
(767, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$wRlATLeOWF4UXcNA0qYmYOlhhtN1Wm8iJp//XW92UmAy9asqqPlGi', '2026-05-05 00:54:40'),
(768, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$SHhTs3dRFMvkSUBrFv1xQOxYxrBqK3jIHCp.8t7mCBD96QKfJ//pu', '2026-05-05 00:54:40'),
(769, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$3kQfAx56epyGvcJGoQ876e5qJ6y5YbDPw31.Xc3GxIXiNcHee9LnK', '2026-05-05 00:54:41'),
(770, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$HHX0pd8D6pl8cgK7xOQDyeCrsxORMWcN6/VuuDBIrBFEp0OVFHUQq', '2026-05-05 00:54:41'),
(771, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$QkrUIyivthJE5kibg7VwKenycrEPfn235yU7EOQqBV7Z.iLK7MEWq', '2026-05-05 00:54:41'),
(772, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$g9JqEZ7dUWM.WQcSHytNyO9/0MgCUqiq2kq5Kn2L0/x/8Liyzrrb2', '2026-05-05 00:54:41'),
(773, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$XK0m/Tq9n9KUbcWXQwZa9.5UW1LDRNOyqDSa3MkDtUoZVpMw5k0MW', '2026-05-05 00:54:41'),
(774, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$wKw51CbSao8LHtSi2xVQfOzqSIJm//43dypL2Kwq4JRitLGNifLlO', '2026-05-05 00:54:41'),
(775, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$O0KjH7.6ctyGv87TS0aigOcovW1XGvElFr22dPkBs/9mhwUxfDQgi', '2026-05-05 00:54:42'),
(776, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$qrMh4ENhgRWj1M6f6fxnsuxWhCVss/gOSih6MHeepSVJOeIAcjgVq', '2026-05-05 00:54:42'),
(777, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Mnp9SYwpkNKsd7oICU8QeOtkztXlNQaCZjl9DcwlK.oFIPAsoJZbi', '2026-05-05 00:54:42'),
(778, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$4Q65VxvjtyfYhNovDEkGqO9QqQ1jI6m3Z3RjyL86DIqIGyhpfV1Ue', '2026-05-05 00:54:42'),
(779, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$CAZLQD1kjZVCEeD4GLM2SOrUTyyNra//O5uIWpDvT0kp86ggXgrJG', '2026-05-05 00:54:42'),
(780, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Pg7H8IaCgrhsrwRUIGo6se2ZWbIz.uyEqZQHrRosAE9wmXSKmaek2', '2026-05-05 00:54:42'),
(781, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$HKbYZMIqxD3Y4G2UxMA/X.zdXfoz8pLKa27/ubZ71jBdzF4K8SBte', '2026-05-05 00:55:01'),
(782, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Tiy6vhYVbUVIfJ1V74LhiuPEiE.9vjZHwnyDFLdhzYbXSJZ45sXBy', '2026-05-05 00:55:33'),
(783, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$FaSzLMopTNKpOreL3bxLI.6bas.6eLNv0ja6JjHlcfw2qzVfMFeLK', '2026-05-05 00:55:33'),
(784, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$6BgbXiZLQgq68dvQ6BNd7O1WFEgVjlIlH.C7242NKD5Na2uDS/FcS', '2026-05-05 00:55:33'),
(785, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$xI8Mj4xB.B.jKBwhFq1exO52vv6yTYFshg8g2zZHJF7LkXBX46z0q', '2026-05-05 00:55:34'),
(786, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$FaFtLyKPbHJGHySv0n8GnONIVqo4Gv/bbmBDqLt6bFQkRN3H398k6', '2026-05-05 00:55:34'),
(787, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$LrnHqZmf/FqDHA2ZM1Y1Cei5im/0cUA5Ol5KRSFVCxwktC.Rnr8qq', '2026-05-05 00:55:34'),
(788, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$9xz8BN9IotFDYiXO9SuCZ.1bSuVJMTgxajJcaxFiPQC29qkx9YGI.', '2026-05-05 00:55:34'),
(789, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$bJccTS6YCKNX8CbbAXTUUOf/HBKwCWc8CpC9rr6lV2hN4hUWEPAN.', '2026-05-05 00:55:34'),
(790, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$gx3KYQUxD6QvMJq4mw3ZNekrFG/QZIvNimxIA8ccLvxjVZ6/MjCzS', '2026-05-05 00:55:34'),
(791, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$KPXO706vWV9pVjvuySzAIe.nlwkPtb0iIiKcjPW99eOW.npBabaoe', '2026-05-05 00:55:35'),
(792, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$vTkeTmWMSDyd.17Tp1UYcufOubAvHfkIbXFNVL2nVvuWI4LTPcC7S', '2026-05-05 00:55:38'),
(793, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Tk7PYVnsbfKRsUDC8.w0F.b6geFfxDC/wbBqyZ7GR17QVXOyJffVe', '2026-05-05 00:55:44'),
(794, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$baCM4Yql9tne4j18DZfXhOAP96j3MX0waEB1GFrqgbikmQoL.B/Ei', '2026-05-05 00:55:44'),
(795, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$9EmXHU6qimBBXwSm1vIPmuMxuyfC7OJ.j5bEhFdqEKJKW1iIda3ne', '2026-05-05 00:55:45'),
(796, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$W8CgEYvtYSeUBEIp.2lfKOL22L28DwnF9QOrPCYIx2sjnaH2ebrVe', '2026-05-05 00:55:50'),
(797, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$0mp2/CSYV6PigJ3PcItTTe3t53TO3DuncHd3POzmuvrB2fl64RYki', '2026-05-05 00:55:57'),
(798, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$QqF4zV9NQt0ZPhSlTopI9ebTuVAikYndY09TXygzPyGCBOy7msAqq', '2026-05-05 00:56:14'),
(799, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$rLPyLUTpNevSya8IDVIAWODmtGdT5855jG5JVxGUb1cvLe4s2Sbba', '2026-05-05 00:56:15'),
(800, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$UQ.oW4Qww/faeQ8vhS8.pe6ehl/MlCx6ZmXjp6acYM.p/IUstTbAm', '2026-05-05 00:56:24'),
(801, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$jF3BX.aASf4Zjac9P64c.eQl/i7.7qFdUryxsalTiXr/wTpBe0alm', '2026-05-05 00:56:57'),
(802, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$SFhULahsivqC8UjISOLQQOXXqfZAgHTK8ag6KQzsNSQ3cn60uLWMS', '2026-05-05 00:57:01'),
(803, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$JStmdd9doXLaPCUnzYLTKu/0xK6TT83jMZ1EIbhdJYdJk3/ndXlAW', '2026-05-05 00:57:11'),
(804, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$lG./X88g5ObqEDwbInsyqOU1XAelarixgn6Ql/lfreYHuJpXfoAAe', '2026-05-05 00:57:22'),
(805, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$U17UYFzOiV.gqfTmxhwE3O6tA534J87jM6h.hd7tFQxQes31z5meG', '2026-05-05 01:04:56'),
(806, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$oW68ZEH77CYeVBWb/QHrguApsQaIMhCQ.IC15a9915Qf0Qa/cDAFO', '2026-05-05 01:05:41'),
(807, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$cgL1Sc8eYnGYWL2T3BIVw.bt8K5MXtKBVYsPvX1F5ii2Q3avu8bw.', '2026-05-05 01:05:45'),
(808, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$H7/qnXQBC77bdtvwwwpKBOv3N3gS153HAdB8QaaGk4szgjKyXMwHu', '2026-05-05 01:05:49'),
(809, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$4NdHvd.OWfjU4Zq.OA7hAe.HC10g0pxtPyFuqRlACmawA03yN81i2', '2026-05-05 01:06:10'),
(810, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$SCdG1V.nJv.xlqEdK4UIMOZmnTCV11saMthvw9p8PbnnaSAEYcbta', '2026-05-05 01:07:04'),
(811, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$qnlI80ZbaOucA/e.z6f08Oih8qFXmoO9NN/BRoVRSKFM3K74ZTaiO', '2026-05-05 01:07:10'),
(812, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$qYMO0p1A2zkOi2OGsBnWdurQkgPdZv1hSnEAV3iFhfq5HD6dVWNZi', '2026-05-05 01:07:14'),
(813, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$QFLTZOF8zb270yiat2el1eyg4F7ZUMzOnSk39Df/00QqFWaV1ij5i', '2026-05-05 01:07:15'),
(814, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$HIFZ3Fk9tk.5dOV7JQWxsuzePIvseHQGFkJZ9E2hOjMyG3N4wS/JK', '2026-05-05 01:07:19'),
(815, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$J8Evugdn6z4brHHcSsMmlunhpDQhnN9Y51RaHyTHUEosRl1N/JUMK', '2026-05-05 01:07:19'),
(816, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$h1JmaXHqUCSXd8Thb5uba.RwLt5p4yNc2OvR90GEMAGuwAIxczyfq', '2026-05-05 01:07:23'),
(817, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$/mh//LivURPQon4pUPYhguiyiYr/zx9.dO.HujhSvCNL41Y/p2Lia', '2026-05-05 01:07:34'),
(818, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$q57EBZmO2Q9cTOmfbz1YiepaITdpjGqAn2k/GoL.Ekg16O4iNhRVe', '2026-05-05 01:07:39'),
(819, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$T8/psxD2rwm8zuhFFcEc9OPfGNsB589oaEqTFdG92UUplfbir6bjC', '2026-05-05 01:07:39'),
(820, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$zN9uQJpzVqq6j5QuDDjIWe6.FI0PXpuEL5cpuTsXVZpKrSL8tJrBi', '2026-05-05 01:07:41'),
(821, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$IdpsG24QfQceL99zGfcxQOwBpdU2EYO05uVb2WgTS5XDnyR.JDeHi', '2026-05-05 01:07:45'),
(822, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$gxq/wyfmS0fesSQEk1FpxeGWTCTsLC77itLKDD8mWxIMpzIMqr68u', '2026-05-05 01:07:47'),
(823, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$sv4UmCoJKFK26X57exvKcex1seL1JxBn4GZsHwOVBGEMp5uMuAMAq', '2026-05-05 01:07:49'),
(824, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$W9RYzD8XtNbUvYvMtchy0ONmyebUCnEFvmEP8lCPBvvMhBbPmf0G2', '2026-05-05 01:07:50'),
(825, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$TbEDT1mQw.//krOboKBl0exZ/KjLgi6ATMCzJrW4jn6qYEXy4hrqC', '2026-05-05 01:07:54'),
(826, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$uMys1E4UYQK8SOCHtzct3.eanXInvkrD69z75kHG.r4nB8KPpSU/S', '2026-05-05 01:07:59'),
(827, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$vEm9YYOwc68F4Jg2swJJa.oRxHfZFU7.FTpykpOQjRQuB3QSQwmSa', '2026-05-05 01:08:03'),
(828, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$r/22F8THdu7TWNUYFbvyi.uD/1THkVtKjYpyQVQngRPU/MSE4vrXe', '2026-05-05 01:08:06'),
(829, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$tWqW1TuVgZHnVoNMIPbgfOAbk1SpRMdpMnIezwerJRQmLqEd48db6', '2026-05-05 01:08:06'),
(830, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$KCq6nphLVdgCN37.n4i2LuXXRrDuoM5g0SdxtZNMo9AuG91PHZ5dW', '2026-05-05 01:08:08'),
(831, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$1Te43gfl5SvqsaZjmjjtq.XGHqDvBZMwsgHU1J8d9MDe/UplhZLEe', '2026-05-05 01:08:08'),
(832, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$h0K5.QUSWcd92tKIWVJcOOyQ8fMEACO6o.k45aV6UBnJeMXx9EDEa', '2026-05-05 01:08:09'),
(833, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$ZzOiAvZfZ2FkiWr8cMA9h.lp21NpfP/ssUaL.8/hmqgxKTh2D9RLC', '2026-05-05 01:08:10'),
(834, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$z/Cl.DqqNqWRAw8xls.V4uqsILA.eTiVtuFO/PkylR/Ba9SZ8FVpS', '2026-05-05 01:08:15'),
(835, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$H3EIYv/kYvYzN4I1ev9Sluz0.0W75HbcnLrNiXpwoigcew1x5.ni6', '2026-05-05 01:08:17'),
(836, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$hXvrLea0LSKV6ipKhxuVb.LgKrdKkSEo0k7ORUJUZGbPw/zQ.uRVW', '2026-05-05 01:08:19'),
(837, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$G09TOFBWVJEa/oBpmV7SdOPhxjkjH7M91MeEJOLzAG0arnacN.gQK', '2026-05-05 01:08:27'),
(838, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$xkf2cxTzNMpmGOPmHxp2aODl2oFhOtz2tuBto7HK0TDNGoJHoFL0W', '2026-05-05 01:08:32'),
(839, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$MrrtlEltMKZnLDQexKPW2uzHGY0H6LT.bg2cygPDZ29FZoLoCGBXi', '2026-05-05 01:08:34'),
(840, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$RkQd1/pjQL30uPhymCq2JOZlDaYDQo7s9nIRH1lI6efGXKwRKEMXG', '2026-05-05 01:08:35'),
(841, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$0P8ZLiOTsRupkrkXbS1oge1iMESE7nfSAQITeSNnYxMRlAzeRKVDC', '2026-05-05 01:08:36'),
(842, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$lh3UThulD/WQtMjytKTSF.hejndumNuwNhtupyWI8gbf4rCKN6aaC', '2026-05-05 01:08:47'),
(843, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$zDcs09YluN0FWso56xHJpObtiJucakIUpRbOmsjUjWks/GrHnUSKy', '2026-05-05 01:08:47'),
(844, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$pKSilvJGDwUF5u0lqGUSa.hHaxJMN2sR7RddrczoIGLwflKu6B9I2', '2026-05-05 01:09:10'),
(845, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$IHmbxJIdLCwQQNc.sJ3fCuW7z/BfZXtT3SHtR6ArUmcTV4NkGpS.K', '2026-05-05 01:09:15'),
(846, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Y98RE8IA3D6u5/C6GDujnOVQaGOI3RE2TkU9nv4SbANXeZJLzG/s2', '2026-05-05 01:09:20'),
(847, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$KP1K9joLbVvm60Ka5Zui2eDdgHAnRonM/Hrv8frO2p1XNuYYjUK3i', '2026-05-05 01:09:25'),
(848, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$fdpGDVP3NEmITNCW3yggfuZ9SOLcGERdpqPaaalwPJcv60L4J6jq2', '2026-05-05 01:09:30'),
(849, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$a0ajDtc2sHyaaKmK1gu.yucTUv1sp2CsiQDRy9XcrtAIZO9Gb.6/.', '2026-05-05 01:09:35'),
(850, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$FhL0ODA9sU7vgzL1hzJoFeEoDdPsrnqyVdXWGdG7qKIwB3rWVty8.', '2026-05-05 01:09:40'),
(851, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$LkCv4RQBohag.3rL5qo8fOFR/toGLoM2JpJO/sfdFKawCreUs87C2', '2026-05-05 01:09:45'),
(852, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$hvlBM4Njy7V1jnNQ.iq5Geufa3zgK.UE91onxrA0nFd9/h1UGSNse', '2026-05-05 01:57:57'),
(853, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Tm0hXpDBhzrx.6wdDHfrBul/gF9rhBwwHXjt4ttHwngxDuW61mKOO', '2026-05-05 11:16:27'),
(854, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$eQJX44.YhLG/ZGr36uoRgemc/TT7w2IF4lVOwSgC8IkgVap4TRuma', '2026-05-05 11:16:27'),
(855, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$bxnMxvvMqe9wG81YW4hFN.FbkvhkURWQJWqrDkb4Wvt6rrG4HB1ZS', '2026-05-05 11:16:27'),
(856, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$jtOUQVxH15V88dv1zqkaOOiSK8q3tUblbbOY/jsCvE4vAECZfRa1W', '2026-05-05 11:16:33'),
(857, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$dg8EvFYkOTR.iSSOadBbhOrO2PC0/2OT333eOfRaZQk9LTTiTmpt6', '2026-05-05 11:19:37'),
(858, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$GxFY4BNevMAL2YDwLS8mt.4RBSw.YIaKK4JhEcm6GlCkuYwr3dk/q', '2026-05-05 11:19:47'),
(859, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Sf1ig/avAVs5d7a1e6.0BOVK1rK3ZFKfez9kd.oqxrC85PifuR6ZW', '2026-05-05 11:20:02'),
(860, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$dzLuj.r3c.WVY9eac.l7ZedUO3vp/5XYTVO6EVQI4AwIrHKJcUwX6', '2026-05-05 11:20:03'),
(861, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$T4zn0NM.2SDzwKnll60TEuRxZ5QwvDttle1ZfDGCyA8T66nNZeibW', '2026-05-05 11:20:03'),
(862, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$X6V11ov4.LQ3aSHzkDu.a.YHjpvhQyP0kUZNHFcE5is.X/q2Xm95.', '2026-05-05 11:20:11'),
(863, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$ImaU8rk2jnw.316F/.IL6OX84EbNYN2ZqmjuJeumuPAdS6Vw5sEtm', '2026-05-05 11:20:18'),
(864, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$AFP0QffSdec6iZJE2nZoy.nJK0FS.TIOj6ODjjc.Cqs1EKVzlUwCe', '2026-05-05 11:20:21'),
(865, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$DBJa4r1dSPtGnwDhNfU2WulJeMO21k2n5GXU5shPRZ2CSGkaJNVIK', '2026-05-05 11:20:22'),
(866, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$6ta5NXOvP5dVbz67XyMbeO6yjkBsIF.5qcL4QD8qXS.Gsmyz9/Rwy', '2026-05-05 11:20:22'),
(867, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$vtYdcR6MaYuCrDjoU3LGIuM1igaojFMNNfToXSsmD4ipyMnkhskZ2', '2026-05-05 11:20:28'),
(868, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$kAwumgT7u/anY7Y8AVKMpehdigIVdelH2JjWHJbcupFg11VVCPt7S', '2026-05-05 11:20:39'),
(869, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$aG37vOfUG/MTsHWJb17gTeYEcmPwOxT5.YBWG5La/hIV3y8cn1aNS', '2026-05-05 11:20:40'),
(870, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$YnbdmTiAk6HcALCYt1ncmO6r4ggY4xqT38qvENLNrwExBlsoQaaVu', '2026-05-05 11:20:49'),
(871, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$DvXOroeEe6iL965MG0gYEuyifnKkc4iDV8xxsR.8/jKmPb31RnXKi', '2026-05-05 11:20:56'),
(872, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$TsDg27s/LGdLRBAbYL3Z2uTAabgqt0XDoCX8U5ehhQEDp0HOkLwOe', '2026-05-05 11:21:35'),
(873, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$84UOxAUUCidTuoWqztHL8Oh1ToATZh60/OCjWU.9C7f8lJw76NtIi', '2026-05-05 11:21:52'),
(874, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$fLOO18p1q.Oul7BE8Cw4VeCA55HMIv0sabe1c0A8.xzpnLmzrOgCi', '2026-05-05 11:21:52'),
(875, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$4X9HF7U4NGOT4k9vwaHF7.hUNa4.jRMd3nW9WunxmOeuBKjpmljZ2', '2026-05-05 11:21:54'),
(876, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$7jwfdqIR.B7cbPYvKDiS8uXQQYPIn3Qu5qqSFuJlPBVPwIpNdp/T6', '2026-05-05 11:21:54'),
(877, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$9U2FtEGO1e.eHSPFHRQje.b2ojx3nzW5VH0WHBbjnVWcnIIjWxqqe', '2026-05-05 11:21:57'),
(878, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$AzV3leO7UBKHW1uMEQi.qOnjUootbc1LblbwJtTs2M2.9dtL9e0rS', '2026-05-05 11:21:57'),
(879, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$/a10NaEP4gKdUGlBrNKEu.NepFMnx2UUSrQgj/5cEceGNhriluxKa', '2026-05-05 11:21:59'),
(880, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$2BAZW1qNdBIYp4N8Qqm7xewTa3lIgqGBdwZXGz1595k4L90h0G1je', '2026-05-05 11:22:00'),
(881, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$7DsQGtSGpvSmZ25TkIfPpO5jStdwqvjpvvrLwr/TW6wc6RyQzrF3a', '2026-05-05 11:22:01'),
(882, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$bIpRmzHkF/1ZYjHx9rArnOzQy8AaXlf8S5WcQsOfOQtvGqBKNU52e', '2026-05-05 11:22:03'),
(883, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$mNFhc5gNxlU/eBup6lqKVe5Yh2JohCUmExy2E2HUsYBPN//E6.isC', '2026-05-05 11:22:34'),
(884, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$smOG/q6idU4JaoTqKCvWcuXqcvJ8Za2fKZIw/H6Dw9AR8AXNW1ZhK', '2026-05-05 11:23:18'),
(885, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$f9rJUmQ8mbw.nRELNXGgsOWhLOxqoDOuIdpRMrvIzw4c4AMed8TS.', '2026-05-05 11:23:19'),
(886, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$TAO9kOf7myzkJtd4szwZV.v311Q42DbNDg/NczH4ZfZb0Fm7EbFWS', '2026-05-05 11:23:24'),
(887, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$xM0DW5KczyKyY1RoaeF2bONKkSIl2pJ6yj819jghdsqXLKK6uViB6', '2026-05-05 11:23:24'),
(888, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$8uji2DWhYPSjwOFPoHuZeuOiAa1.KAUhgMAWJ6eL3QprOrKu.UYci', '2026-05-05 11:23:25'),
(889, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$i1yAeNIGzsgLjeOgp1lG4OdKf2BG.FSSo1XB3KsIyc2ys9KVMO5h2', '2026-05-05 11:23:29'),
(890, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$M8wuNoesh5BZGeaNa8Plp.bi.uwx2sDQm7e5CYqYktjgXKsjUr.QS', '2026-05-05 11:23:29'),
(891, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$QPxIi1j7Yon/S6oJu9lw7u3MZwm.Y7WJCUYzkWThxO9LqiFpplb4y', '2026-05-05 11:23:43'),
(892, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Zo.CFvdMHIQcg.HLwz4nces9fFgohsplEEZJrieCSd.24LEufhF8G', '2026-05-05 11:23:43'),
(893, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Bb6D4I.u6ELUWSqzpTjnXO0MGteEcxgYamL0tr4borBFhkXQrfarq', '2026-05-05 11:23:44'),
(894, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$FbrYV7XkaSNjz6bDGSpYBOuX8fbsyi2bSGeM0CDKVz7IOrxpuiY7y', '2026-05-05 11:23:55'),
(895, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$lXdyY8kxymy6XT6a8nGDJOxzX8HyyIqDOkKPKaHzQceRA8lCNN4NS', '2026-05-05 11:24:22'),
(896, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$e0IRXWATgBf0Zu3fIuhaaeSU6Al.D5UAJBrlE3KZ6E3hkv88NbJ3y', '2026-05-05 11:24:52'),
(897, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$RNknGpDph3heoG65PRHCVOOqMQLCvgHrfcbN5CBtYNlkpbxh7X0wK', '2026-05-05 11:26:19'),
(898, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$UfNhWWCL1rGWK/6jhDCAE.UoB1N3oQO9v2./7jRYrrsUYnBNE1Fx2', '2026-05-05 11:26:19'),
(899, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$8CCeBfoNmvIcXhekCwWw..L1Mygyu1HuC0FNA/0X15c4NmnIu8xeW', '2026-05-05 11:26:20'),
(900, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$rqDlpmNM.tuEuCqjZptytuWol8Sb3TgLT4CEK.vjvggGRjRf5Tcdu', '2026-05-05 11:26:24'),
(901, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$SCKnUDkaOX254JCDN5g5j.RdQ9GgNhKWTck3W0rQHrlPDHeUSkNk.', '2026-05-05 11:26:24'),
(902, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$ObHEdqi/4751HSLp08KmvuOuaOvaV5Lk8R5S4IDciV267O6WVe9em', '2026-05-05 11:26:33'),
(903, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$tQXY6hsHS8nIbHGv5A0eHej9ixwea2DXOCaelYuGpuS0x4plNcz7K', '2026-05-05 11:26:33'),
(904, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$DUFT88RCfoN.1WBEZ/j/peSgXZGoq3UZ6a8hrXYsXiQRVOT.axLDq', '2026-05-05 11:31:41'),
(905, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$us136tDz0pa2ZgssUhMuauagT44rq1KbWmXeStvioCz5Kgp3VYHmC', '2026-05-05 11:31:44'),
(906, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$m.E866BPn77u/0f48R52DOKL9hZvVl1Goir8WVgaif8v2ZJyS01Ga', '2026-05-05 11:31:44'),
(907, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$CGy2gsnhAOjj1Ly2uKsu2OwADk69NO/GifWvnQ80luMBh.KEdGh7.', '2026-05-05 11:31:44'),
(908, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$/Z2IlPrVgM4rmzOis2oq8ub6qk4hEVT4kuV1KzeCg1Va44wpW6wfO', '2026-05-05 11:31:46'),
(909, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$niVz4znuPm.IH5WxB5WPTeA9MgazPH2vLs3vRWSaA7bBa2a6CfS8m', '2026-05-05 11:31:47'),
(910, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$rO8IjJ5EvHlg14X.SMCSJOKscl4RwenCtOuyw7P6HWjujRm/5h2Z.', '2026-05-05 11:31:48'),
(911, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$JFt3JzdZtQ/vwdfzi40.F.I90SF8gDvXFJAU1Eo8Gcm5FCjMGijhq', '2026-05-05 11:31:52'),
(912, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$4cKQHYN51An5kloYRfW4t.wSmEUEJN7zazL3TbYQeqIJIMF44cIoa', '2026-05-05 11:31:52'),
(913, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$yiuzaRO3P0TW5JvzX7zE4ONYH3zbP5FMwUHcMPcAD.soV4qXmCxuC', '2026-05-05 11:31:55'),
(914, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$hqqTkYhSqZilx75qvcXkZe8PVsOB/d7K0to60p0bdZPfQw6aJI0Si', '2026-05-05 11:32:36'),
(915, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$RC7YUSWB4xAOHIEh/MTZXOSdQdMMzLufuq8l5j10t0zp71pRhY/2O', '2026-05-05 11:33:00'),
(916, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$61yG9SGa5dmi/mLHZz6br.O5B9o/fSxRQEyCM2IVeXEx59xwpbsmK', '2026-05-05 11:33:00'),
(917, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$eaHC322errWX/DrMQuREuuUZBYyIC2S/ljTdq7PXenkbbHwyC8EWm', '2026-05-05 11:33:13'),
(918, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$z7IQ7V9oCFRMqJlUh8ek/.xIqNCJpgDMlQcprruNSt9alqTb6E70.', '2026-05-05 11:33:19'),
(919, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$K1OZlXsE/6WGSstk0bdPAeu80iesVJFLKI93GmLadA6KyqjYtNjFS', '2026-05-05 11:33:24'),
(920, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$m8A1fOxmz3b6hDqcE9Je../mVItJDOiIEjd6uFlDhu9FIwwfx./LC', '2026-05-05 11:33:29'),
(921, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$c810vnyEfMxSFbA0klNiau2Y87MeWccI9iziUOOIOOr.UCBg3XXpS', '2026-05-05 11:33:34'),
(922, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$NXUNVxK7Y5vpAo5OXUO.QeRn3kwnco53pNW3vid3aVkiQn6PiBjWi', '2026-05-05 11:33:39'),
(923, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$.TH6ElR1.SuJq63sMwufNeiVPNAuy3qelX0BXceB9e3ANwmfhkCzq', '2026-05-05 11:33:44'),
(924, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Wml3JIldCG4lzNTLPo.AJuFAQPBB5lEqIYAf8wImk.fVr3YOS5adO', '2026-05-05 11:33:50'),
(925, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$NqJPS0Q4uJ4oVaehteO1NuwRfpn0nQmxVq7YsfJHaP3awVxIWzn.6', '2026-05-05 11:33:55'),
(926, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$LZZQGlXSK/4tEcTgIqnEKOMjYoLJ1.ZSlKebH/v7WCwEpsrALCJGu', '2026-05-05 11:33:59'),
(927, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$ZpfXlbkdSj6tTVKbcRp7L.RVEQCrt7NPxjIOWRBod/ipWHVzxG9iu', '2026-05-05 11:34:04'),
(928, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$ndmojIynMTjMbwHD/cRugeQGlTnpyrtwtkPxTVP.m9H6GbI4JIvWG', '2026-05-05 11:34:09'),
(929, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Czmx2IbfbDKCNrdYkW4gE.zT/uP25C8hOez1sGp8ODo.kGIEdzTPu', '2026-05-05 11:34:14'),
(930, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$prrgHgBeUbuZsiopYsTPmuc4HaOb076dp3vJmuTLLUu0cQusi.leK', '2026-05-05 11:35:33'),
(931, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$quXslBa4ky/ABuT7Pu8.5.DrPkgeRMjhdM.nVtDBh9gm77JAvx4/e', '2026-05-05 11:35:34'),
(932, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$diTqPxuK3GUbe/fLsCL1bO9Gfmcf4NySFPmNOVCMt/.QNQXq4FS/W', '2026-05-05 11:35:36'),
(933, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$TmwaLOKiioZt0c/Gr5KKSeDhWWMo5HD3QY6/Ryr8QhdSSNAWvUs/2', '2026-05-05 11:35:41'),
(934, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$w3G2VtcLfC1xdX5UbJArh.m1xsGXPVD6phoxaNay6g3pdpbrAAU1S', '2026-05-05 11:35:46'),
(935, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$GB9jtzSAGHlK59jFOMrRG.CNAqzGwhLB8Bvs/508RqKMlqIZQ64e.', '2026-05-05 11:35:48'),
(936, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$FdiQpcTAVuYSOI8WTQcSiuN1K.aRtMVXZyN5RwSJkcHgrCyJwqRW6', '2026-05-05 11:35:48'),
(937, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$otgGagJISmyHnomaD70tCe7LtYcY13Pmtv6qFpbmAwsYlW3jdg9F6', '2026-05-05 11:35:51'),
(938, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$3IOi0bwA4cZjaQeVlICJxekf8F6tSJv6X4gRyoeKojkRk8JEPA3Rq', '2026-05-05 11:35:51'),
(939, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$maPdepg/ojQVNjUJVOxOVO5mb0HzQJpJD7LQG3lRNBE37F/REFQO.', '2026-05-05 11:35:52'),
(940, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$nazO118Sq1Hi6MDSWBzEmeDXnfnO.6SV25VugTyKV4W.YiyMaT1z2', '2026-05-05 11:35:53'),
(941, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$cq6WwMtBy.E9GhfMdWPHmOfLCcdEIJap3OYMfJ52QOHAsMbEGsNQS', '2026-05-05 11:35:55'),
(942, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$DNyRumE.bixxmGwBF/aY2Ob2wsqkQMYR3r1cf.1YEA4OA5luJunxO', '2026-05-05 11:35:55'),
(943, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$4/VT3xH7oZmD0UuBLRPRT..NtqO21IvAj3Twhw1lzdtFE7/.FMzZm', '2026-05-05 11:37:25'),
(944, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$HYd35Qa.d8JBRp/Kmz5zaOGjekMjDtW3qZg43nwsuO1zHgZj4Hojm', '2026-05-05 11:37:25'),
(945, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$t.9i1a92nLxRvYNN6B2ZH.hzu/BQrzqd7om.FMadmkBYxbaEbiQZi', '2026-05-05 11:37:26'),
(946, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$J4yYZ8i10S4w7ytWjxGBAeMTgpro.VM02cnqPlSQ.bEQkB1D7nFn.', '2026-05-05 11:37:26'),
(947, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Ec.dRJMpPTFUH1kXTqNlMepMlLabizajb4NDKOnLV6aeQmC0hePxu', '2026-05-05 11:37:33'),
(948, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$7p.28SLqdcoouWBzN.LlpuzQ27RKLwWLts441kj0q8Y2zlx2umPua', '2026-05-05 11:37:34'),
(949, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$5epdbuYP3iqM1nqWlILIyuEfj8M/Eosn3CEA5jydXKFZF5Mzy28J6', '2026-05-05 11:37:36'),
(950, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$2YoqtcrrNa7ziVJCNpT4tuMFjUGqg1OYGHOMYlYQMTRm1b48QwU0a', '2026-05-05 11:37:42'),
(951, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$X13hmepaKutBoow45Oh1DuEZo4MhIQbnThFoO9KOei7A3NCC59Cze', '2026-05-05 11:37:48'),
(952, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$iyw4LF9K676c/2cZ4g/1Se3Z1M3UQrBhgnIZHnCP19jgtBTHwJwzC', '2026-05-05 11:37:52'),
(953, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$tMEelXpwZwhjN5escqdp9e.O3Fg5cmtfYqqnw6xWtHu4TUNMPZtRK', '2026-05-05 11:37:52'),
(954, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$ZKNAgwYwyV28xioX/hXHmuub6AVlhMzGQ6yfhySkk6SEFVYgCkPmC', '2026-05-05 11:37:53'),
(955, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$IrQ.OYCIS7YBZbkdbQRHfePrZ9zthZ56fbdqdzdXV08El7TJDD/fS', '2026-05-05 11:38:05'),
(956, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$3bEPy/BNph66OnvgWXamiO1Gu9N9EZNdjFVDNBuA.rEvHhCcqon7W', '2026-05-05 11:38:10'),
(957, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$zXHPRGeaTK.dbJjC9jBtgOb90W2OtIGGk8Mfgo9Zfto.QLnwhlYLC', '2026-05-05 11:38:10'),
(958, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Y1gUtc/q84u2gllddrdghe0Nc32l/M1p4Tksy2Dte3clv3TeM7NUm', '2026-05-05 11:38:16'),
(959, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Tnp8R6WpVxR5hv8JEoaB.Oau1eRe0Tc6FCL/8G1YIhnX1t.bkseZi', '2026-05-05 11:38:23'),
(960, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$B2eCsPP8e2PLCNbuSlOzquoIC2n.5rIY9fLUFKmBHnyB67.HemdZ6', '2026-05-05 11:38:23'),
(961, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$BjDgu8XxgutzRFxEl1gscucOEqzXMiAyvNO7h9lTlELk9CxUvEyK6', '2026-05-05 11:38:29'),
(962, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$DS0Qsv0jGFN1VH7amCbxJOGv6FVyKBQduvj8wL4ynnGpBH65BZoJK', '2026-05-05 11:38:34'),
(963, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$0Cx81QEt1z..pjHU7hTtjOSqG0IyOrONoJngIINnCgFZba0vD6eRW', '2026-05-05 11:38:39'),
(964, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$S26LPc8j6amdqXydGsCWXeP.4SpFISSklz.tMOGdc69J/XGMsj9ke', '2026-05-05 11:38:43'),
(965, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$qrc6eIm4mMzGt2TpG2wvn.WC5.gCovUNV.oEjg06p.f8jwwQymKH2', '2026-05-05 11:39:02'),
(966, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$yLz/4xlPLhmGlSNACdPas.x0FSys9PyECsW87xkwnaqYWeO/yWGM2', '2026-05-05 11:39:16'),
(967, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$s3XPnH.UxTgqAJrM1P.n4ex10Ic9qBUIgK8sTDA46Ii9q.85BP.HW', '2026-05-05 11:39:17'),
(968, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$aqjvzufV0sPk4WGchO07MeTY1MrdFSQAx1Jhy3njcgsccsiWm9x8W', '2026-05-05 11:39:18'),
(969, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$pxmie5oWivxg1IzeBhVutuJVxR/HIhWKuaOT5vOz6F.m37wpEb20K', '2026-05-05 11:39:22'),
(970, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$CswuESLxJzwYivxX8Az0/uajy1H/z6VRZ1j0S2wNdFnZiAhwHAZhm', '2026-05-05 11:39:31'),
(971, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$TFe/j1IxHYzbbSgb1ZmMF.o8/wvruxc1jfNepjSuqjsYO/wJ9zPdC', '2026-05-05 11:39:36'),
(972, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$7i4NKuF20mh1VT8cLw8EWO6RflAyf1g.gGS4XaC2I5zdiODZwttji', '2026-05-05 11:39:44'),
(973, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$Qax.EKGntKZaFl.NydHfuOPqkJN09t5dTJhRP2r0.SR/aEAeISGv.', '2026-05-05 11:39:47'),
(974, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$9/TeqoxJzppnT2Kum4KZvOWNIzGtl0dH//qmspdeBPh7mpCjY1pA.', '2026-05-05 11:39:47'),
(975, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$mt.gbyjOXgBAvBWkKU6mcuTl0GTVTlfSVv5jJ8KPyy7O9gS5xI9zi', '2026-05-05 11:40:10'),
(976, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$O2bKAOtcq1R5PsxOro5TcumUCQQtE3sgE9P0qduXNFJ3MKavEZlnC', '2026-05-05 11:40:15'),
(977, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$tAHu/HiK7IviUk9noeDQKeJ63yCfgURCK2u2jjKlQ7ibbnZPe0Q3S', '2026-05-05 11:40:20'),
(978, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$iH4C62TQqKNK9/WQQ85NY.KnfQGCHbkWFwU8KgeaH1xvlEUrKLGWq', '2026-05-05 11:40:21'),
(979, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$CAN1YgnxvSNnzn7SXzXOa.XhWkldf73VS8m6cMmH4dpLmlwF2mmb.', '2026-05-05 11:41:06'),
(980, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$IawFASLm4ZS7YXxeSSiwbuu68xR7TLfZsUPrVU8Uod9YwvDlXyytu', '2026-05-05 11:41:12'),
(981, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$B7Ue7ZMB6khb.xCk8XGWvuS9.2KE0av/rDoSrzT9XPuhCriWs.UkK', '2026-05-05 11:41:17'),
(982, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$UhYGcTx7X0NRdlejzU75HOKmh1qhC8WFEJmFKQ1YobROZkQXiZ0fC', '2026-05-05 11:41:22'),
(983, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$QLODEwzbWNlnLG7MY5xrKuPT3UNQRqd2QOu/VBGjbP8nSLbzgVcMK', '2026-05-05 11:41:22'),
(984, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$tpucv7zIjSki3dnFqStDWOUAVKhOKSbpfXCkjDp8P1jItHhanYayS', '2026-05-05 11:41:28'),
(985, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$dIb8GQuflD7PSLHNyhnQ1uRUP9tT9hQZpeiVmhSyubF1IhgP8W3M6', '2026-05-05 11:41:34'),
(986, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$HbjKX8jtqaoaWp8jaXTl5.hOknl4Yr..3iV8NQJYbmTHhNX8SNXeO', '2026-05-05 11:41:52'),
(987, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$xT7by87pqJC1e8H2TCACSeTG//0TtHGj4Tu8ajjh77fW38xhMu6u.', '2026-05-05 11:42:04'),
(988, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$UPq6l7m7uh25/zUr8WjKsu8nGFpluLoCngs2pqbTfKIXpecF9ZvR6', '2026-05-05 11:42:19'),
(989, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$vCOA6qE/FjxMDrEaViMu.eq27IQVzgDAxVRlRFbRWSLpj4UyUZQcu', '2026-05-05 11:42:30'),
(990, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$0wIybWJR3qOHdG/h0JxUneLjghFCFLjy61tmhGXJqksglR7Mby.V6', '2026-05-05 11:58:03'),
(991, 'Super Admin', 'superadmin@pdvo.local', '$2y$10$w.av4Oxl7PS6Velg6w48FO5L1lI9TEosP569DAHqbJd8PR0gxXnLS', '2026-05-05 11:58:06');

-- --------------------------------------------------------

--
-- Estrutura para tabela `suporte_tickets`
--

CREATE TABLE `suporte_tickets` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `resposta_sa` text DEFAULT NULL,
  `status` varchar(40) DEFAULT 'aguardando_sa',
  `respondido_em` text DEFAULT NULL,
  `created_at` text DEFAULT current_timestamp(),
  `updated_at` text DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `suporte_tickets`
--

INSERT INTO `suporte_tickets` (`id`, `tenant_id`, `assunto`, `mensagem`, `resposta_sa`, `status`, `respondido_em`, `created_at`, `updated_at`) VALUES
(1, 3, 'Bem-vindo ao PDVo.', 'Você já pode usar as ferramentas do PDVo.', NULL, 'aguardando_sa', NULL, 'urrent_timestamp(', 'urrent_timestamp(');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tenants`
--

CREATE TABLE `tenants` (
  `id` int(11) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `razao_social` text NOT NULL,
  `nome_resp` text NOT NULL,
  `email` varchar(191) NOT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `plano_id` int(11) DEFAULT 1,
  `plano_nome` varchar(30) DEFAULT 'BASIC',
  `status` varchar(20) DEFAULT 'pendente',
  `data_ativacao` varchar(20) DEFAULT NULL,
  `data_vencimento` varchar(20) DEFAULT '2099-12-31',
  `db_path` text DEFAULT NULL,
  `admin_senha` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` text DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tenants`
--

INSERT INTO `tenants` (`id`, `slug`, `razao_social`, `nome_resp`, `email`, `whatsapp`, `plano_id`, `plano_nome`, `status`, `data_ativacao`, `data_vencimento`, `db_path`, `admin_senha`, `observacoes`, `created_at`) VALUES
(1, 'demo', 'Empresa Demo', 'Administrador', 'admin@pdvo.local', '', 3, 'SUPER', 'ativo', '2026-05-04', '2099-12-31', '', '$2y$10$TDBLdzgzJ7tuC8I7TjWok.jFMBZaDeaYO//o8X7Ql4FyD2/whs6Yq', NULL, '2026-05-04 12:08:54'),
(2, 'zeider', 'Zeider', 'Felipe Zeider', 'zeider@adm.com', '11 99587-1339', 2, 'PRO', 'ativo', '2026-05-04', '2027-01-01', '', NULL, NULL, '2026-05-04 12:25:28'),
(3, 'sabino', 'Mercearia Gabriel', 'Gabriel Sabino', 'sabino@adm.com', '11 99918-6361', 1, 'BASIC', 'ativo', '2026-05-05', '2026-08-04', '', NULL, NULL, '2026-05-05 11:20:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `senha` text NOT NULL,
  `perfil` varchar(20) NOT NULL DEFAULT 'operador',
  `ativo` tinyint(4) NOT NULL DEFAULT 1,
  `ultimo_login` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `tenant_id`, `nome`, `email`, `senha`, `perfil`, `ativo`, `ultimo_login`, `created_at`) VALUES
(1, 1, 'Administrador', 'admin@pdvo.local', '$2y$10$UUnDZSYEngDjs9iSHurPpuAk9veBO4sQd61Fe6Ce2.E4j.KiTPTO.', '', 0, '2026-05-05 00:48:29', '2026-05-04 12:08:54'),
(2, 2, 'Felipe Zeider', 'zeider@adm.com', '$2y$10$1aTIlAeOviphL0/GwbMMvuQUmqxzqt..WZD5IYjN/Hp0bYtCkFIcW', 'admin', 1, '2026-05-04 13:17:08', '2026-05-04 12:25:28'),
(3, 3, 'Gabriel Sabino', 'sabino@adm.com', '$2y$10$oktxY.LqbcNkmrNLozptue08vNfiExm4SqJsURgwp91d6uuX0OxTe', 'admin', 1, '2026-05-05 11:38:23', '2026-05-05 11:20:19');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `caixas`
--
ALTER TABLE `caixas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `caixa_movimentacoes`
--
ALTER TABLE `caixa_movimentacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_cfg_tenant_chave` (`tenant_id`,`chave`);

--
-- Índices de tabela `config_plano`
--
ALTER TABLE `config_plano`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `email_log`
--
ALTER TABLE `email_log`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `estoque_movimentacoes`
--
ALTER TABLE `estoque_movimentacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `indicacoes`
--
ALTER TABLE `indicacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ind_indicador` (`indicador_slug`),
  ADD KEY `idx_ind_status` (`status`);

--
-- Índices de tabela `pedidos_ativacao`
--
ALTER TABLE `pedidos_ativacao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_pedidos_ref` (`ref`);

--
-- Índices de tabela `planos_sistema`
--
ALTER TABLE `planos_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sales_tenant_status` (`tenant_id`,`status`);

--
-- Índices de tabela `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `superadmin_config`
--
ALTER TABLE `superadmin_config`
  ADD PRIMARY KEY (`chave`);

--
-- Índices de tabela `superadmin_users`
--
ALTER TABLE `superadmin_users`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `suporte_tickets`
--
ALTER TABLE `suporte_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_tenants_slug` (`slug`),
  ADD UNIQUE KEY `idx_tenants_email` (`email`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_users_email_t` (`tenant_id`,`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `caixas`
--
ALTER TABLE `caixas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `caixa_movimentacoes`
--
ALTER TABLE `caixa_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de tabela `config_plano`
--
ALTER TABLE `config_plano`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `email_log`
--
ALTER TABLE `email_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `estoque_movimentacoes`
--
ALTER TABLE `estoque_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `indicacoes`
--
ALTER TABLE `indicacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pedidos_ativacao`
--
ALTER TABLE `pedidos_ativacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `subcategorias`
--
ALTER TABLE `subcategorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `superadmin_users`
--
ALTER TABLE `superadmin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=992;

--
-- AUTO_INCREMENT de tabela `suporte_tickets`
--
ALTER TABLE `suporte_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
