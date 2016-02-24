<?php
/**
 * Manange topic flow
 */

// PHP 5.3 and later:
namespace Delibera;

class Flow
{
	public function __construct()
	{
		add_filter('delibera_get_main_config', array($this, 'getMainConfig'));
		add_filter('delivera_config_page_rows', array($this, 'configPageRows'), 10, 2);
		add_filter('delibera-pre-main-config-save', array($this, 'preMainConfigSave'));
		add_action('delibera_topic_meta', array($this, 'topicMeta'), 10, 5);
		add_filter('delibera_save_post_metas', array($this, 'savePostMetas'), 10, 2);
		
		add_action('delibera_publish_pauta', array($this, 'publishPauta'), 10, 3);
		
	}
	
	/**
	 * Append configurations 
	 * @param array $opts
	 */
	public function getMainConfig($opts)
	{
		$opts['delibera_flow'] = array('validacao', 'discussao', 'elegerelator', 'relatoria', 'emvotacao', 'comresolucao');
		return $opts;
	}
	
	/**
	 * Array to show on config page
	 * @param array $rows
	 */
	public function configPageRows($rows, $opt)
	{
		$rows[] = array(
				"id" => "delibera_flow",
				"label" => __('Fluxo padrão de uma pauta?', 'delibera'),
				"content" => '<input type="text" name="delibera_flow" id="delibera_flow" value="'.implode(',', array_map("htmlspecialchars", $opt['delibera_flow']) ).'"/>'
		);
		return $rows;
	}
	
	/**
	 * Filter main config option before save
	 * @param unknown $opts
	 */
	public function preMainConfigSave($opts)
	{
		if(array_key_exists('delibera_flow', $opts) && !is_array($opts['delibera_flow']))
		{
			$opts['delibera_flow'] = explode(',', trim($opts['delibera_flow']));
		}
		return $opts;
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
	 */
	public function topicMeta($post, $custom, $options_plugin_delibera, $situacao, $disable_edicao)
	{
		$flow = implode(',', array_map("htmlspecialchars", $this->get($post->ID)) ); 
		?>
			<p>
				<label for="delibera_flow" class="label_flow"><?php _e('Fluxo da Pauta','delibera'); ?>:</label>
				<input <?php echo $disable_edicao ?> id="delibera_flow" name="delibera_flow" class="delibera_flow widefat" value="<?php echo $flow; ?>"/>
			</p>
		<?php
	}
	
	public function get($post_id = false)
	{
		$options_plugin_delibera = delibera_get_config();
		$default_flow = isset($options_plugin_delibera['delibera_flow']) ? $options_plugin_delibera['delibera_flow'] : array();
		
		if($post_id == false)
		{
			$post_id = get_the_ID();
			if($post_id == false)
			{
				return $default_flow;
			}
		}
		
		$flow = get_post_meta($post_id, 'delibera_flow', true);
		if(is_array($flow) && count($flow) > 0)
		{
			return $flow;
		}
		else 
		{
			return $default_flow;
		}
	}
	
	/**
	 * List of Modules and each situation for get information about the module, like deadline
	 */
	public function getFlowModules()
	{
		$modules = array();
		/* Modules need to register to make part of flow
		 * Form: $modules['situacao'] = ModuleObject;
		 */
		$modules = apply_filters('delibera_register_flow_module', $modules);
		return $modules;
	}
	
	public function getLastDeadline($situacao, $post_id = false)
	{
		if($post_id == false)
		{
			$post_id = get_the_ID();
		}
		$flow = $this->get($post_id);
		$modules = $this->getFlowModules();
		
		$now = array_search($situacao, $flow);
		if(($now - 1) > 0)
		{
			$modules[$flow[$now - 1]]->getDeadline();
		}
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
	public function criarAgenda($postID, $prazo_validacao, $prazo_discussao, $prazo_votacao, $prazo_relatoria = false, $prazo_eleicao_relator = false)
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
	
	public function savePostMetas($events_meta, $opt)
	{
		if(array_key_exists('delibera_flow', $_POST) )
		{
			$events_meta['delibera_flow'] = explode(',', trim($_POST['delibera_flow']));
		}
	
		return $events_meta;
	}
	
	public function publishPauta($postID, $opt, $alterar)
	{
		$prazo_validacao = get_post_meta($postID, 'prazo_validacao', true);
		$prazo_discussao =  get_post_meta($postID, 'prazo_discussao', true);
		$prazo_relatoria =  get_post_meta($postID, 'prazo_relatoria', true);
		$prazo_eleicao_relator =  get_post_meta($postID, 'prazo_eleicao_relator', true);
		$prazo_votacao =  get_post_meta($postID, 'prazo_votacao', true);
		
		
		delibera_criar_agenda(
			$post->ID,
			$prazo_validacao,
			$prazo_discussao,
			$prazo_votacao,
			$opt['relatoria'] == 'S' ? $prazo_relatoria : false,
			$opt['relatoria'] == 'S' && $opt['eleicao_relator'] == 'S' ? $prazo_eleicao_relator : false
		);
		// discussao
		/*else
		{
			if (! $alterar)
			{
				wp_set_object_terms ( $post->ID, 'discussao', 'situacao', false );
			}
			delibera_criar_agenda ( $post->ID, false, $prazo_discussao, $prazo_votacao, $opt ['relatoria'] == 'S' ? $prazo_relatoria : false, $opt ['relatoria'] == 'S' && $opt ['eleicao_relator'] == 'S' ? $prazo_eleicao_relator : false );
		}*/
	}
	
}


$DeliberaFlow = new \Delibera\Flow();
