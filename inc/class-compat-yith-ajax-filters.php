<?php

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with YITH WooCommerce Ajax Product Filter
 * https://wordpress.org/plugins/yith-woocommerce-ajax-navigation/
 */
class Iconic_WSSV_Compat_Yith_Ajax_Filters {

	/**
	 * Init.
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! function_exists( 'yith_wcan_free_install' ) && ! function_exists( 'yith_wcan_initialize' ) ) {
			return;
		}

		add_filter( 'yith_wcan_filtered_products_query', array( __CLASS__, 'iconic_ssv_add_product_variation_to_yith_filter_query' ) );
		add_filter( 'yith_wcan_product_ids_in_stock_args', array( __CLASS__, 'iconic_ssv_add_product_variation_to_get_product_ids_in_stock_yith_query' ) );
	}

	/**
	 * Add 'product_variation' post type.
	 *
	 * @param array $args Query args.
	 *
	 * @return array.
	 */
	public static function iconic_ssv_add_product_variation_to_yith_filter_query( $args ) {
		$args['post_type'] = array( 'product', 'product_variation' );
		return $args;
	}

	/**
	 * Add 'variarion' as a type in the query args used to retrieve
	 * products in stock.
	 *
	 * When the option 'Hide out of stock products' is enabled, the
	 * YITH WooCommerce Ajax Product Filter plugin executes a query
	 * to retrieve only the products in stock. By default, this
	 * query doesn't include the product variations.
	 *
	 * @since 1.6.0
	 * @param  array $in_stock_args The args used to the query to retrieve the products in stock.
	 * @return array The new query args.
	 */
	public static function iconic_ssv_add_product_variation_to_get_product_ids_in_stock_yith_query( $in_stock_args ) {
		$in_stock_args['type'] = array_merge( array_keys( wc_get_product_types() ), array( 'variation' ) );

		return $in_stock_args;
	}
}
