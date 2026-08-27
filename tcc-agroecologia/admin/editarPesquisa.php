
<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["administrador"]);

$mensagemErro = "";
$mensagemSucesso = "";


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
 * Carrega autores.
 */
$listaAutores = $conn->query("
    SELECT id, nome
    FROM autores
    ORDER BY nome
");


/*
 * Carrega regiões.
 */
$listaRegioes = $conn->query("
    SELECT id, nome
    FROM regioes
    ORDER BY nome
");


/*
 * Carrega catálogo de solos.
 */
$listaSolos = $conn->query("
    SELECT id, nome
    FROM tipos_solo
    ORDER BY nome
");


/*
 * Carrega catálogo de cultivos.
 */
$listaCultivos = $conn->query("
    SELECT id, nome
    FROM tipos_cultivo
    ORDER BY nome
");


/*
 * ----------------------------------------------------
 * PROCESSA A EDIÇÃO
 * ----------------------------------------------------
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo =
        trim($_POST["titulo"] ?? "");

    $autorId =
        filter_input(
            INPUT_POST,
            "autor_id",
            FILTER_VALIDATE_INT
        );

    $descricao =
        trim($_POST["descricao"] ?? "");

    $resumo =
        trim($_POST["resumo"] ?? "");

    $palavrasChave =
        trim($_POST["palavras_chave"] ?? "");

    $regiaoId =
        filter_input(
            INPUT_POST,
            "regiao_id",
            FILTER_VALIDATE_INT
        );

    $soloInformado =
        trim($_POST["solo_informado"] ?? "");

    $cultivoInformado =
        trim($_POST["cultivo_informado"] ?? "");

    $link =
        trim($_POST["link"] ?? "");


    /*
     * Validação básica.
     */
    if (
        $titulo === "" ||
        !$autorId ||
        $descricao === "" ||
        !$regiaoId ||
        $soloInformado === "" ||
        $cultivoInformado === ""
    ) {

        $mensagemErro =
            "Preencha todos os campos obrigatórios.";

    } elseif (
        $link !== "" &&
        !filter_var(
            $link,
            FILTER_VALIDATE_URL
        )
    ) {

        $mensagemErro =
            "Informe um link válido.";

    } else {

        /*
         * Primeiro tentamos localizar o solo
         * no catálogo oficial.
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

            $dadosSolo =
                $resultadoSolo->fetch_assoc();

            $soloId =
                (int)$dadosSolo["id"];
        }

        $stmtSolo->close();


        /*
         * Localiza cultivo.
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

            $dadosCultivo =
                $resultadoCultivo->fetch_assoc();

            $cultivoId =
                (int)$dadosCultivo["id"];
        }

        $stmtCultivo->close();


        /*
         * Atualiza somente os dados de conteúdo.
         */
        $sqlAtualizar = "
            UPDATE pesquisas
            SET
                titulo = ?,
                descricao = ?,
                resumo = ?,
                palavras_chave = ?,
                link = ?,
                autor_id = ?,
                regiao_id = ?,
                solo_informado = ?,
                solo_id = ?,
                cultivo_informado = ?,
                cultivo_id = ?
            WHERE id = ?
        ";

        $stmtAtualizar =
            $conn->prepare($sqlAtualizar);

        $stmtAtualizar->bind_param(
            "sssssiisisii",
            $titulo,
            $descricao,
            $resumo,
            $palavrasChave,
            $link,
            $autorId,
            $regiaoId,
            $soloInformado,
            $soloId,
            $cultivoInformado,
            $cultivoId,
            $pesquisaId
        );

        if ($stmtAtualizar->execute()) {

            $mensagemSucesso =
                "Pesquisa atualizada com sucesso.";

        } else {

            $mensagemErro =
                "Não foi possível atualizar a pesquisa.";
        }

        $stmtAtualizar->close();
    }
}


/*
 * ----------------------------------------------------
 * BUSCA OS DADOS ATUAIS DA PESQUISA
 * ----------------------------------------------------
 */
$sqlPesquisa = "
    SELECT
        id,
        titulo,
        descricao,
        resumo,
        palavras_chave,
        link,
        autor_id,
        regiao_id,
        solo_informado,
        solo_id,
        cultivo_informado,
        cultivo_id,
        status

    FROM pesquisas

    WHERE id = ?

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

                <h1>Editar pesquisa</h1>

                <p>
                    Atualize os dados de conteúdo da pesquisa.
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


        <form
            method="POST"
            class="formulario-edicao-pesquisa"
        >

            <div class="campo-formulario">

                <label for="titulo">
                    Título *
                </label>

                <input
                    type="text"
                    name="titulo"
                    id="titulo"
                    value="<?php
                    echo htmlspecialchars(
                        $pesquisa["titulo"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="campo-formulario">

                <label for="autor_id">
                    Autor *
                </label>

                <select
                    name="autor_id"
                    id="autor_id"
                    required
                >

                    <?php
                    while (
                        $autor =
                        $listaAutores->fetch_assoc()
                    ) :
                    ?>

                        <option
                            value="<?php
                            echo $autor["id"];
                            ?>"
                            <?php
                            echo (
                                (int)$pesquisa["autor_id"] ===
                                (int)$autor["id"]
                            )
                                ? "selected"
                                : "";
                            ?>
                        >
                            <?php
                            echo htmlspecialchars(
                                $autor["nome"]
                            );
                            ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <div class="campo-formulario">

                <label for="descricao">
                    Descrição *
                </label>

                <textarea
                    name="descricao"
                    id="descricao"
                    rows="6"
                    required
                ><?php
                echo htmlspecialchars(
                    $pesquisa["descricao"]
                );
                ?></textarea>

            </div>


            <div class="campo-formulario">

                <label for="resumo">
                    Resumo
                </label>

                <textarea
                    name="resumo"
                    id="resumo"
                    rows="5"
                ><?php
                echo htmlspecialchars(
                    $pesquisa["resumo"]
                );
                ?></textarea>

            </div>


            <div class="campo-formulario">

                <label for="palavras_chave">
                    Palavras-chave
                </label>

                <input
                    type="text"
                    name="palavras_chave"
                    id="palavras_chave"
                    value="<?php
                    echo htmlspecialchars(
                        $pesquisa[
                            "palavras_chave"
                        ]
                    );
                    ?>"
                >

            </div>


            <div class="campo-formulario">

                <label for="regiao_id">
                    Região *
                </label>

                <select
                    name="regiao_id"
                    id="regiao_id"
                    required
                >

                    <?php
                    while (
                        $regiao =
                        $listaRegioes->fetch_assoc()
                    ) :
                    ?>

                        <option
                            value="<?php
                            echo $regiao["id"];
                            ?>"
                            <?php
                            echo (
                                (int)$pesquisa["regiao_id"] ===
                                (int)$regiao["id"]
                            )
                                ? "selected"
                                : "";
                            ?>
                        >
                            <?php
                            echo htmlspecialchars(
                                $regiao["nome"]
                            );
                            ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <div class="campo-formulario">

                <label for="solo_informado">
                    Tipo de solo *
                </label>

                <input
                    type="text"
                    name="solo_informado"
                    id="solo_informado"
                    list="lista-solos"
                    value="<?php
                    echo htmlspecialchars(
                        $pesquisa[
                            "solo_informado"
                        ]
                    );
                    ?>"
                    required
                >

                <datalist id="lista-solos">

                    <?php
                    while (
                        $solo =
                        $listaSolos->fetch_assoc()
                    ) :
                    ?>

                        <option
                            value="<?php
                            echo htmlspecialchars(
                                $solo["nome"]
                            );
                            ?>"
                        >

                    <?php endwhile; ?>

                </datalist>

            </div>


            <div class="campo-formulario">

                <label for="cultivo_informado">
                    Tipo de cultivo *
                </label>

                <input
                    type="text"
                    name="cultivo_informado"
                    id="cultivo_informado"
                    list="lista-cultivos"
                    value="<?php
                    echo htmlspecialchars(
                        $pesquisa[
                            "cultivo_informado"
                        ]
                    );
                    ?>"
                    required
                >

                <datalist id="lista-cultivos">

                    <?php
                    while (
                        $cultivo =
                        $listaCultivos->fetch_assoc()
                    ) :
                    ?>

                        <option
                            value="<?php
                            echo htmlspecialchars(
                                $cultivo["nome"]
                            );
                            ?>"
                        >

                    <?php endwhile; ?>

                </datalist>

            </div>


            <div class="campo-formulario">

                <label for="link">
                    Link da pesquisa
                </label>

                <input
                    type="url"
                    name="link"
                    id="link"
                    value="<?php
                    echo htmlspecialchars(
                        $pesquisa["link"]
                    );
                    ?>"
                    placeholder="https://..."
                >

            </div>


            <div class="informacao-status-edicao">

                <strong>Status atual:</strong>

                <?php
                echo htmlspecialchars(
                    $pesquisa["status"]
                );
                ?>

            </div>


            <button
                type="submit"
                class="botao-salvar-edicao"
            >
                Salvar alterações
            </button>

        </form>

    </section>

</main>

<?php

$stmtPesquisa->close();

require_once "../includes/footer.php";

?>