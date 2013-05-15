<?php
/*
Plugin Name: Delibera
Plugin URI: http://www.ethymos.com.br
Description: O Plugin Delibera extende as funções padrão do WordPress e cria um ambiente de deliberação.
Version: 0.2
Author: Ethymos
Author URI: http://www.ethymos.com.br

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

// Defines



if(!defined('__DIR__')) {
    $iPos = strrpos(__FILE__, DIRECTORY_SEPARATOR);
    define("__DIR__", substr(__FILE__, 0, $iPos) . DIRECTORY_SEPARATOR);
}

define('DELIBERA_ABOUT_PAGE', __('sobre-a-plataforma', 'delibera'));

// End Defines

// Parse shorttag

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_shortcodes.php';

// End Parse shorttag

// Parse widgets

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_widgets.php';

// End Parse widgets

// Inicialização do plugin

require_once __DIR__.'/print/wp-print.php';

function delibera_init()
{
	add_action('admin_menu', 'delibera_config_menu');
	
	delibera_Add_custom_Post();
	
	delibera_Add_custom_taxonomy();
	
	global $delibera_comments_padrao;
	$delibera_comments_padrao = false;
	
}
add_action('init','delibera_init');

/** 
 * 	Para Multisites
 */

function delibera_wpmu_new_blog($blog_id, $user_id = 0, $domain = '', $path = '', $site_id = '', $meta = '' )
{
	/** Antes de mudar **/
	$permalink_structure = get_option('permalink_structure');
	$qtrans = array();
	if(function_exists('qtrans_enableLanguage'))
	{
		$qtrans['enabled_languages'] = get_option('qtranslate_enabled_languages');
		$qtrans['default_language'] = get_option('qtranslate_default_language');
	}
	
	switch_to_blog($blog_id);
		/** Depois de mudar de blog **/
		
		if(function_exists('qtrans_enableLanguage'))
		{
			update_option('qtranslate_enabled_languages', $qtrans['enabled_languages']); 
			update_option('qtranslate_default_language', $qtrans['default_language']);
		}
		update_option('permalink_structure', $permalink_structure);
		flush_rewrite_rules();
	restore_current_blog();
}

add_action('wpmu_new_blog','delibera_wpmu_new_blog',90,6);

/**
	 * 
	 * Revemos acentos do texto
	 * @param string $texto
	 * @return string
	 */
function delibera_tiracento($texto)
{
	$trocarIsso = 	array('à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ü','ú','ÿ','À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','Ù','Ü','Ú','Ÿ',);
	$porIsso = 		array('a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','y','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','Y',);
	$titletext = str_replace($trocarIsso, $porIsso, $texto);
	return $titletext;
}

function delibera_slug_under($label)
{
	$slug = delibera_tiracento($label);
	$slug = str_replace(array("'",'"','.',',',';','!'), '', $slug);
	$slug = str_replace(array(' ', '-'), '_', $slug);
	return strtolower($slug);
}

function is_pauta($post = false)
{
	return get_post_type($post) == 'pauta' ? true : false;
} 

/**
 * 
 * Insere term no banco e atualizar línguas do qtranslate
 * @param string $label
 * @param string $tax Taxonomy
 * @param array $term EX: array('description'=> __('Español'),'slug' => 'espanol', 'slug' => 'espanol')
 * @param array $idiomas EX: array('qtrans_term_en' => 'United States of America', 'qtrans_term_pt' => 'Estados Unidos da América', 'qtrans_term_es' => 'Estados Unidos de América'
 */
function delibera_insert_term($label, $tax, $term, $idiomas = array())
{
	if(term_exists($term['slug'], $tax, null) == false)
	{
		wp_insert_term($label, $tax, $term);
		global $q_config;
		if(count($idiomas) > 0 && function_exists('qtrans_stripSlashesIfNecessary'))
		{
			if(isset($idiomas['qtrans_term_'.$q_config['default_language']]) && $idiomas['qtrans_term_'.$q_config['default_language']]!='')
			{
				$default = htmlspecialchars(qtrans_stripSlashesIfNecessary($idiomas['qtrans_term_'.$q_config['default_language']]), ENT_NOQUOTES);
				if(!isset($q_config['term_name'][$default]) || !is_array($q_config['term_name'][$default])) $q_config['term_name'][$default] = array();
				foreach($q_config['enabled_languages'] as $lang) {
					$idiomas['qtrans_term_'.$lang] = qtrans_stripSlashesIfNecessary($idiomas['qtrans_term_'.$lang]);
					if($idiomas['qtrans_term_'.$lang]!='') {
						$q_config['term_name'][$default][$lang] = htmlspecialchars($idiomas['qtrans_term_'.$lang], ENT_NOQUOTES);
					} else {
						$q_config['term_name'][$default][$lang] = $default;
					}
				}
				update_option('qtranslate_term_name',$q_config['term_name']);
			}
		}
	}
}

function delibera_Add_custom_Post()
{
	$labels = array
	(
		'name' => __('Pautas','delibera'),
	    'singular_name' => __('Pauta','delibera'),
	    'add_new' => __('Adicionar Nova','delibera'),
	    'add_new_item' => __('Adicionar nova pauta ','delibera'),
	    'edit_item' => __('Editar Pauta','delibera'),
	    'new_item' => __('Nova Pauta','delibera'),
	    'view_item' => __('Visualizar Pauta','delibera'),
	    'search_items' => __('Procurar Pautas','delibera'),
	    'not_found' =>  __('Nenhuma Pauta localizada','delibera'),
	    'not_found_in_trash' => __('Nenhuma Pauta localizada na lixeira','delibera'), 
	    'parent_item_colon' => '',
	    'menu_name' => __('Pautas','delibera')
	
	);
		
	$args = array
	(
		'label' => __('Pautas','delibera'),
		'labels' => $labels,
		'description' => __('Pauta de discussão','delibera'),
		'public' => true,
		'publicly_queryable' => true, // public
		//'exclude_from_search' => '', // public
		'show_ui' => true, // public
		'show_in_menu' => true,
		'menu_position' => 5,
		// 'menu_icon' => '', 
		'capability_type' => array('pauta','pautas'),		
		'map_meta_cap' => true,
		'hierarchical' => false,
		'supports' => array('title', 'editor', 'author', 'excerpt', 'trackbacks', 'revisions', 'comments'),
		'register_meta_box_cb' => 'delibera_pauta_custom_meta', // função para chamar na edição
		//'taxonomies' => array('post_tag'), // Taxionomias já existentes relaciondas, vamos criar e registrar na sequência
		'permalink_epmask' => 'EP_PERMALINK ',
		'has_archive' => true, // Opção de arquivamento por slug
		'rewrite' => true,
		'query_var' => true,
		'can_export' => true//, // veja abaixo
		//'show_in_nav_menus' => '', // public
		//'_builtin' => '', // Core 
		//'_edit_link' => '' // Core
	
	);
	
	register_post_type("pauta", $args);
	
	$tags_tax = get_taxonomy('post_tag');
	
	$pautas_cap = array('assign_terms' => 'edit_pautas',
		  			'edit_terms' => 'edit_pautas');
	$args_tax = array
	(
		'public' => true,
		'capabilities' => $pautas_cap
	);
	
	register_taxonomy('post_tag', array('pauta','post'), $args_tax);
}

function delibera_pauta_redirect_filter($location, $post_id = null) {

	if (strpos($_SERVER['HTTP_REFERER'], "post_type=pauta"))
		return admin_url("edit.php")."?post_type=pauta&updated=1";
	else 
		return $location;
}
add_filter('redirect_post_location', 'delibera_pauta_redirect_filter', '99');

function delibera_Add_custom_taxonomy()
{
	$labels = array
	(
		'name' => __('Temas', 'delibera'),
	    'singular_name' => __('Tema', 'delibera'),
		'search_items' => __('Procurar por Temas','delibera'),
		'all_items' => __('Todos os Temas','delibera'),
		'parent_item' => __( 'Tema Pai','delibera'),
		'parent_item_colon' => __( 'Tema Pai:','delibera'),
		'edit_item' => __('Editar Tema','delibera'),
		'update_item' => __('Atualizar um Tema','delibera'),
		'add_new_item' => __('Adicionar Novo Tema','delibera'),
	    'add_new' => __('Adicionar Novo','delibera'),
	    'new_item_name' => __('Novo Tema','delibera'),
	    'view_item' => __('Visualizar Tema','delibera'),
	    'not_found' =>  __('Nenhum Tema localizado','delibera'),
	    'not_found_in_trash' => __('Nenhum Tema localizado na lixeira','delibera'), 
	    'menu_name' => __('Temas','delibera')
	);
	
	$args = array
	(
		'label' => __('Temas','delibera'),
		'labels' => $labels,
		'public' => true,
		'capabilities' => array('assign_terms' => 'edit_pautas',
								'edit_terms' => 'edit_pautas'),
		//'show_in_nav_menus' => true, // Public
		// 'show_ui' => '', // Public
		'hierarchical' => true,
		//'update_count_callback' => '', //Contar objetos associados
		'rewrite' => true, 
		//'query_var' => '',
		//'_builtin' => '' // Core 
	);
	
	register_taxonomy('tema', array('pauta'), $args);
	
 
	
	$labels = array
	(
		'name' => __('Situações','delibera'),
	    'singular_name' => __('Situação', 'delibera'),
		'search_items' => __('Procurar por Situação','delibera'),
		'all_items' => __('Todas as Situações','delibera'),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __('Editar Situação','delibera'),
		'update_item' => __('Atualizar uma Situação','delibera'),
		'add_new_item' => __('Adicionar Nova Situação','delibera'),
	    'add_new' => __('Adicionar Nova', 'delibera'),
	    'new_item_name' => __('Nova Situação','delibera'),
	    'view_item' => __('Visualizar Situação','delibera'),
	    'not_found' =>  __('Nenhuma Situação localizado','delibera'),
	    'not_found_in_trash' => __('Nenhuma Situação localizada na lixeira','delibera'), 
	    'menu_name' => __('Situações','delibera')
	);
	
	$args = array
	(
		'label' => __('Situações','delibera'),
		'labels' => $labels,
		'public' => false,
		'show_in_nav_menus' => true, // Public
		//'show_ui' => true, // Public
		'hierarchical' => false//,
		//'update_count_callback' => '', //Contar objetos associados
		//'rewrite' => '', // 
		//'query_var' => '',
		//'_builtin' => '' // Core 
	);
	
	register_taxonomy('situacao', array('pauta'), $args);

	// Se precisar trocar os nomes dos terms denovo
	/*$term = get_term_by('slug', 'comresolucao', 'situacao');
	wp_update_term($term->term_id, 'situacao', array('name' => 'Resolução'));
	$term = get_term_by('slug', 'emvotacao', 'situacao');
	wp_update_term($term->term_id, 'situacao', array('name' => 'Regime de Votação'));
	$term = get_term_by('slug', 'discussao', 'situacao');
	wp_update_term($term->term_id, 'situacao', array('name' => 'Pauta em discussão'));
	$term = get_term_by('slug', 'validacao', 'situacao');
	wp_update_term($term->term_id, 'situacao', array('name' => 'Proposta de Pauta'));
	$term = get_term_by('slug', 'naovalidada', 'situacao');
	wp_update_term($term->term_id, 'situacao', array('name' => 'Pauta Recusada'));*/
	
	$opt = delibera_get_config();
	
	if(taxonomy_exists('situacao'))
	{
		if(term_exists('comresolucao', 'situacao', null) == false)
		{
			delibera_insert_term('Resolução', 'situacao', array(
					'description'=> 'Pauta com resoluções aprovadas',
					'slug' => 'comresolucao',
				),
				array(
					'qtrans_term_pt' => 'Resolução',
					'qtrans_term_en' => 'Resolution',
					'qtrans_term_es' => 'Resolución',
				)
			);
		}
		if(term_exists('emvotacao', 'situacao', null) == false)
		{
			delibera_insert_term('Regime de Votação', 'situacao', array(
					'description'=> 'Pauta com encaminhamentos em Votacao',
					'slug' => 'emvotacao',
				),
				array(
					'qtrans_term_pt' => 'Regime de Votação',
					'qtrans_term_en' => 'Voting',
					'qtrans_term_es' => 'Sistema de Votación',
				)
			);
		}
		if($opt['relatoria'] == 'S')
		{
			if($opt['eleicao_relator'] == 'S')
			{
				if(term_exists('eleicaoredator', 'situacao', null) == false)
				{
					delibera_insert_term('Regime de Votação de Relator', 'situacao', array(
							'description'=> 'Pauta em Eleição de Relator',
							'slug' => 'eleicaoredator',
						),
						array(
							'qtrans_term_pt' => 'Regime de Votação de Relator',
							'qtrans_term_en' => 'Election of Rapporteur',
							'qtrans_term_es' => 'Elección del Relator',
						)
					);
				}
			}
			
			if(term_exists('relatoria', 'situacao', null) == false)
			{
				delibera_insert_term('Relatoria', 'situacao', array(
						'description'=> 'Pauta com encaminhamentos em Relatoria',
						'slug' => 'relatoria',
					),
					array(
						'qtrans_term_pt' => 'Relatoria',
						'qtrans_term_en' => 'Rapporteur',
						'qtrans_term_es' => 'Relator',
					)
				);
				}
		}
		if(term_exists('discussao', 'situacao', null) == false)
		{
			delibera_insert_term('Pauta em discussão', 'situacao', array(
					'description'=> 'Pauta em Discussão',
					'slug' => 'discussao',
				),
				array(
					'qtrans_term_pt' => 'Pauta em discussão',
					'qtrans_term_en' => 'Agenda en discusión',
					'qtrans_term_es' => 'Topic under discussion',
				)
			);
		}
		if($opt['validacao'] == 'S')
		{
			if(term_exists('validacao', 'situacao', null) == false)
			{
				delibera_insert_term('Proposta de Pauta', 'situacao', array(
						'description'=> 'Pauta em Validação',
						'slug' => 'validacao',
					),
					array(
						'qtrans_term_pt' => 'Proposta de Pauta',
						'qtrans_term_en' => 'Proposed Topic',
						'qtrans_term_es' => 'Agenda Propuesta',
					)
				);
			}
			if(term_exists('naovalidada', 'situacao', null) == false)
			{
				delibera_insert_term('Pauta Recusada', 'situacao', array(
						'description'=> 'Pauta não Validação',
						'slug' => 'naovalidada',
					),
					array(
						'qtrans_term_pt' => 'Pauta Recusada',
						'qtrans_term_en' => 'Rejected Topic',
						'qtrans_term_es' => 'Agenda Rechazada',
					)
				);
			}
		}
	}
	
	if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'delibera_taxs.php'))
	{
		require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_taxs.php';
	}
	
}

