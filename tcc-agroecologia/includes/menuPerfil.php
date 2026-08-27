<?php

if (!isset($_SESSION["usuario_id"], $_SESSION["usuario_tipo"])) {
    return;
}

$tipoUsuario = $_SESSION["usuario_tipo"];

?>

<?php if ($tipoUsuario === "pesquisador") : ?>

    <nav class="menu-perfil menu-pesquisador">

        <div class="menu-perfil-conteudo">

            <span class="titulo-menu-perfil">
                Área do Pesquisador
            </span>

            <a
                href="/tcc%20vitor/tcc-agroecologia/pesquisador/dashboard.php"
            >
                Painel
            </a>

            <a
                href="/tcc%20vitor/tcc-agroecologia/pesquisador/enviarPesquisa.php"
            >
                Enviar pesquisa
            </a>

            <a
                href="/tcc%20vitor/tcc-agroecologia/pesquisador/status.php"
            >
                Minhas submissões
            </a>

            <a
                href="/tcc%20vitor/tcc-agroecologia/pesquisador/perfilPublico.php"
            >
                Perfil público
            </a>

        </div>

    </nav>


<?php elseif ($tipoUsuario === "administrador") : ?>

    <nav class="menu-perfil menu-administrador">

        <div class="menu-perfil-conteudo">

            <span class="titulo-menu-perfil">
                Administração
            </span>

            <a
                href="/tcc%20vitor/tcc-agroecologia/admin/dashboard.php"
            >
                Painel
            </a>

            <a
                href="/tcc%20vitor/tcc-agroecologia/admin/pesquisas.php"
            >
                Pesquisas
            </a>

            <a
                href="/tcc%20vitor/tcc-agroecologia/admin/usuarios.php"
            >
                Usuários
            </a>

            <a
                href="/tcc%20vitor/tcc-agroecologia/admin/autores.php"
            >
                Autores
            </a>

            <a
    href="/tcc%20vitor/tcc-agroecologia/admin/avaliacoesSite.php"
>
    Avaliações
</a>

        </div>

    </nav>

<?php endif; ?>