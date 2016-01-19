<?php
/*
Plugin Name: Delibera
Plugin URI: http://www.ethymos.com.br
Description: O Plugin Delibera extende as funções padrão do WordPress e cria um ambiente de deliberação.
Version: 1.0.3
Author: Ethymos
Author URI: http://www.ethymos.com.br

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

// Defines

if(!defined('__DIR__')) {
    $iPos = strrpos(__FILE__, DIRECTORY_SEPARATOR);
    define("__DIR__", substr(__FILE__, 0, $iPos) . DIRECTORY_SEPARATOR);
}

define('DELIBERA_ABOUT_PAGE', __('sobre-a-plataforma', 'delibera'));

// End Defines

// Parse shorttag

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_shortcodes.php';

// End Parse shorttag

// Parse widgets

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_widgets.php';

// End Parse widgets

// Parse rewrite-rules

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_rewrite_rules.php';

// End Parse rewrite-rules

// pagina de configuracao do plugin
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_conf.php';

// Inicialização do plugin

require_once __DIR__.'/print/wp-print.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_admin_functions.php';

// setup plugin
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_setup.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_init.php';

/**
	 *
	 * Revemos acentos do texto
	 * @param string $texto
	 * @return string
	 */
function delibera_tiracento($texto)
{
	$trocarIsso = 	array('à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ü','ú','ÿ','À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','Ù','Ü','Ú','Ÿ',);
	$porIsso = 		array('a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','y','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','Y',);
	$titletext = str_replace($trocarIsso, $porIsso, $texto);
	return $titletext;
}

function delibera_slug_under($label)
{
	$slug = delibera_tiracento($label);
	$slug = str_replace(array("'",'"','.',',',';','!'), '', $slug);
	$slug = str_replace(array(' ', '-'), '_', $slug);
	return strtolower($slug);
}

function is_pauta($post = false)
{
	return get_post_type($post) == 'pauta' ? true : false;
}

/**
 *
 * Insere term no banco e atualizar línguas do qtranslate
 * @param string $label
 * @param string $tax Taxonomy
 * @param array $term EX: array('description'=> __('Español'),'slug' => 'espanol', 'slug' => 'espanol')
 * @param array $idiomas EX: array('qtrans_term_en' => 'United States of America', 'qtrans_term_pt' => 'Estados Unidos da América', 'qtrans_term_es' => 'Estados Unidos de América'
 */
function delibera_insert_term($label, $tax, $term, $idiomas = array())
{
	if(term_exists($term['slug'], $tax, null) == false)
	{
		wp_insert_term($label, $tax, $term);
		global $q_config;
		if(count($idiomas) > 0 && function_exists('qtrans_stripSlashesIfNecessary'))
		{
			if(isset($idiomas['qtrans_term_'.$q_config['default_language']]) && $idiomas['qtrans_term_'.$q_config['default_language']]!='')
			{
				$default = htmlspecialchars(qtrans_stripSlashesIfNecessary($idiomas['qtrans_term_'.$q_config['default_language']]), ENT_NOQUOTES);
				if(!isset($q_config['term_name'][$default]) || !is_array($q_config['term_name'][$default])) $q_config['term_name'][$default] = array();
				foreach($q_config['enabled_languages'] as $lang) {
					$idiomas['qtrans_term_'.$lang] = qtrans_stripSlashesIfNecessary($idiomas['qtrans_term_'.$lang]);
					if($idiomas['qtrans_term_'.$lang]!='') {
						$q_config['term_name'][$default][$lang] = htmlspecialchars($idiomas['qtrans_term_'.$lang], ENT_NOQUOTES);
					} else {
						$q_config['term_name'][$default][$lang] = $default;
					}
				}
				update_option('qtranslate_term_name',$q_config['term_name']);
			}
		}
	}
}

function delibera_pauta_redirect_filter($location, $post_id = null) {

	if (strpos($_SERVER['HTTP_REFERER'], "post_type=pauta"))
		return admin_url("edit.php")."?post_type=pauta&updated=1";
	else
		return $location;
}
add_filter('redirect_post_location', 'delibera_pauta_redirect_filter', '99');

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_conf_themes.php';

if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'delibera_filtros.php'))
{
	require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_filtros.php';
}

function delibera_pauta_custom_meta()
{
	add_meta_box("pauta_meta", "Detalhes da Pauta", 'delibera_pauta_meta', 'pauta', 'side', 'default');
}

function delibera_forca_fim_prazo($postID)
{
	$situacao = delibera_get_situacao($postID);

    switch($situacao->slug)
    {
    	case 'discussao':
    		delibera_tratar_prazo_discussao(array(
				'post_ID' => $postID,
				'prazo_discussao' => date('d/m/Y')
			));
    	break;
    	case 'relatoria':
    		delibera_tratar_prazo_relatoria(array(
				'post_ID' => $postID,
				'prazo_relatoria' => date('d/m/Y')
			));
    	break;
    	case 'emvotacao':
    		delibera_computa_votos($postID);
    	break;
    }
    //delibera_notificar_situacao($postID);
}

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

function delibera_forca_fim_prazo_action()
{
	if(current_user_can('forcar_prazo') && check_admin_referer('delibera_forca_fim_prazo_action'.$_REQUEST['post'], '_wpnonce'))
	{
		delibera_forca_fim_prazo($_REQUEST['post']);

		wp_redirect( admin_url( 'edit.php?post_type=pauta') );
	}
	else
	{
		wp_die(__('Você não tem permissão para forçar um prazo','delibera'), __('Sem permissão','delibera'));
	}
}
add_action('admin_action_delibera_forca_fim_prazo_action', 'delibera_forca_fim_prazo_action');

function delibera_nao_validado_action()
{
	if(current_user_can('forcar_prazo') && check_admin_referer('delibera_nao_validado_action'.$_REQUEST['post'], '_wpnonce'))
	{
		delibera_marcar_naovalidada($_REQUEST['post']);

		wp_redirect( admin_url( 'edit.php?post_type=pauta') );
	}
	else
	{
		wp_die(__('Você não tem permissão para invalidar uma pauta','delibera'), __('Sem permissão','delibera'));
	}
}
add_action('admin_action_delibera_nao_validado_action', 'delibera_nao_validado_action');

function delibera_reabrir_pauta_action()
{
	if(current_user_can('delibera_reabrir_pauta') && check_admin_referer('delibera_reabrir_pauta_action'.$_REQUEST['post'], '_wpnonce'))
	{
		delibera_reabrir_pauta($_REQUEST['post']);

		wp_redirect( admin_url( 'edit.php?post_type=pauta') );
	}
	else
	{
		wp_die(__('Você não tem permissão para re-abrir discussão sobre uma pauta','delibera'), __('Sem permissão','delibera'));
	}
}
add_action('admin_action_delibera_reabrir_pauta_action', 'delibera_reabrir_pauta_action');

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_cron.php';

/**
 *
 * Retorna a situação do post
 * @param int $postID
 * @return mixed validacao, discussao, elegerelator, relatoria, emvotacao, comresolucao, naovalidada ou false
 */
function delibera_get_situacao($postID)
{
	$situacao = get_the_terms($postID, 'situacao');
	$ret = false;
	if(is_array($situacao) && count($situacao)  > 0)
	{
		$ret = array_pop($situacao);
	}

	if(!is_object($ret)) // if term situacao does not exists
	{
		$ret = new stdClass();
		$ret->slug = '';
		$ret->name = '';
	}

	if(has_filter('delibera_get_situacao'))
	{
		return apply_filters('delibera_get_situacao', $ret);
	}

	return $ret;
}

/**
 * Retorna o label do botão com a situação da
 * pauta.
 *
 * @param int $postId
 * @return string
 */
function delibera_get_situation_button($postId)
{
    $situacao = get_the_terms($postId, 'situacao');

    if (is_array($situacao) && !empty($situacao)) {
        $situacao = array_pop($situacao);
    }

    switch($situacao->slug) {
        case 'emvotacao':
            return 'Votar';
        case 'discussao':
            return 'Discutir';
        case 'validacao':
            return 'Votar';
        default:
            return;
    }
}

function delibera_update_edit_form() {
    echo ' enctype="multipart/form-data"';
} // end update_edit_form
add_action('post_edit_form_tag', 'delibera_update_edit_form');

