<?php get_header(); ?>

<div id="delibera">
    <div id="container">
    	<div id="content" role="main">
    		<?php
    		
    		get_delibera_header();
    		load_template(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'loop-pauta.php', true);
    		
    		?>
    	</div><!-- #content -->
    </div><!-- #container -->
</div>

<?php get_footer(); ?>
