<?php

if ( ! function_exists( 'et_builder_include_categories_delibera_form_option_radio' ) ) :
function et_builder_include_categories_delibera_form_option_radio( $args = array() ) {

	$defaults = apply_filters( 'et_builder_include_categories_delibera_form_defaults', array (
		'use_terms' => true,
		'term_name' => 'tema',
		'post_type'=>'pauta'
	) );

	$args = wp_parse_args( $args, $defaults );

	$output = "\t" . "<% var et_pb_include_categories_temp = typeof et_pb_include_categories !== 'undefined' ? et_pb_include_categories.split( ',' ) : []; %>" . "\n";

	if ( $args['use_terms'] ) {
		$cats_array = get_terms( $args['term_name'], array( 'hide_empty' => false) );
	} else {
		$cats_array = get_categories( apply_filters( 'et_builder_get_categories_args', 'hide_empty=0' ) );
		//$cats_array = get_categories();
	}

	if ( empty( $cats_array ) ) {
		$output = '<p>' . esc_html__( "You currently don't have any projects assigned to a category.", 'et_builder' ) . '</p>';
	}

	foreach ( $cats_array as $category ) {
		$contains = sprintf(
				'<%%= _.contains( et_pb_include_categories_temp, "%1$s" ) ? checked="checked" : "" %%>',
				esc_html( $category->term_id )
				);

		$output .= sprintf(
				'%4$s<label><input type="checkbox" name="et_pb_include_categories" value="%1$s"%3$s> %2$s </label><br/>',
				esc_attr( $category->term_id ),
				esc_html( $category->name ),
				$contains,
				"\n\t\t\t\t\t"
				);
	}

	$output = '<div id="et_pb_include_categories">' . $output . '</div>';

	return $output;

	//return apply_filters( 'et_builder_include_categories_option_html', $output );
}
endif;

if ( ! function_exists( 'et_builder_include_categories_delibera_option' ) ) :
function et_builder_include_categories_delibera_option( $args = array() ) {

	$defaults = apply_filters( 'et_builder_include_categories_delibera_defaults', array (
		'use_terms' => true,
		'term_name' => 'tema',
		'post_type'=>'pauta'
	) );

	$args = wp_parse_args( $args, $defaults );

	$output = "\t" . "<% var et_pb_include_categories_temp = typeof et_pb_include_categories !== 'undefined' ? et_pb_include_categories.split( ',' ) : []; %>" . "\n";

	if ( $args['use_terms'] ) {
		$cats_array = get_terms( $args['term_name'], array( 'hide_empty' => false) );
	} else {
		$cats_array = get_categories( apply_filters( 'et_builder_get_categories_args', 'hide_empty=0' ) );
		//$cats_array = get_categories();
	}

	if ( empty( $cats_array ) ) {
		$output = '<p>' . esc_html__( "You currently don't have any projects assigned to a category.", 'et_builder' ) . '</p>';
	}

	foreach ( $cats_array as $category ) {
		$contains = sprintf(
				'<%%= _.contains( et_pb_include_categories_temp, "%1$s" ) ? checked="checked" : "" %%>',
				esc_html( $category->term_id )
				);

		$output .= sprintf(
				'%4$s<label><input type="checkbox" name="et_pb_include_categories" value="%1$s"%3$s> %2$s </label><br/>',
				esc_attr( $category->term_id ),
				esc_html( $category->name ),
				$contains,
				"\n\t\t\t\t\t"
				);
	}

	$output = '<div id="et_pb_include_categories">' . $output . '</div>';

	return $output;

	//return apply_filters( 'et_builder_include_categories_option_html', $output );
}
endif;

if(! function_exists('et_builder_pauta_delibera_option'))
{
	function et_builder_pauta_delibera_option($args = array())
	{
		$defaults = apply_filters(
				'et_builder_include_categories_delibera_defaults', 
				array(
					'post_type' => 'pauta'
				)
		);
		
		$args = wp_parse_args($args, $defaults);
		
		$output = "\t" . "<% var et_pb_pautas_temp = typeof et_pb_pautas !== 'undefined' ? et_pb_pautas : ''; %>" . "\n";
		
		$pautas = get_posts(array(
			'post_type' 		=> $args['post_type'],
			'posts_per_page'	=> -1,
			'orderby'			=> 'date',
			'order'				=> 'DESC',
			'post_status'       => 'publish',
		));
		
		foreach($pautas as $pauta)
		{
			$contains = sprintf(
					'<%%= _.contains( et_pb_pautas_temp, "%1$s" ) ? checked="checked" : "" %%>', 
					esc_html($category->term_id));
			
			$output .= sprintf(
					'%4$s<label><input type="checkbox" name="et_pb_pautas" value="%1$s"%3$s> %2$s </label><br/>', 
					esc_attr($category->term_id), esc_html($category->name), 
					$contains, "\n\t\t\t\t\t");
		}
		
		$output = '<div id="et_pb_include_categories">' . $output . '</div>';
		
		return $output;
		
		// return apply_filters( 'et_builder_include_categories_option_html',
	// $output );
	}
}