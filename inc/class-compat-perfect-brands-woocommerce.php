<?php
/**
 * Compatibility with Perfect Brands WooCommerce plugin.
 *
 * @see https://wordpress.org/plugins/perfect-woocommerce-brands/
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Perfect Brands WooCommerce compatibility Class
 *
 * @since 1.12.0
 */
class Iconic_WSSV_Compat_Perfect_Brands_WooCommerce {
	/**
	 * Add action and filters
	 *
	 * @since 1.12.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! defined( 'PWB_PLUGIN_NAME' ) ) {
			return;
		}

		add_filter( 'iconic_wssv_variation_taxonomies', array( __CLASS__, 'add_pwb_brand_taxonomy' ) );
	}

	/**
	 * Add `pwb-brand` taxonomy.
	 *
	 * Perfect Brands WooCommerce plugin creates a custom product taxonomy (pwb-brand)
	 * to show the brands. By default, SSV syncs main products and their variations
	 * for `product_cat` and `product_tag` taxonomies. We have to add `pwb-brand`
	 * to make compatible with Perfect Brands WooCommerce plugin.
	 *
	 * @since 1.12.0
	 *
	 * @param string[] $taxonomies The product taxonomies.
	 * @return string[]
	 */
	public static function add_pwb_brand_taxonomy( $taxonomies ) {
		$taxonomies[] = 'pwb-brand';

		return $taxonomies;
	}
}
