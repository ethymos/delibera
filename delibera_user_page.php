<?php

  get_header();
  get_the_content();
  
  $user =  $wp_query->query_vars['member'];
  $user = get_user_by( 'login' , $user ); ?>
  <p>
    <div>
      <?php echo get_avatar( $user->ID ); ?>
    </div>
    <div>
      <?php echo 'Nesta página você pode encontrar tudo o que o usuário ' . $user->first_name  . ' produziu de pautas e comentários no Delibera'; ?>
    </div>
    <br>
    <?php //var_dump($user); ?>
    <div>
      <?php echo 'Conteudos do tipo Pauta:'; ?>
    </div>
  </p>
  <main id="main" class="site-main" role="main">
  <?php
  $args = array(
      'author' => $user->ID,
      'status' => 'approve',
      'posts_per_page' => 5,
      'post_type' => 'pauta',
      'paged' => get_query_var( 'paged' ) 
  );
  $author_posts = new WP_Query( $args );

  foreach( $author_posts->posts as $post )
  {
    echo '<h2 class="entry-title"><a href="' . get_permalink($post) . '">' . $post->post_title . '</a></h2>';
    echo '<div class="entry-content">' . $post->post_content . '</div>';
    echo '<br>';
  }
  echo '<div>';
  echo paginate_links( array(
    'base' => add_query_arg( 'paged', '%#%' ),
    'format' => '',
    'prev_text' => __('&laquo;'),
    'next_text' => __('&raquo;'),
    'total' =>  $author_posts->max_num_pages,
    'current' => get_query_var( 'paged' )
  ));
  //wp_reset_post_data();
  echo '</div>';
  ?>

  <?php
    $args = array(
        'user_id' => $user->ID,
        'number' => 1, // how many comments to retrieve
        'post_type' => 'pauta',
        'status' => 'approve'
        );

    $comments = get_comments( $args );

    if ( $comments )
    {
        $output.= '<article class="post hentry">';
        $output.= '<header class="entry-header"><br><h2 class="entry-title"> Comentários de ' . $user->first_name . '</h2></header>';
        $output.= '<div class="entry-content">';
        $output.= "<ul>\n";
        foreach ( $comments as $comment )
        {
        $output.= '<li>';
        $output.= $comment->comment_content . '&nbsp';
        $output.= '<a href="'.get_comment_link( $comment->comment_ID ).'">';
        $output.= get_the_title($comment->comment_post_ID);
        $output.= '</a>, '. mysql2date('m/d/Y', $comment->comment_date, $translate);
        $output.= "</li>\n";
        }
        $output.= '</ul>';
        $output.= '</div>';

        $output.= '</article>';
        echo $output;
    } else { echo "No comments made";}

  ?>

  </main>
  <?php wp_footer();?>
</body>
</html> 

