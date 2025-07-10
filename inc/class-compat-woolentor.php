<?php
/**
 * Compatibility with ShopLentor – WooCommerce Builder for Elementor & Gutenberg plugin.
 *
 * @see https://woolentor.com/
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ShopLentor – WooCommerce Builder for Elementor & Gutenberg compatibility Class.
 *
 * @since 1.16.0
 */
class Iconic_WSSV_Compat_Woolentor {
	/**
	 * Add action and filters
	 *
	 * @since 1.16.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! defined( 'WOOLENTOR_VERSION' ) ) {
			return;
		}

		add_filter( 'woolentor_filterable_shortcode_products_query', array( __CLASS__, 'add_product_variation' ), 50, 1 );
	}

	/**
	 * Add `product_variation` to the WP query args to retrieve
	 * both products and product variations.
	 *
	 * @since 1.16.0
	 *
	 * @param array $query_args The WP query args.
	 * @return array
	 */
	public static function add_product_variation( $query_args ) {
		if ( empty( $query_args['post_type'] ) ) {
			return $query_args;
		}

		if (
			'product' !== $query_args['post_type'] ||
			(
				is_array( $query_args['post_type'] ) &&
				! in_array( 'product', $query_args['post_type'], true )
			)
		) {
			return $query_args;
		}

		if (
			is_array( $query_args['post_type'] ) &&
			in_array( 'product_variation', $query_args['post_type'], true )
		) {
			return $query_args;
		}

		$query_args['post_type'] = array( 'product', 'product_variation' );

		return $query_args;
	}
}
