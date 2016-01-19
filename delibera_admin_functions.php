<?php
/**
 * Realiza modificações no painel administrativo do wordpress
 */

/**
 * Função para incluir a ação de edição em massa para prazo de discussão
 *
 * @property admin_footer
 * @return null
 * @package Action
 * @subpackage Admin
 */
function delibera_custom_bulk_admin_footer() {

    $current_screen = get_current_screen();

    if($current_screen->post_type == 'pauta' && $current_screen->id == "edit-pauta") {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('<option>').val('set_prazodiscussao').text('Definir prazo de discussão').appendTo("select[name='action']");

                jQuery("select[name='action']").change(function(){
                    if (jQuery(this).val() == "set_prazodiscussao") {
                        var nova_data = prompt("Defina o novo prazo para discussão");

                        if (nova_data != null) {
                            jQuery("select[name='action']").find("option[value='set_prazodiscussao']").text('Definir prazo de discussão para ' + nova_data);
                            jQuery("select[name='action']").after("<input type='hidden' name='novo_prazo_discussao' value='"+ nova_data +"'/>");
                        }
                    }
                });
            });
        </script>
    <?php
    }
}

add_action('admin_footer', 'delibera_custom_bulk_admin_footer');

/**
 * Função que trata a ação de edição em massa do prazo de discussão
 *
 * @package Action
 * @subpackage Admin
 */
function delibera_custom_bulk_action() {

    $wp_list_table = _get_list_table('WP_Posts_List_Table');
    $action = $wp_list_table->current_action();

    switch($action) {
        case 'set_prazodiscussao':
            check_admin_referer('bulk-posts');

            $pautas_ids = $_REQUEST['post'];
            $novo_prazo_discussao = $_REQUEST['novo_prazo_discussao'];
            $inovo_prazo_discussao = DateTime::createFromFormat('d/m/Y', $novo_prazo_discussao)->getTimestamp();
            $pautas_afetadas = 0;

            foreach($pautas_ids as $pauta_id) {

                delibera_set_novo_prazo_discussao_relatoria($pauta_id, $inovo_prazo_discussao, delibera_get_config());

                $pautas_afetadas++;
            }

            $sendback = admin_url( "edit.php?post_type=pauta&pautas_afetadas=$pautas_afetadas&novo_prazo=$novo_prazo_discussao");

            wp_redirect($sendback);

            exit();

        break;
    }
}

add_action('load-edit.php', 'delibera_custom_bulk_action');

/**
 * Função que exibe a mensagem de confirmação da alteração em massa
 *
 * @package Action
 * @subpackage Admin
 */
function delibera_custom_bulk_admin_notices() {

    $current_screen = get_current_screen();

    if($current_screen->post_type == 'pauta' && $current_screen->id == "edit-pauta" &&
        isset($_REQUEST['pautas_afetadas']) && (int) $_REQUEST['pautas_afetadas']) {
        $mensagem = sprintf( '%s pautas definidas para o prazo de %s.', number_format_i18n( $_REQUEST['pautas_afetadas']), $_REQUEST['novo_prazo'] );
        echo "<div class='updated'><p>{$mensagem}</p></div>";
  }
}

add_action('admin_notices', 'delibera_custom_bulk_admin_notices');

/**
 *
 * Comentário na tela de Edição na administração
 * @param WP_comment $comment
 *
 * @property add_meta_boxes_comment
 * @package Action
 * @subpackage Admin
 */
function delibera_edit_comment($comment)
{
	if(get_post_type($comment->comment_post_ID) == "pauta")
	{
		$tipo = get_comment_meta($comment->comment_ID, "delibera_comment_tipo", true);
		switch ($tipo)
		{
			case 'validacao':
			{
				$validacao = get_comment_meta($comment->comment_ID, "delibera_validacao", true);
				$sim = ($validacao == "S" ? true : false);
				?>
				<div id="painel_validacao delibera-comment-text" >
					<?php if($sim){ ?>
					<label class="delibera-aceitou-view"><?php _e('Aceitou','delibera'); ?></label>
					<?php }else { ?>
					<label class="delibera-rejeitou-view"><?php _e('Rejeitou','delibera'); ?></label>
					<?php } ?>
				</div>
				<script type="text/javascript">
					var quickdiv = document.getElementById('postdiv');
					quickdiv.style.display = 'none';
				</script>
				<?php
			}break;
			case 'discussao':
			case 'encaminhamento':
			{
                if (delibera_pautas_suportam_encaminhamento()) {
                    $tipo = get_comment_meta($comment->comment_ID, "delibera_comment_tipo", true);
                    $checked = $tipo == "discussao" ? "" : ' checked="checked" ';
                    ?>
                    <label class="delibera-encaminha-label">
                        <input type="radio" name="delibera_encaminha"
                               value="N" <?php checked($tipo, 'discussao'); ?> /><?php _e('Opinião', 'delibera'); ?>
                    </label>
                    <label class="delibera-encaminha-label">
                        <input type="radio" name="delibera_encaminha"
                               value="S" <?php checked($tipo, 'encaminhamento'); ?> /><?php _e('Proposta de encaminhamento', 'delibera'); ?>
                    </label>

                <?php

                } else { ?>
                    <input type="hidden" name="delibera_encaminha" value="N" />
                <?php }
			}break;
		}
	}
}

