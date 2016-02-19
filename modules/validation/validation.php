<?php

// PHP 5.3 and later:
namespace Delibera\Modules;

class DeliberaValidation
{
	
	public function __construct()
	{
		add_action('delibera_situacao_register', array($this, 'registerTax'));
		add_filter('delibera_get_main_config', array($this, 'getMainConfig'));
		add_filter('delivera_config_page_rows', array($this, 'configPageRows'), 10, 2);
		add_filter('delibera_situation_button_text', array($this, 'situationButtonText'));
		add_action('delibera_topic_meta', array($this, 'topicMeta'), 10, 5);
	}
	
	/**
	 * Register Tax for the module
	 */
	public function registerTax()
	{
		if(term_exists('validacao', 'situacao', null) == false)
		{
			delibera_insert_term('Proposta de Pauta', 'situacao',
				array(
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
			delibera_insert_term('Pauta Recusada', 'situacao',
				array(
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
	
	/**
	 * Append configurations 
	 * @param array $opts
	 */
	public function getMainConfig($opts)
	{
		$opts['minimo_validacao'] = '10';
		$opts['dias_validacao'] = '5';
		$opts['validacao'] = 'S';
		return $opts;
	}
	
	/**
	 * Array to show on config page
	 * @param array $rows
	 */
	public function configPageRows($rows, $opt)
	{
		$rows[] = array(
				"id" => "validacao",
				"label" => __('É necessário validação das pautas?', 'delibera'),
				"content" => '<input type="checkbox" name="validacao" id="validacao" value="S" '.(htmlspecialchars_decode($opt['validacao']) == 'S' ? 'checked="checked"' : '').' />'
		);
		$rows[] = array(
				"id" => "minimo_validacao",
				"label" => __('Mínimo de validações para uma pauta:', 'delibera'),
				"content" => '<input type="text" name="minimo_validacao" id="minimo_validacao" value="'.htmlspecialchars_decode($opt['minimo_validacao']).'"/>'
		);
		
		$rows[] = array(
				"id" => "dias_validacao",
				"label" => __('Dias para validação da pauta:', 'delibera'),
				"content" => '<input type="text" name="dias_validacao" id="dias_validacao" value="'.htmlspecialchars_decode($opt['dias_validacao']).'"/>'
		);
		return $rows;
	}
	
	/**
	 * Label to apply to button
	 * @param unknown $situation
	 */
	public function situationButtonText($situation)
	{
		if($situation == 'validacao')
		{
			return __('Votar', 'delibera');
		}
		
		return $situation;
	}
	
	public function topicMeta($post, $custom, $options_plugin_delibera, $situacao, $disable_edicao)
	{
		$validacoes = array_key_exists("numero_validacoes", $custom) ?  $custom["numero_validacoes"][0] : 0;
		
		$min_validacoes = array_key_exists("min_validacoes", $custom) ?  $custom["min_validacoes"][0] : htmlentities($options_plugin_delibera['minimo_validacao']);
		
		$dias_validacao = intval(htmlentities($options_plugin_delibera['dias_validacao']));
		
		$now = strtotime(date('Y/m/d')." 11:59:59");
		
		$prazo_validacao_sugerido = strtotime("+$dias_validacao days", $now);
		
		$prazo_validacao = date('d/m/Y', $prazo_validacao_sugerido);
		
		if(!($post->post_status == 'draft' ||
				$post->post_status == 'auto-draft' ||
				$post->post_status == 'pending'))
		{
			$prazo_validacao = array_key_exists("prazo_validacao", $custom) ?  $custom["prazo_validacao"][0] : $prazo_validacao;
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
				<input <?php echo $disable_edicao ?> id="prazo_validacao" name="prazo_validacao" class="prazo_validacao widefat hasdatepicker" value="<?php echo $prazo_validacao; ?>"/>
			</p>
		<?php
		}
		
	}
	
	public function delibera_publish_pauta()
	{
		
	}
	
}
$DeliberaValidation = new DeliberaValidation();

