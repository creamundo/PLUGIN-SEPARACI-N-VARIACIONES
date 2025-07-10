<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * XforWooCommerce compatibility Class
 *
 * @since v1.1.18
 */
class Iconic_WSSV_Compat_XforWoo {
	/**
	 * Init.
	 */
	public static function init() {
		if ( ! class_exists( 'X_for_WooCommerce' ) ) {
			return;
		}

		add_action( 'pre_get_posts', array( __CLASS__, 'add_variations_to_ajax_query' ) );
	}

	/**
	 * Add variations to the output of XfroWooCommerce filters.
	 *
	 * @param WP_Query $query The object of WP_Query to be modified to include product_variation.
	 *
	 * @return void
	 */
	public static function add_variations_to_ajax_query( $query ) {
		$post_types      = is_array( $query->get( 'post_type' ) ) ? $query->get( 'post_type' ) : array( $query->get( 'post_type' ) );
		$allowed_actions = array( 'prdctfltr_respond_550' );

		if ( ! wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_REQUEST['action'] ) || ! in_array( $_REQUEST['action'], $allowed_actions ) ) {
			return;
		}

		if ( ! in_array( 'product', $post_types ) ) {
			return;
		}

		Iconic_WSSV_Query::add_variations_to_query( $query );
	}

}
