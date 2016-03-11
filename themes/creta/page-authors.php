<?php
/*
Template Name: Authors Page
*/
get_header();
$per_page = isset( $_GET['per-page'] ) ?  esc_html( $_GET['per-page'] ) : '20' ;
$search = isset( $_GET['search'] ) ?  esc_html( $_GET['search'] ) : '' ;
$order  = isset( $_GET['order-by'] ) ?  esc_html( $_GET['order-by'] ) : '' ;
$order_by = \Delibera\ThemeTags\UserDisplay::getOrderBy($order);
$paged = get_query_var( 'paged' );
$blogusers = \Delibera\ThemeTags\UserDisplay::getUsers( $order_by , $search , $per_page , $paged );          
$number_of_pages = \Delibera\ThemeTags\UserDisplay::getNumberOfPages( $blogusers->get_total() , $per_page );

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
        <?php echo \Delibera\ThemeTags\UserDisplay::getPaginator( $number_of_pages, $paged ); ?>
      </p>
    </div>
 <div id="user_list" class="user_list">
   <?php
     if ($blogusers)
     {
       foreach ( $blogusers->results as $user )
       {
        ?>
      <h1>
         <p>
           <?php echo get_avatar( $user->ID ); ?>
         </p>
         <p>
           <?php echo esc_html( $user->display_name ); ?>
            - 
           <?php echo get_usernumposts($user->ID); ?>
            posts
         </p>
       <a id="link_pautas" class="link_pautas" href="<?php echo get_site_url()?>/blog/delibera/membro/<?php echo $user->user_login; ?>/pautas">
         <?php echo __('Todas as Pautas' , 'delibera'); ?>
       </a>
       |
       <a id="link_comments" class="link_comments" href="<?php echo get_site_url()?>/blog/delibera/membro/<?php echo $user->user_login; ?>/comentarios">
         <?php echo __('Todas os Comentários' , 'delibera'); ?>
       </a>
    </h1>

   <?php  $last_content = \Delibera\ThemeTags\UserDisplay::getLastPauta($user);  ?> 

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
   echo \Delibera\ThemeTags\UserDisplay::getPaginator( $number_of_pages , $paged );
   wp_reset_post_data();
   ?>
   <div>
   <?php

get_footer();
?>
</body>
</html>
