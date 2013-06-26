<?php

function get_delibera_header() {
    $opt = delibera_get_config();
	?>
	
	<header class="clearfix">
        <div class="alignleft">
            <h1><?php echo $opt['cabecalho_arquivo']; ?></h1>
            
            <p>
                <?php
                if (is_user_logged_in()) {
                    global $current_user;
                    get_currentuserinfo();
                    
                    printf(
                        __('Você está logado como <a href="%1$s" title="Ver meu perfil" class="profile">%2$s</a>. Caso deseje sair de sua conta, <a href="%3$s" title="Sair">faça o logout</a>.', 'delibera'),
                        get_author_posts_url($current_user->ID),
                        $current_user->display_name,
                        wp_logout_url(home_url('/'))
                    );
                } else {   
                    printf(
                        __('Para participar, você precisa <a href="%1$s" title="Faça o login">fazer o login</a> ou <a href="%2$s" title="Registre-se" class="register">registrar-se no site</a>.', 'delibera'), 
                        wp_login_url(home_url('/')),
                        site_url('wp-login.php?action=register', 'login')."&lang="
                    );
                }
                ?>          
            </p>
        </div>
        <div class="alignright">
            <a class="btn" href="<?php echo get_page_link(get_page_by_slug(DELIBERA_ABOUT_PAGE)->ID); ?>"><?php _e('Saiba por que e como participar', 'delibera'); ?></a>
        </div>
    </header>

	<?php
}

/**
 * Formulário do comentário que é usado para
 * aprovar, discutir e votar nas pautas.
 * 
 * Manipula com array usado pelo Wordpress para 
 * compor o formulário de comentário.
 * 
 * @param array $defaults
 * @return array
 */
