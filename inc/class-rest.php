<?php
/**
 * Iconic_WSSV_Rest class.
 *
 * @package iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WSSV_Rest.
 *
 * @class   Iconic_WSSV_Rest
 * @version  1.25.0
 */
class Iconic_WSSV_Rest {
	/**
	 * Instance.
	 *
	 * @var Iconic_WSSV_Rest
	 */
	private static $instance;

	/**
	 * Init.
	 */
	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Iconic_WSSV_Rest();
			add_action( 'set_object_terms', [ self::$instance, 'prevent_product_visibility_from_being_removed_on_wc_rest_request' ], 55, 6 );
		}
	}

	/**
	 * Prevent product visibility settings from being removed when
	 * a variation is updated via WC REST API.
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object term IDs or slugs.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	public function prevent_product_visibility_from_being_removed_on_wc_rest_request( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( ! Iconic_WSSV_Helpers::validate_product_visibility_on_set_object_terms( $taxonomy, $append, $old_tt_ids ) ) {
			return;
		}

		if ( ! $this->is_wc_rest_api_variation_update_endpoint() ) {
			return;
		}

		wp_set_post_terms( $object_id, $old_tt_ids, 'product_visibility', true );
	}

	/**
	 * Whether the current request is a WC REST API request to
	 * update a variation
	 *
	 * @return boolean
	 */
	protected function is_wc_rest_api_variation_update_endpoint() {
		if ( ! WC()->is_rest_api_request() ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( 'PUT' !== ( $_SERVER['REQUEST_METHOD'] ?? false ) ) {
			return false;
		}

		$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] ?? '' );

		/**
		 * Try to catch a pattern like `/wc/v3/products/13/variations/27`
		 * to make sure we are intercepting the correct endpoint
		 */
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( 1 !== preg_match( '#.*/wc/v3/products/(?P<product_id>[\d]+)/variations/(?P<variation_id>[\d]+)#', $route, $matches ) ) {
			return false;
		}

		return true;
	}
}
