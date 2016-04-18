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
echo '
<ul class="social-buttons cf">
    <li>
        <a href="http://twitter.com/share" class="socialite twitter-share" data-text="'.$chamada.'" data-url="'.$link.'" data-count="horizontal" rel="nofollow" target="_blank"><span class="vhidden">Compartilhe no Twitter</span></a>
    </li>
    <li>
        <a href="https://plus.google.com/share?url='.$link.'" class="socialite googleplus-one" data-annotation="bubble" data-heigth="24" data-href="'.$link.'" rel="nofollow" target="_blank"><span class="vhidden">Compartilhe no Google+</span></a>
    </li>
    <li>
        <a href="http://www.facebook.com/sharer.php?u='.$link.'" class="socialite facebook-like" data-href="'.$link.'" data-send="false" data-layout="button" data-width="60" data-show-faces="false" rel="nofollow" target="_blank"><span class="vhidden">Compartilhe no Facebook</span></a>
    </li>
</ul>
';
}

?>