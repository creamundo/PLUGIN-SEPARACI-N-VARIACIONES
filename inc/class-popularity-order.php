<?php
/**
 * Iconic_WSSV_Popularity_Order class
 *
 * @since 1.12.0
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Iconic_WSSV_Popularity_Order class
 */
class Iconic_WSSV_Popularity_Order {
	/**
	 * Init.
	 */
	public static function init() {
		if ( is_admin() ) {
			return;
		}

		add_filter( 'posts_clauses', array( __CLASS__, 'order_by_popularity_post_clauses' ), 15 );
	}

	/**
	 * Modify popularity post clauses.
	 *
	 * @param  string[] $clauses Associative array of the clauses for the query.
	 * @return string[] The new array of the clauses for the query.
	 */
	public static function order_by_popularity_post_clauses( $clauses ) {
		global $wpdb, $jck_wssv;

		/**
		 * Filter whether it should use the custom order by popularity.
		 *
		 * By default, WooCommerce sort by `wc_product_meta_lookup.total_sales`
		 * and it can result in variations appearing before the parent. To keep
		 * variations after their parent, it's necessary to use our custom
		 * order by popularity.
		 *
		 * @since 1.12.0
		 * @hook iconic_wssv_should_use_custom_order_by_popularity
		 * @param  bool $should_use_custom_order_by_popularity Default: `Use custom order by popularity` setting.
		 * @return bool New value
		 */
		$should_use_custom_order_by_popularity = apply_filters(
			'iconic_wssv_should_use_custom_order_by_popularity',
			! empty( $jck_wssv->settings['general_variation_settings_custom_order_by_popularity'] )
		);

		if (
			! $should_use_custom_order_by_popularity ||
			is_admin() ||
			! Iconic_WSSV_Helpers::query_has_products_and_variations( $clauses['where'] ) ||
			empty( $clauses['orderby'] ) ||
			empty( $wpdb->wc_product_meta_lookup )
		) {
			return $clauses;
		}

		if ( ' wc_product_meta_lookup.total_sales DESC, wc_product_meta_lookup.product_id DESC ' !== $clauses['orderby'] ) {
			return $clauses;
		}

		if ( ! strstr( $clauses['join'], 'wc_product_meta_lookup' ) ) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}

		$clauses['join'] .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} meta_lookup_parents ON {$wpdb->posts}.post_parent = meta_lookup_parents.product_id";
		$clauses['join'] .= " LEFT JOIN {$wpdb->posts} parents ON ( {$wpdb->posts}.post_parent = parents.ID AND parents.post_type = 'product' )";

		/**
		 * We use the parent total_sales if the product is a variation, if not, we use its total_sales.
		 *
		 * See:
		 * - https://mariadb.com/kb/en/coalesce/
		 */
		$clauses['fields'] .= ", COALESCE( meta_lookup_parents.total_sales, wc_product_meta_lookup.total_sales ) AS 'iconic_wssv_total_sales'";
		/**
		 * We use the parent ID if the product is a variation, if not, we use its ID.
		 *
		 * See:
		 * - https://mariadb.com/kb/en/coalesce/
		 */
		$clauses['fields'] .= ", COALESCE( parents.ID, {$wpdb->posts}.ID ) AS 'iconic_wssv_post_ID'";

		$order_by = array(
			'iconic_wssv_total_sales DESC',
			'iconic_wssv_post_ID DESC',
			// We order by post_parent to make the parent product appears before variations.
			"{$wpdb->posts}.post_parent ASC",
			'wc_product_meta_lookup.total_sales DESC',
			"{$wpdb->posts}.ID DESC",
		);

		/**
		 * Filter popularity orderby clause.
		 *
		 * @since 1.12.0
		 * @hook iconic_wssv_popularity_order_by_clause
		 * @param string $order_by       The orderby clause
		 * @param array  $order_by_array The orderby clause represented as array.
		 * @return string New orderby clause
		 */
		$clauses['orderby'] = apply_filters( 'iconic_wssv_popularity_order_by_clause', implode( ', ', $order_by ), $order_by );

		return $clauses;
	}
}
