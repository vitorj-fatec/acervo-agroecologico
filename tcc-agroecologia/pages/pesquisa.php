<?php

require_once "../includes/verificacao.php";
require_once "../includes/conexao.php";


/*
 * Valida o ID da pesquisa recebido pela URL.
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
 * ID do usuário atualmente autenticado.
 * O usuário NÃO escolhe esse valor no formulário.
 */
$usuarioId = $_SESSION["usuario_id"];

$mensagemErro = "";
$mensagemSucesso = "";


/*
 * ----------------------------------------------------
 * VERIFICA SE A PESQUISA EXISTE E ESTÁ APROVADA
 * ----------------------------------------------------
 */
$sqlVerificarPesquisa = "
    SELECT id
    FROM pesquisas
    WHERE id = ?
    AND status = 'Aprovada'
    LIMIT 1
";

$stmtVerificarPesquisa =
    $conn->prepare($sqlVerificarPesquisa);

$stmtVerificarPesquisa->bind_param(
    "i",
    $pesquisaId
);

$stmtVerificarPesquisa->execute();

$resultadoVerificarPesquisa =
    $stmtVerificarPesquisa->get_result();


if ($resultadoVerificarPesquisa->num_rows !== 1) {

    $stmtVerificarPesquisa->close();

    header("Location: pesquisas.php");
    exit;
}

$stmtVerificarPesquisa->close();


/*
 * ----------------------------------------------------
 * PROCESSA A AVALIAÇÃO
 * ----------------------------------------------------
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nota = filter_input(
        INPUT_POST,
        "nota",
        FILTER_VALIDATE_INT
    );


    /*
     * A nota obrigatoriamente precisa
     * estar entre 1 e 5.
     */
    if (
        $nota === false ||
        $nota === null ||
        $nota < 1 ||
        $nota > 5
    ) {

        $mensagemErro =
            "Selecione uma nota entre 1 e 5 estrelas.";

    } else {

        /*
         * Verifica se o usuário já avaliou
         * esta pesquisa.
         */
        $sqlVerificarAvaliacao = "
            SELECT id
            FROM avaliacoes_pesquisas
            WHERE usuario_id = ?
            AND pesquisa_id = ?
            LIMIT 1
        ";

        $stmtVerificarAvaliacao =
            $conn->prepare($sqlVerificarAvaliacao);

        $stmtVerificarAvaliacao->bind_param(
            "ii",
            $usuarioId,
            $pesquisaId
        );

        $stmtVerificarAvaliacao->execute();

        $resultadoAvaliacao =
            $stmtVerificarAvaliacao->get_result();


        /*
         * Se já existe avaliação,
         * atualizamos a nota.
         */
        if ($resultadoAvaliacao->num_rows === 1) {

            $sqlAtualizar = "
                UPDATE avaliacoes_pesquisas
                SET
                    nota = ?,
                    dataAvaliacao = CURRENT_TIMESTAMP
                WHERE usuario_id = ?
                AND pesquisa_id = ?
            ";

            $stmtAtualizar =
                $conn->prepare($sqlAtualizar);

            $stmtAtualizar->bind_param(
                "iii",
                $nota,
                $usuarioId,
                $pesquisaId
            );

            if ($stmtAtualizar->execute()) {

                $stmtAtualizar->close();
                $stmtVerificarAvaliacao->close();

                /*
                 * Redirecionamento depois do POST
                 * evita reenvio do formulário.
                 */
                header(
                    "Location: pesquisa.php?id=" .
                    $pesquisaId .
                    "&avaliacao=atualizada"
                );

                exit;

            } else {

                $mensagemErro =
                    "Não foi possível atualizar sua avaliação.";
            }

            $stmtAtualizar->close();


        /*
         * Se ainda não existe,
         * criamos uma nova avaliação.
         */
        } else {

            $sqlInserir = "
                INSERT INTO avaliacoes_pesquisas (
                    usuario_id,
                    pesquisa_id,
                    nota
                )
                VALUES (?, ?, ?)
            ";

            $stmtInserir =
                $conn->prepare($sqlInserir);

            $stmtInserir->bind_param(
                "iii",
                $usuarioId,
                $pesquisaId,
                $nota
            );

            if ($stmtInserir->execute()) {

                $stmtInserir->close();
                $stmtVerificarAvaliacao->close();

                header(
                    "Location: pesquisa.php?id=" .
                    $pesquisaId .
                    "&avaliacao=sucesso"
                );

                exit;

            } else {

                $mensagemErro =
                    "Não foi possível registrar sua avaliação.";
            }

            $stmtInserir->close();
        }

        $stmtVerificarAvaliacao->close();
    }
}


