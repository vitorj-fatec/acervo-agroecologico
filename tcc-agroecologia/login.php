
<?php

session_start();

require_once "includes/conexao.php";

$mensagem = "";
$mensagemSucesso = "";

if (isset($_GET["cadastro"]) && $_GET["cadastro"] === "sucesso") {
    $mensagemSucesso = "Cadastro realizado com sucesso. Faça seu login.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if (empty($email) || empty($senha)) {

        $mensagem = "Preencha todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensagem = "Informe um endereço de e-mail válido.";

    } else {

        $sql = "
            SELECT
                id,
                nome,
                email,
                senha,
                tipo,
                ativo
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {

            $usuario = $resultado->fetch_assoc();

            if (!$usuario["ativo"]) {

                $mensagem = "Esta conta está desativada.";

            } elseif (password_verify($senha, $usuario["senha"])) {

                session_regenerate_id(true);

                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["usuario_nome"] = $usuario["nome"];
                $_SESSION["usuario_email"] = $usuario["email"];
                $_SESSION["usuario_tipo"] = $usuario["tipo"];

                if ($usuario["tipo"] === "administrador") {

                    header("Location: admin/dashboard.php");
                    exit;

                } elseif ($usuario["tipo"] === "pesquisador") {

                    header("Location: pesquisador/dashboard.php");
                    exit;

                } else {

                    header("Location: pages/dashboard.php");
                    exit;
                }

            } else {

                $mensagem = "E-mail ou senha incorretos.";
            }

        } else {

            $mensagem = "E-mail ou senha incorretos.";
        }

        $stmt->close();
    }
}

$cssPagina = "login.css";

require_once "includes/header.php";

?>

<main class="area-login">

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

            <div class="formulario-login">

                <h1>Login</h1>

                <p class="descricao-login">
                    Acesse sua conta no Acervo Agroecológico.
                </p>

                <?php if (!empty($mensagemSucesso)) : ?>

                    <div class="mensagem-sucesso">
                        <?php echo htmlspecialchars($mensagemSucesso); ?>
                    </div>

                <?php endif; ?>

                <?php if (!empty($mensagem)) : ?>

                    <div class="mensagem-erro">
                        <?php echo htmlspecialchars($mensagem); ?>
                    </div>

                <?php endif; ?>

                <form method="POST" action="login.php">

                    <div class="campo-formulario">

                        <label for="email">
                            E-mail
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            placeholder="Digite seu e-mail"
                            required
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
                            placeholder="Digite sua senha"
                            required
                        >

                    </div>

                    <button type="submit">
                        Entrar
                    </button>

                </form>

                <p class="link-autenticacao">
                    Não possui conta?
                    <a href="cadastro.php">Criar conta</a>
                </p>

            </div>

        </section>

    </section>

</main>

<?php

require_once "includes/footer.php";

?>