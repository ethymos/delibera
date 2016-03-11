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
	 *
	 * @var array list of module flows
	 */
	protected $flows = array('emvotacao');
	
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
	 *
	 * {@inheritDoc}
	 * @see \Delibera\Modules\ModuleBase::initModule()
	 */
	public function initModule($post_id)
	{
		wp_set_object_terms($post_id, 'emvotacao', 'situacao', false);
		$this->newDeadline($post_id);
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
	 * {@inheritDoc}
	 * @see \Delibera\Modules\ModuleBase::generateDeadline()
	 */
	public function generateDeadline($options_plugin_delibera)
	{
		$dias_votacao = intval(htmlentities($options_plugin_delibera['dias_votacao']));
		
		$prazo_votacao_sugerido = strtotime("+$dias_votacao days", delibera_tratar_data(\Delibera\Flow::getLastDeadline('emvotacao')));
		
		return date('d/m/Y', $prazo_votacao_sugerido);
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
		$prazo_votacao = $this->generateDeadline($options_plugin_delibera);
		
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
	
	public function publishPauta($postID, $opt)
	{
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
	
	/**
	 *
	 * Faz a apuração dos votos e toma as devidas ações:
	 *    Empate: Mais prazo;
	 *    Vencedor: Marco com resolucao e marca o encaminhamento.
	 * @param interger $postID
	 * @param array $votos
	 */
	function computaVotos($postID, $votos = null)
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
				if (array_key_exists($voto_para, $encaminhamentos_votos))
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
			//wp_set_object_terms($postID, 'comresolucao', 'situacao', false);
			\Delibera\Flow::next($postID);
			
			update_comment_meta($maisvotado[0], 'delibera_comment_tipo', 'resolucao');
			add_post_meta($postID, 'data_resolucao', date('d/m/Y H:i:s'), true);
			////delibera_notificar_situacao($postID);
			if(has_action('votacao_concluida'))
			{
				do_action('votacao_concluida', $post);
			}
		}
	}
	
	/**
	 *
	 * Verifica se o número de votos é igual ao número de representantes para deflagar fim da votação
	 * @param integer $postID
	 */
	public function validaVotos($postID)
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
			$this->computaVotos($postID, $votos);
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
		if($situacao->slug == 'emvotacao')
		{
			$current = \Delibera\Flow::getCurrentModule($post_id);
			$current->computaVotos($post_id);
		}
	}
	
}
$DeliberaVote = new \Delibera\Modules\Vote();


