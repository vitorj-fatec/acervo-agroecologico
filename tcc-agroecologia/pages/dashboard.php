<?php

require_once "../includes/verificacao.php";
require_once "../includes/conexao.php";

$cssPagina = "dashboard.css";


/*
 * Contadores do acervo.
 */
$sqlPesquisas = "
    SELECT COUNT(*) AS total
    FROM pesquisas
    WHERE status = 'Aprovada'
";

$resultadoPesquisas = $conn->query($sqlPesquisas);

$totalPesquisas =
    (int)$resultadoPesquisas
        ->fetch_assoc()["total"];


/*
 * Conta autores que possuem
 * pelo menos uma pesquisa aprovada.
 */
$sqlAutores = "
    SELECT COUNT(DISTINCT autor_id) AS total
    FROM pesquisas
    WHERE status = 'Aprovada'
";

$resultadoAutores = $conn->query($sqlAutores);

$totalAutores =
    (int)$resultadoAutores
        ->fetch_assoc()["total"];


/*
 * Conta somente as regiões brasileiras
 * que possuem pelo menos uma pesquisa aprovada.
 *
 * A classificação "Não se aplica" não entra
 * nesse contador porque não representa
 * uma região geográfica brasileira.
 */
$sqlRegioes = "
    SELECT COUNT(DISTINCT p.regiao_id) AS total

    FROM pesquisas p

    INNER JOIN regioes r
        ON p.regiao_id = r.id

    WHERE p.status = 'Aprovada'
    AND r.nome <> 'Não se aplica'
";

$resultadoRegioes = $conn->query($sqlRegioes);

$totalRegioes =
    (int)$resultadoRegioes
        ->fetch_assoc()["total"];


require_once "../includes/header.php";
require_once "../includes/menu.php";


$mensagemErro = "";

if (
    isset($_GET["erro"]) &&
    $_GET["erro"] === "sem_permissao"
) {

    $mensagemErro =
        "Você não possui permissão para acessar essa área.";
}

?>

<main class="dashboard">

    <?php if (!empty($mensagemErro)) : ?>

        <div class="mensagem-permissao">

            <?php
            echo htmlspecialchars(
                $mensagemErro
            );
            ?>

        </div>

    <?php endif; ?>


    <section class="apresentacao">

        <h1>
            Bem-vindo ao Acervo Agroecológico
        </h1>

        <p>
            O Acervo Agroecológico foi desenvolvido
            com o objetivo de facilitar o acesso a
            pesquisas científicas relacionadas à
            agroecologia e ao manejo do solo.
        </p>

        <p>
            A plataforma organiza pesquisas de forma
            simples e acessível, permitindo consultar
            conteúdos por região, tipo de solo,
            cultivo e autor.
        </p>

    </section>


    <section class="como-funciona">

        <h2>Como funciona?</h2>

        <div class="cards-dashboard cards-home">

            <article class="card-dashboard">

                <h3>Pesquisas</h3>

                <p>
                    Consulte pesquisas científicas
                    relacionadas à agricultura,
                    agroecologia, manejo do solo
                    e diferentes cultivos.
                </p>

                <a href="pesquisas.php">
                    Acessar pesquisas
                </a>

            </article>


            <article class="card-dashboard">

                <h3>Autores</h3>

                <p>
                    Conheça autores e pesquisadores
                    presentes no acervo e encontre
                    os trabalhos relacionados
                    a cada um.
                </p>

                <a href="autores.php">
                    Conhecer autores
                </a>

            </article>


            <article class="card-dashboard">

                <h3>Regiões</h3>

                <p>
                    Explore pesquisas relacionadas
                    às diferentes regiões presentes
                    no sistema.
                </p>

                <a href="regiao.php">
                    Explorar regiões
                </a>

            </article>


            <article class="card-dashboard">

                <h3>Sobre o Projeto</h3>

                <p>
                    Conheça a proposta, os objetivos
                    e o funcionamento do
                    Acervo Agroecológico.
                </p>

                <a href="sobre.php">
                    Sobre o projeto
                </a>

            </article>


            <article class="card-dashboard">

                <h3>Avaliar Plataforma</h3>

                <p>
                    Compartilhe sua experiência de uso
                    e contribua para a melhoria contínua
                    do Acervo Agroecológico.
                </p>

                <a href="avaliarSite.php">
                    Avaliar plataforma
                </a>

            </article>

        </div>

    </section>


    <section class="numeros-acervo">

        <h2>Nosso acervo</h2>

        <p class="descricao-numeros">
            Conheça alguns dados atuais
            disponíveis na plataforma.
        </p>


        <div class="cards-numeros-acervo">

            <article class="card-numero-acervo">

                <span class="numero-acervo">
                    <?php echo $totalPesquisas; ?>
                </span>

                <h3>
                    Pesquisas aprovadas
                </h3>

            </article>


            <article class="card-numero-acervo">

                <span class="numero-acervo">
                    <?php echo $totalAutores; ?>
                </span>

                <h3>
                    Autores no acervo
                </h3>

            </article>


            <article class="card-numero-acervo">

                <span class="numero-acervo">
                    <?php echo $totalRegioes; ?>
                </span>

                <h3>
                    Regiões com pesquisas
                </h3>

            </article>

        </div>

    </section>


    <section class="sobre-projeto">

        <h2>
            Conhecimento científico mais acessível
        </h2>

        <p>
            A proposta do Acervo Agroecológico
            é aproximar o conhecimento científico
            de agricultores, pesquisadores e demais
            usuários interessados em informações
            confiáveis sobre agricultura,
            agroecologia e manejo do solo.
        </p>

        <a
            href="sobre.php"
            class="link-sobre-dashboard"
        >
            Conhecer o projeto
        </a>

    </section>

</main>

<?php

require_once "../includes/footer.php";

?>