/*
 * Mensagem depois do redirecionamento.
 */
if (
    isset($_GET["avaliacao"]) &&
    $_GET["avaliacao"] === "sucesso"
) {

    $mensagemSucesso =
        "Avaliação registrada com sucesso.";
}


if (
    isset($_GET["avaliacao"]) &&
    $_GET["avaliacao"] === "atualizada"
) {

    $mensagemSucesso =
        "Sua avaliação foi atualizada com sucesso.";
}


/*
 * ----------------------------------------------------
 * BUSCA OS DADOS COMPLETOS DA PESQUISA
 * ----------------------------------------------------
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
        a.instituicao AS autor_instituicao,
        a.biografia AS autor_biografia,

        r.nome AS regiao,

        COALESCE(AVG(av.nota), 0) AS media_avaliacao,
        COUNT(av.id) AS quantidade_avaliacoes

    FROM pesquisas p

    INNER JOIN autores a
        ON p.autor_id = a.id

    INNER JOIN regioes r
        ON p.regiao_id = r.id

    LEFT JOIN avaliacoes_pesquisas av
        ON av.pesquisa_id = p.id

    WHERE p.id = ?
    AND p.status = 'Aprovada'

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
        a.instituicao,
        a.biografia,
        r.nome

    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $pesquisaId
);

$stmt->execute();

$resultado = $stmt->get_result();


if ($resultado->num_rows !== 1) {

    $stmt->close();

    header("Location: pesquisas.php");
    exit;
}


$pesquisa = $resultado->fetch_assoc();


/*
 * Formata a média.
 */
$mediaAvaliacao = number_format(
    (float)$pesquisa["media_avaliacao"],
    1,
    ",",
    "."
);


/*
 * ----------------------------------------------------
 * BUSCA A NOTA DO USUÁRIO LOGADO
 * ----------------------------------------------------
 */
$sqlMinhaAvaliacao = "
    SELECT nota
    FROM avaliacoes_pesquisas
    WHERE usuario_id = ?
    AND pesquisa_id = ?
    LIMIT 1
";

$stmtMinhaAvaliacao =
    $conn->prepare($sqlMinhaAvaliacao);

$stmtMinhaAvaliacao->bind_param(
    "ii",
    $usuarioId,
    $pesquisaId
);

$stmtMinhaAvaliacao->execute();

$resultadoMinhaAvaliacao =
    $stmtMinhaAvaliacao->get_result();

$minhaNota = 0;


if ($resultadoMinhaAvaliacao->num_rows === 1) {

    $avaliacaoUsuario =
        $resultadoMinhaAvaliacao->fetch_assoc();

    $minhaNota =
        (int)$avaliacaoUsuario["nota"];
}


