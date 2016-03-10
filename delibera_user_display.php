<?php

class DeliberaUserDisplay
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

  public function formSearch($seach)
  {
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
  <?php
  }

  public function formOrderAndPaginator($order, $per_page)
  {
  ?>
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
  <?php
  }

  public function formPaginator( $per_page )
  {?>
    <form method="get">
      <label for="per-page"><?php echo __('Usuários por Página:' , 'delibera'); ?></label>
      <select id="per-page" name="per-page"  onchange='if(this.value != 0) { this.form.submit(); }' >
        <option value="5" <?php echo $per_page=='5' ? 'selected' : '' ;?> >5</option>
        <option value="10" <?php echo $per_page=='10' ? 'selected' : '' ;?> >10</option>
        <option value="20" <?php echo $per_page=='20' ? 'selected' : '' ;?> >20</option>
      </select>
    </form>
  <?php
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

  public function displayPaginator( $number_of_pages , $paged ){
   ?> 
    <div id="user_pager" class="user_pager">
      <p>
        <?php echo $this->getPaginator( $number_of_pages, $paged ); ?>
      </p>
    </div>
   <?php
  }

  public function avatarAndName($user)
  {?>
    <h1>
       <a id="user_name" class="user_name" href="<?php echo get_site_url()?>/delibera/membro/<?php echo $user->user_login; ?>">
         <p><?php echo get_avatar( $user->ID );         ?></p>
         <p><?php echo esc_html( $user->display_name ); ?> - <?php echo get_usernumposts($user->ID);?> posts</p>
       </a>
    </h1>
   <?php
   }
   protected function getLastPauta($user)
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
   public function displayLastPauta($user)
   { 
     $last_content = $this->getLastPauta($user);
   ?> 
    <div id="user_post" class="user_post" >
      <div id="user_post_title" class="user_post_title" ><h2><a href="<?php echo $last_content->guid; ?>" ><?php echo  $last_content->post_title; ?></a></h2></div>
      <div id="user_post_content" class="user_post_content"><p><?php echo $last_content->post_content;?></div>
    </div>
   <?php
   }
}

global $delibera_user_display;
$delibera_user_display = new DeliberaUserDisplay();
