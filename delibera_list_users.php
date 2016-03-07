<?php
  get_header();
  $number_users_per_page = 1; 
  $search = isset( $_POST['search'] ) ?  esc_html( $_POST['search'] ) : '' ;
  $blogusers = new WP_User_Query( array( 
                     'number' => $number_users_per_page ,
                     'offset' => get_query_var( 'paged' ),
                     'fields' => array( 'display_name', 'user_login' , 'ID' ),
	             'search'         => $search,
	             'search_columns' => array( 'display_name', 'user_login', 'user_email' )
                     ) 
                   );
  echo '<div id="user_pager" class="user_pager">';
  echo paginate_links( array(
    'base' => add_query_arg( 'paged', '%#%' ),
    'format' => '',
    'prev_text' => __('&laquo;'),
    'next_text' => __('&raquo;'),
    'total' =>  $blogusers->get_total()/$number_users_per_page,
    'current' => get_query_var( 'paged' )
  ));
  //wp_reset_post_data();
  echo '</div>';
 
?>
  <div id="user_form_search" class="user_form_search">

  <form method="post" action="<?php site_url('?members') ?>"  name="form">
    <input type="text" name="search" placeholder="Busque por login, email e nome" value="<?php echo $search; ?>"/>
    <br>
    <br>
    <input type="submit" name="submit" id="submit" class="button button-primary" value="Search"  />
  </form>
 
  </div>
 
  <div id="user_list" class="user_list">
<?php
  if (!empty($blogusers))
  {
    foreach ( $blogusers->results as $user )
    {
      echo get_avatar( $user->email ); 
      echo '<h1><a href="' . get_site_url() . '?member=' . $user->user_login  . '" >' . esc_html( $user->display_name ) . '</a></h1>';
      echo '<br>';
      $args = array(
        'author'          =>  $user->ID, 
        'post_type'       =>  'pauta',
        'orderby'         =>  'post_date',
        'posts_per_page'  =>   1 , 
        'order'           =>  'ASC' 
     );

     $current_user_posts = get_posts( $args );
     $last_content = $current_user_posts[0];
?>
<div id="user_post" class="user_post" >
     <div id="user_post_title" class="user_post_title" ><h2><a href="<?php echo $last_content->guid ?>" > <?php echo  $last_content->post_title; ?></a></h2></div>
     <div id="user_post_content" class="user_post_content"><p><?php echo $last_content->post_content ;?></div>
</div>
<?php
    }
  }
  else
  {
    echo __('Nenhum usuário encontrado!' , 'delibera');
  }
  echo '</div>';
  wp_footer();

?>
</body>
</html> 

