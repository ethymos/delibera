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
		
		//add_action('delibera_publish_pauta', array($this, 'publishPauta'), 10, 3);
		
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
		if(($now - 1) > 0 && array_key_exists($flow[$now - 1], $modules) && property_exists($modules[$flow[$now - 1]], 'getDeadline'))
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
	
		return $events_meta;
	}
	
}

global $DeliberaFlow;
$DeliberaFlow = new \Delibera\Flow();
