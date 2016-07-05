<?php

/**
 * Gera um arquivo XLS com as opiniões e propostas de
 * encaminhamento feitos pelos usuários nas pautas
 */

// contorna problema com links simbolicos no ambiente de desenvolvimento
$wp_root = dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/../../';

require_once($wp_root . 'wp-load.php');

if (!current_user_can('manage_options')) {
    die('Você não deveria estar aqui');
}

//$pautas = get_posts(array('post_type' => 'pauta', 'post_status' => 'publish'));
$pautas_query = new WP_Query(array(
	'post_type' => 'pauta',
	'post_status' => 'publish',
	'posts_per_page' => -1
));

$comments = array();
/* @var $pauta WP_POST */

if($pautas_query->have_posts())
{
	global $post;
	while ($pautas_query->have_posts())
	{
		$pautas_query->the_post();
		$pauta = $post;	
		
		$situacao = delibera_get_situacao($pauta->ID);
		$comment_fake = new stdClass();
		$comment_fake->pauta_title = get_the_title($pauta->ID);
		$comment_fake->pauta_status = $situacao->name;
		$comment_fake->type = 'Pauta';
		$comment_fake->link = get_permalink($pauta);
		$comment_fake->concordaram = (int) get_post_meta($pauta->ID, 'delibera_numero_curtir', true);
		$comment_fake->discordaram = (int) get_post_meta($pauta->ID, 'delibera_numero_discordar', true);
		$comment_fake->votes_count = (int) get_post_meta($pauta->ID, "delibera_numero_comments_votos", true);
		$comment_fake->comment_author = get_the_author();
		$comment_fake->comment_author_email = get_the_author_meta('email', $pauta->post_author);
		$comment_fake->comment_content = get_the_content();
		
		$comment_tmp = delibera_get_comments($pauta->ID, array('discussao', 'encaminhamento', 'encaminhamento_selecionado', 'resolucao'));
	    $comments = array_merge(
	        $comments,
	    	array($comment_fake),
	        $comment_tmp
	    );
	}
	foreach ($comments as $comment) //TODO with this get bigger, we will have memory problem, better read pauta, comments and write, read next...
	{
		if($comment->type == 'Pauta') continue;
		
		$situacao = delibera_get_situacao($comment->comment_post_ID);
	    $comment->pauta_title = get_the_title($comment->comment_post_ID);
	    $comment->pauta_status = $situacao->name;
	    $comment->type = delibera_get_comment_type_label($comment, false, false);
	    $comment->link = get_comment_link($comment);
	    $comment->concordaram = (int) get_comment_meta($comment->comment_ID, 'delibera_numero_curtir', true);
	    $comment->discordaram = (int) get_comment_meta($comment->comment_ID, 'delibera_numero_discordar', true);
	    $comment->votes_count = (int) get_comment_meta($comment->comment_ID, "delibera_comment_numero_votos", true);
	}
}

header('Pragma: public');
header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Transfer-Encoding: none');
header('Content-Type: application/vnd.ms-excel; charset=UTF-8'); // This should work for IE & Opera
header("Content-type: application/x-msexcel; charset=UTF-8"); // This should work for the rest
header('Content-Disposition: attachment; filename=relatorio.xls');

echo utf8_decode("
<table>
    <tr>
        <td>Título da Pauta</td>
        <td>Situação</td>
        <td>Nome do Autor</td>
        <td>E-mail</td>
        <td>Tipo (Pauta ou tipo de comentário)</td>
        <td>Conteúdo</td>
        <td>Link</td>
        <td>Concordaram</td>
        <td>Discordaram</td>
        <td>Votos</td>
    </tr>"
);
    
?>

<?php foreach ($comments as $comment) : ?>

    <?php ob_start(); ?>
    
    <tr>
        <td><?php echo $comment->pauta_title; ?></td>
        <td><?php echo $comment->pauta_status; ?></td>
        <td><?php echo $comment->comment_author; ?></td>
        <td><?php echo $comment->comment_author_email; ?></td>
        <td><?php echo $comment->type; ?></td>
        <td><?php echo $comment->comment_content; ?></td>
        <td><?php echo $comment->link; ?></td>
        <td><?php echo $comment->concordaram; ?></td>
        <td><?php echo $comment->discordaram; ?></td><br />
        <td><?php echo $comment->votes_count; ?></td>
    </tr>

    <?php echo utf8_decode(ob_get_clean()); ?>
<?php endforeach; ?>

</table>