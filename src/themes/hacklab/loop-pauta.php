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
            <p class="meta">Discussão criada por <span class="author"><a class="url fn n" href="<?php the_author_meta('user_url'); ?>" title="<?php printf('Ver o perfil de %s', get_the_author()); ?>"><?php the_author(); ?></a></span> em <span class="date"><?php the_date('d/m/y'); ?></span></p>
            <div class="content"><?php the_content(); ?></div>
            <div class="actions textcenter">
                <h2>Você quer ver essa pauta posta em discussão?</h2>
                <button class="btn btn-success">Sim</button> <button class="btn btn-danger">Não</button>
            </div>
        </div>
				<?php comments_template( '', true ); ?>

    <?php endwhile; ?>
<?php endif; ?>