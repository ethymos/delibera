<?php

require_once(ABSPATH . 'wp-admin/includes/screen.php');

global $delibera_comments_padrao;

$situacao = delibera_get_situacao($id);

if (($situacao->slug == "validacao" || $situacao->slug == "emvotacao") && !$delibera_comments_padrao === true) {
    comment_form(); 
}

?>

<div class="actions">
    <div id="<?php echo ($situacao->slug == 'comresolucao') ? 'encaminhamentos' : 'comments'; ?>" class="comments-area">
        <?php if (have_comments()) : ?>
            <h2 class="comments-title"><?php echo ($situacao->slug == 'comresolucao') ? 'Encaminhamentos propostos' : 'Discussão sobre a pauta'; ?></h2>
            <ol class="commentlist">
                <?php delibera_wp_list_comments(); ?>
            </ol>
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

    
