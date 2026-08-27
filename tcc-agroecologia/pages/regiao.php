<?php

require_once "../includes/verificacao.php";
require_once "../includes/conexao.php";

$cssPagina = "regiao.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";


/*
 * Busca somente as cinco regiões
 * geográficas brasileiras.
 *
 * "Não se aplica" é utilizada apenas
 * como classificação para pesquisas
 * sem localização regional brasileira.
 */
$sql = "
    SELECT
        id,
        nome

    FROM regioes

    WHERE nome <> 'Não se aplica'

    ORDER BY id
";

$resultado = $conn->query($sql);

?>

<main class="pagina-regioes">

    <section class="conteudo-regioes">

        <div class="cabecalho-regioes">

            <h1>Regiões</h1>

            <p>
                Explore pesquisas aprovadas de acordo
                com as diferentes regiões do Brasil.
            </p>

        </div>


        <div class="cards-regioes">

            <?php while ($regiao = $resultado->fetch_assoc()) : ?>

                <?php

                /*
                 * Define a imagem de acordo
                 * com o nome da região.
                 */
                $imagem = "";

                switch ($regiao["nome"]) {

                    case "Norte":
                        $imagem =
                            "../images/regioes/regiao-norte.png";
                        break;

                    case "Nordeste":
                        $imagem =
                            "../images/regioes/regiao-nordeste.png";
                        break;

                    case "Centro-Oeste":
                        $imagem =
                            "../images/regioes/regiao-centro-oeste.png";
                        break;

                    case "Sudeste":
                        $imagem =
                            "../images/regioes/regiao-sudeste.png";
                        break;

                    case "Sul":
                        $imagem =
                            "../images/regioes/regiao-sul.png";
                        break;
                }

                ?>

                <a
                    href="pesquisas.php?regiao=<?php
                    echo $regiao["id"];
                    ?>"
                    class="card-regiao"
                >

                    <div class="imagem-regiao">

                        <img
                            src="<?php
                            echo htmlspecialchars(
                                $imagem
                            );
                            ?>"
                            alt="Região <?php
                            echo htmlspecialchars(
                                $regiao["nome"]
                            );
                            ?>"
                        >

                    </div>


                    <div class="nome-regiao">

                        <h2>
                            <?php
                            echo htmlspecialchars(
                                $regiao["nome"]
                            );
                            ?>
                        </h2>

                    </div>

                </a>

            <?php endwhile; ?>

        </div>

    </section>

</main>

<?php

require_once "../includes/footer.php";

?>