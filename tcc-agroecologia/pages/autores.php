<?php

require_once "../includes/verificacao.php";
require_once "../includes/conexao.php";


/*
 * =========================================================
 * BUSCA
 * =========================================================
 */

$busca =
    trim($_GET["busca"] ?? "");


/*
 * =========================================================
 * PAGINAÇÃO
 * =========================================================
 */

$itensPorPagina = 4;

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
 * =========================================================
 * TOTAL DE AUTORES
 *
 * São considerados somente autores que possuem
 * pelo menos uma pesquisa aprovada.
 *
 * A busca também é considerada antes da paginação.
 * =========================================================
 */

if ($busca !== "") {

    $sqlTotal = "
        SELECT COUNT(*) AS total

        FROM autores a

        WHERE a.nome LIKE ?

        AND EXISTS (

            SELECT 1

            FROM pesquisas p

            WHERE p.autor_id = a.id
            AND p.status = 'Aprovada'
        )
    ";

    $stmtTotal =
        $conn->prepare($sqlTotal);

    $termoBusca =
        "%" . $busca . "%";

    $stmtTotal->bind_param(
        "s",
        $termoBusca
    );

} else {

    $sqlTotal = "
        SELECT COUNT(*) AS total

        FROM autores a

        WHERE EXISTS (

            SELECT 1

            FROM pesquisas p

            WHERE p.autor_id = a.id
            AND p.status = 'Aprovada'
        )
    ";

    $stmtTotal =
        $conn->prepare($sqlTotal);
}


$stmtTotal->execute();

$resultadoTotal =
    $stmtTotal->get_result();

$totalAutores =
    (int)$resultadoTotal
        ->fetch_assoc()["total"];

$stmtTotal->close();


/*
 * =========================================================
 * TOTAL DE PÁGINAS
 * =========================================================
 */

$totalPaginas =
    max(
        1,
        (int)ceil(
            $totalAutores /
            $itensPorPagina
        )
    );


if (
    $paginaAtual >
    $totalPaginas
) {

    $paginaAtual =
        $totalPaginas;
}


$offset =
    ($paginaAtual - 1) *
    $itensPorPagina;


/*
 * =========================================================
 * CONSULTA DOS AUTORES
 *
 * Exibe publicamente somente autores com
 * pelo menos uma pesquisa aprovada.
 *
 * Se o autor estiver vinculado a uma conta
 * de pesquisador, a foto de perfil da conta
 * continua sendo utilizada como primeira opção.
 * =========================================================
 */

$sqlBase = "
    SELECT
        a.id,
        a.usuario_id,
        a.nome,
        a.biografia,
        a.instituicao,

        COALESCE(
            u.foto_perfil,
            a.foto
        ) AS foto_exibicao,

        COUNT(p.id)
            AS quantidade_pesquisas

    FROM autores a

    INNER JOIN pesquisas p
        ON p.autor_id = a.id
        AND p.status = 'Aprovada'

    LEFT JOIN usuarios u
        ON u.id = a.usuario_id
        AND u.tipo = 'pesquisador'
";


if ($busca !== "") {

    $sql = $sqlBase . "

        WHERE a.nome LIKE ?

        GROUP BY
            a.id,
            a.usuario_id,
            a.nome,
            a.biografia,
            a.instituicao,
            u.foto_perfil,
            a.foto

        ORDER BY a.nome

        LIMIT ?
        OFFSET ?
    ";

    $stmt =
        $conn->prepare($sql);

    $termoBusca =
        "%" . $busca . "%";

    $stmt->bind_param(
        "sii",
        $termoBusca,
        $itensPorPagina,
        $offset
    );

} else {

    $sql = $sqlBase . "

        GROUP BY
            a.id,
            a.usuario_id,
            a.nome,
            a.biografia,
            a.instituicao,
            u.foto_perfil,
            a.foto

        ORDER BY a.nome

        LIMIT ?
        OFFSET ?
    ";

    $stmt =
        $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $itensPorPagina,
        $offset
    );
}


$stmt->execute();

$resultado =
    $stmt->get_result();


