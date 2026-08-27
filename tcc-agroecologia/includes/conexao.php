<?php

$host = "sql113.infinityfree.com";
$usuario = "if0_42411794";
$senha = "";
$banco = "if0_42411794_tcc_agro";

$conn = new mysqli(
    $host,
    $usuario,
    $senha,
    $banco
);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados.");
}

$conn->set_charset("utf8mb4");

?>
