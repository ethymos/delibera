<?php 
get_header(); 
$situacao = delibera_get_situacao(get_the_ID());
?>

<div class="row">
    <?php
    get_template_part('logo', 'mci');
    get_template_part('menu', 'interno');
    ?>
</div>

<div class="layer-mci situacao-<?php echo $situacao->slug; ?>">
    <div id="content" role="main">
        <?php

        load_template(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'loop-pauta.php', true);

        ?>
    </div><!-- #content -->
</div>

<?php get_footer(); ?>
