<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["pesquisador"]);

$pesquisadorId =
    (int)$_SESSION["usuario_id"];


/* =========================================================
   BUSCA E FILTRO
   ========================================================= */

$busca =
    trim($_GET["busca"] ?? "");

$statusSelecionado =
    trim($_GET["status"] ?? "");


$statusPermitidos = [
    "Pendente",
    "Aprovada",
    "Rejeitada"
];


if (
    $statusSelecionado !== "" &&
    !in_array(
        $statusSelecionado,
        $statusPermitidos,
        true
    )
) {

    $statusSelecionado = "";
}


/* =========================================================
   PAGINAÇÃO
   ========================================================= */

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


/* =========================================================
   CONDIÇÕES DA CONSULTA
   ========================================================= */

$where = "
    WHERE p.pesquisador_id = ?
";

$tipos = "i";

$parametros = [
    $pesquisadorId
];


/*
 * Busca pelo título da pesquisa.
 */
if ($busca !== "") {

    $where .= "
        AND p.titulo LIKE ?
    ";

    $tipos .= "s";

    $termoBusca =
        "%" . $busca . "%";

    $parametros[] =
        $termoBusca;
}


/*
 * Filtro por situação.
 */
if ($statusSelecionado !== "") {

    $where .= "
        AND p.status = ?
    ";

    $tipos .= "s";

    $parametros[] =
        $statusSelecionado;
}


/* =========================================================
   TOTAL DE SUBMISSÕES
   ========================================================= */

$sqlTotal = "
    SELECT COUNT(*) AS total

    FROM pesquisas p

    $where
";


$stmtTotal =
    $conn->prepare($sqlTotal);


$stmtTotal->bind_param(
    $tipos,
    ...$parametros
);


$stmtTotal->execute();

$resultadoTotal =
    $stmtTotal->get_result();


$totalSubmissoes =
    (int)$resultadoTotal
        ->fetch_assoc()["total"];


$stmtTotal->close();


/* =========================================================
   TOTAL DE PÁGINAS
   ========================================================= */

