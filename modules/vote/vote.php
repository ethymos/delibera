<?php

// PHP 5.3 and later:
namespace Delibera\Modules;

class Vote extends \Delibera\Modules\ModuleBase
{
	
	/**
	 * List of of topic status
	 * @var array
	 */
	protected $situacao = array('emvotacao');
	
	/**
	 * Name of module deadline metadata
	 * @var String
	 */
	protected $prazo_meta = 'prazo_votacao';
	
	/**
	 * Register Tax for the module
	 */
	public function registerTax()
	{
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
	}
	
	/**
	 * Append configurations 
	 * @param array $opts
	 */
	public function getMainConfig($opts)
	{
		$opts['dias_votacao'] = '5';
		return $opts;
	}
	
	/**
	 * Array to show on config page
	 * @param array $rows
	 */
	public function configPageRows($rows, $opt)
	{
		$rows[] = array(
			"id" => "dias_votacao",
			"label" => __('Dias para votação de encaminhamentos:', 'delibera'),
			"content" => '<input type="text" name="dias_votacao" id="dias_votacao" value="'.htmlspecialchars_decode($opt['dias_votacao']).'"/>'
		);
		return $rows;
	}
	
	/**
	 * Label to apply to button
	 * @param unknown $situation
	 */
	public function situationButtonText($situation)
	{
		if($situation == 'emvotacao')
		{
			return __('Votar', 'delibera');
		}
		
		return $situation;
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
		$now = strtotime(date('Y/m/d')." 11:59:59");
		
		$dias_votacao = /*$dias_discussao +*/ intval(htmlentities($options_plugin_delibera['dias_votacao']));
		
		$prazo_votacao_sugerido = strtotime("+$dias_votacao days", $now);
		
		$prazo_votacao = date('d/m/Y', $prazo_votacao_sugerido);
		
		if(!($post->post_status == 'draft' ||
			$post->post_status == 'auto-draft' ||
			$post->post_status == 'pending'))
		{
			
			$prazo_votacao = array_key_exists("prazo_votacao", $custom) ?  $custom["prazo_votacao"][0] : $prazo_votacao;
		}
		
		?>
		<p>
			<label for="prazo_votacao" class="label_prazo_votacao"><?php _e('Prazo para Votações','delibera') ?>:</label>
			<input <?php echo $disable_edicao ?> id="prazo_votacao" name="prazo_votacao" class="prazo_votacao widefat hasdatepicker" value="<?php echo $prazo_votacao; ?>"/>
		</p>
		<?php
		
	}
	
	public function publishPauta($postID, $opt, $alterar)
	{
		if(!array_key_exists('relatoria', $opt) || $opt['relatoria'] == 'S' && $opt['delibera_flow'][0] == 'relatoria' )
		{
			if(!$alterar)
			{
				if($opt['eleicao_relator'] == 'S')
				{
					wp_set_object_terms($postID, 'eleicaoredator', 'situacao', false);
				}
				else 
				{
					wp_set_object_terms($postID, 'relatoria', 'situacao', false);
				}
			}
		
		}
		
		$events_meta = array();
		$events_meta['delibera_numero_comments_votos'] = 0;
		
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
		/*
		 * Faz agendamento das datas para seguir passos
		 * 1) Excluir ao atingir data de validação se não foi validade
		 * 2) Iniciar votação se tiver encaminhamento, ou novo prazo, caso contrário
		 * 3) Fim da votação
		 * 
		 */ 
		$prazo_votacao = get_post_meta($postID, 'prazo_votacao', true);
		
		if( ! empty($prazo_votacao) )
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
	
	function checkPostData($erros, $opt, $autosave)
	{
		$value = $_POST['prazo_votacao'];
		$valida = delibera_tratar_data($value);
		if(!$autosave && ($valida === false || $valida < 1))
		{
			$erros[] = __("É necessário definir corretamente o prazo para votação", "delibera");
		}
		return $erros;
	}
	
	/**
	 *
	 * Retorna pautas em Validação
	 * @param array $filtro
	 */
	public static function getEmvotacao($filtro = array())
	{
		return self::getPautas($filtro);
	}
	
	public function savePostMetas($events_meta, $opt)
	{
		if(array_key_exists('prazo_votacao', $_POST))
		{
			$events_meta['prazo_votacao'] = $_POST['prazo_votacao'];
		}
		
		return $events_meta;
	}
	
	public function createPautaAtFront($opt)
	{
		if (trim($opt['data_fixa_nova_pauta_externa']) != '') {
			$prazo_discussao = DateTime::createFromFormat('d/m/Y', $opt['data_fixa_nova_pauta_externa']);
			$_POST['prazo_votacao'] = date('d/m/Y', strtotime ('+'.$opt['dias_votacao'].' DAYS', $prazo_discussao->getTimestamp()));
		} else {
			$_POST['prazo_votacao'] = date('d/m/Y', strtotime ('+'.$opt['dias_votacao'].' DAYS'));
		}
	}
	
}
$DeliberaVote = new \Delibera\Modules\Vote();