if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'delibera_themes.php'))
{
	require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_themes.php';
}

if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'delibera_filtros.php'))
{
	require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_filtros.php';
}

function delibera_get_comment_type($comment)
{
	$comment_ID = $comment;
	if(is_object($comment_ID)) $comment_ID = $comment->comment_ID;
	return get_comment_meta($comment_ID, "delibera_comment_tipo", true);
}

function delibera_get_comment_type_label($comment, $tipo = false, $echo = true)
{
	if($tipo === false) $tipo = get_comment_meta($comment->comment_ID, "delibera_comment_tipo", true);
	switch ($tipo)
	{
		case 'validacao':
			if($echo) _e('Validação', 'delibera');
			return __('Validação', 'delibera');
		break;
		case 'encaminhamento':
			if($echo) _e('Proposta', 'delibera');
			return __('Proposta', 'delibera');
		break;
		case 'voto':
			if($echo) _e('Voto', 'delibera'); 
			return __('Voto', 'delibera');
		break;
		case 'resolucao':
			if($echo)  _e('Resolução', 'delibera');
			return __('Resolução', 'delibera');
		break;
		case 'discussao':
			if($echo) _e('Opinião', 'delibera');
			return __('Opinião', 'delibera');
		default:
		break;
	}
}

function delibera_get_comments_types()
{
	return array('validacao', 'discussao', 'encaminhamento', 'voto', 'resolucao');
}

function delibera_pauta_custom_meta()
{
	add_meta_box("pauta_meta", "Detalhes da Pauta", 'delibera_pauta_meta', 'pauta', 'side', 'default');
}

function delibera_forca_fim_prazo($postID)
{
	$situacao = delibera_get_situacao($postID);
	
    switch($situacao->slug)
    {
    	case 'discussao':
    		delibera_tratar_prazo_discussao(array(
				'post_ID' => $postID,
				'prazo_discussao' => date('d/m/Y')
			));
    	break;
    	case 'relatoria':
    		delibera_tratar_prazo_relatoria(array(
				'post_ID' => $postID,
				'prazo_relatoria' => date('d/m/Y')
			));
    	break;
    	case 'emvotacao':
    		delibera_computa_votos($postID);
    	break;
    }
    //delibera_notificar_situacao($postID);
}

function delibera_admin_list_options($actions, $post)
{
	if(get_post_type($post) == 'pauta' && $post->post_status == 'publish' )
	{
		if(current_user_can('forcar_prazo'))
		{
			$url = 'admin.php?action=delibera_forca_fim_prazo_action&amp;post='.$post->ID;
			$url = wp_nonce_url($url, 'delibera_forca_fim_prazo_action'.$post->ID);
			$actions['forcar_prazo'] = '<a href="'.$url.'" title="'.__('Forçar fim de prazo','delibera').'" >'.__('Forçar fim de prazo','delibera').'</a>';
			
			$url = 'admin.php?action=delibera_nao_validado_action&amp;post='.$post->ID;
			$url = wp_nonce_url($url, 'delibera_nao_validado_action'.$post->ID);
			$actions['nao_validado'] = '<a href="'.$url.'" title="'.__('Invalidar','delibera').'" >'.__('Invalidar','delibera').'</a>';
			
		}
		if(delibera_get_situacao($post->ID)->slug == 'naovalidada' && current_user_can('delibera_reabrir_pauta'))
		{
			$url = 'admin.php?action=delibera_reabrir_pauta_action&amp;post='.$post->ID;
			$url = wp_nonce_url($url, 'delibera_reabrir_pauta_action'.$post->ID);
			$actions['reabrir'] = '<a href="'.$url.'" title="'.__('Reabrir','delibera').'" >'.__('Reabrir','delibera').'</a>';
		}
		
	}
	
	//print_r(_get_cron_array());
	return $actions;
}

add_filter('post_row_actions','delibera_admin_list_options', 10, 2);

function delibera_forca_fim_prazo_action() 
{
	if(current_user_can('forcar_prazo') && check_admin_referer('delibera_forca_fim_prazo_action'.$_REQUEST['post'], '_wpnonce')) 
	{
		delibera_forca_fim_prazo($_REQUEST['post']);
		
		wp_redirect( admin_url( 'edit.php?post_type=pauta') );
	}
	else 
	{
		wp_die(__('Você não tem permissão para forçar um prazo','delibera'), __('Sem permissão','delibera'));
	}
}
add_action('admin_action_delibera_forca_fim_prazo_action', 'delibera_forca_fim_prazo_action');

function delibera_nao_validado_action() 
{
	if(current_user_can('forcar_prazo') && check_admin_referer('delibera_nao_validado_action'.$_REQUEST['post'], '_wpnonce')) 
	{
		delibera_marcar_naovalidada($_REQUEST['post']);
		
		wp_redirect( admin_url( 'edit.php?post_type=pauta') );
	}
	else 
	{
		wp_die(__('Você não tem permissão para invalidar uma pauta','delibera'), __('Sem permissão','delibera'));
	}
}
add_action('admin_action_delibera_nao_validado_action', 'delibera_nao_validado_action');

function delibera_reabrir_pauta_action() 
{
	if(current_user_can('delibera_reabrir_pauta') && check_admin_referer('delibera_reabrir_pauta_action'.$_REQUEST['post'], '_wpnonce')) 
	{
		delibera_reabrir_pauta($_REQUEST['post']);
		
		wp_redirect( admin_url( 'edit.php?post_type=pauta') );
	}
	else 
	{
		wp_die(__('Você não tem permissão para re-abrir discussão sobre uma pauta','delibera'), __('Sem permissão','delibera'));
	}
}
add_action('admin_action_delibera_reabrir_pauta_action', 'delibera_reabrir_pauta_action');

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_cron.php';

/**
 * 
 * Retorna a situação do post
 * @param int $postID
 * @return mixed validacao, discussao, elegerelator, relatoria, emvotacao, comresolucao, naovalidada ou false
 */
function delibera_get_situacao($postID)
{
	$situacao = get_the_terms($postID, 'situacao');
	$ret = false;
	if(is_array($situacao) && count($situacao)  > 0)
	{
		$ret = array_pop($situacao);
	}
	
	if(has_filter('delibera_get_situacao'))
	{
		return apply_filters('delibera_get_situacao', $ret);
	}
	
	return $ret;
}

function delibera_pauta_meta()
{
	global $post;
	$custom = get_post_custom($post->ID);
	$options_plugin_delibera = delibera_get_config();
	
	if(!is_array($custom)) $custom = array();
	$validacoes = array_key_exists("numero_validacoes", $custom) ?  $custom["numero_validacoes"][0] : 0;
	
	$min_validacoes = array_key_exists("min_validacoes", $custom) ?  $custom["min_validacoes"][0] : htmlentities($options_plugin_delibera['minimo_validacao']);
	
	$situacao = delibera_get_situacao($post->ID);
	
	$dias_validacao = intval(htmlentities($options_plugin_delibera['dias_validacao']));
	$dias_discussao = intval(htmlentities($options_plugin_delibera['dias_discussao']));
	$dias_relatoria = intval(htmlentities($options_plugin_delibera['dias_relatoria']));
	$dias_votacao_relator = intval(htmlentities($options_plugin_delibera['dias_votacao_relator']));
	
	if($options_plugin_delibera['validacao'] == "S") // Adiciona prazo de validação se for necessário
	{
		$dias_discussao += $dias_validacao;
	}
	
	$dias_votacao = $dias_discussao + intval(htmlentities($options_plugin_delibera['dias_votacao']));
	
	if($options_plugin_delibera['relatoria'] == "S") // Adiciona prazo de relatoria se for necessário
	{
		$dias_votacao += $dias_relatoria;
		$dias_relatoria += $dias_discussao;	
		if($options_plugin_delibera['eleicao_relator'] == "S") // Adiciona prazo de vatacao relator se for necessário
		{
			$dias_votacao += $dias_votacao_relator;
			$dias_relatoria += $dias_votacao_relator;
			$dias_votacao_relator += $dias_discussao;
		}
	}
	
	$now = strtotime(date('Y/m/d')." 11:59:59");
	
	$prazo_validacao_sugerido = strtotime("+$dias_validacao days", $now);
	$prazo_discussao_sugerido = strtotime("+$dias_discussao days", $now);
	$prazo_eleicao_relator_sugerido = strtotime("+$dias_votacao_relator days", $now);
	$prazo_relatoria_sugerido = strtotime("+$dias_relatoria days", $now);
	$prazo_votacao_sugerido = strtotime("+$dias_votacao days", $now);
	
	$prazo_validacao = date('d/m/Y', $prazo_validacao_sugerido);
	$prazo_discussao = date('d/m/Y', $prazo_discussao_sugerido);
	$prazo_eleicao_relator = date('d/m/Y', $prazo_eleicao_relator_sugerido);
	$prazo_relatoria = date('d/m/Y', $prazo_relatoria_sugerido); 
	$prazo_votacao = date('d/m/Y', $prazo_votacao_sugerido);
	
	if (
		$options_plugin_delibera['representante_define_prazos'] == "N" &&
		!($post->post_status == 'draft' ||
		$post->post_status == 'auto-draft' ||
		$post->post_status == 'pending')
	)
	{
		$disable_edicao = 'readonly="readonly"';
	} else {
	    $disable_edicao = '';
	}
	
	if(!($post->post_status == 'draft' ||
		$post->post_status == 'auto-draft' ||
		$post->post_status == 'pending'))
	{
		$prazo_validacao = array_key_exists("prazo_validacao", $custom) ?  $custom["prazo_validacao"][0] : $prazo_validacao;
		$prazo_discussao = array_key_exists("prazo_discussao", $custom) ?  $custom["prazo_discussao"][0] : $prazo_discussao;
		$prazo_eleicao_relator = array_key_exists("prazo_eleicao_relator", $custom) ?  $custom["prazo_eleicao_relator"][0] : $prazo_eleicao_relator;
		$prazo_relatoria = array_key_exists("prazo_relatoria", $custom) ?  $custom["prazo_relatoria"][0] : $prazo_relatoria; 
		$prazo_votacao = array_key_exists("prazo_votacao", $custom) ?  $custom["prazo_votacao"][0] : $prazo_votacao;
	}

	if($options_plugin_delibera['validacao'] == "S")
	{
	?>
		<p>	
			<label for="min_validacoes" class="label_min_validacoes"><?php _e('Mínimo de Validações','delibera'); ?>:</label>
			<input <?php echo $disable_edicao ?> id="min_validacoes" name="min_validacoes" class="min_validacoes widefat" value="<?php echo $min_validacoes; ?>"/>
		</p>
		<p>
			<label for="prazo_validacao" class="label_prazo_validacao"><?php _e('Prazo para Validação','delibera') ?>:</label>
			<input <?php echo $disable_edicao ?> id="prazo_validacao" name="prazo_validacao" class="prazo_validacao widefat hasDatepicker" value="<?php echo $prazo_validacao; ?>"/>
		</p>
	<?php
	} 
	?>
	<p>
		<label for="prazo_discussao" class="label_prazo_discussao"><?php _e('Prazo para Discussões','delibera') ?>:</label>
		<input <?php echo $disable_edicao ?> id="prazo_discussao" name="prazo_discussao" class="prazo_discussao widefat hasDatepicker" value="<?php echo $prazo_discussao; ?>"/>
	</p>
	<?php 
	if($options_plugin_delibera['relatoria'] == "S")
	{
		if($options_plugin_delibera['eleicao_relator'] == "S")
		{
		?>
			<p>
				<label for="prazo_eleicao_relator" class="label_prazo_eleicao_relator"><?php _e('Prazo para Eleição de Relator','delibera') ?>:</label>
				<input <?php echo $disable_edicao ?> id="prazo_eleicao_relator" name="prazo_eleicao_relator" class="prazo_eleicao_relator widefat hasDatepicker" value="<?php echo $prazo_eleicao_relator; ?>"/>
			</p>
		<?php
		}
	?>
		<p>
			<label for="prazo_relatoria" class="label_prazo_relatoria"><?php _e('Prazo para Relatoria','delibera') ?>:</label>
			<input <?php echo $disable_edicao ?> id="prazo_relatoria" name="prazo_relatoria" class="prazo_relatoria widefat hasDatepicker" value="<?php echo $prazo_relatoria; ?>"/>
		</p>
	<?php
	}
	?>
	<p>
		<label for="prazo_votacao" class="label_prazo_votacao"><?php _e('Prazo para Votações','delibera') ?>:</label>
		<input <?php echo $disable_edicao ?> id="prazo_votacao" name="prazo_votacao" class="prazo_votacao widefat hasDatepicker" value="<?php echo $prazo_votacao; ?>"/>
	</p>
	<?php
}

function delibera_tratar_data($data, $int = true, $full = true)
{
	$data = trim($data);
	if(strlen($data) < 8) return false;
	$data = substr($data, 6, 4).substr($data, 2, 4).substr($data, 0, 2);
	$data .= $full === true ? ' 23:59:59' : ''; 
	return strtotime($data);
}

