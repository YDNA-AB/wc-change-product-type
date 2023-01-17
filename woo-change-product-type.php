<?php
/*
 * Plugin Name: Change Product Type WooCommerce
 * Description: Allows changing product type from simple to variation through the REST API.
 * Version: 0.0.1
 * Author: YDNA AB
 * URL: https://ydna.se/integrations/woo/plugins
*/

$name_space = 'wc/v3/ydna';

add_action( 'rest_api_init', 'create_product_type_route' );

function create_product_type_route() {
    register_rest_route( $name_space, 'change-product-type', array(
        'methods' => 'POST',
        'permission_callback' => 'authenticate_request',
        'callback' => 'change_product_type_callback',
) );
}

function authenticate_request( WP_REST_Request $request ) {
	if ( ! current_user_can( 'edit_posts') ) {
		return new WP_Error( 'invalid_consumer_key', 'Consumer key or secret is missing or wrong', array( 'status' => 403 ) );
	}
    return true;
}

function change_product_type_callback( WP_REST_Request $request ) {
    $body = $request->get_body();
    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error( 'invalid_json', 'The request body is not a valid JSON format', array( 'status' => 400 ) );
    }

    if ( !isset( $data['products'] ) || !is_array( $data['products'] ) ) {
        return new WP_Error( 'invalid_json', 'The request body does not contain a products array', array( 'status' => 400 ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'posts';
    $update_count = 0;
    foreach ( $data['products'] as $product ) {
        $product_id = $product['product_id'];
        $parent_id = $product['parent_id'];

        $update_count += $wpdb->update(
            $table_name,
            array( 
                'post_parent' => $parent_id,
                'post_type' => 'product_variation' ),
            array(
                'ID' => $product_id,
                'post_type' => 'product' )
        );
    }

    if ( false === $update_count ) {
        return new WP_Error( 'update_query_failed', 'Update query failed' . $wpdb->last_error, array( 'status' => 500 ) );
    }

    if ( count($data['products']) !== $update_count ) {
        return new WP_Error( 'update_query_failed', 'Update query failed, the number of updated rows is not equal to the number of products', array( 'status' => 500 ) );
    }
    return true;
}

?>