function delibera_pauta_meta()
{
	global $post;

	$custom = get_post_custom($post->ID);
	$options_plugin_delibera = delibera_get_config();

	if(!is_array($custom)) $custom = array();
	$validacoes = array_key_exists("numero_validacoes", $custom) ?  $custom["numero_validacoes"][0] : 0;

	$min_validacoes = array_key_exists("min_validacoes", $custom) ?  $custom["min_validacoes"][0] : htmlentities($options_plugin_delibera['minimo_validacao']);

	$situacao = delibera_get_situacao($post->ID);

	$dias_validacao = intval(htmlentities($options_plugin_delibera['dias_validacao']));
	$dias_discussao = intval(htmlentities($options_plugin_delibera['dias_discussao']));
	$dias_relatoria = intval(htmlentities($options_plugin_delibera['dias_relatoria']));
	$dias_votacao_relator = intval(htmlentities($options_plugin_delibera['dias_votacao_relator']));

    $pauta_pdf_file = get_post_meta($post->ID, 'pauta_pdf_contribution', true);

    // Recupera arquivo caso já tenha sido adicionados
    $pdf_html  = "<p><label>Pauta em PDF</label>";
    if( $pauta_pdf_file ) {
        $pdf_html .= "<a href='" . $pauta_pdf_file . "' target='_blank'>Arquivo Atual</a><br/>";
    }
    $pdf_html .= "<input type='file' name='pauta_pdf_contribution' id='pauta_pdf_contribution' value='' size='25'/></p>";
    echo $pdf_html;

	if($options_plugin_delibera['validacao'] == "S") // Adiciona prazo de validação se for necessário
	{
		$dias_discussao += $dias_validacao;
	}

	$dias_votacao = $dias_discussao + intval(htmlentities($options_plugin_delibera['dias_votacao']));

	if($options_plugin_delibera['relatoria'] == "S") // Adiciona prazo de relatoria se for necessário
	{
		$dias_votacao += $dias_relatoria;
		$dias_relatoria += $dias_discussao;
		if($options_plugin_delibera['eleicao_relator'] == "S") // Adiciona prazo de vatacao relator se for necessário
		{
			$dias_votacao += $dias_votacao_relator;
			$dias_relatoria += $dias_votacao_relator;
			$dias_votacao_relator += $dias_discussao;
		}
	}

	$now = strtotime(date('Y/m/d')." 11:59:59");

	$prazo_validacao_sugerido = strtotime("+$dias_validacao days", $now);
	$prazo_discussao_sugerido = strtotime("+$dias_discussao days", $now);
	$prazo_eleicao_relator_sugerido = strtotime("+$dias_votacao_relator days", $now);
	$prazo_relatoria_sugerido = strtotime("+$dias_relatoria days", $now);
	$prazo_votacao_sugerido = strtotime("+$dias_votacao days", $now);

	$prazo_validacao = date('d/m/Y', $prazo_validacao_sugerido);
	$prazo_discussao = date('d/m/Y', $prazo_discussao_sugerido);
	$prazo_eleicao_relator = date('d/m/Y', $prazo_eleicao_relator_sugerido);
	$prazo_relatoria = date('d/m/Y', $prazo_relatoria_sugerido);
	$prazo_votacao = date('d/m/Y', $prazo_votacao_sugerido);

	if (
		$options_plugin_delibera['representante_define_prazos'] == "N" &&
		!($post->post_status == 'draft' ||
		$post->post_status == 'auto-draft' ||
		$post->post_status == 'pending')
	)
	{
		$disable_edicao = 'readonly="readonly"';
	} else {
	    $disable_edicao = '';
	}

	if(!($post->post_status == 'draft' ||
		$post->post_status == 'auto-draft' ||
		$post->post_status == 'pending'))
	{
		$prazo_validacao = array_key_exists("prazo_validacao", $custom) ?  $custom["prazo_validacao"][0] : $prazo_validacao;
		$prazo_discussao = array_key_exists("prazo_discussao", $custom) ?  $custom["prazo_discussao"][0] : $prazo_discussao;
		$prazo_eleicao_relator = array_key_exists("prazo_eleicao_relator", $custom) ?  $custom["prazo_eleicao_relator"][0] : $prazo_eleicao_relator;
		$prazo_relatoria = array_key_exists("prazo_relatoria", $custom) ?  $custom["prazo_relatoria"][0] : $prazo_relatoria;
		$prazo_votacao = array_key_exists("prazo_votacao", $custom) ?  $custom["prazo_votacao"][0] : $prazo_votacao;
	}

	if($options_plugin_delibera['validacao'] == "S")
	{
	?>
		<p>
			<label for="min_validacoes" class="label_min_validacoes"><?php _e('Mínimo de Validações','delibera'); ?>:</label>
			<input <?php echo $disable_edicao ?> id="min_validacoes" name="min_validacoes" class="min_validacoes widefat" value="<?php echo $min_validacoes; ?>"/>
		</p>
		<p>
			<label for="prazo_validacao" class="label_prazo_validacao"><?php _e('Prazo para Validação','delibera') ?>:</label>
			<input <?php echo $disable_edicao ?> id="prazo_validacao" name="prazo_validacao" class="prazo_validacao widefat hasdatepicker" value="<?php echo $prazo_validacao; ?>"/>
		</p>
	<?php
	}
	?>
	<p>
		<label for="prazo_discussao" class="label_prazo_discussao"><?php _e('Prazo para Discussões','delibera') ?>:</label>
		<input <?php echo $disable_edicao ?> id="prazo_discussao" name="prazo_discussao" class="prazo_discussao widefat hasdatepicker" value="<?php echo $prazo_discussao; ?>"/>
	</p>
	<?php
	if($options_plugin_delibera['relatoria'] == "S")
	{
		if($options_plugin_delibera['eleicao_relator'] == "S")
		{
		?>
			<p>
				<label for="prazo_eleicao_relator" class="label_prazo_eleicao_relator"><?php _e('Prazo para Eleição de Relator','delibera') ?>:</label>
				<input <?php echo $disable_edicao ?> id="prazo_eleicao_relator" name="prazo_eleicao_relator" class="prazo_eleicao_relator widefat hasdatepicker" value="<?php echo $prazo_eleicao_relator; ?>"/>
			</p>
		<?php
		}
	?>
		<p>
			<label for="prazo_relatoria" class="label_prazo_relatoria"><?php _e('Prazo para Relatoria','delibera') ?>:</label>
			<input <?php echo $disable_edicao ?> id="prazo_relatoria" name="prazo_relatoria" class="prazo_relatoria widefat hasdatepicker" value="<?php echo $prazo_relatoria; ?>"/>
		</p>
	<?php
	}
	?>
	<p>
		<label for="prazo_votacao" class="label_prazo_votacao"><?php _e('Prazo para Votações','delibera') ?>:</label>
		<input <?php echo $disable_edicao ?> id="prazo_votacao" name="prazo_votacao" class="prazo_votacao widefat hasdatepicker" value="<?php echo $prazo_votacao; ?>"/>
	</p>
	<?php
}

function delibera_tratar_data($data, $int = true, $full = true)
{
	$data = trim($data);
	if(strlen($data) < 8) return false;
	$data = substr($data, 6, 4).substr($data, 2, 4).substr($data, 0, 2);
	$data .= $full === true ? ' 23:59:59' : '';
	return strtotime($data);
}

/**
 *
 * Faz agendamento das datas para seguir passos
 * 1) Excluir ao atingir data de validação se não foi validade
 * 2) Iniciar votação se tiver encaminhamento, ou novo prazo, caso contrário
 * 3) Fim da votação
 * @param $prazo_validacao
 * @param $prazo_discussao
 * @param $prazo_votacao
 */
function delibera_criar_agenda($postID, $prazo_validacao, $prazo_discussao, $prazo_votacao, $prazo_relatoria = false, $prazo_eleicao_relator = false)
{

	if($prazo_validacao !== false)
	{
		delibera_add_cron(
			delibera_tratar_data($prazo_validacao),
			'delibera_tratar_prazo_validacao',
			array(
				'post_ID' => $postID,
				'prazo_validacao' => $prazo_validacao
			)
		);
		delibera_add_cron(
			strtotime("-1 day", delibera_tratar_data($prazo_validacao)),
			'delibera_notificar_fim_prazo',
			array(
				'post_ID' => $postID,
				'prazo_validacao' => $prazo_validacao
			)
		);
	}

	if($prazo_discussao !== false)
	{
		delibera_add_cron(
			delibera_tratar_data($prazo_discussao),
			'delibera_tratar_prazo_discussao',
			array(
				'post_ID' => $postID,
				'prazo_discussao' => $prazo_discussao
			)
		);
		delibera_add_cron(
			strtotime("-1 day", delibera_tratar_data($prazo_discussao)),
			'delibera_notificar_fim_prazo',
			array(
				'post_ID' => $postID,
				'prazo_discussao' => $prazo_discussao
			)
		);
	}

	if($prazo_eleicao_relator != false)
	{
		delibera_add_cron(
			delibera_tratar_data($prazo_eleicao_relator),
			'delibera_tratar_prazo_eleicao_relator',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_eleicao_relator
			)
		);
		delibera_add_cron(
			strtotime("-1 day", delibera_tratar_data($prazo_eleicao_relator)),
			'delibera_notificar_fim_prazo',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_eleicao_relator
			)
		);
	}

	if($prazo_relatoria != false)
	{
		delibera_add_cron(
			delibera_tratar_data($prazo_relatoria),
			'delibera_tratar_prazo_relatoria',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_relatoria
			)
		);
		delibera_add_cron(
			strtotime("-1 day", delibera_tratar_data($prazo_relatoria)),
			'delibera_notificar_fim_prazo',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_relatoria
			)
		);
	}

	if($prazo_votacao != false)
	{
		delibera_add_cron(
			delibera_tratar_data($prazo_votacao),
			'delibera_tratar_prazo_votacao',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_votacao
			)
		);
		delibera_add_cron(
			strtotime("-1 day", delibera_tratar_data($prazo_votacao)),
			'delibera_notificar_fim_prazo',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_votacao
			)
		);
	}
}