/**
 * 
 * Faz agendamento das datas para seguir passos
 * 1) Excluir ao atingir data de validação se não foi validade
 * 2) Iniciar votação se tiver encaminhamento, ou novo prazo, caso contrário
 * 3) Fim da votação
 * @param $prazo_validacao
 * @param $prazo_discussao
 * @param $prazo_votacao
 */
function delibera_criar_agenda($postID, $prazo_validacao, $prazo_discussao, $prazo_votacao, $prazo_relatoria = false, $prazo_eleicao_relator = false)
{
	if($prazo_validacao !== false)
	{
		delibera_add_cron(
			delibera_tratar_data($prazo_validacao),
			'delibera_tratar_prazo_validacao',
			array(
				'post_ID' => $postID,
				'prazo_validacao' => $prazo_validacao
			)
		);
		delibera_add_cron(
			strtotime("-1 day", delibera_tratar_data($prazo_validacao)),
			'delibera_notificar_fim_prazo',
			array(
				'post_ID' => $postID,
				'prazo_validacao' => $prazo_validacao
			)
		);
	}
	
	if($prazo_discussao !== false)
	{
		delibera_add_cron(
			delibera_tratar_data($prazo_discussao),
			'delibera_tratar_prazo_discussao',
			array(
				'post_ID' => $postID,
				'prazo_discussao' => $prazo_discussao
			)
		);
		delibera_add_cron(
			strtotime("-1 day", delibera_tratar_data($prazo_discussao)),
			'delibera_notificar_fim_prazo',
			array(
				'post_ID' => $postID,
				'prazo_discussao' => $prazo_discussao
			)
		);
	}
	
	if($prazo_eleicao_relator != false)
	{
		delibera_add_cron(
			delibera_tratar_data($prazo_eleicao_relator),
			'delibera_tratar_prazo_eleicao_relator',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_eleicao_relator
			)
		);
		delibera_add_cron(
			strtotime("-1 day", delibera_tratar_data($prazo_eleicao_relator)),
			'delibera_notificar_fim_prazo',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_eleicao_relator
			)
		);
	}
	
	if($prazo_relatoria != false)
	{
		delibera_add_cron(
			delibera_tratar_data($prazo_relatoria),
			'delibera_tratar_prazo_relatoria',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_relatoria
			)
		);
		delibera_add_cron(
			strtotime("-1 day", delibera_tratar_data($prazo_relatoria)),
			'delibera_notificar_fim_prazo',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_relatoria
			)
		);
	}
	
	if($prazo_votacao != false)
	{
		delibera_add_cron(
			delibera_tratar_data($prazo_votacao),
			'delibera_tratar_prazo_votacao',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_votacao
			)
		);
		delibera_add_cron(
			strtotime("-1 day", delibera_tratar_data($prazo_votacao)),
			'delibera_notificar_fim_prazo',
			array(
				'post_ID' => $postID,
				'prazo_votacao' => $prazo_votacao
			)
		);
	}
}

function delibera_tratar_prazos($args)
{
	$situacao = delibera_get_situacao($args['post_ID']);
	switch ($situacao->slug)
	{
		case 'validacao':
			delibera_tratar_prazo_validacao($args);
		break;
		case 'discussao':
			delibera_tratar_prazo_discussao($args);
		break;
		case 'relatoria':
			delibera_tratar_prazo_relatoria($args);
		break;
		case 'emvotacao':
			delibera_tratar_prazo_votacao($args);
		break;
	}
}

add_action('delibera_tratar_prazos', 'delibera_tratar_prazos', 1, 1);

function delibera_tratar_prazo_validacao($args)
{
	$situacao = delibera_get_situacao($args['post_ID']);
	if($situacao->slug == 'validacao') 
	{
		delibera_marcar_naovalidada($args['post_ID']);
	}
}

function delibera_tratar_prazo_discussao($args)
{
	$situacao = delibera_get_situacao($args['post_ID']);
	if($situacao->slug == 'discussao') 
	{
		$post_id = $args['post_ID'];
		if(count(delibera_get_comments_encaminhamentos($post_id)) > 0)
		{
			$opts = delibera_get_config();
			if($opts['eleicao_relator'] == 'S')
			{
				wp_set_object_terms($post_id, 'eleicaoredator', 'situacao', false); //Mudar situação para Votação
				//delibera_notificar_situacao($post_id);
			}
			elseif($opts['relatoria'] == 'S')
			{
				wp_set_object_terms($post_id, 'relatoria', 'situacao', false); //Mudar situação para Votação
				//delibera_notificar_situacao($post_id);
			}
			else 
			{
				wp_set_object_terms($post_id, 'emvotacao', 'situacao', false); //Mudar situação para Votação
				//delibera_notificar_situacao($post_id); 
			}
			if(has_action('delibera_discussao_concluida'))
			{
				do_action('delibera_discussao_concluida', $post_id);
			}
		}
		else
		{
			delibera_novo_prazo($post_id);
		}
	}
}

function delibera_tratar_prazo_relatoria($args)
{
	$situacao = delibera_get_situacao($args['post_ID']);
	if($situacao->slug == 'relatoria') 
	{
		$post_id = $args['post_ID'];
		if(count(delibera_get_comments_encaminhamentos($post_id)) > 0)
		{
			wp_set_object_terms($post_id, 'emvotacao', 'situacao', false); //Mudar situação para Votação
			//delibera_notificar_situacao($post_id);
			if(has_action('delibera_relatoria_concluida'))
			{
				do_action('delibera_relatoria_concluida', $post_id);
			}
		}
		else
		{
			delibera_novo_prazo($post_id);
		}
	}
}

function delibera_tratar_prazo_votacao($args)
{
	$situacao = delibera_get_situacao($args['post_ID']);
	if($situacao->slug == 'emvotacao') 
	{
		delibera_computa_votos($args['post_ID']);
	}
}

function delibera_marcar_naovalidada($postID)
{
	wp_set_object_terms($postID, 'naovalidada', 'situacao', false);
	if(has_action('delibera_pauta_recusada'))
	{
		do_action('delibera_pauta_recusada', $postID);
	}
}

function delibera_reabrir_pauta($postID)
{
	wp_set_object_terms($postID, 'validacao', 'situacao', false);
	//delibera_notificar_situacao($postID);
	
	delibera_novo_prazo($postID);
}

/**
 * 
 * Save o post da pauta
 * @param $post_id int
 * @param $post 
 */
function delibera_save_post($post_id, $post)
{
	if(get_post_type( ) != "pauta")
	{
		return $post_id;
	}
	$opt = delibera_get_config();
	$autosave = ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE );
	if(
		( // Se tem validação, tem que ter o prazo
			$opt['validacao'] == 'N' || 
			(array_key_exists('prazo_validacao', $_POST) && array_key_exists('min_validacoes', $_POST) )
		) &&
		( // Se tem relatoria, tem que ter o prazo
			$opt['relatoria'] == 'N' ||
			array_key_exists('prazo_relatoria', $_POST)
		) &&
		( // Se tem relatoria, e é preciso eleger o relator, tem que ter o prazo para eleição
			$opt['relatoria'] == 'N' ||
			(
				$opt['eleicao_relator'] == 'N' || 
				array_key_exists('prazo_eleicao_relator', $_POST)
			)
		) &&
		array_key_exists('prazo_discussao', $_POST) &&
		array_key_exists('prazo_votacao', $_POST)
	)
	{
		
		$events_meta = array();
	
		$validacoes = get_post_meta($post_id, 'numero_validacoes', true);
		if($validacoes == "" || $validacoes === false || is_null($validacoes))
		{
			$events_meta['numero_validacoes'] = 0;
			$events_meta['delibera_numero_comments_validacoes'] = 0;
			$events_meta['delibera_numero_comments_encaminhamentos'] = 0;
			$events_meta['delibera_numero_comments_discussoes'] = 0;
			$events_meta['delibera_numero_comments_votos'] = 0;
			$events_meta['delibera_numero_comments_padroes'] = 0;
			$events_meta['delibera_numero_curtir'] = 0;
			$events_meta['delibera_curtiram'] = array();
			$events_meta['delibera_numero_discordar'] = 0;
			$events_meta['delibera_discordaram'] = array();
			$events_meta['delibera_numero_seguir'] = 0;
			$events_meta['delibera_seguiram'] = array();
		}
	
		$events_meta['prazo_validacao'] = $opt['validacao'] == 'S' ? $_POST['prazo_validacao'] : date('d/m/Y');
		$events_meta['prazo_discussao'] = $_POST['prazo_discussao'];
		$events_meta['prazo_relatoria'] = $opt['relatoria'] == 'S' ? $_POST['prazo_relatoria'] : date('d/m/Y');
		$events_meta['prazo_eleicao_relator'] = $opt['relatoria'] == 'S' && $opt['eleicao_relator'] == 'S' ? $_POST['prazo_eleicao_relator'] : date('d/m/Y');
		$events_meta['prazo_votacao'] = $_POST['prazo_votacao'];
		$events_meta['min_validacoes'] = $opt['validacao'] == 'S' ? $_POST['min_validacoes'] : 10;
		
		
		foreach ($events_meta as $key => $value) // Buscar dados
		{
	        if(get_post_meta($post->ID, $key, true)) // Se já existe
	        { 
	            update_post_meta($post->ID, $key, $value); // Atualiza
	        }
	        else 
	        { 
	            add_post_meta($post->ID, $key, $value, true); // Se não cria
	        }
	    }
	    	    
	    if(
	    	array_key_exists('delibera_fim_prazo', $_POST) &&
	    	$_POST['delibera_fim_prazo'] == 'S' &&
	    	current_user_can('forcar_prazo')
	    )
	    {
	    	delibera_forca_fim_prazo($post->ID);
	    }
	    
	    if($post->post_status == 'publish' && !$autosave)
	    {
	    	delibera_del_cron($post->ID);
	    	delibera_publish_pauta($post->ID, $post, true);
	    }
	    
	}
	
	
}

add_action ('save_post', 'delibera_save_post', 1, 2);

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_curtir.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_discordar.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_seguir.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_edit_comment.php';

if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'mailer') && file_exists(__DIR__.DIRECTORY_SEPARATOR.'mailer'.DIRECTORY_SEPARATOR.'delibera_mailer.php'))
{
	//require_once __DIR__.DIRECTORY_SEPARATOR.'mailer'.DIRECTORY_SEPARATOR.'delibera_mailer.php';
}

function delibera_publish_pauta($postID, $post, $alterar = false)
{
	if(get_post_type( ) != "pauta")
	{
		return $postID;
	}
	if ($alterar || (($post->post_status == 'publish' || $_POST['publish'] == 'Publicar') && ($_POST['prev_status'] == 'draft' || $_POST['original_post_status'] == 'draft' || $_POST['original_post_status'] == 'auto-draft' || $_POST['prev_status'] == 'pending' || $_POST['original_post_status'] == 'pending' ) ))
	{
		$prazo_validacao = get_post_meta($postID, 'prazo_validacao', true);
		$prazo_discussao =  get_post_meta($postID, 'prazo_discussao', true);
		$prazo_relatoria =  get_post_meta($postID, 'prazo_relatoria', true);
		$prazo_eleicao_relator =  get_post_meta($postID, 'prazo_eleicao_relator', true);
		$prazo_votacao =  get_post_meta($postID, 'prazo_votacao', true);
		$opt = delibera_get_config();
		
		if(!array_key_exists('validacao', $opt) || $opt['validacao'] == 'S' )
		{
			if(!$alterar)
			{
				wp_set_object_terms($post->ID, 'validacao', 'situacao', false);
			}
	    	delibera_criar_agenda(
	    		$post->ID,
	    		$prazo_validacao,
	    		$prazo_discussao,
	    		$prazo_votacao,
	    		$opt['relatoria'] == 'S' ? $prazo_relatoria : false,
	    		$opt['relatoria'] == 'S' && $opt['eleicao_relator'] == 'S' ? $prazo_eleicao_relator : false
	    	);
		}
		else 
		{
			if(!$alterar)
			{
				wp_set_object_terms($post->ID, 'discussao', 'situacao', false);
			}
	    	delibera_criar_agenda(
	    		$post->ID,
	    		false,
	    		$prazo_discussao,
	    		$prazo_votacao,
	    		$opt['relatoria'] == 'S' ? $prazo_relatoria : false,
	    		$opt['relatoria'] == 'S' && $opt['eleicao_relator'] == 'S' ? $prazo_eleicao_relator : false
	    	);
		}
		
		if($alterar)
		{
			//delibera_notificar_situacao($post);
		}
		else 
		{
			delibera_notificar_nova_pauta($post);
		}
	}
}

add_action ('publish_pauta', 'delibera_publish_pauta', 1, 2);

function delibera_check_post_data($data, $postarr)
{
	$opt = delibera_get_config();
	$erros = array();
	$autosave = ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE );
	if(get_post_type() == 'pauta' && $_POST['action'] != 'trash' && $_REQUEST['action'] != 'trash')
	{
		if($opt['validacao'] == 'S')
		{
			$value = $_POST['prazo_validacao'];
			$valida = delibera_tratar_data($value);
			if(!$autosave && ($valida === false || $valida < 1))
			{
				$erros[] = __("É necessário definir corretamente o prazo de Validação", "delibera"); 
			} 
		}
		$value = $_POST['prazo_discussao'];
		$valida = delibera_tratar_data($value);
		if(!$autosave && ($valida === false || $valida < 1))
		{
			$erros[] = __("É necessário definir corretamente o prazo de discussão", "delibera"); 
		}
		
		if($opt['relatoria'] == 'S')
		{
			$value = $_POST['prazo_relatoria'];
			$valida = delibera_tratar_data($value);
			if(!$autosave && ($valida === false || $valida < 1))
			{
				$erros[] = __("É necessário definir corretamente o prazo para relatoria", "delibera"); 
			}
			
			if($opt['eleicao_relator'] == 'S')
			{
				$value = $_POST['prazo__leicao_relator'];
				$valida = delibera_tratar_data($value);
				if(!$autosave && ($valida === false || $valida < 1))
				{
					$erros[] = __("É necessário definir corretamente o prazo para eleição de um relator", "delibera"); 
				}
			}
			
		}
		
		$value = $_POST['prazo_votacao'];
		$valida = delibera_tratar_data($value);
		if(!$autosave && ($valida === false || $valida < 1))
		{
			$erros[] = __("É necessário definir corretamente o prazo para votação", "delibera"); 
		}
		
		if($opt['validacao'] == 'S')
		{
			$value = (int)$_POST['min_validacoes'];
			$valida = is_int($value) && $value > 0;
			if(!$autosave && ($valida === false))
			{
				$erros[] = __("É necessário definir corretamente o número mínimo de validações", "delibera"); 
			} 
		}
		
		if(
			count($erros) == 0
		)
		{
			return $data;
		}
		else 
		{
			//wp_die(__('Erro ao salvar dados da pauta, faltando informações de prazos e validações mínimas!','delibera'));
			wp_die(implode("<BR/>", $erros));
		}
	}
	return $data;
}

