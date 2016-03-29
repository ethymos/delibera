<?php get_header(); ?>
		<div id="container">
			<div id="main-content" role="main">

				<?php
				// Chama o cabeçalho que apresenta o sistema de discussão
				get_delibera_header();

				//delibera_filtros_gerar();



				//$args = get_tax_filtro($_REQUEST, array('post_type' => 'pauta'));


				?>

				<div class="filter-bar">
					<div class="title">
						<h2><?php echo _e('Todas as discussões', 'delibera'); ?></h2>
					</div>
					<div class="actions">
						<a href="#" class="button">filtros</a>
						<a href="#" class="button">nova pauta</a>
					</div>
				</div>

				<div class="lista-de-pautas">
					<?php
					// Chama o loop do arquivo
					//wp_reset_query();

					//echo count(query_posts($args));

					load_template(dirname(__FILE__).DIRECTORY_SEPARATOR.'delibera-loop-archive.php', true);

					?>

					<div class="banner-ciclo">
						<h3 class="title">Ciclo de vida de uma pauta</h3>
						<p class="description">
							Entenda como funciona o ciclo de pautas dentro do Delibera, <br>abaixo os possíveis ciclos.
						</p>
						<ul class="ciclos">
							<li class="validacao">1<br>Validação</li>
							<li class="discussao">2<br>Discussão</li>
							<li class="relatoria">3<br>Relatoria</li>
							<li class="emvotacao">4<br>Votação</li>
							<li class="comresolucao">5<br>Resolução</li>
						</ul>
					</div>


					<div id="nav-below" class="navigation">
						<?php if ( function_exists( 'wp_pagenavi' ) )
						{

							wp_pagenavi(array('query' => $wp_query));
						}
						?>
					</div><!-- #nav-below -->

				</div>


			</div><!-- #content -->
		</div><!-- #container -->

<?php
	get_footer();
?>
