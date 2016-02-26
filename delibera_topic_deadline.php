<?php

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
        //delibera_computa_votos($postID); TODO use module
    	break;
    }
    //delibera_notificar_situacao($postID);
}


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