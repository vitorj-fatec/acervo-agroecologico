<?php

require_once "../includes/verificacao.php";
require_once "../includes/autorizacao.php";

exigirPerfil(["pesquisador"]);

$cssPagina = "dashboard.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="dashboard dashboard-pesquisador">

    <section class="apresentacao">

        <h1>Área do Pesquisador</h1>

        <p>
            Bem-vindo,
            <strong>
                <?php
                echo htmlspecialchars(
                    $_SESSION["usuario_nome"]
                );
                ?>
            </strong>.
        </p>

        <p>
            Nesta área você pode enviar pesquisas para o
            Acervo Agroecológico, acompanhar suas submissões
            e administrar seu perfil público de autor.
        </p>

    </section>


    <section class="como-funciona">

        <h2>Gerenciamento</h2>

        <div class="cards-dashboard">


            <article class="card-dashboard">

                <h3>Enviar pesquisa</h3>

                <p>
                    Cadastre uma nova pesquisa científica
                    para análise e possível inclusão
                    no acervo.
                </p>

                <a href="enviarPesquisa.php">
                    Enviar pesquisa
                </a>

            </article>


            <article class="card-dashboard">

                <h3>Minhas submissões</h3>

                <p>
                    Consulte as pesquisas enviadas e
                    acompanhe o status de cada submissão.
                </p>

                <a href="status.php">
                    Acompanhar submissões
                </a>

            </article>


            <article class="card-dashboard">

                <h3>Meu perfil público</h3>

                <p>
                    Atualize sua biografia, instituição
                    e foto exibidas publicamente
                    na área de autores.
                </p>

                <a href="perfilPublico.php">
                    Gerenciar perfil público
                </a>

            </article>


        </div>

    </section>

</main>

<?php

require_once "../includes/footer.php";

?>