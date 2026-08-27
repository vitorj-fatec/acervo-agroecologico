<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["administrador"]);

$mensagemErro = "";
$mensagemSucesso = "";


/*
 * =========================================================
 * PROCESSA BLOQUEIO OU REATIVAÇÃO
 * =========================================================
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuarioId = filter_input(
        INPUT_POST,
        "usuario_id",
        FILTER_VALIDATE_INT
    );

    $acao = $_POST["acao"] ?? "";


    if (!$usuarioId) {

        $mensagemErro =
            "Usuário inválido.";

    } elseif (
        $acao !== "bloquear" &&
        $acao !== "reativar"
    ) {

        $mensagemErro =
            "Ação inválida.";

    } elseif (
        $usuarioId === $_SESSION["usuario_id"]
    ) {

        /*
         * Impede o administrador
         * de bloquear a própria conta.
         */
        $mensagemErro =
            "Você não pode bloquear sua própria conta.";

    } else {

        /*
         * Define o novo estado.
         *
         * 0 = bloqueado
         * 1 = ativo
         */
        $novoEstado =
            $acao === "bloquear"
                ? 0
                : 1;


        /*
         * Confirma que o usuário existe
         * e impede alterações em contas
         * administrativas.
         */
        $sqlVerificar = "
            SELECT
                id,
                tipo

            FROM usuarios

            WHERE id = ?

            LIMIT 1
        ";

        $stmtVerificar =
            $conn->prepare($sqlVerificar);

        $stmtVerificar->bind_param(
            "i",
            $usuarioId
        );

        $stmtVerificar->execute();

        $resultadoVerificar =
            $stmtVerificar->get_result();


        if (
            $resultadoVerificar->num_rows !== 1
        ) {

            $mensagemErro =
                "Usuário não encontrado.";

        } else {

            $usuario =
                $resultadoVerificar
                    ->fetch_assoc();


            if (
                $usuario["tipo"] ===
                "administrador"
            ) {

                $mensagemErro =
                    "Contas administrativas não podem ser alteradas por esta tela.";

            } else {

                $sqlAtualizar = "
                    UPDATE usuarios

                    SET ativo = ?

                    WHERE id = ?
                ";

                $stmtAtualizar =
                    $conn->prepare(
                        $sqlAtualizar
                    );

                $stmtAtualizar->bind_param(
                    "ii",
                    $novoEstado,
                    $usuarioId
                );


                if (
                    $stmtAtualizar->execute()
                ) {

                    $mensagemSucesso =
                        $novoEstado === 1
                            ? "Usuário reativado com sucesso."
                            : "Usuário bloqueado com sucesso.";

                } else {

                    $mensagemErro =
                        "Não foi possível atualizar o usuário.";
                }

                $stmtAtualizar->close();
            }
        }

        $stmtVerificar->close();
    }
}


/*
 * =========================================================
 * PAGINAÇÃO
 * =========================================================
 */

$itensPorPagina = 15;

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
 * TOTAL DE USUÁRIOS E PESQUISADORES
 *
 * Administradores não entram na listagem.
 * =========================================================
 */

$sqlTotal = "
    SELECT COUNT(*) AS total

    FROM usuarios

    WHERE tipo IN (
        'usuario',
        'pesquisador'
    )
";

$resultadoTotal =
    $conn->query($sqlTotal);

$totalUsuarios =
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
            $totalUsuarios /
            $itensPorPagina
        )
    );


/*
 * Se a página informada não existir,
 * utiliza a última página disponível.
 */
if (
    $paginaAtual >
    $totalPaginas
) {

    $paginaAtual =
        $totalPaginas;
}


/*
 * Posição inicial da consulta.
 */
$offset =
    ($paginaAtual - 1) *
    $itensPorPagina;


/*
 * =========================================================
 * LISTAGEM DE USUÁRIOS
 * =========================================================
 */

$sqlUsuarios = "
    SELECT
        id,
        nome,
        email,
        tipo,
        instituicao,
        ativo,
        dataCadastro

    FROM usuarios

    WHERE tipo IN (
        'usuario',
        'pesquisador'
    )

    ORDER BY nome

    LIMIT ?
    OFFSET ?
";

$stmtUsuarios =
    $conn->prepare($sqlUsuarios);

$stmtUsuarios->bind_param(
    "ii",
    $itensPorPagina,
    $offset
);

$stmtUsuarios->execute();

$resultadoUsuarios =
    $stmtUsuarios->get_result();


