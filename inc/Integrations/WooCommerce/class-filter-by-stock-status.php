<?php
/**
 * This class handles WooCommerce Filter By Stock Status.
 *
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Filter By Stock Status integration Class.
 *
 * @since 1.25.0
 */
class Iconic_WSSV_Filter_By_Stock_Status {
	/**
	 * Add action and filters
	 *
	 * @since 1.25.0
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'pre_get_posts', [ __CLASS__, 'add_product_variations' ], 50, 1 );
	}

	/**
	 * Get the meta from parent product.
	 *
	 * @param WP_Query $wp_query The WP query.
	 */
	public static function add_product_variations( $wp_query ) {
		if ( $wp_query->is_main_query() ) {
			return;
		}

		if ( ! isset( $_GET['calculate_stock_status_counts'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		remove_filter( 'posts_clauses', [ 'Iconic_WSSV_Most_Recent_Order', 'order_by_most_recent_post_clauses' ] );

		$wp_query->set( 'post_type', [ 'product', 'product_variation' ] );
	}
}
