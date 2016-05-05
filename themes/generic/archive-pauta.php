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
						<!--a href="#" class="button">filtros</a-->
						<a href="/wp-admin/post-new.php?post_type=pauta" class="button">nova pauta</a>
					</div>
				</div>

				<div class="lista-de-pautas">
					<?php
					// Chama o loop do arquivo
					//wp_reset_query();

					//echo count(query_posts($args));

					global $deliberaThemes;
					$deliberaThemes->archiveLoop();

					$options_plugin_delibera = delibera_get_config();
					$default_flow = isset($options_plugin_delibera['delibera_flow']) ? $options_plugin_delibera['delibera_flow'] : array();
					$default_flow = apply_filters('delibera_flow_list', $default_flow);
					?>

					<div class="banner-ciclo">
						<h3 class="title">Ciclo de vida de uma pauta</h3>
						<p class="description">
							Entenda como funciona o ciclo de pautas dentro do Delibera, <br>abaixo os possíveis ciclos.
						</p>
						<ul class="ciclos"><?php
							$i = 1;
							foreach ($default_flow as $situacao)
							{
								switch($situacao)
								{
									case 'validacao':?>
										<li class="validacao"><?php echo $i; ?><br>Validação</li><?php
									break;
									case 'discussao': ?>
										<li class="discussao"><?php echo $i; ?><br>Discussão</li><?php
									break;
									case 'relatoria':
									case 'eleicao_relator': ?>
										<li class="relatoria"><?php echo $i; ?><br>Relatoria</li><?php
									break;
									case 'emvotacao': ?>
										<li class="emvotacao"><?php echo $i; ?><br>Votação</li><?php
									break;
									case 'naovalidada':
									case 'comresolucao': ?>
										<li class="comresolucao"><?php echo $i; ?><br>Resolução</li><?php
									break;
								}
								$i++;
							}?>
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
