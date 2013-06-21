<?php

/**
 * Baseado em no comments-template
 */

/**
 * Gera o HTML de exibição de um comentário
 * do Delibera.
 */
class Delibera_Walker_Comment extends Walker_Comment
{
    /**
     * @see Walker::start_el()
     * @since 2.7.0
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param object $comment Comment data object.
     * @param int $depth Depth of comment in reference to parents.
     * @param array $args
     */
    function start_el(&$output, $comment, $depth, $args)
    {
        $depth++;
        $GLOBALS['comment_depth'] = $depth;
        $args['avatar_size'] = '85';

        if (!empty($args['callback'])) {
            call_user_func($args['callback'], $comment, $args, $depth);
            return;
        }

        $GLOBALS['comment'] = $comment;
        
        $tipo = get_comment_meta($comment->comment_ID, "delibera_comment_tipo", true);
        $situacao = delibera_get_situacao($comment->comment_post_ID);
        
        extract($args, EXTR_SKIP);

        if ('div' == $args['style']) {
            $tag = 'div';
            $add_below = 'comment';
        } else {
            $tag = 'li';
            $add_below = 'div-comment';
        }
        
        $classes = array();
        
        if (!empty($args['has_children'])) {
            $classes[] = 'parent';
        }
        
        if ($tipo == 'encaminhamento' || $tipo == 'resolucao') {
            $classes[] = 'encaminhamento';
        }
        
        ?>

        <?php if (($tipo == 'resolucao' || $tipo == 'encaminhamento') && $situacao->slug == 'comresolucao') : ?>
            <?php $nvotos = get_comment_meta($comment->comment_ID, "delibera_comment_numero_votos", true); ?>
            <li class="encaminhamento clearfix">
                <div class="alignleft votos">
                    <span><?php echo ($nvotos == 1) ? sprintf(__('%d voto', 'delibera'), $nvotos) : sprintf(__('%d votos', 'delibera'), $nvotos); ?></span>
                </div>
                <div class="alignleft content">
                    <?php comment_text(); ?>
                </div>
            </li>    
        <?php else : ?>
            <li <?php comment_class($classes)?>>
                <article>
                    <header class="coment-meta comment-author vcard clearfix">
                        <div class="alignleft">
                            <?php echo get_avatar($comment, 44); ?>
                            <cite class="fn"><a href="<?php echo get_author_posts_url($comment->user_id); ?>" rel="external nofollow" class="url"><?php echo $comment->comment_author; ?></a></cite>
                            <a href="<?php echo htmlspecialchars(delibera_get_comment_link($comment->comment_ID)); ?>">
                                <time datetime="<?php echo get_comment_date('c'); ?>">
                                    <?php
                                    $time = mysql2date('G', $comment->comment_date);
                                    
                                    $time_diff = time() - $time;
                                    
                                    if ($time_diff > 0 && $time_diff < 30*24*60*60) {
                                        printf(__('há %s', 'delibera'), human_time_diff(mysql2date('U', $comment->comment_date, true)));
                                    } else {
                                        echo get_comment_date('d \d\e F \d\e Y à\s H:i');
                                    }
                                    
                                    ?>
                                </time>
                            </a>
                        </div>
                        <div class="alignright textright">
                            <span class="type"><?php delibera_get_comment_type_label($comment); ?></span>
                        </di>
                    </header>
                    <section class="comment-content">
                        <?php if ($comment->comment_approved == '0') : ?>
                            <em class="delibera-comment-awaiting-moderation"><?php _e('Seu comentário está aguardando moderação.', 'delibera') ?></em>
                            <br />
                        <?php endif; ?>
                        <?php comment_text(); ?>
                        <?php delibera_comment_edit_form(); ?>
                    </section>
                    <section class="actions clearfix">
                        <?php if (delibera_comments_is_open($comment->comment_post_ID) && $situacao->slug != 'emvotacao') : ?>
                            <div class="bottom alignleft">
                                <div class="reply">
                                    <?php
                                    if ($situacao->slug == 'relatoria' && is_user_logged_in()) {
                                        if ($tipo == 'encaminhamento' && current_user_can('relatoria')) {
                                            ?>
                                            <button class="btn btn-mini btn-info comment-reply-link">
                                                <?php edit_comment_link(__('Editar Encaminhamento', 'delibera'), '<p>', '</p>'); ?>
                                            </button>
                                            <?php
                                        }
                                    } else if ($situacao->slug != 'validacao' && is_user_logged_in()) {            
                                        $args['reply_text'] = __("Responder", 'delibera');
                                        ?>
                                        <button class="btn btn-mini btn-info comment-reply-link"> 
                                            <?php comment_reply_link(array_merge($args, array('add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth']))); ?>
                                        </button>
                                        <?php
                                    } else if (is_user_logged_in()) {
                                        ?>
                                        <button class="btn btn-mini btn-info comment-reply-link">
                                            <a href="<?php delibera_get_comment_link();?>#respond" class="comment-reply-link"><?php _e('De sua opinião', 'delibera'); ?></a>
                                        </button>
                                        <?php
                                    } else {
                                        ?>
                                        <button class="btn btn-mini btn-info comment-reply-link">
                                            <a href="<?php echo wp_login_url(delibera_get_comment_link());?>#respond" class="comment-reply-link"><?php _e('Faça login e de sua opinião', 'delibera'); ?></a>
                                        </button>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="bottom alignright">
                            <?php
                            
                            $curtir = delibera_gerar_curtir($comment->comment_ID, 'comment');
                            $discordar = delibera_gerar_discordar($comment->comment_ID, 'comment');
                            
                            if ($curtir) {
                                ?>
                                <button class="btn btn-mini btn-success"><?php echo $curtir; ?></button>
                                <?php
                            }
                            
                            if ($discordar) {
                                ?>
                                <button class="btn btn-mini btn-danger"><?php echo $discordar; ?></button>
                                <?php
                            }                                                
                            ?>
                        </div>
                    </section><!-- .reply -->
                    <?php
                    if ($situacao->slug == 'discussao' || ($situacao->slug == 'relatoria' && current_user_can('relatoria'))) {
                        delibera_edit_comment_link(__('(Edit)'),'&nbsp;&nbsp;', '');
                        delibera_delete_comment_link(__('(Delete)'),'&nbsp;&nbsp;', '');
                    }
    
                    if ($situacao->slug == 'relatoria' && current_user_can('relatoria'))    {
                        $baseouseem = get_comment_meta($comment->comment_ID, 'delibera-baseouseem', true);
                        if (strlen($baseouseem) > 0) {
                            $baseouseem_elements = "";
                            foreach (explode(',', $baseouseem) as $baseouseem_element) {
                                $baseouseem_elements .= do_shortcode($baseouseem_element);
                            }
                            echo '<div id="comment-painel-baseouseem" class="comment-painel-baseouseem"><label id="painel-baseouseem-label" class="painel-baseouseem-label" >'.__('Proposta baseada em:', 'delibera').'&nbsp;</label>'.$baseouseem_elements.'</div>';
                        }
                    }
                    
                    if ($tipo == "encaminhamento" && current_user_can('relatoria') && $situacao->slug == "relatoria") {
                        ?>
                        <div class="baseadoem-checkbox-div"><label class="baseadoem-checkbox-label"><input id="baseadoem-checkbox-<?php echo $comment->comment_ID; ?>" type="checkbox" name="baseadoem-checkbox[]" value="<?php echo $comment->comment_ID; ?>" class="baseadoem-checkbox" autocomplete="off" /><?php _e('basear-se neste encaminhamento?', 'delibera'); ?></label></div>
                        <?php 
                    }
                    
                    ?>
                </article>
            </li>
        <?php
        endif;
    }
}
