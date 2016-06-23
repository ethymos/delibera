<?php if ( have_posts() ) while ( have_posts() ) : the_post();
$status_pauta = delibera_get_situacao($post->ID)->slug;
//echo $status_pauta;
global $DeliberaFlow;
$flow = $DeliberaFlow->get(get_the_ID());
?>
<div class="pauta-content <?php echo $status_pauta; ?>">

	<div class="banner-ciclo status-ciclo">
		<h3 class="title">Estágio da pauta</h3>
		<ul class="ciclos"><?php
		$i = 1;
		foreach ($flow as $situacao)
		{
			switch($situacao)
			{
				case 'validacao':?>
				<li class="validacao <?php echo ($status_pauta != "validacao" ? "inactive" : ""); ?>"><?php echo $i; ?><br>Validação</li><?php
				break;
				case 'discussao': ?>
				<li class="discussao <?php echo ($status_pauta != "discussao" ? "inactive" : ""); ?>"><?php echo $i; ?><br>Discussão</li><?php
				break;
				case 'relatoria':
				case 'eleicao_relator': ?>
				<li class="relatoria <?php echo ($status_pauta != "relatoria" ? "inactive" : ""); ?>"><?php echo $i; ?><br>Relatoria</li><?php
				break;
				case 'emvotacao': ?>
				<li class="emvotacao <?php echo ($status_pauta != "emvotacao" ? "inactive" : ""); ?>"><?php echo $i; ?><br>Votação</li><?php
				break;
				case 'naovalidada':
				case 'comresolucao': ?>
				<li class="comresolucao <?php echo ($status_pauta != "comresolucao" && $status_pauta != "naovalidada" ? "inactive" : ""); ?>"><?php echo $i; ?><br>Resolução</li><?php
				break;
			}
			$i++;
		}?>
	</ul>
</div>



<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div id="leader">
		<h1 class="entry-title"><?php the_title(); ?></h1>
		<div class="entry-prazo">

			<?php
			if ( \Delibera\Flow::getDeadlineDays( $post->ID ) == -1 )
			_e( 'Prazo encerrado', 'delibera' );
			else
			printf( _n( 'Por mais <br><span class="numero">1</span> dia', 'Por mais <br><span class="numero">%1$s</span> dias', \Delibera\Flow::getDeadlineDays( $post->ID ), 'delibera' ), number_format_i18n( \Delibera\Flow::getDeadlineDays( $post->ID ) ) );
			?>
		</div><!-- .entry-prazo -->
		<!--div class="entry-meta">
		<div class="entry-situacao">
		<?php printf( __( 'Situação da pauta', 'delibera' ).': %s', delibera_get_situacao($post->ID)->name );?>
	</div .entry-situacao -->
	<div class="entry-author">
		<?php _e( 'Por', 'delibera' ); ?>
		<span class="author vcard">
			<a class="url fn n" href="<?php echo get_site_url().'/delibera/' . get_the_author_meta( 'ID' ) . '/pautas' ; ?>" title="<?php printf( __( 'Ver o perfil de %s', 'delibera' ), get_the_author() ); ?>">
				<?php the_author(); ?>
			</a>
		</span>
	</div><!-- .entry-author -->
	<div class="entry-seguir button">
		<?php echo delibera_gerar_seguir($post->ID); ?>
	</div>
	<div class="entry-print button">
		<a title="Versão simplificada para impressão" href="?delibera_print=1" class=""><i class="delibera-icon-print"></i></a>
	</div><!-- .entry-print -->

	<div class="entry-attachment">
	</div><!-- .entry-attachment -->
	
	</div><!-- #leader -->

	<div class="entry-content">
		<?php the_content(); ?>
	</div><!-- .entry-content -->

	<?php
	echo '<div id="delibera-comment-botoes-'.$comment->comment_ID.'" class="delibera-comment-botoes">';
	echo '<div class="group-button-like">
	<!--span class="label">O que achou?</span-->';
	echo delibera_gerar_curtir($post->ID, 'pauta');
	echo delibera_gerar_discordar($post->ID, 'pauta');
	echo '</div>';
	social_buttons(get_permalink(), get_the_title());
	?>
</div>
</div><!-- #post-## -->
<h2 class="discussion-title">Discussão sobre a Pauta</h2>
	<?php comments_template( '', true ); ?>
</div>
<?php endwhile; // end of the loop. ?>
