<?php

require_once "../includes/verificacao.php";
require_once "../includes/conexao.php";

$usuarioId = $_SESSION["usuario_id"];

$mensagemErro = "";
$mensagemSucesso = "";


/*
 * Verifica se o usuário já possui
 * uma avaliação cadastrada.
 */
$sqlAvaliacao = "
    SELECT
        id,
        nota,
        facilidadeNavegacao,
        facilidadeBusca,
        clarezaInformacoes,
        comentario,
        dataAvaliacao

    FROM avaliacoes_site

    WHERE usuario_id = ?

    LIMIT 1
";

$stmtAvaliacao =
    $conn->prepare($sqlAvaliacao);

$stmtAvaliacao->bind_param(
    "i",
    $usuarioId
);

$stmtAvaliacao->execute();

$resultadoAvaliacao =
    $stmtAvaliacao->get_result();

$avaliacaoExistente = null;

if ($resultadoAvaliacao->num_rows === 1) {

    $avaliacaoExistente =
        $resultadoAvaliacao->fetch_assoc();
}

$stmtAvaliacao->close();


/*
 * Processa envio ou atualização.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nota =
        filter_input(
            INPUT_POST,
            "nota",
            FILTER_VALIDATE_INT
        );

    $facilidadeNavegacao =
        filter_input(
            INPUT_POST,
            "facilidadeNavegacao",
            FILTER_VALIDATE_INT
        );

    $facilidadeBusca =
        filter_input(
            INPUT_POST,
            "facilidadeBusca",
            FILTER_VALIDATE_INT
        );

    $clarezaInformacoes =
        filter_input(
            INPUT_POST,
            "clarezaInformacoes",
            FILTER_VALIDATE_INT
        );

    $comentario =
        trim($_POST["comentario"] ?? "");


    /*
     * Todas as notas devem estar
     * entre 1 e 5.
     */
    $notas = [
        $nota,
        $facilidadeNavegacao,
        $facilidadeBusca,
        $clarezaInformacoes
    ];

    $notasValidas = true;

    foreach ($notas as $valor) {

        if (
            $valor === false ||
            $valor === null ||
            $valor < 1 ||
            $valor > 5
        ) {

            $notasValidas = false;
            break;
        }
    }


    if (!$notasValidas) {

        $mensagemErro =
            "Todas as avaliações devem possuir uma nota entre 1 e 5.";

    } else {

        /*
         * Como usuario_id possui restrição UNIQUE:
         *
         * primeira avaliação -> INSERT
         * avaliações seguintes -> UPDATE
         */
        $sqlSalvar = "
            INSERT INTO avaliacoes_site (
                usuario_id,
                nota,
                facilidadeNavegacao,
                facilidadeBusca,
                clarezaInformacoes,
                comentario
            )
            VALUES (?, ?, ?, ?, ?, ?)

            ON DUPLICATE KEY UPDATE
                nota = VALUES(nota),
                facilidadeNavegacao =
                    VALUES(facilidadeNavegacao),
                facilidadeBusca =
                    VALUES(facilidadeBusca),
                clarezaInformacoes =
                    VALUES(clarezaInformacoes),
                comentario =
                    VALUES(comentario),
                dataAvaliacao =
                    CURRENT_TIMESTAMP
        ";

        $stmtSalvar =
            $conn->prepare($sqlSalvar);

        $stmtSalvar->bind_param(
            "iiiiis",
            $usuarioId,
            $nota,
            $facilidadeNavegacao,
            $facilidadeBusca,
            $clarezaInformacoes,
            $comentario
        );


        if ($stmtSalvar->execute()) {

            $mensagemSucesso =
                "Avaliação registrada com sucesso.";

            /*
             * Recarrega a avaliação
             * depois de salvar.
             */
            $sqlRecarregar = "
                SELECT
                    id,
                    nota,
                    facilidadeNavegacao,
                    facilidadeBusca,
                    clarezaInformacoes,
                    comentario,
                    dataAvaliacao

                FROM avaliacoes_site

                WHERE usuario_id = ?

                LIMIT 1
            ";

            $stmtRecarregar =
                $conn->prepare($sqlRecarregar);

            $stmtRecarregar->bind_param(
                "i",
                $usuarioId
            );

            $stmtRecarregar->execute();

            $resultadoRecarregar =
                $stmtRecarregar->get_result();

            $avaliacaoExistente =
                $resultadoRecarregar
                    ->fetch_assoc();

            $stmtRecarregar->close();

        } else {

            $mensagemErro =
                "Não foi possível registrar sua avaliação.";
        }

        $stmtSalvar->close();
    }
}


