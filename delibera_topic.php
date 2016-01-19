<?php
require_once(__DIR__.DIRECTORY_SEPARATOR.'delibera_topic_deadline.php');

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