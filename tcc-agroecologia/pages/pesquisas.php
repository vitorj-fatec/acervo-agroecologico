<?php

require_once "../includes/verificacao.php";
require_once "../includes/conexao.php";


/*
 * =========================================================
 * FILTROS RECEBIDOS PELA URL
 * =========================================================
 */

$busca = trim($_GET["busca"] ?? "");
$regiao = trim($_GET["regiao"] ?? "");
$solo = trim($_GET["solo"] ?? "");
$cultivo = trim($_GET["cultivo"] ?? "");
$autor = trim($_GET["autor"] ?? "");


/*
 * =========================================================
 * PAGINAÇÃO
 * =========================================================
 */

$itensPorPagina = 6;

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
 * CARREGA OPÇÕES DOS FILTROS
 * =========================================================
 */

$listaRegioes = $conn->query("
    SELECT id, nome
    FROM regioes
    ORDER BY nome
");

$listaSolos = $conn->query("
    SELECT id, nome
    FROM tipos_solo
    ORDER BY nome
");

$listaCultivos = $conn->query("
    SELECT id, nome
    FROM tipos_cultivo
    ORDER BY nome
");

$listaAutores = $conn->query("
    SELECT id, nome
    FROM autores
    ORDER BY nome
");


/*
 * =========================================================
 * MONTA AS CONDIÇÕES DOS FILTROS
 * =========================================================
 */

$where = "
    WHERE p.status = 'Aprovada'
";

$tipos = "";
$parametros = [];


/*
 * BUSCA POR PALAVRA-CHAVE
 */
if ($busca !== "") {

    $where .= "
        AND (
            p.titulo LIKE ?
            OR p.descricao LIKE ?
            OR p.resumo LIKE ?
            OR p.palavras_chave LIKE ?
        )
    ";

    $termoBusca =
        "%" . $busca . "%";

    $tipos .= "ssss";

    $parametros[] = $termoBusca;
    $parametros[] = $termoBusca;
    $parametros[] = $termoBusca;
    $parametros[] = $termoBusca;
}


/*
 * REGIÃO
 */
if ($regiao !== "") {

    $where .= "
        AND p.regiao_id = ?
    ";

    $tipos .= "i";

    $parametros[] =
        (int)$regiao;
}


/*
 * SOLO
 */
if ($solo !== "") {

    $where .= "
        AND p.solo_id = ?
    ";

    $tipos .= "i";

    $parametros[] =
        (int)$solo;
}


/*
 * CULTIVO
 */
if ($cultivo !== "") {

    $where .= "
        AND p.cultivo_id = ?
    ";

    $tipos .= "i";

    $parametros[] =
        (int)$cultivo;
}


/*
 * AUTOR
 */
if ($autor !== "") {

    $where .= "
        AND p.autor_id = ?
    ";

    $tipos .= "i";

    $parametros[] =
        (int)$autor;
}


/*
 * =========================================================
 * CONTA O TOTAL DE PESQUISAS
 * considerando todos os filtros atuais
 * =========================================================
 */

$sqlTotal = "
    SELECT COUNT(*) AS total

    FROM pesquisas p

    $where
";

$stmtTotal =
    $conn->prepare($sqlTotal);


if (!empty($parametros)) {

    $stmtTotal->bind_param(
        $tipos,
        ...$parametros
    );
}


$stmtTotal->execute();

$resultadoTotal =
    $stmtTotal->get_result();

$totalPesquisas =
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
            $totalPesquisas /
            $itensPorPagina
        )
    );


if ($paginaAtual > $totalPaginas) {

    $paginaAtual =
        $totalPaginas;
}


$offset =
    ($paginaAtual - 1) *
    $itensPorPagina;


/*
 * =========================================================
 * CONSULTA PRINCIPAL
 * =========================================================
 */

$sql = "
    SELECT
        p.id,
        p.titulo,
        p.descricao,
        p.resumo,
        p.palavras_chave,
        p.link,
        p.solo_informado,
        p.cultivo_informado,
        p.dataAprovacao,

        a.nome AS autor,

        r.nome AS regiao,

        COALESCE(
            AVG(av.nota),
            0
        ) AS media_avaliacao,

        COUNT(av.id)
            AS quantidade_avaliacoes

    FROM pesquisas p

    INNER JOIN autores a
        ON p.autor_id = a.id

    INNER JOIN regioes r
        ON p.regiao_id = r.id

    LEFT JOIN avaliacoes_pesquisas av
        ON av.pesquisa_id = p.id

    $where

    GROUP BY
        p.id,
        p.titulo,
        p.descricao,
        p.resumo,
        p.palavras_chave,
        p.link,
        p.solo_informado,
        p.cultivo_informado,
        p.dataAprovacao,
        a.nome,
        r.nome

    ORDER BY p.dataAprovacao DESC

    LIMIT ?
    OFFSET ?
";


$stmt =
    $conn->prepare($sql);


/*
 * Precisamos acrescentar LIMIT e OFFSET
 * aos parâmetros já existentes.
 */
$tiposConsulta =
    $tipos . "ii";

$parametrosConsulta =
    $parametros;

$parametrosConsulta[] =
    $itensPorPagina;

$parametrosConsulta[] =
    $offset;


$stmt->bind_param(
    $tiposConsulta,
    ...$parametrosConsulta
);


$stmt->execute();

$resultado =
    $stmt->get_result();


$cssPagina = "pesquisas.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-pesquisa">

    <section class="pagina-pesquisas-com-filtros">


        <!-- =================================================
             BARRA LATERAL DE FILTROS
             ================================================= -->

        <aside class="filtros-pesquisas">

            <h2>
                Buscar pesquisas
            </h2>


            <form method="GET">


                <!-- PALAVRA-CHAVE -->

                <div class="campo-filtro">

                    <label for="busca">
                        Palavra-chave
                    </label>

                    <input
                        type="text"
                        name="busca"
                        id="busca"
                        value="<?php
                        echo htmlspecialchars(
                            $busca
                        );
                        ?>"
                        placeholder="Ex.: manejo do solo"
                    >

                </div>


                <!-- REGIÃO -->

                <div class="campo-filtro">

                    <label for="regiao">
                        Região
                    </label>

                    <select
                        name="regiao"
                        id="regiao"
                    >

                        <option value="">
                            Todas
                        </option>

                        <?php
                        while (
                            $itemRegiao =
                            $listaRegioes
                                ->fetch_assoc()
                        ) :
                        ?>

                            <option
                                value="<?php
                                echo $itemRegiao[
                                    "id"
                                ];
                                ?>"
                                <?php
                                echo (
                                    (string)$regiao ===
                                    (string)$itemRegiao[
                                        "id"
                                    ]
                                )
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $itemRegiao[
                                        "nome"
                                    ]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- SOLO -->

                <div class="campo-filtro">

                    <label for="solo">
                        Tipo de solo
                    </label>

                    <select
                        name="solo"
                        id="solo"
                    >

                        <option value="">
                            Todos
                        </option>

                        <?php
                        while (
                            $itemSolo =
                            $listaSolos
                                ->fetch_assoc()
                        ) :
                        ?>

                            <option
                                value="<?php
                                echo $itemSolo[
                                    "id"
                                ];
                                ?>"
                                <?php
                                echo (
                                    (string)$solo ===
                                    (string)$itemSolo[
                                        "id"
                                    ]
                                )
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $itemSolo[
                                        "nome"
                                    ]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- CULTIVO -->

                <div class="campo-filtro">

                    <label for="cultivo">
                        Tipo de cultivo
                    </label>

                    <select
                        name="cultivo"
                        id="cultivo"
                    >

                        <option value="">
                            Todos
                        </option>

                        <?php
                        while (
                            $itemCultivo =
                            $listaCultivos
                                ->fetch_assoc()
                        ) :
                        ?>

                            <option
                                value="<?php
                                echo $itemCultivo[
                                    "id"
                                ];
                                ?>"
                                <?php
                                echo (
                                    (string)$cultivo ===
                                    (string)$itemCultivo[
                                        "id"
                                    ]
                                )
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $itemCultivo[
                                        "nome"
                                    ]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- AUTOR -->

                <div class="campo-filtro">

                    <label for="autor">
                        Autor
                    </label>

                    <select
                        name="autor"
                        id="autor"
                    >

                        <option value="">
                            Todos
                        </option>

                        <?php
                        while (
                            $itemAutor =
                            $listaAutores
                                ->fetch_assoc()
                        ) :
                        ?>

                            <option
                                value="<?php
                                echo $itemAutor[
                                    "id"
                                ];
                                ?>"
                                <?php
                                echo (
                                    (string)$autor ===
                                    (string)$itemAutor[
                                        "id"
                                    ]
                                )
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $itemAutor[
                                        "nome"
                                    ]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <button
                    type="submit"
                    class="botao-filtrar"
                >
                    Aplicar filtros
                </button>


                <a
                    href="pesquisas.php"
                    class="botao-limpar-filtros"
                >
                    Limpar filtros
                </a>


            </form>

        </aside>


        <!-- =================================================
             RESULTADOS
             ================================================= -->

        <section class="resultados-pesquisas">


            <div class="cabecalho-pesquisas">

                <h1>
                    Pesquisas
                </h1>

                <p>
                    Consulte pesquisas aprovadas disponíveis
                    no Acervo Agroecológico.
                </p>

            </div>


            <?php
            if (
                $resultado->num_rows === 0
            ) :
            ?>

                <div class="sem-submissoes">

                    <h2>
                        Nenhuma pesquisa encontrada
                    </h2>

                    <p>
                        Tente alterar os termos de busca
                        ou remover alguns filtros.
                    </p>

                </div>


            <?php else : ?>


                <div class="cards-pesquisas-publicas">

                    <?php
                    while (
                        $pesquisa =
                        $resultado
                            ->fetch_assoc()
                    ) :
                    ?>

                        <article class="card-pesquisa-publica">


                            <h2>

                                <?php
                                echo htmlspecialchars(
                                    $pesquisa[
                                        "titulo"
                                    ]
                                );
                                ?>

                            </h2>


                            <div class="dados-pesquisa-publica">


                                <p>

                                    <strong>
                                        Autor:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $pesquisa[
                                            "autor"
                                        ]
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Região:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $pesquisa[
                                            "regiao"
                                        ]
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Solo:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $pesquisa[
                                            "solo_informado"
                                        ]
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Cultivo:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $pesquisa[
                                            "cultivo_informado"
                                        ]
                                    );
                                    ?>

                                </p>


                            </div>


                            <!-- AVALIAÇÃO -->

                            <div class="avaliacao-resumo">

                                <?php

                                $media =
                                    number_format(
                                        (float)$pesquisa[
                                            "media_avaliacao"
                                        ],
                                        1,
                                        ",",
                                        "."
                                    );

                                ?>

                                <span>
                                    ★ <?php echo $media; ?>
                                </span>

                                <span>

                                    (
                                    <?php
                                    echo (int)$pesquisa[
                                        "quantidade_avaliacoes"
                                    ];
                                    ?>
                                    avaliações)

                                </span>

                            </div>


                            <!-- DESCRIÇÃO -->

                            <?php
                            if (
                                !empty(
                                    $pesquisa[
                                        "descricao"
                                    ]
                                )
                            ) :
                            ?>

                                <p class="descricao-card">

                                    <?php

                                    $descricao =
                                        $pesquisa[
                                            "descricao"
                                        ];

                                    if (
                                        mb_strlen(
                                            $descricao
                                        ) > 180
                                    ) {

                                        $descricao =
                                            mb_substr(
                                                $descricao,
                                                0,
                                                180
                                            )
                                            . "...";
                                    }

                                    echo htmlspecialchars(
                                        $descricao
                                    );

                                    ?>

                                </p>

                            <?php endif; ?>


                            <div class="acoes-card-pesquisa">

                                <a
                                    href="pesquisa.php?id=<?php
                                    echo $pesquisa[
                                        "id"
                                    ];
                                    ?>"
                                    class="botao-ver-pesquisa"
                                >
                                    Ver detalhes
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
                        aria-label="Paginação das pesquisas"
                    >


                        <?php

                        /*
                         * Parâmetros atuais.
                         * Eles serão mantidos ao trocar de página.
                         */
                        $parametrosBase = [];

                        if ($busca !== "") {
                            $parametrosBase[
                                "busca"
                            ] = $busca;
                        }

                        if ($regiao !== "") {
                            $parametrosBase[
                                "regiao"
                            ] = $regiao;
                        }

                        if ($solo !== "") {
                            $parametrosBase[
                                "solo"
                            ] = $solo;
                        }

                        if ($cultivo !== "") {
                            $parametrosBase[
                                "cultivo"
                            ] = $cultivo;
                        }

                        if ($autor !== "") {
                            $parametrosBase[
                                "autor"
                            ] = $autor;
                        }

                        ?>


                        <!-- ANTERIOR -->

                        <?php
                        if (
                            $paginaAtual > 1
                        ) :
                        ?>

                            <?php

                            $paramsAnterior =
                                $parametrosBase;

                            $paramsAnterior[
                                "pagina"
                            ] =
                                $paginaAtual - 1;

                            ?>

                            <a
                                class="pagina-link"
                                href="?<?php
                                echo htmlspecialchars(
                                    http_build_query(
                                        $paramsAnterior
                                    )
                                );
                                ?>"
                            >
                                ← Anterior
                            </a>

                        <?php endif; ?>


                        <!-- NÚMEROS -->

                        <?php
                        for (
                            $pagina = 1;
                            $pagina <=
                            $totalPaginas;
                            $pagina++
                        ) :
                        ?>

                            <?php

                            $paramsPagina =
                                $parametrosBase;

                            $paramsPagina[
                                "pagina"
                            ] =
                                $pagina;

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
                                            $paramsPagina
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

                            $paramsProxima =
                                $parametrosBase;

                            $paramsProxima[
                                "pagina"
                            ] =
                                $paginaAtual + 1;

                            ?>

                            <a
                                class="pagina-link"
                                href="?<?php
                                echo htmlspecialchars(
                                    http_build_query(
                                        $paramsProxima
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

    </section>

</main>

<?php

$stmt->close();

require_once "../includes/footer.php";

?>