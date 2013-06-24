<?php if (have_posts()) :
    while (have_posts()) :
        the_post(); ?>

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
                    <a href="" class="btn"><i class="icon-print"></i> Imprimir</a>
                    <a href="" class="btn"><i class="icon-star-empty"></i> Seguir</a>
                </div>
            </div>

            <div class="content"><?php the_content(); ?></div>
            
            <div class="meta">
                <ul class="meta meta-tags">
                    <li>Tema:</li>
                    <li><a href="">Tema 1</a>,</li>
                    <li><a href="">Tema 2</a>,</li>
                    <li><a href="">Tema 3</a>,</li>
                    <li><a href="">Tema 4</a>,</li>
                    <li><a href="">Tema 5</a></li>
                </ul>
            
                <ul class="meta meta-tags">
                    <li>Tags:</li>
                    <li><a href="">Tag 1</a>,</li>
                    <li><a href="">Tag 2</a>,</li>
                    <li><a href="">Tag 3</a>,</li>
                    <li><a href="">Tag 4</a>,</li>
                    <li><a href="">Tag 5</a></li>
                </ul>
            </div>
            
            
            <?php comments_template( '', true ); ?>
        </div>
    <?php endwhile; ?>
<?php endif; ?>