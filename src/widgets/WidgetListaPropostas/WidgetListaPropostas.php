<?php
class WidgetListaPropostas extends WP_Widget
{
	
	public function getDefaults()
	{
		return array(
			'number' => 5,
			'title' => __( 'Delibera Lista de Propostas', 'delibera' ),
			'show_summary' => 1,
			'show_author' => 1,
			'show_date' => 1
		);
	}
	
	public function __construct()
	{
		parent::__construct(
				'WidgetListaPropostas', // Base ID
				'Delibera Lista de Propostas', // Name
				array( 'description' => __( 'Listas as Propostas de Pauta do Delibera', 'delibera' ), ) // Args
		);
	}
	
	public function widget( $args, $instance )
	{
		$param = array(); //TODO parametros do formulário
		
		$wp_posts = delibera_get_propostas($param);
		
		include 'view.php';
	}
	
	public function form( $instance )
	{
		$default_inputs = $this->getDefaults();
		if(!is_array($instance))
		{
			$instance = $default_inputs;
		}
		print_r($instance);
		
		$args = $instance; 
		
		extract( $args );
		
		$number = esc_attr( $number );
		$title  = esc_attr( $title );
		$items  = (int) $items;
		if ( $items < 1 || 20 < $items )
			$items  = 10;
		$show_summary   = (int) $show_summary;
		$show_author    = (int) $show_author;
		$show_date      = (int) $show_date;
		
		if ( !empty($error) )
			echo '<p class="widget-error"><strong>' . sprintf( __('RSS Error: %s'), $error) . '</strong></p>';
		
		include 'form.php';
	}
	
	public function update( $new_instance, $old_instance )
	{
		$old_instance = is_array($old_instance) ? $old_instance : $this->getDefaults();
		$new_instance = array_map(
			function ($item)
			{
				return strip_tags( $item );
			}, $new_instance
		);
		
		$defaults = $this->getDefaults();
		foreach ($defaults as $key => $default)
		{
			if(!array_key_exists($key, $new_instance) && in_array($default, array(true,false,1,0), true))
			{
				$new_instance[$key] = false;
			}
		}
		return array_merge($old_instance, $new_instance);
	}
	
}
?>