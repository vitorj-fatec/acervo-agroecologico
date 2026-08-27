<?php

function exigirPerfil(array $perfisPermitidos)
{
    if (!isset($_SESSION["usuario_tipo"])) {

        header("Location: /login.php");
        exit;
    }

    if (!in_array($_SESSION["usuario_tipo"], $perfisPermitidos, true)) {

        header(
            "Location: /pages/dashboard.php?erro=sem_permissao"
        );

        exit;
    }
}

?>
