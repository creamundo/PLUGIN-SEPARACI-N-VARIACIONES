<?php
/**
 * Iconic_WSSV_Checkout.
 *
 * @package iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Iconic_WSSV_Checkout.
 *
 * @class    Iconic_WSSV_Checkout
 * @version  1.15.2
 */
class Iconic_WSSV_Checkout {
	/**
	 * Run.
	 */
	public static function init() {
		add_action( 'set_object_terms', array( __CLASS__, 'prevent_product_visibility_from_being_removed_on_process_checkout' ), 50, 6 );
	}

	/**
	 * Prevent product visibility settings from being removed on process checkout.
	 *
	 * WooCommerce checks the stock status and updates the product visibility
	 * settings but it removes the data stored before. That way, this function tries
	 * to prevent loosing the data used by our plugin to show or not a variation in
	 * the catalogue, filtered results or search results.
	 *
	 * @see WC_Product_Variation_Data_Store_CPT::update_visibility().
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object term IDs or slugs.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	public static function prevent_product_visibility_from_being_removed_on_process_checkout( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( ! Iconic_WSSV_Helpers::validate_product_visibility_on_set_object_terms( $taxonomy, $append, $old_tt_ids ) ) {
			return;
		}

		if (
			empty( $_POST['woocommerce-process-checkout-nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' )
		) {
			return;
		}

		if ( empty( $_GET['wc-ajax'] ) || 'checkout' !== $_GET['wc-ajax'] ) {
			return;
		}

		$product = wc_get_product( $object_id );

		if ( empty( $product ) || ! $product->is_type( 'variation' ) ) {
			return;
		}

		wp_set_post_terms( $product->get_id(), $old_tt_ids, 'product_visibility', true );
	}
}
