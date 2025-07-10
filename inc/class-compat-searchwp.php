<?php
/**
 * Compatibility with SearchWP plugin.
 *
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SearchWP compatibility Class
 *
 * @since 1.6.0
 */
class Iconic_WSSV_Compat_SearchWP {
	/**
	 * Add action and filters
	 *
	 * @since 1.6.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! Iconic_WSSV_Core_Helpers::is_plugin_active( 'searchwp/index.php' ) ) {
			return;
		}

		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'add_variation_to_be_indexed' ), 101, 1 );

		add_filter( 'searchwp\sources', array( __CLASS__, 'add_product_variation_as_source' ) );
		add_filter( 'searchwp\query\sql', array( __CLASS__, 'add_product_variation_when_searching_products' ), 10, 2 );
	}

	/**
	 * Add product_variation post type as a source to SearchWP
	 *
	 * @since 1.6.0
	 *
	 * @param  array $sources Array of SearchWP sources.
	 * @return array The new value.
	 */
	public static function add_product_variation_as_source( $sources ) {
		if ( ! class_exists( '\SearchWP\Sources\Post' ) ) {
			return $sources;
		}

		$sources[] = new Iconic_WSSV_Compat_SearchWP_Product_Variation_Source();

		return $sources;
	}

	/**
	 * Change the query executed by SearchWP when the plugin is
	 * searching for products to include product variations.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $query           The query executed by SearchWP.
	 * @param  array  $search_wp_query Array with the class that performs searches against the Index.
	 * @return string The new query.
	 */
	public static function add_product_variation_when_searching_products( $query, $search_wp_query ) {
		if ( empty( $search_wp_query['context'] ) || ! is_a( $search_wp_query['context'], 'SearchWP\Query' ) ) {
			return $query;
		}

		$engine = $search_wp_query['context']->get_engine();

		if ( ! is_a( $engine, 'SearchWP\Engine' ) ) {
			return $query;
		}

		$sources = $engine->get_sources();

		if ( isset( $sources['post.product'] ) ) {
			$query = str_replace(
				array(
					"s.source = 'post.product'",
					"`s1`.`post_type` = 'product'",
				),
				array(
					"s.source in ('post.product', 'post.product_variation')",
					"`s1`.`post_type` in ('product', 'product_variation')",
				),
				$query
			);
		}

		return $query;
	}

	/**
	 * Add variation to be indexed by SearchWP
	 *
	 * @since 1.6.0
	 *
	 * @param int $variation_id The variation ID to be processed.
	 * @return void
	 */
	public static function add_variation_to_be_indexed( $variation_id ) {
		if ( ! class_exists( '\SearchWP' ) || ! class_exists( '\SearchWP\Entry' ) ) {
			return;
		}

		$source = \SearchWP::$index->get_source_by_name( 'post.product_variation' );

		if ( is_wp_error( $source ) ) {
			return;
		}

		$entry = new \SearchWP\Entry( $source, $variation_id );

		\SearchWP::$index->add( $entry );
	}
}
