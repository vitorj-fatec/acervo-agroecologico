<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["administrador"]);


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
 * TOTAL DE AUTORES
 * =========================================================
 */

$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM autores
";

$resultadoTotal =
    $conn->query($sqlTotal);

$totalAutores =
    (int)$resultadoTotal
        ->fetch_assoc()["total"];


/*
 * Calcula a quantidade total
 * de páginas.
 */
$totalPaginas =
    max(
        1,
        (int)ceil(
            $totalAutores /
            $itensPorPagina
        )
    );


/*
 * Impede acesso a uma página
 * maior do que a existente.
 */
if ($paginaAtual > $totalPaginas) {

    $paginaAtual = $totalPaginas;
}


/*
 * Calcula a posição inicial
 * utilizada pelo LIMIT/OFFSET.
 */
$offset =
    ($paginaAtual - 1) *
    $itensPorPagina;


/*
 * =========================================================
 * BUSCA DOS AUTORES
 * =========================================================
 */

$sql = "
    SELECT
        a.id,
        a.nome,
        a.foto,
        a.biografia,
        a.instituicao,
        a.usuario_id,

        u.nome AS usuario_nome,
        u.email AS usuario_email,

        COUNT(p.id) AS quantidade_pesquisas

    FROM autores a

    LEFT JOIN usuarios u
        ON u.id = a.usuario_id

    LEFT JOIN pesquisas p
        ON p.autor_id = a.id

    GROUP BY
        a.id,
        a.nome,
        a.foto,
        a.biografia,
        a.instituicao,
        a.usuario_id,
        u.nome,
        u.email

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

$stmt->execute();

$resultado =
    $stmt->get_result();


$cssPagina = "autoresAdmin.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-autores-admin">

    <section class="conteudo-autores-admin">


        <!-- =================================================
             CABEÇALHO
             ================================================= -->

        <div class="cabecalho-autores-admin">

            <div>

                <h1>
                    Gerenciar autores
                </h1>

                <p>
                    Consulte e edite os autores cadastrados
                    no Acervo Agroecológico.
                </p>

            </div>

        </div>


        <!-- =================================================
             SEM AUTORES
             ================================================= -->

        <?php if ($resultado->num_rows === 0) : ?>

            <div class="sem-autores-admin">

                <h2>
                    Nenhum autor cadastrado
                </h2>

                <p>
                    Ainda não existem autores
                    registrados no sistema.
                </p>

            </div>


        <?php else : ?>


            <!-- =================================================
                 CARDS DOS AUTORES
                 ================================================= -->

            <div class="cards-autores-admin">

                <?php
                while (
                    $autor =
                    $resultado->fetch_assoc()
                ) :
                ?>

                    <article class="card-autor-admin">


                        <!-- FOTO -->

                        <div class="foto-autor-admin">

                            <?php
                            if (
                                !empty(
                                    $autor["foto"]
                                )
                            ) :
                            ?>

                                <img
                                    src="<?php
                                    echo htmlspecialchars(
                                        $autor["foto"]
                                    );
                                    ?>"
                                    alt="<?php
                                    echo htmlspecialchars(
                                        $autor["nome"]
                                    );
                                    ?>"
                                >

                            <?php else : ?>

                                <div class="autor-sem-foto-admin">

                                    <?php
                                    echo strtoupper(
                                        mb_substr(
                                            $autor["nome"],
                                            0,
                                            1
                                        )
                                    );
                                    ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- DADOS -->

                        <div class="dados-autor-admin">


                            <div class="topo-card-autor-admin">


                                <h2>

                                    <?php
                                    echo htmlspecialchars(
                                        $autor["nome"]
                                    );
                                    ?>

                                </h2>


                                <?php
                                if (
                                    $autor["usuario_id"] !== null
                                ) :
                                ?>

                                    <span
                                        class="status-vinculo vinculado"
                                    >
                                        Vinculado
                                    </span>

                                <?php else : ?>

                                    <span
                                        class="status-vinculo nao-vinculado"
                                    >
                                        Autor externo
                                    </span>

                                <?php endif; ?>


                            </div>


                            <!-- INSTITUIÇÃO -->

                            <?php
                            if (
                                !empty(
                                    $autor["instituicao"]
                                )
                            ) :
                            ?>

                                <p>

                                    <strong>
                                        Instituição:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $autor[
                                            "instituicao"
                                        ]
                                    );
                                    ?>

                                </p>

                            <?php endif; ?>


                            <!-- QUANTIDADE DE PESQUISAS -->

                            <p>

                                <strong>
                                    Pesquisas cadastradas:
                                </strong>

                                <?php
                                echo (int)$autor[
                                    "quantidade_pesquisas"
                                ];
                                ?>

                            </p>


                            <!-- CONTA VINCULADA -->

                            <?php
                            if (
                                $autor["usuario_id"] !== null
                            ) :
                            ?>

                                <div class="vinculo-pesquisador">

                                    <p>

                                        <strong>
                                            Conta vinculada:
                                        </strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $autor[
                                                "usuario_nome"
                                            ]
                                        );
                                        ?>

                                    </p>


                                    <p>

                                        <?php
                                        echo htmlspecialchars(
                                            $autor[
                                                "usuario_email"
                                            ]
                                        );
                                        ?>

                                    </p>

                                </div>

                            <?php endif; ?>


                            <!-- AÇÕES -->

                            <div class="acoes-autor-admin">

                                <a
                                    href="editarAutor.php?id=<?php
                                    echo $autor["id"];
                                    ?>"
                                    class="botao-editar-autor"
                                >
                                    Editar autor
                                </a>

                            </div>


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
                    aria-label="Paginação dos autores"
                >


                    <!-- ANTERIOR -->

                    <?php if ($paginaAtual > 1) : ?>

                        <a
                            class="pagina-link"
                            href="?pagina=<?php
                            echo $paginaAtual - 1;
                            ?>"
                        >
                            ← Anterior
                        </a>

                    <?php endif; ?>


                    <!-- NÚMEROS -->

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

</main>

<?php

$stmt->close();

require_once "../includes/footer.php";

?>