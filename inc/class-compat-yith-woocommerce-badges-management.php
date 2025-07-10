<?php
/**
 * Compatibility with YITH WooCommerce Badges Management plugin.
 *
 * @see https://wordpress.org/plugins/yith-woocommerce-badges-management/
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * YITH WooCommerce Badges Management compatibility Class
 *
 * @since 1.15.0
 */
class Iconic_WSSV_Compat_YITH_WooCommerce_Badges_Management {
	/**
	 * Add action and filters
	 *
	 * @since 1.15.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! defined( 'YITH_WCBM' ) ) {
			return;
		}

		add_filter( 'woocommerce_product_variation_get__yith_wcbm_badge_ids', array( __CLASS__, 'get_parent_product_yith_wcbm_badge_ids' ), 50, 2 );
	}

	/**
	 * Get `_yith_wcbm_badge_ids` meta data from parent product.
	 *
	 * @since 1.15.0
	 *
	 * @param mixed                $value             The meta data value.
	 * @param WC_Product_Variation $product_variation The product variation object.
	 * @return mixed
	 */
	public static function get_parent_product_yith_wcbm_badge_ids( $value, $product_variation ) {
		if ( ! empty( $value ) ) {
			return $value;
		}

		$parent_product = wc_get_product( $product_variation->get_parent_id() );

		if ( ! $parent_product ) {
			return $value;
		}

		$badge_ids = $parent_product->get_meta( '_yith_wcbm_badge_ids' );

		if ( empty( $badge_ids ) ) {
			return $value;
		}

		return $badge_ids;
	}
}
