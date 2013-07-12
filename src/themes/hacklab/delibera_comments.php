<?php

require_once(ABSPATH . 'wp-admin/includes/screen.php');

global $delibera_comments_padrao;

$situacao = delibera_get_situacao($id);

if ($situacao->slug == 'comresolucao') {
    $title = 'Encaminhamentos propostos';
} else if ($situacao->slug == 'validacao') {
    $title = '';
    $votes = delibera_get_comments_validacoes($post->ID);
    $approvals = (int) get_post_meta($post->ID, 'numero_validacoes', true);
    $rejections = (int) get_post_meta($post->ID, 'delibera_numero_comments_validacoes', true) - $approvals;
} else if ($situacao->slug == 'relatoria') {
    $title = 'Relatoria da pauta';
} else {
    $title = 'Discussão sobre a pauta';
}

if (($situacao->slug == "validacao" || $situacao->slug == "emvotacao") && !$delibera_comments_padrao === true) {
    comment_form(); 
}

?>

<div class="actions">
    <div id="<?php echo ($situacao->slug == 'comresolucao') ? 'encaminhamentos' : 'comments'; ?>" class="comments-area">
        <?php if (have_comments()) : ?>
            <h2 class="comments-title"><?php echo $title; ?></h2>
            <?php if ($situacao->slug == 'validacao') : ?>
                <div class="votes">
                    <div class="votes-agree">
                        <h3>Pessoas que votaram a favor (<?php echo $approvals; ?>)</h3>
                        <?php if ($approvals) : ?>
                            <ul class="clearfix">
                                <?php foreach ($votes as $vote) : ?>
                                    <?php if (get_comment_meta($vote->comment_ID, 'delibera_validacao', true) == 'S') : ?>
                                        <?php
                                        $authorName = get_the_author_meta('display_name', $vote->user_id);
                                        $avatar = get_avatar($vote->user_id, 44, '', $authorName);
                                        // parseia a tag <img> com o avatar do usuário para poder adicionar o atributo
                                        // title já que não existe uma função no wp que retorna apenas a url do avatar do usuário
                                        $avatar = preg_replace('|/>$|', " title='{$authorName}' />", $avatar);
                                        ?>
                                        <li><?php echo $avatar; ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="votes-disagree">
                        <h3>Pessoas que votaram contra (<?php echo $rejections; ?>)</h3>
                        <?php if ($rejections) : ?>
                            <ul class="clearfix">
                                <?php foreach ($votes as $vote) : ?>
                                    <?php if (get_comment_meta($vote->comment_ID, 'delibera_validacao', true) == 'N') : ?>
                                        <?php
                                        $authorName = get_the_author_meta('display_name', $vote->user_id);
                                        $avatar = get_avatar($vote->user_id, 44, '', $authorName);
                                        // parseia a tag <img> com o avatar do usuário para poder adicionar o atributo
                                        // title já que não existe uma função no wp que retorna apenas a url do avatar do usuário
                                        $avatar = preg_replace('|/>$|', " title='{$authorName}' />", $avatar);
                                        ?>
                                        <li><?php echo $avatar; ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else : ?>
                <ol class="commentlist">
                    <?php delibera_wp_list_comments(); ?>
                </ol>
            <?php endif; ?>
        <?php else : 
            if (!comments_open()) : ?>
               <p class="nocomments"><?php _e( 'Comments are closed.', 'twentyten' ); ?></p>
            <?php endif; // end ! comments_open() ?>
        <?php endif; // end have_comments() ?>
        
        <?php if (($situacao->slug != "validacao" && $situacao->slug != "emvotacao" && $situacao->slug != "naovalidada") || $delibera_comments_padrao === true) {
            comment_form();
            if (function_exists('ecu_upload_form_default')) {
                ecu_upload_form_default();
            } 
        } ?>
    </div>
</div>

    
