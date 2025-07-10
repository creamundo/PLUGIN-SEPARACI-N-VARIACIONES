<?php
/**
 * Compatibility with UpSolution Core plugin.
 *
 * @see https://help.us-themes.com/impreza/us-core/
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * UpSolution Core compatibility Class.
 *
 * @since 1.21.0
 */
class Iconic_WSSV_Compat_Upsolution_Core {
	/**
	 * Add action and filters
	 *
	 * @since 1.21.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! defined( 'US_CORE_VERSION' ) ) {
			return;
		}

		add_filter( 'us_grid_available_post_types', [ __CLASS__, 'add_product_variation_post_type' ] );
	}

	/**
	 * Add `product_variation` post type to the available post types.
	 *
	 * @param array $available_post_types The available post types.
	 * @return array
	 */
	public static function add_product_variation_post_type( $available_post_types ) {
		if ( ! is_array( $available_post_types ) ) {
			return $available_post_types;
		}

		$available_post_types['product_variation'] = __( 'Variation Products (product_variation)', 'iconic-wssv' );

		return $available_post_types;
	}
}
