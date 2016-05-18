<?php

<?php
add_action( 'rest_api_init', 'slug_register_starship' );
function slug_register_starship() {
    register_rest_field( 'post',
        'starship',
        array(
            'get_callback'    => 'slug_get_starship',
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

/**
 * Get the value of the "situação" field
 *
 * @param array $object Details of current .
 * @param string $field_name Name of field.
 * @param WP_REST_Request $request Current request
 *
 * @return mixed
 */
function slug_get_starship( $object, $field_name, $request ) {
    return 	get_class(delibera_get_situacao($post->ID)?;
}


