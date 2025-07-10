<?php
/**
 * Compatibility with Goya theme.
 *
 * @see https://themeforest.net/item/goya-modern-woocommerce-theme/25175097
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Goya compatibility Class.
 *
 * @since 1.25.0
 */
class Iconic_WSSV_Compat_Goya {
	/**
	 * Add action and filters
	 *
	 * @since 1.25.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! defined( 'GOYA_THEME_VERSION' ) ) {
			return;
		}

		self::handle_add_to_cart_product_variations();
		add_filter( 'woocommerce_get_price_html', [ __CLASS__, 'handle_price_html_product_variations' ], 50, 2 );
	}

	/**
	 * Get product variation from global post.
	 *
	 * @return WC_Product|null
	 */
	protected static function get_product_variation_from_global_post() {
		global $post;

		$variation = is_a( $post, 'WP_Post' ) ? wc_get_product( $post->ID ) : null;

		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			return null;
		}

		return $variation;
	}

	/**
	 * Handle add to cart product variations.
	 *
	 * @return void
	 */
	protected static function handle_add_to_cart_product_variations() {
		add_filter( 'woocommerce_product_add_to_cart_url', [ __CLASS__, 'handle_product_variation_add_to_cart_url' ], 50, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', [ __CLASS__, 'handle_product_variation_add_to_cart_text' ], 50, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_args', [ __CLASS__, 'handle_product_variation_add_to_cart_args' ], 50, 2 );
	}

	/**
	 * Handle the product variation Add To Cart URL.
	 *
	 * @param string     $url     The Add To Cart URL.
	 * @param WC_Product $product The product.
	 * @return string
	 */
	public static function handle_product_variation_add_to_cart_url( $url, $product ) {
		if ( 'product' !== $product->post_type ) {
			return $url;
		}

		$variation = self::get_product_variation_from_global_post();

		if ( ! $variation ) {
			return $url;
		}

		$variation_url = $variation->add_to_cart_url();

		if ( empty( $variation_url ) ) {
			return $url;
		}

		return $variation_url;
	}

	/**
	 * Handle the product variation Add To Cart text.
	 *
	 * @param string     $add_to_cart_text The Add To Cart text.
	 * @param WC_Product $product          The product.
	 * @return string
	 */
	public static function handle_product_variation_add_to_cart_text( $add_to_cart_text, $product ) {
		if ( 'product' !== $product->post_type ) {
			return $add_to_cart_text;
		}

		$variation = self::get_product_variation_from_global_post();

		if ( ! $variation ) {
			return $add_to_cart_text;
		}

		$variation_add_to_cart_text = $variation->add_to_cart_text();

		if ( empty( $variation_add_to_cart_text ) ) {
			return $add_to_cart_text;
		}

		return $variation_add_to_cart_text;
	}

	/**
	 * Handle the product variation Add To Cart args.
	 *
	 * Adds the `ajax_add_to_cart` class.
	 *
	 * @param string     $args    The Add To Cart args.
	 * @param WC_Product $product The product.
	 * @return string
	 */
	public static function handle_product_variation_add_to_cart_args( $args, $product ) {
		if ( empty( $args['class'] ) ) {
			return $args;
		}

		if ( str_contains( $args['class'], 'ajax_add_to_cart' ) ) {
			return $args;
		}

		if ( 'product' !== $product->post_type ) {
			return $args;
		}

		$variation = self::get_product_variation_from_global_post();

		if ( ! $variation ) {
			return $args;
		}

		$supports_ajax_add_to_cart = $variation->supports( 'ajax_add_to_cart' ) && $variation->is_purchasable() && $variation->is_in_stock();

		if ( ! $supports_ajax_add_to_cart ) {
			return $args;
		}

		$args['class'] .= ' ajax_add_to_cart';

		return $args;
	}

	/**
	 * Handle price html product variations.
	 *
	 * @param string     $price   The product price.
	 * @param WC_Product $product The product.
	 * @return string
	 */
	public static function handle_price_html_product_variations( $price, $product ) {
		if ( 'product' !== $product->post_type ) {
			return $price;
		}

		$variation = self::get_product_variation_from_global_post();

		if ( ! $variation ) {
			return $price;
		}

		if ( '' === $variation->get_price() ) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks
			$price = apply_filters( 'woocommerce_empty_price_html', '', $variation );
		} elseif ( $variation->is_on_sale() ) {
			$price = wc_format_sale_price( wc_get_price_to_display( $variation, array( 'price' => $variation->get_regular_price() ) ), wc_get_price_to_display( $variation ) ) . $variation->get_price_suffix();
		} else {
			$price = wc_price( wc_get_price_to_display( $variation ) ) . $variation->get_price_suffix();
		}

		return $price;
	}
}