$cssPagina = "pesquisas.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-pesquisa">

    <section class="detalhes-pesquisa-publica">

        <div class="cabecalho-detalhes-pesquisa">

            <div>

                <h1>
                    <?php
                    echo htmlspecialchars(
                        $pesquisa["titulo"]
                    );
                    ?>
                </h1>

                <p>
                    Pesquisa disponível no Acervo Agroecológico.
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


        <article class="conteudo-pesquisa">


            <div class="informacoes-pesquisa">

                <p>
                    <strong>Autor:</strong>

                    <?php
                    echo htmlspecialchars(
                        $pesquisa["autor"]
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
                    <strong>Tipo de solo:</strong>

                    <?php
                    echo htmlspecialchars(
                        $pesquisa["solo_informado"]
                    );
                    ?>
                </p>


                <p>
                    <strong>Tipo de cultivo:</strong>

                    <?php
                    echo htmlspecialchars(
                        $pesquisa["cultivo_informado"]
                    );
                    ?>
                </p>


                <?php
                if (!empty($pesquisa["dataAprovacao"])) :
                ?>

                    <p>

                        <strong>
                            Data de aprovação:
                        </strong>

                        <?php
                        echo date(
                            "d/m/Y",
                            strtotime(
                                $pesquisa["dataAprovacao"]
                            )
                        );
                        ?>

                    </p>

                <?php endif; ?>

            </div>


            <!-- MÉDIA DAS AVALIAÇÕES -->

            <div class="avaliacao-detalhes">

                <span class="nota-pesquisa">

                    ★ <?php echo $mediaAvaliacao; ?>

                </span>

                <span>

                    <?php
                    echo (int)$pesquisa[
                        "quantidade_avaliacoes"
                    ];
                    ?>

                    avaliações

                </span>

            </div>


            <!-- AVALIAÇÃO DO USUÁRIO -->

            <div class="area-avaliacao">

                <h2>Avalie esta pesquisa</h2>

                <?php if ($minhaNota > 0) : ?>

                    <p>
                        Sua avaliação atual:
                        <strong>
                            <?php echo $minhaNota; ?>
                            estrela<?php
                            echo $minhaNota > 1
                                ? "s"
                                : "";
                            ?>
                        </strong>
                    </p>

                <?php else : ?>

                    <p>
                        Você ainda não avaliou esta pesquisa.
                    </p>

                <?php endif; ?>


                <form
                    method="POST"
                    class="formulario-estrelas"
                >

                    <div class="opcoes-estrelas">

                        <?php for ($i = 5; $i >= 1; $i--) : ?>

                            <input
                                type="radio"
                                name="nota"
                                id="nota-<?php echo $i; ?>"
                                value="<?php echo $i; ?>"
                                <?php
                                echo $minhaNota === $i
                                    ? "checked"
                                    : "";
                                ?>
                                required
                            >

                            <label
                                for="nota-<?php echo $i; ?>"
                                class="opcao-estrela"
                                title="<?php echo $i; ?> estrela<?php echo $i > 1 ? 's' : ''; ?>"
                                aria-label="<?php echo $i; ?> estrela<?php echo $i > 1 ? 's' : ''; ?>"
                            >
                                ★
                            </label>

                        <?php endfor; ?>

                    </div>


                    <button
                        type="submit"
                        class="botao-avaliar"
                    >

                        <?php
                        echo $minhaNota > 0
                            ? "Atualizar avaliação"
                            : "Enviar avaliação";
                        ?>

                    </button>

                </form>

            </div>


            <div class="bloco-detalhes">

                <h2>Descrição</h2>

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

                    <h2>Resumo</h2>

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


            <?php
            if (!empty($pesquisa["palavras_chave"])) :
            ?>

                <div class="bloco-detalhes">

                    <h2>Palavras-chave</h2>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $pesquisa["palavras_chave"]
                        );
                        ?>
                    </p>

                </div>

            <?php endif; ?>


            <?php
            if (!empty($pesquisa["autor_instituicao"])) :
            ?>

                <div class="bloco-detalhes">

                    <h2>Instituição do autor</h2>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $pesquisa["autor_instituicao"]
                        );
                        ?>
                    </p>

                </div>

            <?php endif; ?>


            <?php
            if (!empty($pesquisa["autor_biografia"])) :
            ?>

                <div class="bloco-detalhes">

                    <h2>Sobre o autor</h2>

                    <p>
                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $pesquisa["autor_biografia"]
                            )
                        );
                        ?>
                    </p>

                </div>

            <?php endif; ?>


            <?php if (!empty($pesquisa["link"])) : ?>

                <div class="bloco-detalhes">

                    <h2>
                        Acessar pesquisa original
                    </h2>

                    <a
                        href="<?php
                        echo htmlspecialchars(
                            $pesquisa["link"]
                        );
                        ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="botao-link-pesquisa"
                    >
                        Acessar pesquisa externa
                    </a>

                </div>

            <?php endif; ?>

        </article>

    </section>

</main>

<?php

$stmtMinhaAvaliacao->close();
$stmt->close();

require_once "../includes/footer.php";

?>