<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WSSV_Ajax.
 *
 * @class   Iconic_WSSV_Ajax
 * @version  1.0.0
 * @author   Iconic
 */
class Iconic_WSSV_Ajax {

	/**
	 * Instance.
	 */
	private static $instance;

	/**
	 * Init.
	 */
	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Iconic_WSSV_Ajax();
			self::$instance->add_ajax_events();
		}
	}

	/**
	 * Hook in methods.
	 */
	private static function add_ajax_events() {
		$ajax_events = array(
			'get_product_count'          => false,
			'process_product_visibility' => false,
			'product_taxonomy_search'    => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_iconic_wssv_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_iconic_wssv_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Get product count.
	 */
	public static function get_product_count( $return = false ) {
		$count = Iconic_WSSV_Index::get_product_count();

		$response = array(
			'success' => true,
			'count'   => $count,
		);

		wp_send_json( $response );
	}

	/**
	 * Get products to apply visibility settings.
	 *
	 * @return array
	 */
	protected static function get_products_to_process_visibility() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'iconic-ssv-index' ) ) {
			return;
		}

		$limit  = empty( $_POST['iconic_wssv_limit'] ) ? 0 : absint( wp_unslash( $_POST['iconic_wssv_limit'] ) );
		$offset = empty( $_POST['iconic_wssv_offset'] ) ? 0 : absint( wp_unslash( $_POST['iconic_wssv_offset'] ) );

		$query_args = array(
			'fields'                 => 'ids',
			'posts_per_page'         => $limit,
			'offset'                 => $offset,
			'post_type'              => array( 'product', 'product_variation' ),
			'post_status'            => array( 'publish', 'pending', 'future', 'private' ),
			'order'                  => 'ASC',
			'orderby'                => 'menu_order ID',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		$category_ids_to_apply_visibility_settings = Iconic_WSSV_Helpers::get_category_ids_to_apply_visibility_settings();

		if ( $category_ids_to_apply_visibility_settings['is_empty'] ) {
			/**
			 * Filter process product query args.
			 *
			 * @since 1.16.0
			 * @hook iconic_wssv_process_product_query_args
			 * @param  array $query_args The query args used in WP_Query.
			 * @return array New value
			 */
			$query_args = apply_filters( 'iconic_wssv_process_product_query_args', $query_args );

			$query = new WP_Query( $query_args );

			/**
			 * Filter the products IDs to apply visibility settings.
			 *
			 * @since 1.16.0
			 * @hook iconic_wssv_products_to_apply_visibility_settings
			 * @param  array $products The product IDs.
			 * @return array New value
			 */
			return apply_filters( 'iconic_wssv_products_to_apply_visibility_settings', $query->posts );
		}

		if ( $category_ids_to_apply_visibility_settings['has_same_categories'] ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'product_cat',
					'terms'    => $category_ids_to_apply_visibility_settings['to_variations'],
				),
			);

			// phpcs:ignore WooCommerce.Commenting.CommentHooks
			$query_args = apply_filters( 'iconic_wssv_process_product_query_args', $query_args );

			$query = new WP_Query( $query_args );

			// phpcs:ignore WooCommerce.Commenting.CommentHooks
			return apply_filters( 'iconic_wssv_products_to_apply_visibility_settings', $query->posts );
		}

		$variations_query_args = array(
			'post_type' => 'product_variation',
		);

		if ( ! empty( $category_ids_to_apply_visibility_settings['to_variations'] ) ) {
			$variations_query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'product_cat',
					'terms'    => $category_ids_to_apply_visibility_settings['to_variations'],
				),
			);
		}

		$variations_query_args = wp_parse_args( $variations_query_args, $query_args );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks
		$variations_query_args = apply_filters( 'iconic_wssv_process_product_query_args', $variations_query_args );

		$query_variations = new WP_Query( $variations_query_args );
		$products         = $query_variations->posts;

		$variables_query_args = array(
			'post_type' => 'product',
		);

		if ( ! empty( $category_ids_to_apply_visibility_settings['to_variables'] ) ) {
			$variables_query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'product_cat',
					'terms'    => $category_ids_to_apply_visibility_settings['to_variables'],
				),
			);
		}

		$variables_query_args = wp_parse_args( $variables_query_args, $query_args );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks
		$variables_query_args = apply_filters( 'iconic_wssv_process_product_query_args', $variables_query_args );

		$query_variables = new WP_Query( $variables_query_args );
		$products        = array_merge( $products, $query_variables->posts );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks
		return apply_filters( 'iconic_wssv_products_to_apply_visibility_settings', $products );
	}

	/**
	 * Process product visibility.
	 */
	public static function process_product_visibility() {
		global $jck_wssv;

		$products = self::get_products_to_process_visibility();

		if ( ! empty( $products ) ) {
			foreach ( $products as $product_id ) {
				$product = wc_get_product( $product_id );

				if ( empty( $product ) ) {
					continue;
				}

				if ( $product->is_type( 'variation' ) ) {
					$jck_wssv->on_variation_save( $product->get_id() );
					Iconic_WSSV_Product_Variation::set_total_sales( $product->get_id() );
					Iconic_WSSV_Product_Variation::set_parent_attributes_to_variation( $product->get_id() );
				} else {
					Iconic_WSSV_Product::update_visibility( $product->get_id() );
				}
			}
		}

		wp_reset_postdata();
		wp_send_json( array( 'success' => true ) );
	}

	/**
	 * Search for product taxonomy terms
	 *
	 * @return void
	 */
	public static function product_taxonomy_search() {
		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json( null, 403 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$search_text = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		if ( empty( $search_text ) || empty( $_GET['taxonomy'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_send_json( null, 400 );
		}

		$taxonomy = sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		$results = array(
			'results' => array(),
		);

		$args = array(
			'taxonomy'   => $taxonomy,
			'orderby'    => 'id',
			'order'      => 'ASC',
			'hide_empty' => false,
			'name__like' => $search_text,
			'number'     => 5,
		);

		/**
		 * Filter the WP_Term_Query args used to search for product taxonomy terms.
		 *
		 * @since 1.10.0
		 * @hook iconic_wssv_product_taxonomy_search_query_args
		 * @param  array $term_query_args The args used by WP_Term_Query.
		 * @return array New value
		 */
		$args = apply_filters( 'iconic_wssv_product_taxonomy_search_query_args', $args );

		$term_query  = new WP_Term_Query( $args );
		$found_terms = $term_query->get_terms();

		if ( ! is_array( $found_terms ) ) {
			wp_send_json( $results );
		}

		foreach ( $found_terms as $term ) {
			$term_id   = $term->term_id;
			$term_name = Iconic_WSSV_Helpers::get_term_name_with_parent_name( $term, $taxonomy );

			$results['results'][] = array(
				'id'   => $term_id,
				'text' => $term_name,
			);
		}

		/**
		 * Filter the results of the product taxonomy term search.
		 *
		 * @since 1.13.0
		 * @hook iconic_wssv_product_taxonomy_search_results
		 * @param  array     $results      The results of the search.
		 * @param  WP_Term[] $found_terms  The terms found by the search.
		 * @param  string    $taxonomy     The taxonomy being searched.
		 * @param  array     $args         The args used by WP_Term_Query.
		 * @return array New value
		 */
		$results = apply_filters( 'iconic_wssv_product_taxonomy_search_results', $results, $found_terms, $taxonomy, $args );

		wp_send_json( $results );
	}
}