function delibera_tratar_prazos($args)
{
	$situacao = delibera_get_situacao($args['post_ID']);
	switch ($situacao->slug)
	{
		case 'validacao':
			delibera_tratar_prazo_validacao($args);
		break;
		case 'discussao':
			delibera_tratar_prazo_discussao($args);
		break;
		case 'relatoria':
			delibera_tratar_prazo_relatoria($args);
		break;
		case 'emvotacao':
			delibera_tratar_prazo_votacao($args);
		break;
	}
}

add_action('delibera_tratar_prazos', 'delibera_tratar_prazos', 1, 1);

function delibera_tratar_prazo_validacao($args)
{
	$situacao = delibera_get_situacao($args['post_ID']);
	if($situacao->slug == 'validacao')
	{
		delibera_marcar_naovalidada($args['post_ID']);
	}
}

function delibera_tratar_prazo_discussao($args)
{
	$situacao = delibera_get_situacao($args['post_ID']);
	if($situacao->slug == 'discussao')
	{
		$post_id = $args['post_ID'];
		if(count(delibera_get_comments_encaminhamentos($post_id)) > 0)
		{
			$opts = delibera_get_config();
			if($opts['eleicao_relator'] == 'S')
			{
				wp_set_object_terms($post_id, 'eleicaoredator', 'situacao', false); //Mudar situação para Votação
			}
			elseif($opts['relatoria'] == 'S')
			{
				wp_set_object_terms($post_id, 'relatoria', 'situacao', false); //Mudar situação para Votação
			}
			else
			{
				wp_set_object_terms($post_id, 'emvotacao', 'situacao', false); //Mudar situação para Votação
			}
			if(has_action('delibera_discussao_concluida'))
			{
				do_action('delibera_discussao_concluida', $post_id);
			}
		}
		else
		{
			delibera_novo_prazo($post_id);
		}
	}
}

function delibera_tratar_prazo_relatoria($args)
{
	$situacao = delibera_get_situacao($args['post_ID']);
	if($situacao->slug == 'relatoria')
	{
		$post_id = $args['post_ID'];
		if(count(delibera_get_comments_encaminhamentos($post_id)) > 0)
		{
			wp_set_object_terms($post_id, 'emvotacao', 'situacao', false); //Mudar situação para Votação
			//delibera_notificar_situacao($post_id);
			if(has_action('delibera_relatoria_concluida'))
			{
				do_action('delibera_relatoria_concluida', $post_id);
			}
		}
		else
		{
			delibera_novo_prazo($post_id);
		}
	}
}

function delibera_tratar_prazo_votacao($args)
{
	$situacao = delibera_get_situacao($args['post_ID']);
	if($situacao->slug == 'emvotacao')
	{
		delibera_computa_votos($args['post_ID']);
	}
}

function delibera_marcar_naovalidada($postID)
{
	wp_set_object_terms($postID, 'naovalidada', 'situacao', false);
	if(has_action('delibera_pauta_recusada'))
	{
		do_action('delibera_pauta_recusada', $postID);
	}
}

function delibera_reabrir_pauta($postID)
{
	wp_set_object_terms($postID, 'validacao', 'situacao', false);
	//delibera_notificar_situacao($postID);

	delibera_novo_prazo($postID);
}

/**
 *
 * Save o post da pauta
 * @param $post_id int
 * @param $post
 */
function delibera_save_post($post_id, $post)
{
    if(get_post_type( $post_id ) != "pauta")
	{
		return $post_id;
	}
	$opt = delibera_get_config();
	$autosave = ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE );

	if(
		( // Se tem validação, tem que ter o prazo
			$opt['validacao'] == 'N' ||
			(array_key_exists('prazo_validacao', $_POST) && array_key_exists('min_validacoes', $_POST) )
		) &&
		( // Se tem relatoria, tem que ter o prazo
			$opt['relatoria'] == 'N' ||
			array_key_exists('prazo_relatoria', $_POST)
		) &&
		( // Se tem relatoria, e é preciso eleger o relator, tem que ter o prazo para eleição
			$opt['relatoria'] == 'N' ||
			(
				$opt['eleicao_relator'] == 'N' ||
				array_key_exists('prazo_eleicao_relator', $_POST)
			)
		) &&
		array_key_exists('prazo_discussao', $_POST) &&
		array_key_exists('prazo_votacao', $_POST)
	)
	{
        $events_meta = array();

		$validacoes = get_post_meta($post_id, 'numero_validacoes', true);
		if($validacoes == "" || $validacoes === false || is_null($validacoes))
		{
			$events_meta['numero_validacoes'] = 0;
			$events_meta['delibera_numero_comments_validacoes'] = 0;
			$events_meta['delibera_numero_comments_encaminhamentos'] = 0;
			$events_meta['delibera_numero_comments_discussoes'] = 0;
			$events_meta['delibera_numero_comments_votos'] = 0;
			$events_meta['delibera_numero_comments_padroes'] = 0;
			$events_meta['delibera_numero_curtir'] = 0;
			$events_meta['delibera_curtiram'] = array();
			$events_meta['delibera_numero_discordar'] = 0;
			$events_meta['delibera_discordaram'] = array();
			$events_meta['delibera_numero_seguir'] = 0;
			$events_meta['delibera_seguiram'] = array();
		}

		$events_meta['prazo_validacao'] = $opt['validacao'] == 'S' ? $_POST['prazo_validacao'] : date('d/m/Y');
		$events_meta['prazo_discussao'] = $_POST['prazo_discussao'];
		$events_meta['prazo_relatoria'] = $opt['relatoria'] == 'S' ? $_POST['prazo_relatoria'] : date('d/m/Y');
		$events_meta['prazo_eleicao_relator'] = $opt['relatoria'] == 'S' && $opt['eleicao_relator'] == 'S' ? $_POST['prazo_eleicao_relator'] : date('d/m/Y');
		$events_meta['prazo_votacao'] = $_POST['prazo_votacao'];
		$events_meta['min_validacoes'] = $opt['validacao'] == 'S' ? $_POST['min_validacoes'] : 10;

        /* ######### START ######### */
        /* ######### FOR PDF UPLOAD FILE ######### */
        // Setup the array of supported file types. In this case, it's just PDF.
        $supported_types = array('application/pdf');

        // Get the file type of the upload
        $arr_uploaded_file_type = wp_check_filetype(basename($_FILES['pauta_pdf_contribution']['name']));
        $uploaded_file_type = $arr_uploaded_file_type['type'];

        if (isset ($_FILES['pauta_pdf_contribution']['name']) && $_FILES['pauta_pdf_contribution']['name'] != '') {
            if (!in_array($uploaded_file_type, $supported_types)) {
                //TODO: Improve this message and avoid wp_die
                wp_die("O arquivo para web não é um PDF (formato permitido).");
            }


            // Use the WordPress API to upload the file
            $upload_pauta_pdf = wp_upload_bits($_FILES['pauta_pdf_contribution']['name'], null, file_get_contents($_FILES['pauta_pdf_contribution']['tmp_name']));

            if (isset($upload_pauta_pdf['error']) && $upload_pauta_pdf['error'] != 0) {
                $events_meta['pauta_pdf_contribution'] = none;
                wp_die('Erro ao salvar arquivo para Web. O erro foi: ' . $upload_pauta_pdf['error']);
            } else {
                $events_meta['pauta_pdf_contribution'] = $upload_pauta_pdf['url'];

                global $wpdb;

                $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "posts SET post_content=%s WHERE ID=%d", '<iframe id="pauta-pdf-content" src="https://docs.google.com/viewer?url=' . urlencode($upload_pauta_pdf['url']) . '&amp;embedded=true" style="width: 100%; min-height: 400px; max-height: 800px; ">' . $upload_pauta_pdf['url'] . '</iframe>', $post->ID));
            }
        }
        /* ######### FOR PDF UPLOAD FILE ######### */
        /* ######### END ######### */

		foreach ($events_meta as $key => $value) // Buscar dados
		{
	        if(get_post_meta($post->ID, $key, true)) // Se já existe
	        {
                update_post_meta($post->ID, $key, $value); // Atualiza
	        }
	        else
	        {
                add_post_meta($post->ID, $key, $value, true); // Senão, cria
	        }
	    }

	    if(
	    	array_key_exists('delibera_fim_prazo', $_POST) &&
	    	$_POST['delibera_fim_prazo'] == 'S' &&
	    	current_user_can('forcar_prazo')
	    )
	    {
	    	delibera_forca_fim_prazo($post->ID);
	    }

	    if($post->post_status == 'publish' && !$autosave)
	    {
	    	delibera_del_cron($post->ID);
            delibera_publish_pauta($post->ID, $post, true);
	    }

	}

}

