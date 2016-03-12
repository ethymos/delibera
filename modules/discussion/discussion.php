<?php

// PHP 5.3 and later:
namespace Delibera\Modules;

class Discussion extends \Delibera\Modules\ModuleBase
{
	
	/**
	 * List of of topic status
	 * @var array
	 */
	protected $situacao = array('discussao');
	
	/**
	 *
	 * @var array list of module flows
	 */
	protected $flows = array('discussao');
	
	/**
	 * Name of module deadline metadata
	 * @var String
	 */
	protected $prazo_meta = 'prazo_discussao';
	
	/**
	 * List of pair shotcode name => method
	 * @var array
	 */
	protected $shorcodes = array('delibera_lista_de_pautas' => 'replacePautas' );
	
	/**
	 * Register Tax for the module
	 */
	public function registerTax()
	{
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
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Delibera\Modules\ModuleBase::initModule()
	 */
	public function initModule($post_id)
	{
		wp_set_object_terms($post_id, 'discussao', 'situacao', false);
		$this->newDeadline($post_id);
	}
	
	/**
	 * Append configurations 
	 * @param array $opts
	 */
	public function getMainConfig($opts)
	{
		$opts['pauta_suporta_encaminhamento'] = 'S';
		$opts['dias_discussao'] = '5';
		return $opts;
	}
	
	/**
	 * Array to show on config page
	 * @param array $rows
	 */
	public function configPageRows($rows, $opt)
	{
		$rows[] = array(
			"id" => "dias_discussao",
			"label" => __('Dias para discussão da pauta:', 'delibera'),
			"content" => '<input type="text" name="dias_discussao" id="dias_discussao" value="'.htmlspecialchars_decode($opt['dias_discussao']).'"/>'
		);
		$rows[] = array(
			"id" => "pauta_suporta_encaminhamento",
			"label" => __('Pautas suportam sugestão de encaminhamento?', 'delibera'),
			"content" => '<input type="checkbox" name="pauta_suporta_encaminhamento" id="pauta_suporta_encaminhamento" value="S" '. ( htmlspecialchars_decode($opt['pauta_suporta_encaminhamento']) == "S" ? "checked='checked'" : "" ).'/>',
		);
		return $rows;
	}
	
	/**
	 * Label to apply to button
	 * @param unknown $situation
	 */
	public function situationButtonText($situation)
	{
		if($situation == 'discussao')
		{
			return __('Discutir', 'delibera');
		}
		
		return $situation;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Delibera\Modules\ModuleBase::generateDeadline()
	 */
	public function generateDeadline($options_plugin_delibera)
	{
		$dias_discussao = intval(htmlentities($options_plugin_delibera['dias_discussao']));
		
		$prazo_discussao_sugerido = strtotime("+$dias_discussao days", delibera_tratar_data(\Delibera\Flow::getLastDeadline('discussao')));
		return date('d/m/Y', $prazo_discussao_sugerido);
	}
	
	/**
	 * 
	 * Post Meta Fields display
	 * 
	 * @param \WP_Post $post
	 * @param array $custom post custom fields
	 * @param array $options_plugin_delibera Delibera options array
	 * @param WP_Term $situacao
	 * @param bool $disable_edicao
	 * 
	 */
	public function topicMeta($post, $custom, $options_plugin_delibera, $situacao, $disable_edicao)
	{
		$prazo_discussao = $this->generateDeadline($options_plugin_delibera);
		
		if(!($post->post_status == 'draft' ||
				$post->post_status == 'auto-draft' ||
				$post->post_status == 'pending'))
		{
			$prazo_discussao = array_key_exists("prazo_discussao", $custom) ?  $custom["prazo_discussao"][0] : $prazo_discussao;
		}
		
		?>
		<p>
			<label for="prazo_discussao" class="label_prazo_discussao"><?php _e('Prazo para Discussões','delibera') ?>:</label>
			<input <?php echo $disable_edicao ?> id="prazo_discussao" name="prazo_discussao" class="prazo_discussao widefat hasdatepicker" value="<?php echo $prazo_discussao; ?>"/>
		</p>
		<?php
		
	}
	
	public function publishPauta($postID, $opt)
	{
		$events_meta = array();
		$events_meta['delibera_numero_comments_encaminhamentos'] = 0;
		$events_meta['delibera_numero_comments_discussoes'] = 0;
		
		foreach ($events_meta as $key => $value) // Buscar dados
		{
			if(get_post_meta($postID, $key, true)) // Se já existe
			{
				update_post_meta($postID, $key, $value); // Atualiza
			}
			else
			{
				add_post_meta($postID, $key, $value, true); // Senão, cria
			}
		}
		
	}
	
	function checkPostData($erros, $opt, $autosave)
	{
		$value = $_POST['prazo_discussao'];
		$valida = delibera_tratar_data($value);
		if(!$autosave && ($valida === false || $valida < 1))
		{
			$erros[] = __("É necessário definir corretamente o prazo de discussão", "delibera");
		}
		return $erros;
	}
	
	public function replacePautas($matches)
	{
		$temp = explode(',', $matches[1]); // configurações da shorttag
	    $count = count($temp);
	
	    $param = array(); // TODO Tratar Parametros
	
	    $html = self::getPautas($param);
		$wp_posts = $html;
	    global $post;
	    $old = $post;
	    echo '<div id="lista-de-pautas">';
	    foreach ( $wp_posts as $wp_post )
	    {
			$post = $wp_post;
			include 'delibera_loop_pauta.php';
		}
		echo '</div>';
		$post = $old;
	
		return ''; // Retornar código da representação
				
	}
	
	public function savePostMetas($events_meta, $opt)
	{
		if(array_key_exists('prazo_discussao', $_POST))
		{
			$events_meta['prazo_discussao'] = $_POST['prazo_discussao'];
		}
		
		return $events_meta;
	}
	
	public function createPautaAtFront($opt)
	{
		if (trim($opt['data_fixa_nova_pauta_externa']) != '') {
			$prazo_discussao = DateTime::createFromFormat('d/m/Y', $opt['data_fixa_nova_pauta_externa']);
			$_POST['prazo_discussao'] = $prazo_discussao->format('d/m/Y');
		} else {
			$_POST['prazo_discussao'] = date('d/m/Y', strtotime ('+'.$opt['dias_discussao'].' DAYS'));
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Delibera\Modules\ModuleBase::deadline()
	 */
	public static function deadline($args)
	{
		$post_id = $args['post_ID'];
		$situacao = delibera_get_situacao($post_id);
		if($situacao->slug == 'discussao')
		{
			if(count(delibera_get_comments_encaminhamentos($post_id)) > 0)
			{
				\Delibera\Flow::next($post_id);
				
				if(has_action('delibera_discussao_concluida'))
				{
					do_action('delibera_discussao_concluida', $post_id);
				}
			}
			else
			{
				$current = \Delibera\Flow::getCurrentModule($post_id);
				$current->newDeadline($post_id, false);
			}
		}
	}
	
}
$DeliberaDiscussion = new \Delibera\Modules\Discussion();


