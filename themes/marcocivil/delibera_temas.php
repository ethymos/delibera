<?php
global $deliberaThemes;

get_header();?>
<div class="row">
<?php
    get_template_part('logo', 'mci');
    get_template_part('menu', 'interno');
?>
</div>

<div class="layer-mci">
    <div class="row">
        <div class="col-md-4 mb-lg text-center">
            <a href="#"><img src="<?php echo $deliberaThemes->themeFileUrl('img/neutralidade.jpg') ?>"></a>
            <p class="mt-sm item-iclin-001"><a href="<?php echo get_site_url(); ?>/tema/neutralidade/" class="titulos-mci">Neutralidade</a></p>
        </div>
        <div class="col-md-4 mb-lg text-center">
            <a href="#"><img src="<?php echo $deliberaThemes->themeFileUrl('img/protecao.jpg'); ?>"></a>
            <p class="mt-sm item-iclin-002"><a href="<?php echo get_site_url(); ?>/tema/protecao-dos-dados-pessoais/" class="titulos-mci">Proteção dos dados pessoais</a></p>
        </div>
        <div class="col-md-4 mb-lg text-center">
            <a href="#"><img src="<?php echo $deliberaThemes->themeFileUrl('img/apuracao.jpg'); ?>"></a>
            <p class="mt-sm item-iclin-001"><a href="http://localhost/marcocivil/tema/apuracao-de-infracoes-a-lei/" class="titulos-mci">Apuração de infrações à Lei</a></p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-lg text-center">
            <a href="#"><img src="<?php echo $deliberaThemes->themeFileUrl('img/registro.jpg'); ?>"></a>
            <p class="mt-sm item-iclin-001"><a href="<?php echo get_site_url(); ?>/tema/protecao-dos-dados-pessoais/" class="titulos-mci">Registros de acesso</a></p>
        </div>
        <div class="col-md-6 mb-lg text-center">
            <a href="#"><img src="<?php echo $deliberaThemes->themeFileUrl('img/politicas.jpg'); ?>"></a>
            <p class="mt-sm item-iclin-002"><a href="<?php echo get_site_url(); ?>/tema/politicas-publicas-de-internet/" class="titulos-mci">Políticas públicas de internet</a></p>
        </div>
    </div>
</div>

<?php get_footer(); ?>
