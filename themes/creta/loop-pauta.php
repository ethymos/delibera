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

        <div class="topic clearfix">
            <div class="meta textright clearfix">
                <span class="status"><?php echo delibera_get_situacao($post->ID)->name; ?></span>
                <span class="deadline">
                    <?php if (delibera_get_prazo($post->ID) == -1) {
                        echo 'Prazo encerrado';
                    } else {
                        printf(_n('Encerra em um dia', 'Encerra em %1$s dias', delibera_get_prazo($post->ID), 'delibera'), number_format_i18n(delibera_get_prazo($post->ID)));
                    } ?>
                </span>
            </div>
            <h1><a href=""><?php the_title(); ?></a></h1>
            <p class="meta clearfix">Discussão criada por <span class="author"><?php the_author(); ?></span> em <span class="date"><?php the_date('d/m/y'); ?></span></p>

           <div class="meta meta-social clearfix">
                <ul class="social-buttons cf">
                    <li>
                        <a href="http://twitter.com/share" class="socialite twitter-share" data-text="Socialite.js" data-url="http://socialitejs.com" data-count="vertical" rel="nofollow" target="_blank"><span class="vhidden">Share on Twitter</span></a>
                    </li>
                    <li>
                        <a href="https://plus.google.com/share?url=http://socialitejs.com" class="socialite googleplus-one" data-size="tall" data-href="http://socialitejs.com" rel="nofollow" target="_blank"><span class="vhidden">Share on Google+</span></a>
                    </li>
                    <li>
                        <a href="http://www.facebook.com/sharer.php?u=http://www.socialitejs.com&t=Socialite.js" class="socialite facebook-like" data-href="http://socialitejs.com" data-send="false" data-layout="box_count" data-width="60" data-show-faces="false" rel="nofollow" target="_blank"><span class="vhidden">Share on Facebook</span></a>
                    </li>
                    <li>
                        <a href="http://www.linkedin.com/shareArticle?mini=true&url=http://socialitejs.com&title=Socialite.js" class="socialite linkedin-share" data-url="http://socialitejs.com" data-counter="top" rel="nofollow" target="_blank"><span class="vhidden">Share on LinkedIn</span></a>
                    </li>
                    <li>
                        <a href="http://www.linkedin.com/shareArticle?mini=true&url=http://socialitejs.com&title=Socialite.js" class="socialite linkedin-share" data-url="http://socialitejs.com" data-counter="top" rel="nofollow" target="_blank"><span class="vhidden">Share on LinkedIn</span></a>
                    </li>
                </ul>
                <div class="alignright bottom">
                    <a href="?delibera_print=1" class="btn"><i class="icon-print"></i> Imprimir</a>
                    <button id="delibera_seguir" href="" class="btn">
                        <span id="delibera-seguir-text" <?php if (!$seguir) echo ' style="display: none;" ';?>><i class="icon-star-empty"></i> Seguir</span>
                        <span id="delibera-seguindo-text"  <?php if ($seguir) echo ' style="display: none;" ';?>><i class="icon-star"></i> Seguindo</span>
                    </button>
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
