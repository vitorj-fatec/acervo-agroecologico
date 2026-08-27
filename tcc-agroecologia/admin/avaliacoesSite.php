<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["administrador"]);


/*
 * =========================================================
 * RESUMO GERAL DAS AVALIAÇÕES
 * =========================================================
 */

$sqlResumo = "
    SELECT
        COUNT(*) AS total,
        AVG(nota) AS mediaNota,
        AVG(facilidadeNavegacao) AS mediaNavegacao,
        AVG(facilidadeBusca) AS mediaBusca,
        AVG(clarezaInformacoes) AS mediaClareza

    FROM avaliacoes_site
";

$resultadoResumo =
    $conn->query($sqlResumo);

$resumo =
    $resultadoResumo->fetch_assoc();


$totalAvaliacoes =
    (int)$resumo["total"];


$mediaNota =
    $resumo["mediaNota"] !== null
        ? number_format(
            (float)$resumo["mediaNota"],
            1,
            ",",
            "."
        )
        : "0,0";


$mediaNavegacao =
    $resumo["mediaNavegacao"] !== null
        ? number_format(
            (float)$resumo["mediaNavegacao"],
            1,
            ",",
            "."
        )
        : "0,0";


$mediaBusca =
    $resumo["mediaBusca"] !== null
        ? number_format(
            (float)$resumo["mediaBusca"],
            1,
            ",",
            "."
        )
        : "0,0";


$mediaClareza =
    $resumo["mediaClareza"] !== null
        ? number_format(
            (float)$resumo["mediaClareza"],
            1,
            ",",
            "."
        )
        : "0,0";


/*
 * =========================================================
 * PAGINAÇÃO
 * =========================================================
 */

$itensPorPagina = 10;

$paginaAtual =
    filter_input(
        INPUT_GET,
        "pagina",
        FILTER_VALIDATE_INT
    );


if (
    $paginaAtual === false ||
    $paginaAtual === null ||
    $paginaAtual < 1
) {

    $paginaAtual = 1;
}


/*
 * O total utilizado na paginação
 * é o mesmo total geral de avaliações.
 */
$totalPaginas =
    max(
        1,
        (int)ceil(
            $totalAvaliacoes /
            $itensPorPagina
        )
    );


/*
 * Impede acesso a uma página
 * que não existe.
 */
if (
    $paginaAtual >
    $totalPaginas
) {

    $paginaAtual =
        $totalPaginas;
}


/*
 * Define a posição inicial
 * da consulta.
 */
$offset =
    ($paginaAtual - 1) *
    $itensPorPagina;


/*
 * =========================================================
 * LISTA INDIVIDUAL DAS AVALIAÇÕES
 * =========================================================
 */

$sqlAvaliacoes = "
    SELECT
        av.id,
        av.nota,
        av.facilidadeNavegacao,
        av.facilidadeBusca,
        av.clarezaInformacoes,
        av.comentario,
        av.dataAvaliacao,

        u.nome AS usuario,
        u.email AS email

    FROM avaliacoes_site av

    INNER JOIN usuarios u
        ON av.usuario_id = u.id

    ORDER BY av.dataAvaliacao DESC

    LIMIT ?
    OFFSET ?
";

$stmtAvaliacoes =
    $conn->prepare(
        $sqlAvaliacoes
    );

$stmtAvaliacoes->bind_param(
    "ii",
    $itensPorPagina,
    $offset
);

$stmtAvaliacoes->execute();

$resultadoAvaliacoes =
    $stmtAvaliacoes->get_result();