$cssPagina = "autores.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-autores">

    <section class="conteudo-autores">


        <!-- =================================================
             CABEÇALHO
             ================================================= -->

        <div class="cabecalho-autores">

            <h1>
                Autores
            </h1>

            <p>
                Conheça os autores presentes no
                Acervo Agroecológico e consulte
                suas pesquisas disponíveis.
            </p>

        </div>


        <!-- =================================================
             BUSCA POR AUTOR
             ================================================= -->

        <div class="area-busca-autores">

            <form
                method="GET"
                class="form-busca-autores"
            >

                <label for="busca">
                    Buscar autor
                </label>


                <div class="linha-busca-autores">

                    <input
                        type="text"
                        name="busca"
                        id="busca"
                        value="<?php
                        echo htmlspecialchars(
                            $busca
                        );
                        ?>"
                        placeholder="Digite o nome do autor"
                    >


                    <button
                        type="submit"
                        class="botao-buscar-autor"
                    >
                        Buscar
                    </button>


                    <?php
                    if ($busca !== "") :
                    ?>

                        <a
                            href="autores.php"
                            class="botao-limpar-busca-autor"
                        >
                            Limpar
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </div>


        <!-- =================================================
             RESULTADOS
             ================================================= -->

        <?php
        if (
            $resultado->num_rows === 0
        ) :
        ?>

            <div class="sem-autores">

                <?php if ($busca !== "") : ?>

                    <h2>
                        Nenhum autor encontrado
                    </h2>

                    <p>
                        Não encontramos autores com
                        pesquisas aprovadas para
                        "<strong><?php
                        echo htmlspecialchars(
                            $busca
                        );
                        ?></strong>".
                    </p>

                <?php else : ?>

                    <h2>
                        Nenhum autor disponível
                    </h2>

                    <p>
                        Ainda não existem autores
                        com pesquisas aprovadas
                        no acervo.
                    </p>

                <?php endif; ?>

            </div>


        <?php else : ?>


            <!-- =================================================
                 CARDS DOS AUTORES
                 ================================================= -->

            <div class="cards-autores">

                <?php
                while (
                    $autor =
                    $resultado->fetch_assoc()
                ) :
                ?>

                    <article class="card-autor">


                        <!-- FOTO -->

                        <div class="imagem-card-autor">

                            <?php
                            if (
                                !empty(
                                    $autor[
                                        "foto_exibicao"
                                    ]
                                )
                            ) :
                            ?>

                                <img
                                    src="<?php
                                    echo htmlspecialchars(
                                        $autor[
                                            "foto_exibicao"
                                        ]
                                    );
                                    ?>"
                                    alt="<?php
                                    echo htmlspecialchars(
                                        $autor["nome"]
                                    );
                                    ?>"
                                >

                            <?php else : ?>

                                <div class="autor-sem-foto-card">

                                    <?php
                                    echo strtoupper(
                                        mb_substr(
                                            $autor[
                                                "nome"
                                            ],
                                            0,
                                            1
                                        )
                                    );
                                    ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- CONTEÚDO -->

                        <div class="conteudo-card-autor">


                            <h2>

                                <?php
                                echo htmlspecialchars(
                                    $autor["nome"]
                                );
                                ?>

                            </h2>


                            <!-- INSTITUIÇÃO -->

                            <?php
                            if (
                                !empty(
                                    $autor[
                                        "instituicao"
                                    ]
                                )
                            ) :
                            ?>

                                <p class="instituicao-card-autor">

                                    <?php
                                    echo htmlspecialchars(
                                        $autor[
                                            "instituicao"
                                        ]
                                    );
                                    ?>

                                </p>

                            <?php endif; ?>


                            <!-- BIOGRAFIA -->

                            <p class="biografia-card-autor">

                                <?php

                                if (
                                    !empty(
                                        $autor[
                                            "biografia"
                                        ]
                                    )
                                ) {

                                    $biografia =
                                        $autor[
                                            "biografia"
                                        ];

                                    if (
                                        mb_strlen(
                                            $biografia
                                        ) > 180
                                    ) {

                                        $biografia =
                                            mb_substr(
                                                $biografia,
                                                0,
                                                180
                                            )
                                            . "...";
                                    }

                                    echo htmlspecialchars(
                                        $biografia
                                    );

                                } else {

                                    echo
                                        "Informações adicionais sobre este autor ainda não foram cadastradas.";
                                }

                                ?>

                            </p>


                            <!-- QUANTIDADE -->

                            <p class="quantidade-card-autor">

                                <strong>
                                    Pesquisas no acervo:
                                </strong>

                                <?php
                                echo (int)$autor[
                                    "quantidade_pesquisas"
                                ];
                                ?>

                            </p>


                            <!-- ACESSO -->

                            <a
                                href="autor.php?id=<?php
                                echo $autor["id"];
                                ?>"
                                class="botao-acessar-autor"
                            >
                                Acessar autor
                            </a>


                        </div>

                    </article>

                <?php endwhile; ?>

            </div>


            <!-- =================================================
                 PAGINAÇÃO
                 ================================================= -->

            <?php
            if (
                $totalPaginas > 1
            ) :
            ?>

                <nav
                    class="paginacao"
                    aria-label="Paginação dos autores"
                >


                    <!-- ANTERIOR -->

                    <?php
                    if (
                        $paginaAtual > 1
                    ) :
                    ?>

                        <?php

                        $parametrosAnterior = [
                            "pagina" =>
                                $paginaAtual - 1
                        ];

                        if ($busca !== "") {

                            $parametrosAnterior[
                                "busca"
                            ] = $busca;
                        }

                        ?>

                        <a
                            class="pagina-link"
                            href="?<?php
                            echo htmlspecialchars(
                                http_build_query(
                                    $parametrosAnterior
                                )
                            );
                            ?>"
                        >
                            ← Anterior
                        </a>

                    <?php endif; ?>


                    <!-- NÚMEROS DAS PÁGINAS -->

                    <?php
                    for (
                        $pagina = 1;
                        $pagina <=
                        $totalPaginas;
                        $pagina++
                    ) :
                    ?>

                        <?php

                        $parametrosPagina = [
                            "pagina" =>
                                $pagina
                        ];

                        if ($busca !== "") {

                            $parametrosPagina[
                                "busca"
                            ] = $busca;
                        }

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
                                href="?<?php
                                echo htmlspecialchars(
                                    http_build_query(
                                        $parametrosPagina
                                    )
                                );
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

                        <?php

                        $parametrosProxima = [
                            "pagina" =>
                                $paginaAtual + 1
                        ];

                        if ($busca !== "") {

                            $parametrosProxima[
                                "busca"
                            ] = $busca;
                        }

                        ?>

                        <a
                            class="pagina-link"
                            href="?<?php
                            echo htmlspecialchars(
                                http_build_query(
                                    $parametrosProxima
                                )
                            );
                            ?>"
                        >
                            Próxima →
                        </a>

                    <?php endif; ?>


                </nav>

            <?php endif; ?>


        <?php endif; ?>

    </section>

</main>

<?php

$stmt->close();

require_once "../includes/footer.php";

?>