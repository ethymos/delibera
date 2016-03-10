<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'delibera_user_display.php';

class DeliberaListUsers 
{

  public function html( $per_page , $search , $order )
  {
    global $delibera_user_display;
    $order_by = $delibera_user_display->getOrderBy($order);
    $paged = get_query_var( 'paged' );
    $blogusers = $delibera_user_display->getUsers( $order_by , $search , $per_page , $paged );
    $number_of_pages = $delibera_user_display->getNumberOfPages( $blogusers->get_total() , $per_page );

    $delibera_user_display->formSearch($search);
    $delibera_user_display->formOrderAndPaginator( $order , $per_page );
    $delibera_user_display->displayPaginator( $number_of_pages , $paged );
   ?> 
   <div id="user_list" class="user_list">
   <?php
     if ($blogusers)
     {
       foreach ( $blogusers->results as $user )
       {
         $delibera_user_display->avatarAndName($user);
         $delibera_user_display->displayLastPauta($user);
       }
     }
     else
     {
       echo __('Nenhum usuário encontrado!' , 'delibera');
     }
   $delibera_user_display->displayPaginator( $number_of_pages , $paged );
   wp_reset_post_data();
   ?>
   <div>
   <?php
  }

  public function page(){
    get_header();
    $per_page = isset( $_GET['per-page'] ) ?  esc_html( $_GET['per-page'] ) : '20' ;
    $search = isset( $_GET['search'] ) ?  esc_html( $_GET['search'] ) : '' ;
    $order  = isset( $_GET['order-by'] ) ?  esc_html( $_GET['order-by'] ) : '' ;
    $this->html($per_page , $search , $order);
    wp_footer(); 
    ?>
    </body>
    </html>
  <?php
  }
}

global $delibera_list_users;
$delibera_list_users = new DeliberaListUsers();
