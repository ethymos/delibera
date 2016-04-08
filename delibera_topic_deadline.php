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
		\Delibera\Flow::reabrirPauta($_REQUEST['post'], true);

		wp_redirect( admin_url( 'edit.php?post_type=pauta') );
	}
	else
	{
		wp_die(__('Você não tem permissão para re-abrir discussão sobre uma pauta','delibera'), __('Sem permissão','delibera'));
	}
}
add_action('admin_action_delibera_reabrir_pauta_action', 'delibera_reabrir_pauta_action');

require_once __DIR__.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'delibera_cron.php';


