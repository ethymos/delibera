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
 * @param int $post_id The ID of the post being saved.
 */
function delibera_salvar_pautas_mescladas( $post_id ) {
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

        if ( ! current_user_can( 'edit_pauta', $post_id ) ) {
            return;
        }

    } else {

        if ( ! current_user_can( 'edit_pauta', $post_id ) ) {
            return;
        }
    }

    // Make sure that it is set.
    if ( ! isset( $_POST['pautas_para_mesclar'] ) ) {
        return;
    }

    $pautas_para_mesclar = $_POST['pautas_para_mesclar'];

    foreach($pautas_para_mesclar as $pauta_id) {
        $wpdb->query("  INSERT INTO " . $wpdb->prefix . "comments
                            (comment_post_ID,
                            comment_author,
                            comment_author_email,
                            comment_author_url,
                            comment_author_IP,
                            comment_date,
                            comment_date_gmt,
                            comment_content,
                            comment_karma,
                            comment_approved,
                            comment_agent,
                            comment_type,
                            comment_parent,
                            user_id)
                        SELECT $post_id,
                            comment_author,
                            comment_author_email,
                            comment_author_url,
                            comment_author_IP,
                            comment_date,
                            comment_date_gmt,
                            comment_content,
                            comment_karma,
                            comment_approved,
                            comment_agent,
                            'mesclagem',
                            comment_parent,
                            user_id
                        FROM " . $wpdb->prefix . "comments
                        WHERE comment_post_ID = $pauta_id");

        $total_comments = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "comments
                                            WHERE comment_post_ID = $pauta_id AND comment_approved = 1");
        $wpdb->query("UPDATE " . $wpdb->prefix . "posts SET comment_count = $total_comments
                      WHERE ID=$post_id");

        // Importar também os comment metas

        // A criação de novos comentários gerou um problema com a organização de IDs e hierarquia
        // Solução pensnda é usar o comment+type pra guardar que ele é do tipo mesclagem
        // mais o ID da pauta original

        remove_action('save_post', 'delibera_salvar_pautas_mescladas');

        wp_update_post( array(
            'ID'           => $pauta_id,
            'post_status' => 'trash'
        ));

        add_action('save_post', 'delibera_salvar_pautas_mescladas');
    }

    update_post_meta( $post_id, '_pautas_mescladas', $pautas_para_mesclar );
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
