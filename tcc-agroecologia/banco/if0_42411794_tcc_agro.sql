-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 27-Ago-2026 às 04:27
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `if0_42411794_tcc_agro`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `autores`
--

CREATE TABLE `autores` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `instituicao` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `autores`
--

INSERT INTO `autores` (`id`, `usuario_id`, `nome`, `foto`, `biografia`, `instituicao`) VALUES
(1, 2, 'Autor Teste', '../images/autores/autor_1_1787444142.jpg', 'Teste teste', '........'),
(2, NULL, 'Autor Temporario Teste', NULL, NULL, NULL),
(3, NULL, 'Autor Exclusao Teste', NULL, NULL, NULL),
(4, NULL, 'Ana Maria Primavesi', '../images/autores/autor_4_1787796937.jpg', '', ''),
(6, NULL, 'Miguel A. Altieri', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `avaliacoes_pesquisas`
--

CREATE TABLE `avaliacoes_pesquisas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pesquisa_id` int(11) NOT NULL,
  `nota` tinyint(4) NOT NULL,
  `dataAvaliacao` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Extraindo dados da tabela `avaliacoes_pesquisas`
--

INSERT INTO `avaliacoes_pesquisas` (`id`, `usuario_id`, `pesquisa_id`, `nota`, `dataAvaliacao`) VALUES
(1, 1, 1, 3, '2026-08-19 19:40:41'),
(2, 4, 1, 5, '2026-08-19 19:46:50');

-- --------------------------------------------------------

--
-- Estrutura da tabela `avaliacoes_site`
--

CREATE TABLE `avaliacoes_site` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nota` tinyint(4) NOT NULL,
  `facilidadeNavegacao` tinyint(4) NOT NULL,
  `facilidadeBusca` tinyint(4) NOT NULL,
  `clarezaInformacoes` tinyint(4) NOT NULL,
  `comentario` text DEFAULT NULL,
  `dataAvaliacao` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Extraindo dados da tabela `avaliacoes_site`
--

INSERT INTO `avaliacoes_site` (`id`, `usuario_id`, `nota`, `facilidadeNavegacao`, `facilidadeBusca`, `clarezaInformacoes`, `comentario`, `dataAvaliacao`) VALUES
(1, 1, 5, 5, 3, 3, '..', '2026-08-26 20:55:14');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pesquisas`
--

CREATE TABLE `pesquisas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(250) NOT NULL,
  `descricao` text NOT NULL,
  `resumo` longtext DEFAULT NULL,
  `palavras_chave` varchar(255) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `autor_id` int(11) NOT NULL,
  `regiao_id` int(11) NOT NULL,
  `solo_informado` varchar(80) DEFAULT NULL,
  `solo_id` int(11) DEFAULT NULL,
  `cultivo_informado` varchar(80) DEFAULT NULL,
  `cultivo_id` int(11) DEFAULT NULL,
  `pesquisador_id` int(11) NOT NULL,
  `administrador_id` int(11) DEFAULT NULL,
  `status` enum('Pendente','Aprovada','Rejeitada') NOT NULL DEFAULT 'Pendente',
  `observacao` text DEFAULT NULL,
  `dataEnvio` datetime NOT NULL DEFAULT current_timestamp(),
  `dataAprovacao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `pesquisas`
--

INSERT INTO `pesquisas` (`id`, `titulo`, `descricao`, `resumo`, `palavras_chave`, `link`, `autor_id`, `regiao_id`, `solo_informado`, `solo_id`, `cultivo_informado`, `cultivo_id`, `pesquisador_id`, `administrador_id`, `status`, `observacao`, `dataEnvio`, `dataAprovacao`) VALUES
(1, 'Pesquisa teste de manejo do solo', 'Pesquisa utilizada para testar o sistema de submissão do Acervo Agroecológico.', 'Teste do fluxo de envio e aprovação de pesquisas.', 'solo, manejo, agroecologia', 'https://example.com', 1, 4, 'Latossolo', 1, 'Milho', 1, 2, 3, 'Aprovada', 'Pesquisa analisada e aprovada para inclusão no Acervo Agroecológico.', '2026-08-17 22:26:59', '2026-08-17 22:45:30'),
(2, 'Teste 2', '.', '.', 'milho, solo', 'https://example.com', 1, 3, 'Latossolo', 1, 'Milho', 1, 2, 3, 'Aprovada', 'ok', '2026-08-17 22:56:40', '2026-08-17 22:57:49'),
(5, 'teste', '.', '.', 'excluir', 'https://example.com', 3, 2, 'Latossolo', 1, 'Milho', 1, 2, 3, 'Rejeitada', 'teste recusar', '2026-08-22 22:02:44', NULL),
(6, 'Influência da técnica de plantio no rendimento de milho (Zea mays)', 'Estudo experimental sobre diferentes técnicas de preparo e manejo do solo aplicadas à cultura do milho.', 'O trabalho compara diferentes profundidades de lavração, formas de adubação e adição de matéria orgânica na produção de milho. Os experimentos indicaram resultados superiores com lavração rasa e adubação orgânica. A aplicação superficial de palha e farinha de ossos apresentou rendimento superior ao método convencional empregado no experimento. O estudo conclui que técnicas desenvolvidas para condições de solo e clima de outros países nem sempre são adequadas às condições brasileiras.', 'milho, manejo do solo, adubação orgânica, matéria orgânica, plantio, fertilidade', 'https://periodicos.ufsm.br/revccr/article/view/72431', 4, 5, 'Solo tropical/subtropical', NULL, 'Milho', 1, 2, NULL, 'Pendente', NULL, '2026-08-26 22:52:00', NULL),
(8, 'Estudo de fertilidade de solos do município de Faxinal do Soturno (RS)', 'Estudo de 1970 desenvolvido na UFSM sobre a fertilidade dos solos de Faxinal do Soturno, no Rio Grande do Sul.', 'O trabalho investiga aspectos relacionados à fertilidade dos solos do município de Faxinal do Soturno. O material integra a produção acadêmica de Primavesi na UFSM e está preservado pelo Acervo Ana Maria Primavesi, que disponibiliza o documento em PDF.', 'fertilidade do solo, solo, matéria orgânica, nutrientes, Rio Grande do Sul, agricultura', 'https://anamariaprimavesi.com.br/2019/07/23/estudo-de-fertilidade-de-solos-do-municipio-de-faxinal-do-soturno-rs/', 4, 5, 'Não especificado', 3, 'Diversos', 2, 2, 3, 'Aprovada', 'ok', '2026-08-26 22:58:28', '2026-08-26 23:11:44'),
(10, 'Agroecologia e manejo do solo', 'Artigo que apresenta e compara diferentes formas de manejo do solo agrícola, destacando os princípios do manejo agroecológico e a importância da manutenção da vida e da estrutura do solo.', 'Ana Maria Primavesi apresenta três formas principais de manejo do solo agrícola: convencional, orgânico por substituição de insumos e agroecológico. A autora critica práticas que aceleram a decomposição da matéria orgânica e prejudicam a atividade biológica do solo. Em contraposição, apresenta o manejo agroecológico como uma abordagem baseada nas características do ambiente local, na conservação da estrutura e da vida do solo e na redução de intervenções que prejudiquem seu equilíbrio natural.', 'agroecologia; manejo do solo; solo vivo; matéria orgânica; agricultura tropical; manejo agroecológico', 'https://transforma.fbb.org.br/storage/socialtecnologies/252/files/Agroecologia%20e%20Manejo%20do%20Solo_Ana%20Primavesi.pdf', 4, 6, 'Diversos', 2, 'Diversos', 2, 2, 3, 'Aprovada', 'ok', '2026-08-26 23:02:11', '2026-08-26 23:11:11'),
(11, 'Cartilha do Solo: Como reconhecer e sanar seus problemas', 'Material técnico de Ana Primavesi voltado à identificação de problemas relacionados à saúde do solo e às práticas agroecológicas utilizadas para seu manejo.', 'A publicação apresenta métodos práticos para observar e interpretar as condições do solo tropical. São abordados temas como estrutura e agregação do solo, matéria orgânica, proteção superficial, atividade biológica, desenvolvimento das raízes, nutrição vegetal e relação entre desequilíbrios do solo e ocorrência de pragas e doenças. O material enfatiza a compreensão do solo como um sistema vivo e a utilização de práticas adaptadas às condições tropicais.', 'solo tropical; saúde do solo; agroecologia; matéria orgânica; raízes; nutrição vegetal; pragas; agricultura tropical', 'https://www.udesc.br/arquivos/cav/documentos/Cartilha_do_Solo_Como_reconhecer_e_sanar_seus_problemas_16670734552389_3251.pdf', 4, 6, 'Diversos', 2, 'Diversos', 2, 2, 3, 'Aprovada', 'ok', '2026-08-26 23:06:03', '2026-08-26 23:10:52'),
(12, 'Meio ambiente – solo – nutrição de plantas e sustentabilidade agrícola', 'Material técnico de Ana Primavesi voltado à identificação de problemas relacionados à saúde do solo e às práticas agroecológicas utilizadas para seu manejo.', 'A publicação apresenta métodos práticos para observar e interpretar as condições do solo tropical. São abordados temas como estrutura e agregação do solo, matéria orgânica, proteção superficial, atividade biológica, desenvolvimento das raízes, nutrição vegetal e relação entre desequilíbrios do solo e ocorrência de pragas e doenças. O material enfatiza a compreensão do solo como um sistema vivo e a utilização de práticas adaptadas às condições tropicais.', 'solo tropical; saúde do solo; agroecologia; matéria orgânica; raízes; nutrição vegetal; pragas; agricultura tropical', 'https://anamariaprimavesi.com.br/2019/06/30/meio-ambiente-solo-nutricao-de-plantas-e-sustentabilidade-agricola/', 4, 2, 'Diversos', 2, 'Diversos', 2, 2, 3, 'Aprovada', 'ok', '2026-08-26 23:09:19', '2026-08-26 23:10:14'),
(13, 'Applying Agroecology to Enhance the Productivity of Peasant Farming Systems in Latin America', 'Artigo que analisa a aplicação de princípios agroecológicos para aumentar a produtividade e a sustentabilidade de sistemas agrícolas camponeses na América Latina.', 'O trabalho analisa sistemas produtivos de pequenos agricultores latino-americanos e sua importância para a segurança alimentar regional. Altieri discute como sistemas tradicionais, mesmo utilizando poucos insumos externos, podem apresentar produtividade relevante. O artigo apresenta experiências agroecológicas voltadas à reorganização biológica das propriedades, destacando processos como ciclagem de nutrientes, acúmulo de matéria orgânica e regulação biológica de pragas. A abordagem procura aumentar a produtividade utilizando recursos locais e conhecimentos tradicionais associados aos princípios científicos da agroecologia.', 'agroecologia; agricultura camponesa; agricultura familiar; produtividade; matéria orgânica; ciclagem de nutrientes; América Latina', 'https://www.agroeco.org/doc/LApeasantdev.pdf', 6, 6, 'Diversos', 2, 'Diversos', 2, 5, 3, 'Aprovada', 'ok', '2026-08-26 23:20:45', '2026-08-26 23:26:46'),
(14, 'Agroecology: Principles and Strategies for Designing Sustainable Farming Systems', 'Trabalho que apresenta princípios e estratégias agroecológicas para o planejamento de sistemas agrícolas sustentáveis.', 'Altieri discute a agricultura sustentável como resposta à degradação da base de recursos naturais associada à agricultura moderna. O trabalho apresenta a produção agrícola como um sistema que não pode ser analisado apenas por aspectos técnicos, pois envolve também dimensões ambientais, sociais, culturais, políticas e econômicas. A agroecologia é utilizada como base para compreender essas interações e orientar o desenvolvimento de agroecossistemas capazes de conciliar produção, conservação dos recursos e sustentabilidade.', 'agroecologia; agricultura sustentável; agroecossistemas; biodiversidade; manejo ecológico; sustentabilidade', 'https://www.agroeco.org/doc/new_docs/Agroeco_principles.pdf', 6, 6, 'Diversos', 2, 'Diversos', 2, 5, 3, 'Aprovada', 'ok', '2026-08-26 23:22:11', '2026-08-26 23:26:31'),
(15, 'Agroecologically Efficient Agricultural Systems for Smallholder Farmers: Contributions to Food Sovereignty', 'Artigo científico que analisa a contribuição de sistemas agrícolas agroecologicamente eficientes para pequenos agricultores e para a soberania alimentar.', 'O artigo discute a importância da agricultura camponesa diante de mudanças climáticas e crises econômicas e energéticas. Os autores analisam sistemas produtivos que combinam conhecimentos científicos modernos com conhecimentos tradicionais, demonstrando sua contribuição para segurança alimentar, conservação da agrobiodiversidade e preservação dos recursos de solo e água. O trabalho também apresenta experiências em diferentes países, incluindo uma seção dedicada ao Brasil, além de Cuba, Filipinas e experiências africanas.', 'agroecologia; agricultura familiar; soberania alimentar; agrobiodiversidade; pequenos agricultores; resiliência; segurança alimentar', 'https://agroeco.org/wp-content/uploads/2009/11/Altieri-Funes-Petersen-Palencia.pdf', 6, 6, 'Diversos', 2, 'Diversos', 2, 5, 3, 'Aprovada', 'ok', '2026-08-26 23:25:27', '2026-08-26 23:26:11');

-- --------------------------------------------------------

--
-- Estrutura da tabela `regioes`
--

CREATE TABLE `regioes` (
  `id` int(11) NOT NULL,
  `nome` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `regioes`
--

INSERT INTO `regioes` (`id`, `nome`) VALUES
(1, 'Norte'),
(2, 'Nordeste'),
(3, 'Centro-Oeste'),
(4, 'Sudeste'),
(5, 'Sul'),
(6, 'Não se aplica');

-- --------------------------------------------------------

--
-- Estrutura da tabela `tipos_cultivo`
--

CREATE TABLE `tipos_cultivo` (
  `id` int(11) NOT NULL,
  `nome` varchar(80) NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `tipos_cultivo`
--

INSERT INTO `tipos_cultivo` (`id`, `nome`, `descricao`) VALUES
(1, 'Milho', NULL),
(2, 'Diversos', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `tipos_solo`
--

CREATE TABLE `tipos_solo` (
  `id` int(11) NOT NULL,
  `nome` varchar(80) NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `tipos_solo`
--

INSERT INTO `tipos_solo` (`id`, `nome`, `descricao`) VALUES
(1, 'Latossolo', NULL),
(2, 'Diversos', NULL),
(3, 'Não especificado', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('usuario','pesquisador','administrador') NOT NULL,
  `instituicao` varchar(150) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `dataCadastro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo`, `instituicao`, `foto_perfil`, `ativo`, `dataCadastro`) VALUES
(1, 'Teste', 'teste@teste.com', '$2y$10$vpdafNaZbUNhYQpzMBhgpes/zMtDgkTpd12ktJ6Er4.hL/pyNPbS2', 'usuario', '', NULL, 1, '2026-08-12 20:57:42'),
(2, 'teste pesquisador', 'teste@pesquisador.com', '$2y$10$hbfYygWo6sHO2iPs6w8uk.Y17EWQhFLwq9xt03d55T2fedvpsOlV2', 'pesquisador', 'Unesp', '../images/perfis/usuario_2_1787441249.jpg', 1, '2026-08-17 22:04:53'),
(3, 'Administrador', 'admin@acervo.com', '$2y$10$KClnKIwdEiv9xs/kESSUl.pzPL9uTETcX3KH5I6geDqVQYswVZZEa', 'administrador', 'Acervo Agroecológico', NULL, 1, '2026-08-17 22:10:10'),
(4, 'teste 2', 'teste2@teste.com', '$2y$10$31CUrSVWnnbzdtynKjt68.qsmH1aoQL8YMBgxw/ax.5jgGi3aA3Ra', 'usuario', '', NULL, 1, '2026-08-19 19:46:31'),
(5, 'Conta pesquisador', 'pesquisador@gmail.com', '$2y$10$92tD9mKuce1keMuI68Y9be6lZ4InvyPveoN/SG6fvI0TQ4v.TyYXi', 'pesquisador', '', NULL, 1, '2026-08-26 23:18:40');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `autores`
--
ALTER TABLE `autores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_autores_usuario` (`usuario_id`);

--
-- Índices para tabela `avaliacoes_pesquisas`
--
ALTER TABLE `avaliacoes_pesquisas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_pesquisa` (`usuario_id`,`pesquisa_id`),
  ADD KEY `fk_avaliacao_pesquisa` (`pesquisa_id`);

--
-- Índices para tabela `avaliacoes_site`
--
ALTER TABLE `avaliacoes_site`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_avaliacao_site` (`usuario_id`);

--
-- Índices para tabela `pesquisas`
--
ALTER TABLE `pesquisas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pesquisa_autor` (`autor_id`),
  ADD KEY `fk_pesquisa_regiao` (`regiao_id`),
  ADD KEY `fk_pesquisa_solo` (`solo_id`),
  ADD KEY `fk_pesquisa_cultivo` (`cultivo_id`),
  ADD KEY `fk_pesquisa_pesquisador` (`pesquisador_id`),
  ADD KEY `fk_pesquisa_administrador` (`administrador_id`);

--
-- Índices para tabela `regioes`
--
ALTER TABLE `regioes`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `tipos_cultivo`
--
ALTER TABLE `tipos_cultivo`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `tipos_solo`
--
ALTER TABLE `tipos_solo`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `autores`
--
ALTER TABLE `autores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `avaliacoes_pesquisas`
--
ALTER TABLE `avaliacoes_pesquisas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `avaliacoes_site`
--
ALTER TABLE `avaliacoes_site`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pesquisas`
--
ALTER TABLE `pesquisas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `regioes`
--
ALTER TABLE `regioes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `tipos_cultivo`
--
ALTER TABLE `tipos_cultivo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `tipos_solo`
--
ALTER TABLE `tipos_solo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `autores`
--
ALTER TABLE `autores`
  ADD CONSTRAINT `fk_autores_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `avaliacoes_pesquisas`
--
ALTER TABLE `avaliacoes_pesquisas`
  ADD CONSTRAINT `fk_avaliacao_pesquisa` FOREIGN KEY (`pesquisa_id`) REFERENCES `pesquisas` (`id`),
  ADD CONSTRAINT `fk_avaliacao_pesquisa_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `avaliacoes_site`
--
ALTER TABLE `avaliacoes_site`
  ADD CONSTRAINT `fk_avaliacao_site_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `pesquisas`
--
ALTER TABLE `pesquisas`
  ADD CONSTRAINT `fk_pesquisa_administrador` FOREIGN KEY (`administrador_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_pesquisa_autor` FOREIGN KEY (`autor_id`) REFERENCES `autores` (`id`),
  ADD CONSTRAINT `fk_pesquisa_cultivo` FOREIGN KEY (`cultivo_id`) REFERENCES `tipos_cultivo` (`id`),
  ADD CONSTRAINT `fk_pesquisa_pesquisador` FOREIGN KEY (`pesquisador_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_pesquisa_regiao` FOREIGN KEY (`regiao_id`) REFERENCES `regioes` (`id`),
  ADD CONSTRAINT `fk_pesquisa_solo` FOREIGN KEY (`solo_id`) REFERENCES `tipos_solo` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
