<?php
/**
 * Manange topic flow
 */

// PHP 5.3 and later:
namespace Delibera;

class Flow
{
	
	protected $flow = array();
	protected $deadlines = array();
	
	public function __construct()
	{
		add_filter('delibera_get_main_config', array($this, 'getMainConfig'));
		add_filter('delivera_config_page_rows', array($this, 'configPageRows'), 10, 2);
		add_filter('delibera-pre-main-config-save', array($this, 'preMainConfigSave'));
		add_action('delibera_topic_meta', array($this, 'topicMeta'), 10, 5);
		add_filter('delibera_save_post_metas', array($this, 'savePostMetas'), 10, 2);
		add_action('delibera_publish_pauta', array($this, 'publishPauta'), 10, 3);
		add_filter('delibera_flow_list', array($this, 'filterFlowList'));
	}
	
	/**
	 * Append configurations 
	 * @param array $opts
	 */
	public function getMainConfig($opts)
	{
		$opts['delibera_flow'] = array('validacao', 'discussao', 'relatoria', 'emvotacao', 'comresolucao');
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
	
	/**
	 * get topic flow sequence
	 * @param string $post_id
	 */
	public function get($post_id = false)
	{
		$options_plugin_delibera = delibera_get_config();
		
		$default_flow = isset($options_plugin_delibera['delibera_flow']) ? $options_plugin_delibera['delibera_flow'] : array();
		$default_flow = apply_filters('delibera_flow_list', $default_flow);
		
		if($post_id == false)
		{
			$post_id = get_the_ID();
			if($post_id == false)
			{
				return $default_flow;
			}
		}
		
		if(array_key_exists($post_id, $this->flow)) return $this->flow[$post_id];
		
		$flow = get_post_meta($post_id, 'delibera_flow', true);
		if(is_array($flow) && count($flow) > 0)
		{
			$flow = apply_filters('delibera_flow_list', $flow);
			$this->flow[$post_id] = $flow;
			return $flow;
		}
		else 
		{
			$this->flow[$post_id] = $default_flow;
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
	
	public static function getLastDeadline($situacao, $post_id = false)
	{
		global $DeliberaFlow;
		
		if($post_id == false)
		{
			$post_id = get_the_ID();
		}
		$flow = $DeliberaFlow->get($post_id);
		$modules = $DeliberaFlow->getFlowModules();
		
		$now = array_search($situacao, $flow);
		if(($now - 1) >= 0 && array_key_exists($now - 1, $flow) && array_key_exists($flow[$now - 1], $modules) && method_exists($modules[$flow[$now - 1]], 'getDeadline'))
		{
			return $modules[$flow[$now - 1]]->getDeadline();
		}
		else 
		{
			return date('d/m/Y');
		}
	}
	
	public function savePostMetas($events_meta, $opt)
	{
		if(array_key_exists('delibera_flow', $_POST) )
		{
			$events_meta['delibera_flow'] = explode(',', trim($_POST['delibera_flow']));
		}
	
		$post_id = get_the_ID();
		$module = $this->getCurrentModule($post_id);
		$module->newDeadline($post_id);
		
		return $events_meta;
	}
	
	/**
	 * When the topic is published
	 * @param int $postID
	 * @param array $opt delibera configs
	 * @param bool $alterar has been altered
	 */
	public function publishPauta($postID, $opt, $alterar)
	{
		self::reabrirPauta($postID, false);
	}
	
	/**
	 * Return Current Flow Module
	 * @param int $post_id
	 * @return \Delibera\Modules\ModuleBase
	 */
	public static function getCurrentModule($post_id)
	{
		global $DeliberaFlow;
		
		$flow = $DeliberaFlow->get($post_id);
		$situacao = delibera_get_situacao($post_id);
		$current = array_search($situacao, $flow);
		$modules = $DeliberaFlow->getFlowModules(); //TODO cache?
		
		return $modules[$flow[$current]];
	}
	
	/**
	 * Go to the next module on flow
	 * @param string $post_id
	 */
	public static function next($post_id = false)
	{
		global $DeliberaFlow;
		
		$flow = $DeliberaFlow->get($post_id);
		$situacao = delibera_get_situacao($post_id);
		$current = array_search($situacao, $flow);
		$modules = $DeliberaFlow->getFlowModules(); //TODO cache?
		
		if(array_key_exists($current+1, $flow))
		{
			$modules[$flow[$current+1]]->initModule($post_id);
		}
		else 
		{
			//TODO the end?
		}
	}
	
	public static function forcarFimPrazo($post_id)
	{
		if(is_object($post_id)) $post_id = $post_id->ID;
		
		global $DeliberaFlow;
		
		$flow = $DeliberaFlow->get($post_id);
		$situacao = delibera_get_situacao($post_id);
		$current = array_search($situacao, $flow);
		$modules = $DeliberaFlow->getFlowModules(); //TODO cache?
		
		if(array_key_exists($current, $flow))
		{
			$modules[$flow[$current]]->deadline(array('post_id' => $post_id, 'prazo' => date('d/m/Y'), 'force' => true));
		}
		$DeliberaFlow->next($post_id);
		//delibera_notificar_situacao($postID); // Originaly comment, why?
	}
	
	public static function reabrirPauta($postID, $new_deadline = false)
	{
		global $DeliberaFlow;
		$flow = $DeliberaFlow->get($postID);
		$modules = $DeliberaFlow->getFlowModules();
		$modules[$flow[0]]->initModule($postID);
		if($new_deadline) delibera_novo_prazo($postID);
	}
	
	/**
	 * Check if module has bean remove or altered
	 * @param array $flows
	 * @return array
	 */
	public function filterFlowList($flow)
	{
		if(is_array($flow))
		{
			$modules = $this->getFlowModules();
			$flow = array_values(array_intersect($flow, array_keys($modules)));
			return $flow;
		}
		else 
		{
			return array();
		}
	}
	
}

global $DeliberaFlow;
$DeliberaFlow = new \Delibera\Flow();