add_filter('wp_insert_post_data', 'delibera_check_post_data', 10, 2);

function delibera_get_comments_link() {
	global $post;
	
	return get_permalink($post->ID) . '#delibera-comments';
}

function delibera_get_comment_link($comment_pass = false)
{
	global $comment;
	if(is_object($comment_pass))
	{
		$comment = $comment_pass;
	}
	
	return str_replace('#comment', '#delibera-comment', get_comment_link($comment));
}

function delibera_comment_post_redirect( $location ) {
	global $post, $comment_id;
	
	return ( $post->post_type == 'pauta' ) ? preg_replace("/#comment-([\d]+)/", "#delibera-comment-" . $comment_id, $location) : $location; 
}
add_filter( 'comment_post_redirect', 'delibera_comment_post_redirect' );

/**
 * 
 * Comentário em listagem (Visualização)
 * @param string $commentText
 */
function delibera_comment_text($commentText) 
{
	global $comment, $post, $delibera_comments_padrao;
	if(get_post_type($post) == "pauta" && $delibera_comments_padrao !== true)
	{
		$commentText = delibera_comment_text_filtro($commentText, $comment->comment_ID);
		$tipo = get_comment_meta($comment->comment_ID, "delibera_comment_tipo", true);
		$total = 0;
		$nvotos = 0;
		switch ($tipo)
		{ 
			case 'validacao':
			{
				$validacao = get_comment_meta($comment->comment_ID, "delibera_validacao", true);
				$sim = ($validacao == "S" ? true : false);
				$commentText = '
					<div id="painel_validacao delibera-comment-text" >
						'.($sim ? '
						<label class="delibera-aceitou-view">'.__('Aceitou','delibera').'</label>
						' : '
						<label class="delibera-rejeitou-view">'.__('Rejeitou','delibera').'</label>
					</div>
				');
			}break;
			case 'discussao':
			case 'encaminhamento':
			case 'relatoria':
			{
				$situacao = delibera_get_situacao($comment->comment_post_ID);
				if($situacao->slug == 'discussao' || $situacao->slug == 'relatoria')
				{
					if ($tipo == "discussao")
					{
						$class_comment = "discussao delibera-comment-text";
					}
					else
					{
						$class_comment = "encaminhamento delibera-comment-text";
					}
					$commentText = "<div id=\"delibera-comment-text-".$comment->comment_ID."\" class='".$class_comment."'>".$commentText."</div>";
				}
				elseif($situacao->slug == 'comresolucao')
				{
					$total = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_votos', true);
					$nvotos = get_comment_meta($comment->comment_ID, "delibera_comment_numero_votos", true);
					$commentText = '
						<div id="delibera-comment-text-'.$comment->comment_ID.'" class="comentario_coluna1 delibera-comment-text">
							'.$commentText.'
						</div>
						<div class="comentario_coluna2 delibera-comment-text">
							'.$nvotos.($nvotos == 1 ? " ".__('Voto','delibera') : " ".__('Votos','delibera') ).
						'('.( $nvotos > 0 && $total > 0 ? (($nvotos*100)/$total) : 0).'%)
						</div>
					';
				}
				if(has_filter('delibera_mostra_discussao'))
				{
					$commentText = apply_filters('delibera_mostra_discussao', $commentText, $total, $nvotos, $situacao->slug);
				}
			}break;
			case 'resolucao':
			{
				$total = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_votos', true);
				$nvotos = get_comment_meta($comment->comment_ID, "delibera_comment_numero_votos", true);
				$commentText = '
					<div class="comentario_coluna1 delibera-comment-text">
						'.$commentText.'
					</div>
					<div class="comentario_coluna2 delibera-comment-text">
						'.$nvotos.($nvotos == 1 ? " ".__('Voto','delibera') : " ".__('Votos','delibera') ).
						'('.( $nvotos > 0 && $total > 0 ? (($nvotos*100)/$total) : 0).'%)
					</div>
				';
			}break;
			case 'voto':
			{
				$commentText = ' 
				<div class="comentario_coluna1 delibera-comment-text">
					'.$commentText.'
				</div>
				';
			}break;
		}
		if(has_filter('delibera_mostra_discussao'))
		{
			$commentText = apply_filters('delibera_mostra_discussao', $commentText, $tipo, $total, $nvotos);
		}
		return $commentText;
	}
	else 
	{
		return '<div class="delibera-comment-text">'.$commentText.'</div>';
	}
}

add_filter('comment_text', 'delibera_comment_text');

function delibera_comment_text_filtro($text, $comment_id = false, $show = true)
{
	$opt = delibera_get_config();
	$tamanho = $opt['numero_max_palavras_comentario'];
	if($opt['limitar_tamanho_comentario'] === 'S' && strlen($text) > $tamanho)
	{
		if($comment_id === false)
		{
			$comment_id = get_comment_ID();
		}
		$string_temp = wordwrap($text, $tamanho, '##!##');
		$cut = strpos($string_temp, '##!##');
		
		$text = delibera_show_hide_button($comment_id, $text, $cut, $show);
	}
	return $text;
}

function delibera_show_hide_button($comment_id, $text, $cut, $show)
{
	$comment_text = $text;
	$label = __('Continue lendo este comentário', 'delibera');
	if($show === true)
	{
		$showhide = '
			<div id="showhide_comment'.$comment_id.'" class="delibera-slide-text" style="display:none" >
		';
		$showhide_button = '
			<div id="showhide_button'.$comment_id.'" class="delibera-slide" onclick="delibera_showhide(\''.$comment_id.'\');" >'.$label.'</div>
		';
		$part = '<div id="showhide-comment-part-text-'.$comment_id.'" class="delibera-slide-part-text" >';
		$part .= truncate($text, $cut, '&hellip;');
		$part .= '</div>';
		
		$comment_text = $part.$showhide.$text."</div>".$showhide_button;
	}
	else
	{
		$link = '<a class="delibera_leia_mais_link" href="'.delibera_get_comment_link($comment_id).'">'.$label."</a>";
		$comment_text = truncate($text, $cut,'&hellip;').'<br/>
		'.$link;
	}
	
	return $comment_text;
}

/**
 * 
 *  
 * @param string $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param string $ending Ending to be appended to the trimmed string.
 * @param boolean $exact If false, $text will not be cut mid-word
 * @param boolean $considerHtml If true, HTML tags would be handled correctly
 * @return string Trimmed string.
 */

function truncate($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true) {
	if ($considerHtml) {
		// if the plain text is shorter than the maximum length, return the whole text
		if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		// splits all html-tags to scanable lines
		preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
		$total_length = strlen($ending);
		$open_tags = array();
		$truncate = '';
		foreach ($lines as $line_matchings) {
			// if there is any html-tag in this line, handle it and add it (uncounted) to the output
			if (!empty($line_matchings[1])) {
				// if it's an "empty element" with or without xhtml-conform closing slash
				if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
					// do nothing
				// if tag is a closing tag
				} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
					// delete tag from $open_tags list
					$pos = array_search($tag_matchings[1], $open_tags);
					if ($pos !== false) {
					unset($open_tags[$pos]);
					}
				// if tag is an opening tag
				} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
					// add tag to the beginning of $open_tags list
					array_unshift($open_tags, strtolower($tag_matchings[1]));
				}
				// add html-tag to $truncate'd text
				$truncate .= $line_matchings[1];
			}
			// calculate the length of the plain text part of the line; handle entities as one character
			$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
			if ($total_length+$content_length> $length) {
				// the number of characters which are left
				$left = $length - $total_length;
				$entities_length = 0;
				// search for html entities
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
					// calculate the real length of all entities in the legal range
					foreach ($entities[0] as $entity) {
						if ($entity[1]+1-$entities_length <= $left) {
							$left--;
							$entities_length += strlen($entity[0]);
						} else {
							// no more characters left
							break;
						}
					}
				}
				$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
				// maximum lenght is reached, so get off the loop
				break;
			} else {
				$truncate .= $line_matchings[2];
				$total_length += $content_length;
			}
			// if the maximum length is reached, get off the loop
			if($total_length>= $length) {
				break;
			}
		}
	} else {
		if (strlen($text) <= $length) {
			return $text;
		} else {
			$truncate = substr($text, 0, $length - strlen($ending));
		}
	}
	// if the words shouldn't be cut in the middle...
	if (!$exact) {
		// ...search the last occurance of a space...
		$spacepos = strrpos($truncate, ' ');
		if (isset($spacepos)) {
			// ...and cut the text in this position
			$truncate = substr($truncate, 0, $spacepos);
		}
	}
	// add the defined ending to the text
	$truncate .= $ending;
	if($considerHtml) {
		// close all unclosed html-tags
		foreach ($open_tags as $tag) {
			$truncate .= '</' . $tag . '>';
		}
	}
	return $truncate;
}

/**
 * 
 * Comentário na tela de Edição na administração
 * @param WP_comment $comment
 */
function delibera_edit_comment($comment)
{
	if(get_post_type($comment->comment_post_ID) == "pauta")
	{
		$tipo = get_comment_meta($comment->comment_ID, "delibera_comment_tipo", true);
		switch ($tipo)
		{ 
			case 'validacao':
			{
				$validacao = get_comment_meta($comment->comment_ID, "delibera_validacao", true);
				$sim = ($validacao == "S" ? true : false);
				?>
				<div id="painel_validacao delibera-comment-text" >
					<?php if($sim){ ?>
					<label class="delibera-aceitou-view"><?php _e('Aceitou','delibera'); ?></label>
					<?php }else { ?>
					<label class="delibera-rejeitou-view"><?php _e('Rejeitou','delibera'); ?></label>
					<?php } ?>
				</div>
				<script type="text/javascript">
					var quickdiv = document.getElementById('postdiv');
					quickdiv.style.display = 'none';
				</script>
				<?php
			}break;
			case 'discussao':
			case 'encaminhamento':
			{
				$tipo = get_comment_meta($comment->comment_ID, "delibera_comment_tipo", true);
				$checked = $tipo == "discussao" ? "" : ' checked="checked" ';
				?>
				<span class="checkbox-encaminhamento"><input id="delibera_encaminha-<?php echo $comment->comment_ID ?>" type="checkbox" name="delibera_encaminha" value="S" <?php echo $checked ?> /><?php _e('proposta de encaminhamento','delibera'); ?></span>
				<?php 
			}break;
		}
	}
}

add_filter('add_meta_boxes_comment', 'delibera_edit_comment');

function delibera_can_comment($postID = '')
{
	if(is_null($postID))
	{
		$post = get_post($postID);
		$postID = $post->ID;
	}
	
	$situacoes_validas = array('validacao' => true, 'discussao' => true, 'emvotacao' => true, 'elegerelator' => true);
	$situacao = delibera_get_situacao($postID);
	
	if(array_key_exists($situacao->slug, $situacoes_validas))
	{
		return current_user_can('votar');
	}
	elseif($situacao->slug == 'relatoria')
	{
		return current_user_can('relatoria');
	}
	return false;
}

function delibera_comments_open($open, $post_id)
{
	if ( 'pauta' == get_post_type($post_id) )
		return $open && delibera_can_comment($post_id);
	else
		return $open;
}
add_filter('comments_open', 'delibera_comments_open', 10, 2);

/**
 * Verifica se é possível fazer comentários, se o usuário tiver poder para tanto
 * @param unknown_type $postID
 */
function delibera_comments_is_open($postID = null)
{
	if(is_null($postID))
	{
		$post = get_post($postID);
		$postID = $post->ID;
	}
	
	$situacoes_validas = array('validacao' => true, 'discussao' => true, 'emvotacao' => true, 'elegerelator' => true,'relatoria'=>true);
	$situacao = delibera_get_situacao($postID);
	
	if(array_key_exists($situacao->slug, $situacoes_validas))
	{
		return $situacoes_validas[$situacao->slug];
	}

	return false;
}

/**
 * 
 * Formulário do comentário
 * @param array $defaults
 */
