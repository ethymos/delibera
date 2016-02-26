<?php

// PHP 5.3 and later:
namespace Delibera\Modules;

use JMS\SecurityExtraBundle\Security\Util\String;
abstract class ModuleBase
{

	/**
	 * List of of topic status
	 * @var array
	 */
	abstract protected $situacao = array();
	
	/**
	 * Name of module deadline metadata
	 * @var String
	 */
	abstract protected $prazo_meta = 'prazo';
	
	/**
	 * List of pair shotcode name => method
	 * @var array
	 */
	protected $shortcodes = array();
	
	public function __construct()
	{
		add_filter('delibera_register_flow_module', array($this, 'registerFlowModule'));
		add_action('delibera_situacao_register', array($this, 'registerTax'));
		add_filter('delibera_get_main_config', array($this, 'getMainConfig'));
		add_filter('delivera_config_page_rows', array($this, 'configPageRows'), 10, 2);
		add_filter('delibera_situation_button_text', array($this, 'situationButtonText'));
		add_action('delibera_topic_meta', array($this, 'topicMeta'), 10, 5);
		add_action('delibera_publish_pauta', array($this, 'publishPauta'), 10, 3);
		add_filter('delibera_check_post_data', array($this, 'checkPostData'), 10, 3);
		add_filter('delibera_save_post_metas', array($this, 'savePostMetas'), 10, 2);
		add_action('delibera_create_pauta_frontend', array($this, 'createPautaAtFront'));
		
		foreach ($this->shortcodes as $name => $function)
		{
			add_shortcode( $name,  array( $this, $function ));
		}
	}
	
	/**
	 * Register situacao objects for flow treat
	 * @param array $modules
	 */
	public function registerFlowModule($modules)
	{
		foreach ($this->situacao as $situacao)
		{
			$modules[$situacao] = $this;
		}
		return $modules;
	}
	
	/**
	 * Register Tax for the module
	 */
	abstract public function registerTax();
	
	/**
	 * Append configurations
	 * @param array $opts
	 */
	abstract public function getMainConfig($opts);
	
	/**
	 * Array to show on config page
	 * @param array $rows
	 */
	abstract public function configPageRows($rows, $opt);
	
	/**
	 * Label to apply to button
	 * @param unknown $situation
	 */
	abstract public function situationButtonText($situation);
	
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
	abstract public function topicMeta($post, $custom, $options_plugin_delibera, $situacao, $disable_edicao);
	
	/**
	 * When the topic is published
	 * @param int $postID
	 * @param array $opt delibera configs
	 * @param bool $alterar has been altered
	 */
	abstract public function publishPauta($postID, $opt, $alterar);
	
	/**
	 * Validate topic required data
	 * @param array $erros erros report array
	 * @param array $opt Delibera configs
	 * @param bool $autosave is autosave?
	 * @return array erros report array append if needed
	 */
	abstract public function checkPostData($erros, $opt, $autosave);
	
	/**
	 * Save topic metadata
	 * @param array $events_meta
	 * @param array $opt Delibera configs
	 *
	 * @return array events_meta to be save on the topic
	 */
	abstract public function savePostMetas($events_meta, $opt);
	
	/**
	 * Treat postback of frotend topic
	 * @param array $opt Delibera configs
	 */
	abstract public function createPautaAtFront($opt);
	
	/**
	 * Return this module deadline for the current post
	 * @param int $post_id
	 * @return mixed|string deadline date
	 */
	public function getDeadline($post_id = false)
	{
		if($post_id == false)
		{
			$post_id = get_the_ID();
		}
		if(is_array($this->prazo_meta))
		{
			$situacao = delibera_get_situacao($post_id);
			return array_key_exists($situacao, $this->prazo_meta) ? get_post_meta($post_id, $this->prazo_meta[$situacao], true) : date('d/m/Y');
		}
		return get_post_meta($post_id, $this->prazo_meta, true);
	}
	
	/**
	 *
	 * Retorn topic at module situation
	 * @param array $filtro
	 */
	public static function getPautas($filtro = array())
	{
		return delibera_get_pautas_em($filtro, $this->situacao);
	}
	
}