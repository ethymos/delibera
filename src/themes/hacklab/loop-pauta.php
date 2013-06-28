<?php if (have_posts()) :
    while (have_posts()) :
        the_post();
        $temas = wp_get_post_terms($post->ID, 'tema');
        ?>

        <div class="topic clearfix">
            <div class="meta textright clearfix">
                <span class="status"><?php echo delibera_get_situacao($post->ID)->name; ?></span>
                <span class="deadline">
                    <?php if (delibera_get_prazo($post->ID) == 0) {
                        echo 'Prazo encerrado';
                    } else {
                        printf(_n('Encerra em um dia', 'Encerra em %1$s dias', delibera_get_prazo($post->ID), 'delibera'), number_format_i18n(delibera_get_prazo($post->ID)));
                    } ?>
                </span>
            </div>
            <h1><a href=""><?php the_title(); ?></a></h1>
            <p class="meta clearfix">Discussão criada por <span class="author"><a class="url fn n" href="<?php the_author_meta('user_url'); ?>" title="<?php printf('Ver o perfil de %s', get_the_author()); ?>"><?php the_author(); ?></a></span> em <span class="date"><?php the_date('d/m/y'); ?></span></p>

            <div class="meta meta-social clearfix">
                <a href="" class="btn btn-facebook">Facebook</a>
                <a href="" class="btn btn-twitter">Twitter</a>
                <a href="" class="btn btn-google-plus">Google+</a>
                <div class="alignright bottom">
                    <a href="?delibera_print=1" class="btn"><i class="icon-print"></i> Imprimir</a>
                    <a href="" class="btn"><i class="icon-star-empty"></i> Seguir</a>
                </div>
            </div>

            <div class="content"><?php the_content(); ?></div>
            
            <div class="meta">
                <?php if (!empty($temas)) : ?>
                    <ul class="meta meta-tags">
                        <li>Tema:</li>
                        <?php $size = count($temas) - 1; ?>
                        <?php foreach ($temas as $key => $tema) : ?>
                            <li><a href="<?php echo get_post_type_archive_link('pauta') . "?tema_filtro[{$tema->slug}]=on"; ?>"><?php echo $tema->name; ?></a><?php echo ($key != $size) ? ',' : ''; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <?php comments_template( '', true ); ?>
        </div>
    <?php endwhile; ?>
<?php endif; ?>
