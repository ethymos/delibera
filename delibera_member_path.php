<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'delibera_user_page.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'delibera_list_users.php';

class DeliberaMemberPath
{

  public function __construct()
  {
    add_filter( 'query_vars', array( $this , 'userpage_rewrite_add_var' ) ) ;
    add_action( 'init', array( $this , 'userpage_rewrite_rule') ) ;
    add_action( 'template_redirect', array( $this , 'userpage_rewrite_catch') ) ;
  }

  // Create the query var so that WP catches the custom /member/username url
  public function userpage_rewrite_add_var( $vars ) {
      $vars[] = 'login';
      $vars[] = 'merbers';
      return $vars;
  }
  
  // Create the rewrites
  public function userpage_rewrite_rule() {
      add_rewrite_tag( '%login%', '([^&]+)' );
      add_rewrite_rule(
          '^delibera/membro/([^/]*)/?',
          'index.php?login=$matches[1]',
          'top'
      );
      add_rewrite_tag( '%members%', '' );
      add_rewrite_rule(
          '^delibera/membros',
          'index.php?members',
          'top'
      );
  }
  
  // Catch the URL and redirect it to a template file
  public function userpage_rewrite_catch() {
      global $wp_query;
      if ( array_key_exists( 'login', $wp_query->query_vars ) ) {
          $login = $wp_query->query_vars['login'];
          $user = get_user_by( 'login' , $login ); 
          global $delibera_user_page;
          $delibera_user_page->page($user);
          exit;
      }
      if ( array_key_exists( 'members', $wp_query->query_vars ) ) {
          global $delibera_list_users;
          $delibera_list_users->page();
          exit;
      }
  }
  
}
global $delibera_member_path;
$delibera_member_path= new DeliberaMemberPath();
