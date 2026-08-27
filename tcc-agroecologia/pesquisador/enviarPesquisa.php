<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";
require_once "../includes/conexao.php";

exigirPerfil(["pesquisador"]);

$mensagemErro = "";
$mensagemSucesso = "";


/*
 * Função simples para padronizar o texto.
 * Remove espaços extras e deixa a primeira letra maiúscula.
 */
function primeiraMaiuscula($texto)
{
    $texto = trim($texto);

    if ($texto === "") {
        return "";
    }

    return mb_strtoupper(
        mb_substr($texto, 0, 1, "UTF-8"),
        "UTF-8"
    ) . mb_substr($texto, 1, null, "UTF-8");
}


/*
 * Carrega as regiões para o SELECT.
 */
$sqlRegioes = "
    SELECT id, nome
    FROM regioes
    ORDER BY nome
";

$resultadoRegioes = $conn->query($sqlRegioes);


/*
 * Carrega os tipos de solo existentes
 * para a lista de sugestões.
 */
$sqlSolos = "
    SELECT nome
    FROM tipos_solo
    ORDER BY nome
";

$resultadoSolos = $conn->query($sqlSolos);


/*
 * Carrega os cultivos existentes
 * para a lista de sugestões.
 */
$sqlCultivos = "
    SELECT nome
    FROM tipos_cultivo
    ORDER BY nome
";

$resultadoCultivos = $conn->query($sqlCultivos);


/*
 * Processamento do formulário.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = trim($_POST["titulo"] ?? "");
    $descricao = trim($_POST["descricao"] ?? "");
    $resumo = trim($_POST["resumo"] ?? "");
    $palavrasChave = trim($_POST["palavras_chave"] ?? "");
    $link = trim($_POST["link"] ?? "");

    $autorNome = primeiraMaiuscula($_POST["autor"] ?? "");

    $regiaoId = intval($_POST["regiao_id"] ?? 0);

    $soloInformado = primeiraMaiuscula(
        $_POST["solo"] ?? ""
    );

    $cultivoInformado = primeiraMaiuscula(
        $_POST["cultivo"] ?? ""
    );

    $pesquisadorId = $_SESSION["usuario_id"];


    /*
     * Validação dos campos obrigatórios.
     */
    if (
        $titulo === "" ||
        $descricao === "" ||
        $autorNome === "" ||
        $regiaoId <= 0 ||
        $soloInformado === "" ||
        $cultivoInformado === ""
    ) {

        $mensagemErro =
            "Preencha todos os campos obrigatórios.";

    } elseif (
        $link !== "" &&
        !filter_var($link, FILTER_VALIDATE_URL)
    ) {

        $mensagemErro =
            "Informe um link válido para a pesquisa.";

    } else {

        /*
         * Verifica se a região realmente existe.
         */
        $sqlVerificarRegiao = "
            SELECT id
            FROM regioes
            WHERE id = ?
        ";

        $stmtRegiao = $conn->prepare(
            $sqlVerificarRegiao
        );

        $stmtRegiao->bind_param(
            "i",
            $regiaoId
        );

        $stmtRegiao->execute();

        $resultadoRegiao =
            $stmtRegiao->get_result();


        if ($resultadoRegiao->num_rows !== 1) {

            $mensagemErro =
                "A região selecionada é inválida.";

        } else {

            /*
             * AUTOR
             *
             * Primeiro procura um autor com
             * o nome informado.
             */
            $sqlAutor = "
                SELECT id
                FROM autores
                WHERE nome = ?
                LIMIT 1
            ";

            $stmtAutor =
                $conn->prepare($sqlAutor);

            $stmtAutor->bind_param(
                "s",
                $autorNome
            );

            $stmtAutor->execute();

            $resultadoAutor =
                $stmtAutor->get_result();


            if ($resultadoAutor->num_rows === 1) {

                $autor =
                    $resultadoAutor->fetch_assoc();

                $autorId = $autor["id"];

            } else {

                /*
                 * Autor ainda não existe.
                 * Cria um registro básico.
                 */
                $sqlNovoAutor = "
                    INSERT INTO autores (nome)
                    VALUES (?)
                ";

                $stmtNovoAutor =
                    $conn->prepare($sqlNovoAutor);

                $stmtNovoAutor->bind_param(
                    "s",
                    $autorNome
                );

                $stmtNovoAutor->execute();

                $autorId =
                    $stmtNovoAutor->insert_id;

                $stmtNovoAutor->close();
            }


            /*
             * SOLO
             *
             * Se já existir no catálogo,
             * pega o ID.
             *
             * Caso contrário, fica NULL.
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

                $soloId = $solo["id"];
            }


            /*
             * CULTIVO
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

            if (
                $resultadoCultivo->num_rows === 1
            ) {

                $cultivo =
                    $resultadoCultivo->fetch_assoc();

                $cultivoId =
                    $cultivo["id"];
            }


            /*
             * Finalmente cadastra a pesquisa.
             *
             * O status não precisa ser enviado,
             * pois o banco já define Pendente.
             */
            $sqlPesquisa = "
                INSERT INTO pesquisas (
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
                    pesquisador_id
                )
                VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?
                )
            ";

            $stmtPesquisa =
                $conn->prepare($sqlPesquisa);

            $stmtPesquisa->bind_param(
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
                $pesquisadorId
            );


            if ($stmtPesquisa->execute()) {

                $mensagemSucesso =
                    "Pesquisa enviada com sucesso e encaminhada para análise.";

            } else {

                $mensagemErro =
                    "Não foi possível enviar a pesquisa.";
            }


            $stmtPesquisa->close();
            $stmtAutor->close();
            $stmtSolo->close();
            $stmtCultivo->close();
        }

        $stmtRegiao->close();
    }
}


