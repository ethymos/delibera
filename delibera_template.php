<?php
/**
 * Cria nova página no WP utilizando fake_page
 * @package Pauta\Admin
 */

/**
 * Adiciona item no menu do admin do WP para escolha de template padrão
 * @deprecated - não é possível ver esse item no admin do wordpress
 * @param string $base_page - 
 */
function delibera_template_menu_action($base_page)
{
    require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_fake_page.php';

    add_submenu_page($base_page, __('Templates','delibera'), __('Templates Padrões','delibera'), 'manage_options', 'delibera-templates', 'delibera_template_page' );
}
add_action('delibera_menu_itens', 'delibera_template_menu_action', 10, 1);

/**
 * Carrega template de listagem de pautas
 *
 * @deprecated - não tem nenhuma implementação, está comentado
 */
function delibera_template_page()
{
    //load_template(dirname(__FILE__).DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'archive-pauta.php', true);
}