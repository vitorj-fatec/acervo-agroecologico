<?php

require_once "../includes/verificacao.php";
require_once "../includes/conexao.php";

$usuarioId = $_SESSION["usuario_id"];

$mensagemErro = "";
$mensagemSucesso = "";


/*
 * Atualização dos dados permitidos.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $instituicao = trim($_POST["instituicao"] ?? "");

    if ($nome === "") {

        $mensagemErro = "Informe o nome.";

    } else {

        /*
         * Busca a foto atual antes de processar upload.
         */
        $sqlFotoAtual = "
            SELECT foto_perfil
            FROM usuarios
            WHERE id = ?
            LIMIT 1
        ";

        $stmtFotoAtual = $conn->prepare($sqlFotoAtual);

        $stmtFotoAtual->bind_param(
            "i",
            $usuarioId
        );

        $stmtFotoAtual->execute();

        $resultadoFotoAtual =
            $stmtFotoAtual->get_result();

        $dadosFotoAtual =
            $resultadoFotoAtual->fetch_assoc();

        $fotoPerfil =
            $dadosFotoAtual["foto_perfil"] ?? null;

        $stmtFotoAtual->close();


        /*
         * Processa a nova foto, caso enviada.
         */
        if (
            isset($_FILES["foto_perfil"]) &&
            $_FILES["foto_perfil"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            $arquivo = $_FILES["foto_perfil"];


            if ($arquivo["error"] !== UPLOAD_ERR_OK) {

                $mensagemErro =
                    "Não foi possível enviar a imagem.";

            } elseif ($arquivo["size"] > 5 * 1024 * 1024) {

                $mensagemErro =
                    "A imagem deve ter no máximo 5 MB.";

            } else {

                /*
                 * Detecta o tipo real da imagem.
                 */
                $finfo = new finfo(FILEINFO_MIME_TYPE);

                $tipoMime =
                    $finfo->file($arquivo["tmp_name"]);


                $tiposPermitidos = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
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


                    /*
                     * Gera nome próprio para evitar
                     * conflitos e nomes manipulados.
                     */
                    $nomeArquivo =
                        "usuario_" .
                        $usuarioId .
                        "_" .
                        time() .
                        "." .
                        $extensao;


                    $caminhoFisico =
                        "../images/perfis/" .
                        $nomeArquivo;


                    if (
                        move_uploaded_file(
                            $arquivo["tmp_name"],
                            $caminhoFisico
                        )
                    ) {

                        /*
                         * Valor salvo no banco.
                         *
                         * Como perfil.php está dentro de pages/,
                         * usamos esse caminho para exibição.
                         */
                        $fotoPerfil =
                            "../images/perfis/" .
                            $nomeArquivo;

                    } else {

                        $mensagemErro =
                            "Não foi possível salvar a imagem.";
                    }
                }
            }
        }


        /*
         * Só atualiza o banco se não houve erro
         * durante o upload.
         */
        if ($mensagemErro === "") {

            $sqlAtualizar = "
                UPDATE usuarios
                SET
                    nome = ?,
                    instituicao = ?,
                    foto_perfil = ?
                WHERE id = ?
            ";

            $stmtAtualizar =
                $conn->prepare($sqlAtualizar);

            $stmtAtualizar->bind_param(
                "sssi",
                $nome,
                $instituicao,
                $fotoPerfil,
                $usuarioId
            );


            if ($stmtAtualizar->execute()) {

                /*
                 * Mantém o nome da sessão atualizado.
                 */
                $_SESSION["usuario_nome"] = $nome;

                $mensagemSucesso =
                    "Informações atualizadas com sucesso.";

            } else {

                $mensagemErro =
                    "Não foi possível atualizar suas informações.";
            }

            $stmtAtualizar->close();
        }
    }
}


/*
 * Busca os dados atuais do usuário.
 */
$sqlUsuario = "
    SELECT
        id,
        nome,
        email,
        tipo,
        instituicao,
        foto_perfil,
        ativo,
        dataCadastro

    FROM usuarios

    WHERE id = ?

    LIMIT 1
";

$stmtUsuario =
    $conn->prepare($sqlUsuario);

$stmtUsuario->bind_param(
    "i",
    $usuarioId
);

$stmtUsuario->execute();

$resultadoUsuario =
    $stmtUsuario->get_result();


