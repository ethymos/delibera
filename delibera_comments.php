<?php
require_once('delibera_comments_query.php');
require_once('delibera_comments_template.php');
require_once('delibera_comments_edit.php');


function delibera_get_comment_type($comment)
{
	$comment_ID = $comment;
	if(is_object($comment_ID)) $comment_ID = $comment->comment_ID;
	return get_comment_meta($comment_ID, "delibera_comment_tipo", true);
}

/**
 * Retorna o nome "amigável" do tipo de um comentário.
 *
 * @param object $comment
 * @param string $tipo
 * @param bool $echo
 * @return string
 */
function delibera_get_comment_type_label($comment, $tipo = false, $echo = true)
{
	if($tipo === false) $tipo = get_comment_meta($comment->comment_ID, "delibera_comment_tipo", true);
	switch ($tipo)
	{
		case 'validacao':
			if($echo) _e('Validação', 'delibera');
			return __('Validação', 'delibera');
		break;
		case 'encaminhamento_selecionado':
		case 'encaminhamento':
			if($echo) _e('Proposta', 'delibera');
			return __('Proposta', 'delibera');
		break;
		case 'voto':
			if($echo) _e('Voto', 'delibera');
			return __('Voto', 'delibera');
		break;
		case 'resolucao':
			if($echo)  _e('Resolução', 'delibera');
			return __('Resolução', 'delibera');
		break;
		case 'discussao':
			if($echo) _e('Opinião', 'delibera');
			return __('Opinião', 'delibera');
		default:
		break;
	}
}

/**
 * Retorna uma string com a quantidade de comentários
 * associados a pauta do tipo correspondente a situação
 * atual.
 *
 * @param int $postId
 * @return string (exemplo: "5 votos")
 */
function delibera_get_comments_count_by_type($postId)
{
    $situacao = delibera_get_situacao($postId);

    switch ($situacao->slug) {
        case 'validacao':
            $count = count(delibera_get_comments_validacoes($postId));

            if ($count == 0) {
                $label = __('Nenhuma validação', 'delibera');
            } else if ($count == 1) {
                $label = __('1 validação', 'delibera');
            } else {
                $label = sprintf(__('%d validações', 'delibera'), $count);
            }

            return $label;
        case 'discussao':
            $count = count(delibera_get_comments_discussoes($postId));

            if ($count == 0) {
                $label = __('Nenhum comentário', 'delibera');
            } else if ($count == 1) {
                $label = __('1 comentário', 'delibera');
            } else {
                $label = sprintf(__('%d comentários', 'delibera'), $count);
            }

            return $label;
        case 'emvotacao':
            $count = count(delibera_get_comments_votacoes($postId));

            if ($count == 0) {
                $label = __('Nenhum voto', 'delibera');
            } else if ($count == 1) {
                $label = __('1 voto', 'delibera');
            } else {
                $label = sprintf(__('%d votos', 'delibera'), $count);
            }

            return $label;
    }
}

function delibera_get_comments_types()
{
	return array('validacao', 'discussao', 'encaminhamento', 'encaminhamento_selecionado', 'voto', 'resolucao');
}

function delibera_get_comments_link() {
	global $post;

	return get_permalink($post->ID) . '#delibera-comments';
}

function delibera_get_comment_link($comment_pass = false)
{
	global $comment;
	if(is_object($comment_pass))
	{
		$comment = $comment_pass;
	}

	if(!isset($comment))
	{
		return str_replace('#comment', '#delibera-comment', get_comments_link());
	}

	return str_replace('#comment', '#delibera-comment', get_comment_link($comment));
}

function delibera_comment_post_redirect( $location ) {
	global $post, $comment_id;

	return ( $post->post_type == 'pauta' ) ? preg_replace("/#comment-([\d]+)/", "#delibera-comment-" . $comment_id, $location) : $location;
}
add_filter( 'comment_post_redirect', 'delibera_comment_post_redirect' );

/**
 *
 * Comentário em listagem (Visualização)
 * @param string $commentText
 */
