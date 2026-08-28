<?php

require_once "../includes/verificacao.php";
require_once "../includes/conexao.php";


/*
 * Valida o ID do autor recebido pela URL.
 */
$autorId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$autorId) {
    header("Location: autores.php");
    exit;
}


/*
 * Busca os dados do autor.
 *
 * O autor só pode ser acessado publicamente
 * se possuir pelo menos uma pesquisa aprovada.
 *
 * Caso o autor esteja vinculado a uma conta
 * de pesquisador, usamos a foto de perfil
 * dessa conta como primeira opção.
 */
$sqlAutor = "
    SELECT
        a.id,
        a.usuario_id,
        a.nome,
        a.biografia,
        a.instituicao,

        COALESCE(
            NULLIF(a.foto, ''),
            u.foto_perfil
        ) AS foto_exibicao

    FROM autores a

    LEFT JOIN usuarios u
        ON u.id = a.usuario_id
        AND u.tipo = 'pesquisador'

    WHERE a.id = ?

    AND EXISTS (
        SELECT 1

        FROM pesquisas p

        WHERE
            p.autor_id = a.id
            AND p.status = 'Aprovada'
    )

    LIMIT 1
";

$stmtAutor =
    $conn->prepare($sqlAutor);

$stmtAutor->bind_param(
    "i",
    $autorId
);

$stmtAutor->execute();

$resultadoAutor =
    $stmtAutor->get_result();


/*
 * Se o autor não existir ou não possuir
 * nenhuma pesquisa aprovada, não permite
 * acesso direto ao perfil público.
 */
if ($resultadoAutor->num_rows !== 1) {

    $stmtAutor->close();

    header("Location: autores.php");
    exit;
}


$autor =
    $resultadoAutor->fetch_assoc();


/*
 * Busca somente pesquisas aprovadas
 * relacionadas ao autor.
 */
$sqlPesquisas = "
    SELECT
        p.id,
        p.titulo,
        p.descricao,
        p.solo_informado,
        p.cultivo_informado,
        p.dataAprovacao,

        r.nome AS regiao,

        COALESCE(
            AVG(av.nota),
            0
        ) AS media_avaliacao,

        COUNT(av.id)
            AS quantidade_avaliacoes

    FROM pesquisas p

    INNER JOIN regioes r
        ON p.regiao_id = r.id

    LEFT JOIN avaliacoes_pesquisas av
        ON av.pesquisa_id = p.id

    WHERE
        p.autor_id = ?
        AND p.status = 'Aprovada'

    GROUP BY
        p.id,
        p.titulo,
        p.descricao,
        p.solo_informado,
        p.cultivo_informado,
        p.dataAprovacao,
        r.nome

    ORDER BY p.dataAprovacao DESC
";

$stmtPesquisas =
    $conn->prepare($sqlPesquisas);

$stmtPesquisas->bind_param(
    "i",
    $autorId
);

$stmtPesquisas->execute();

$resultadoPesquisas =
    $stmtPesquisas->get_result();


$cssPagina = "autores.css";

require_once "../includes/header.php";
require_once "../includes/menu.php";

?>

