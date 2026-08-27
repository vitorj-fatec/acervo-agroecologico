
<?php

require_once "includes/conexao.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmarSenha = $_POST["confirmarSenha"] ?? "";
    $tipo = $_POST["tipo"] ?? "";
    $instituicao = trim($_POST["instituicao"] ?? "");

    // Validação dos campos obrigatórios
    if (
        empty($nome) ||
        empty($email) ||
        empty($senha) ||
        empty($confirmarSenha) ||
        empty($tipo)
    ) {
        $mensagem = "Preencha todos os campos obrigatórios.";
    }

    // Validação do e-mail
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Informe um endereço de e-mail válido.";
    }

    // Confirmação da senha
    elseif ($senha !== $confirmarSenha) {
        $mensagem = "As senhas não coincidem.";
    }

    // Tamanho mínimo da senha
    elseif (strlen($senha) < 6) {
        $mensagem = "A senha deve possuir pelo menos 6 caracteres.";
    }

    // Impede criação de administrador pelo cadastro público
   elseif (!in_array($tipo, ["usuario", "pesquisador"], true)) {
    $mensagem = "Tipo de usuário inválido.";
}

    else {

        /*
         * Primeiro verificamos se já existe uma conta
         * utilizando esse e-mail.
         */
        $sqlVerificar = "SELECT id FROM usuarios WHERE email = ?";

        $stmtVerificar = $conn->prepare($sqlVerificar);
        $stmtVerificar->bind_param("s", $email);
        $stmtVerificar->execute();

        $resultado = $stmtVerificar->get_result();

        if ($resultado->num_rows > 0) {

            $mensagem = "Já existe uma conta cadastrada com esse e-mail.";

        } else {

            /*
             * Nunca salvamos a senha original no banco.
             * password_hash cria uma versão segura dela.
             */
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            $sqlCadastrar = "
                INSERT INTO usuarios
                (
                    nome,
                    email,
                    senha,
                    tipo,
                    instituicao
                )
                VALUES (?, ?, ?, ?, ?)
            ";

            $stmtCadastrar = $conn->prepare($sqlCadastrar);

            $stmtCadastrar->bind_param(
                "sssss",
                $nome,
                $email,
                $senhaHash,
                $tipo,
                $instituicao
            );

            if ($stmtCadastrar->execute()) {

                header("Location: login.php?cadastro=sucesso");
                exit;

            } else {

                $mensagem = "Não foi possível realizar o cadastro.";

            }

            $stmtCadastrar->close();
        }

        $stmtVerificar->close();
    }
}
$cssPagina = "login.css";

require_once "includes/header.php";

?>


<main class="area-login area-cadastro">

    <section class="caixa-autenticacao">

        <aside class="painel-visual-login" aria-hidden="true">

            <div class="onda onda-superior"></div>
            <div class="onda onda-inferior"></div>

            <div class="circulo-planta">
                <span class="icone-planta">🌱</span>
            </div>

            <div class="marca-login">
                <strong>Acervo</strong>
                <span>Agroecológico</span>
            </div>

        </aside>

        <section class="lado-formulario-login">

            <div class="formulario-login formulario-cadastro">

                <h1>Criar conta</h1>

                <p class="descricao-login">
                    Cadastre-se para acessar o Acervo Agroecológico.
                </p>

                <?php if (!empty($mensagem)) : ?>

                    <div class="mensagem-erro">
                        <?php echo htmlspecialchars($mensagem); ?>
                    </div>

                <?php endif; ?>

                <form method="POST" action="cadastro.php">

                    <div class="grade-cadastro">

                        <div class="campo-formulario campo-largo">

                            <label for="nome">
                                Nome
                            </label>

                            <input
                                type="text"
                                name="nome"
                                id="nome"
                                placeholder="Seu nome completo"
                                required
                            >

                        </div>

                        <div class="campo-formulario campo-largo">

                            <label for="email">
                                E-mail
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                placeholder="Seu e-mail"
                                required
                            >

                        </div>

                        <div class="campo-formulario">

                            <label for="tipo">
                                Tipo de conta
                            </label>

                            <select
                                name="tipo"
                                id="tipo"
                                required
                            >

                                <option value="">
                                    Selecione
                                </option>

                                <option value="usuario">
                                    Usuário
                                </option>

                                <option value="pesquisador">
                                    Pesquisador
                                </option>

                            </select>

                        </div>

                        <div class="campo-formulario">

                            <label for="instituicao">
                                Instituição
                            </label>

                            <input
                                type="text"
                                name="instituicao"
                                id="instituicao"
                                placeholder="Opcional"
                            >

                        </div>

                        <div class="campo-formulario">

                            <label for="senha">
                                Senha
                            </label>

                            <input
                                type="password"
                                name="senha"
                                id="senha"
                                placeholder="Mínimo de 6 caracteres"
                                minlength="6"
                                required
                            >

                        </div>

                        <div class="campo-formulario">

                            <label for="confirmarSenha">
                                Confirmar senha
                            </label>

                            <input
                                type="password"
                                name="confirmarSenha"
                                id="confirmarSenha"
                                placeholder="Repita sua senha"
                                minlength="6"
                                required
                            >

                        </div>

                    </div>

                    <button type="submit">
                        Cadastrar
                    </button>

                </form>

                <p class="link-autenticacao">
                    Já possui uma conta?
                    <a href="login.php">Entrar</a>
                </p>

            </div>

        </section>

    </section>

</main>

<?php

require_once "includes/footer.php";

?>