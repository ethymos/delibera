<?php
require_once('delibera_comments_query.php');
require_once('delibera_comments_template.php');


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