function delibera_comment_form($defaults)
{
	global $post,$delibera_comments_padrao,$user_identity,$comment_footer;
	$comment_footer = "";
	
	if($delibera_comments_padrao === true)
	{
		$defaults['fields'] = $defaults['must_log_in'];
		if(!is_user_logged_in())
		{
			$defaults['comment_field'] = "";
			$defaults['logged_in_as'] = '';
			$defaults['comment_notes_after'] = "";
			$defaults['label_submit'] = "";
			$defaults['id_submit'] = "botao-oculto";
			$defaults['comment_notes_before'] = ' ';
		}
		return $defaults;
	}
	if(get_post_type($post) == "pauta")
	{
		/* @var WP_User $current_user */
		$current_user = wp_get_current_user();
		$defaults['id_form'] = 'delibera_commentform';
		$defaults['comment_field'] = '<div class="delibera_before_fields">'.$defaults['comment_field'];
		$situacao = delibera_get_situacao($post->ID);
		
		switch ($situacao->slug)
		{ 
			
			case 'validacao':
			{
				$user_comments = delibera_get_comments($post->ID, 'validacao', array('user_id' => $current_user->ID));
				$temvalidacao = false;
				foreach ($user_comments as $user_comment)
				{
					if(get_comment_meta($user_comment->comment_ID, 'delibera_comment_tipo', true) == 'validacao')
					{
						$temvalidacao = true;
						break;
					}
				}
				if($temvalidacao)
				{
					$defaults['comment_notes_after'] = '
						<script type="text/javascript">
							var formdiv = document.getElementById("respond");
							formdiv.style.display = "none";
						</script>
					';
				}
				else
				{
					$defaults['title_reply'] = __('Você quer ver essa pauta posta em discussão?','delibera');
					$defaults['must_log_in'] = sprintf(__('Você precisar <a href="%s">estar logado</a> e ter permissão para votar.','delibera'),wp_login_url( apply_filters( 'the_permalink', get_permalink( $post->ID ))));				
					if (current_user_can('votar')) {
						$form = '
							<div id="painel_validacao" >
								<input id="delibera_aceitar" type="radio" name="delibera_validacao" value="S" checked /><label for="delibera_aceitar" class="delibera_aceitar_radio_label">'.__('Aceitar','delibera').'</label>
								<input id="delibera_rejeitar" type="radio" name="delibera_validacao" value="N"  /><label for="delibera_rejeitar" class="delibera_aceitar_radio_label">'.__('Rejeitar','delibera').'</label>
								<input name="comment" value="A validação de '.$current_user->display_name.' foi registrada no sistema." style="display:none;" />
								<input name="delibera_comment_tipo" value="validacao" style="display:none;" />
							</div>
						';
						$defaults['comment_field'] = $form;
						$defaults['comment_notes_after'] = '<div class="delibera_comment_button">';;
						$defaults['logged_in_as'] = "";
						$defaults['label_submit'] = __('Votar','delibera');
						$comment_footer = "</div>";
					} else {
						$defaults['comment_field'] = "";
						$defaults['logged_in_as'] = '<p class="logged-in-as">' . sprintf( __('Você está logado como <a href="%1$s">%2$s</a> que não é um usuário autorizado a votar. <a href="%3$s" title="Sair desta conta?">Sair desta conta</a> e logar com um usuário com permissão de votar?','delibera') , admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post->ID ) ) ) ) . '</p>';
						$defaults['comment_notes_after'] = "";
						$defaults['label_submit'] = "";
						$defaults['id_submit'] = "botao-oculto";
					}
				}
			} break;
			case 'discussao':
			case 'relatoria':
			{
				$defaults['title_reply'] = sprintf(__('Discussão em torno de "%s"','delibera'),$post->post_title);
				$defaults['must_log_in'] = sprintf(__('Você precisar <a href="%s">estar logado</a> para contribuir com a discussão.','delibera'),wp_login_url( apply_filters( 'the_permalink', get_permalink( $post->ID ))));
				$defaults['comment_notes_after'] = "";
				$defaults['logged_in_as'] = "";
				$defaults['comment_field'] = '
						<input name="delibera_comment_tipo" value="discussao" style="display:none;" />'.$defaults['comment_field']
				;
				if($situacao->slug == 'relatoria')
				{
					$defaults['comment_field'] = '
							<input id="delibera-baseouseem" name="delibera-baseouseem" value="" style="display:none;" autocomplete="off" />
							<div id="painel-baseouseem" class="painel-baseouseem"><label id="painel-baseouseem-label" class="painel-baseouseem-label" >'.__('Proposta baseada em:', 'delibera').'&nbsp;</label></div><br/>
							'.$defaults['comment_field']
					;
				}
				if (current_user_can('votar'))
				{	
					$replace = '
								'.(($situacao->slug != 'relatoria') ? '<label class="delibera-encaminha-label" ><input type="radio" name="delibera_encaminha" value="N" checked="checked" />'.__('Opinião', 'delibera').'</label>' : '').'
								<label class="delibera-encaminha-label" ><input type="radio" name="delibera_encaminha" value="S" '.(($situacao->slug == 'relatoria') ? ' checked="checked" ' : '').' />'.__('Proposta de encaminhamento', 'delibera').'</label>
					';
					$defaults['comment_field'] = preg_replace ("/<label for=\"comment\">(.*?)<\/label>/", $replace, $defaults['comment_field']);
				}
				else
				{
					$defaults['comment_field'] = "";
					$defaults['logged_in_as'] = '<p class="logged-in-as">' . sprintf( __('Você está logado como <a href="%1$s">%2$s</a> que não é um usuário autorizado a votar. <a href="%3$s" title="Sair desta conta?">Sair desta conta</a> e logar com usuário que possa votar?','delibera') , admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post->ID ) ) ) ) . '</p>';
					$defaults['comment_notes_after'] = "";
					$defaults['label_submit'] = "";
					$defaults['id_submit'] = "botao-oculto";
				}
				if(has_filter('delibera_discussao_comment_form'))
				{
					$defaults = apply_filters('delibera_discussao_comment_form', $defaults, $situacao->slug);
				}
			}break;
			case 'emvotacao':
			{
				$user_comments = delibera_get_comments($post->ID, 'voto', array('user_id' => $current_user->ID));
				$temvoto = false;
				foreach ($user_comments as $user_comment)
				{
					if(get_comment_meta($user_comment->comment_ID, 'delibera_comment_tipo', true) == 'voto')
					{
						$temvoto = true;
						break;
					}
				}
				if($temvoto)
				{
					$defaults['comment_notes_after'] = '
						<script type="text/javascript">
							var formdiv = document.getElementById("respond");
							formdiv.style.display = "none";
						</script>
					';
				}
				else
				{
					$defaults['title_reply'] = sprintf(__('Regime de votação para a pauta "%s"','delibera'),$post->post_title);
					$defaults['must_log_in'] = sprintf(__('Você precisar <a href="%s">estar logado</a> e ter permissão para votar.'),wp_login_url( apply_filters( 'the_permalink', get_permalink( $post->ID ))));
					$encaminhamentos = array();
					if (current_user_can('votar')) {
						$form = '<div class="delibera_checkbox_voto">';
						$encaminhamentos = delibera_get_comments_encaminhamentos($post->ID);
						
						$form .= '<div class="instrucoes-votacao">'.__('Escolha os encaminhamentos que deseja aprovar e depois clique em "Votar":','delibera').'</div>';
						
						$i = 0;
						foreach ($encaminhamentos as $encaminhamento)
						{
							$form .= '
								<div class="checkbox-voto"><input type="checkbox" name="delibera_voto'.$i.'" id="delibera_voto'.$i.'" value="'.$encaminhamento->comment_ID.'" /><label for="delibera_voto'.$i++.'" class="label-voto">'.$encaminhamento->comment_content.'</label></div> 
							';
						}
						$form .= '
								<input name="delibera_comment_tipo" value="voto" style="display:none;" />
								<input name="comment" value="O voto de '.$current_user->display_name.' foi registrado no sistema" style="display:none;" />
							</div>'
						;
						
						$defaults['comment_field'] = $form;
						$defaults['logged_in_as'] = "";
						$defaults['label_submit'] = __('Votar','delibera');
						$defaults['comment_notes_after'] = '<div class="delibera_comment_button">';;
						$comment_footer = "</div>";
					} else {
						$defaults['comment_field'] = "";
						$defaults['logged_in_as'] = '<p class="logged-in-as">' . sprintf( __('Você está logado como <a href="%1$s">%2$s</a> que não é um usuário autorizado a votar. <a href="%3$s" title="Sair desta conta?">Sair desta conta</a> e logar com um usuário com permisão para votar?','delibera') , admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post->ID ) ) ) ) . '</p>';
						$defaults['comment_notes_after'] = "";
						$defaults['label_submit'] = "";
						$defaults['id_submit'] = "botao-oculto";
					}
				}
				if(has_filter('delibera_resolucoes_comment_form'))
				{
					$defaults = apply_filters('delibera_resolucoes_comment_form', $defaults, $temvoto, $encaminhamentos);
				}
			} break;
			case 'comresolucao':
			{
				$defaults['comment_notes_after'] = '<script type="text/javascript">
					var formdiv = document.getElementById("respond");
					formdiv.style.display = "none";
				</script>';
				if(has_filter('delibera_comresolucao_comment_form'))
				{
					$defaults = apply_filters('delibera_comresolucao_comment_form', $defaults);
				}
			}break;
		}
		if(!is_user_logged_in())
		{
			$defaults['comment_notes_before'] = '<script type="text/javascript">
					var formdiv = document.getElementById("respond");
					formdiv.style.display = "none";
			</script>';
		}
	}
	return $defaults;	
}
add_filter('comment_form_defaults', 'delibera_comment_form');

function delibera_comment_form_action($postID)
{
	if(is_pauta())
	{
		global $comment_footer;
		echo $comment_footer;
		echo "</div>";
		if(function_exists('ecu_upload_form') && $situacao->slug != 'relatoria' && $situacao->slug != 'discussao')
		{
			echo '<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#ecu_uploadform").replaceWith("");
				});
				</script>';
		}
	}
}

add_action('comment_form', 'delibera_comment_form_action');

/**
 * 
 * Salvar custom fields do comentário
 * @param int $comment_id
 */
function delibera_save_comment_metas($comment_id) 
{
	$tipo = get_comment_meta($comment_id, "delibera_comment_tipo", true);
	
	if($tipo == false || $tipo == "")
	{
		if(array_key_exists("delibera_comment_tipo", $_POST))
		{
			$tipo = $_POST['delibera_comment_tipo'];
		}
	}
	
	delibera_curtir_comment_meta($comment_id);
	
	delibera_discordar_comment_meta($comment_id);
	
	$comment = get_comment($comment_id);
	
	switch($tipo)
	{
		case "validacao":
		{
			add_comment_meta($comment_id, 'delibera_validacao', $_POST['delibera_validacao'], true);
			add_comment_meta($comment_id, 'delibera_comment_tipo', 'validacao', true);
			
			if($_POST['delibera_validacao'] == "S")
			{
				$validacoes = get_post_meta($comment->comment_post_ID, 'numero_validacoes', true);
				$validacoes++;
				update_post_meta($comment->comment_post_ID, 'numero_validacoes', $validacoes); // Atualiza
				delibera_valida_validacoes($comment->comment_post_ID);
			}
			$nvalidacoes = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_validacoes', true);
			$nvalidacoes++;
			update_post_meta($comment->comment_post_ID, 'delibera_numero_comments_validacoes', $nvalidacoes);
		}break;
		
		case 'discussao':
		case 'encaminhamento':
		{
			$encaminhamento = $_POST['delibera_encaminha'];
			if($encaminhamento == "S")
			{
				add_comment_meta($comment_id, 'delibera_comment_tipo', 'encaminhamento', true);
				$nencaminhamentos = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_encaminhamentos', true);
				$nencaminhamentos++;
				update_post_meta($comment->comment_post_ID, 'delibera_numero_comments_encaminhamentos', $nencaminhamentos);
				if(array_key_exists('delibera-baseouseem', $_POST))
				{
					add_comment_meta($comment_id, 'delibera-baseouseem', $_POST['delibera-baseouseem'], true);
				}
			}
			else 
			{
				add_comment_meta($comment_id, 'delibera_comment_tipo', 'discussao', true);
				$ndiscussoes = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_discussoes', true);
				$ndiscussoes++;
				update_post_meta($comment->comment_post_ID, 'delibera_numero_comments_discussoes', $ndiscussoes);
			}
			if(has_action('delibera_nova_discussao'))
			{
				do_action('delibera_nova_discussao', $comment_id, $comment, $encaminhamento);
			}
		}break;
		case 'voto':
		{
			
			add_comment_meta($comment_id, 'delibera_comment_tipo', 'voto', true);
			
			$votos = array();
			
			foreach ($_POST as $postkey => $postvar)
			{
				if( substr($postkey, 0, strlen('delibera_voto')) == 'delibera_voto' )
				{
					$votos[] = $postvar;
				}
			}
			
			add_comment_meta($comment_id, 'delibera_votos', $votos, true);
			
			$comment = get_comment($comment_id);
			delibera_valida_votos($comment->comment_post_ID);
			
			$nvotos = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_votos', true);
			$nvotos++;
			update_post_meta($comment->comment_post_ID, 'delibera_numero_comments_votos', $nvotos);
			
			if(has_action('delibera_novo_voto'))
			{
				do_action('delibera_novo_voto', $comment_id, $comment, $votos);
			}
			
		} break;
			
		default:
		{
			$npadroes = get_post_meta($comment->comment_post_ID, 'delibera_numero_comments_padroes', true);
			$npadroes++;
			update_post_meta($comment->comment_post_ID, 'delibera_numero_comments_padroes', $npadroes);
		}break;
	}
	if(array_search($tipo, delibera_get_comments_types()) !== false)
	{
		wp_set_comment_status($comment_id, 'approve');
		delibera_notificar_novo_comentario($comment);
		do_action('delibera_nova_interacao', $comment_id);
	}
}
add_action ('comment_post', 'delibera_save_comment_metas', 1);

function delibera_pre_edit_comment($dados)
{
	$comment_id = 0;
	if(array_key_exists('comment_ID', $_POST))
	{
		$comment_id = $_POST['comment_ID'];
	}
	else
	{
		global $comment;
		if(isset($comment->comment_ID))
		{
			$comment_id = $comment->comment_ID;
		}
		else 
		{
			wp_die(__('Você não pode Editar esse tipo de comentário','delibera'));
		}
	}
	
	$tipo = get_comment_meta($comment_id, "delibera_comment_tipo", true);
	if(array_search($tipo, delibera_get_comments_types()) !== false)
	{
		wp_die(__('Você não pode Editar esse tipo de comentário','delibera'));
	}
}

