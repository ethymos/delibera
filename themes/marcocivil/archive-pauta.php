<?php

wp_enqueue_script('creta-filters', $deliberaThemes->getThemeUrl() . '/js/creta-filters.js', array('jquery'));

if (!isset($_GET['situacao_filtro'])) {
    $_GET['situacao_filtro'] = array();
}

if (!isset($_GET['tema_filtro'])) {
    $_GET['tema_filtro'] = array();
}

get_header();

$options = delibera_get_config();

$tema = $wp_query->get_queried_object();

$archive_tema = false;
if (is_object($tema) && $tema->taxonomy == "tema") {
    $archive_tema = true;
}

?>
<div class="row">
    <?php
    get_template_part('logo', 'mci');
    get_template_part('menu', 'interno');
    ?>
</div>

    <div class="layer-mci <?php echo $tema->slug; ?>" role="main">
        <div class="row">
            <div class="col-sm-2 imagem-destaque-tema">
                <img src="<?php echo $deliberaThemes->themeFileUrl('img/neutralidade.jpg'); ?>">
            </div>
            <div class="col-sm-8">

                <?php if ($archive_tema): ?>
                        <h4 class="mb-lg item-iclin-001">
                            <span class="titulos-mci-h4"><?php echo $tema->name; ?></span>
                        </h4>
                <?php
                        echo $tema->description;
                    else:
                        get_delibera_header(); ?>
                        <h2>Filtrar Pautas</h2>
                        <form>
                            <ul class="status">
                                <?php foreach (get_terms('situacao') as $situacao) : ?>
                                    <li>
                                    <span class="<?php echo (isset($_GET['situacao_filtro'][$situacao->slug]) && $_GET['situacao_filtro'][$situacao->slug] == 'on') ? 'selected' : ''; ?>">
                                        <?php echo $situacao->name; ?>
                                        <input type="hidden" name="situacao_filtro[<?php echo $situacao->slug; ?>]" value="<?php echo (isset($_GET['situacao_filtro'][$situacao->slug]) && $_GET['situacao_filtro'][$situacao->slug] == 'on') ? 'on' : ''; ?>" />
                                    </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <ul>
                                <?php foreach (get_terms('tema') as $tema) : ?>
                                    <li><label class="checkbox"><input type="checkbox" name="tema_filtro[<?php echo $tema->slug; ?>]" <?php echo (isset($_GET['tema_filtro'][$tema->slug]) && $_GET['tema_filtro'][$tema->slug] == 'on') ? ' checked="checked" ' : ''; ?> /><?php echo $tema->name; ?></label></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="textright">
                                <button type="submit" class="btn">Filtrar</button>
                            </div>
                        </form>
                <?php
                    endif;
                ?>
                <h3 class="mt-lg">Lista de pautas sendo discutidas nesse tema</h3>
                <div class="row">
                    <div id="lista-de-pautas" class="col-md-12 clearfix">
                        <?php load_template(dirname(__FILE__) . '/delibera-loop-archive.php', true); ?>
                        <?php

                        global $wp_query;
                        $big = 99999999; // need an unlikely integer

                        $links = paginate_links(array(
                            'base' => str_replace($big, '%#%', get_pagenum_link($big)),
                            'format' => '?paged=%#%',
                            'total' => $wp_query->max_num_pages,
                            'current' => max(1, get_query_var('paged')),
                            'type' => 'array',
                            'prev_next' => false,
                        ));

                        ?>

                        <?php if (!empty($links)) : ?>
                            <nav class="navigation">
                                <ol>
                                    <?php foreach ($links as $link) : ?>
                                        <li><?php echo $link; ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row text-right mt-md">
                    <?php if (is_user_logged_in() && isset($options['criar_pauta_pelo_front_end']) && $options['criar_pauta_pelo_front_end'] == 'S') : ?>
                        <a href="<?php echo site_url('nova-pauta'); ?>" class="btn btn-danger">Criar uma nova pauta</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php get_footer(); ?>