function delibera_comment_form($defaults)
{
    global $post, $delibera_comments_padrao, $user_identity, $comment_footer;
    $comment_footer = "";
    
    if ($delibera_comments_padrao === true) {
        $defaults['fields'] = $defaults['must_log_in'];
        
        if (!is_user_logged_in()) {
            $defaults['comment_field'] = "";
            $defaults['logged_in_as'] = '';
            $defaults['comment_notes_after'] = "";
            $defaults['label_submit'] = "";
            $defaults['id_submit'] = "botao-oculto";
            $defaults['comment_notes_before'] = ' ';
        }
        
        return $defaults;
    }
    
    if (get_post_type($post) == "pauta") {
        $current_user = wp_get_current_user();
        $defaults['id_form'] = 'delibera_commentform';
        $defaults['comment_field'] = '<div class="delibera_before_fields">'.$defaults['comment_field'];
        $situacao = delibera_get_situacao($post->ID);
        
        switch ($situacao->slug) { 
            case 'validacao':
                $user_comments = delibera_get_comments($post->ID, 'validacao', array('user_id' => $current_user->ID));
                $temvalidacao = false;
                foreach ($user_comments as $user_comment) {
                    if (get_comment_meta($user_comment->comment_ID, 'delibera_comment_tipo', true) == 'validacao') {
                        $temvalidacao = true;
                        break;
                    }
                }

                if ($temvalidacao) {
                    $defaults['comment_notes_after'] = '
                        <script type="text/javascript">
                            jQuery(document).ready(function() {
                                jQuery("#respond").hide();
                                jQuery(".reply").hide();
                            });
                        </script>
                    ';
                } else {
                    $defaults['title_reply'] = __('Você quer ver essa pauta posta em discussão?','delibera');
                    $defaults['must_log_in'] = sprintf(__('Você precisar <a href="%s">estar logado</a> e ter permissão para votar.','delibera'), wp_login_url(apply_filters('the_permalink', get_permalink($post->ID))));                
                    if (current_user_can('votar')) {
                        $form = '
                            <div id="painel_validacao" class="actions textcenter">
                                <button class="btn btn-success"><input id="delibera_aceitar" type="radio" name="delibera_validacao" value="S" checked style="display: none;" />Sim</button>
                                <button class="btn btn-danger"><input id="delibera_rejeitar" type="radio" name="delibera_validacao" value="N" style="display: none;" />Não</button>
                                <input name="comment" value="A validação de '.$current_user->display_name.' foi registrada no sistema." style="display:none;" />
                                <input name="delibera_comment_tipo" value="validacao" style="display:none;" />
                            </div>
                            <div class="votes">
                                <div class="votes-agree">
                                    <h3>Pessoas que votaram a favor (123)</h3>
                                    <ul class="clearfix">
                                        <li><img src="http://1.gravatar.com/avatar/9450ed14e26e47efb94ae3cc40d1e891?s=44&d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D44&r=G" alt="Nome Sobrenome" title="Nome Sobrenome"/></li>
                                    </ul>
                                </div>
                                <div class="votes-disagree">
                                    <h3>Pessoas que votaram contra (321)</h3>
                                    <ul class="clearfix">
                                        <li><img src="http://1.gravatar.com/avatar/9450ed14e26e47efb94ae3cc40d1e891?s=44&d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D44&r=G" alt="Nome Sobrenome" title="Nome Sobrenome"/></li>
                                    </ul>
                                </div>
                            </div>
                        ';
                        $defaults['comment_field'] = $form;
                        $defaults['comment_notes_after'] = '<script type="text/javascript">jQuery(document).ready(function() { jQuery(\'input[name="submit"]\').hide(); });</script><div class="delibera_comment_button">';;
                        $defaults['logged_in_as'] = "";
                        $defaults['label_submit'] = "__('Votar','delibera')";
                        $comment_footer = "</div>";
                    } else {
                        $defaults['comment_field'] = "";
                        $defaults['logged_in_as'] = '<p class="logged-in-as">' . sprintf( __('Você está logado como <a href="%1$s">%2$s</a> que não é um usuário autorizado a votar. <a href="%3$s" title="Sair desta conta?">Sair desta conta</a> e logar com um usuário com permissão de votar?','delibera') , admin_url('profile.php'), $user_identity, wp_logout_url(apply_filters('the_permalink', get_permalink($post->ID)))) . '</p>';
                        $defaults['comment_notes_after'] = "";
                        $defaults['label_submit'] = "";
                        $defaults['id_submit'] = "botao-oculto";
                    }
                }
                break;
            case 'discussao':
            case 'relatoria':
                $defaults['title_reply'] = sprintf(__('Discussão em torno de "%s"','delibera'),$post->post_title);
                $defaults['must_log_in'] = sprintf(__('Você precisar <a href="%s">estar logado</a> para contribuir com a discussão.','delibera'), wp_login_url(apply_filters('the_permalink', get_permalink($post->ID))));
                $defaults['comment_notes_after'] = "";
                $defaults['logged_in_as'] = "";
                $defaults['comment_field'] = '<input name="delibera_comment_tipo" value="discussao" style="display:none;" />' . $defaults['comment_field'];
                
                if($situacao->slug == 'relatoria') {
                    $defaults['comment_field'] = '<input id="delibera-baseouseem" name="delibera-baseouseem" value="" style="display:none;" autocomplete="off" />
                        <div id="painel-baseouseem" class="painel-baseouseem"><label id="painel-baseouseem-label" class="painel-baseouseem-label" >' . __('Proposta baseada em:', 'delibera') . '&nbsp;</label></div><br/>'
                        . $defaults['comment_field'];
                }
                
                if (current_user_can('votar')) {   
                    $replace = '' . (($situacao->slug != 'relatoria') ? '<label class="delibera-encaminha-label" /><input type="radio" name="delibera_encaminha" value="N" checked="checked" />' . __('Opinião', 'delibera') . '</label>' : '') 
                    . '<label class="delibera-encaminha-label" ><input type="radio" name="delibera_encaminha" value="S" ' . (($situacao->slug == 'relatoria') ? ' checked="checked" ' : '') . ' />' . __('Proposta de encaminhamento', 'delibera') . '</label>';
                    $defaults['comment_field'] = preg_replace ("/<label for=\"comment\">(.*?)<\/label>/", $replace, $defaults['comment_field']);
                } else {
                    $defaults['comment_field'] = "";
                    $defaults['logged_in_as'] = '<p class="logged-in-as">' . sprintf( __('Você está logado como <a href="%1$s">%2$s</a> que não é um usuário autorizado a votar. <a href="%3$s" title="Sair desta conta?">Sair desta conta</a> e logar com usuário que possa votar?','delibera') , admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post->ID ) ) ) ) . '</p>';
                    $defaults['comment_notes_after'] = "";
                    $defaults['label_submit'] = "";
                    $defaults['id_submit'] = "botao-oculto";
                }
                
                if (has_filter('delibera_discussao_comment_form')) {
                    $defaults = apply_filters('delibera_discussao_comment_form', $defaults, $situacao->slug);
                }
                break;
            case 'emvotacao':
                $user_comments = delibera_get_comments($post->ID, 'voto', array('user_id' => $current_user->ID));
                $temvoto = false;
                
                foreach ($user_comments as $user_comment) {
                    if(get_comment_meta($user_comment->comment_ID, 'delibera_comment_tipo', true) == 'voto') {
                        $temvoto = true;
                        break;
                    }
                }
                
                if ($temvoto) {
                    $defaults['comment_notes_after'] = '
                        <script type="text/javascript">
                            var formdiv = document.getElementById("respond");
                            formdiv.style.display = "none";
                        </script>
                    ';
                } else {
                    $defaults['title_reply'] = sprintf(__('Regime de votação para a pauta "%s"','delibera'), $post->post_title);
                    $defaults['must_log_in'] = sprintf(__('Você precisar <a href="%s">estar logado</a> e ter permissão para votar.'), wp_login_url(apply_filters('the_permalink', get_permalink($post->ID))));
                    $encaminhamentos = array();
                    
                    if (current_user_can('votar')) {
                        $form = '<div id="encaminhamentos" class="delibera_checkbox_voto">';
                        $encaminhamentos = delibera_get_comments_encaminhamentos($post->ID);
                        
                        $form .= '<div class="instrucoes-votacao">'.__('Escolha os encaminhamentos que deseja aprovar e depois clique em "Votar":','delibera').'</div>';
                        $form .= '<ol class="encaminhamentos">';
                        
                        $i = 0;
                        foreach ($encaminhamentos as $encaminhamento) {
                            $form .= '<li class="encaminhamento clearfix">
                                <div class="alignleft checkbox">
                                    <input type="checkbox" name="delibera_voto'.$i.'" id="delibera_voto'.$i.'" value="'.$encaminhamento->comment_ID.'" />
                                </div>
                                <div class="alignleft content">
                                    <label for="delibera_voto'.$i++.'" class="label-voto">'.$encaminhamento->comment_content.'</label>
                                </div>
                            </li>';
                        }
                        
                        $form .= '</ol>';
                        $form .= '
                                <input name="delibera_comment_tipo" value="voto" style="display:none;" />
                                <input name="comment" value="O voto de '.$current_user->display_name.' foi registrado no sistema" style="display:none;" />
                            </div>'
                        ;
                        
                        $defaults['comment_field'] = $form;
                        $defaults['logged_in_as'] = "";
                        $defaults['label_submit'] = __('Votar','delibera');
                        $defaults['comment_notes_after'] = '<ol class="encaminhamentos"><li class="submit">';;
                        $comment_footer = "</li></ol>";
                    } else {
                        $defaults['comment_field'] = "";
                        $defaults['logged_in_as'] = '<p class="logged-in-as">' . sprintf( __('Você está logado como <a href="%1$s">%2$s</a> que não é um usuário autorizado a votar. <a href="%3$s" title="Sair desta conta?">Sair desta conta</a> e logar com um usuário com permisão para votar?','delibera') , admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post->ID ) ) ) ) . '</p>';
                        $defaults['comment_notes_after'] = "";
                        $defaults['label_submit'] = "";
                        $defaults['id_submit'] = "botao-oculto";
                    }
                }

                if (has_filter('delibera_resolucoes_comment_form')) {
                    $defaults = apply_filters('delibera_resolucoes_comment_form', $defaults, $temvoto, $encaminhamentos);
                }
                break;
            case 'comresolucao':
                $defaults['comment_notes_after'] = '<script type="text/javascript">
                    var formdiv = document.getElementById("respond");
                    formdiv.style.display = "none";
                </script>';
                if (has_filter('delibera_comresolucao_comment_form')) {
                    $defaults = apply_filters('delibera_comresolucao_comment_form', $defaults);
                }
                break;
        }

        if (!is_user_logged_in()) {
            $defaults['comment_notes_before'] = '<script type="text/javascript">
                    var formdiv = document.getElementById("respond");
                    formdiv.style.display = "none";
            </script>';
        }
    }

    return $defaults;   
}
add_filter('comment_form_defaults', 'delibera_comment_form');

