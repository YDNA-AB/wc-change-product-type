<?php
/*
 * Plugin Name: Change Product Type WooCommerce
 * Description: Allows changing product type from simple to variation (and vice versa) through the REST API.
 * Version: 0.0.4
 * Author: YDNA AB
 * URL: https://ydna.se/integrations/woo/plugins
*/

add_action( 'rest_api_init', 'ydna_api_init' );

function ydna_api_init() {
    $ydna_name_space = 'wc/v3/ydna';

    register_rest_route( $ydna_name_space, 'change-product-type', array(
        'methods' => 'POST',
        'permission_callback' => 'authenticate_request',
        'callback' => 'change_product_type_callback',
    ) );

    register_rest_route( $ydna_name_space, 'change-product-type-to-simple', array(
        'methods' => 'POST',
        'permission_callback' => 'authenticate_request',
        'callback' => 'change_product_type_to_simple_callback',
    ) );
}

function authenticate_request( WP_REST_Request $request ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return new WP_Error( 'invalid_consumer_key', 'Consumer key or secret is missing or wrong', array( 'status' => 403 ) );
	}
    return true;
}

function change_product_type_callback( WP_REST_Request $request ) {
    // Read and parse the body

    $body = $request->get_body();
    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error( 'invalid_json', 'The request body is not a valid JSON format', array( 'status' => 400 ) );
    }

    // Validate request body

    if ( !isset( $data['products'] ) || !is_array( $data['products'] ) ) {
        // @TODO Make sure that the error code is correct.
        return new WP_Error( 'invalid_json', '"products" is missing in the request body (or the value is of an invalid type).', array( 'status' => 400 ) );
    }

    foreach ( $data['products'] as $index => $product ) {
        // Make sure all fields are defined
        // @TODO Make sure they are the correct type(s) as well!

        $missing_product_id = !isset($product['product_id']);
        $missing_parent_id  = !isset($product['parent_id']);

        if ( $missing_product_id || $missing_parent_id ) {
            $error_str = '';
            if ( $missing_product_id ) { $error_str .= '"product_id"'; }
            if ( $missing_parent_id) {
                if ( $error_str != '' ) { $error_str .= ' and '; }
                $error_str .= '"parent_id"';
            }
            
            // @TODO Make sure that the error code is correct.
            return new WP_Error( 'invalid_json', $error_str . ' is missing in the request body (or the value is of an invalid type). See "products[' . $index . ']".', array( 'status' => 400 ) );
        }

        // @TODO Make sure no "product_id" is used more than once

        // @TODO We probably need some safe-guarding against circular parents, or setting a variable to variation when it has children or something...
    }

    // Update products

    $result_changed     = array();
    $result_not_changed = array();
    $result_failed      = array();

    global $wpdb;
    $table_name = $wpdb->prefix . 'posts';

    foreach ( $data['products'] as $product ) {
        $product_id = $product['product_id'];
        $parent_id  = $product['parent_id'];

        $current_update_count = $wpdb->update(
            $table_name,
            array( 
                'post_parent' => $parent_id,
                'post_type' => 'product_variation'
            ),
            array(
                'ID' => $product_id,
                'post_type' => 'product'
            )
        );

        // @BUG What happens if it is a variable product with children? We probably have to change their parent or something...

        // Product was changed (in the database)
        if ( $current_update_count === 1 ) { // Change
            array_push($result_changed, $product_id);
        } else if ( $current_update_count === 0 ) { // No change
            array_push($result_not_changed, $product_id);
        } else if ( $current_update_count === false ) { // Failed
            array_push($result_failed, array(
                'product_id' => $product_id,
                'message'    => 'Update query failed' . $wpdb->last_error,
            ));
        } else { // ???
            // @TODO Unexepcted result (> 1 means that we selected too many, < 0 maybe never happens or is an error or something idk)
            array_push($result_failed, array(
                'product_id' => $product_id,
                'message'    => 'Unexpected query result. Value: ' . $current_update_count, // @SECURITY Should we expose this value to the REST API users? Idk if it can contain anything secret :S
            ));
        }
    }

    // Success!

    return array(
        'changed'     => $result_changed,
        'not_changed' => $result_not_changed,
        'failed'      => $result_failed,
    );
}

function change_product_type_to_simple_callback( WP_REST_Request $request ) {
    // Read and parse the body

    $body = $request->get_body();
    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error( 'invalid_json', 'The request body is not a valid JSON format', array( 'status' => 400 ) );
    }

    // Validate request body

    if ( !isset( $data['product_ids'] ) || !is_array( $data['product_ids'] ) ) {
        // @TODO Make sure that the error code is correct.
        return new WP_Error( 'invalid_json', '"product_ids" is missing in the request body (or the value is of an invalid type).', array( 'status' => 400 ) );
    }

    foreach ( $data['product_ids'] as $index => $product_id ) {
        if ( !is_int($product_id) ) {
            // @TODO Make sure that the error code is correct.
            return new WP_Error( 'invalid_json', 'One or more product IDs in the request body is not an integer. See "product_ids[' . $index . ']".', array( 'status' => 400 ) );
        }
    }

    // Update products

    $result_changed     = array();
    $result_not_changed = array();
    $result_failed      = array();

    global $wpdb;
    $table_name = $wpdb->prefix . 'posts';

    foreach ( $data['product_ids'] as $product_id ) {
        $current_update_count = $wpdb->update(
            $table_name,
            array( 
                'post_parent' => null,
                'post_type' => 'product'
            ),
            array(
                'ID' => $product_id,
                'post_type' => 'product_variation'
            )
        );

        // Product was changed (in the database)
        if ( $current_update_count === 1 ) { // Change
            array_push($result_changed, $product_id);
        } else if ( $current_update_count === 0 ) { // No change
            array_push($result_not_changed, $product_id);
        } else if ( $current_update_count === false ) { // Failed
            array_push($result_failed, array(
                'product_id' => $product_id,
                'message'    => 'Update query failed' . $wpdb->last_error,
            ));
        } else { // ???
            // @TODO Unexepcted result (> 1 means that we selected too many, < 0 maybe never happens or is an error or something idk)
            array_push($result_failed, array(
                'product_id' => $product_id,
                'message'    => 'Unexpected query result. Value: ' . $current_update_count, // @SECURITY Should we expose this value to the REST API users? Idk if it can contain anything secret :S
            ));
        }
    }

    // Success!

    return array(
        'changed'     => $result_changed,
        'not_changed' => $result_not_changed,
        'failed'      => $result_failed,
    );
}

?>
