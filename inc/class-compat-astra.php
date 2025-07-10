<?php
/**
 * Compatibility with Astra theme.
 *
 * @see https://wordpress.org/themes/astra
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Astra compatibility Class.
 *
 * @since 1.25.0
 */
class Iconic_WSSV_Compat_Astra {
	/**
	 * Add action and filters
	 *
	 * @since 1.25.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! defined( 'ASTRA_THEME_VERSION' ) ) {
			return;
		}

		add_action( 'astra_addon_woo_quick_view_before', [ __CLASS__, 'handle_quick_view_for_product_variation' ] );
		add_action( 'woocommerce_variation_add_to_cart', [ __CLASS__, 'handle_add_to_cart_variation' ] );
	}

	/**
	 * Handle Quick View feature for product variation.
	 *
	 * @param int $product_id The product ID.
	 * @return void
	 */
	public static function handle_quick_view_for_product_variation( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( empty( $product ) ) {
			return;
		}

		if ( ! $product->is_type( 'variation' ) ) {
			return;
		}

		add_action( 'pre_get_posts', [ __CLASS__, 'add_product_variation_to_quick_view_query' ] );
	}

	/**
	 * Add `product_variation` post type to the Quick View query.
	 *
	 * @param WP_Query $query The Quick View query.
	 * @return void
	 */
	public static function add_product_variation_to_quick_view_query( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		$post_type = (array) $query->get( 'post_type' );

		if ( in_array( 'product_variation', $post_type, true ) ) {
			return;
		}

		if ( ! in_array( 'product', $post_type, true ) ) {
			return;
		}

		$post_type = 'product_variation';

		$query->set( 'post_type', $post_type );
	}

	/**
	 * Handle add to cart variation.
	 *
	 * Make the adjusts for a product variation.
	 *
	 * @return void
	 */
	public static function handle_add_to_cart_variation() {
		$action     = wp_unslash( $_POST['action'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$product_id = wp_unslash( $_POST['product_id'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

		if ( 'ast_load_product_quick_view' !== $action ) {
			return;
		}

		$product = wc_get_product( absint( $product_id ) );

		if ( ! $product->is_type( 'variation' ) ) {
			return;
		}

		$iconic_wssv = Iconic_WSSV::instance();

		add_filter( 'woocommerce_product_add_to_cart_url', [ $iconic_wssv, 'add_to_cart_url' ], 50, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', [ __CLASS__, 'update_add_to_cart_text_variation' ], 50, 2 );
		add_filter( 'iconic_wssv_button_args', [ __CLASS__, 'adjust_add_to_cart_button_style_variation' ], 50, 2 );

		echo wp_kses_post( $iconic_wssv->change_variation_add_to_cart_link( '', $product ) );
	}

	/**
	 * Update the Add to Cart text for the variation.
	 *
	 * @param string           $text    The `Add to Cart` text.
	 * @param WC_Product|false $product The product.
	 * @return string
	 */
	public static function update_add_to_cart_text_variation( $text, $product = false ) {
		if ( ! $product ) {
			global $product;
		}

		if ( $product->get_type() !== 'variation' ) {
			return $text;
		}

		if ( ! Iconic_WSSV::instance()->is_purchasable( $product ) || ! $product->is_in_stock() ) {
			$text = __( 'Select options', 'woocommerce' );
		}

		return __( 'Add to cart', 'woocommerce' );
	}

	/**
	 * Adjust the `Add to Cart` button style for variations.
	 *
	 * @param array      $args    The button arguments.
	 * @param WC_Product $product The product.
	 * @return array
	 */
	public static function adjust_add_to_cart_button_style_variation( $args, $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return $args;
		}

		if ( ! $product->is_type( 'variation' ) ) {
			return $args;
		}

		$args['attributes']['style'] = 'padding: 10px 20px; margin-bottom: 1rem;';

		return $args;
	}
}
