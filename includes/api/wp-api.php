<?php
add_action('rest_api_init', 'slug_register_situacao');

function slug_register_situacao()
{
	register_rest_field('pauta', 'situacao', 
			array(
				'get_callback' => 'slug_get_situacao',
				'update_callback' => null,
				'schema' => null
			));
}

/**
 * Get the value of the "situação" field
 *
 * @param array $object
 *        	Details of current .
 * @param string $field_name
 *        	Name of field.
 * @param WP_REST_Request $request
 *        	Current request
 *        	
 * @return mixed
 */
function slug_get_situacao($object, $field_name, $request)
{
	return delibera_get_situacao( $object[ 'id' ] )->slug;
}


