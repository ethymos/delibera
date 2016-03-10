<?php

function delibera_forca_fim_prazo_action()
{
	if(current_user_can('forcar_prazo') && check_admin_referer('delibera_forca_fim_prazo_action'.$_REQUEST['post'], '_wpnonce'))
	{
		\Delibera\Flow::forcarFimPrazo($_REQUEST['post']);

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