<main class="pagina-autores">

    <section class="conteudo-autor-detalhes">

        <div class="cabecalho-autor-detalhes">

            <a
                href="autores.php"
                class="botao-voltar"
            >
                Voltar
            </a>

        </div>


        <article class="perfil-autor">

            <div class="imagem-autor-detalhes">

                <?php
                if (!empty($autor["foto_exibicao"])) :
                ?>

                    <img
                        src="<?php
                        echo htmlspecialchars(
                            $autor["foto_exibicao"]
                        );
                        ?>"
                        alt="Foto de <?php
                        echo htmlspecialchars(
                            $autor["nome"]
                        );
                        ?>"
                    >

                <?php else : ?>

                    <div
                        class="
                            autor-sem-foto
                            autor-sem-foto-grande
                        "
                    >

                        <?php
                        echo strtoupper(
                            mb_substr(
                                $autor["nome"],
                                0,
                                1
                            )
                        );
                        ?>

                    </div>

                <?php endif; ?>

            </div>


            <div class="dados-autor-detalhes">

                <h1>
                    <?php
                    echo htmlspecialchars(
                        $autor["nome"]
                    );
                    ?>
                </h1>


                <?php
                if (!empty($autor["instituicao"])) :
                ?>

                    <p class="instituicao-autor-detalhes">

                        <strong>
                            Instituição:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $autor["instituicao"]
                        );
                        ?>

                    </p>

                <?php endif; ?>


                <div class="biografia-autor-detalhes">

                    <h2>
                        Sobre o autor
                    </h2>


                    <?php
                    if (!empty($autor["biografia"])) :
                    ?>

                        <p>
                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $autor["biografia"]
                                )
                            );
                            ?>
                        </p>

                    <?php else : ?>

                        <p>
                            Informações adicionais sobre
                            este autor ainda não foram
                            cadastradas.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </article>


        <section class="pesquisas-do-autor">

            <div class="titulo-pesquisas-autor">

                <h2>
                    Pesquisas deste autor
                </h2>

                <p>
                    Consulte as pesquisas aprovadas
                    relacionadas a este autor.
                </p>

            </div>


            <?php
            if ($resultadoPesquisas->num_rows === 0) :
            ?>

                <div class="sem-autores">

                    <h3>
                        Nenhuma pesquisa disponível
                    </h3>

                    <p>
                        Este autor ainda não possui
                        pesquisas aprovadas no acervo.
                    </p>

                </div>

            <?php else : ?>

                <div class="cards-pesquisas-autor">

                    <?php
                    while (
                        $pesquisa =
                        $resultadoPesquisas->fetch_assoc()
                    ) :
                    ?>

                        <article class="card-pesquisa-autor">

                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $pesquisa["titulo"]
                                );
                                ?>
                            </h3>


                            <div class="dados-pesquisa-autor">

                                <p>
                                    <strong>
                                        Região:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $pesquisa["regiao"]
                                    );
                                    ?>
                                </p>


                                <p>
                                    <strong>
                                        Solo:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $pesquisa[
                                            "solo_informado"
                                        ]
                                    );
                                    ?>
                                </p>


                                <p>
                                    <strong>
                                        Cultivo:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $pesquisa[
                                            "cultivo_informado"
                                        ]
                                    );
                                    ?>
                                </p>

                            </div>


                            <?php

                            $media = number_format(
                                (float)$pesquisa[
                                    "media_avaliacao"
                                ],
                                1,
                                ",",
                                "."
                            );

                            ?>


                            <div class="avaliacao-autor">

                                <strong>
                                    ★ <?php echo $media; ?>
                                </strong>

                                <span>
                                    (
                                    <?php
                                    echo (int)$pesquisa[
                                        "quantidade_avaliacoes"
                                    ];
                                    ?>
                                    avaliações)
                                </span>

                            </div>


                            <?php
                            if (
                                !empty(
                                    $pesquisa["descricao"]
                                )
                            ) :
                            ?>

                                <p class="descricao-pesquisa-autor">

                                    <?php

                                    $descricao =
                                        $pesquisa["descricao"];

                                    if (
                                        mb_strlen(
                                            $descricao
                                        ) > 160
                                    ) {

                                        $descricao =
                                            mb_substr(
                                                $descricao,
                                                0,
                                                160
                                            ) . "...";
                                    }

                                    echo htmlspecialchars(
                                        $descricao
                                    );

                                    ?>

                                </p>

                            <?php endif; ?>


                            <a
                                href="pesquisa.php?id=<?php
                                echo $pesquisa["id"];
                                ?>"
                                class="botao-ver-pesquisa-autor"
                            >
                                Ver pesquisa
                            </a>

                        </article>

                    <?php endwhile; ?>

                </div>

            <?php endif; ?>

        </section>

    </section>

</main>

<?php

$stmtPesquisas->close();
$stmtAutor->close();

require_once "../includes/footer.php";

?>