//add_filter('comment_save_pre', 'delibera_pre_edit_comment'); //TODO Verificar edição

function delibera_comments_template($path)
{
	if(get_post_type() == 'pauta')
	{
		$include = dirname(__FILE__).DIRECTORY_SEPARATOR."delibera_comments.php";
		return $include;
	}
	return $path;
}

add_filter('comments_template', 'delibera_comments_template');

$filename = __DIR__.DIRECTORY_SEPARATOR.'delibera_template.php';
//if(file_exists($filename))
	require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_template.php';

// Fim Inicialização do plugin

// Menu de configuração

function delibera_config_menu()
{
	/*if (function_exists('add_menu_page'))
		add_menu_page( __('Delibera','delibera'), __('Delibera plugin','delibera'), 'manage_options', 'delibera-config', 'delibera_conf_page');*/
	
	$base_page = 'delibera-config';
	
	if (function_exists('add_menu_page'))
	{
		add_object_page( __('Delibera','delibera'), __('Delibera','delibera'), 'manage_options', $base_page, array(), WP_PLUGIN_URL."/delibera/images/delibera_icon.png");
		//add_submenu_page($base_page, __('Pesquisar Contatos','delibera'), __('Pesquisar Contatos','delibera'), 'manage_options', 'delibera-gerenciar', 'delibera_GerenciarContato' );
		//add_submenu_page($base_page, __('Criar Contato','delibera'), __('Criar Contato','delibera'), 'manage_options', 'delibera-criar', 'delibera_CriarContato' );
		//add_submenu_page($base_page, __('Importar Contatos','delibera'), __('Importar Contatos','delibera'), 'manage_options', 'delibera-importar', 'delibera_ImportarContato' );
		add_submenu_page($base_page, __('Configurações do Plugin','delibera'),__('Configurações do Plugin','delibera'), 'manage_options', 'delibera-config', 'delibera_conf_page');
		do_action('delibera_menu_itens', $base_page);
	}
}

/**
 * Create a form table from an array of rows
 */
function delibera_form_table($rows) {
	$content = '<table class="form-table">';
	foreach ($rows as $row) {
		$content .= '<tr '.(array_key_exists('row-id', $row) ? 'id="'.$row['row-id'].'"' : '' ).' '.(array_key_exists('row-style', $row) ? 'style="'.$row['row-style'].'"' : '' ).' '.(array_key_exists('row-class', $row) ? 'class="'.$row['row-class'].'"' : '' ).' ><th valign="top" scrope="row">';
		if (isset($row['id']) && $row['id'] != '')
			$content .= '<label for="'.$row['id'].'">'.$row['label'].':</label>';
		else
			$content .= $row['label'];
		if (isset($row['desc']) && $row['desc'] != '')
			$content .= '<br/><small>'.$row['desc'].'</small>';
		$content .= '</th><td valign="top">';
		$content .= $row['content'];
		$content .= '</td></tr>'; 
	}
	$content .= '</table>';
	return $content;
}

/**
 * Create a potbox widget
 */
function delibera_postbox($id, $title, $content) {
?>
	<div id="<?php echo $id; ?>" class="postbox">
		<div class="handlediv" title="Click to toggle"><br /></div>
		<h3 class="hndle"><span><?php echo $title; ?></span></h3>
		<div class="inside">
			<?php echo $content; ?>
		</div>
	</div>
<?php
}	

/**
 * Gera a página de configuração/Tratamento dos dados de Post
 */
function delibera_conf_page()
{
	$mensagem = '';
	
	if ($_SERVER['REQUEST_METHOD']=='POST')
	{
		
		if (!current_user_can('manage_options')) die(__('Você não pode editar as configurações do delibera.','delibera'));
		check_admin_referer('delibera-config');
			
		foreach ( array_keys(delibera_get_config()) as $option_name
		)
		{
			if (isset($_POST[$option_name]))
			{
				$opt[$option_name] = htmlspecialchars($_POST[$option_name]);
			}
			else 
			{
				$opt[$option_name] = "N";
			}
		}

		if(
			isset($_POST["delibera_reinstall"]) &&
			$_POST['delibera_reinstall'] == 'S'
		)
		{
			try
			{
				include_once __DIR__.DIRECTORY_SEPARATOR.'delibera_reinstall.php';
			}
			catch (Exception $e)
			{
				wp_die($e->getMessage());
			}
		}
		
		if (update_option('delibera-config', $opt) || (isset($_POST["delibera_reinstall"]) && $_POST['delibera_reinstall'] == 'S'))
			$mensagem = __('Configurações salvas!','delibera');
		else
			$mensagem = __('Erro ao salvar as configurações. Verifique os valores inseridos e tente novamente!','delibera');
	}

	$opt = delibera_get_config();
	?>
		
<div class="wrap">
<h2>Configurações gerais</h2>
<div class="postbox-container" style="width:80%;">
	<div class="metabox-holder">	
		<div class="meta-box-sortables">
			<?php if ($mensagem) {?>
			<div id="message" class="updated">
			<?php echo $mensagem; ?>
			</div>
			<?php }?>
			<form action="<?php echo $_SERVER['REQUEST_URI'];?>" method="post" id="delibera-config" >
			<?php if (function_exists('wp_nonce_field')) 		
					wp_nonce_field('delibera-config');
						
				$rows = array();
				if(is_multisite() && get_current_blog_id() == 1)
				{
					$rows[] = array(
						"id" => "plan_restriction",
						"label" => __('Sistema de planos de pagamento ativo?','delibera'),
						"content" => '<input type="checkbox" name="plan_restriction" id="plan_restriction" value="S" '. ( htmlspecialchars_decode($opt['plan_restriction']) == "S" ? "checked='checked'" : "" ).'/>',
					);
				}
				$rows[] = array(
					"id" => "representante_define_prazos",
					"label" => __('Representante define prazos?','delibera'),
					"content" => '<input type="checkbox" name="representante_define_prazos" id="representante_define_prazos" value="S" '. ( htmlspecialchars_decode($opt['representante_define_prazos']) == "S" ? "checked='checked'" : "" ).'/>',
				);
				$rows[] = array(
					"id" => "validacao",
					"label" => __('É Necessário Validação?','delibera'),
					"content" => '<input type="checkbox" name="validacao" value="S" '.(htmlspecialchars_decode($opt['validacao']) == 'S' ? 'checked="checked"' : '').' />'
				);
				$rows[] = array(
					"id" => "minimo_validacao",
					"label" => __('Mínimo de adesões para pauta','delibera'),
					"content" => '<input type="text" name="minimo_validacao" id="minimo_validacao" value="'.htmlspecialchars_decode($opt['minimo_validacao']).'"/>'
				);
				
				$rows[] = array(
					"id" => "dias_validacao",
					"label" => __('Dias para validação da pauta','delibera'),
					"content" => '<input type="text" name="dias_validacao" id="dias_validacao" value="'.htmlspecialchars_decode($opt['dias_validacao']).'"/>'
				);
				
				$rows[] = array(
					"id" => "dias_discussao",
					"label" => __('Dias para discussão da pauta','delibera'),
					"content" => '<input type="text" name="dias_discussao" id="dias_discussao" value="'.htmlspecialchars_decode($opt['dias_discussao']).'"/>'
				);
				
				$rows[] = array(
					"id" => "dias_votacao",
					"label" => __('Dias para votação de encaminhamentos','delibera'),
					"content" => '<input type="text" name="dias_votacao" id="dias_votacao" value="'.htmlspecialchars_decode($opt['dias_votacao']).'"/>'
				);
				
				$rows[] = array(
					"id" => "dias_novo_prazo",
					"label" => __('Dias para novo prazo','delibera'),
					"content" => '<input type="text" name="dias_novo_prazo" id="dias_novo_prazo" value="'.htmlspecialchars_decode($opt['dias_novo_prazo']).'"/>'
				);
				$rows[] = array(
					"id" => "relatoria",
					"label" => __('Necessário Relatoria?','delibera'),
					"content" => '<input type="checkbox" name="relatoria" value="S" '.(htmlspecialchars_decode($opt['relatoria']) == 'S' ? 'checked="checked"' : '').' />'
				);
				$rows[] = array(
					"id" => "dias_relatoria",
					"label" => __('Prazo para Relatoria','delibera'),
					"content" => '<input type="text" name="dias_relatoria" id="dias_relatoria" value="'.htmlspecialchars_decode($opt['dias_relatoria']).'"/>'
				);
				$rows[] = array(
					"id" => "eleicao_relator",
					"label" => __('Necessário Eleição de Relator?','delibera'),
					"content" => '<input type="checkbox" name="eleicao_relator" value="S" '.(htmlspecialchars_decode($opt['eleicao_relator']) == 'S' ? 'checked="checked"' : '').' />'
				);
				$rows[] = array(
					"id" => "dias_votacao_relator",
					"label" => __('Prazo para Eleição de Relator','delibera'),
					"content" => '<input type="text" name="dias_votacao_relator" id="dias_votacao_relator" value="'.htmlspecialchars_decode($opt['dias_votacao_relator']).'"/>'
				);
				$rows[] = array(
					"id" => "limitar_tamanho_comentario",
					"label" => __('Necessário Limitar o Tamanho do comentário visível?','delibera'),
					"content" => '<input type="checkbox" name="limitar_tamanho_comentario" value="S" '.(htmlspecialchars_decode($opt['limitar_tamanho_comentario']) == 'S' ? 'checked="checked"' : '').' />'
				);
				$rows[] = array(
					"id" => "numero_max_palavras_comentario",
					"label" => __('Número máximo de caracteres por comentário','delibera'),
					"content" => '<input type="text" name="numero_max_palavras_comentario" id="numero_max_palavras_comentario" value="'.htmlspecialchars_decode($opt['numero_max_palavras_comentario']).'"/>'
				);
				$rows[] = array(
					"id" => "delibera_reinstall",
					"label" => __('Importar novas configurações de arquivo externo?','delibera'),
					"content" => '<input type="checkbox" name="delibera_reinstall" value="S" />'
				);
				$rows[] = array(
					"id" => "cabecalho_arquivo",
					"label" => __('Cabeçalho da página de arquivo do sistema (lista de pautas)','delibera'),
					"content" => '<input type="text" name="cabecalho_arquivo" id="cabecalho_arquivo" value="'.htmlspecialchars_decode($opt['cabecalho_arquivo']).'"/>'
				);
				$table = delibera_form_table($rows);
				if(has_filter('delibera_config_form'))
				{
					$table = apply_filters('delibera_config_form', $table, $opt);
				}
				delibera_postbox('delibera-config',__('Configurações para o plugin Delibera','delibera'), $table.'<div class="submit"><input type="submit" class="button-primary" name="submit" value="'.__('Salvar as configurações do Delibera','delibera').'" /></form></div>');
			?>
				
				</form>
			</div> <!-- meta-box-sortables -->
		</div> <!-- meta-box-holder -->
	</div> <!-- postbox-container -->

	<?php do_action('delibera_config_page_extra');?>
	
</div>

<?php	

}

// Fim Página de configuração

// Scripts

function delibera_scripts()
{
	if(is_pauta())
	{
		//global $_POST;
		wp_enqueue_script('jquery-expander', WP_CONTENT_URL.'/plugins/delibera/js/jquery.expander.js', array('jquery'));
		wp_enqueue_script('delibera',WP_CONTENT_URL.'/plugins/delibera/js/scripts.js', array( 'jquery-expander'));
	}
}
add_action( 'wp_print_scripts', 'delibera_scripts' );

/**
 * 
 * Se tiver estilos customizados, ta aí a dica...
 *
function delibera_print_styles()
{
	
} 
add_action('wp_print_styles', 'delibera_print_styles');*/

function delibera_print_styles()
{
	wp_enqueue_style('delibera_style', WP_CONTENT_URL.'/plugins/delibera/delibera.css');
} 
add_action('admin_print_styles', 'delibera_print_styles');

function delibera_admin_scripts()
{
	if(is_pauta())
	{
		wp_enqueue_script('jquery-ui-datepicker-ptbr', WP_CONTENT_URL.'/plugins/delibera/js/jquery.ui.datepicker-pt-BR.js', array('jquery-ui-datepicker'));
		wp_enqueue_script('delibera-admin',WP_CONTENT_URL.'/plugins/delibera/js/admin_scripts.js', array( 'jquery-ui-datepicker-ptbr'));
	}
	if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'delibera-config')
	{
		wp_enqueue_script('delibera-admin-notifica',WP_CONTENT_URL.'/plugins/delibera/js/admin_notifica_scripts.js', array('jquery'));
	}
}
add_action( 'admin_print_scripts', 'delibera_admin_scripts' );

// Fim Scripts

/*
 * Rotinas de instalação do plugin
 */

function delibera_get_config()
{
	$opt = array();
	$opt['minimo_validacao'] = '10';
	$opt['dias_validacao'] = '5';
	$opt['dias_discussao'] = '5';
	$opt['dias_votacao'] = '5';
	$opt['representante_define_prazos'] = 'N';
	$opt['dias_novo_prazo'] = '2';
	$opt['validacao'] = 'S';
	$opt['dias_relatoria'] = '2';
	$opt['relatoria'] = 'N';
	$opt['eleicao_relator'] = 'N';
	$opt['dias_votacao_relator'] = '2';
	$opt['limitar_tamanho_comentario'] = 'N';
	$opt['numero_max_palavras_comentario'] = '50';
	$opt['plan_restriction'] = 'N';
	$opt['cabecalho_arquivo'] = false;
	
	
	$opt_conf = get_option('delibera-config');
	if(!is_array($opt_conf)) $opt_conf = array();
	$opt = array_merge($opt, $opt_conf);
	if(has_filter('delibera_get_config'))
	{
		$opt = apply_filters('delibera_get_config', $opt);
	}
	return $opt;
} 

