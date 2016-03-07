<?php
// Create the query var so that WP catches the custom /member/username url
function userpage_rewrite_add_var( $vars ) {
    $vars[] = 'member';
    $vars[] = 'merbers';
    return $vars;
}
add_filter( 'query_vars', 'userpage_rewrite_add_var' );

// Create the rewrites
function userpage_rewrite_rule() {
    add_rewrite_tag( '%member%', '([^&]+)' );
    add_rewrite_rule(
        '^member/([^/]*)/?',
        'index.php?member=$matches[1]',
        'top'
    );
    add_rewrite_tag( '%members%', '([^&]+)' );
    add_rewrite_rule(
        '^members',
        'index.php?members',
        'top'
    );
}
add_action('init','userpage_rewrite_rule');

// Catch the URL and redirect it to a template file
function userpage_rewrite_catch() {
    global $wp_query;
    if ( array_key_exists( 'member', $wp_query->query_vars ) ) {
        include ( ABSPATH . 'wp-content/plugins/delibera/delibera_user_page.php');
        exit;
    }
    if ( array_key_exists( 'members', $wp_query->query_vars ) ) {
        echo "hello word!";
        include ( ABSPATH . 'wp-content/plugins/delibera/delibera_list_users.php');
        exit;
    }
}
add_action( 'template_redirect', 'userpage_rewrite_catch' );

function user_last_login( $user_login, $user ){
    update_user_meta( $user->ID, '_last_login', time() );
}
add_action( 'wp_login', 'user_last_login', 10, 2 );



