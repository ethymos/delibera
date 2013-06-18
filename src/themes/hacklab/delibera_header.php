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
