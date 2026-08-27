<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["administrador"]);

$mensagemErro = "";
$mensagemSucesso = "";


/*
 * Valida o ID recebido pela URL.
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
 * Processa aprovação ou rejeição.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $acao = $_POST["acao"] ?? "";
    $observacao = trim($_POST["observacao"] ?? "");

    $administradorId = $_SESSION["usuario_id"];


    /*
     * Verifica se a pesquisa existe e ainda está pendente.
     * Também recupera solo e cultivo informados.
     */
    $sqlVerificar = "
        SELECT
            status,
            solo_informado,
            cultivo_informado
        FROM pesquisas
        WHERE id = ?
        LIMIT 1
    ";

    $stmtVerificar = $conn->prepare($sqlVerificar);

    $stmtVerificar->bind_param(
        "i",
        $pesquisaId
    );

    $stmtVerificar->execute();

    $resultadoVerificar =
        $stmtVerificar->get_result();


    if ($resultadoVerificar->num_rows !== 1) {

        $mensagemErro =
            "Pesquisa não encontrada.";

    } else {

        $dadosPesquisa =
            $resultadoVerificar->fetch_assoc();

        if ($dadosPesquisa["status"] !== "Pendente") {

            $mensagemErro =
                "Esta pesquisa já foi analisada.";

        } elseif (
            $acao !== "aprovar" &&
            $acao !== "rejeitar"
        ) {

            $mensagemErro =
                "Ação inválida.";

        } else {

            /*
             * --------------------------------
             * APROVAÇÃO
             * --------------------------------
             */
            if ($acao === "aprovar") {

                $novoStatus = "Aprovada";

                $soloInformado = trim(
                    $dadosPesquisa["solo_informado"]
                );

                $cultivoInformado = trim(
                    $dadosPesquisa["cultivo_informado"]
                );


                /*
                 * Transação:
                 * ou tudo é realizado,
                 * ou nada é mantido.
                 */
                $conn->begin_transaction();

                try {

                    /*
                     * ------------------------
                     * SOLO
                     * ------------------------
                     */

                    $soloId = null;

                    $sqlSolo = "
                        SELECT id
                        FROM tipos_solo
                        WHERE nome = ?
                        LIMIT 1
                    ";

                    $stmtSolo =
                        $conn->prepare($sqlSolo);

                    $stmtSolo->bind_param(
                        "s",
                        $soloInformado
                    );

                    $stmtSolo->execute();

                    $resultadoSolo =
                        $stmtSolo->get_result();


                    if ($resultadoSolo->num_rows === 1) {

                        $solo =
                            $resultadoSolo->fetch_assoc();

                        $soloId =
                            $solo["id"];

                    } else {

                        /*
                         * Se o solo ainda não existe,
                         * ele entra no catálogo somente
                         * porque a pesquisa está sendo aprovada.
                         */
                        $sqlNovoSolo = "
                            INSERT INTO tipos_solo (nome)
                            VALUES (?)
                        ";

                        $stmtNovoSolo =
                            $conn->prepare($sqlNovoSolo);

                        $stmtNovoSolo->bind_param(
                            "s",
                            $soloInformado
                        );

                        $stmtNovoSolo->execute();

                        $soloId =
                            $stmtNovoSolo->insert_id;

                        $stmtNovoSolo->close();
                    }

                    $stmtSolo->close();


                    /*
                     * ------------------------
                     * CULTIVO
                     * ------------------------
                     */

                    $cultivoId = null;

                    $sqlCultivo = "
                        SELECT id
                        FROM tipos_cultivo
                        WHERE nome = ?
                        LIMIT 1
                    ";

                    $stmtCultivo =
                        $conn->prepare($sqlCultivo);

                    $stmtCultivo->bind_param(
                        "s",
                        $cultivoInformado
                    );

                    $stmtCultivo->execute();

                    $resultadoCultivo =
                        $stmtCultivo->get_result();


                    if ($resultadoCultivo->num_rows === 1) {

                        $cultivo =
                            $resultadoCultivo->fetch_assoc();

                        $cultivoId =
                            $cultivo["id"];

                    } else {

                        /*
                         * Se o cultivo ainda não existe,
                         * ele entra no catálogo somente
                         * após a aprovação.
                         */
                        $sqlNovoCultivo = "
                            INSERT INTO tipos_cultivo (nome)
                            VALUES (?)
                        ";

                        $stmtNovoCultivo =
                            $conn->prepare($sqlNovoCultivo);

                        $stmtNovoCultivo->bind_param(
                            "s",
                            $cultivoInformado
                        );

                        $stmtNovoCultivo->execute();

                        $cultivoId =
                            $stmtNovoCultivo->insert_id;

                        $stmtNovoCultivo->close();
                    }

                    $stmtCultivo->close();


                    /*
                     * ------------------------
                     * ATUALIZA A PESQUISA
                     * ------------------------
                     */

                    $sqlAtualizar = "
                        UPDATE pesquisas
                        SET
                            status = ?,
                            observacao = ?,
                            administrador_id = ?,
                            solo_id = ?,
                            cultivo_id = ?,
                            dataAprovacao = NOW()
                        WHERE id = ?
                        AND status = 'Pendente'
                    ";

                    $stmtAtualizar =
                        $conn->prepare($sqlAtualizar);

                    $stmtAtualizar->bind_param(
                        "ssiiii",
                        $novoStatus,
                        $observacao,
                        $administradorId,
                        $soloId,
                        $cultivoId,
                        $pesquisaId
                    );

                    $stmtAtualizar->execute();


                    if ($stmtAtualizar->affected_rows !== 1) {

                        throw new Exception(
                            "Não foi possível aprovar a pesquisa."
                        );
                    }

                    $stmtAtualizar->close();


                    /*
                     * Confirma todas as alterações.
                     */
                    $conn->commit();

                    $mensagemSucesso =
                        "Pesquisa analisada com sucesso.";

                } catch (Throwable $erro) {

                    /*
                     * Se qualquer parte falhar,
                     * desfaz tudo.
                     */
                    $conn->rollback();

                    $mensagemErro =
                        "Não foi possível aprovar a pesquisa.";
                }


            /*
             * --------------------------------
             * REJEIÇÃO
             * --------------------------------
             */
            } else {

                $novoStatus = "Rejeitada";

                /*
                 * Pesquisa rejeitada NÃO adiciona
                 * solo nem cultivo aos catálogos.
                 */
                $sqlAtualizar = "
                    UPDATE pesquisas
                    SET
                        status = ?,
                        observacao = ?,
                        administrador_id = ?,
                        dataAprovacao = NULL
                    WHERE id = ?
                    AND status = 'Pendente'
                ";

                $stmtAtualizar =
                    $conn->prepare($sqlAtualizar);

                $stmtAtualizar->bind_param(
                    "ssii",
                    $novoStatus,
                    $observacao,
                    $administradorId,
                    $pesquisaId
                );

                $stmtAtualizar->execute();


                if ($stmtAtualizar->affected_rows === 1) {

                    $mensagemSucesso =
                        "Pesquisa analisada com sucesso.";

                } else {

                    $mensagemErro =
                        "Não foi possível rejeitar a pesquisa.";
                }

                $stmtAtualizar->close();
            }
        }
    }

    $stmtVerificar->close();
}


