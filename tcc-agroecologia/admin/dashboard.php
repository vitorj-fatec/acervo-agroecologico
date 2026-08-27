<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["administrador"]);


/*
 * =========================================================
 * CONTADORES DAS PESQUISAS
 * =========================================================
 */

$sqlPendentes = "
    SELECT COUNT(*) AS total
    FROM pesquisas
    WHERE status = 'Pendente'
";

$sqlAprovadas = "
    SELECT COUNT(*) AS total
    FROM pesquisas
    WHERE status = 'Aprovada'
";

$sqlRejeitadas = "
    SELECT COUNT(*) AS total
    FROM pesquisas
    WHERE status = 'Rejeitada'
";


$resultadoPendentes =
    $conn->query($sqlPendentes);

$resultadoAprovadas =
    $conn->query($sqlAprovadas);

$resultadoRejeitadas =
    $conn->query($sqlRejeitadas);


$totalPendentes =
    (int)$resultadoPendentes
        ->fetch_assoc()["total"];

$totalAprovadas =
    (int)$resultadoAprovadas
        ->fetch_assoc()["total"];

$totalRejeitadas =
    (int)$resultadoRejeitadas
        ->fetch_assoc()["total"];


/*
 * =========================================================
 * TOTAL DE USUÁRIOS COMUNS
 * =========================================================
 */

$sqlUsuarios = "
    SELECT COUNT(*) AS total
    FROM usuarios
    WHERE tipo = 'usuario'
";

$resultadoUsuarios =
    $conn->query($sqlUsuarios);

$totalUsuarios =
    (int)$resultadoUsuarios
        ->fetch_assoc()["total"];


/*
 * =========================================================
 * TOTAL DE PESQUISADORES
 * =========================================================
 */

$sqlPesquisadores = "
    SELECT COUNT(*) AS total
    FROM usuarios
    WHERE tipo = 'pesquisador'
";

$resultadoPesquisadores =
    $conn->query($sqlPesquisadores);

$totalPesquisadores =
    (int)$resultadoPesquisadores
        ->fetch_assoc()["total"];


/*
 * =========================================================
 * TOTAL DE AUTORES
 * =========================================================
 */

$sqlAutores = "
    SELECT COUNT(*) AS total
    FROM autores
";

$resultadoAutores =
    $conn->query($sqlAutores);

$totalAutores =
    (int)$resultadoAutores
        ->fetch_assoc()["total"];


/*
 * =========================================================
 * ÚLTIMAS PESQUISAS PENDENTES
 * =========================================================
 */

$sqlUltimasPendentes = "
    SELECT
        p.id,
        p.titulo,
        p.dataEnvio,
        u.nome AS pesquisador

    FROM pesquisas p

    INNER JOIN usuarios u
        ON p.pesquisador_id = u.id

    WHERE p.status = 'Pendente'

    ORDER BY p.dataEnvio DESC

    LIMIT 5
";

$resultadoUltimasPendentes =
    $conn->query($sqlUltimasPendentes);


/*
 * =========================================================
 * INTERFACE
 * =========================================================
 */

