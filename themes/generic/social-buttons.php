<?php
/**
*
*
*/

/**
*
*
*/

function social_buttons($link='', $chamada='' ) {
  echo '<p class="social-buttons"> Compartilhe:
<a href="https://twitter.com/home?status='.$chamada.'%20'.$link.'" rel="nofollow" target="_blank"><i class="delibera-icon-twitter"></i></a>
<a href="https://www.facebook.com/sharer/sharer.php?u='.$link.'" rel="nofollow" target="_blank"><i class="delibera-icon-facebook"></i></a>
</p>';
}

?>