add_action ('save_post', 'delibera_save_post', 1, 2);

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_curtir.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_discordar.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_seguir.php';

if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'mailer') && file_exists(__DIR__.DIRECTORY_SEPARATOR.'mailer'.DIRECTORY_SEPARATOR.'delibera_mailer.php'))
{
	//require_once __DIR__.DIRECTORY_SEPARATOR.'mailer'.DIRECTORY_SEPARATOR.'delibera_mailer.php';
}

function delibera_publish_pauta($postID, $post, $alterar = false)
{
	if(get_post_type( $postID ) != "pauta")
	{
		return $postID;
	}

	if (
			$alterar ||	(
				($post->post_status == 'publish' || $_POST['publish'] == 'Publicar') &&
					(
						(
							array_key_exists('prev_status', $_POST) &&
							(
								$_POST['prev_status'] == 'draft' ||
								$_POST['prev_status'] == 'pending'
							)
						) ||
						(
							array_key_exists('original_post_status', $_POST) && (
									$_POST['original_post_status'] == 'draft' ||
									$_POST['original_post_status'] == 'auto-draft' ||
									$_POST['original_post_status'] == 'pending')
						)
					)
			)
		)
	{
		$prazo_validacao = get_post_meta($postID, 'prazo_validacao', true);
		$prazo_discussao =  get_post_meta($postID, 'prazo_discussao', true);
		$prazo_relatoria =  get_post_meta($postID, 'prazo_relatoria', true);
		$prazo_eleicao_relator =  get_post_meta($postID, 'prazo_eleicao_relator', true);
		$prazo_votacao =  get_post_meta($postID, 'prazo_votacao', true);
		$opt = delibera_get_config();

		if(!array_key_exists('validacao', $opt) || $opt['validacao'] == 'S' )
		{
			if(!$alterar)
			{

				wp_set_object_terms($post->ID, 'validacao', 'situacao', false);
			}

	    	delibera_criar_agenda(
	    		$post->ID,
	    		$prazo_validacao,
	    		$prazo_discussao,
	    		$prazo_votacao,
	    		$opt['relatoria'] == 'S' ? $prazo_relatoria : false,
	    		$opt['relatoria'] == 'S' && $opt['eleicao_relator'] == 'S' ? $prazo_eleicao_relator : false
	    	);
		}
		else
		{
			if(!$alterar)
			{
				wp_set_object_terms($post->ID, 'discussao', 'situacao', false);
			}
	    	delibera_criar_agenda(
	    		$post->ID,
	    		false,
	    		$prazo_discussao,
	    		$prazo_votacao,
	    		$opt['relatoria'] == 'S' ? $prazo_relatoria : false,
	    		$opt['relatoria'] == 'S' && $opt['eleicao_relator'] == 'S' ? $prazo_eleicao_relator : false
	    	);
		}

		if($alterar)
		{
			//delibera_notificar_situacao($post);
		}
		else
		{
			delibera_notificar_nova_pauta($post);
		}
	}
}

add_action ('publish_pauta', 'delibera_publish_pauta', 1, 2);

function delibera_check_post_data($data, $postarr)
{
	$opt = delibera_get_config();
	$erros = array();
	$autosave = ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE );
	if(get_post_type() == 'pauta' && (!isset($_REQUEST['action']) || $_REQUEST['action'] != 'trash'))
	{
		if($opt['validacao'] == 'S')
		{
			$value = $_POST['prazo_validacao'];
			$valida = delibera_tratar_data($value);
			if(!$autosave && ($valida === false || $valida < 1))
			{
				$erros[] = __("É necessário definir corretamente o prazo de validação", "delibera");
			}
		}
		$value = $_POST['prazo_discussao'];
		$valida = delibera_tratar_data($value);
		if(!$autosave && ($valida === false || $valida < 1))
		{
			$erros[] = __("É necessário definir corretamente o prazo de discussão", "delibera");
		}

		if($opt['relatoria'] == 'S')
		{
			$value = $_POST['prazo_relatoria'];
			$valida = delibera_tratar_data($value);
            if(!$autosave && ($valida === false || $valida < 1))
            {
                $erros[] = __("É necessário definir corretamente o prazo para relatoria", "Delibera");
			}

			if($opt['eleicao_relator'] == 'S')
			{
				$value = $_POST['prazo__leicao_relator'];
				$valida = delibera_tratar_data($value);
				if(!$autosave && ($valida === false || $valida < 1))
				{
					$erros[] = __("É necessário definir corretamente o prazo para eleição de um relator", "delibera");
				}
			}

		}

		$value = $_POST['prazo_votacao'];
		$valida = delibera_tratar_data($value);
		if(!$autosave && ($valida === false || $valida < 1))
		{
			$erros[] = __("É necessário definir corretamente o prazo para votação", "delibera");
		}

		if($opt['validacao'] == 'S')
		{
			$value = (int)$_POST['min_validacoes'];
			$valida = is_int($value) && $value > 0;
			if(!$autosave && ($valida === false))
			{
				$erros[] = __("É necessário definir corretamente o número mínimo de validações", "delibera");
			}
		}

		if(
			count($erros) == 0
		)
		{
			return $data;
		}
		else
		{
			//wp_die(__('Erro ao salvar dados da pauta, faltando informações de prazos e validações mínimas!','delibera'));
			wp_die(implode("<BR/>", $erros));
		}
	}
	return $data;
}

add_filter('wp_insert_post_data', 'delibera_check_post_data', 10, 2);

/**
 *
 *
 * @param string $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param string $ending Ending to be appended to the trimmed string.
 * @param boolean $exact If false, $text will not be cut mid-word
 * @param boolean $considerHtml If true, HTML tags would be handled correctly
 * @return string Trimmed string.
 */

function truncate($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true) {
	if ($considerHtml) {
		// if the plain text is shorter than the maximum length, return the whole text
		if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		// splits all html-tags to scanable lines
		preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
		$total_length = strlen($ending);
		$open_tags = array();
		$truncate = '';
		foreach ($lines as $line_matchings) {
			// if there is any html-tag in this line, handle it and add it (uncounted) to the output
			if (!empty($line_matchings[1])) {
				// if it's an "empty element" with or without xhtml-conform closing slash
				if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
					// do nothing
				// if tag is a closing tag
				} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
					// delete tag from $open_tags list
					$pos = array_search($tag_matchings[1], $open_tags);
					if ($pos !== false) {
					unset($open_tags[$pos]);
					}
				// if tag is an opening tag
				} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
					// add tag to the beginning of $open_tags list
					array_unshift($open_tags, strtolower($tag_matchings[1]));
				}
				// add html-tag to $truncate'd text
				$truncate .= $line_matchings[1];
			}
			// calculate the length of the plain text part of the line; handle entities as one character
			$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
			if ($total_length+$content_length> $length) {
				// the number of characters which are left
				$left = $length - $total_length;
				$entities_length = 0;
				// search for html entities
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
					// calculate the real length of all entities in the legal range
					foreach ($entities[0] as $entity) {
						if ($entity[1]+1-$entities_length <= $left) {
							$left--;
							$entities_length += strlen($entity[0]);
						} else {
							// no more characters left
							break;
						}
					}
				}
				$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
				// maximum lenght is reached, so get off the loop
				break;
			} else {
				$truncate .= $line_matchings[2];
				$total_length += $content_length;
			}
			// if the maximum length is reached, get off the loop
			if($total_length>= $length) {
				break;
			}
		}
	} else {
		if (strlen($text) <= $length) {
			return $text;
		} else {
			$truncate = substr($text, 0, $length - strlen($ending));
		}
	}
	// if the words shouldn't be cut in the middle...
	if (!$exact) {
		// ...search the last occurance of a space...
		$spacepos = strrpos($truncate, ' ');
		if (isset($spacepos)) {
			// ...and cut the text in this position
			$truncate = substr($truncate, 0, $spacepos);
		}
	}
	// add the defined ending to the text
	$truncate .= $ending;
	if($considerHtml) {
		// close all unclosed html-tags
		foreach ($open_tags as $tag) {
			$truncate .= '</' . $tag . '>';
		}
	}
	return $truncate;
}

