<?php
/**
 * Compatibility with WooCommerce Wholesale Prices (free and premium) plugin.
 *
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Wholesale Prices compatibility Class
 *
 * @since 1.3.1
 */
class Iconic_WSSV_Compat_WC_Wholesale_Prices {
	/**
	 * Add action and filters
	 *
	 * @since 1.3.1
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! Iconic_WSSV_Core_Helpers::is_plugin_active( 'woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php' ) ) {
			return;
		}

		add_action( 'pre_get_posts', array( __CLASS__, 'add_product_variation_post_type_to_product_wholesale_visibility_filter' ) );
		add_filter( 'wwpp_pre_get_post__in', array( __CLASS__, 'remove_duplicate_product_ids' ), 20 );
	}

	/**
	 * Add product_variation post type if the product post type and
	 * wwpp_product_wholesale_visibility_filter meta query key are used in the query
	 *
	 * @since 1.3.1
	 *
	 * @param WP_Query $query The WP_Query instance (passed by reference).
	 * @return void
	 */
	public static function add_product_variation_post_type_to_product_wholesale_visibility_filter( $query ) {
		if (
			! defined( 'WWPP_PRODUCT_WHOLESALE_VISIBILITY_FILTER' ) ||
			! is_string( WWPP_PRODUCT_WHOLESALE_VISIBILITY_FILTER ) ||
			empty( WWPP_PRODUCT_WHOLESALE_VISIBILITY_FILTER )
		) {
			return;
		}

		$meta_query = $query->get( 'meta_query', array() );

		$is_wwpp_query = false;

		foreach ( $meta_query as $item ) {
			if ( isset( $item['key'] ) && WWPP_PRODUCT_WHOLESALE_VISIBILITY_FILTER === $item['key'] ) {
				$is_wwpp_query = true;

				break;
			}
		}

		if ( ! $is_wwpp_query ) {
			return;
		}

		$post_type = $query->get( 'post_type', array() );

		if ( in_array( 'product', (array) $post_type, true ) ) {
			$query->set( 'post_type', array( 'product', 'product_variation' ) );
		}
	}

	/**
	 * Remove duplicate product IDs.
	 *
	 * When the user is not logged-in, WooCommerce Wholesale Prices plugin
	 * checks how products can be shown to not logged-in users. However, the
	 * list of product IDs can have duplicated IDs because of the product
	 * variations.
	 *
	 * @param array $wwpp_products The product IDs.
	 * @return array
	 */
	public static function remove_duplicate_product_ids( $wwpp_products ) {
		if ( ! is_array( $wwpp_products ) ) {
			return $wwpp_products;
		}

		return array_unique( $wwpp_products );
	}
}
