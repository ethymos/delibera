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
<a title="Compartilhe no facebook!" href="https://www.facebook.com/sharer/sharer.php?u='.$link.'" rel="nofollow" target="_blank"><i class="delibera-icon-facebook"></i></a>
<a title="Envie um tweet!" href="https://twitter.com/home?status='.$chamada.'%20'.$link.'" rel="nofollow" target="_blank"><i class="delibera-icon-twitter"></i></a>
</p>';
}

?>