// Fim Inicialização do plugin

// Menu de configuração

function delibera_config_menu()
{
	/*if (function_exists('add_menu_page'))
		add_menu_page( __('Delibera','delibera'), __('Delibera plugin','delibera'), 'manage_options', 'delibera-config', 'delibera_conf_page');*/

	$base_page = 'delibera-config';

	if (function_exists('add_menu_page'))
	{
		add_object_page( __('Delibera','delibera'), __('Delibera','delibera'), 'manage_options', $base_page, array(), WP_PLUGIN_URL."/delibera/images/delibera_icon.png");
		//add_submenu_page($base_page, __('Pesquisar Contatos','delibera'), __('Pesquisar Contatos','delibera'), 'manage_options', 'delibera-gerenciar', 'delibera_GerenciarContato' );
		//add_submenu_page($base_page, __('Criar Contato','delibera'), __('Criar Contato','delibera'), 'manage_options', 'delibera-criar', 'delibera_CriarContato' );
		//add_submenu_page($base_page, __('Importar Contatos','delibera'), __('Importar Contatos','delibera'), 'manage_options', 'delibera-importar', 'delibera_ImportarContato' );
		add_submenu_page($base_page, __('Configurações do Plugin','delibera'),__('Configurações do Plugin','delibera'), 'manage_options', 'delibera-config', 'delibera_conf_page');
		do_action('delibera_menu_itens', $base_page);
	}
}

/**
 * Create a form table from an array of rows
 */
function delibera_form_table($rows) {
	$content = '<table class="form-table">';
	foreach ($rows as $row) {
		$content .= '<tr '.(array_key_exists('row-id', $row) ? 'id="'.$row['row-id'].'"' : '' ).' '.(array_key_exists('row-style', $row) ? 'style="'.$row['row-style'].'"' : '' ).' '.(array_key_exists('row-class', $row) ? 'class="'.$row['row-class'].'"' : '' ).' ><th valign="top" scrope="row">';

		if (isset($row['id']) && $row['id'] != '') {
			$content .= '<label for="'.$row['id'].'">'.$row['label'].'</label>';
		} else {
			$content .= $row['label'];
		}

		if (isset($row['desc']) && $row['desc'] != '') {
			$content .= '<br/><small>'.$row['desc'].'</small>';
		}

		$content .= '</th><td valign="top">';
		$content .= $row['content'];
		$content .= '</td></tr>';
	}
	$content .= '</table>';
	return $content;
}

// Funções de conteudo

/**
 *
 * Retorna post do tipo pauta em uma determinada situacao (validacao, discussao, emvotacao ou comresolucao), usando um filtro
 * @param array $filtro
 * @param string $situacao
 */
function delibera_get_pautas_em($filtro = array(), $situacao = false)
{
	$filtro['post_type'] = "pauta";
	$filtro['post_status'] = "publish";
	$tax_query = array();

	if(array_key_exists("tax_query", $filtro) && $situacao !== false)
	{
		$tax_query = $filtro['tax_query'];
		$tax_query['relation'] = 'AND';
	}
	if($situacao !== false)
	{
		$tax_query[] = array(
			'taxonomy' => 'situacao',
			'field' => 'slug',
			'terms' => $situacao
		);
		$filtro['tax_query'] = $tax_query;
	}
	return get_posts($filtro);
}

/**
 *
 * Retorna pautas em Validação
 * @param array $filtro
 */
function delibera_get_propostas($filtro = array())
{
	return delibera_get_pautas_em($filtro, 'validacao');
}

/**
 *
 * Retorna pautas em Discussão
 * @param array $filtro
 */
function delibera_get_pautas($filtro = array())
{
	return delibera_get_pautas_em($filtro, 'discussao');
}

function delibera_des_filtro_qtranslate($where)
{
	if(is_archive())
	{
		global $q_config, $wpdb;
		if($q_config['hide_untranslated'] && !is_singular()) {
			$where = str_replace(" AND $wpdb->posts.post_content LIKE '%<!--:".qtrans_getLanguage()."-->%'", '', $where);
		}
	}
	return $where;
}

add_filter('posts_where_request', 'delibera_des_filtro_qtranslate', 11);

/**
 *
 * Retorna pautas em Votação
 * @param array $filtro
 */
function delibera_get_emvotacao($filtro = array())
{
	return delibera_get_pautas_em($filtro, 'emvotacao');
}

/**
 *
 * Retorna pautas já resolvidas
 * @param array $filtro
 */
function delibera_get_resolucoes($filtro = array())
{
	return delibera_get_pautas_em($filtro, 'comresolucao');
}

require_once 'delibera_comments_template.php';

function delibera_get_comments_padrao($args = array(), $file = '/comments.php' )
{
	global $delibera_comments_padrao;
	$delibera_comments_padrao = true;
	comments_template($file);
	$delibera_comments_padrao = false;
}

/**
 * Retorna comentários do Delibera de acordo com o tipo.
 *
 * @param int $post_id
 * @param string|array $tipo um tipo ou um array de tipos
 * @return array
 */
function delibera_get_comments($post_id, $tipo, $args = array())
{
	if (is_string($tipo)) {
		$tipo = array($tipo);
	}

	$args = array_merge(array('post_id' => $post_id), $args);
	$comments = get_comments($args);
	$ret = array();
	foreach ($comments as $comment)
	{
		$comment_tipo = get_comment_meta($comment->comment_ID, 'delibera_comment_tipo', true);
		if (in_array($comment_tipo, $tipo)) {
			$ret[] = $comment;
		}
	}
	return $ret;
}

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_comments.php';


function delibera_wp_list_comments($args = array(), $comments = null)
{
	global $post;
	global $delibera_comments_padrao;

	if(get_post_type($post) == "pauta")
	{
		$situacao = delibera_get_situacao($post->ID);

		if($delibera_comments_padrao === true)
		{
			$args['post_id'] = $post->ID;
			$args['walker'] = new Delibera_Walker_Comment_padrao();
			$comments = get_comments($args);
			$ret = array();
			foreach ($comments as $comment)
			{
				$tipo_tmp = get_comment_meta($comment->comment_ID, 'delibera_comment_tipo', true);
				if(strlen($tipo_tmp) <= 0 || $tipo_tmp === false)
				{
					$ret[] = $comment;
				}
			}
			wp_list_comments($args, $ret);
		}
		elseif($situacao->slug == 'validacao')
		{
			//comment_form();
			$args['walker'] = new Delibera_Walker_Comment();
			//$args['callback'] = 'delibera_comments_list';
			?>
			<div class="delibera_lista_validacoes">
			<?php
			wp_list_comments($args, $comments);
			?>
			</div>
			<?php
		}
		elseif($situacao->slug == 'comresolucao')
		{
			$args['walker'] = new Delibera_Walker_Comment();
			wp_list_comments($args, $comments);

			$encaminhamentos = delibera_get_comments_encaminhamentos($post->ID);
			$discussoes = delibera_get_comments_discussoes($post->ID);
			?>
			<div class="delibera_encaminhamentos_inferior">
    			<?php wp_list_comments($args, $encaminhamentos); ?>
			</div>

			<div id="comments" class="delibera_opinioes_inferior">
			    <hr>
			    <h2 class="comments-title bottom"><?php _e('Histórico da pauta', 'delibera'); ?></h2>
			    <?php wp_list_comments($args, $discussoes); ?>
			</div>

			<?php
		}
		else
		{
			$args['walker'] = new Delibera_Walker_Comment();
			//$args['callback'] = 'delibera_comments_list';
			wp_list_comments($args, $comments);
		}
	}
	else
	{
		wp_list_comments($args, $comments);
	}
}


/**
 * Retrieve a list of comments.
 *
 * The comment list can be for the blog as a whole or for an individual post.
 *
 * The list of comment arguments are 'status', 'orderby', 'comment_date_gmt',
 * 'order', 'number', 'offset', and 'post_id'.
 *
 * @since 2.7.0
 * @uses $wpdb
 *
 * @param mixed $args Optional. Array or string of options to override defaults.
 * @return array List of comments.
 */
function delibera_wp_get_comments( $args = '' ) {
	$query = new delibera_WP_Comment_Query();
	return $query->query( $args );
}

function delibera_get_comments_validacoes($post_id)
{
	return delibera_get_comments($post_id, 'validacao');
}

function delibera_get_comments_discussoes($post_id)
{
	return delibera_get_comments($post_id, 'discussao');
}

