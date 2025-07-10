<?php
/**
 * Compatibility with SUMO Subscriptions plugin.
 *
 * @see https://codecanyon.net/item/sumo-subscriptions-woocommerce-subscription-system/16486054
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SUMO Subscriptions compatibility Class
 *
 * @since 1.10.0
 */
class Iconic_WSSV_Compat_Sumo_Subscriptions {
	/**
	 * Add action and filters
	 *
	 * @since 1.10.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! function_exists( 'sumosubscriptions' ) ) {
			return;
		}

		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'get_variation_price' ), 15, 2 );
	}

	/**
	 * Get the variation price.
	 *
	 * The SUMO Subscriptions plugin sets the price of variations to empty
	 * in the function SUMOSubscriptions_Frontend::get_product_data_to_display.
	 * So, we need to set the variation price.
	 *
	 * @since 1.10.0
	 *
	 * @param string     $price   The product price.
	 * @param WC_Product $product The product.
	 * @return string
	 */
	public static function get_variation_price( $price, $product ) {
		if ( is_admin() ) {
			return $price;
		}

		if ( ! empty( $price ) ) {
			return $price;
		}

		if ( empty( $product ) || 'variation' !== $product->get_type() ) {
			return $price;
		}

		/**
		 * We reproduce the same behaviour of
		 * WC_Product::get_price_html without calling the filter
		 * `woocommerce_get_price_html`.
		 */
		if ( '' === $product->get_price() ) {
			$price = apply_filters( 'woocommerce_empty_price_html', '', $product ); // phpcs:ignore WooCommerce.Commenting.CommentHooks
		} elseif ( $product->is_on_sale() ) {
			$price = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
		} else {
			$price = wc_price( wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
		}

		return $price;
	}
}
