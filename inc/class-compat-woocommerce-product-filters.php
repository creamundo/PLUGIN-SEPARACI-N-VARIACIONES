<?php
/**
 * Compatibility with Product Filters for WooCommerce plugin.
 *
 * @see https://woocommerce.com/products/product-filters/
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Product Filters for WooCommerce compatibility Class.
 *
 * @since 1.25.0
 */
class Iconic_WSSV_Compat_WooCommerce_Product_Filters {
	/**
	 * Add action and filters
	 *
	 * @since 1.25.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! defined( 'WC_PRODUCT_FILTER_VERSION' ) ) {
			return;
		}

		add_filter( 'wcpf_product_counts_clauses', [ __CLASS__, 'add_variation_to_product_counts_query' ], 50, 1 );
	}

	/**
	 * Add `product_variation` post type to the product
	 * counts query
	 *
	 * @param array $query The query clauses.
	 * @return array
	 */
	public static function add_variation_to_product_counts_query( $query ) {
		if ( empty( $query['where'] ) ) {
			return $query;
		}

		$query['where'] = str_replace(
			[ "post_type IN ( 'product' )" ],
			[ "post_type IN ( 'product', 'product_variation' )" ],
			$query['where']
		);

		return $query;
	}
}
