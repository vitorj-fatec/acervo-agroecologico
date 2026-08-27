<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["administrador"]);


/*
 * =========================================================
 * FILTRO POR STATUS
 * =========================================================
 */

$statusPermitidos = [
    "Pendente",
    "Aprovada",
    "Rejeitada"
];

$statusSelecionado =
    trim($_GET["status"] ?? "");


/*
 * Impede que um valor inválido seja utilizado
 * diretamente na consulta.
 */
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
 * CONTA QUANTAS PESQUISAS EXISTEM
 * considerando o filtro selecionado.
 * =========================================================
 */

if ($statusSelecionado !== "") {

    $sqlTotal = "
        SELECT COUNT(*) AS total
        FROM pesquisas
        WHERE status = ?
    ";

    $stmtTotal =
        $conn->prepare($sqlTotal);

    $stmtTotal->bind_param(
        "s",
        $statusSelecionado
    );

    $stmtTotal->execute();

    $resultadoTotal =
        $stmtTotal->get_result();

    $totalRegistros =
        (int)$resultadoTotal
            ->fetch_assoc()["total"];

    $stmtTotal->close();

} else {

    $sqlTotal = "
        SELECT COUNT(*) AS total
        FROM pesquisas
    ";

    $resultadoTotal =
        $conn->query($sqlTotal);

    $totalRegistros =
        (int)$resultadoTotal
            ->fetch_assoc()["total"];
}


/*
 * Quantidade total de páginas.
 */
$totalPaginas =
    max(
        1,
        (int)ceil(
            $totalRegistros /
            $itensPorPagina
        )
    );


/*
 * Se alguém informar uma página maior
 * que a existente, direcionamos para
 * a última página possível.
 */
if ($paginaAtual > $totalPaginas) {

    $paginaAtual = $totalPaginas;
}


/*
 * OFFSET utilizado pelo MySQL.
 */
$offset =
    ($paginaAtual - 1) *
    $itensPorPagina;


/*
 * =========================================================
 * CONSULTA DAS PESQUISAS
 * =========================================================
 */

$sqlBase = "
    SELECT
        p.id,
        p.titulo,
        p.descricao,
        p.dataEnvio,
        p.dataAprovacao,
        p.status,
        p.solo_informado,
        p.cultivo_informado,

        a.nome AS autor,
        r.nome AS regiao,
        u.nome AS pesquisador

    FROM pesquisas p

    INNER JOIN autores a
        ON p.autor_id = a.id

    INNER JOIN regioes r
        ON p.regiao_id = r.id

    INNER JOIN usuarios u
        ON p.pesquisador_id = u.id
";


