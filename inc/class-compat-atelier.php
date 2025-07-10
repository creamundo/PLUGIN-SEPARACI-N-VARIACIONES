<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Atelier compatibility Class
 *
 * @since 1.1.10
 */
class Iconic_WSSV_Compat_Atelier {
	/**
	 * Init.
	 */
	public static function init() {
		add_filter( 'iconic_wssv_button_args', array( __CLASS__, 'add_to_cart_button_args' ), 10, 2 );
	}

	/**
	 * Add to cart button args.
	 *
	 * @param array      $args
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	public static function add_to_cart_button_args( $args, $product ) {
		$icon_class          = self::get_add_to_cart_icon_class( $args, $product );
		$args['button_text'] = '<i class="' . $icon_class . '"></i><span>' . $args['button_text'] . '</span>';

		return $args;
	}

	/**
	 * Get add to cart icon class.
	 *
	 * @param array      $args
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public static function get_add_to_cart_icon_class( $args, $product ) {
		if ( ! $args['is_purchasable'] ) {
			return 'sf-icon-variable-options';
		}

		if ( ! $product->is_in_stock() ) {
			return 'sf-icon-soldout';
		}

		return 'sf-icon-add-to-cart';
	}
}
