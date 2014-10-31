<?php if (have_posts()) :
    while (have_posts()) :
        the_post();
        $temas = wp_get_post_terms($post->ID, 'tema');

        $user_id = get_current_user_id();
        $situacao = delibera_get_situacao($post->ID);

        $seguir = false;
        if (!delibera_ja_seguiu($post->ID, $user_id) && $situacao->slug != 'relatoria') {
            $seguir = true;
        }
        ?>

        <div class="topic">
            <div class="meta clearfix mb-xs">
                <p class="status pull-left fontsize-sm text-muted"> <i class="fa fa-users"></i> <?php echo delibera_get_situacao($post->ID)->name; ?></p>
                <p class="deadline pull-left ml-lg fontsize-sm text-muted"> <i class="fa fa-calendar"></i>
                    <?php if (delibera_get_prazo($post->ID) == -1) {
                        echo 'Prazo encerrado';
                    } else {
                        printf(_n('Encerra em um dia', 'Encerra em %1$s dias', delibera_get_prazo($post->ID), 'delibera'), number_format_i18n(delibera_get_prazo($post->ID)));
                    } ?>
                </p>
            </div>

            <div class="row meta meta-social clearfix">
                <div class="col-md-12 divider-bottom">
                    <div class="pull-left fontsize-sm text-muted">
                        <a href=""><i class="fa fa-facebook-square"></i> Facebook</a>
                        <a href=""><i class="fa fa-twitter ml-md"></i> Twitter</a>
                        <a href=""><i class="fa fa-google-plus ml-md"></i> Google+</a>
                    </div>
                    <div class="pull-right fontsize-sm text-muted clearfix">
                        <p class="pull-left"><a href="?delibera_print=1"><i class="fa fa-print"></i> Imprimir</a>
                        </p>
                        <a href="#" class="pull-left ml-md" id="delibera_seguir">
                            <span id="delibera-seguir-text" <?php if (!$seguir) echo ' style="display: none;" ';?>><i class="icon-star-empty"></i> Seguir</span>
                            <span id="delibera-seguindo-text"  <?php if ($seguir) echo ' style="display: none;" ';?>><i class="icon-star"></i> Seguindo</span>
                        </a>
                    </div>
                </div>
            </div>

            <h2><?php the_title(); ?></h2>

            <p class="meta fontsize-sm">Discussão criada por
                <strong class="author text-danger"><?php the_author(); ?></strong> em
                <strong class="date text-danger"><?php the_date('d/m/y'); ?></strong>
            </p>

            <div class="content"><?php the_content(); ?></div>

            <?php if (!empty($temas)) : ?>
                <p class="meta fontsize-sm text-muted">Tema: <a href="#" class="ml-sm">
                        <?php $size = count($temas) - 1; ?>
                        <?php foreach ($temas as $key => $tema) : ?>
                            <a href="<?php echo get_post_type_archive_link('pauta') . "?tema_filtro[{$tema->slug}]=on"; ?>"><?php echo $tema->name; ?></a><?php echo ($key != $size) ? ',' : ''; ?>
                        <?php endforeach; ?>
                </p>
            <?php endif; ?>

            <?php comments_template( '', true ); ?>
        </div>
    <?php endwhile; ?>
<?php endif; ?>
