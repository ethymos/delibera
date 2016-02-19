<?php
/**
 * Manange topic flow
 */

// PHP 5.3 and later:
namespace Delibera;

class DeliberaFlow
{
	public function __construct()
	{
		add_filter('delibera_get_main_config', array($this, 'getMainConfig'));
		add_filter('delivera_config_page_rows', array($this, 'configPageRows'), 10, 2);
		add_filter('delibera-pre-main-config-save', array($this, 'preMainConfigSave'));
		
	}
	
	/**
	 * Append configurations 
	 * @param array $opts
	 */
	public function getMainConfig($opts)
	{
		$opts['flow'] = array('validacao', 'discussao', 'elegerelator', 'relatoria', 'emvotacao', 'comresolucao');
		return $opts;
	}
	
	/**
	 * Array to show on config page
	 * @param array $rows
	 */
	public function configPageRows($rows, $opt)
	{
		$rows[] = array(
				"id" => "flow",
				"label" => __('Fluxo padrão de uma pauta?', 'delibera'),
				"content" => '<input type="text" name="flow" id="flow" value="'.implode(',', array_map("htmlspecialchars", $opt['flow']) ).'"/>'
		);
		return $rows;
	}
	
	/**
	 * Filter main config option before save
	 * @param unknown $opts
	 */
	public function preMainConfigSave($opts)
	{
		if(array_key_exists('flow', $opts) && !is_array($opts['flow']))
		{
			$opts['flow'] = explode(',', trim($opts['flow']));
		}
		return $opts;
	}
	
}


$DeliberaFlow = new DeliberaFlow();