$cssPagina = "usuarios.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-usuarios-admin">

    <section class="conteudo-usuarios-admin">


        <!-- =================================================
             CABEÇALHO
             ================================================= -->

        <div class="cabecalho-usuarios-admin">

            <div>

                <h1>
                    Gerenciamento de usuários
                </h1>

                <p>
                    Consulte, bloqueie ou reative
                    usuários e pesquisadores cadastrados.
                </p>

            </div>


            <a
                href="dashboard.php"
                class="botao-voltar"
            >
                Voltar
            </a>

        </div>


        <!-- =================================================
             MENSAGEM DE ERRO
             ================================================= -->

        <?php if ($mensagemErro !== "") : ?>

            <div class="mensagem-erro">

                <?php
                echo htmlspecialchars(
                    $mensagemErro
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             MENSAGEM DE SUCESSO
             ================================================= -->

        <?php if ($mensagemSucesso !== "") : ?>

            <div class="mensagem-sucesso">

                <?php
                echo htmlspecialchars(
                    $mensagemSucesso
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             SEM USUÁRIOS
             ================================================= -->

        <?php
        if (
            $resultadoUsuarios->num_rows === 0
        ) :
        ?>

            <div class="sem-usuarios">

                <p>
                    Nenhum usuário ou pesquisador
                    cadastrado no momento.
                </p>

            </div>


        <?php else : ?>


            <!-- =================================================
                 TABELA
                 ================================================= -->

            <div class="tabela-usuarios-container">

                <table class="tabela-usuarios">

                    <thead>

                        <tr>

                            <th>Nome</th>

                            <th>E-mail</th>

                            <th>Perfil</th>

                            <th>Instituição</th>

                            <th>Situação</th>

                            <th>Cadastro</th>

                            <th>Ação</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php
                        while (
                            $usuario =
                            $resultadoUsuarios
                                ->fetch_assoc()
                        ) :
                        ?>

                            <tr>

                                <!-- NOME -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $usuario["nome"]
                                    );
                                    ?>

                                </td>


                                <!-- E-MAIL -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $usuario["email"]
                                    );
                                    ?>

                                </td>


                                <!-- PERFIL -->

                                <td>

                                    <?php
                                    if (
                                        $usuario["tipo"]
                                        === "pesquisador"
                                    ) :
                                    ?>

                                        Pesquisador

                                    <?php else : ?>

                                        Usuário

                                    <?php endif; ?>

                                </td>


                                <!-- INSTITUIÇÃO -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $usuario[
                                                "instituicao"
                                            ]
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            $usuario[
                                                "instituicao"
                                            ]
                                        );

                                    } else {

                                        echo "—";
                                    }

                                    ?>

                                </td>


                                <!-- SITUAÇÃO -->

                                <td>

                                    <?php
                                    if (
                                        (int)$usuario[
                                            "ativo"
                                        ] === 1
                                    ) :
                                    ?>

                                        <span
                                            class="status-usuario status-ativo"
                                        >
                                            Ativo
                                        </span>

                                    <?php else : ?>

                                        <span
                                            class="status-usuario status-bloqueado"
                                        >
                                            Bloqueado
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- DATA -->

                                <td>

                                    <?php
                                    echo date(
                                        "d/m/Y",
                                        strtotime(
                                            $usuario[
                                                "dataCadastro"
                                            ]
                                        )
                                    );
                                    ?>

                                </td>


                                <!-- AÇÃO -->

                                <td>

                                    <form method="POST">


                                        <input
                                            type="hidden"
                                            name="usuario_id"
                                            value="<?php
                                            echo $usuario[
                                                "id"
                                            ];
                                            ?>"
                                        >


                                        <?php
                                        if (
                                            (int)$usuario[
                                                "ativo"
                                            ] === 1
                                        ) :
                                        ?>

                                            <button
                                                type="submit"
                                                name="acao"
                                                value="bloquear"
                                                class="botao-bloquear"
                                            >
                                                Bloquear
                                            </button>

                                        <?php else : ?>

                                            <button
                                                type="submit"
                                                name="acao"
                                                value="reativar"
                                                class="botao-reativar"
                                            >
                                                Reativar
                                            </button>

                                        <?php endif; ?>


                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>


            <!-- =================================================
                 PAGINAÇÃO
                 ================================================= -->

            <?php if ($totalPaginas > 1) : ?>

                <nav
                    class="paginacao"
                    aria-label="Paginação dos usuários"
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

</main>

<?php

$stmtUsuarios->close();

require_once "../includes/footer.php";

?>