add_action('wp_enqueue_scripts', function() {
    global $deliberaThemes, $post;
    
    if (get_post_type() == 'pauta') {
        $situation = delibera_get_situacao($post->ID);
        
        wp_enqueue_script('delibera-hacklab', $deliberaThemes->getThemeUrl() . '/js/delibera-hacklab.js', array('jquery'));
        wp_localize_script('delibera-hacklab', 'delibera', array('situation' => $situation->slug));
    }
});

/**
 * Implementa o filtro da página de listagem de pautas
 * 
 * @return null
 */
function delibera_hacklab_filter_pautas($query) {
    if (is_post_type_archive('pauta') && !is_admin()) {
        $situacoes = array();
        $temas = array();
        $taxonomy_filters = array('relation' => 'AND');
        
        if (!empty($_GET['situacao_filtro'])) {
            foreach ($_GET['situacao_filtro'] as $situacao => $value) {
                if ($value == 'on') {
                    $situacoes[] = $situacao;
                }
            }
        }

        if (!empty($_GET['tema_filtro'])) {
            foreach ($_GET['tema_filtro'] as $tema => $value) {
                if ($value == 'on') {
                    $temas[] = $tema;
                }
            }
        }

        if (!empty($situacoes)) {
            $taxonomy_filters[] = array('taxonomy' => 'situacao', 'field' => 'slug', 'terms' => $situacoes);
        }
        
        if (!empty($temas)) {
            $taxonomy_filters[] = array('taxonomy' => 'tema', 'field' => 'slug', 'terms' => $temas);
        }
        
        $query->set('tax_query', $taxonomy_filters);
    }
}
add_action('pre_get_posts', 'delibera_hacklab_filter_pautas');
