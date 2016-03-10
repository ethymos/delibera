<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'delibera_user_display.php';

class Delibera_User_page
{
  public function __construct()
  {
  }

  function page($user)
  {
    get_header();
    $per_page = isset( $_GET['per-page'] ) ?  esc_html( $_GET['per-page'] ) : '20' ;
    $search = isset( $_GET['search'] ) ?  esc_html( $_GET['search'] ) : '' ;
    $order  = isset( $_GET['order-by'] ) ?  esc_html( $_GET['order-by'] ) : '' ;
    $this->html( $user , $per_page );
    wp_footer();
    ?>
    </body>
    </html> 
    <?php
  }
  
  function html( $user , $per_page )
  { 
    $this->user_pautas( $user , $per_page );
    $this->user_comments($user);
    wp_reset_post_data();
  }

  function user_pautas($user, $per_page)
  {
    global $user_display;
    ?>
    <p>
      <div>
        <?php echo get_avatar( $user->ID ); ?>
        <h1><?php echo $user->first_name ?></h1>
      </div>
      <div>
        <h2><?php echo 'Pautas:'; ?></h2>
      </div>
    </p>
    <?php
    $args = array(
        'author' => $user->ID,
        'status' => 'approve',
        'posts_per_page' => $per_page,
        'post_type' => 'pauta',
        'paged' => get_query_var( 'paged' ) 
    );
    $author_posts = new WP_Query( $args );
    $user_display->formPaginator( $per_page );
    $user_display->displayPaginator($author_posts->max_num_pages , get_query_var( 'paged' ) );
    foreach( $author_posts->posts as $post )
    {
      echo '<h2 id="post_title" class="post_title" ><a href="' . get_permalink($post) . '">' . $post->post_title . '</a></h2>';
      echo '<div id="post_content" class="post_content">' . $post->post_content . '</div>';
      echo '<br>';
    }
    $user_display->displayPaginator($author_posts->max_num_pages , get_query_var( 'paged' ) );
  }
  
  function user_comments($user)
  {
      $args = array(
          'user_id' => $user->ID,
          'number' => 2,
          'post_type' => 'pauta',
          'status' => 'approve',
          'offset' => get_query_var( 'pagec' ) 
          );

      $comments = get_comments( $args );
      echo '<div id="user_pagination" class="user_pagination">';
        echo $this->get_paginator( $comments[0]->comment_count/2 , get_query_var( 'pagec' ) );
      echo '</div>';
      if ( $comments )
      {
          $output.= '<h2>Comentários: </h2>';
          $output.= '<div class="user_comment">';
          foreach ( $comments as $comment )
          {
            $output.= '<strong>' . $this->parse_comment_type( $comment->comment_ID , 'tipo') . ': </strong><br>';
            $output.= $comment->comment_content . '<br>';
            $output.= '' . wp_get_attachment_image(get_comment_meta( $comment->comment_ID , 'attachmentId', true)) . '<br>';
            $output.= '<p>Na  pauta <a href="'.get_comment_link( $comment->comment_ID ).'">';
            $output.= get_the_title($comment->comment_post_ID);
            $output.= ', em </a> '. mysql2date('m/d/Y', $comment->comment_date, $translate);
            if( $this->get_comment_meta( $comment->comment_ID , 'tipo') != discussao ) 
            {
              $output.= ' <br><br>';
              $output.= 'Numero de votos: ';
              $votaram = $this->get_comment_meta( $comment->comment_ID , 'numero_votos');
              $output.=  $votaram ? $votaram : '0';
              $output.= ' <br>';
            }
            $output.= '<p>Concordaram: ';
            $curtiram = $this->get_comment_meta( $comment->comment_ID , 'numero_curtir' );
            $output.=  $curtiram ? $curtiram : '0';
            $output.= '<br>Discordaram: ';
            $discordaram =  $this->get_comment_meta( $comment->comment_ID , 'numero_discordar' );
            $output.= $discordaram ? $discordaram : '0';
            $output.= ' </p>';
          }
          $output.= '</div>';
          echo $output;
      } else { echo __('Usuário não possui nenhum comentário' , 'delibera'); }
  }
  
  function get_comment_meta($id, $control)
  {
    $string = 'delibera_comment_' . $control;
    return  get_comment_meta( $id , $string  , true);
    }

  function parse_comment_type($id, $control)
  {
    $type = $this->get_comment_meta( $id , $control );
    switch($type)
    {
      case 'encaminhamento':
        return 'Proposta de Encaminhamento';
      case 'discussao':
        return 'Discussão';
    }
  }
}

global $delibera_user_page;
$delibera_user_page = new Delibera_User_Page();