add_filter('add_meta_boxes_comment', 'delibera_edit_comment');

/**
 * Internacionaliza label das propriedades
 *
 * @package Action
 * @subpackage Admin
 *
 */
 function delibera_edit_columns($columns)
{
	$columns[ 'tema' ] = __( 'Tema' );
	$columns[ 'situacao' ] = __( 'Situação' );
	$columns[ 'prazo' ] = __( 'Prazo' );
	return $columns;
}

add_filter('manage_edit-pauta_columns', 'delibera_edit_columns');

/**
 *
 *
 * @package Action
 * @subpackage Admin
 *
 */
function delibera_post_custom_column($column)
{
	global $post;

	switch ( $column )
	{
    case 'tema':
        echo the_terms($post->ID, "tema");
        break;
    case 'situacao':
        echo delibera_get_situacao($post->ID)->name;
        break;
    case 'prazo':
        $data = "";
        $prazo = delibera_get_prazo($post->ID, $data);
        if($prazo == -1)
        {
            echo __('Encerrado', 'delibera');
        }
        elseif($data != "")
        {
            echo $data." (".$prazo.($prazo == 1 ? __(" dia", 'delibera') : __(" dias", 'delibera')).")";
        }
        break;
	}

}

add_action('manage_posts_custom_column',  'delibera_post_custom_column');

/**
 *
 *
 * @package Action
 * @subpackage Admin
 *
 */
function delibera_admin_list_options($actions, $post)
{
	if(get_post_type($post) == 'pauta' && $post->post_status == 'publish' )
	{
		if(current_user_can('forcar_prazo'))
		{
			$url = 'admin.php?action=delibera_forca_fim_prazo_action&amp;post='.$post->ID;
			$url = wp_nonce_url($url, 'delibera_forca_fim_prazo_action'.$post->ID);
			$actions['forcar_prazo'] = '<a href="'.$url.'" title="'.__('Forçar fim de prazo','delibera').'" >'.__('Forçar fim de prazo','delibera').'</a>';

			$url = 'admin.php?action=delibera_nao_validado_action&amp;post='.$post->ID;
			$url = wp_nonce_url($url, 'delibera_nao_validado_action'.$post->ID);
			$actions['nao_validado'] = '<a href="'.$url.'" title="'.__('Invalidar','delibera').'" >'.__('Invalidar','delibera').'</a>';

		}
		if(delibera_get_situacao($post->ID)->slug == 'naovalidada' && current_user_can('delibera_reabrir_pauta'))
		{
			$url = 'admin.php?action=delibera_reabrir_pauta_action&amp;post='.$post->ID;
			$url = wp_nonce_url($url, 'delibera_reabrir_pauta_action'.$post->ID);
			$actions['reabrir'] = '<a href="'.$url.'" title="'.__('Reabrir','delibera').'" >'.__('Reabrir','delibera').'</a>';
		}

	}

	//print_r(_get_cron_array());
	return $actions;
}

add_filter('post_row_actions','delibera_admin_list_options', 10, 2);

/**
 *
 *
 * @package Action
 * @subpackage Admin
 *
 */
function delibera_restrict_listings()
{
	global $typenow;
	global $wp_query;
	if ($typenow=='pauta')
	{
		$taxonomy = 'situacao';
		$situacao_taxonomy = get_taxonomy($taxonomy);
		wp_dropdown_categories(array(
			'show_option_all' => sprintf(__('Mostrar todas as %s','delibera'),$situacao_taxonomy->label),
			'taxonomy' => $taxonomy,
			'name' => 'situacao',
			'orderby' => 'id',
			'selected' => isset($_REQUEST['situacao']) ? $_REQUEST['situacao'] : '',
			'hierarchical' => false,
			'depth' => 1,
			'show_count' => true, // This will give a view
			'hide_empty' => true, // This will give false positives, i.e. one's not empty related to the other terms.
		));
	}
}
add_action('restrict_manage_posts','delibera_restrict_listings');