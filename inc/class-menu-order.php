<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @class    Iconic_WSSV_Menu_Order
 * @version  1.0.0
 * @author   Iconic
 */
class Iconic_WSSV_Menu_Order {
	/**
	 * Init.
	 */
	public static function init() {
		add_filter( 'posts_clauses', array( __CLASS__, 'order_by_menu_order_post_clauses' ) );
	}

	/**
	 * Modify menu order post clauses.
	 *
	 * @param $args
	 *
	 * @return array
	 */
	public static function order_by_menu_order_post_clauses( $args ) {
		global $wp_query, $wpdb;

		/**
		 * Skip the initialization of the Iconic_WSSV_Menu_Order class.
		 * Preventing adding action and filter hooks.
		 *
		 * @since 1.8.0
		 * @hook iconic_wssv_skip_order_by_menu_order_post_clauses
		 * @param  bool $skip_menu_order_init Whether we should skip or not. Default is is_admin().
		 * @return bool New value
		 */
		$skip_menu_order = apply_filters( 'iconic_wssv_skip_order_by_menu_order_post_clauses', is_admin() );

		// Don't change order on other admin pages, non woo pages, etc.
		if ( $skip_menu_order ) {
			return $args;
		}

		if ( ! Iconic_WSSV_Helpers::query_has_products_and_variations( $args['where'] ) ) {
			return $args;
		}

		if ( empty( $args['orderby'] ) ) {
			return $args;
		}

		// Don't change order if it's not menu/title
		if (
			$args['orderby'] !== "{$wpdb->posts}.menu_order ASC, {$wpdb->posts}.post_title ASC" &&
			$args['orderby'] !== "{$wpdb->posts}.menu_order, {$wpdb->posts}.menu_order ASC, {$wpdb->posts}.post_title ASC"
		) {
			return $args;
		}

		$args['join'] .= " LEFT JOIN {$wpdb->posts} parents ON ( {$wpdb->posts}.post_parent = parents.ID AND parents.post_type = 'product' )";
		/**
		 * We use the parent menu_order if the product is a variation, if not, we use its menu_order.
		 *
		 * To keep the order of the variations, we sum their menu_order. For example, if the order
		 * of the variation is 2 and the order of its parent is 6, the order of variation will be 6.002.
		 *
		 * See:
		 * - https://mariadb.com/kb/en/coalesce/
		 * - https://mariadb.com/kb/en/round/
		 */
		$args['fields'] .= ", COALESCE( ROUND( parents.menu_order + ( {$wpdb->posts}.menu_order / 1000 ), 3 ), {$wpdb->posts}.menu_order ) AS 'iconic_wssv_order'";
		$args['orderby'] = 'iconic_wssv_order ASC';

		return $args;
	}
}
