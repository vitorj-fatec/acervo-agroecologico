Acervo Agroecológico

Sistema web desenvolvido como projeto acadêmico de TCC com o objetivo de facilitar o acesso a pesquisas científicas relacionadas à agroecologia e ao manejo do solo.

A plataforma organiza conteúdos científicos de forma centralizada e permite consultar pesquisas por diferentes critérios, além de possuir áreas específicas para usuários, pesquisadores e administradores.

🌱 Acesso ao sistema

Site online:

https://tcc-agroecologia.infinityfree.me

📌 Principais funcionalidades

Usuário

Cadastro e login.

Consulta de pesquisas aprovadas.

Busca por título e palavras-chave.

Filtros por região, tipo de solo e cultivo.

Consulta de autores.

Consulta de pesquisas por região.

Visualização dos detalhes de uma pesquisa.

Avaliação de pesquisas.

Avaliação da plataforma.

Edição de informações do próprio perfil.

Pesquisador

Todas as funcionalidades disponíveis ao usuário.

Área exclusiva do pesquisador.

Envio de pesquisas para análise.

Acompanhamento das submissões.

Consulta do status das pesquisas enviadas.

Edição de pesquisas quando permitido.

Gerenciamento do perfil público de autor.

Administrador

Painel administrativo.

Consulta e gerenciamento de pesquisas.

Aprovação ou rejeição de pesquisas submetidas.

Edição e exclusão de pesquisas.

Gerenciamento de usuários.

Gerenciamento de autores.

Consulta das avaliações realizadas sobre a plataforma.

🔄 Fluxo de publicação de pesquisas

O pesquisador envia uma pesquisa.

A pesquisa é registrada com status Pendente.

O administrador analisa a submissão.

A pesquisa pode ser Aprovada ou Rejeitada.

Somente pesquisas aprovadas ficam disponíveis para consulta pública no acervo.

🛠️ Tecnologias utilizadas

PHP

MySQL

HTML5

CSS3

XAMPP para desenvolvimento local

InfinityFree para hospedagem

Git e GitHub para versionamento

🗂️ Estrutura principal do projeto

tcc-agroecologia/
├── admin/
├── css/
├── images/
├── includes/
├── js/
├── pages/
├── pesquisador/
├── cadastro.php
├── index.php
├── login.php
└── logout.php

💾 Banco de dados

O sistema utiliza MySQL para armazenar informações como:

usuários;

autores;

pesquisas;

regiões;

avaliações de pesquisas;

avaliações da plataforma.

O banco utilizado no ambiente online está hospedado no InfinityFree.

⚙️ Execução local

Para executar o projeto localmente:

Instale o XAMPP.

Coloque a pasta do projeto dentro de:

C:\xampp\htdocs\

Inicie o Apache e o MySQL.

Importe o arquivo SQL do projeto pelo phpMyAdmin.

Configure o arquivo:

includes/conexao.php

com os dados do banco local.
6. Acesse o projeto pelo navegador usando o endereço correspondente à pasta dentro do htdocs.

🔐 Segurança

As credenciais reais do banco de dados utilizado em produção não devem ser armazenadas neste repositório.

O arquivo de conexão publicado no GitHub deve permanecer sem senhas reais de produção.

🎓 Projeto acadêmico

Este sistema foi desenvolvido como parte de um Trabalho de Conclusão de Curso, com foco na organização e consulta de pesquisas científicas voltadas à agroecologia.

O projeto busca reduzir a dispersão de informações e facilitar o acesso a conteúdos científicos confiáveis por agricultores e demais interessados no tema.

👤 Autor

Vitor José Guedes Goes

Projeto desenvolvido para fins acadêmicos.

© 2026 Acervo Agroecológico.
