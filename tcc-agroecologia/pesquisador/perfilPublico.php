<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["pesquisador"]);

$usuarioId = $_SESSION["usuario_id"];

$mensagemErro = "";
$mensagemSucesso = "";


/*
 * Busca o perfil público vinculado
 * à conta do pesquisador.
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

    WHERE usuario_id = ?

    LIMIT 1
";

$stmtAutor = $conn->prepare($sqlAutor);

$stmtAutor->bind_param(
    "i",
    $usuarioId
);

$stmtAutor->execute();

$resultadoAutor =
    $stmtAutor->get_result();


/*
 * Caso ainda não exista perfil público
 * vinculado à conta.
 */
if ($resultadoAutor->num_rows !== 1) {

    $stmtAutor->close();

    $cssPagina = "perfilPublico.css";

    require_once "../includes/header.php";
    require_once "../includes/menu.php";

    ?>

    <main class="pagina-perfil-publico">

        <section class="conteudo-perfil-publico">

            <div class="cabecalho-perfil-publico">

                <h1>Meu perfil público</h1>

                <p>
                    Gerencie as informações que aparecem
                    publicamente no Acervo Agroecológico.
                </p>

            </div>


            <div class="sem-perfil-publico">

                <h2>Perfil público ainda não vinculado</h2>

                <p>
                    Sua conta de pesquisador ainda não está
                    vinculada a um perfil público de autor.
                </p>

                <p>
                    O vínculo deve ser realizado por um
                    administrador do sistema.
                </p>

                <a
                    href="dashboard.php"
                    class="botao-voltar-perfil-publico"
                >
                    Voltar
                </a>

            </div>

        </section>

    </main>

    <?php

    require_once "../includes/footer.php";

    exit;
}


$autor =
    $resultadoAutor->fetch_assoc();

$stmtAutor->close();

$autorId =
    (int)$autor["id"];


/*
 * ----------------------------------------------------
 * ATUALIZAÇÃO DO PERFIL PÚBLICO
 * ----------------------------------------------------
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $instituicao =
        trim($_POST["instituicao"] ?? "");

    $biografia =
        trim($_POST["biografia"] ?? "");

    $fotoAutor =
        $autor["foto"] ?? null;


    /*
     * Processa nova foto, se enviada.
     */
    if (
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
     * Atualiza somente os dados públicos
     * que o pesquisador pode editar.
     *
     * O nome e o vínculo com usuario_id
     * continuam controlados pelo administrador.
     */
    if ($mensagemErro === "") {

        $sqlAtualizar = "
            UPDATE autores
            SET
                foto = ?,
                biografia = ?,
                instituicao = ?
            WHERE
                id = ?
                AND usuario_id = ?
        ";

        $stmtAtualizar =
            $conn->prepare(
                $sqlAtualizar
            );

        $stmtAtualizar->bind_param(
            "sssii",
            $fotoAutor,
            $biografia,
            $instituicao,
            $autorId,
            $usuarioId
        );


        if ($stmtAtualizar->execute()) {

            $mensagemSucesso =
                "Perfil público atualizado com sucesso.";

        } else {

            $mensagemErro =
                "Não foi possível atualizar o perfil público.";
        }

        $stmtAtualizar->close();


        /*
         * Recarrega os dados.
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

                WHERE
                    id = ?
                    AND usuario_id = ?

                LIMIT 1
            ";

            $stmtRecarregar =
                $conn->prepare(
                    $sqlRecarregar
                );

            $stmtRecarregar->bind_param(
                "ii",
                $autorId,
                $usuarioId
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


$cssPagina = "perfilPublico.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-perfil-publico">

    <section class="conteudo-perfil-publico">

        <div class="cabecalho-perfil-publico">

            <div>

                <h1>Meu perfil público</h1>

                <p>
                    Atualize as informações exibidas
                    publicamente no Acervo Agroecológico.
                </p>

            </div>

            <a
                href="dashboard.php"
                class="botao-voltar-perfil-publico"
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


        <div class="perfil-publico-grid">

            <aside class="visualizacao-perfil-publico">

                <?php if (!empty($autor["foto"])) : ?>

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
                        class="foto-perfil-publico"
                    >

                <?php else : ?>

                    <div class="sem-foto-perfil-publico">

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

                    <p class="instituicao-perfil-publico">

                        <?php
                        echo htmlspecialchars(
                            $autor["instituicao"]
                        );
                        ?>

                    </p>

                <?php endif; ?>


                <span class="perfil-publico-status">
                    Perfil público vinculado
                </span>

            </aside>


            <section class="formulario-perfil-publico">

                <h2>Informações públicas</h2>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                >


                    <div class="campo-perfil-publico">

                        <label>
                            Nome do autor
                        </label>

                        <input
                            type="text"
                            value="<?php
                            echo htmlspecialchars(
                                $autor["nome"]
                            );
                            ?>"
                            disabled
                        >

                        <small>
                            O nome do perfil público é
                            administrado pelo sistema.
                        </small>

                    </div>


                    <div class="campo-perfil-publico">

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
                            placeholder="Informe sua instituição"
                        >

                    </div>


                    <div class="campo-perfil-publico">

                        <label for="biografia">
                            Biografia
                        </label>

                        <textarea
                            name="biografia"
                            id="biografia"
                            rows="9"
                            placeholder="Escreva uma breve apresentação profissional ou acadêmica."
                        ><?php
                        echo htmlspecialchars(
                            $autor["biografia"] ?? ""
                        );
                        ?></textarea>

                    </div>


                    <div class="campo-perfil-publico">

                        <label for="foto">
                            Foto pública
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


                    <button
                        type="submit"
                        class="botao-salvar-perfil-publico"
                    >
                        Salvar perfil público
                    </button>

                </form>

            </section>

        </div>

    </section>

</main>

<?php

require_once "../includes/footer.php";

?>