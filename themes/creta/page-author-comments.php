<?php
/*
Template Name: Author Page Comments
*/

get_header();
$login = $wp_query->query_vars['commentsfor'];
$user = get_user_by( 'login' , $login ); 

$per_page = isset( $_GET['per-page'] ) ?  esc_html( $_GET['per-page'] ) : '20' ;
$search = isset( $_GET['search'] ) ?  esc_html( $_GET['search'] ) : '' ;
$order  = isset( $_GET['order-by'] ) ?  esc_html( $_GET['order-by'] ) : '' ;
$paged = get_query_var( 'paged' );

    ?>
    <div id="user_form_search" class="user_form_search">
      <form method="get"  name="form">
        <p>
          <input type="text" name="search" placeholder="Pesquisar por Comentários ..." value="<?php echo $search; ?>"/>
          <br>
          <br>
          <input type="submit" id="submit" class="button button-primary" value="Pesquisar"  />
        </p>
      </form>
    </div>
    <form method="get">
      <label for="per-page"><?php echo __('Comentários por Página:' , 'delibera'); ?></label>
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
        <h2><?php echo 'Comentários:'; ?></h2>
      </div>
    </p>
  
    <?php $comments = \Delibera\ThemeTags\UserDisplay::getUserComments($user, $search, $per_page, $paged); ?>
      <div id="user_pager" class="user_pager">
        <p>
          <?php echo \Delibera\ThemeTags\UserDisplay::getPaginator( \Delibera\ThemeTags\UserDisplay::getNumberOfPages($comments[0]->comment_count,$per_page) , $paged ); ?>
        </p>
      </div>
      <?php
      if ( $comments )
      {
          ?>
          <h2>Comentários: </h2>
          <div class="user_comment">
          <?php foreach ( $comments as $comment ) { ?>
            <strong>
              <?php echo \Delibera\ThemeTags\UserDisplay::parse_comment_type( $comment->comment_ID , 'tipo'); ?>
            </strong>
            <br>
            <?php echo $comment->comment_content;?>
            <br>
            <?php echo wp_get_attachment_image(get_comment_meta( $comment->comment_ID , 'attachmentId', true)); ?>
            <br>
            <p>Na pauta 
            <a href="<?php echo get_comment_link( $comment->comment_ID )?>">
              <?php echo get_the_title($comment->comment_post_ID); ?>, em 
            </a>
            <?php echo mysql2date('m/d/Y', $comment->comment_date, $translate); ?>
            <?php if( \Delibera\ThemeTags\UserDisplay::get_comment_meta( $comment->comment_ID , 'tipo') === 'validacao')
                {
              ?>
              <br><br>
              Numero de votos:
            <?php 
               $votaram = \Delibera\ThemeTags\UserDisplay::get_comment_meta( $comment->comment_ID , 'numero_votos');
               echo $votaram ? $votaram : '0';
            ?>
               <br>
            <?php
                  }
            ?>
            <p>Concordaram:
            <?php 
              $curtiram = \Delibera\ThemeTags\UserDisplay::get_comment_meta( $comment->comment_ID , 'numero_curtir' );
              echo $curtiram ? $curtiram : '0';
             ?>
            <br>Discordaram:
            <?php
              $discordaram =  \Delibera\ThemeTags\UserDisplay::get_comment_meta( $comment->comment_ID , 'numero_discordar' );
              echo $discordaram ? $discordaram : '0';
            ?>
             </p>
             <?php
          }
          ?>
          </div>
          <?php
      } else { echo __('Nenhum Comentário encontrado!' , 'delibera'); }
   ?>
      <div id="user_pager" class="user_pager">
        <p>
          <?php echo \Delibera\ThemeTags\UserDisplay::getPaginator( 
          \Delibera\ThemeTags\UserDisplay::getNumberOfPages($comments[0]->comment_count,$per_page) 
          , $paged); 
          ?>
        </p>
      </div>
      <?php
wp_reset_post_data();
wp_footer();
?>
</body>
</html>
