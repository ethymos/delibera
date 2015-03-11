<?php
/**
 * Adiciona o metabox para mesclar pautas
 */
function delibera_add_mesclar_pauta_metabox() {

    add_meta_box(
        'mesclar_pauta_id',
        __( 'Mesclar pautas', 'delibera' ),
        'delibera_mesclar_pauta_callback',
        'pauta',
        'side'
    );
}
add_action( 'add_meta_boxes', 'delibera_add_mesclar_pauta_metabox' );

/**
 * Imprime metabox de mesclagem.
 *
 * @param WP_Post $post The object for the current pauta.
 */
function delibera_mesclar_pauta_callback( $post ) {

    // Campo nonce para verificação de segurança
    wp_nonce_field( 'delibera_mesclar_pautas_meta_box', 'delibera_mesclar_pautas_meta_box_nonce' );

    $pautas_mescladas = get_post_meta( $post->ID, '_pautas_mescladas', true );

    $pautas = new WP_Query( array(
                            'post__not_in' => array($post->ID),
                            'post_type' => 'pauta',
                            'post_status' => 'publish',
                            'posts_per_page' => 10,
                            'paged' => 1
                        ) );
?>
    <div id="mesclar-pautas" class="categorydiv">
        <ul id="mesclar-pauta-checklist" class="categorychecklist form-no-clear">
<?php
    while ( $pautas->have_posts() ) {
        $pautas->the_post();
?>
            <li id="pauta-<?php the_ID(); ?>" >
                <label>
                    <input name="pautas_para_mesclar[]" class="in-mesclar-pauta" id="in-pauta-<?php the_ID(); ?>" value="<?php the_ID(); ?>" type="checkbox">
                    <?php the_title(); ?> </label>
            </li>

<?php } ?>
        </ul>
        <div><a class="button button-large" href="#" id="carregar-mais-pautas">Carregar mais pautas</a></div>
    </div>
    <script>
        var pageNumber = 2;
        var maxPages = <?php echo $pautas->max_num_pages; ?>;

        jQuery(function(){
            function check_show_mais_pautas() {
                if (pageNumber > maxPages) {
                    jQuery("#carregar-mais-pautas").hide();
                }
            }

            function carregar_pautas(pageNumber) {
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: "action=delibera_get_more_pautas&post_atual=<?php echo $post->ID; ?>&paged="+ pageNumber,
                    success: function(html){
                        jQuery("#mesclar-pauta-checklist").append(html);
                        jQuery("#carregar-mais-pautas").text("Carregar mais pautas")
                        check_show_mais_pautas();
                    }
                });
                return false;
            }

            jQuery("#carregar-mais-pautas").click(function(event){
                event.preventDefault();
                jQuery("#carregar-mais-pautas").text("Carregando...")
                carregar_pautas(pageNumber++);
            });

            jQuery("#publish").click(function(){
                if (jQuery(".in-mesclar-pauta:checked").length > 0) {
                    if (confirm("Você selecionou pautas a serem mescladas. Todos os comentários das pautas selecionadas " +
                    "serão mesclados com a pauta atual e os posts mesclados serão postos na lixeira, deseja continuar?")) {
                        return true;
                    } else {
                        return false;
                    }
                }
            });

            check_show_mais_pautas();
        });
    </script>
<?php
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $pauta_master_id The ID of the post being saved.
 */
function delibera_salvar_pautas_mescladas( $pauta_master_id ) {
    global $wpdb;

    if ( "pauta" != get_post_type() ) {
        return;
    }

    // Verificar o nonce
    if ( ! isset( $_POST['delibera_mesclar_pautas_meta_box_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( $_POST['delibera_mesclar_pautas_meta_box_nonce'], 'delibera_mesclar_pautas_meta_box' ) ) {
        return;
    }

    // Verificar se esse não é um autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Verificar permissões
    if ( isset( $_POST['post_type'] ) && 'pauta' == $_POST['post_type'] ) {

        if ( ! current_user_can( 'edit_pauta', $pauta_master_id ) ) {
            return;
        }

    } else {

        if ( ! current_user_can( 'edit_pauta', $pauta_master_id ) ) {
            return;
        }
    }

    // Make sure that it is set.
    if ( ! isset( $_POST['pautas_para_mesclar'] ) ) {
        return;
    }

    $pautas_para_mesclar = $_POST['pautas_para_mesclar'];

    foreach($pautas_para_mesclar as $pauta_mesclagem_id) {

        $comments2update =  $wpdb->get_col("SELECT comment_ID FROM " . $wpdb->prefix . "comments
                                            WHERE comment_post_ID=$pauta_mesclagem_id");

        $wpdb->query("  UPDATE " . $wpdb->prefix . "comments
                        SET comment_post_ID = $pauta_master_id
                        WHERE comment_post_ID = $pauta_mesclagem_id");

        $post_slug = $wpdb->get_var("SELECT post_name FROM " . $wpdb->prefix . "posts
                                    WHERE ID = $pauta_mesclagem_id");

        remove_action('save_post', 'delibera_salvar_pautas_mescladas');

        foreach ($comments2update as $comment_ID) {
            delibera_save_comment_metas($comment_ID);
            add_comment_meta($comment_ID, 'pauta_original', $pauta_master_id);
        }

        wp_update_post( array(
            'ID'           => $pauta_mesclagem_id,
            'post_status' => 'trash'
        ));

        add_action('save_post', 'delibera_salvar_pautas_mescladas');

        // Limpa qualquer direcionamento anterior
        $wpdb->query("DELETE FROM " . $wpdb->prefix . "postmeta WHERE meta_value = '$post_slug'");
        // Linka nova pauta a pauta corrente para que seja feito o direcionamento e não se quebre o permalink
        add_post_meta( $pauta_master_id, '_wp_old_slug', $post_slug);

        // Redireciona qualquer post_name anterior para o novo post para não haver quebra de permalink
        $wpdb->query("UPDATE " . $wpdb->prefix . "postmeta SET post_id = $pauta_master_id
                  WHERE meta_key='_wp_old_slug' AND post_id=$pauta_mesclagem_id");
    }

    update_post_meta( $pauta_master_id, '_pautas_mescladas', $pautas_para_mesclar );

}
add_action( 'save_post', 'delibera_salvar_pautas_mescladas' );

/**
 * Função ajax pra recuperar novas pautas
 */
function delibera_get_more_pautas(){
    $paged = $_POST['paged'];

    $args = array(
        'paged' => $paged,
        'posts_per_page' => 10,
        'post__not_in' => array($_POST['post_atual']),
        'post_type' => 'pauta',
        'post_status' => 'publish',
    );
    $pautas = new WP_Query($args);

    if ($pautas->have_posts()) {
        while ($pautas->have_posts()) {
            $pautas->the_post();
?>
            <li id="pauta-<?php the_ID(); ?>" >
                <label>
                    <input name="pautas_para_mesclar[]" class="in-mesclar-pauta" id="in-pauta-<?php the_ID(); ?>" value="<?php the_ID(); ?>" type="checkbox">
                    <?php the_title(); ?> </label>
            </li>

<?php
        }
    }

    exit;
}

add_action('wp_ajax_delibera_get_more_pautas', 'delibera_get_more_pautas');
add_action('wp_ajax_nopriv_delibera_get_more_pautas', 'delibera_get_more_pautas');
