<?php
/*
Template Name: Author Page
*/

get_header();
$login = $wp_query->query_vars['pautasfor'];
$user = get_user_by( 'login' , $login ); 

$per_page = isset( $_GET['per-page'] ) ?  esc_html( $_GET['per-page'] ) : '20' ;
$search = isset( $_GET['search'] ) ?  esc_html( $_GET['search'] ) : '' ;
$order  = isset( $_GET['order-by'] ) ?  esc_html( $_GET['order-by'] ) : '' ;

  global $user_display;
    ?>
    <div id="user_form_search" class="user_form_search">
      <form method="get"  name="form">
        <p>
          <input type="text" name="search" placeholder="Pesquisar por Pautas ..." value="<?php echo $search; ?>"/>
          <br>
          <br>
          <input type="submit" id="submit" class="button button-primary" value="Pesquisar"  />
        </p>
      </form>
    </div>
    <form method="get">
      <label for="per-page"><?php echo __('Pautas por Página:' , 'delibera'); ?></label>
      <select id="per-page" name="per-page"  onchange='if(this.value != 0) { this.form.submit(); }' >
        <option value="5" <?php echo $per_page=='5' ? 'selected' : '' ;?> >5</option>
        <option value="10" <?php echo $per_page=='10' ? 'selected' : '' ;?> >10</option>
        <option value="20" <?php echo $per_page=='20' ? 'selected' : '' ;?> >20</option>
      </select>
    </form>
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
        'author'          => $user->ID,
        'status'          => 'approve',
        's'               => $search,
        'posts_per_page'  => $per_page,
        'post_type'       => 'pauta',
        'paged'           => get_query_var( 'paged' ) 
    );

    $author_posts = new WP_Query( $args );
    ?>
  <div id="user_pager" class="user_pager">
      <p>
        <?php echo \Delibera\Theme\UserDisplay::getPaginator( $author_posts->max_num_pages, $paged ); ?>
      </p>
    </div>
    <?php
    foreach( $author_posts->posts as $post )
    {
    	?>
        <p>
          <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
            <?php the_title('<h3>', '</h3>'); ?>
          </a>
        </p>
        <?php
        the_excerpt();
    }

?>
    <div id="user_pager" class="user_pager">
      <p>
        <?php echo \Delibera\Theme\UserDisplay::getPaginator( $author_posts->max_num_pages , $per_page ); ?>
      </p>
    </div>
<?php
wp_footer();
?>
</body>
</html>
