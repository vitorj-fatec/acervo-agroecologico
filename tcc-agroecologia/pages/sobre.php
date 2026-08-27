<?php

require_once "../includes/verificacao.php";

$cssPagina = "sobre.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-sobre">

    <section class="conteudo-sobre">

        <div class="cabecalho-sobre">

            <h1>Sobre o Projeto</h1>

            <p>
                Conheça a proposta e os objetivos do
                Acervo Agroecológico.
            </p>

        </div>


        <section class="bloco-sobre destaque-sobre">

            <h2>O que é o Acervo Agroecológico?</h2>

            <p>
                O Acervo Agroecológico é uma plataforma web
                desenvolvida para facilitar o acesso a pesquisas
                científicas relacionadas à agroecologia,
                agricultura e manejo do solo.
            </p>

            <p>
                A proposta é reunir informações que normalmente
                estão distribuídas em diferentes fontes e
                apresentá-las de maneira organizada, simples
                e acessível.
            </p>

        </section>


        <div class="grade-sobre">

            <section class="bloco-sobre">

                <h2>Problema</h2>

                <p>
                    Muitas pesquisas científicas relacionadas
                    ao manejo do solo estão disponíveis em
                    diferentes plataformas, instituições e
                    publicações.
                </p>

                <p>
                    Essa dispersão pode dificultar a localização
                    e a interpretação das informações por
                    agricultores e outros usuários interessados.
                </p>

            </section>


            <section class="bloco-sobre">

                <h2>Objetivo</h2>

                <p>
                    O objetivo do sistema é organizar pesquisas
                    científicas em um único ambiente digital,
                    permitindo que o usuário encontre conteúdos
                    de acordo com características como região,
                    tipo de solo, cultivo e autor.
                </p>

            </section>


            <section class="bloco-sobre">

                <h2>Público-alvo</h2>

                <p>
                    O sistema foi pensado principalmente para
                    agricultores de pequeno e médio porte,
                    agricultores familiares, pesquisadores
                    e pessoas interessadas em informações
                    relacionadas à agroecologia.
                </p>

            </section>


            <section class="bloco-sobre">

                <h2>Como funciona?</h2>

                <p>
                    Os usuários podem consultar pesquisas
                    aprovadas, realizar buscas, utilizar filtros,
                    conhecer autores e explorar conteúdos
                    relacionados às regiões brasileiras.
                </p>

                <p>
                    Pesquisadores cadastrados também podem
                    enviar pesquisas para análise.
                </p>

            </section>

        </div>


        <section class="bloco-sobre fluxo-sobre">

            <h2>Processo de publicação</h2>

            <div class="etapas-sobre">

                <div class="etapa-sobre">

                    <span>1</span>

                    <h3>Envio</h3>

                    <p>
                        O pesquisador cadastrado envia uma
                        pesquisa por meio da plataforma.
                    </p>

                </div>


                <div class="etapa-sobre">

                    <span>2</span>

                    <h3>Análise</h3>

                    <p>
                        A pesquisa permanece pendente até
                        ser analisada por um administrador.
                    </p>

                </div>


                <div class="etapa-sobre">

                    <span>3</span>

                    <h3>Publicação</h3>

                    <p>
                        Quando aprovada, a pesquisa passa
                        a ficar disponível no acervo.
                    </p>

                </div>

            </div>

        </section>


        <section class="bloco-sobre">

            <h2>Recursos disponíveis</h2>

            <div class="recursos-sobre">

                <div>
                    <strong>Busca por palavras-chave</strong>
                    <p>
                        Permite localizar pesquisas por termos
                        relacionados ao conteúdo.
                    </p>
                </div>

                <div>
                    <strong>Filtros</strong>
                    <p>
                        As pesquisas podem ser filtradas por
                        região, solo, cultivo e autor.
                    </p>
                </div>

                <div>
                    <strong>Avaliações</strong>
                    <p>
                        Usuários podem avaliar as pesquisas
                        disponíveis de uma a cinco estrelas.
                    </p>
                </div>

                <div>
                    <strong>Autores</strong>
                    <p>
                        O sistema organiza autores e permite
                        consultar suas pesquisas relacionadas.
                    </p>
                </div>

            </div>

        </section>


        <section class="bloco-sobre desenvolvimento-sobre">

            <h2>Desenvolvimento</h2>

            <p>
                O Acervo Agroecológico está sendo desenvolvido
                como projeto acadêmico utilizando tecnologias
                web como HTML, CSS, PHP e MySQL.
            </p>

            <p>
                O sistema utiliza autenticação de usuários,
                controle de perfis, banco de dados relacional
                e mecanismos de validação e segurança para
                controlar o acesso às diferentes funcionalidades.
            </p>

        </section>


        <div class="acao-sobre">

            <a
                href="pesquisas.php"
                class="botao-sobre"
            >
                Explorar pesquisas
            </a>

        </div>

    </section>

</main>

<?php

require_once "../includes/footer.php";

?>