<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["administrador"]);

$mensagemErro = "";
$mensagemSucesso = "";


/*
 * Valida o ID do autor.
 */
$autorId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$autorId) {
    header("Location: autores.php");
    exit;
}


/*
 * Busca dados atuais do autor.
 */
$sqlAutor = "
    SELECT
        id,
        usuario_id,
        nome,
        foto,
        biografia,
        instituicao

    FROM autores

    WHERE id = ?

    LIMIT 1
";

$stmtAutor = $conn->prepare($sqlAutor);

$stmtAutor->bind_param(
    "i",
    $autorId
);

$stmtAutor->execute();

$resultadoAutor =
    $stmtAutor->get_result();


if ($resultadoAutor->num_rows !== 1) {

    $stmtAutor->close();

    header("Location: autores.php");
    exit;
}


$autor =
    $resultadoAutor->fetch_assoc();

$stmtAutor->close();


/*
 * ----------------------------------------------------
 * PROCESSA A EDIÇÃO
 * ----------------------------------------------------
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome =
        trim($_POST["nome"] ?? "");

    $instituicao =
        trim($_POST["instituicao"] ?? "");

    $biografia =
        trim($_POST["biografia"] ?? "");

    $usuarioIdSelecionado =
        $_POST["usuario_id"] ?? "";

    /*
     * Converte vazio para NULL.
     */
    if ($usuarioIdSelecionado === "") {

        $usuarioIdSelecionado = null;

    } else {

        $usuarioIdSelecionado =
            filter_var(
                $usuarioIdSelecionado,
                FILTER_VALIDATE_INT
            );

        if (!$usuarioIdSelecionado) {

            $mensagemErro =
                "Conta de pesquisador inválida.";
        }
    }


    if ($nome === "") {

        $mensagemErro =
            "Informe o nome do autor.";
    }


    /*
     * Verifica se a conta selecionada
     * realmente pertence a um pesquisador.
     */
    if (
        $mensagemErro === "" &&
        $usuarioIdSelecionado !== null
    ) {

        $sqlUsuario = "
            SELECT id
            FROM usuarios
            WHERE
                id = ?
                AND tipo = 'pesquisador'
            LIMIT 1
        ";

        $stmtUsuario =
            $conn->prepare($sqlUsuario);

        $stmtUsuario->bind_param(
            "i",
            $usuarioIdSelecionado
        );

        $stmtUsuario->execute();

        $resultadoUsuario =
            $stmtUsuario->get_result();


        if ($resultadoUsuario->num_rows !== 1) {

            $mensagemErro =
                "A conta selecionada não pertence a um pesquisador.";
        }

        $stmtUsuario->close();
    }


    /*
     * Verifica se a conta já está vinculada
     * a outro autor.
     */
    if (
        $mensagemErro === "" &&
        $usuarioIdSelecionado !== null
    ) {

        $sqlVinculo = "
            SELECT id
            FROM autores
            WHERE
                usuario_id = ?
                AND id <> ?
            LIMIT 1
        ";

        $stmtVinculo =
            $conn->prepare($sqlVinculo);

        $stmtVinculo->bind_param(
            "ii",
            $usuarioIdSelecionado,
            $autorId
        );

        $stmtVinculo->execute();

        $resultadoVinculo =
            $stmtVinculo->get_result();


        if ($resultadoVinculo->num_rows > 0) {

            $mensagemErro =
                "Essa conta de pesquisador já está vinculada a outro autor.";
        }

        $stmtVinculo->close();
    }


    /*
     * Foto atual.
     */
    $fotoAutor =
        $autor["foto"] ?? null;


    /*
     * Processa uma nova foto.
     */
    if (
        $mensagemErro === "" &&
        isset($_FILES["foto"]) &&
        $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        $arquivo =
            $_FILES["foto"];


        if ($arquivo["error"] !== UPLOAD_ERR_OK) {

            $mensagemErro =
                "Não foi possível enviar a imagem.";

        } elseif (
            $arquivo["size"] >
            5 * 1024 * 1024
        ) {

            $mensagemErro =
                "A imagem deve ter no máximo 5 MB.";

        } else {

            $finfo =
                new finfo(
                    FILEINFO_MIME_TYPE
                );

            $tipoMime =
                $finfo->file(
                    $arquivo["tmp_name"]
                );


            $tiposPermitidos = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];


            if (
                !isset(
                    $tiposPermitidos[$tipoMime]
                )
            ) {

                $mensagemErro =
                    "Envie uma imagem JPG, PNG ou WEBP.";

            } else {

                $extensao =
                    $tiposPermitidos[$tipoMime];


                $nomeArquivo =
                    "autor_" .
                    $autorId .
                    "_" .
                    time() .
                    "." .
                    $extensao;


                $caminhoFisico =
                    "../images/autores/" .
                    $nomeArquivo;


                if (
                    move_uploaded_file(
                        $arquivo["tmp_name"],
                        $caminhoFisico
                    )
                ) {

                    /*
                     * Caminho salvo no banco.
                     */
                    $fotoAutor =
                        "../images/autores/" .
                        $nomeArquivo;

                } else {

                    $mensagemErro =
                        "Não foi possível salvar a imagem.";
                }
            }
        }
    }


    /*
     * Atualiza os dados.
     */
    if ($mensagemErro === "") {

        $sqlAtualizar = "
            UPDATE autores
            SET
                usuario_id = ?,
                nome = ?,
                foto = ?,
                biografia = ?,
                instituicao = ?
            WHERE id = ?
        ";

        $stmtAtualizar =
            $conn->prepare(
                $sqlAtualizar
            );


        /*
         * Como usuario_id pode ser NULL,
         * tratamos separadamente.
         */
        if ($usuarioIdSelecionado === null) {

            $sqlAtualizar = "
                UPDATE autores
                SET
                    usuario_id = NULL,
                    nome = ?,
                    foto = ?,
                    biografia = ?,
                    instituicao = ?
                WHERE id = ?
            ";

            $stmtAtualizar =
                $conn->prepare(
                    $sqlAtualizar
                );

            $stmtAtualizar->bind_param(
                "ssssi",
                $nome,
                $fotoAutor,
                $biografia,
                $instituicao,
                $autorId
            );

        } else {

            $stmtAtualizar->bind_param(
                "issssi",
                $usuarioIdSelecionado,
                $nome,
                $fotoAutor,
                $biografia,
                $instituicao,
                $autorId
            );
        }


        if ($stmtAtualizar->execute()) {

            $mensagemSucesso =
                "Autor atualizado com sucesso.";

        } else {

            $mensagemErro =
                "Não foi possível atualizar o autor.";
        }

        $stmtAtualizar->close();


        /*
         * Atualiza os dados locais depois
         * de salvar.
         */
        if ($mensagemErro === "") {

            $sqlRecarregar = "
                SELECT
                    id,
                    usuario_id,
                    nome,
                    foto,
                    biografia,
                    instituicao

                FROM autores

                WHERE id = ?

                LIMIT 1
            ";

            $stmtRecarregar =
                $conn->prepare(
                    $sqlRecarregar
                );

            $stmtRecarregar->bind_param(
                "i",
                $autorId
            );

            $stmtRecarregar->execute();

            $resultadoRecarregar =
                $stmtRecarregar->get_result();

            $autor =
                $resultadoRecarregar
                    ->fetch_assoc();

            $stmtRecarregar->close();
        }
    }
}


