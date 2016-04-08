<?php 

// PHP 5.3 and later:
namespace Delibera;

/**
 * Manage cron activities of Delibera 
 *
 */
class Cron
{
	
	public function __construct()
	{
		add_action('admin_action_delibera_cron_action', array($this, 'action'));
		add_action('wp',  array($this, 'registry') );
		add_action('admin_action_delibera_cron_list', array($this, 'list'));
		add_action('init',  array($this, 'init') );
		add_action('wp_trash_post', array($this, 'del') );
		add_action('before_delete_post', array($this, 'del') );
	}
	
	/**
	 * Wordpress hook when cron is trigged
	 */
	public function action()
	{
		ignore_user_abort(true);
		define('DOING_DELIBERA_CRON', true);
		$crons =  get_option('delibera-cron', array());
		$new_crons = array();
		$dT = new \DateTime();
		$now = $dT->getTimestamp();
			
		$exec = 0;
		foreach ($crons as $key => $values)
		{
			if($key <= $now)
			{
				foreach ($values as $value)
				{
					$exec++;
					try
					{
						if(is_array($value['call_back']))
						{
							if(method_exists($value['call_back'][0], $value['call_back'][1]))
							{
								call_user_func($value['call_back'], $value['args']);
							}
						}
						else 
						{
							if(function_exists($value['call_back']))
							{
								call_user_func($value['call_back'], $value['args']);
							}
						}
					}
					catch (Exception $e)
					{
						$error = __('Erro no cron Delibera: ','delibera').$e->getMessage()."\n".$e->getCode()."\n".$e->getTraceAsString()."\n".$e->getLine()."\n".$e->getFile();
						wp_mail("jacson@ethymos.com.br", get_bloginfo('name'), $error);
						file_put_contents('/tmp/delibera_cron.log', $error, FILE_APPEND);
					}
				}
			}
			else
			{
				$new_crons[$key] = $values;
			}
		}
		update_option('delibera-cron', $new_crons);
		
		//wp_mail("jacson@ethymos.com.br", get_bloginfo('name'),"Foram executadas $exec tarefa(s)");
	}
	
	/**
	 * Registry a cron event check hourly
	 */
	function registry()
	{
		if ( !wp_next_scheduled( 'admin_action_delibera_cron_action' ) ) // if already been scheduled, will return a time 
		{
			wp_schedule_event(time(), 'hourly', 'admin_action_delibera_cron_action');
		}
	}
	
	/**
	 * Simple text list of cron jobs
	 */
	function cronList()
	{
		?>
		<div class="delibera-cron-table">
			<span><?php _e('Lista de tarefas agendadas', 'delibera'); ?></span>
			<pre><?php
			$crons =  get_option('delibera-cron', array());
			foreach ($crons as $key => $values)
			{
				echo "\n<br/>[$key]: ".date("d/m/Y H:i:s", $key);
				foreach ($values as $key2 => $value)
				{
					echo "\n<br/>\t&nbsp;&nbsp;&nbsp;[$key2]";
					foreach ($value as $ki => $item)
					{
						echo "\n<br/>\t\t&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[$ki]";
						if(is_array($item))
						{
							if(array_key_exists('post_ID', $item))
							{
								$post = get_post($item['post_ID']);
								$title = get_the_title($post);
								echo "\n$title";
							}
							echo "\n<br/>\t\t&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".print_r($item, true);
						}
						else
						{
							echo "\n<br/>\t\t&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$item";
						}
					}
				}
			}?>
			</pre>
		</div><?php
	}
	
	/**
	 * Add cron trigger
	 * @param int $data date that will occur
	 * @param function $call_back
	 * @param array $args
	 */
	public static function add($data, $call_back, $args)
	{
		$data = intval($data);
		if(is_int($data) && $data > 0)
		{
			$crons =  get_option('delibera-cron', array());
			if(!is_array($crons)) $crons = array();
	
			if(!array_key_exists($data, $crons))
			{
				$crons[$data] = array();
			}
			$crons[$data][] = array('call_back' => $call_back, "args" => $args);
			ksort($crons);
			if(!update_option('delibera-cron', $crons))
			{
				throw new \Exception("Cron not updated on $data, values:".print_r($crons[$data],true));
			}
		}
	}
	
	/**
	 * Remove a cron trigger
	 * @param int $postID
	 * @param function $callback function name or array($object, 'functio-name')
	 */
	static function del($postID, $callback = false)
	{
		$crons =  get_option('delibera-cron', array());
		if(!is_array($crons)) $crons = array();
	
		if( is_array($callback) )
		{
			$callback = $callback[0].'_'.$callback[1];
		}
	
		$crons_new = array();
		foreach($crons as $cron_data => $cron_value)
		{
			$new_cron = array();
			foreach ($cron_value as $call)
			{
				$cron_callback = $call['call_back'];
				if( is_array($call['call_back']) )
				{
					$cron_callback = $call['call_back'][0].'_'.$call['call_back'][1];
				}
				
				if(array_key_exists('post_id', $call['args'])) $call['args']['post_ID'] = $call['args']['post_id'];
				
				if($call['args']['post_ID'] != $postID || ($callback !== false && $callback != $cron_callback ))
				{
					$new_cron[] = $call;
				}
			}
			if(count($new_cron) > 0)
			{
				$crons_new[$cron_data] = $new_cron;
			}
		}
	
		ksort($crons_new);
		update_option('delibera-cron', $crons_new);
	}
	
	public function addMenu($base_page)
	{
		add_submenu_page($base_page, __('Delibera Cron','delibera'),__('Delibera Cron','delibera'), 'manage_options', 'delibera-cron', array($this, 'confPage'));
	}
	
	public function confPage()
	{
		$this->cronList();
	}

	public function init()
	{
		if(is_super_admin()) // TODO load after init
		{
			add_action('delibera_menu_itens', array($this, 'addMenu'));
		}
	}
	
}

$DeliberaCron = new \Delibera\Cron();

// Force cron exec
/*if(array_key_exists("delibera_cron_action", $_REQUEST) && !defined('DOING_DELIBERA_CRON'))
 {
 ignore_user_abort(true);
 define('DOING_DELIBERA_CRON', true);
 delibera_cron_action();
 }*/
//update_option('delibera-cron', array()); //delete cron
?>