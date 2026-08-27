<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["administrador"]);

$mensagemErro = "";


/*
 * Valida o ID da pesquisa.
 */
$pesquisaId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$pesquisaId) {
    header("Location: pesquisas.php");
    exit;
}


/*
 * Busca os dados da pesquisa.
 *
 * Importante:
 * também buscamos autor_id porque ele será
 * necessário depois da exclusão para verificar
 * se o autor ficou sem pesquisas.
 */
$sqlPesquisa = "
    SELECT
        p.id,
        p.titulo,
        p.status,
        p.dataEnvio,
        p.dataAprovacao,
        p.autor_id,

        a.nome AS autor,

        u.nome AS pesquisador

    FROM pesquisas p

    INNER JOIN autores a
        ON p.autor_id = a.id

    INNER JOIN usuarios u
        ON p.pesquisador_id = u.id

    WHERE p.id = ?

    LIMIT 1
";

$stmtPesquisa =
    $conn->prepare($sqlPesquisa);

$stmtPesquisa->bind_param(
    "i",
    $pesquisaId
);

$stmtPesquisa->execute();

$resultadoPesquisa =
    $stmtPesquisa->get_result();


if ($resultadoPesquisa->num_rows !== 1) {

    $stmtPesquisa->close();

    header("Location: pesquisas.php");
    exit;
}


$pesquisa =
    $resultadoPesquisa->fetch_assoc();

/*
 * Guardamos o autor antes de excluir a pesquisa.
 */
$autorId =
    (int)$pesquisa["autor_id"];


/*
 * Processa a exclusão somente via POST.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $confirmacao =
        $_POST["confirmacao"] ?? "";


    if ($confirmacao !== "excluir") {

        $mensagemErro =
            "Confirmação inválida.";

    } else {

        /*
         * Usa transação para garantir que:
         *
         * - avaliações sejam removidas;
         * - pesquisa seja removida;
         * - autor órfão seja removido quando necessário;
         *
         * ou nenhuma alteração seja confirmada
         * em caso de erro.
         */
        $conn->begin_transaction();

        try {

            /*
             * Remove avaliações relacionadas.
             *
             * Mesmo havendo ON DELETE CASCADE,
             * mantemos a exclusão explícita.
             */
            $sqlAvaliacoes = "
                DELETE FROM avaliacoes_pesquisas
                WHERE pesquisa_id = ?
            ";

            $stmtAvaliacoes =
                $conn->prepare($sqlAvaliacoes);

            $stmtAvaliacoes->bind_param(
                "i",
                $pesquisaId
            );

            $stmtAvaliacoes->execute();

            $stmtAvaliacoes->close();


            /*
             * Remove a pesquisa.
             */
            $sqlExcluir = "
                DELETE FROM pesquisas
                WHERE id = ?
            ";

            $stmtExcluir =
                $conn->prepare($sqlExcluir);

            $stmtExcluir->bind_param(
                "i",
                $pesquisaId
            );

            $stmtExcluir->execute();


            if ($stmtExcluir->affected_rows !== 1) {

                $stmtExcluir->close();

                throw new Exception(
                    "Não foi possível excluir a pesquisa."
                );
            }

            $stmtExcluir->close();


            /*
             * Verifica se o autor ainda possui
             * alguma pesquisa cadastrada.
             */
            $sqlVerificarAutor = "
                SELECT COUNT(*) AS total
                FROM pesquisas
                WHERE autor_id = ?
            ";

            $stmtVerificarAutor =
                $conn->prepare(
                    $sqlVerificarAutor
                );

            $stmtVerificarAutor->bind_param(
                "i",
                $autorId
            );

            $stmtVerificarAutor->execute();

            $resultadoVerificarAutor =
                $stmtVerificarAutor->get_result();

            $dadosAutor =
                $resultadoVerificarAutor
                    ->fetch_assoc();

            $totalPesquisasAutor =
                (int)$dadosAutor["total"];

            $stmtVerificarAutor->close();


            /*
             * Se o autor ficou sem pesquisas,
             * tenta removê-lo.
             *
             * A condição usuario_id IS NULL
             * protege autores vinculados a uma
             * conta de pesquisador.
             *
             * Portanto:
             *
             * Autor externo + 0 pesquisas
             *      -> é removido.
             *
             * Autor vinculado + 0 pesquisas
             *      -> permanece no sistema.
             */
            if ($totalPesquisasAutor === 0) {

                $sqlExcluirAutor = "
                    DELETE FROM autores
                    WHERE
                        id = ?
                        AND usuario_id IS NULL
                ";

                $stmtExcluirAutor =
                    $conn->prepare(
                        $sqlExcluirAutor
                    );

                $stmtExcluirAutor->bind_param(
                    "i",
                    $autorId
                );

                $stmtExcluirAutor->execute();

                $stmtExcluirAutor->close();
            }


            /*
             * Confirma todas as alterações.
             */
            $conn->commit();

            header(
                "Location: pesquisas.php?exclusao=sucesso"
            );

            exit;

        } catch (Throwable $erro) {

            $conn->rollback();

            $mensagemErro =
                "Não foi possível excluir a pesquisa.";
        }
    }
}