if ($statusSelecionado !== "") {

    $sql = $sqlBase . "

        WHERE p.status = ?

        ORDER BY p.dataEnvio DESC

        LIMIT ?
        OFFSET ?
    ";

    $stmt =
        $conn->prepare($sql);

    $stmt->bind_param(
        "sii",
        $statusSelecionado,
        $itensPorPagina,
        $offset
    );

} else {

    $sql = $sqlBase . "

        ORDER BY p.dataEnvio DESC

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


$cssPagina = "pesquisas.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-pesquisa">


    <?php
    if (
        isset($_GET["exclusao"]) &&
        $_GET["exclusao"] === "sucesso"
    ) :
    ?>

        <div class="mensagem-sucesso">
            Pesquisa excluída com sucesso.
        </div>

    <?php endif; ?>


    <section class="lista-submissoes">


        <!-- =================================================
             CABEÇALHO
             ================================================= -->

        <div class="cabecalho-submissoes">

            <div>

                <h1>
                    Gerenciar pesquisas
                </h1>

                <p>
                    Consulte pesquisas pendentes,
                    aprovadas e rejeitadas do
                    Acervo Agroecológico.
                </p>

            </div>

        </div>


        <!-- =================================================
             FILTROS
             ================================================= -->

        <div class="filtros-admin-pesquisas">

            <span class="titulo-filtro-pesquisas">
                Filtrar por situação:
            </span>


            <a
                href="pesquisas.php"
                class="filtro-status <?php
                echo $statusSelecionado === ""
                    ? "filtro-ativo"
                    : "";
                ?>"
            >
                Todas
            </a>


            <a
                href="pesquisas.php?status=Pendente"
                class="filtro-status <?php
                echo $statusSelecionado === "Pendente"
                    ? "filtro-ativo"
                    : "";
                ?>"
            >
                Pendentes
            </a>


            <a
                href="pesquisas.php?status=Aprovada"
                class="filtro-status <?php
                echo $statusSelecionado === "Aprovada"
                    ? "filtro-ativo"
                    : "";
                ?>"
            >
                Aprovadas
            </a>


            <a
                href="pesquisas.php?status=Rejeitada"
                class="filtro-status <?php
                echo $statusSelecionado === "Rejeitada"
                    ? "filtro-ativo"
                    : "";
                ?>"
            >
                Rejeitadas
            </a>

        </div>


        <!-- =================================================
             RESULTADOS
             ================================================= -->

        <?php if ($resultado->num_rows === 0) : ?>

            <div class="sem-submissoes">

                <h2>
                    Nenhuma pesquisa encontrada
                </h2>

                <p>

                    <?php if ($statusSelecionado !== "") : ?>

                        Não existem pesquisas com
                        a situação

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $statusSelecionado
                            );
                            ?>
                        </strong>.

                    <?php else : ?>

                        Ainda não existem pesquisas
                        registradas no sistema.

                    <?php endif; ?>

                </p>

            </div>


        <?php else : ?>


            <div class="cards-submissoes">

                <?php
                while (
                    $pesquisa =
                    $resultado->fetch_assoc()
                ) :
                ?>


                    <?php

                    $classeStatus =
                        "status-pendente";

                    if (
                        $pesquisa["status"] ===
                        "Aprovada"
                    ) {

                        $classeStatus =
                            "status-aprovada";
                    }

                    if (
                        $pesquisa["status"] ===
                        "Rejeitada"
                    ) {

                        $classeStatus =
                            "status-rejeitada";
                    }

                    ?>


                    <article class="card-submissao">


                        <!-- TOPO -->

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
                                echo $classeStatus;
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
                                    Pesquisador responsável:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $pesquisa[
                                        "pesquisador"
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

                                <p>

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


                        </div>


                        <!-- DESCRIÇÃO -->

                        <?php
                        if (
                            !empty(
                                $pesquisa["descricao"]
                            )
                        ) :
                        ?>

                            <div class="descricao-submissao">

                                <strong>
                                    Descrição:
                                </strong>

                                <p>

                                    <?php
                                    echo nl2br(
                                        htmlspecialchars(
                                            $pesquisa[
                                                "descricao"
                                            ]
                                        )
                                    );
                                    ?>

                                </p>

                            </div>

                        <?php endif; ?>


                        <!-- AÇÕES -->

                        <div class="acoes-administrador">


                            <?php
                            if (
                                $pesquisa["status"] ===
                                "Pendente"
                            ) :
                            ?>

                                <a
                                    href="analisarPesquisa.php?id=<?php
                                    echo $pesquisa["id"];
                                    ?>"
                                    class="botao-analisar"
                                >
                                    Analisar
                                </a>

                            <?php endif; ?>


                            <a
                                href="editarPesquisa.php?id=<?php
                                echo $pesquisa["id"];
                                ?>"
                                class="botao-editar-pesquisa"
                            >
                                Editar
                            </a>


                            <a
                                href="excluirPesquisa.php?id=<?php
                                echo $pesquisa["id"];
                                ?>"
                                class="botao-excluir-pesquisa"
                            >
                                Excluir
                            </a>

                        </div>


                    </article>

                <?php endwhile; ?>

            </div>


            <!-- =================================================
                 PAGINAÇÃO
                 ================================================= -->

            <?php if ($totalPaginas > 1) : ?>

                <nav
                    class="paginacao"
                    aria-label="Paginação das pesquisas"
                >


                    <?php if ($paginaAtual > 1) : ?>

                        <?php

                        $parametrosAnterior = [
                            "pagina" =>
                                $paginaAtual - 1
                        ];

                        if (
                            $statusSelecionado !== ""
                        ) {

                            $parametrosAnterior[
                                "status"
                            ] =
                                $statusSelecionado;
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


                    <?php
                    for (
                        $pagina = 1;
                        $pagina <= $totalPaginas;
                        $pagina++
                    ) :
                    ?>

                        <?php

                        $parametrosPagina = [
                            "pagina" => $pagina
                        ];

                        if (
                            $statusSelecionado !== ""
                        ) {

                            $parametrosPagina[
                                "status"
                            ] =
                                $statusSelecionado;
                        }

                        ?>


                        <?php
                        if (
                            $pagina === $paginaAtual
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

                        if (
                            $statusSelecionado !== ""
                        ) {

                            $parametrosProxima[
                                "status"
                            ] =
                                $statusSelecionado;
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