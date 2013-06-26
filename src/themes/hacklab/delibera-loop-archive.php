<?php

if (have_posts()) :
    while (have_posts()) :
        the_post();
        
        $situacao = delibera_get_situacao($post->ID);
        ?>
        <div class="topic clearfix">
            <div class="meta clearfix">
                <div class="status alignleft"><?php echo $situacao->name; ?></div>
                <div class="deadline alignright">
                    <?php if (delibera_get_prazo($post->ID) == 0) {
                        echo 'Prazo encerrado';
                    } else {
                        printf(_n('Encerra em um dia', 'Encerra em %1$s dias', delibera_get_prazo($post->ID), 'delibera'), number_format_i18n(delibera_get_prazo($post->ID)));
                    } ?>
                </div>
            </div>
            <h1><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h1>
            <p class="meta">Discussão criada por <span class="author"><a class="url fn n" href="<?php the_author_meta('user_url'); ?>" title="<?php printf('Ver o perfil de %s', get_the_author()); ?>"><?php the_author(); ?></a></span> em <span class="date"><?php echo get_the_date('d/m/y'); ?></span></p>
            <p><?php the_excerpt(); ?></p>
            
            <ul class="meta meta-tags">
                <li>Tema:</li>
                <li><a href="">Tema 1</a>,</li>
                <li><a href="">Tema 2</a>,</li>
                <li><a href="">Tema 3</a>,</li>
                <li><a href="">Tema 4</a>,</li>
                <li><a href="">Tema 5</a></li>
            </ul>
            <div class="actions bottom clearfix">
                <div class="number-of-comments alignleft">
                    <a href="">123 comentários</a>
                </div>
                <?php if (in_array($situacao->slug, array('emvotacao', 'discussao', 'validacao'))) : ?>
                    <div class="alignright bottom textright">
                        <a class="btn" href="<?php the_permalink() ?>"><?php echo delibera_get_situation_button($post->ID); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php
    endwhile;
endif; ?>