function delibera_instalacao() 
{ 
	if(is_multisite())
	{
		$id = get_current_blog_id();
		switch_to_blog(1);
		delibera_wpmu_new_blog($id);
		restore_current_blog();
	}
	
	if (!get_page_by_slug(DELIBERA_ABOUT_PAGE)) {
		$post = array(
			'post_name' => DELIBERA_ABOUT_PAGE,
			'post_title' => __('Sobre a plataforma', 'delibera'),
	        'post_content' => __('Use está página para explicar para os usuários como utilizar o sistema', 'delibera'),
	        'post_type' => 'page',
	        'post_status' => 'publish',
		);
		wp_insert_post($post);
	}
}
register_activation_hook(__FILE__, 'delibera_instalacao');

function delibera_install_roles()
{
	// simple check to see if pautas capabilities are in place. We only set them if not.
	$Role = get_role('administrator');
	if(!$Role->has_cap('publish_pautas'))
	{
	    // Inicialização das configurações padrão
	    $opt = delibera_get_config();
	    	
	    update_option('delibera-config', $opt);
	    if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'delibera_roles.php'))
	    {
	        $delibera_permissoes = array();
	        require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_roles.php';
	        delibera_roles_install($delibera_permissoes);
	    }
	}
}
add_action('admin_init', 'delibera_install_roles');

function delibera_roles_install($delibera_permissoes)
{
	
	// Criação das regras
	foreach ($delibera_permissoes as $nome => $permisao)
	{
		if($permisao['Novo'] == true)
		{
			$Role = get_role($permisao['From']);
			
			if(!is_object($Role))
			{
				throw new Exception(sprintf(__('Permissão original (%s) não localizada','delibera'),$permisao['From']));
			}
			
			$cap = $Role->capabilities;
			add_role($nome, $permisao["nome"], $cap);
		}
		
		$Role = get_role($nome);
		if(!is_object($Role))
		{
			throw new Exception(sprintf(__('Permissão %s não localizada','delibera'),$nome));
		}
		
		foreach ($permisao['Caps'] as $cap)
		{	
			
			$Role->add_cap($cap);
		}
	}
	
}

function delibera_roles_uninstall($delibera_permissoes)
{

	foreach ($delibera_permissoes as $nome => $permisao)
	{
		if($permisao['Novo'] == true)
		{
			remove_role($nome);
		}
		else 
		{
			$Role = get_role($nome);
			if(!is_object($Role))
			{
				throw new Exception(sprintf(__('Permissão %s não localizada','delibera'),$nome));
			}
		
			foreach ($permisao['Caps'] as $cap)
			{
				$Role->remove_cap($cap);
			}
		}
	}
	
}

/*
 * Desinstalação do Plugin 
 */
function delibera_desinstalacao()
{
	delete_option('delibera-config');
	if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'delibera_roles.php'))
	{
		$delibera_permissoes = array();
		require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_roles.php';
		delibera_roles_uninstall($delibera_permissoes);	
	}
}
register_deactivation_hook( __FILE__, 'delibera_desinstalacao' );


// Funções de conteudo

/**
 * 
 * Retorna post do tipo pauta em uma determinada situacao (validacao, discussao, emvotacao ou comresolucao), usando um filtro
 * @param array $filtro
 * @param string $situacao
 */
function delibera_get_pautas_em($filtro = array(), $situacao = false)
{
	$filtro['post_type'] = "pauta";
	$filtro['post_status'] = "publish";
	$tax_query = array();
	
	if(array_key_exists("tax_query", $filtro) && $situacao !== false)
	{
		$tax_query = $filtro['tax_query'];
		$tax_query['relation'] = 'AND';
	}
	if($situacao !== false)
	{
		$tax_query[] = array(
			'taxonomy' => 'situacao',
			'field' => 'slug',
			'terms' => $situacao
		);
		$filtro['tax_query'] = $tax_query;
	}
	return get_posts($filtro);
}

/**
 * 
 * Retorna pautas em Validação
 * @param array $filtro
 */
function delibera_get_propostas($filtro = array())
{
	return delibera_get_pautas_em($filtro, 'validacao');
}

/**
 * 
 * Retorna pautas em Discussão
 * @param array $filtro
 */
function delibera_get_pautas($filtro = array())
{
	return delibera_get_pautas_em($filtro, 'discussao');
}

function delibera_des_filtro_qtranslate($where)
{
	if(is_archive())
	{
		global $q_config, $wpdb;
		if($q_config['hide_untranslated'] && !is_singular()) {
			$where = str_replace(" AND $wpdb->posts.post_content LIKE '%<!--:".qtrans_getLanguage()."-->%'", '', $where);
		}
	}
	return $where;
}

add_filter('posts_where_request', 'delibera_des_filtro_qtranslate', 11);

/**
 * 
 * Retorna pautas em Votação
 * @param array $filtro
 */
function delibera_get_emvotacao($filtro = array())
{
	return delibera_get_pautas_em($filtro, 'emvotacao');
}

/**
 * 
 * Retorna pautas já resolvidas
 * @param array $filtro
 */
function delibera_get_resolucoes($filtro = array())
{
	return delibera_get_pautas_em($filtro, 'comresolucao');
}

require_once 'delibera_comments_template.php';



function delibera_get_comments_padrao($args = array(), $file = '/comments.php' )
{
	global $delibera_comments_padrao;
	$delibera_comments_padrao = true;
	comments_template($file);
	$delibera_comments_padrao = false;
}

function delibera_get_comments($post_id, $tipo, $args = array())
{
	$args = array_merge(array('post_id' => $post_id), $args);
	$comments = get_comments($args);
	$ret = array();
	foreach ($comments as $comment)
	{
		$tipo_tmp = get_comment_meta($comment->comment_ID, 'delibera_comment_tipo', true);
		if($tipo_tmp == $tipo)
		{
			$ret[] = $comment;
		}
	}
	return $ret;
}

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_WP_comment.php';


function delibera_wp_list_comments($args = array(), $comments = null)
{
	global $post;
	global $delibera_comments_padrao;
	
	if(get_post_type($post) == "pauta")
	{
		$situacao = delibera_get_situacao($post->ID);
		
		if($delibera_comments_padrao === true)
		{
			$args['post_id'] = $post->ID;
			$args['walker'] = new Deliera_Walker_Comment_padrao();
			$comments = get_comments($args);
			$ret = array();
			foreach ($comments as $comment)
			{
				$tipo_tmp = get_comment_meta($comment->comment_ID, 'delibera_comment_tipo', true);
				if(strlen($tipo_tmp) <= 0 || $tipo_tmp === false)
				{
					$ret[] = $comment;
				}
			}
			wp_list_comments($args, $ret);
		}
		elseif($situacao->slug == 'validacao')
		{
			//comment_form();
			$args['walker'] = new Delibera_Walker_Comment();
			//$args['callback'] = 'delibera_comments_list';
			?>
			<div class="delibera_lista_validacoes">
			<?php 
			wp_list_comments($args, $comments);
			?>
			</div>
			<?php 
		}
		elseif($situacao->slug == 'comresolucao')
		{
			$args['walker'] = new Delibera_Walker_Comment();
			wp_list_comments($args, $comments);
			$comments = delibera_get_comments_encaminhamentos($post->ID);
			?>
			<div class="delibera_encaminhamentos_inferior">
			<?php
			wp_list_comments($args, $comments);
			?>
			</div>
			<?php
		}
		else
		{
			$args['walker'] = new Delibera_Walker_Comment();
			//$args['callback'] = 'delibera_comments_list';
			wp_list_comments($args, $comments);
		}
	}
	else
	{
		wp_list_comments($args, $comments);
	}
}


/**
 * Retrieve a list of comments.
 *
 * The comment list can be for the blog as a whole or for an individual post.
 *
 * The list of comment arguments are 'status', 'orderby', 'comment_date_gmt',
 * 'order', 'number', 'offset', and 'post_id'.
 *
 * @since 2.7.0
 * @uses $wpdb
 *
 * @param mixed $args Optional. Array or string of options to override defaults.
 * @return array List of comments.
 */
function delibera_wp_get_comments( $args = '' ) {
	$query = new delibera_WP_Comment_Query();
	return $query->query( $args );
}

function delibera_get_comments_validacoes($post_id)
{
	return delibera_get_comments($post_id, 'validacao');
}

function delibera_get_comments_discussoes($post_id)
{
	return delibera_get_comments($post_id, 'discussao');
}

function delibera_get_comments_encaminhamentos($post_id)
{
	return delibera_get_comments($post_id, 'encaminhamento');
}

function delibera_get_comments_votacoes($post_id)
{
	return delibera_get_comments($post_id, 'voto');
}

function delibera_get_comments_resolucoes($post_id)
{
	if(has_filter('delibera_get_resolucoes'))
	{
		return apply_filters('delibera_get_resolucoes', delibera_get_comments($post_id, 'resolucao'));
	}
	return delibera_get_comments($post_id, 'resolucao');
}

/**
 * 
 * Busca comentários com o tipo em tipos
 * @param array $comments lista de comentários a ser filtrada
 * @param array $tipos tipos aceitos
 */
function delibera_comments_filter_portipo($comments, $tipos)
{
	$ret = array();
	
	foreach ($comments as $comment)
	{
		$tipo = get_comment_meta($comment->comment_ID, 'delibera_comment_tipo', true);
		if(array_search($tipo, $tipos) !== false)
		{
			$ret[] = $comment;
		}
	}
	return $ret;
}

/**
 * 
 * Filtro que retorna Comentário filtrados pela a situação da pauta
 * @param array $comments
 * @param int $postID
 * @return array Comentários filtrados
 */
function delibera_get_comments_filter($comments)
{
	global $delibera_comments_padrao;
	
	if($delibera_comments_padrao === true) return $comments;
	
	$ret = array();
	
	if(count($comments) > 0)
	{
		if(get_post_type($comments[0]->comment_post_ID) == "pauta")
		{
			$situacao = delibera_get_situacao($comments[0]->comment_post_ID);
			switch ($situacao->slug)
			{
				case 'validacao':
				{
					$ret = delibera_comments_filter_portipo($comments, array('validacao'));
				}break;
				case 'discussao':
				{
					$ret = delibera_comments_filter_portipo($comments, array('discussao', 'encaminhamento'));
				}break;
				case 'relatoria':
				{
					$ret = delibera_comments_filter_portipo($comments, array('discussao', 'encaminhamento'));
				}break;
				case 'emvotacao':
				{
					$ret = delibera_comments_filter_portipo($comments, array('voto'));
				}break;
				case 'comresolucao':
				{
					$ret = delibera_comments_filter_portipo($comments, array('resolucao')); 
				}break;
			}
			return $ret;
		}
	}
	return $comments;
}

add_filter('comments_array', 'delibera_get_comments_filter');

function delibera_get_prazo($postID, &$data = null)
{
	$situacao = delibera_get_situacao($postID);
	$prazo = "";
	$idata = strtotime(date('Y/m/d').' 23:59:59');
	
	switch ($situacao->slug)
	{ 
		case 'validacao':
		{
			$prazo = get_post_meta($postID, 'prazo_validacao', true);
		} break;
		case 'discussao':
		{
			$prazo = get_post_meta($postID, 'prazo_discussao', true);
		}break;
		case 'elegerelator':
		{
			$prazo = get_post_meta($postID, 'prazo_eleicao_relator', true);
		}break;
		case 'relatoria':
		{
			$prazo = get_post_meta($postID, 'prazo_relatoria', true);
		}break;
		case 'emvotacao':
		{
			$prazo = get_post_meta($postID, 'prazo_votacao', true);
		} break;
	}
	
	$iprazo = strtotime(substr($prazo, 6).substr($prazo, 2, 4).substr($prazo, 0, 2).' 23:59:59');
	
	$diff = $iprazo - $idata;
	$dias = 0;
	
	if($diff > 0) $dias = ceil($diff/(60*60*24));
	
	if(!is_null($data)) $data = $prazo;
		
	return $dias;
}

function delibera_edit_columns($columns)
{
	$columns[ 'tema' ] = __( 'Tema' );
	$columns[ 'situacao' ] = __( 'Situação' );
	$columns[ 'prazo' ] = __( 'Prazo' );
	return $columns;
}

add_filter('manage_edit-pauta_columns', 'delibera_edit_columns');

function delibera_post_custom_column($column)
{
	global $post;
	
	switch ( $column )
	{
		case 'tema':
			echo the_terms($post->ID, "tema");
			break;
		case 'situacao':
			echo delibera_get_situacao($post->ID)->name;
			break;
		case 'prazo':
			$data = "";
			$prazo = delibera_get_prazo($post->ID, $data);
			if($data != "")
			{
				echo $data." (".$prazo.($prazo == 1 ? " dia" : " dias").")";
			}
			break;
	}
	
}

add_action('manage_posts_custom_column',  'delibera_post_custom_column');

function delibera_comment_number($postID, $tipo)
{
	switch($tipo)
	{
		case 'validacao':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_validacoes', true));
		break;
		case 'discussao':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_discussoes', true));
		break;
		case 'encaminhamento':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_encaminhamentos', true));
		break;
		case 'voto':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_votos', true));
		break;
		/*case 'resolucao':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_resolucoes', true));
		break;*/
		case 'todos':
			return get_post($postID)->comment_count;
		break;
		default:
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_padroes', true));
		break;
	}
}

function delibera_comment_number_filtro($count, $postID)
{
	$situacao = delibera_get_situacao($postID);
	
	if (!$situacao) {
		return;
	}
	
	switch($situacao->slug)
	{
		case 'validacao':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_validacoes', true));
		break;
		case 'discussao':
		case 'comresolucao':
			return doubleval(
				get_post_meta($postID, 'delibera_numero_comments_encaminhamentos', true) +
				get_post_meta($postID, 'delibera_numero_comments_discussoes', true)
			);
		break;
		case 'relatoria':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_encaminhamentos', true));
		break;
		case 'emvotacao':
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_votos', true));
		break;
		default:
			return doubleval(get_post_meta($postID, 'delibera_numero_comments_padroes', true));
		break;
	}
}

