<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Iconic_WSSV_Compat_Woodmart.
 *
 * Compatiblity with Woodmart theme.
 *
 * @class    Iconic_WSSV_Compat_Woodmart
 * @version  1.0.0
 * @package  Iconic_WSSV
 */
class Iconic_WSSV_Compat_Woodmart {

	/**
	 * Init
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'hooks' ) );

	}

	/**
	 * Hooks to be setup after wp has loaded.
	 */
	public static function hooks() {
		if ( ! class_exists( 'WOODMART_Theme' ) ) {
			return;
		}

		add_filter( 'woocommerce_product_object_query_args', array( __CLASS__, 'add_variations_to_wc_query' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'add_variations_to_ajax' ), 10, 1 );
		add_filter( 'woodmart_quick_view_posts_args', array( __CLASS__, 'add_variations_to_quickview' ), 10, 1 );
		add_filter( 'woodmart_product_label_output', array( __CLASS__, 'show_new_badge_for_variations' ), 10, 1 );
		add_filter( 'woodmart_ajax_search_args', [ __CLASS__, 'add_variations_to_ajax_search' ] );

		if ( ! is_admin() ) {
			// Change priority from 10 to 11. Execute after Woodmart has modified the tax query.
			remove_action( 'pre_get_posts', array( 'Iconic_WSSV_Query', 'add_variations_to_product_query' ), 10 );
			add_action( 'pre_get_posts', array( 'Iconic_WSSV_Query', 'add_variations_to_product_query' ), 11 );
		}
	}

	/**
	 * Add variations to WC Query.
	 *
	 * @param array $query Query Arguments.
	 *
	 * @return array
	 */
	public static function add_variations_to_wc_query( $query ) {
		$page_id = woodmart_get_opt( 'compare_page' );

		if ( empty( $page_id ) || ! is_page( $page_id ) ) {
			return $query;
		}

		$query['type'][] = 'variation';
		return $query;
	}

	/**
	 * Add variations to AJAX.
	 *
	 * @param WP_Query $query WP_Query object.
	 */
	public static function add_variations_to_ajax( $query ) {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		$action          = filter_var( $_REQUEST['action'], FILTER_SANITIZE_STRING );
		$allowed_actions = array( 'woodmart_ajax_search', 'woodmart_remove_from_wishlist', 'woodmart_get_posts_by_query', 'woodmart_get_products_tab_shortcode' );
		if ( ! in_array( $action, $allowed_actions, true ) ) {
			return;
		}

		Iconic_WSSV_Query::add_variations_to_product_query( $query, false );
	}

	/**
	 * Add variations to QuickView.
	 *
	 * @param array $args Arguments.
	 *
	 * @return array
	 */
	public static function add_variations_to_quickview( $args ) {
		if ( empty( $args ) ) {
			return $args;
		}

		$args['post_type'] = array( 'product', 'product_variation' );

		return $args;
	}

	/**
	 * Show New badge for Variations.
	 *
	 * @param array $labels List of Labels/Badges.
	 *
	 * @return array
	 */
	public static function show_new_badge_for_variations( $labels ) {
		global $product;

		if ( ! empty( $product ) && ! $product->is_type( 'variation' ) ) {
			return $labels;
		}

		if ( woodmart_get_opt( 'new_label' ) && woodmart_is_new_label_needed( $product->get_parent_id() ) ) {
			$labels[] = '<span class="new product-label">' . esc_html__( 'New', 'woodmart' ) . '</span>';
		}

		return $labels;
	}

	/**
	 * Add product variations post type to the AJAX search.
	 *
	 * @param array $query_args The search query args.
	 * @return array
	 */
	public static function add_variations_to_ajax_search( $query_args ) {
		if ( empty( $query_args['post_type'] ) ) {
			return $query_args;
		}

		$post_type = $query_args['post_type'];

		if ( ! is_string( $post_type ) && ! is_array( $post_type ) ) {
			return $query_args;
		}

		if (
			( is_string( $post_type ) && 'product' !== $post_type ) ||
			( is_array( $post_type ) && ! in_array( 'product', $post_type, true ) )
		) {
			return $query_args;
		}

		$query_args['post_type'] = [ 'product', 'product_variation' ];

		return $query_args;
	}

}
