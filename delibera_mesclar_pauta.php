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
                            'posts_per_page' => 10
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
                    <input class="in-mesclar-pauta" id="in-pauta-<?php the_ID(); ?>" value="<?php the_ID(); ?>" type="checkbox">
                    <?php the_title(); ?> </label>
            </li>

<?php } ?>
        </ul>
        <div><a class="button button-large" href="#" id="carregar-mais-pautas">Carregar mais pautas</a></div>
    </div>
    <script>
        var pageNumber = 1;
        var maxPages = <?php echo $pautas->max_num_pages; ?>;

        jQuery(function(){
            function check_show_mais_pautas() {
                if (pageNumber >= maxPages) {
                    jQuery("#carregar-mais-pautas").hide();
                }
            }

            function carregar_pautas(pageNumber) {
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: "action=delibera_get_more_pautas&paged="+ pageNumber,
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
                    "serão mesclados com a pauta atual, deseja continuar?")) {
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
 * @param int $post_id The ID of the post being saved.
 */
function myplugin_save_meta_box_data( $post_id ) {

    /*
     * We need to verify this came from our screen and with proper authorization,
     * because the save_post action can be triggered at other times.
     */

    // Check if our nonce is set.
    if ( ! isset( $_POST['myplugin_meta_box_nonce'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['myplugin_meta_box_nonce'], 'myplugin_meta_box' ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }

    } else {

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    /* OK, it's safe for us to save the data now. */

    // Make sure that it is set.
    if ( ! isset( $_POST['myplugin_new_field'] ) ) {
        return;
    }

    // Sanitize user input.
    $my_data = sanitize_text_field( $_POST['myplugin_new_field'] );

    // Update the meta field in the database.
    update_post_meta( $post_id, '_my_meta_value_key', $my_data );
}
add_action( 'save_post', 'myplugin_save_meta_box_data' );

/**
 * Função ajax pra recuperar novas pautas
 */
function delibera_get_more_pautas(){
    $paged = $_POST['paged'];

    $args = array(
        'paged' => $paged,
        'posts_per_page' => 10,
        'post__not_in' => $_POST['post_atual']
    );
    $pautas = new WP_Query($args);

    if ($pautas->have_posts()) {
        while ($pautas->have_posts()) {
            $pautas->the_post();
?>
            <li id="pauta-<?php the_ID(); ?>" >
                <label>
                    <input id="in-pauta-<?php the_ID(); ?>" value="<?php the_ID(); ?>" type="checkbox">
                    <?php the_title(); ?> </label>
            </li>

<?php
        }
    }

    exit;
}

add_action('wp_ajax_delibera_get_more_pautas', 'delibera_get_more_pautas');
add_action('wp_ajax_nopriv_delibera_get_more_pautas', 'delibera_get_more_pautas');
