<?php

// Configuração de permissões
$delibera_permissoes = array(
	'administrator' => array('Novo' => false, 'Caps' => array
	(
		'delete_pautas',
		'delete_private_pautas',
		'edit_pauta',
		'edit_pautas',
		'edit_private_pautas',
		'publish_pautas',
		'read_pauta',
		'read_private_pautas',
		'delete_published_pautas',
		'forcar_prazo',
		'delibera_reabrir_pauta',
		'edit_published_pautas',
		'edit_published_pauta',
		'edit_encaminhamento',
		'votar',
		'relatoria',
		'edit_others_pautas',
		'edit_others_pauta',
		'delete_others_pautas',
		'delete_others_pauta'
	)),
	'coordenador' => array('nome'=> 'Coordenador', 'Novo' => true, 'From' => 'administrator', 'Caps' => array
	(
		'delete_pautas',
		'delete_private_pautas',
		'edit_pauta',
		'edit_pautas',
		'edit_private_pautas',
		'publish_pautas',
		'read_pauta',
		'read_private_pautas',
		'delete_published_pautas',
		'forcar_prazo',
		'delibera_reabrir_pauta',
		'edit_published_pautas',
		'edit_published_pauta',
		'edit_encaminhamento',
		'votar',
		'relatoria',
		'edit_others_pautas',
		'edit_others_pauta',
		'delete_others_pautas',
		'delete_others_pauta'
	)),
	'contributor' => array('Novo' => false, 'Caps' => array
	(
		'read_pauta',
		'votar',
	)),
	'subscriber' => array('Novo' => false, 'Caps' => array
	(
		'read_pauta',
		'votar',
	)),
	'author' => array('Novo' => false, 'Caps' => array
	(
		'read_pauta',
		'votar',
	)),
	'editor' => array('Novo' => false, 'Caps' => array
	(
		'read_pauta',
		'votar',
	)),
	/*'super admin' => array('Novo' => false, 'Caps' => array
	(
		'read_pauta',
		'votar',
	)),*/
);

/**
 * Verifica se o usuário atual pode participar das discussão
 * de uma pauta votando ou discutindo.
 *
 * Por padrão retorna true apenas de o usuário tiver a capability 'votar',
 * mas se a opção "Todos os usuários da rede podem participar" estiver habilitada
 * retorna true para todos os usuários logados.
 *
 * Quando estiver na single da pauta, retorna false sempre que ela
 * estiver com o prazo encerrado.
 *
 * @return bool
 */
function delibera_current_user_can_participate($permissao = 'votar') {
    global $post;

    $options = delibera_get_config();

    if (is_singular('pauta') && delibera_get_prazo($post->ID) == -1) {
        return false;
    } else if (is_multisite() && $options['todos_usuarios_logados_podem_participar'] == 'S') {
        return is_user_logged_in();
    } else {
        return current_user_can($permissao);
    }
}

function delibera_can_comment($postID = '')
{
	if(is_null($postID))
	{
		$post = get_post($postID);
		$postID = $post->ID;
	}

	$situacoes_validas = array('validacao' => true, 'discussao' => true, 'emvotacao' => true, 'elegerelator' => true);
	$situacao = delibera_get_situacao($postID);

	if(array_key_exists($situacao->slug, $situacoes_validas))
	{
		return delibera_current_user_can_participate();
	}
	elseif($situacao->slug == 'relatoria')
	{
		return current_user_can('relatoria');
	}
	return false;
}
?>