function delibera_comment_text($commentText)
{
	global $comment, $post, $delibera_comments_padrao;
	if(get_post_type($post) == "pauta" && $delibera_comments_padrao !== true)
	{
		$commentId = isset($comment) ? $comment->comment_ID : false;
		$commentText = delibera_comment_text_filtro($commentText, $commentId);
		$tipo = get_comment_meta($commentId, "delibera_comment_tipo", true);
		$total = 0;
		$nvotos = 0;
		switch ($tipo)
		{
			case 'validacao':
			{
				$validacao = get_comment_meta($comment->comment_ID, "delibera_validacao", true);
				$sim = ($validacao == "S" ? true : false);
				$commentText = '
					<div id="painel_validacao delibera-comment-text" >
						'.($sim ? '
						<label class="delibera-aceitou-view">'.__('Aceitou','delibera').'</label>
						' : '
						<label class="delibera-rejeitou-view">'.__('Rejeitou','delibera').'</label>
					</div>
				');
			}break;
			case 'discussao':
			case 'encaminhamento':
			case 'relatoria':
			{
				$situacao = delibera_get_situacao($comment->comment_post_ID);
				if($situacao->slug == 'discussao' || $situacao->slug == 'relatoria')
				{
					if ($tipo == "discussao")
					{
						$class_comment = "discussao delibera-comment-text";
					}
					else
					{
						$class_comment = "encaminhamento delibera-comment-text";
					}
					$commentText = "<div id=\"delibera-comment-text-".$comment->comment_ID."\" class='".$class_comment."'>".$commentText."</div>";
				}
				elseif($situacao->slug == 'comresolucao' && !defined('PRINT'))
				{
					$total = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_votos', true);
					$nvotos = get_comment_meta($comment->comment_ID, "delibera_comment_numero_votos", true);
					$commentText = '
						<div id="delibera-comment-text-'.$comment->comment_ID.'" class="comentario_coluna1 delibera-comment-text">
							'.$commentText.'
						</div>
						<div class="comentario_coluna2 delibera-comment-text">
							'.$nvotos.($nvotos == 1 ? " ".__('Voto','delibera') : " ".__('Votos','delibera') ).
						'('.( $nvotos > 0 && $total > 0 ? (($nvotos*100)/$total) : 0).'%)
						</div>
					';
				}
				if(has_filter('delibera_mostra_discussao'))
				{
					$commentText = apply_filters('delibera_mostra_discussao', $commentText, $total, $nvotos, $situacao->slug);
				}
			}break;
			case 'resolucao':
			{
				$total = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_votos', true);
				$nvotos = get_comment_meta($comment->comment_ID, "delibera_comment_numero_votos", true);
				$commentText = '
					<div class="comentario_coluna1 delibera-comment-text">
						'.$commentText.'
					</div>
					<div class="comentario_coluna2 delibera-comment-text">
						'.$nvotos.($nvotos == 1 ? " ".__('Voto','delibera') : " ".__('Votos','delibera') ).
						'('.( $nvotos > 0 && $total > 0 ? (($nvotos*100)/$total) : 0).'%)
					</div>
				';
			}break;
			case 'voto':
			{
				$commentText = '
				<div class="comentario_coluna1 delibera-comment-text">
					'.$commentText.'
				</div>
				';
			}break;
		}
		if(has_filter('delibera_mostra_discussao'))
		{
			$commentText = apply_filters('delibera_mostra_discussao', $commentText, $tipo, $total, $nvotos);
		}
		return $commentText;
	}
	else
	{
		return '<div class="delibera-comment-text">'.$commentText.'</div>';
	}
}

add_filter('comment_text', 'delibera_comment_text');

function delibera_comment_text_filtro($text, $comment_id = false, $show = true)
{
	$opt = delibera_get_config();
	$tamanho = $opt['numero_max_palavras_comentario'];
	if($opt['limitar_tamanho_comentario'] === 'S' && strlen($text) > $tamanho)
	{
		if($comment_id === false)
		{
			$comment_id = get_comment_ID();
		}
		$string_temp = wordwrap($text, $tamanho, '##!##');
		$cut = strpos($string_temp, '##!##');

		$text = delibera_show_hide_button($comment_id, $text, $cut, $show);
	}
	return $text;
}

function delibera_show_hide_button($comment_id, $text, $cut, $show)
{
	$comment_text = $text;
	$label = __('Continue lendo este comentário', 'delibera');
	if($show === true)
	{
		$showhide = '
			<div id="showhide_comment'.$comment_id.'" class="delibera-slide-text" style="display:none" >
		';
		$showhide_button = '
			<div id="showhide_button'.$comment_id.'" class="delibera-slide" onclick="delibera_showhide(\''.$comment_id.'\');" >'.$label.'</div>
		';
		$part = '<div id="showhide-comment-part-text-'.$comment_id.'" class="delibera-slide-part-text" >';
		$part .= truncate($text, $cut, '&hellip;');
		$part .= '</div>';

		$comment_text = $part.$showhide.$text."</div>".$showhide_button;
	}
	else
	{
		$link = '<a class="delibera_leia_mais_link" href="'.delibera_get_comment_link($comment_id).'">'.$label."</a>";
		$comment_text = truncate($text, $cut,'&hellip;').'<br/>
		'.$link;
	}

	return $comment_text;
}

function delibera_comments_open($open, $post_id)
{
	if ( 'pauta' == get_post_type($post_id) )
		return $open && delibera_can_comment($post_id);
	else
		return $open;
}
add_filter('comments_open', 'delibera_comments_open', 10, 2);

/**
 * Verifica se é possível fazer comentários, se o usuário tiver poder para tanto
 * @param unknown_type $postID
 */
function delibera_comments_is_open($postID = null)
{
	if(is_null($postID))
	{
		$post = get_post($postID);
		$postID = $post->ID;
	}

	$situacoes_validas = array('validacao' => true, 'discussao' => true, 'emvotacao' => true, 'elegerelator' => true,'relatoria'=>true);
	$situacao = delibera_get_situacao($postID);

	if(array_key_exists($situacao->slug, $situacoes_validas))
	{
		return $situacoes_validas[$situacao->slug];
	}

	return false;
}

