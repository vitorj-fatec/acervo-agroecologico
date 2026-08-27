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
                href="/pesquisador/dashboard.php"
            >
                Painel
            </a>

            <a
                href="/pesquisador/enviarPesquisa.php"
            >
                Enviar pesquisa
            </a>

            <a
                href="/pesquisador/status.php"
            >
                Minhas submissões
            </a>

            <a
                href="/pesquisador/perfilPublico.php"
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
                href="/admin/dashboard.php"
            >
                Painel
            </a>

            <a
                href="/admin/pesquisas.php"
            >
                Pesquisas
            </a>

            <a
                href="/admin/usuarios.php"
            >
                Usuários
            </a>

            <a
                href="/admin/autores.php"
            >
                Autores
            </a>

            <a
    href="/admin/avaliacoesSite.php"
>
    Avaliações
</a>

        </div>

    </nav>

<?php endif; ?>
