<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'delibera_user_display.php';

class MemberPath
{

  public function __construct()
  {
    add_filter( 'query_vars', array( $this , 'userpage_rewrite_add_var' ) );
    add_action( 'init', array( $this , 'userpage_rewrite_rule') );
    add_action( 'template_redirect', array( $this , 'userpage_rewrite_catch') );
    add_filter( 'excerpt_length', array( $this , 'new_excerpt_length' ) );
    add_filter( 'excerpt_more', array( $this , 'new_excerpt_more' ) );

  }

  // Create the query var so that WP catches the custom /member/username url
  public function userpage_rewrite_add_var( $vars ) 
  {
      $vars[] = 'pautasfor';
      $vars[] = 'commentsfor';
      $vars[] = 'merbers';
      return $vars;
  }
  
  // Create the rewrites
  public function userpage_rewrite_rule()
  {
      add_rewrite_tag( '%pautasfor%', '([^&]+)' );
      add_rewrite_rule(
          '^blog/delibera/membro/([^/]*)/pautas?',
          'index.php?pautasfor=$matches[1]',
          'top'
      );
      add_rewrite_tag( '%commentsfor%', '([^&]+)' );
      add_rewrite_rule(
          '^blog/delibera/membro/([^/]*)/comentarios?',
          'index.php?commentsfor=$matches[1]',
          'top'
      );
      add_rewrite_tag( '%members%', '' );
      add_rewrite_rule(
          '^blog/delibera/membros',
          'index.php?members',
          'top'
      );
  }
  
  // Catch the URL and redirect it to a template file
  public function userpage_rewrite_catch()
  {
      global $wp_query;
      if ( array_key_exists( 'pautasfor', $wp_query->query_vars ) ) {
          load_template(__DIR__ . DIRECTORY_SEPARATOR . 'themes/creta/page-author-pautas.php' , true);
          exit;
      }
      if ( array_key_exists( 'commentsfor', $wp_query->query_vars ) ) {
          load_template(__DIR__ . DIRECTORY_SEPARATOR . 'themes/creta/page-author-comments.php' , true);
          exit;
      }
      if ( array_key_exists( 'members', $wp_query->query_vars ) ) {
          load_template(__DIR__ . DIRECTORY_SEPARATOR . 'themes/creta/page-authors.php' , true);
          exit;
      }
  }

  // Changing excerpt length
  public function new_excerpt_length($length) 
  {
    return 100;
  }

  // Changing excerpt more
  public function new_excerpt_more($more)
  {
    return '...';
  }

}
global $member_path;
$member_path= new MemberPath();
