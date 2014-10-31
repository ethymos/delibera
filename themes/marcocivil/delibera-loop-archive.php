<?php

if (have_posts()) :
    while (have_posts()) :
        the_post();
        $temas = wp_get_post_terms($post->ID, 'tema');
        
        $situacao = delibera_get_situacao($post->ID);
        ?>
        <div class="topic">
            <div class="meta clearfix mb-xs">
                <p class="status pull-left fontsize-sm text-muted"> <i class="fa fa-users"></i><?php echo $situacao->name; ?></p>
                <p class="deadline pull-left ml-lg fontsize-sm text-muted"> <i class="fa fa-calendar"></i>
                    <?php if (delibera_get_prazo($post->ID) == -1) {
                        echo 'Prazo encerrado';
                    } else {
                        printf(_n('Encerra em um dia', 'Encerra em %1$s dias', delibera_get_prazo($post->ID), 'delibera'), number_format_i18n(delibera_get_prazo($post->ID)));
                    } ?>
                </p>
            </div>
            <h3><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
            <p class="meta fontsize-sm">Discussão criada por <strong class="author text-danger"><?php the_author(); ?></strong> em <strong class="date text-danger"><?php echo get_the_date('d/m/y'); ?></strong></p>
            <p><?php the_excerpt(); ?></p>

            <?php if (!empty($temas)) : ?>
                <p class="meta fontsize-sm text-muted">Tema: <a href="#" class="ml-sm">
                    <?php $size = count($temas) - 1; ?>
                    <?php foreach ($temas as $key => $tema) : ?>
                        <a href="<?php echo get_post_type_archive_link('pauta') . "?tema_filtro[{$tema->slug}]=on"; ?>"><?php echo $tema->name; ?></a><?php echo ($key != $size) ? ',' : ''; ?>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>

            <div class="clearfix">
                <?php $label = delibera_get_comments_count_by_type($post->ID); ?>
                <?php if ($label) : ?>
                    <p class="pull-left fontsize-sm text-muted"><i class="fa fa-comments-o"></i>
                        <a href="<?php the_permalink(); ?>#comments"><?php echo $label; ?></a>
                    </p>
                <?php endif; ?>
                <?php if (in_array($situacao->slug, array('emvotacao', 'discussao', 'validacao'))) : ?>
                    <p class="pull-left fontsize-sm ml-lg"><a href="#"><i class="fa fa-comment"></i>
                        <a href="<?php the_permalink() ?>"><?php echo delibera_get_situation_button($post->ID); ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php
    endwhile;
endif; ?>