function delibera_get_comments_encaminhamentos($post_id)
{
	return delibera_get_comments($post_id, 'encaminhamento');
}

/**
 * Retorna os encaminhamentos dos tipos 'encaminhamento' e
 * 'encaminhamento_selecionado' (aqueles que foram selecionados
 * pelo relator para ir para votação).
 *
 * @param int $post_id
 * @return array
 */
function delibera_get_comments_all_encaminhamentos($post_id)
{
    return delibera_get_comments($post_id, array('encaminhamento', 'encaminhamento_selecionado'));
}

/**
 * Retorna os encaminhamentos do tipo 'encaminhamento_selecionado'
 * (aqueles que foram selecionados pelo relator para ir para votação).
 *
 * @param int $post_id
 * @return array
 */
function delibera_get_comments_encaminhamentos_selecionados($post_id)
{
    return delibera_get_comments($post_id, 'encaminhamento_selecionado');
}


function delibera_get_comments_votacoes($post_id)
{
	return delibera_get_comments($post_id, 'voto');
}

function delibera_get_comments_resolucoes($post_id)
{
	if(has_filter('delibera_get_resolucoes'))
	{
		return apply_filters('delibera_get_resolucoes', delibera_get_comments($post_id, 'resolucao'));
	}
	return delibera_get_comments($post_id, 'resolucao');
}

/**
 *
 * Busca comentários com o tipo em tipos
 * @param array $comments lista de comentários a ser filtrada
 * @param array $tipos tipos aceitos
 */
function delibera_comments_filter_portipo($comments, $tipos)
{
	$ret = array();

	foreach ($comments as $comment)
	{
		$tipo = get_comment_meta($comment->comment_ID, 'delibera_comment_tipo', true);
		if(array_search($tipo, $tipos) !== false)
		{
			$ret[] = $comment;
		}
	}
	return $ret;
}

/**
 *
 * Filtro que retorna Comentário filtrados pela a situação da pauta
 * @param array $comments
 * @param int $postID
 * @return array Comentários filtrados
 */
function delibera_get_comments_filter($comments)
{
	global $delibera_comments_padrao;

	if($delibera_comments_padrao === true) return $comments;

	$ret = array();

	if(count($comments) > 0)
	{
		if(get_post_type($comments[0]->comment_post_ID) == "pauta")
		{
			$situacao = delibera_get_situacao($comments[0]->comment_post_ID);
			switch ($situacao->slug)
			{
				case 'validacao':
				{
					$ret = delibera_comments_filter_portipo($comments, array('validacao'));
				}break;
				case 'discussao':
				{
					$ret = delibera_comments_filter_portipo($comments, array('discussao', 'encaminhamento'));
				}break;
				case 'relatoria':
				{
					$ret = delibera_comments_filter_portipo($comments, array('discussao', 'encaminhamento'));
				}break;
				case 'emvotacao':
				{
					$ret = delibera_comments_filter_portipo($comments, array('voto'));
				}break;
				case 'comresolucao':
				{
					$ret = delibera_comments_filter_portipo($comments, array('resolucao'));
				}break;
			}
			return $ret;
		}
	}
	return $comments;
}

add_filter('comments_array', 'delibera_get_comments_filter');

/**
 * Retorna um inteiro indicando quantos dias faltam para o fim do prazo
 * de uma pauta.
 *
 * Se o parâmetro $data for passado por referência o prazo para o fim da
 * pauta é associado a ele.
 *
 * @param int $postID
 * @param string $data
 * @return int
 */
function delibera_get_prazo($postID, &$data = null)
{
	$situacao = delibera_get_situacao($postID);
	$prazo = "";
	$idata = strtotime(date('Y/m/d').' 23:59:59');
	$diff = -1;

	if(is_object($situacao))
	{
		switch ($situacao->slug)
		{
			case 'validacao':
			{
				$prazo = get_post_meta($postID, 'prazo_validacao', true);
			} break;
			case 'discussao':
			{
				$prazo = get_post_meta($postID, 'prazo_discussao', true);
			}break;
			case 'elegerelator':
			{
				$prazo = get_post_meta($postID, 'prazo_eleicao_relator', true);
			}break;
			case 'relatoria':
			{
				$prazo = get_post_meta($postID, 'prazo_relatoria', true);
			}break;
			case 'emvotacao':
			{
				$prazo = get_post_meta($postID, 'prazo_votacao', true);
			} break;
		}

		$iprazo = strtotime(substr($prazo, 6).substr($prazo, 2, 4).substr($prazo, 0, 2).' 23:59:59');

		$diff = $iprazo - $idata;
	}
	$dias = -1;

	if($diff >= 0) $dias = ceil($diff/(60*60*24));

	if(!is_null($data)) $data = $prazo;

	return $dias;
}

function delibera_edit_columns($columns)
{
	$columns[ 'tema' ] = __( 'Tema' );
	$columns[ 'situacao' ] = __( 'Situação' );
	$columns[ 'prazo' ] = __( 'Prazo' );
	return $columns;
}

add_filter('manage_edit-pauta_columns', 'delibera_edit_columns');

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

function delibera_comment_number($postID, $tipo)
{
	switch($tipo)
	{
		case 'validacao':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_validacoes', true));
		break;
		case 'discussao':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_discussoes', true));
		break;
		case 'encaminhamento':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_encaminhamentos', true));
		break;
		case 'voto':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_votos', true));
		break;
		/*case 'resolucao':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_resolucoes', true)); TODO Número de resoluções, baseado no mínimo de votos, ou marcação especial
		break;*/
		case 'todos':
			return get_post($postID)->comment_count;
		break;
		default:
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_padroes', true));
		break;
	}
}

function delibera_comment_number_filtro($count, $postID)
{
	if (!is_pauta()) {
		return $count;
	}
	$situacao = delibera_get_situacao($postID);

	if (!$situacao) {
		return;
	}

	switch($situacao->slug)
	{
		case 'validacao':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_validacoes', true));
		break;
		case 'discussao':
		case 'comresolucao':
			return doubleval(
				get_post_meta($postID, 'delibera_numero_comments_encaminhamentos', true) +
				get_post_meta($postID, 'delibera_numero_comments_discussoes', true)
			);
		break;
		case 'relatoria':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_encaminhamentos', true));
		break;
		case 'emvotacao':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_votos', true));
		break;
		default:
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_padroes', true));
		break;
	}
}

add_filter('get_comments_number', 'delibera_comment_number_filtro', 10, 2);

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

function delibera_convert_situacao_id_to_taxonomy_term_in_query(&$query)
{
	global $pagenow;
	$qv = &$query->query_vars;
	if (isset($qv['post_type']) &&
		$qv['post_type'] == 'pauta' &&
		$pagenow=='edit.php' &&
		isset($qv['situacao'])
	)
	{
		$situacao = get_term_by('id', $_REQUEST['situacao'], 'situacao');
		$qv['situacao'] = $situacao->slug;
	}
}
add_filter('parse_query','delibera_convert_situacao_id_to_taxonomy_term_in_query');

/**
 * Notificações do sistema.
 */
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_notificar.php';

/**
 * Perfil do usuário
 */
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_user_painel.php';

// gerar relatório em um arquivo xls
require_once __DIR__ . DIRECTORY_SEPARATOR . 'delibera_relatorio.php';

/**
 *
 * Pega os ultimos conteúdos
 * @param string $tipo (option) 'pauta' ou 'comments', padrão 'pauta'
 * @param array $args (option) query padrão do post ou do comments
 * @param int $count (option) padrão 5
 */
function delibera_ultimas($tipo = 'pauta', $args = array(), $count = 5)
{
	switch($tipo)
	{
		case 'pauta':
			$filtro = array('orderby' => 'modified', 'order' => 'DESC', 'posts_per_page' => $count);
			$filtro = array_merge($filtro, $args);
			return delibera_get_pautas_em($filtro, false);
		break;
		case 'comments':
			$filtro = array('orderby' => 'comment_date_gmt', 'order' => 'DESC', 'number' => $count, 'post_type' => 'pauta');
			$filtro = array_merge($filtro, $args);
			return delibera_wp_get_comments($filtro);
		break;
	}
}

function delibera_timeline($post_id = false, $tipo_data = false)
{
	require_once __DIR__.DIRECTORY_SEPARATOR.'timeline/delibera_timeline.php';
	$timeline = new delibera_timeline();
	$timeline->generate($post_id, $tipo_data);
}