$cssPagina =
    "avaliacoesSite.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-avaliacoes-site">

    <section class="conteudo-avaliacoes-site">


        <!-- =================================================
             CABEÇALHO
             ================================================= -->

        <div class="cabecalho-avaliacoes-site">

            <h1>
                Avaliações da plataforma
            </h1>

            <p>
                Consulte os resultados das avaliações
                realizadas pelos usuários do
                Acervo Agroecológico.
            </p>

        </div>


        <!-- =================================================
             VISÃO GERAL
             ================================================= -->

        <section class="resumo-avaliacoes-site">

            <h2>
                Visão geral
            </h2>


            <div class="cards-resumo-avaliacoes">


                <!-- TOTAL -->

                <article class="card-resumo-avaliacao">

                    <span class="valor-avaliacao">
                        <?php
                        echo $totalAvaliacoes;
                        ?>
                    </span>

                    <h3>
                        Avaliações recebidas
                    </h3>

                </article>


                <!-- NOTA GERAL -->

                <article class="card-resumo-avaliacao">

                    <span class="valor-avaliacao">
                        <?php
                        echo $mediaNota;
                        ?>
                    </span>

                    <h3>
                        Nota geral
                    </h3>

                    <span class="escala-avaliacao">
                        de 5
                    </span>

                </article>


                <!-- NAVEGAÇÃO -->

                <article class="card-resumo-avaliacao">

                    <span class="valor-avaliacao">
                        <?php
                        echo $mediaNavegacao;
                        ?>
                    </span>

                    <h3>
                        Navegação
                    </h3>

                    <span class="escala-avaliacao">
                        de 5
                    </span>

                </article>


                <!-- BUSCA -->

                <article class="card-resumo-avaliacao">

                    <span class="valor-avaliacao">
                        <?php
                        echo $mediaBusca;
                        ?>
                    </span>

                    <h3>
                        Busca
                    </h3>

                    <span class="escala-avaliacao">
                        de 5
                    </span>

                </article>


                <!-- CLAREZA -->

                <article class="card-resumo-avaliacao">

                    <span class="valor-avaliacao">
                        <?php
                        echo $mediaClareza;
                        ?>
                    </span>

                    <h3>
                        Clareza das informações
                    </h3>

                    <span class="escala-avaliacao">
                        de 5
                    </span>

                </article>


            </div>

        </section>


        <!-- =================================================
             AVALIAÇÕES INDIVIDUAIS
             ================================================= -->

        <section class="lista-avaliacoes-site">

            <h2>
                Avaliações individuais
            </h2>


            <?php
            if (
                $resultadoAvaliacoes
                    ->num_rows === 0
            ) :
            ?>

                <div class="sem-avaliacoes-site">

                    <p>
                        Ainda não existem avaliações
                        cadastradas para a plataforma.
                    </p>

                </div>


            <?php else : ?>


                <div class="cards-avaliacoes-site">

                    <?php
                    while (
                        $avaliacao =
                        $resultadoAvaliacoes
                            ->fetch_assoc()
                    ) :
                    ?>

                        <article class="card-avaliacao-site">


                            <!-- TOPO -->

                            <div class="topo-avaliacao-site">

                                <div>

                                    <h3>

                                        <?php
                                        echo htmlspecialchars(
                                            $avaliacao[
                                                "usuario"
                                            ]
                                        );
                                        ?>

                                    </h3>


                                    <p>

                                        <?php
                                        echo htmlspecialchars(
                                            $avaliacao[
                                                "email"
                                            ]
                                        );
                                        ?>

                                    </p>

                                </div>


                                <span class="data-avaliacao-site">

                                    <?php
                                    echo date(
                                        "d/m/Y H:i",
                                        strtotime(
                                            $avaliacao[
                                                "dataAvaliacao"
                                            ]
                                        )
                                    );
                                    ?>

                                </span>

                            </div>


                            <!-- NOTAS -->

                            <div class="notas-avaliacao-site">


                                <div>

                                    <strong>
                                        Nota geral:
                                    </strong>

                                    <?php
                                    echo (int)$avaliacao[
                                        "nota"
                                    ];
                                    ?>/5

                                </div>


                                <div>

                                    <strong>
                                        Navegação:
                                    </strong>

                                    <?php
                                    echo (int)$avaliacao[
                                        "facilidadeNavegacao"
                                    ];
                                    ?>/5

                                </div>


                                <div>

                                    <strong>
                                        Busca:
                                    </strong>

                                    <?php
                                    echo (int)$avaliacao[
                                        "facilidadeBusca"
                                    ];
                                    ?>/5

                                </div>


                                <div>

                                    <strong>
                                        Clareza:
                                    </strong>

                                    <?php
                                    echo (int)$avaliacao[
                                        "clarezaInformacoes"
                                    ];
                                    ?>/5

                                </div>


                            </div>


                            <!-- COMENTÁRIO -->

                            <?php
                            if (
                                !empty(
                                    $avaliacao[
                                        "comentario"
                                    ]
                                )
                            ) :
                            ?>

                                <div class="comentario-avaliacao-site">

                                    <strong>
                                        Comentário
                                    </strong>

                                    <p>

                                        <?php
                                        echo nl2br(
                                            htmlspecialchars(
                                                $avaliacao[
                                                    "comentario"
                                                ]
                                            )
                                        );
                                        ?>

                                    </p>

                                </div>

                            <?php endif; ?>


                        </article>

                    <?php endwhile; ?>

                </div>


                <!-- =========================================
                     PAGINAÇÃO
                     ========================================= -->

                <?php
                if (
                    $totalPaginas > 1
                ) :
                ?>

                    <nav
                        class="paginacao"
                        aria-label="Paginação das avaliações"
                    >


                        <!-- ANTERIOR -->

                        <?php
                        if (
                            $paginaAtual > 1
                        ) :
                        ?>

                            <a
                                class="pagina-link"
                                href="?pagina=<?php
                                echo $paginaAtual - 1;
                                ?>"
                            >
                                ← Anterior
                            </a>

                        <?php endif; ?>


                        <!-- PÁGINAS -->

                        <?php
                        for (
                            $pagina = 1;
                            $pagina <= $totalPaginas;
                            $pagina++
                        ) :
                        ?>


                            <?php
                            if (
                                $pagina ===
                                $paginaAtual
                            ) :
                            ?>

                                <span
                                    class="pagina-link pagina-atual"
                                >
                                    <?php
                                    echo $pagina;
                                    ?>
                                </span>

                            <?php else : ?>

                                <a
                                    class="pagina-link"
                                    href="?pagina=<?php
                                    echo $pagina;
                                    ?>"
                                >
                                    <?php
                                    echo $pagina;
                                    ?>
                                </a>

                            <?php endif; ?>


                        <?php endfor; ?>


                        <!-- PRÓXIMA -->

                        <?php
                        if (
                            $paginaAtual <
                            $totalPaginas
                        ) :
                        ?>

                            <a
                                class="pagina-link"
                                href="?pagina=<?php
                                echo $paginaAtual + 1;
                                ?>"
                            >
                                Próxima →
                            </a>

                        <?php endif; ?>


                    </nav>

                <?php endif; ?>


            <?php endif; ?>


        </section>

    </section>

</main>

<?php

$stmtAvaliacoes->close();

require_once "../includes/footer.php";

?>