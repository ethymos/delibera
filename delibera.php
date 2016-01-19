<?php
/*
Plugin Name: Delibera
Plugin URI: http://www.ethymos.com.br
Description: O Plugin Delibera extende as funções padrão do WordPress e cria um ambiente de deliberação.
Version: 1.0.3
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

// Parse rewrite-rules

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_rewrite_rules.php';

// End Parse rewrite-rules

// pagina de configuracao do plugin
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_conf.php';

// Inicialização do plugin

require_once __DIR__.'/print/wp-print.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_admin_functions.php';

// setup plugin
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_setup.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_init.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_utils.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_comments.php';


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

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_cron.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_topic.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_curtir.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_discordar.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_seguir.php';

if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'mailer') && file_exists(__DIR__.DIRECTORY_SEPARATOR.'mailer'.DIRECTORY_SEPARATOR.'delibera_mailer.php'))
{
	//require_once __DIR__.DIRECTORY_SEPARATOR.'mailer'.DIRECTORY_SEPARATOR.'delibera_mailer.php';
}

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
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_notificar.php';

/**
 * Perfil do usuário
 */
require_once __DIR__.DIRECTORY_SEPARATOR.'delibera_user_painel.php';

// gerar relatório em um arquivo xls
require_once __DIR__ . DIRECTORY_SEPARATOR . 'delibera_relatorio.php';


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

    if ( ($timeline_found) && (!is_admin()) )
	{
		// enqueue here
		wp_enqueue_style('delibera_timeline_css',  WP_CONTENT_URL.'/plugins/delibera/timeline/delibera_timeline.css');
		wp_enqueue_script( 'delibera_timeline_js', WP_CONTENT_URL.'/plugins/delibera/timeline/js/delibera_timeline.js', array( 'jquery' ));
		wp_enqueue_script( 'jquery-ui-draggable');
	}

	return $posts;
}

add_filter('the_posts', 'delibera_the_posts'); // the_posts gets triggered before wp_head

// FIM Funções de conteudo

// Validadores