/*
 * Busca os dados atuais da pesquisa.
 */
$sqlPesquisa = "
    SELECT
        p.id,
        p.titulo,
        p.descricao,
        p.resumo,
        p.palavras_chave,
        p.link,
        p.solo_informado,
        p.cultivo_informado,
        p.status,
        p.observacao,
        p.dataEnvio,
        p.dataAprovacao,
        p.administrador_id,

        a.nome AS autor,

        r.nome AS regiao,

        u.nome AS pesquisador,
        u.email AS pesquisador_email

    FROM pesquisas p

    INNER JOIN autores a
        ON p.autor_id = a.id

    INNER JOIN regioes r
        ON p.regiao_id = r.id

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


$cssPagina = "pesquisas.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-pesquisa">

    <section class="analise-pesquisa">

        <div class="cabecalho-analise">

            <div>

                <h1>Analisar pesquisa</h1>

                <p>
                    Revise os dados da submissão antes de
                    aprovar ou rejeitar a publicação.
                </p>

            </div>

            <a
                href="pesquisas.php"
                class="botao-voltar"
            >
                Voltar
            </a>

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


        <?php if ($mensagemSucesso !== "") : ?>

            <div class="mensagem-sucesso">
                <?php
                echo htmlspecialchars(
                    $mensagemSucesso
                );
                ?>
            </div>

        <?php endif; ?>


        <article class="detalhes-analise">

            <div class="topo-submissao">

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $pesquisa["titulo"]
                    );
                    ?>
                </h2>

                <?php

                $classeStatus = "status-pendente";

                if ($pesquisa["status"] === "Aprovada") {
                    $classeStatus = "status-aprovada";
                }

                if ($pesquisa["status"] === "Rejeitada") {
                    $classeStatus = "status-rejeitada";
                }

                ?>

                <span
                    class="status-pesquisa <?php echo $classeStatus; ?>"
                >
                    <?php
                    echo htmlspecialchars(
                        $pesquisa["status"]
                    );
                    ?>
                </span>

            </div>


            <div class="dados-submissao">

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
                    <strong>E-mail do pesquisador:</strong>

                    <?php
                    echo htmlspecialchars(
                        $pesquisa["pesquisador_email"]
                    );
                    ?>
                </p>


                <p>
                    <strong>Região:</strong>

                    <?php
                    echo htmlspecialchars(
                        $pesquisa["regiao"]
                    );
                    ?>
                </p>


                <p>
                    <strong>Solo informado:</strong>

                    <?php
                    echo htmlspecialchars(
                        $pesquisa["solo_informado"]
                    );
                    ?>
                </p>


                <p>
                    <strong>Cultivo informado:</strong>

                    <?php
                    echo htmlspecialchars(
                        $pesquisa["cultivo_informado"]
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


            <div class="bloco-detalhes">

                <h3>Descrição</h3>

                <p>
                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $pesquisa["descricao"]
                        )
                    );
                    ?>
                </p>

            </div>


            <?php if (!empty($pesquisa["resumo"])) : ?>

                <div class="bloco-detalhes">

                    <h3>Resumo</h3>

                    <p>
                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $pesquisa["resumo"]
                            )
                        );
                        ?>
                    </p>

                </div>

            <?php endif; ?>


            <?php if (!empty($pesquisa["palavras_chave"])) : ?>

                <div class="bloco-detalhes">

                    <h3>Palavras-chave</h3>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $pesquisa["palavras_chave"]
                        );
                        ?>
                    </p>

                </div>

            <?php endif; ?>


            <?php if (!empty($pesquisa["link"])) : ?>

                <div class="bloco-detalhes">

                    <h3>Pesquisa original</h3>

                    <a
                        href="<?php
                        echo htmlspecialchars(
                            $pesquisa["link"]
                        );
                        ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Acessar link da pesquisa
                    </a>

                </div>

            <?php endif; ?>


            <?php if ($pesquisa["status"] === "Pendente") : ?>

                <form
                    method="POST"
                    class="formulario-analise"
                >

                    <div class="campo-formulario">

                        <label for="observacao">
                            Observação do administrador
                        </label>

                        <textarea
                            name="observacao"
                            id="observacao"
                            rows="5"
                            placeholder="Informe observações, correções solicitadas ou justificativa da decisão."
                        ></textarea>

                    </div>


                    <div class="botoes-analise">

                        <button
                            type="submit"
                            name="acao"
                            value="rejeitar"
                            class="botao-rejeitar"
                        >
                            Rejeitar pesquisa
                        </button>


                        <button
                            type="submit"
                            name="acao"
                            value="aprovar"
                            class="botao-aprovar"
                        >
                            Aprovar pesquisa
                        </button>

                    </div>

                </form>

            <?php else : ?>

                <div class="analise-finalizada">

                    <h3>Análise finalizada</h3>

                    <?php
                    if (!empty($pesquisa["observacao"])) :
                    ?>

                        <p>
                            <strong>Observação:</strong>
                        </p>

                        <p>
                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $pesquisa["observacao"]
                                )
                            );
                            ?>
                        </p>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </article>

    </section>

</main>

<?php

$stmtPesquisa->close();

require_once "../includes/footer.php";

?>