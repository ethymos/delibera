<?php

class UserDisplay
{

  function getOrderBy($order)
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

  public function getPaginator($total, $page)
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

  function getNumberOfPages( $total_users , $users_per_page )
  {
    return ($total_users/$users_per_page);
  }

  function getUsers($order_by , $search , $per_page , $paged)
  {
    return new WP_User_Query( array( 
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

   public function getLastPauta($user)
   {
      $args = array(
        'author'          =>  $user->ID, 
        'post_type'       =>  'pauta',
        'posts_per_page'  =>   1 , 
        'orderby'         =>  'post_date',
        'order'           =>  'ASC' 
      );
      $current_user_posts = get_posts( $args );
      return $current_user_posts[0];
   }
 
}

global $user_display;
$user_display = new UserDisplay();