/*
 * ----------------------------------------------------
 * BUSCA CONTAS DE PESQUISADORES
 * ----------------------------------------------------
 *
 * Exibe:
 * - a conta atualmente vinculada;
 * - contas ainda não vinculadas a nenhum autor.
 */
$sqlPesquisadores = "
    SELECT
        u.id,
        u.nome,
        u.email,
        u.instituicao

    FROM usuarios u

    LEFT JOIN autores a
        ON a.usuario_id = u.id

    WHERE
        u.tipo = 'pesquisador'
        AND (
            a.id IS NULL
            OR a.id = ?
        )

    ORDER BY u.nome
";

$stmtPesquisadores =
    $conn->prepare(
        $sqlPesquisadores
    );

$stmtPesquisadores->bind_param(
    "i",
    $autorId
);

$stmtPesquisadores->execute();

$resultadoPesquisadores =
    $stmtPesquisadores
        ->get_result();


$cssPagina = "autoresAdmin.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-autores-admin">

    <section class="conteudo-editar-autor">

        <div class="cabecalho-editar-autor">

            <div>

                <h1>Editar autor</h1>

                <p>
                    Atualize as informações públicas
                    deste autor.
                </p>

            </div>


            <a
                href="autores.php"
                class="botao-voltar-autor"
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


        <div class="edicao-autor-grid">

            <aside class="visualizacao-autor-admin">

                <?php
                if (!empty($autor["foto"])) :
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
                        class="preview-foto-autor"
                    >

                <?php else : ?>

                    <div class="preview-sem-foto-autor">

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


                <h2>
                    <?php
                    echo htmlspecialchars(
                        $autor["nome"]
                    );
                    ?>
                </h2>


                <?php
                if (!empty($autor["instituicao"])) :
                ?>

                    <p class="preview-instituicao">
                        <?php
                        echo htmlspecialchars(
                            $autor["instituicao"]
                        );
                        ?>
                    </p>

                <?php endif; ?>


                <?php
                if ($autor["usuario_id"] !== null) :
                ?>

                    <span class="status-vinculo vinculado">
                        Vinculado a pesquisador
                    </span>

                <?php else : ?>

                    <span class="status-vinculo nao-vinculado">
                        Autor externo
                    </span>

                <?php endif; ?>

            </aside>


            <section class="formulario-editar-autor">

                <form
                    method="POST"
                    enctype="multipart/form-data"
                >


                    <div class="campo-autor-admin">

                        <label for="nome">
                            Nome *
                        </label>

                        <input
                            type="text"
                            name="nome"
                            id="nome"
                            value="<?php
                            echo htmlspecialchars(
                                $autor["nome"]
                            );
                            ?>"
                            required
                        >

                    </div>


                    <div class="campo-autor-admin">

                        <label for="instituicao">
                            Instituição
                        </label>

                        <input
                            type="text"
                            name="instituicao"
                            id="instituicao"
                            value="<?php
                            echo htmlspecialchars(
                                $autor["instituicao"] ?? ""
                            );
                            ?>"
                            placeholder="Opcional"
                        >

                    </div>


                    <div class="campo-autor-admin">

                        <label for="biografia">
                            Biografia
                        </label>

                        <textarea
                            name="biografia"
                            id="biografia"
                            rows="8"
                            placeholder="Informe uma breve biografia pública do autor."
                        ><?php
                        echo htmlspecialchars(
                            $autor["biografia"] ?? ""
                        );
                        ?></textarea>

                    </div>


                    <div class="campo-autor-admin">

                        <label for="foto">
                            Foto do autor
                        </label>

                        <input
                            type="file"
                            name="foto"
                            id="foto"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <small>
                            JPG, PNG ou WEBP.
                            Máximo de 5 MB.
                        </small>

                    </div>


                    <div class="campo-autor-admin">

                        <label for="usuario_id">
                            Conta de pesquisador vinculada
                        </label>

                        <select
                            name="usuario_id"
                            id="usuario_id"
                        >

                            <option value="">
                                Nenhuma — autor externo
                            </option>


                            <?php
                            while (
                                $pesquisador =
                                $resultadoPesquisadores
                                    ->fetch_assoc()
                            ) :
                            ?>

                                <option
                                    value="<?php
                                    echo $pesquisador["id"];
                                    ?>"
                                    <?php
                                    echo (
                                        (int)$autor["usuario_id"] ===
                                        (int)$pesquisador["id"]
                                    )
                                        ? "selected"
                                        : "";
                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $pesquisador["nome"]
                                    );
                                    ?>

                                    -

                                    <?php
                                    echo htmlspecialchars(
                                        $pesquisador["email"]
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                        <small>
                            Se este autor possuir uma conta
                            de pesquisador no sistema,
                            o administrador pode vinculá-la aqui.
                        </small>

                    </div>


                    <div class="aviso-vinculo-autor">

                        <strong>
                            Importante:
                        </strong>

                        <p>
                            A conta vinculada deve pertencer
                            à mesma pessoa representada
                            por este perfil público.
                        </p>

                        <p>
                            O pesquisador que submeteu uma pesquisa
                            não é automaticamente considerado
                            autor dela.
                        </p>

                    </div>


                    <button
                        type="submit"
                        class="botao-salvar-autor"
                    >
                        Salvar alterações
                    </button>

                </form>

            </section>

        </div>

    </section>

</main>

<?php

$stmtPesquisadores->close();

require_once "../includes/footer.php";

?>