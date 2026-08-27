<header class="cabecalho">

    <div class="logo">

        <a href="/tcc%20vitor/tcc-agroecologia/pages/dashboard.php">
            Acervo Agroecológico
        </a>

    </div>


    <nav class="menu-principal">

        <a href="/tcc%20vitor/tcc-agroecologia/pages/dashboard.php">
            Início
        </a>

        <a href="/tcc%20vitor/tcc-agroecologia/pages/pesquisas.php">
            Pesquisas
        </a>

        <a href="/tcc%20vitor/tcc-agroecologia/pages/autores.php">
            Autores
        </a>

        <a href="/tcc%20vitor/tcc-agroecologia/pages/regiao.php">
            Região
        </a>


        <?php if (isset($_SESSION["usuario_id"])) : ?>

            <a
                class="usuario-logado"
                href="/tcc%20vitor/tcc-agroecologia/pages/perfil.php"
            >

                <?php
                echo htmlspecialchars(
                    $_SESSION["usuario_nome"]
                );
                ?>

            </a>


            <a
                class="link-sair"
                href="/tcc%20vitor/tcc-agroecologia/logout.php"
            >
                Sair
            </a>

        <?php endif; ?>

    </nav>

</header>


<?php

require_once __DIR__ . "/menuPerfil.php";

?>