function delibera_the_posts($posts)
{
	if (empty($posts)) return $posts;

	$timeline_found = false; // use this flag to see if styles and scripts need to be enqueued
	$relatoria = false;
	foreach ($posts as $post)
	{
		if (stripos($post->post_content, '[delibera_timeline') !== false)
		{
			$timeline_found = true; // bingo!
		}
		if(get_post_type($post) == 'pauta')
		{
			$situacao = delibera_get_situacao($post->ID);
			if($situacao->slug == 'relatoria')
			{
				$relatoria = true;
			}
		}
	}

	if ($timeline_found)
	{
		// enqueue here
		wp_enqueue_style('delibera_timeline_css',  WP_CONTENT_URL.'/plugins/delibera/timeline/delibera_timeline.css');
		wp_enqueue_script( 'delibera_timeline_js', WP_CONTENT_URL.'/plugins/delibera/timeline/js/delibera_timeline.js', array( 'jquery' ));
		wp_enqueue_script( 'jquery-ui-draggable');
	}

	return $posts;
}

add_filter('the_posts', 'delibera_the_posts'); // the_posts gets triggered before wp_head

// FIM Funções de conteudo

// Validadores

/**
 * Sempre que um usuário valida uma pauta
 * verifica se o número mínimo de validações foi
 * atingido e se sim muda a situação da pauta de
 * "emvotacao" para "discussao".
 *
 * @param unknown $post
 * @return null
 */
function delibera_valida_validacoes($post)
{
	$validacoes = get_post_meta($post, 'numero_validacoes', true);
	$min_validacoes = get_post_meta($post, 'min_validacoes', true);

	if($validacoes >= $min_validacoes)
	{
		wp_set_object_terms($post, 'discussao', 'situacao', false); //Mudar situação para Discussão
		if(has_action('delibera_validacao_concluida'))
		{
			do_action('delibera_validacao_concluida', $post);
		}
	}
	else
	{
		if(has_action('delibera_validacao'))
		{
			do_action('delibera_validacao', $post);
		}
	}
}

/* Faz os testes de permissões para garantir que nenhum engraçadinho
 * está injetando variáveis maliciosas.
 * TODO: Incluir todas as variaveis a serem verificadas aqui
 */
function delibera_valida_permissoes($comment_ID)
{
	if (get_post_type() == 'pauta' && !delibera_current_user_can_participate())
	{
		if (array_key_exists('delibera_validacao', $_REQUEST) || array_key_exists('delibera_encaminha', $_REQUEST) )
			wp_die("Nananina não! Você não tem que ter permissão pra votar.","Tocooo!!");
	}
}
add_action( 'wp_blacklist_check', 'delibera_valida_permissoes' );

/**
 *
 * Verifica se o número de votos é igual ao número de representantes para deflagar fim da votação
 * @param integer $postID
 */
function delibera_valida_votos($postID)
{
	global $wp_roles,$wpdb;
	$users_count = 0;
    foreach ($wp_roles->roles as $nome => $role)
    {
    	if(is_array($role['capabilities']) && array_key_exists('votar', $role['capabilities']) && $role['capabilities']['votar'] == 1 ? "SSSSSim" : "NNNnnnnnnnao")
    	{
    		$result = $wpdb->get_results("SELECT count(*) as n FROM $wpdb->usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%$nome%' ");
    		$users_count += $result[0]->n;
    	}
    }

	$votos = delibera_get_comments_votacoes($postID);

	$votos_count = count($votos);

	if($votos_count >= $users_count)
	{
		delibera_computa_votos($postID, $votos);
	}
}

/**
 *
 * Faz a apuração dos votos e toma as devidas ações:
 *    Empate: Mais prazo;
 *    Vencedor: Marco com resolucao e marca o encaminhamento.
 * @param interger $postID
 * @param array $votos
 */
function delibera_computa_votos($postID, $votos = null)
{
	if(is_null($votos)) // Ocorre no fim do prazo de votação
	{
		$votos = delibera_get_comments_votacoes($postID);
	}
	$encaminhamentos = delibera_get_comments_encaminhamentos($postID);
	$encaminhamentos_votos = array();
	foreach ($encaminhamentos as $encaminhamento)
	{
		$encaminhamentos_votos[$encaminhamento->comment_ID] = 0;
	}

	foreach ($votos as $voto_comment)
	{
		$voto = get_comment_meta($voto_comment->comment_ID, 'delibera_votos', true);
		foreach ($voto as $voto_para)
		{
			$encaminhamentos_votos[$voto_para]++;
		}
	}
	$maisvotado = array(-1, -1);
	$iguais = array();

	foreach ($encaminhamentos_votos as $encaminhamentos_voto_key => $encaminhamentos_voto_valor)
	{
		if($encaminhamentos_voto_valor > $maisvotado[1])
		{
			$maisvotado[0] = $encaminhamentos_voto_key;
			$maisvotado[1] = $encaminhamentos_voto_valor;
			$iguais = array();
		}
		elseif($encaminhamentos_voto_valor == $maisvotado[1])
		{
			$iguais[] = $encaminhamentos_voto_key;
		}
		delete_comment_meta($encaminhamentos_voto_key, 'delibera_comment_numero_votos');
		add_comment_meta($encaminhamentos_voto_key, 'delibera_comment_numero_votos', $encaminhamentos_voto_valor, true);
	}

	// nao finaliza a votacao caso haja um empate, exceto quando o administrador clicar no botão "Forçar fim do prazo"
	if(count($iguais) > 0 && !(isset($_REQUEST['action']) && $_REQUEST['action'] == 'delibera_forca_fim_prazo_action')) // Empato
	{
		delibera_novo_prazo($postID);
	}
	else
	{
		wp_set_object_terms($postID, 'comresolucao', 'situacao', false);
		update_comment_meta($maisvotado[0], 'delibera_comment_tipo', 'resolucao');
		add_post_meta($postID, 'data_resolucao', date('d/m/Y H:i:s'), true);
		////delibera_notificar_situacao($postID);
		if(has_action('votacao_concluida'))
		{
			do_action('votacao_concluida', $post);
		}
	}
}

function delibera_emvotacao($post)
{
	$opt = delibera_get_config();
	if($opt['relatoria'] == 'S')
	{
		if($opt['eleicao_relator'] == 'S')
		{

		}
	}
}

function delibera_novo_prazo($postID)
{
	$situacao = delibera_get_situacao($postID);
	$opts = delibera_get_config();
	switch ($situacao->slug)
	{
		case 'validacao':
			$inova_data = strtotime("+{$opts['dias_novo_prazo']} days");
			$nova_data = date("d/m/Y", $inova_data);
			$inova_datad = strtotime("+{$opts['dias_discussao']} days",$inova_data);
			$nova_datad = date("d/m/Y", $inova_datad);
			$inova_datavt = strtotime("+{$opts['dias_votacao']} days",$inova_datad);
			$nova_datavt = date("d/m/Y", $inova_datavt);
			$inova_datarel = strtotime("+{$opts['dias_votacao_relator']} days",$inova_datavt);
			$nova_datarel = date("d/m/Y", $inova_datarel);
			$inova_datar = strtotime("+{$opts['dias_relatoria']} days",$inova_datarel);
			$nova_datar = date("d/m/Y", $inova_datar);

			$events_meta['prazo_validacao'] = $opts['validacao'] == 'S' ? $nova_data : date('d/m/Y');
			$events_meta['prazo_discussao'] = $nova_datad;
			$events_meta['prazo_relatoria'] = $opts['relatoria'] == 'S' ? $nova_datar : date('d/m/Y');
			$events_meta['prazo_eleicao_relator'] = $opts['relatoria'] == 'S' && $opts['eleicao_relator'] == 'S' ? $nova_datarel : date('d/m/Y');
			$events_meta['prazo_votacao'] = $nova_datavt;

			foreach ($events_meta as $key => $value) // Buscar dados
			{
				if(get_post_meta($postID, $key, true)) // Se já existe
				{
					update_post_meta($postID, $key, $value); // Atualiza
				}
				else
				{
					add_post_meta($postID, $key, $value, true); // Se não cria
				}
			}
			delibera_del_cron($postID);
			delibera_criar_agenda($postID, $nova_data, $nova_datad, $nova_datavt, $nova_datar, $nova_datarel);
		break;
		case 'discussao':
		case 'relatoria':
			$inova_data = strtotime("+{$opts['dias_novo_prazo']} days");
			delibera_set_novo_prazo_discussao_relatoria($postID, $inova_data, $opts);
		break;
		case 'emvotacao':
			$inova_data = strtotime("+{$opts['dias_novo_prazo']} days");
			$nova_data = date("d/m/Y", $inova_data);
			update_post_meta($postID, 'prazo_votacao', $nova_data);
			delibera_del_cron($postID);
			delibera_criar_agenda($postID, false, false, $nova_data);
		break;
	}
	//delibera_notificar_situacao($postID);
}

/**
 * @param $postID
 * @param $opts
 */
