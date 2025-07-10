<?php
/**
 * Compatibility with Ajax Search Lite plugin.
 *
 * @see https://wordpress.org/plugins/ajax-search-lite/
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Ajax Search Lite compatibility Class.
 *
 * @since 1.19.0
 */
class Iconic_WSSV_Compat_Ajax_Search_Lite {
	/**
	 * Add action and filters
	 *
	 * @since 1.19.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! defined( 'ASL_CURRENT_VERSION' ) ) {
			return;
		}

		add_filter( 'asl_query_args', [ __CLASS__, 'filter_out_variable_products_from_post_parent_exclude_query_args' ] );
	}

	/**
	 * Filter out variable products from the `post_parent_exclude' query arg.
	 *
	 * Ajax Search Lite adds hidden variable products to the `post_parent_exclude'
	 * query arg. However, Show Single Variations allows controlling visibility at
	 * the variation level. That way, we filter out variable products from
	 * the `post_parent_exclude' query arg to rely on the visibility settings
	 * defined at the variation level.
	 *
	 * @param array $args The query args.
	 * @return array
	 */
	public static function filter_out_variable_products_from_post_parent_exclude_query_args( $args ) {
		if ( empty( $args['post_parent_exclude'] ) || ! is_array( $args['post_parent_exclude'] ) ) {
			return $args;
		}

		foreach ( $args['post_parent_exclude'] as $key => $post_id ) {
			$product = wc_get_product( $post_id );

			if ( ! $product ) {
				continue;
			}

			if ( ! $product->is_type( 'variable' ) ) {
				continue;
			}

			unset( $args['post_parent_exclude'][ $key ] );
		}

		return $args;
	}
}