add_filter('get_comments_number', 'delibera_comment_number_filtro', 10, 2);

function delibera_restrict_listings()
{
	global $typenow;
	global $wp_query;
	if ($typenow=='pauta')
	{
		$taxonomy = 'situacao';
		$situacao_taxonomy = get_taxonomy($taxonomy);
		wp_dropdown_categories(array(
			'show_option_all' => sprintf(__('Mostrar todas as %s','delibera'),$situacao_taxonomy->label),
			'taxonomy' => $taxonomy,
			'name' => 'situacao',
			'orderby' => 'id',
			'selected' => isset($_REQUEST['situacao']) ? $_REQUEST['situacao'] : '',
			'hierarchical' => false,
			'depth' => 1,
			'show_count' => true, // This will give a view
			'hide_empty' => true, // This will give false positives, i.e. one's not empty related to the other terms.
		));
	}
}
add_action('restrict_manage_posts','delibera_restrict_listings');

function delibera_convert_situacao_id_to_taxonomy_term_in_query(&$query)
{
	global $pagenow; 
	$qv = &$query->query_vars;
	if (isset($qv['post_type']) &&
		$qv['post_type'] == 'pauta' &&
		$pagenow=='edit.php' &&
		isset($qv['situacao'])
	)
	{
		$situacao = get_term_by('id', $_REQUEST['situacao'], 'situacao');
		$qv['situacao'] = $situacao->slug;
	}
}
add_filter('parse_query','delibera_convert_situacao_id_to_taxonomy_term_in_query');

/**
 * Notificações do sistema.
 */
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_notifica.php';

/**
 * Perfil do usuário
 */
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_user_painel.php';

/**
 * 
 * Pega os ultimos conteúdos
 * @param string $tipo (option) 'pauta' ou 'comments', padrão 'pauta' 
 * @param array $args (option) query padrão do post ou do comments 
 * @param int $count (option) padrão 5
 */
function delibera_ultimas($tipo = 'pauta', $args = array(), $count = 5)
{
	switch($tipo)
	{
		case 'pauta':
			$filtro = array('orderby' => 'modified', 'order' => 'DESC', 'posts_per_page' => $count);
			$filtro = array_merge($filtro, $args);
			return delibera_get_pautas_em($filtro, false);
		break;
		case 'comments':
			$filtro = array('orderby' => 'comment_date_gmt', 'order' => 'DESC', 'number' => $count, 'post_type' => 'pauta');
			$filtro = array_merge($filtro, $args);
			return delibera_wp_get_comments($filtro);
		break;
	}
}

function delibera_timeline($post_id = false, $tipo_data = false)
{
	require_once __DIR__.DIRECTORY_SEPARATOR.'timeline/delibera_timeline.php';
	$timeline = new delibera_timeline();
	$timeline->generate($post_id, $tipo_data);
}

function delibera_the_posts($posts)
{
	if (empty($posts)) return $posts;
 
	$timeline_found = false; // use this flag to see if styles and scripts need to be enqueued
	$relatoria = false;
	foreach ($posts as $post)
	{
		if (stripos($post->post_content, '[delibera_timeline') !== false)
		{
			$timeline_found = true; // bingo!
		}
		if(get_post_type($post) == 'pauta')
		{
			$situacao = delibera_get_situacao($post->ID);
			if($situacao->slug == 'relatoria')
			{
				$relatoria = true;
			}
		}
	}
 
	if ($timeline_found)
	{
		// enqueue here
		wp_enqueue_style('delibera_timeline_css',  WP_CONTENT_URL.'/plugins/delibera/timeline/delibera_timeline.css');
		wp_enqueue_script( 'delibera_timeline_js', WP_CONTENT_URL.'/plugins/delibera/timeline/js/delibera_timeline.js', array( 'jquery' ));
		wp_enqueue_script( 'jquery-ui-draggable');
	}
	
	if($relatoria)
	{
			wp_enqueue_script( 'delibera_relatoria_js', WP_CONTENT_URL.'/plugins/delibera/js/delibera_relatoria.js', array( 'jquery' ));
	}
 
	return $posts;
}

add_filter('the_posts', 'delibera_the_posts'); // the_posts gets triggered before wp_head

// FIM Funções de conteudo

// Validadores

function delibera_valida_validacoes($post)
{
	$validacoes = get_post_meta($post, 'numero_validacoes', true);
	$min_validacoes = get_post_meta($post, 'min_validacoes', true);
	
	if($validacoes >= $min_validacoes)
	{
		wp_set_object_terms($post, 'discussao', 'situacao', false); //Mudar situação para Discussão
		if(has_action('delibera_validacao_concluida'))
		{
			do_action('delibera_validacao_concluida', $post);
		}
	}
	else
	{
		if(has_action('delibera_validacao'))
		{
			do_action('delibera_validacao', $post);
		}
	}
}

/* Faz os testes de permissões para garantir que nenhum engraçadinho 
 * está injetando variáveis maliciosas.
 * TODO: Incluir todas as variaveis a serem verificadas aqui
 */
function delibera_valida_permissoes($comment_ID)
{
	if (!current_user_can('votar'))
	{
		if ($_REQUEST['delibera_validacao'] || $_REQUEST['delibera_encaminha'])
			wp_die("Nananina não! Você não tem que ter permissão pra votar.","Tocooo!!");	
	}
}
add_action( 'wp_blacklist_check', 'delibera_valida_permissoes' );

/**
 * 
 * Verifica se o número de votos é igual ao número de representantes para deflagar fim da votação
 * @param integer $postID
 */
function delibera_valida_votos($postID)
{
	global $wp_roles,$wpdb;
	$users_count = 0;
    foreach ($wp_roles->roles as $nome => $role)
    {
    	if(is_array($role['capabilities']) && array_key_exists('votar', $role['capabilities']) && $role['capabilities']['votar'] == 1 ? "SSSSSim" : "NNNnnnnnnnao")
    	{
    		$result = $wpdb->get_results("SELECT count(*) as n FROM $wpdb->usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%$nome%' ");
    		$users_count += $result[0]->n;
    	}
    }
	
	$votos = delibera_get_comments_votacoes($postID);
	
	$votos_count = count($votos);
	
	if($votos_count >= $users_count)
	{
		delibera_computa_votos($postID, $votos);
	}
}

/**
 * 
 * Faz a apuração dos votos e toma as devidas ações:
 *    Empate: Mais prazo;
 *    Vencedor: Marco com resolucao e marca o encaminhamento.
 * @param interger $postID
 * @param array $votos
 */
function delibera_computa_votos($postID, $votos = null)
{
	if(is_null($votos)) // Ocorre no fim do prazo de votação
	{
		$votos = delibera_get_comments_votacoes($postID);
	}
	$encaminhamentos = delibera_get_comments_encaminhamentos($postID);
	$encaminhamentos_votos = array();
	foreach ($encaminhamentos as $encaminhamento)
	{
		$encaminhamentos_votos[$encaminhamento->comment_ID] = 0;
	}
	
	foreach ($votos as $voto_comment)
	{
		$voto = get_comment_meta($voto_comment->comment_ID, 'delibera_votos', true);
		foreach ($voto as $voto_para)
		{
			$encaminhamentos_votos[$voto_para]++;
		}
	}
	$maisvotado = array(-1, -1);
	$iguais = array();
	
	foreach ($encaminhamentos_votos as $encaminhamentos_voto_key => $encaminhamentos_voto_valor)
	{
		if($encaminhamentos_voto_valor > $maisvotado[1])
		{
			$maisvotado[0] = $encaminhamentos_voto_key;
			$maisvotado[1] = $encaminhamentos_voto_valor;
			$iguais = array();
		}
		elseif($encaminhamentos_voto_valor == $maisvotado[1])
		{
			$iguais[] = $encaminhamentos_voto_key;
		}
		delete_comment_meta($encaminhamentos_voto_key, 'delibera_comment_numero_votos');
		add_comment_meta($encaminhamentos_voto_key, 'delibera_comment_numero_votos', $encaminhamentos_voto_valor, true);
	}
	
	if(count($iguais) > 0) // Empato
	{
		delibera_novo_prazo($postID);
	}
	else 
	{
		wp_set_object_terms($postID, 'comresolucao', 'situacao', false);
		update_comment_meta($maisvotado[0], 'delibera_comment_tipo', 'resolucao');
		add_post_meta($postID, 'data_resolucao', date('d/m/Y H:i:s'), true);
		////delibera_notificar_situacao($postID);
		if(has_action('votacao_concluida'))
		{
			do_action('votacao_concluida', $post);
		}
	}
}

function delibera_emvotacao($post)
{
	$opt = delibera_get_config();
	if($opt['relatoria'] == 'S')
	{
		if($opt['eleicao_relator'] == 'S')
		{
			
		}
	}
}

function delibera_novo_prazo($postID)
{
	$situacao = delibera_get_situacao($postID);
	$opts = delibera_get_config();
	switch ($situacao->slug)
	{
		case 'validacao':
			$inova_data = strtotime("+{$opts['dias_novo_prazo']} days");
			$nova_data = date("d/m/Y", $inova_data);
			$inova_datad = strtotime("+{$opts['dias_discussao']} days",$inova_data);
			$nova_datad = date("d/m/Y", $inova_datad);
			$inova_datavt = strtotime("+{$opts['dias_votacao']} days",$inova_datad);
			$nova_datavt = date("d/m/Y", $inova_datavt);
			$inova_datarel = strtotime("+{$opts['dias_votacao_relator']} days",$inova_datavt);
			$nova_datarel = date("d/m/Y", $inova_datarel);
			$inova_datar = strtotime("+{$opts['dias_relatoria']} days",$inova_datarel);
			$nova_datar = date("d/m/Y", $inova_datar);
			
			$events_meta['prazo_validacao'] = $opts['validacao'] == 'S' ? $nova_data : date('d/m/Y');
			$events_meta['prazo_discussao'] = $nova_datad;
			$events_meta['prazo_relatoria'] = $opts['relatoria'] == 'S' ? $nova_datar : date('d/m/Y');
			$events_meta['prazo_eleicao_relator'] = $opts['relatoria'] == 'S' && $opts['eleicao_relator'] == 'S' ? $nova_datarel : date('d/m/Y');
			$events_meta['prazo_votacao'] = $nova_datavt;
			
			foreach ($events_meta as $key => $value) // Buscar dados
			{
				if(get_post_meta($postID, $key, true)) // Se já existe
				{
					update_post_meta($postID, $key, $value); // Atualiza
				}
				else
				{
					add_post_meta($postID, $key, $value, true); // Se não cria
				}
			}
			delibera_del_cron($postID);
			delibera_criar_agenda($postID, $nova_data, $nova_datad, $nova_datavt, $nova_datar, $nova_datarel);
		break;
		case 'discussao':
		case 'relatoria':
			$inova_data = strtotime("+{$opts['dias_novo_prazo']} days");
			$nova_data = date("d/m/Y", $inova_data);
			update_post_meta($postID, 'prazo_discussao', $nova_data);
			$nova_eleicao_rel = false;
			$nova_relatoria = false;
			if($opts['relatoria'] == "S") // Adiciona prazo de relatoria se for necessário
			{
				$opts['dias_votacao'] += $opts['dias_relatoria'];
				if($opts['eleicao_relator'] == "S") // Adiciona prazo de vatacao relator se for necessário
				{
					$opts['dias_votacao'] += $opts['dias_votacao_relator'];
					$opts['dias_relatoria'] += $opts['dias_votacao_relator'];
					$nova_eleicao_rel = date("d/m/Y", strtotime("+{$opt['dias_votacao_relator']} days", $inova_data));
				}
				$nova_relatoria = date("d/m/Y", strtotime("+{$opts['dias_relatoria']} days", $inova_data));
			}
			$inova_data_votacao = strtotime("+{$opts['dias_votacao']} days", $inova_data);
			$nova_data_votacao = date("d/m/Y", $inova_data_votacao);
			update_post_meta($postID, 'prazo_votacao', $nova_data_votacao);
			delibera_del_cron($postID);
			delibera_criar_agenda($postID, false, $nova_data, $nova_data_votacao, $nova_relatoria, $nova_eleicao_rel);
		break;
		case 'emvotacao':
			$inova_data = strtotime("+{$opts['dias_novo_prazo']} days");
			$nova_data = date("d/m/Y", $inova_data);
			update_post_meta($postID, 'prazo_votacao', $nova_data);
			delibera_del_cron($postID);
			delibera_criar_agenda($postID, false, false, $nova_data);
		break;
	}
	//delibera_notificar_situacao($postID);
} 

function delibera_footer() {
	
    echo '<div id="mensagem-confirma-voto" style="display:none;"><p>'.__('Sua contribuição foi registrada no sistema','delibera').'</p></div>';
    
}
add_action('wp_footer', 'delibera_footer');


function delibera_loaded() {
	// load plugin translations
	load_plugin_textdomain('delibera', false, dirname(plugin_basename( __FILE__ )).'/lang');
}
add_action('plugins_loaded','delibera_loaded'); 

function delibera_get_plan_config()
{
	$plan = 'N';
	
	if( is_multisite() && get_current_blog_id() != 1 )
	{
		switch_to_blog(1);
		$opt = delibera_get_config();
		$plan = $opt['plan_restriction'];
		restore_current_blog();
	}
	else
	{
		$opt = delibera_get_config();
		$plan = $opt['plan_restriction'];
	}
	
	return $plan;
}


$conf = delibera_get_config();
if(array_key_exists('plan_restriction', $conf) && $conf['plan_restriction'] == 'S')
{
	require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_plan.php';
}

/*
 * Get page by slug
*/
function get_page_by_slug($page_slug, $output = OBJECT, $post_type = 'page' ) {
	global $wpdb;
	$page = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type= %s", $page_slug, $post_type ) );
	if ( $page )
		return get_page($page, $output);
	return null;
}

?>