function delibera_set_novo_prazo_discussao_relatoria($postID, $inova_data, $opts)
{
	$nova_data = date("d/m/Y", $inova_data);
	update_post_meta($postID, 'prazo_discussao', $nova_data);
	$nova_eleicao_rel = false;
	$nova_relatoria = false;
	if ($opts['relatoria'] == "S") // Adiciona prazo de relatoria se for necessário
	{
		$opts['dias_votacao'] += $opts['dias_relatoria'];
		if ($opts['eleicao_relator'] == "S") // Adiciona prazo de vatacao relator se for necessário
		{
			$opts['dias_votacao'] += $opts['dias_votacao_relator'];
			$opts['dias_relatoria'] += $opts['dias_votacao_relator'];
			$nova_eleicao_rel = date("d/m/Y", strtotime("+{$opts['dias_votacao_relator']} days", $inova_data));
		}
		$nova_relatoria = date("d/m/Y", strtotime("+{$opts['dias_relatoria']} days", $inova_data));
	}
	$inova_data_votacao = strtotime("+{$opts['dias_votacao']} days", $inova_data);
	$nova_data_votacao = date("d/m/Y", $inova_data_votacao);
	update_post_meta($postID, 'prazo_votacao', $nova_data_votacao);
	delibera_del_cron($postID);
	delibera_criar_agenda($postID, false, $nova_data, $nova_data_votacao, $nova_relatoria, $nova_eleicao_rel);
}


function delibera_get_plan_config()
{
	$plan = 'N';

	if( is_multisite() && get_current_blog_id() != 1 )
	{
		switch_to_blog(1);
		$opt = delibera_get_config();
		$plan = $opt['plan_restriction'];
		restore_current_blog();
	}
	else
	{
		$opt = delibera_get_config();
		$plan = $opt['plan_restriction'];
	}

	return $plan;
}


add_action('init', 'delibera_nova_pauta_create_action');
function delibera_nova_pauta_create_action(){
    $opt = delibera_get_config();
    if ($opt['criar_pauta_pelo_front_end'] == 'S' && is_user_logged_in() && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'delibera_nova_pauta')) {
        $title = $_POST['nova-pauta-titulo'];
        $content = $_POST['nova-pauta-conteudo'];
        $excerpt = $_POST['nova-pauta-resumo'];

        $pauta = array();
        $pauta['post_title'] = $title;
        $pauta['post_excerpt'] = $excerpt;
        $pauta['post_type'] = 'pauta';

        //Check if there is any file uploaded
        // If there is any, then ignore 'content' and use File.
        // else do add 'pauta' with the text content
        if(!empty($_FILES['post_pdf_contribution']['name'])) {
            // Setup the array of supported file types. In this case, it's just PDF.
            $supported_types = array('application/pdf');
            // Get the file type of the upload
            $pdf_contribution = wp_check_filetype(basename($_FILES['post_pdf_contribution']['name']));
            $sent_file_type = $pdf_contribution['type'];
            // Check if the type is supported. If not, throw an error.
            if (!in_array($sent_file_type, $supported_types)) {
                //TODO: Improve this message and avoid wp_die
                wp_die("O arquivo para web não é um PDF (formato permitido).");
            }
            $uploaded_file = wp_upload_bits($_FILES['pauta_pdf_contribution']['name'], null, file_get_contents($_FILES['pauta_pdf_contribution']['tmp_name']));
            if(isset($uploaded_file['error']) && $uploaded_file['error'] != 0) {
                wp_die('Erro ao salvar arquivo para Web. O erro foi: ' . $upload['error']);
            } else {
                $pauta['pauta_pdf_contribution'] = $uploaded_file['url'];
            }
        } else {
            $pauta['post_content'] = $content;
        }

        // para que a situação da pauta seja criada corretamente,
        // é necessário criar a pauta como rascunho para depois publicar no final desta função
        $pauta['post_status'] = 'draft';

        $pauta_id = wp_insert_post($pauta);

        if(is_int($pauta_id) && $pauta_id > 0){

            /* Os valores adicionados ao array $_POST são baseados no if da função delibera_save_post(),
             * comentado abaixo
            if(
                ( // Se tem validação, tem que ter o prazo
                    $opt['validacao'] == 'N' ||
                    (array_key_exists('prazo_validacao', $_POST) && array_key_exists('min_validacoes', $_POST) )
                ) &&
                ( // Se tem relatoria, tem que ter o prazo
                    $opt['relatoria'] == 'N' ||
                    array_key_exists('prazo_relatoria', $_POST)
                ) &&
                ( // Se tem relatoria, e é preciso eleger o relator, tem que ter o prazo para eleição
                    $opt['relatoria'] == 'N' ||
                    (
                        $opt['eleicao_relator'] == 'N' ||
                        array_key_exists('prazo_eleicao_relator', $_POST)
                    )
                ) &&
                array_key_exists('prazo_discussao', $_POST) &&
                array_key_exists('prazo_votacao', $_POST)
             )
            */

            if($opt['validacao'] == 'S'){
                $_POST['prazo_validacao'] = date('d/m/Y', strtotime ('+'.$opt['dias_validacao'].' DAYS'));
                $_POST['min_validacoes'] = $opt['minimo_validacao'];
            }

            if($opt['relatoria'] == 'S'){
                $_POST['prazo_relatoria'] = date('d/m/Y', strtotime ('+'.$opt['dias_relatoria'].' DAYS'));
                if($opt['eleicao_relator'] == 'S'){
                    $_POST['prazo_eleicao_relator'] = date('d/m/Y', strtotime ('+'.$opt['dias_votacao_relator'].' DAYS'));
                }
            }

			if (trim($opt['data_fixa_nova_pauta_externa']) != '') {
				$prazo_discussao = DateTime::createFromFormat('d/m/Y', $opt['data_fixa_nova_pauta_externa']);
				$_POST['prazo_discussao'] = $prazo_discussao->format('d/m/Y');
				$_POST['prazo_votacao'] = date('d/m/Y', strtotime ('+'.$opt['dias_votacao'].' DAYS', $prazo_discussao->getTimestamp()));
			} else {
				$_POST['prazo_discussao'] = date('d/m/Y', strtotime ('+'.$opt['dias_discussao'].' DAYS'));
				$_POST['prazo_votacao'] = date('d/m/Y', strtotime ('+'.$opt['dias_votacao'].' DAYS'));
			}

            // isto é necessário por causa do if da função delibera_publish_pauta()
            $_POST['publish'] = 'Publicar';
            $_POST['prev_status'] = 'draft';

            // verifica se todos os temas enviados por post são válidos
            $temas = get_terms('tema', array('hide_empty'    => true));
            $temas_ids = array();

            if(isset($_POST['tema']) && is_array($_POST['tema']))
                foreach($temas as $tema)
                    if(in_array ($tema->term_id, $_POST['tema']))
                        $temas_ids[] = $tema->term_id;

            // coloca os termos de temas no post
            wp_set_post_terms($pauta_id, $temas_ids, 'tema');

            // publica o post
            wp_publish_post($pauta_id);

            // isto serve para criar o slug corretamente,
            // já que no wp_insert_post não cria o slug quando o status é draft e o wp_publish_post tb não cria o slug
            unset($pauta['post_status']);
            $pauta['ID'] = $pauta_id;
            $pauta['post_name'] = sanitize_post_field('post_name', $title, $pauta_id, 'save');
            wp_update_post($pauta);

            // redireciona para a pauta criada
            $permalink = get_post_permalink($pauta_id);
            wp_safe_redirect($permalink);
            die;
        }
    }
}

// END - Interface pública para a criação de novas pautas

/**
 * Redireciona usuários que não são membros do site
 * onde o Delibera foi instalado para a página de pautas após o
 * login se a opção "Todos os usuários logados na rede podem participar?"
 * estiver habilitada.
 *
 * Se não fizermos esse redicionamento estes usuários serão redirecionados
 * para suas páginas de perfil fora do site onde o Delibera está instalado.
 *
add_filter('login_redirect', function($redirect_to, $request, $user) {
    $options = delibera_get_config();

    if ($options['todos_usuarios_logados_podem_participar'] == 'S' && !is_user_member_of_blog()) {
        return site_url('pauta');
    } else {
        return $redirect_to;
    }
}, 10, 3);
TODO mundo redirecionado para a lista de pauta, talvez uma nova opções */

/***
 * Verifica se as pautas devem suportar sugestão de encaminhamento ou se
 * as propostas entram apenas como opinião. Muito útil para consultas públicas.
 *
 * @return bool
 */
function delibera_pautas_suportam_encaminhamento()
{
    $options = delibera_get_config();

    if ( $options['pauta_suporta_encaminhamento'] == 'S' ) {
        return true;
    } else {
        return false;
    }
}

?>
