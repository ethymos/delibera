<?php

wp_enqueue_script('hacklab-filters', $deliberaThemes->getThemeUrl() . '/js/hacklab-filters.js', array('jquery'));

if (!isset($_GET['situacao_filtro'])) {
    $_GET['situacao_filtro'] = array();
}

if (!isset($_GET['tema_filtro'])) {
    $_GET['tema_filtro'] = array();
}

get_header();

?>

<div id="delibera">
    <div id="container">
        <div id="content" role="main">
            <?php get_delibera_header(); ?>
            <div class="clearfix">
                <div class="filters widget-area alignleft">
                    <h2>Filtros</h2>
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
                </div>
                <div id="lista-de-pautas" class="site-content alignright">
                    <?php load_template(dirname(__FILE__) . '/delibera-loop-archive.php', true); ?>
                    <?php
                    
                    global $wp_query;
                    $big = 99999999; // need an unlikely integer
                    
                    echo paginate_links(array(
                        'base' => str_replace($big, '%#%', get_pagenum_link($big)),
                        'format' => '?paged=%#%',
                        'total' => $wp_query->max_num_pages,
                        'current' => max(1, get_query_var('paged')),
                    ));

                    ?>
                    
                    <nav class="navigation">
                        <ol>
                            <li><a href="">1</a></li>
                            <li><a href="">2</a></li>
                            <li class="current"><a href="">3</a></li>
                            <li><a href="">4</a></li>
                            <li><a href="">5</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
