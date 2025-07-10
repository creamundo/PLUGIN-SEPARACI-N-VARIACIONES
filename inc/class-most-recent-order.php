<?php
/**
 * Iconic_WSSV_Most_Recent_Order class
 *
 * @since 1.5.0
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Iconic_WSSV_Most_Recent_Order class
 */
class Iconic_WSSV_Most_Recent_Order {
	/**
	 * Init.
	 */
	public static function init() {
		if ( is_admin() ) {
			return;
		}

		add_filter( 'posts_clauses', array( __CLASS__, 'order_by_most_recent_post_clauses' ) );
	}

	/**
	 * Modify menu order post clauses.
	 *
	 * @param  string[] $clauses Associative array of the clauses for the query.
	 * @return string[] The new array of the clauses for the query.
	 */
	public static function order_by_most_recent_post_clauses( $clauses ) {
		global $wpdb;

		/**
		 * Disable the custom most recent orderby clause used by the plugin.
		 *
		 * @since 1.5.0
		 * @hook iconic_wssv_disable_custom_order_by_most_recent
		 * @param  bool $disable_custom_order_by_most_recent true to disable the custom most recent orderby clause. Default is false.
		 * @return bool New value
		 */
		$disable_custom_order_by_most_recent = apply_filters( 'iconic_wssv_disable_custom_order_by_most_recent', false );

		if (
			$disable_custom_order_by_most_recent ||
			is_admin() ||
			! Iconic_WSSV_Helpers::query_has_products_and_variations( $clauses['where'] ) ||
			empty( $clauses['orderby'] )
		) {
			return $clauses;
		}

		if ( "{$wpdb->posts}.post_date DESC, {$wpdb->posts}.ID DESC" !== $clauses['orderby'] ) {
			return $clauses;
		}

		$clauses['join'] .= " LEFT JOIN {$wpdb->posts} parents ON ( {$wpdb->posts}.post_parent = parents.ID AND parents.post_type = 'product' )";
		/**
		 * We use the parent post_date if the product is a variation, if not, we use its post_date.
		 *
		 * See:
		 * - https://mariadb.com/kb/en/coalesce/
		 */
		$clauses['fields'] .= ", COALESCE( parents.post_date, {$wpdb->posts}.post_date ) AS 'iconic_wssv_post_date'";
		/**
		 * We use the parent ID if the product is a variation, if not, we use its ID.
		 *
		 * See:
		 * - https://mariadb.com/kb/en/coalesce/
		 */
		$clauses['fields'] .= ", COALESCE( parents.ID, {$wpdb->posts}.ID ) AS 'iconic_wssv_post_ID'";

		$order_by = array(
			'iconic_wssv_post_date DESC',
			'iconic_wssv_post_ID DESC',
			// We order by post_parent to make the parent product appears before variations.
			"{$wpdb->posts}.post_parent ASC",
			"{$wpdb->posts}.post_date DESC",
			"{$wpdb->posts}.ID DESC",
		);

		/**
		 * Filter most recent orderby clause.
		 *
		 * @since 1.5.0
		 * @hook iconic_wssv_most_recent_order_by_clause
		 * @param string $order_by The orderby clause
		 * @return string New orderby clause
		 */
		$clauses['orderby'] = apply_filters( 'iconic_wssv_most_recent_order_by_clause', implode( ', ', $order_by ) );

		return $clauses;
	}
}
