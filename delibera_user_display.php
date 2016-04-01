<?php
namespace Delibera\Theme;

use WP_User_Query;
use WP_Query;

class UserDisplay
{

  public static function getOrderBy($order)
  {
    switch( $order )
    {
      case "active":
        return array( 'orderby' => 'post_count' , 'order' => 'DESC' );
      case "newest":
        return array( 'orderby' => 'registered' , 'order' => 'DESC' );
      default:
        return array( 'orderby' => 'display_name' , 'order' => 'ASC' );
    }
  }

  public static function getPaginator($total, $page)
  {
    return paginate_links( array(
      'base'       =>  add_query_arg( 'paged', '%#%' ),
      'format'     => '',
      'prev_text'  => __('&laquo;'),
      'next_text'  => __('&raquo;'),
      'show_all'   =>  true ,
      'total'      =>  $total,
      'current'    =>  $page
    ));
  }

  public static function getNumberOfPages( $total_users , $users_per_page )
  {
    return ($total_users/$users_per_page);
  }

  public static function getUsers($order_by , $search , $per_page , $paged)
  {
    return new WP_User_Query( 
      array( 
      'number'         => $per_page ,
      'offset'         => $paged,
      'fields'         => array( 'display_name', 'user_login' , 'ID' ),
      'search'         => $search . '*',
      'search_columns' => array( 'ID' , 'user_nicename' , 'user_login' , 'user_email' ),
      'orderby'        => $order_by['orderby'],
      'order'          => $order_by['order'],
      ) 
    );
  }

   public static function getLastPauta($user)
   {
      $args = array(
        'author'          =>  $user->ID, 
        'post_type'       =>  'pauta',
        'posts_per_page'  =>   1 , 
        'orderby'         =>  'post_date',
        'order'           =>  'ASC' 
      );
      $current_user_posts = get_posts( $args );
     
      return $current_user_posts? $current_user_posts[0] : null;
   }
 
  public static function get_comment_meta($id, $control)
  {
    $string = 'delibera_' . $control;
    return  get_comment_meta( $id , $string  , true);
  }

  public static function parse_comment_type($id, $control)
  {
    $string = 'delibera_comment_' . $control;
    $type = get_comment_meta( $id , $string  , true);
    //echo "DEBUG Tipo: " . $type;
    switch($type)
    {
      case 'encaminhamento':
        return __('Proposta de Encaminhamento','delibera');
      case 'discussao':
        return __('Discussão','delibera');
      case 'validacao':
        return __('Validação','delibera');
      case 'voto':
        return __('Votação','delibera');
    }
  }

  public static function getUserComments($user, $search, $per_page, $paged)
  {
    $args = array(
          'user_id' => $user->ID,
          'number' => $per_page,
          'post_type' => 'pauta',
          'status' => 'approve',
          'search' =>  $search,
          'offset' => $paged 
          );

    return get_comments( $args );
  }

}
global $user_display;
$user_display = new \Delibera\Theme\UserDisplay();