$cssPagina = "pesquisas.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-pesquisa">

    <section class="confirmacao-exclusao">

        <div class="cabecalho-exclusao">

            <h1>Excluir pesquisa</h1>

            <p>
                Esta ação removerá a pesquisa
                definitivamente do sistema.
            </p>

        </div>


        <?php if ($mensagemErro !== "") : ?>

            <div class="mensagem-erro">

                <?php
                echo htmlspecialchars(
                    $mensagemErro
                );
                ?>

            </div>

        <?php endif; ?>


        <article class="card-confirmacao-exclusao">

            <h2>
                <?php
                echo htmlspecialchars(
                    $pesquisa["titulo"]
                );
                ?>
            </h2>


            <div class="dados-confirmacao-exclusao">

                <p>
                    <strong>Autor:</strong>

                    <?php
                    echo htmlspecialchars(
                        $pesquisa["autor"]
                    );
                    ?>
                </p>


                <p>
                    <strong>Pesquisador responsável:</strong>

                    <?php
                    echo htmlspecialchars(
                        $pesquisa["pesquisador"]
                    );
                    ?>
                </p>


                <p>
                    <strong>Status:</strong>

                    <?php
                    echo htmlspecialchars(
                        $pesquisa["status"]
                    );
                    ?>
                </p>


                <p>
                    <strong>Data de envio:</strong>

                    <?php
                    echo date(
                        "d/m/Y H:i",
                        strtotime(
                            $pesquisa["dataEnvio"]
                        )
                    );
                    ?>
                </p>

            </div>


            <div class="aviso-exclusao">

                <strong>Atenção:</strong>

                <p>
                    Ao confirmar, a pesquisa será
                    removida do Acervo Agroecológico.
                </p>

                <p>
                    As avaliações relacionadas a esta
                    pesquisa também serão removidas.
                </p>

                <p>
                    Caso seja a última pesquisa de um
                    autor externo, o perfil desse autor
                    também será removido.
                </p>

                <p>
                    Autores vinculados a contas de
                    pesquisadores não serão excluídos
                    automaticamente.
                </p>

                <p>
                    Esta ação não poderá ser desfeita.
                </p>

            </div>


            <form method="POST">

                <input
                    type="hidden"
                    name="confirmacao"
                    value="excluir"
                >


                <div class="acoes-confirmacao-exclusao">

                    <a
                        href="pesquisas.php"
                        class="botao-cancelar-exclusao"
                    >
                        Cancelar
                    </a>


                    <button
                        type="submit"
                        class="botao-confirmar-exclusao"
                    >
                        Excluir definitivamente
                    </button>

                </div>

            </form>

        </article>

    </section>

</main>

<?php

$stmtPesquisa->close();

require_once "../includes/footer.php";

?>