$cssPagina = "dashboard.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="dashboard dashboard-admin">


    <!-- =====================================================
         APRESENTAÇÃO
         ===================================================== -->

    <section class="apresentacao">

        <h1>
            Painel Administrativo
        </h1>

        <p>
            Bem-vindo,
            <strong>
                <?php
                echo htmlspecialchars(
                    $_SESSION["usuario_nome"]
                );
                ?>
            </strong>.
        </p>

        <p>
            Acompanhe as informações principais
            do Acervo Agroecológico e gerencie
            pesquisas, usuários, autores e avaliações.
        </p>

    </section>


    <!-- =====================================================
         VISÃO GERAL
         ===================================================== -->

    <section class="resumo-administrativo">

        <h2>
            Visão geral
        </h2>


        <div class="cards-resumo-admin">


            <!-- PENDENTES -->

            <article class="card-resumo-admin">

                <span class="numero-resumo">
                    <?php
                    echo $totalPendentes;
                    ?>
                </span>

                <h3>
                    Pesquisas pendentes
                </h3>

                <a href="pesquisas.php">
                    Analisar pesquisas
                </a>

            </article>


            <!-- APROVADAS -->

            <article class="card-resumo-admin">

                <span class="numero-resumo">
                    <?php
                    echo $totalAprovadas;
                    ?>
                </span>

                <h3>
                    Pesquisas aprovadas
                </h3>

            </article>


            <!-- REJEITADAS -->

            <article class="card-resumo-admin">

                <span class="numero-resumo">
                    <?php
                    echo $totalRejeitadas;
                    ?>
                </span>

                <h3>
                    Pesquisas rejeitadas
                </h3>

            </article>


            <!-- USUÁRIOS -->

            <article class="card-resumo-admin">

                <span class="numero-resumo">
                    <?php
                    echo $totalUsuarios;
                    ?>
                </span>

                <h3>
                    Usuários
                </h3>

            </article>


            <!-- PESQUISADORES -->

            <article class="card-resumo-admin">

                <span class="numero-resumo">
                    <?php
                    echo $totalPesquisadores;
                    ?>
                </span>

                <h3>
                    Pesquisadores
                </h3>

            </article>


            <!-- AUTORES -->

            <article class="card-resumo-admin">

                <span class="numero-resumo">
                    <?php
                    echo $totalAutores;
                    ?>
                </span>

                <h3>
                    Autores
                </h3>

            </article>


        </div>

    </section>


    <!-- =====================================================
         GERENCIAMENTO
         ===================================================== -->

    <section class="acoes-admin">

        <h2>
            Gerenciamento
        </h2>


        <div class="cards-dashboard">


            <!-- PESQUISAS -->

            <article class="card-dashboard">

                <h3>
                    Pesquisas
                </h3>

                <p>
                    Consulte as pesquisas enviadas
                    pelos pesquisadores e realize
                    a aprovação, rejeição e demais
                    operações administrativas.
                </p>

                <a href="pesquisas.php">
                    Gerenciar pesquisas
                </a>

            </article>


            <!-- USUÁRIOS -->

            <article class="card-dashboard">

                <h3>
                    Usuários
                </h3>

                <p>
                    Consulte e gerencie os usuários
                    cadastrados no Acervo Agroecológico.
                </p>

                <a href="usuarios.php">
                    Gerenciar usuários
                </a>

            </article>


            <!-- AUTORES -->

            <article class="card-dashboard">

                <h3>
                    Autores
                </h3>

                <p>
                    Consulte e edite os perfis públicos
                    dos autores cadastrados no acervo.
                </p>

                <a href="autores.php">
                    Gerenciar autores
                </a>

            </article>


            <!-- AVALIAÇÕES -->

            <article class="card-dashboard">

                <h3>
                    Avaliações da plataforma
                </h3>

                <p>
                    Consulte as avaliações realizadas
                    pelos usuários e acompanhe as médias
                    de experiência da plataforma.
                </p>

                <a href="avaliacoesSite.php">
                    Consultar avaliações
                </a>

            </article>


        </div>

    </section>


    <!-- =====================================================
         ÚLTIMAS PESQUISAS PENDENTES
         ===================================================== -->

    <section class="ultimas-pendentes">

        <h2>
            Últimas pesquisas pendentes
        </h2>


        <?php
        if (
            $resultadoUltimasPendentes
                ->num_rows === 0
        ) :
        ?>

            <div class="sem-pendencias">

                <p>
                    Não existem pesquisas aguardando
                    análise no momento.
                </p>

            </div>

        <?php else : ?>


            <div class="lista-pendencias-dashboard">


                <?php
                while (
                    $pesquisa =
                    $resultadoUltimasPendentes
                        ->fetch_assoc()
                ) :
                ?>

                    <article class="item-pendencia-dashboard">

                        <div>

                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $pesquisa["titulo"]
                                );
                                ?>
                            </h3>


                            <p>
                                Enviado por:

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $pesquisa[
                                            "pesquisador"
                                        ]
                                    );
                                    ?>
                                </strong>
                            </p>


                            <p>
                                <?php
                                echo date(
                                    "d/m/Y H:i",
                                    strtotime(
                                        $pesquisa[
                                            "dataEnvio"
                                        ]
                                    )
                                );
                                ?>
                            </p>

                        </div>


                        <a
                            href="analisarPesquisa.php?id=<?php
                            echo $pesquisa["id"];
                            ?>"
                            class="botao-analisar"
                        >
                            Analisar
                        </a>

                    </article>

                <?php endwhile; ?>


            </div>

        <?php endif; ?>

    </section>


</main>

<?php

require_once "../includes/footer.php";

?>