$totalPaginas =
    max(
        1,
        (int)ceil(
            $totalSubmissoes /
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


/* =========================================================
   CONSULTA DAS SUBMISSÕES
   ========================================================= */

$sql = "
    SELECT
        p.id,
        p.titulo,
        p.status,
        p.observacao,
        p.dataEnvio,
        p.dataAprovacao,
        p.solo_informado,
        p.cultivo_informado,

        a.nome AS autor,
        r.nome AS regiao

    FROM pesquisas p

    INNER JOIN autores a
        ON p.autor_id = a.id

    INNER JOIN regioes r
        ON p.regiao_id = r.id

    $where

    ORDER BY p.dataEnvio DESC

    LIMIT ?
    OFFSET ?
";


$stmt =
    $conn->prepare($sql);


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


/* =========================================================
   CSS E INTERFACE
   ========================================================= */

$cssPagina = "pesquisas.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-pesquisa">

    <section class="lista-submissoes">


        <!-- =================================================
             CABEÇALHO
             ================================================= -->

        <div class="cabecalho-submissoes">

            <div>

                <h1>
                    Minhas submissões
                </h1>

                <p>
                    Acompanhe a situação das pesquisas
                    enviadas para análise.
                </p>

            </div>


            <a
                href="enviarPesquisa.php"
                class="botao-nova-pesquisa"
            >
                Enviar nova pesquisa
            </a>

        </div>


        <!-- =================================================
             BUSCA E FILTRO
             ================================================= -->

        <div class="filtros-status-pesquisador">

            <form method="GET">


                <div class="campo-busca-status">

                    <label for="busca">
                        Buscar por título
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
                        placeholder="Digite o nome da pesquisa"
                    >

                </div>


                <div class="campo-status-pesquisador">

                    <label for="status">
                        Situação
                    </label>

                    <select
                        name="status"
                        id="status"
                    >

                        <option value="">
                            Todas
                        </option>


                        <option
                            value="Pendente"
                            <?php
                            echo (
                                $statusSelecionado ===
                                "Pendente"
                            )
                                ? "selected"
                                : "";
                            ?>
                        >
                            Pendentes
                        </option>


                        <option
                            value="Aprovada"
                            <?php
                            echo (
                                $statusSelecionado ===
                                "Aprovada"
                            )
                                ? "selected"
                                : "";
                            ?>
                        >
                            Aprovadas
                        </option>


                        <option
                            value="Rejeitada"
                            <?php
                            echo (
                                $statusSelecionado ===
                                "Rejeitada"
                            )
                                ? "selected"
                                : "";
                            ?>
                        >
                            Rejeitadas
                        </option>

                    </select>

                </div>


                <div class="acoes-filtros-status">

                    <button
                        type="submit"
                        class="botao-filtrar-status"
                    >
                        Aplicar filtros
                    </button>


                    <a
                        href="status.php"
                        class="botao-limpar-status"
                    >
                        Limpar
                    </a>

                </div>

            </form>

        </div>


        <!-- =================================================
             NENHUM RESULTADO
             ================================================= -->

        <?php if ($resultado->num_rows === 0) : ?>

            <div class="sem-submissoes">

                <h2>
                    Nenhuma pesquisa encontrada
                </h2>

                <p>

                    <?php
                    if (
                        $busca !== "" ||
                        $statusSelecionado !== ""
                    ) :
                    ?>

                        Nenhuma das suas submissões
                        corresponde aos filtros selecionados.

                    <?php else : ?>

                        Você ainda não possui pesquisas
                        submetidas para análise.

                    <?php endif; ?>

                </p>

            </div>


        <?php else : ?>


            <!-- =================================================
                 CARDS
                 ================================================= -->

            <div class="cards-submissoes">

                <?php
                while (
                    $pesquisa =
                    $resultado->fetch_assoc()
                ) :
                ?>


                    <?php

                    if (
                        $pesquisa["status"] ===
                        "Aprovada"
                    ) {

                        $statusClasse =
                            "status-aprovada";

                    } elseif (
                        $pesquisa["status"] ===
                        "Rejeitada"
                    ) {

                        $statusClasse =
                            "status-rejeitada";

                    } else {

                        $statusClasse =
                            "status-pendente";
                    }

                    ?>


                    <article class="card-submissao">


                        <!-- TÍTULO E STATUS -->

                        <div class="topo-submissao">

                            <h2>

                                <?php
                                echo htmlspecialchars(
                                    $pesquisa["titulo"]
                                );
                                ?>

                            </h2>


                            <span
                                class="status-pesquisa <?php
                                echo $statusClasse;
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $pesquisa["status"]
                                );
                                ?>

                            </span>

                        </div>


                        <!-- DADOS -->

                        <div class="dados-submissao">


                            <p>

                                <strong>
                                    Autor:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $pesquisa["autor"]
                                );
                                ?>

                            </p>


                            <p>

                                <strong>
                                    Região:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $pesquisa["regiao"]
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


                            <p>

                                <strong>
                                    Data de envio:
                                </strong>

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


                        <!-- OBSERVAÇÃO DO ADMIN -->

                        <?php
                        if (
                            !empty(
                                $pesquisa[
                                    "observacao"
                                ]
                            )
                        ) :
                        ?>

                            <div class="observacao-administrador">

                                <strong>
                                    Observação do administrador:
                                </strong>

                                <p>

                                    <?php
                                    echo nl2br(
                                        htmlspecialchars(
                                            $pesquisa[
                                                "observacao"
                                            ]
                                        )
                                    );
                                    ?>

                                </p>

                            </div>

                        <?php endif; ?>


                        <!-- DATA DE APROVAÇÃO -->

                        <?php
                        if (
                            $pesquisa["status"] ===
                            "Aprovada" &&
                            !empty(
                                $pesquisa[
                                    "dataAprovacao"
                                ]
                            )
                        ) :
                        ?>

                            <p class="data-aprovacao">

                                <strong>
                                    Data de aprovação:
                                </strong>

                                <?php
                                echo date(
                                    "d/m/Y H:i",
                                    strtotime(
                                        $pesquisa[
                                            "dataAprovacao"
                                        ]
                                    )
                                );
                                ?>

                            </p>

                        <?php endif; ?>


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


                <?php

                /*
                 * Mantém os filtros selecionados
                 * ao navegar entre páginas.
                 */
                $parametrosBase = [];

                if ($busca !== "") {

                    $parametrosBase[
                        "busca"
                    ] = $busca;
                }


                if (
                    $statusSelecionado !== ""
                ) {

                    $parametrosBase[
                        "status"
                    ] =
                        $statusSelecionado;
                }

                ?>


                <nav
                    class="paginacao"
                    aria-label="Paginação das submissões"
                >


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

</main>

<?php

$stmt->close();

require_once "../includes/footer.php";

?>