<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WSSV_Term_Counts.
 *
 * @class    Iconic_WSSV_Term_Counts
 * @version  1.0.0
 * @author   Iconic
 */
class Iconic_WSSV_Term_Counts {
	/**
	 * Instance.
	 */
	private static $instance;

	/**
	 * Init.
	 */
	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
	}

	/**
	 * Iconic_WSSV_Term_Counts constructor.
	 */
	protected function __construct() {
		self::add_actions();
		self::add_filters();
	}

	/**
	 * Add actions.
	 */
	protected static function add_actions() {
		add_action( 'deleted_transient', array( __CLASS__, 'delete_child_term_count_transient' ), 10, 1 );
	}

	/**
	 * Add filters.
	 */
	protected static function add_filters() {
		if ( is_admin() ) {
			return;
		}

		add_filter( 'woocommerce_get_filtered_term_product_counts_query', array( __CLASS__, 'filtered_term_product_counts_where_clause' ), 10, 1 );
		add_filter( 'get_terms', array( __CLASS__, 'change_term_counts' ), 100, 2 );
	}

	/**
	 * Modify the "filtered term product counts" where clause
	 *
	 * Adds post_type and post_parent__not_in parameter so unpublished variable
	 * product variations are ignored in the filter counts
	 *
	 * @since 1.1.0
	 *
	 * @param array $query The term's post count query.
	 *
	 * @return array
	 */
	public static function filtered_term_product_counts_where_clause( $query ) {
		global $wpdb, $wp_the_query, $jck_wssv;

		if ( version_compare( $jck_wssv->get_woo_version_number(), '4.5.2', '>=' ) ) {
			$query['where'] = str_replace( "{$wpdb->posts}.post_type IN ( 'product' )", "{$wpdb->posts}.post_type IN ( 'product', 'product_variation' ) ", $query['where'] );
		} else {
			$query['where'] = str_replace( "{$wpdb->posts}.post_type = 'product'", "{$wpdb->posts}.post_type IN ( 'product', 'product_variation' ) ", $query['where'] );
		}

		if ( ! empty( $wp_the_query->query_vars['post_parent__not_in'] ) ) {
			$query['where'] = sprintf( "%s AND %s.post_parent NOT IN ('%s')", $query['where'], $wpdb->posts, implode( "','", $wp_the_query->query_vars['post_parent__not_in'] ) );
		}

		if ( ! is_filtered() ) {
			$current_tax_query     = WC_Query::get_main_tax_query();
			$current_tax_query_obj = new WP_Tax_Query( $current_tax_query );
			$current_tax_query_sql = $current_tax_query_obj->get_sql( $wpdb->posts, 'ID' );

			$new_tax_query     = Iconic_WSSV_Query::update_tax_query( $current_tax_query, true );
			$new_tax_query_obj = new WP_Tax_Query( $new_tax_query );
			$new_tax_query_sql = $new_tax_query_obj->get_sql( $wpdb->posts, 'ID' );

			$query['where'] = str_replace( $current_tax_query_sql, $new_tax_query_sql, $query['where'] );
		}

		return $query;
	}

	/**
	 * Frontend: Change Term Counts
	 *
	 * @param array $terms
	 * @param array $taxonomies
	 *
	 * @return array
	 */
	public static function change_term_counts( $terms, $taxonomies ) {
		if ( is_admin() || defined( 'DOING_AJAX' ) ) {
			return $terms;
		}

		if ( ! isset( $taxonomies[0] ) || ! in_array( $taxonomies[0], apply_filters( 'woocommerce_change_term_counts', array( 'product_cat', 'product_tag' ) ), true ) ) {
			return $terms;
		}

		$variation_term_counts = array();

		foreach ( $terms as &$term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}

			$variation_term_counts[ $term->term_id ] = self::get_variations_count_in_term( $term );
		}

		$term_counts = get_transient( 'wc_term_counts' );

		foreach ( $terms as &$term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}

			if ( ! isset( $term_counts[ $term->term_id ] ) ) {
				continue;
			}

			$child_term_count = isset( $variation_term_counts[ $term->term_id ] ) ? (int) $variation_term_counts[ $term->term_id ] : 0;
			$term_count       = (int) $term_counts[ $term->term_id ];

			$term_counts[ $term->term_id ] = $term_count + $child_term_count;

			if ( empty( $term_counts[ $term->term_id ] ) ) {
				continue;
			}

			$term->count = absint( $term_counts[ $term->term_id ] );
		}

		return $terms;
	}

	/**
	 * Helper: Get Variaitons count in term
	 *
	 * @param WP_Term $term
	 *
	 * @return int
	 */
	public static function get_variations_count_in_term( $term ) {
		$transient_name = 'iconic_wssv_variations_in_term_counts';

		$variations_in_term_counts = $o_variations_in_term_counts = get_transient( $transient_name );

		if ( empty( $variations_in_term_counts ) || ! isset( $variations_in_term_counts[ $term->term_id ] ) ) {
			global $wpdb;

			if ( ! is_array( $variations_in_term_counts ) ) {
				$variations_in_term_counts = array();
			}

			// Add parent term to count.
			$terms_to_count = array( absint( $term->term_id ) );

			// Add children terms to count as well.
			if ( is_taxonomy_hierarchical( $term->taxonomy ) ) {
				// We need to get the $term's hierarchy so we can count its children too.
				$children = get_term_children( $term->term_id, $term->taxonomy );

				if ( $children && ! is_wp_error( $children ) ) {
					$terms_to_count = array_unique( array_map( 'absint', array_merge( $terms_to_count, $children ) ) );
				}
			}

			$query = array(
				'fields' => "
					SELECT COUNT( DISTINCT ID ) FROM {$wpdb->posts} wp
				",
				'join'   => "
					INNER JOIN {$wpdb->postmeta} wm ON (wm.`post_id` = wp.`ID` AND wm.`meta_key`='_visibility')
					INNER JOIN {$wpdb->term_relationships} wtr ON (wp.`ID` = wtr.`object_id`)
					INNER JOIN {$wpdb->term_taxonomy} wtt ON (wtr.`term_taxonomy_id` = wtt.`term_taxonomy_id`)
					INNER JOIN {$wpdb->terms} wt ON (wt.`term_id` = wtt.`term_id`)
				",
				'where'  => "
					WHERE 1=1
					AND wtt.taxonomy = '%s' AND wt.`term_id` IN (" . implode( ',', array_map( 'absint', $terms_to_count ) ) . ")
					AND wp.post_status = 'publish' AND ( wm.meta_value LIKE '%%visible%%' OR wm.meta_value LIKE '%%catalog%%' )
					AND wp.post_type = 'product_variation'
				",
			);

			$exclude_term_ids            = array();
			$product_visibility_term_ids = wc_get_product_visibility_term_ids();

			if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && $product_visibility_term_ids['outofstock'] ) {
				$exclude_term_ids[] = $product_visibility_term_ids['outofstock'];
			}

			if ( count( $exclude_term_ids ) ) {
				$query['join']  .= " LEFT JOIN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( " . implode( ',', array_map( 'absint', $exclude_term_ids ) ) . ' ) ) AS exclude_join ON exclude_join.object_id = wp.ID';
				$query['where'] .= ' AND exclude_join.object_id IS NULL';
			}

			$sql = $wpdb->prepare(
				implode( ' ', $query ),
				$term->taxonomy
			);

			$count                                       = absint( $wpdb->get_var( $sql ) );
			$variations_in_term_counts[ $term->term_id ] = $count;

			set_transient( $transient_name, $variations_in_term_counts );
		}

		return apply_filters( 'iconic_wssv_variations_count_in_term', $variations_in_term_counts[ $term->term_id ], $term );
	}

	/**
	 * Delete variations term counts.
	 *
	 * @param string $transient
	 */
	public static function delete_child_term_count_transient( $transient ) {
		if ( $transient !== 'wc_term_counts' ) {
			return;
		}

		delete_transient( 'iconic_wssv_variations_in_term_counts' );
	}
}
