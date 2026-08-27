<header class="cabecalho">

    <div class="logo">

        <a href="/pages/dashboard.php">
            Acervo Agroecológico
        </a>

    </div>


    <nav class="menu-principal">

        <a href="/pages/dashboard.php">
            Início
        </a>

        <a href="/pages/pesquisas.php">
            Pesquisas
        </a>

        <a href="/pages/autores.php">
            Autores
        </a>

        <a href="/pages/regiao.php">
            Região
        </a>


        <?php if (isset($_SESSION["usuario_id"])) : ?>

            <a
                class="usuario-logado"
                href="/pages/perfil.php"
            >

                <?php
                echo htmlspecialchars(
                    $_SESSION["usuario_nome"]
                );
                ?>

            </a>


            <a
                class="link-sair"
                href="/logout.php"
            >
                Sair
            </a>

        <?php endif; ?>

    </nav>

</header>


<?php

require_once __DIR__ . "/menuPerfil.php";

?>