$cssPagina = "avaliarSite.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-avaliacao-site">

    <section class="conteudo-avaliacao-site">

        <div class="cabecalho-avaliacao-site">

            <h1>
                Avalie o Acervo Agroecológico
            </h1>

            <p>
                Sua avaliação contribui para identificar
                pontos de melhoria na plataforma.
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


        <?php if ($mensagemSucesso !== "") : ?>

            <div class="mensagem-sucesso">

                <?php
                echo htmlspecialchars(
                    $mensagemSucesso
                );
                ?>

            </div>

        <?php endif; ?>


        <?php if ($avaliacaoExistente !== null) : ?>

            <div class="aviso-avaliacao-existente">

                <strong>
                    Você já avaliou a plataforma.
                </strong>

                <p>
                    Ao enviar novamente, sua avaliação
                    anterior será atualizada.
                </p>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            class="formulario-avaliacao-site"
        >


            <!-- NOTA GERAL -->

            <div class="campo-avaliacao">

                <label>
                    Nota geral da plataforma
                </label>

                <div class="avaliacao-estrelas">

                    <?php
                    for ($i = 5; $i >= 1; $i--) :
                    ?>

                        <input
                            type="radio"
                            name="nota"
                            id="nota_<?php echo $i; ?>"
                            value="<?php echo $i; ?>"
                            required
                            <?php
                            echo (
                                $avaliacaoExistente !== null &&
                                (int)$avaliacaoExistente["nota"] === $i
                            )
                                ? "checked"
                                : "";
                            ?>
                        >

                        <label
                            for="nota_<?php echo $i; ?>"
                            title="<?php echo $i; ?> estrela(s)"
                        >
                            ★
                        </label>

                    <?php endfor; ?>

                </div>

            </div>


            <!-- FACILIDADE DE NAVEGAÇÃO -->

            <div class="campo-avaliacao">

                <label>
                    Facilidade de navegação
                </label>

                <div class="avaliacao-estrelas">

                    <?php
                    for ($i = 5; $i >= 1; $i--) :
                    ?>

                        <input
                            type="radio"
                            name="facilidadeNavegacao"
                            id="navegacao_<?php echo $i; ?>"
                            value="<?php echo $i; ?>"
                            required
                            <?php
                            echo (
                                $avaliacaoExistente !== null &&
                                (int)$avaliacaoExistente[
                                    "facilidadeNavegacao"
                                ] === $i
                            )
                                ? "checked"
                                : "";
                            ?>
                        >

                        <label
                            for="navegacao_<?php echo $i; ?>"
                            title="<?php echo $i; ?> estrela(s)"
                        >
                            ★
                        </label>

                    <?php endfor; ?>

                </div>

            </div>


            <!-- FACILIDADE DE BUSCA -->

            <div class="campo-avaliacao">

                <label>
                    Facilidade de busca
                </label>

                <div class="avaliacao-estrelas">

                    <?php
                    for ($i = 5; $i >= 1; $i--) :
                    ?>

                        <input
                            type="radio"
                            name="facilidadeBusca"
                            id="busca_<?php echo $i; ?>"
                            value="<?php echo $i; ?>"
                            required
                            <?php
                            echo (
                                $avaliacaoExistente !== null &&
                                (int)$avaliacaoExistente[
                                    "facilidadeBusca"
                                ] === $i
                            )
                                ? "checked"
                                : "";
                            ?>
                        >

                        <label
                            for="busca_<?php echo $i; ?>"
                            title="<?php echo $i; ?> estrela(s)"
                        >
                            ★
                        </label>

                    <?php endfor; ?>

                </div>

            </div>


            <!-- CLAREZA DAS INFORMAÇÕES -->

            <div class="campo-avaliacao">

                <label>
                    Clareza das informações
                </label>

                <div class="avaliacao-estrelas">

                    <?php
                    for ($i = 5; $i >= 1; $i--) :
                    ?>

                        <input
                            type="radio"
                            name="clarezaInformacoes"
                            id="clareza_<?php echo $i; ?>"
                            value="<?php echo $i; ?>"
                            required
                            <?php
                            echo (
                                $avaliacaoExistente !== null &&
                                (int)$avaliacaoExistente[
                                    "clarezaInformacoes"
                                ] === $i
                            )
                                ? "checked"
                                : "";
                            ?>
                        >

                        <label
                            for="clareza_<?php echo $i; ?>"
                            title="<?php echo $i; ?> estrela(s)"
                        >
                            ★
                        </label>

                    <?php endfor; ?>

                </div>

            </div>


            <!-- COMENTÁRIO -->

            <div class="campo-avaliacao">

                <label for="comentario">
                    Comentário
                </label>

                <textarea
                    name="comentario"
                    id="comentario"
                    rows="7"
                    placeholder="Conte-nos sua experiência com a plataforma."
                ><?php
                echo htmlspecialchars(
                    $avaliacaoExistente[
                        "comentario"
                    ] ?? ""
                );
                ?></textarea>

            </div>


            <button
                type="submit"
                class="botao-avaliar-site"
            >

                <?php
                echo $avaliacaoExistente !== null
                    ? "Atualizar avaliação"
                    : "Enviar avaliação";
                ?>

            </button>

        </form>

    </section>

</main>

<?php

require_once "../includes/footer.php";

?>