if ($resultadoUsuario->num_rows !== 1) {

    $stmtUsuario->close();

    header("Location: ../logout.php");
    exit;
}


$usuario =
    $resultadoUsuario->fetch_assoc();


/*
 * Nome amigável do perfil.
 */
$nomeTipo = "Usuário";

if ($usuario["tipo"] === "pesquisador") {
    $nomeTipo = "Pesquisador";
}

if ($usuario["tipo"] === "administrador") {
    $nomeTipo = "Administrador";
}


$cssPagina = "perfil.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-perfil">

    <section class="conteudo-perfil">

        <div class="cabecalho-perfil">

            <div>

                <h1>Meu perfil</h1>

                <p>
                    Consulte e atualize suas informações
                    no Acervo Agroecológico.
                </p>

            </div>

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


        <div class="perfil-grid">

            <aside class="resumo-perfil">


                <?php
                if (
                    !empty(
                        $usuario["foto_perfil"]
                    )
                ) :
                ?>

                    <img
                        src="<?php
                        echo htmlspecialchars(
                            $usuario["foto_perfil"]
                        );
                        ?>"
                        alt="Foto de perfil"
                        class="foto-perfil"
                    >

                <?php else : ?>

                    <div class="avatar-perfil">

                        <?php
                        echo strtoupper(
                            mb_substr(
                                $usuario["nome"],
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
                        $usuario["nome"]
                    );
                    ?>
                </h2>


                <span class="tipo-perfil">

                    <?php
                    echo htmlspecialchars(
                        $nomeTipo
                    );
                    ?>

                </span>


                <div class="dados-resumo-perfil">

                    <p>
                        <strong>E-mail</strong>
                    </p>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $usuario["email"]
                        );
                        ?>
                    </p>


                    <p>
                        <strong>Conta criada em</strong>
                    </p>

                    <p>
                        <?php
                        echo date(
                            "d/m/Y",
                            strtotime(
                                $usuario["dataCadastro"]
                            )
                        );
                        ?>
                    </p>


                    <p>
                        <strong>Situação</strong>
                    </p>

                    <p>
                        <?php
                        echo (int)$usuario["ativo"] === 1
                            ? "Ativa"
                            : "Bloqueada";
                        ?>
                    </p>

                </div>

            </aside>


            <section class="edicao-perfil">

                <h2>Informações da conta</h2>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                >


                    <div class="campo-formulario">

                        <label for="foto_perfil">
                            Foto de perfil
                        </label>

                        <input
                            type="file"
                            name="foto_perfil"
                            id="foto_perfil"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <small>
                            Formatos permitidos:
                            JPG, PNG ou WEBP.
                            Máximo de 5 MB.
                        </small>

                    </div>


                    <div class="campo-formulario">

                        <label for="nome">
                            Nome
                        </label>

                        <input
                            type="text"
                            name="nome"
                            id="nome"
                            value="<?php
                            echo htmlspecialchars(
                                $usuario["nome"]
                            );
                            ?>"
                            required
                        >

                    </div>


                    <div class="campo-formulario">

                        <label for="email">
                            E-mail
                        </label>

                        <input
                            type="email"
                            id="email"
                            value="<?php
                            echo htmlspecialchars(
                                $usuario["email"]
                            );
                            ?>"
                            disabled
                        >

                        <small>
                            O e-mail da conta não pode ser
                            alterado por esta tela.
                        </small>

                    </div>


                    <div class="campo-formulario">

                        <label for="instituicao">
                            Instituição
                        </label>

                        <input
                            type="text"
                            name="instituicao"
                            id="instituicao"
                            value="<?php
                            echo htmlspecialchars(
                                $usuario["instituicao"] ?? ""
                            );
                            ?>"
                            placeholder="Opcional"
                        >

                    </div>


                    <div class="campo-formulario">

                        <label>
                            Tipo de conta
                        </label>

                        <input
                            type="text"
                            value="<?php
                            echo htmlspecialchars(
                                $nomeTipo
                            );
                            ?>"
                            disabled
                        >

                    </div>


                    <button
                        type="submit"
                        class="botao-salvar-perfil"
                    >
                        Salvar alterações
                    </button>

                </form>

            </section>

        </div>

    </section>

</main>

<?php

$stmtUsuario->close();

require_once "../includes/footer.php";

?>