$cssPagina = "pesquisas.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-pesquisa">

    <section class="formulario-pesquisa">

        <h1>Enviar pesquisa</h1>

        <p>
            Preencha as informações da pesquisa.
            Após o envio, ela ficará pendente até
            ser analisada por um administrador.
        </p>


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


        <form method="POST">

            <div class="campo-formulario">

                <label for="titulo">
                    Título *
                </label>

                <input
                    type="text"
                    name="titulo"
                    id="titulo"
                    maxlength="250"
                    required
                >

            </div>


            <div class="campo-formulario">

                <label for="autor">
                    Autor *
                </label>

                <input
                    type="text"
                    name="autor"
                    id="autor"
                    maxlength="150"
                    required
                >

            </div>


            <div class="campo-formulario">

                <label for="descricao">
                    Descrição *
                </label>

                <textarea
                    name="descricao"
                    id="descricao"
                    rows="4"
                    required
                ></textarea>

            </div>


            <div class="campo-formulario">

                <label for="resumo">
                    Resumo
                </label>

                <textarea
                    name="resumo"
                    id="resumo"
                    rows="6"
                ></textarea>

            </div>


            <div class="campo-formulario">

                <label for="palavras_chave">
                    Palavras-chave
                </label>

                <input
                    type="text"
                    name="palavras_chave"
                    id="palavras_chave"
                    maxlength="255"
                    placeholder="Ex.: milho, manejo, solo"
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

                    <option value="">
                        Selecione
                    </option>

                    <?php
                    while (
                        $regiao =
                        $resultadoRegioes->fetch_assoc()
                    ) :
                    ?>

                        <option
                            value="<?php
                            echo $regiao["id"];
                            ?>"
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

                <label for="solo">
                    Tipo de solo *
                </label>

                <input
                    type="text"
                    name="solo"
                    id="solo"
                    list="lista-solos"
                    maxlength="80"
                    autocomplete="off"
                    required
                >

                <datalist id="lista-solos">

                    <?php
                    while (
                        $solo =
                        $resultadoSolos->fetch_assoc()
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

                <label for="cultivo">
                    Tipo de cultivo *
                </label>

                <input
                    type="text"
                    name="cultivo"
                    id="cultivo"
                    list="lista-cultivos"
                    maxlength="80"
                    autocomplete="off"
                    required
                >

                <datalist id="lista-cultivos">

                    <?php
                    while (
                        $cultivo =
                        $resultadoCultivos->fetch_assoc()
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
                    maxlength="500"
                    placeholder="https://..."
                >

            </div>


            <button type="submit">
                Enviar para análise
            </button>

        </form>

    </section>

</main>

<?php

require_once "../includes/footer.php";

?>