function delibera_comment_form_action($postID)
{
	if(is_pauta())
	{
		global $comment_footer;
		echo $comment_footer;
		echo "</div>";
		if(function_exists('ecu_upload_form') && $situacao->slug != 'relatoria' && $situacao->slug != 'discussao')
		{
			echo '<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#ecu_uploadform").replaceWith("");
				});
				</script>';
		}
	}
}

add_action('comment_form', 'delibera_comment_form_action');

/**
 *
 * Salvar custom fields do comentário
 * @param int $comment_id
 */
function delibera_save_comment_metas($comment_id)
{
	$tipo = get_comment_meta($comment_id, "delibera_comment_tipo", true);

	if($tipo == false || $tipo == "")
	{
		if(array_key_exists("delibera_comment_tipo", $_POST))
		{
			$tipo = $_POST['delibera_comment_tipo'];
		}
	}

	delibera_curtir_comment_meta($comment_id);

	delibera_discordar_comment_meta($comment_id);

	$comment = get_comment($comment_id);

	switch($tipo)
	{
		case "validacao":
		{
			add_comment_meta($comment_id, 'delibera_validacao', $_POST['delibera_validacao'], true);
			add_comment_meta($comment_id, 'delibera_comment_tipo', 'validacao', true);

			if($_POST['delibera_validacao'] == "S")
			{
				$validacoes = get_post_meta($comment->comment_post_ID, 'numero_validacoes', true);
				$validacoes++;
				update_post_meta($comment->comment_post_ID, 'numero_validacoes', $validacoes); // Atualiza
				delibera_valida_validacoes($comment->comment_post_ID);
			}
			$nvalidacoes = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_validacoes', true);
			$nvalidacoes++;
			update_post_meta($comment->comment_post_ID, 'delibera_numero_comments_validacoes', $nvalidacoes);
		}break;

		case 'discussao':
		case 'encaminhamento':
		{
			$encaminhamento = $_POST['delibera_encaminha'];
			if($encaminhamento == "S")
			{
				add_comment_meta($comment_id, 'delibera_comment_tipo', 'encaminhamento', true);
				$nencaminhamentos = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_encaminhamentos', true);
				$nencaminhamentos++;
				update_post_meta($comment->comment_post_ID, 'delibera_numero_comments_encaminhamentos', $nencaminhamentos);
				if(array_key_exists('delibera-baseouseem', $_POST))
				{
					add_comment_meta($comment_id, 'delibera-baseouseem', $_POST['delibera-baseouseem'], true);
				}
			}
			else
			{
				add_comment_meta($comment_id, 'delibera_comment_tipo', 'discussao', true);
				$ndiscussoes = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_discussoes', true);
				$ndiscussoes++;
				update_post_meta($comment->comment_post_ID, 'delibera_numero_comments_discussoes', $ndiscussoes);
			}
			if(has_action('delibera_nova_discussao'))
			{
				do_action('delibera_nova_discussao', $comment_id, $comment, $encaminhamento);
			}
		}break;
		case 'voto':
		{

			add_comment_meta($comment_id, 'delibera_comment_tipo', 'voto', true);

			$votos = array();

			foreach ($_POST as $postkey => $postvar)
			{
				if( substr($postkey, 0, strlen('delibera_voto')) == 'delibera_voto' )
				{
					$votos[] = $postvar;
				}
			}

			add_comment_meta($comment_id, 'delibera_votos', $votos, true);

			$comment = get_comment($comment_id);
			delibera_valida_votos($comment->comment_post_ID);

			$nvotos = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_votos', true);
			$nvotos++;
			update_post_meta($comment->comment_post_ID, 'delibera_numero_comments_votos', $nvotos);

			if(has_action('delibera_novo_voto'))
			{
				do_action('delibera_novo_voto', $comment_id, $comment, $votos);
			}

		} break;

		default:
		{
			$npadroes = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_padroes', true);
			$npadroes++;
			update_post_meta($comment->comment_post_ID, 'delibera_numero_comments_padroes', $npadroes);
		}break;
	}
	if(array_search($tipo, delibera_get_comments_types()) !== false)
	{
		wp_set_comment_status($comment_id, 'approve');
		delibera_notificar_novo_comentario($comment);
		do_action('delibera_nova_interacao', $comment_id);
	}
}
add_action ('comment_post', 'delibera_save_comment_metas', 1);

function delibera_pre_edit_comment($dados)
{
	$comment_id = 0;
	if(array_key_exists('comment_ID', $_POST))
	{
		$comment_id = $_POST['comment_ID'];
	}
	else
	{
		global $comment;
		if(isset($comment->comment_ID))
		{
			$comment_id = $comment->comment_ID;
		}
		else
		{
			wp_die(__('Você não pode Editar esse tipo de comentário','delibera'));
		}
	}

	$tipo = get_comment_meta($comment_id, "delibera_comment_tipo", true);
	if(array_search($tipo, delibera_get_comments_types()) !== false)
	{
		wp_die(__('Você não pode Editar esse tipo de comentário','delibera'));
	}
}

//add_filter('comment_save_pre', 'delibera_pre_edit_comment'); //TODO Verificar edição

// require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_template.php';