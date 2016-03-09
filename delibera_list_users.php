<?php


class Delibera_User_List 
{
  public function __construct()
  {
    //$this->html();
  }
  function get_paginator($total, $page)
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

  function get_number_of_pages( $total_users , $users_per_page )
  {
    return ($total_users/$users_per_page);
  }

  function get_order_by($order)
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

  function member_page( $per_page , $search , $order )
  {
    $order_by = $this->get_order_by($order);
    $blogusers = new WP_User_Query( array( 
                       'number'         => $per_page ,
                       'offset'         => get_query_var( 'paged' ),
                       'fields'         => array( 'display_name', 'user_login' , 'ID' ),
                       'search'         => $search . '*',
                       'search_columns' => array( 'ID' , 'user_nicename' , 'user_login' , 'user_email' ),
                       'orderby'        => $order_by['orderby'],
                       'order'          => $order_by['order'],
                       ) 
                     );
    ?>

    <div id="user_form_search" class="user_form_search">
      <form method="get"  name="form">
        <p>
          <input type="text" name="search" placeholder="Pesquisar Membros ..." value="<?php echo $search; ?>"/>
          <br>
          <br>
          <input type="submit" id="submit" class="button button-primary" value="Pesquisar"  />
        </p>
      </form>
    </div>
    
    <form method="get">
      <label for="order-by"><?php echo __('Ordenar por: ' , 'delibera' ); ?></label>
      <select id="order-by" name="order-by"  onchange='if(this.value != 0) { this.form.submit(); }' >
        <!--option value="active" <?php echo $order=='active' ? 'selected' : '' ;?> >Atividade Recente</option-->
        <option value="active" <?php echo $order=='active' ? 'selected' : '' ;?> ><?php echo __('Possui Maior Número de Posts' , 'delibera'); ?></option>
        <option value="newest" <?php echo $order=='newest' ? 'selected' : '' ;?> ><?php echo __('Recém-registrado' , 'delibera'); ?></option>
        <option value="alphabetical" <?php echo $order=='alphabetical' ? 'selected' : '' ;?> ><?php echo __('Ordem alfabética' , 'delibera'); ?></option>
      </select>
      <label for="per-page"><?php echo __('Usuários por Página:' , 'delibera'); ?></label>
      <select id="per-page" name="per-page"  onchange='if(this.value != 0) { this.form.submit(); }' >
        <option value="5" <?php echo $per_page=='5' ? 'selected' : '' ;?> >5</option>
        <option value="10" <?php echo $per_page=='10' ? 'selected' : '' ;?> >10</option>
        <option value="20" <?php echo $per_page=='20' ? 'selected' : '' ;?> >20</option>
      </select>
    </form>
    <div id="user_pager" class="user_pager">
      <p>
        <?php echo $this->get_paginator(
                     $this->get_number_of_pages( $blogusers->get_total() , $per_page ), 
                     get_query_var( 'paged' ) 
                   ); 
        ?>
      </p>
    </div>
    
    <div id="user_list" class="user_list">
    <?php
      if ($blogusers)
      {
        foreach ( $blogusers->results as $user )
        {
          echo '<h1><a id="user_name" class="user_name" href="' . get_site_url() . '/delibera/membro/' . $user->user_login  . '" >' 
               . '<p>' . get_avatar( $user->ID ) . '</p>' 
               . '<p>' . esc_html( $user->display_name ) . ' - ' . get_usernumposts($user->ID) . ' posts</p></a></h1>';
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
      <div id="user_post_title" class="user_post_title" ><h2><a href="<?php echo $last_content->guid; ?>" ><?php echo  $last_content->post_title; ?></a></h2></div>
      <div id="user_post_content" class="user_post_content"><p><?php echo $last_content->post_content;?></div>
    </div>
    <?php
        }
      }
      else
      {
        echo __('Nenhum usuário encontrado!' , 'delibera');
      }
    
    ?>
    <div id="user_pager" class="user_pager">
      <p>
        <?php echo $this->get_paginator(
                     $this->get_number_of_pages( $blogusers->get_total() , $per_page ), 
                     get_query_var( 'paged' ) 
                   ); 
        ?>
      </p>
    </div>
    <?php
    wp_reset_post_data();
  }

  public function page(){
    get_header();
    get_the_content();
    //get_sidebar();
    get_the_content();
    $per_page = isset( $_GET['per-page'] ) ?  esc_html( $_GET['per-page'] ) : '20' ;
    $search = isset( $_GET['search'] ) ?  esc_html( $_GET['search'] ) : '' ;
    $order  = isset( $_GET['order-by'] ) ?  esc_html( $_GET['order-by'] ) : '' ;
    $this->member_page($per_page , $search , $order);
    wp_footer(); 
    ?>
    </body>
    </html>
  <?php
  }

  public function html(){
    get_the_content();
    $per_page = isset( $_GET['per-page'] ) ?  esc_html( $_GET['per-page'] ) : '20' ;
    $search = isset( $_GET['search'] ) ?  esc_html( $_GET['search'] ) : '' ;
    $order  = isset( $_GET['order-by'] ) ?  esc_html( $_GET['order-by'] ) : '' ;
    $this->member_page($per_page , $search , $order);
  }
}

global $delibera_user_list;
$delibera_user_list = new Delibera_User_List();