/**
 * Sempre que um usuário valida uma pauta
 * verifica se o número mínimo de validações foi
 * atingido e se sim muda a situação da pauta de
 * "emvotacao" para "discussao".
 *
 * @param unknown $post
 * @return null
 */
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
	if (get_post_type() == 'pauta' && !delibera_current_user_can_participate())
	{
		if (array_key_exists('delibera_validacao', $_REQUEST) || array_key_exists('delibera_encaminha', $_REQUEST) )
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
            if (isset($encaminhamentos_votos[$voto_para]))
            {
                $encaminhamentos_votos[$voto_para]++;
            } else {
                $encaminhamentos_votos[$voto_para] = 1;
            }
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

	// nao finaliza a votacao caso haja um empate, exceto quando o administrador clicar no botão "Forçar fim do prazo"
	if(count($iguais) > 0 && !(isset($_REQUEST['action']) && $_REQUEST['action'] == 'delibera_forca_fim_prazo_action')) // Empato
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

add_action('init', 'delibera_nova_pauta_create_action');
function delibera_nova_pauta_create_action(){
    $opt = delibera_get_config();
    if ($opt['criar_pauta_pelo_front_end'] == 'S' && is_user_logged_in() && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'delibera_nova_pauta')) {
        $title = $_POST['nova-pauta-titulo'];
        $content = $_POST['nova-pauta-conteudo'];
        $excerpt = $_POST['nova-pauta-resumo'];

        $pauta = array();
        $pauta['post_title'] = $title;
        $pauta['post_excerpt'] = $excerpt;
        $pauta['post_type'] = 'pauta';

        //Check if there is any file uploaded
        // If there is any, then ignore 'content' and use File.
        // else do add 'pauta' with the text content
        if(!empty($_FILES['post_pdf_contribution']['name'])) {
            // Setup the array of supported file types. In this case, it's just PDF.
            $supported_types = array('application/pdf');
            // Get the file type of the upload
            $pdf_contribution = wp_check_filetype(basename($_FILES['post_pdf_contribution']['name']));
            $sent_file_type = $pdf_contribution['type'];
            // Check if the type is supported. If not, throw an error.
            if (!in_array($sent_file_type, $supported_types)) {
                //TODO: Improve this message and avoid wp_die
                wp_die("O arquivo para web não é um PDF (formato permitido).");
            }
            $uploaded_file = wp_upload_bits($_FILES['pauta_pdf_contribution']['name'], null, file_get_contents($_FILES['pauta_pdf_contribution']['tmp_name']));
            if(isset($uploaded_file['error']) && $uploaded_file['error'] != 0) {
                wp_die('Erro ao salvar arquivo para Web. O erro foi: ' . $upload['error']);
            } else {
                $pauta['pauta_pdf_contribution'] = $uploaded_file['url'];
            }
        } else {
            $pauta['post_content'] = $content;
        }

        // para que a situação da pauta seja criada corretamente,
        // é necessário criar a pauta como rascunho para depois publicar no final desta função
        $pauta['post_status'] = 'draft';

        $pauta_id = wp_insert_post($pauta);

        if(is_int($pauta_id) && $pauta_id > 0){

            /* Os valores adicionados ao array $_POST são baseados no if da função delibera_save_post(),
             * comentado abaixo
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
            */

            if($opt['validacao'] == 'S'){
                $_POST['prazo_validacao'] = date('d/m/Y', strtotime ('+'.$opt['dias_validacao'].' DAYS'));
                $_POST['min_validacoes'] = $opt['minimo_validacao'];
            }

            if($opt['relatoria'] == 'S'){
                $_POST['prazo_relatoria'] = date('d/m/Y', strtotime ('+'.$opt['dias_relatoria'].' DAYS'));
                if($opt['eleicao_relator'] == 'S'){
                    $_POST['prazo_eleicao_relator'] = date('d/m/Y', strtotime ('+'.$opt['dias_votacao_relator'].' DAYS'));
                }
            }

			if (trim($opt['data_fixa_nova_pauta_externa']) != '') {
				$prazo_discussao = DateTime::createFromFormat('d/m/Y', $opt['data_fixa_nova_pauta_externa']);
				$_POST['prazo_discussao'] = $prazo_discussao->format('d/m/Y');
				$_POST['prazo_votacao'] = date('d/m/Y', strtotime ('+'.$opt['dias_votacao'].' DAYS', $prazo_discussao->getTimestamp()));
			} else {
				$_POST['prazo_discussao'] = date('d/m/Y', strtotime ('+'.$opt['dias_discussao'].' DAYS'));
				$_POST['prazo_votacao'] = date('d/m/Y', strtotime ('+'.$opt['dias_votacao'].' DAYS'));
			}

            // isto é necessário por causa do if da função delibera_publish_pauta()
            $_POST['publish'] = 'Publicar';
            $_POST['prev_status'] = 'draft';

            // verifica se todos os temas enviados por post são válidos
            $temas = get_terms('tema', array('hide_empty'    => true));
            $temas_ids = array();

            if(isset($_POST['tema']) && is_array($_POST['tema']))
                foreach($temas as $tema)
                    if(in_array ($tema->term_id, $_POST['tema']))
                        $temas_ids[] = $tema->term_id;

            // coloca os termos de temas no post
            wp_set_post_terms($pauta_id, $temas_ids, 'tema');

            // publica o post
            wp_publish_post($pauta_id);

            // isto serve para criar o slug corretamente,
            // já que no wp_insert_post não cria o slug quando o status é draft e o wp_publish_post tb não cria o slug
            unset($pauta['post_status']);
            $pauta['ID'] = $pauta_id;
            $pauta['post_name'] = sanitize_post_field('post_name', $title, $pauta_id, 'save');
            wp_update_post($pauta);

            // redireciona para a pauta criada
            $permalink = get_post_permalink($pauta_id);
            wp_safe_redirect($permalink);
            die;
        }
    }
}

// END - Interface pública para a criação de novas pautas

/**
 * Redireciona usuários que não são membros do site
 * onde o Delibera foi instalado para a página de pautas após o
 * login se a opção "Todos os usuários logados na rede podem participar?"
 * estiver habilitada.
 *
 * Se não fizermos esse redicionamento estes usuários serão redirecionados
 * para suas páginas de perfil fora do site onde o Delibera está instalado.
 *
add_filter('login_redirect', function($redirect_to, $request, $user) {
    $options = delibera_get_config();

    if ($options['todos_usuarios_logados_podem_participar'] == 'S' && !is_user_member_of_blog()) {
        return site_url('pauta');
    } else {
        return $redirect_to;
    }
}, 10, 3);
TODO mundo redirecionado para a lista de pauta, talvez uma nova opções */

/***
 * Verifica se as pautas devem suportar sugestão de encaminhamento ou se
 * as propostas entram apenas como opinião. Muito útil para consultas públicas.
 *
 * @return bool
 */
function delibera_pautas_suportam_encaminhamento()
{
    $options = delibera_get_config();

    if ( $options['pauta_suporta_encaminhamento'] == 'S' ) {
        return true;
    } else {
        return false;
    }
}

?>
