<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * BeRocket Ajax Filters compatibility Class
 *
 * @since 1.1.14
 */
class Iconic_WSSV_Compat_BeRocket_Ajax_Filters {
	/**
	 * The current tax query.
	 *
	 * @var null|WP_Query|array
	 */
	public static $wc_tax_query = null;

	/**
	 * Init.
	 */
	public static function init() {
		if ( ! Iconic_WSSV_Core_Helpers::is_plugin_active( 'woocommerce-ajax-filters/woocommerce-filters.php' ) ) {
			return;
		}

		add_filter( 'pre_get_posts', array( __CLASS__, 'cache_wc_query' ), 99988 );
		add_filter( 'pre_get_posts', array( __CLASS__, 'restore_wc_query' ), 999999 );
		add_action( 'pre_get_posts', array( __CLASS__, 'remove_exclude_catalog_terms_from_tax_query' ), 100 );

		add_filter( 'berocket_aapf_recount_stock_status_query', array( __CLASS__, 'fix_filter_by_stock_count' ), 20 );
	}

	/**
	 * We need to cache the query before "BeRocket´s Ajax Filters"
	 * plugin filters the query, so we can restore the visibility
	 * parameter later.
	 *
	 * @param WP_Query $query
	 *
	 * @return WP_Query
	 */
	public static function cache_wc_query( $query ) {
		if ( $query->get( 'wc_query' ) ) {
			self::$wc_tax_query = $query->get( 'tax_query' );
		}

		return $query;
	}

	/**
	 * We need to restore the visibility parameter after "BeRocket's
	 * Ajax Filters" removed it from the original query.
	 *
	 * @param WP_Query $query
	 *
	 * @return WP_Query
	 */
	public static function restore_wc_query( $query ) {
		if ( ! empty( self::$wc_tax_query ) ) {
			$visibility_query = self::get_product_visibility_query( self::$wc_tax_query );

			if ( ! empty( $visibility_query ) ) {
				$tax_query   = self::remove_existing_visibility_query( $query->get( 'tax_query' ) );
				$tax_query[] = $visibility_query;
				$query->set( 'tax_query', $tax_query );
			}

			self::$wc_tax_query = null;
		}

		return $query;
	}

	/**
	 * Get product visibility queries from a tax query array.
	 *
	 * @param array $tax_query
	 *
	 * @return array
	 */
	public static function get_product_visibility_query( $tax_query ) {
		if ( empty( $tax_query ) ) {
			return $tax_query;
		}

		$visibility_query = array();

		foreach ( $tax_query as $index => $visibility_queries ) {
			if ( isset( $visibility_queries['taxonomy'] ) && $visibility_queries['taxonomy'] === 'product_visibility' ) {
				$visibility_query[] = $visibility_queries;
			} elseif ( isset( $visibility_queries['relation'] ) ) {
				$visibility_query[] = self::get_product_visibility_query( $tax_query[ $index ] );
			}
		}

		return $visibility_query;
	}

	/**
	 * Remove product visibility queries from a tax query array.
	 *
	 * @param array $tax_query
	 *
	 * @return array
	 */
	public static function remove_existing_visibility_query( $tax_query ) {
		if ( empty( $tax_query ) ) {
			return $tax_query;
		}

		foreach ( $tax_query as $index => $visibility_queries ) {
			if ( isset( $visibility_queries['taxonomy'] ) && $visibility_queries['taxonomy'] === 'product_visibility' ) {
				unset( $tax_query[ $index ] );
				if ( count( $tax_query ) == 1 && isset( $tax_query['relation'] ) ) {
					unset( $tax_query['relation'] );
					break;
				}
			} elseif ( isset( $visibility_queries['relation'] ) ) {
				$tax_query[ $index ] = self::remove_existing_visibility_query( $tax_query[ $index ] );
				if ( empty( $tax_query[ $index ] ) ) {
					unset( $tax_query[ $index ] );
				}
			}
		}

		return $tax_query;
	}

	/**
	 * This code would modify the tax_query argument and remove exclude-from-catalog references.
	 *
	 * BeRocket would add a condition which says don't include the products which
	 * have exclude-from-catalog terms (are excluded from catalog).
	 * However, when filtering products there is not need to check exclude-from-catalog
	 * terms, as even those products which are excluded from catalog can appear in
	 * filter results.
	 *
	 * @param WP_Query $q WP Query object.
	 */
	public static function remove_exclude_catalog_terms_from_tax_query( $q ) {

		$query_vars = $q->query_vars;

		if ( ! isset( $query_vars['wc_query'], $query_vars['iconic_ssv_query'] ) || ! is_filtered() ) {
			return $q;
		}

		$exclude_from_catalog_term = get_term_by( 'slug', 'exclude-from-catalog', 'product_visibility' );
		$tax_query                 = $q->get( 'tax_query' );

		$tax_query = self::iconic_ssv_remove__exclude_from_catalog__term( $tax_query, $exclude_from_catalog_term );

		$q->set( 'tax_query', $tax_query );
	}

	/**
	 * Recursively remove the tax_query which references 'exclude-from-catalog' terms.
	 *
	 * @param array   $tax_query                 Tax query.
	 * @param WP_Term $exclude_from_catalog_term Exclude from catalog WP_Term object.
	 */
	public static function iconic_ssv_remove__exclude_from_catalog__term( $tax_query, $exclude_from_catalog_term ) {
		if ( empty( $tax_query ) || empty( $exclude_from_catalog_term ) ) {
			return $tax_query;
		}

		$updated_tax_query = array();

		foreach ( $tax_query as $index => $visibility_queries ) {
			if ( ! isset( $visibility_queries['taxonomy'] ) && is_array( $visibility_queries ) ) {
				// If it is an array then recurse.
				$updated_tax_query[] = self::iconic_ssv_remove__exclude_from_catalog__term( $tax_query[ $index ], $exclude_from_catalog_term );
			} elseif ( isset( $visibility_queries['taxonomy'] ) && 'product_visibility' === $visibility_queries['taxonomy'] ) {
				$query_terms = (array) $visibility_queries['terms'];
				$query_terms = array_map( 'intval', $query_terms );
				// If exclude-from-catalog term is present, then don't skip this query.
				if ( false !== array_search( (int) $exclude_from_catalog_term->term_id, $query_terms, true ) ) {
					continue;
				}
				$updated_tax_query[ $index ] = $visibility_queries;
			} else {
				// Not `product_visibility` taxonomy? Simply add this query without any changes.
				$updated_tax_query[ $index ] = $visibility_queries;
			}
		}

		return $updated_tax_query;
	}

	/**
	 * Fix the SQL query to include the product variations in
	 * the count of products in stock and out of stock.
	 *
	 * @param string $query The SQL query.
	 * @return string
	 */
	public static function fix_filter_by_stock_count( $query ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $query;
		}

		$query = str_replace( ' parent_id = 0', ' 1 = 1', $query );

		return $query;
	}
}
