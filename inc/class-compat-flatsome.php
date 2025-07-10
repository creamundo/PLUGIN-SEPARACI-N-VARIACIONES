<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Flatsome compatibility Class
 *
 * @since 1.1.10
 */
class Iconic_WSSV_Compat_Flatsome {
	/**
	 * Init.
	 */
	public static function init() {
		add_filter( 'iconic_wssv_button_args', array( __CLASS__, 'add_to_cart_button_args' ), 10, 2 );
		remove_action( 'flatsome_product_box_actions', 'flatsome_lightbox_button', 50 );
		add_action( 'flatsome_product_box_actions', array( __CLASS__, 'lightbox_button' ), 50 );
		add_filter( 'woocommerce_sale_flash', array( __CLASS__, 'fix_sale_flash_percent' ), 10, 3 );
		add_action( 'pre_get_posts', array( __CLASS__, 'add_variations_to_ajax_queries' ) );
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
		if ( ! function_exists( 'flatsome_option' ) ) {
			return $args;
		}

		$args['button_class'] = sprintf(
			'%s %s button %s is-%s mb-0 is-%s',
			esc_attr( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ? '' : 'ajax_add_to_cart' ),
			$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
			'primary', // Button color.
			esc_attr( flatsome_option( 'add_to_cart_style' ) ), // Button style.
			'small' // Button size.
		);

		if ( flatsome_option( 'add_to_cart_icon' ) !== 'show' ) {
			return $args;
		}

		if ( ! isset( $args['attributes']['style'] ) ) {
			$args['attributes']['style'] = '';
		}

		$args['attributes']['style'] .= 'width:0;margin:0;';
		$args['button_text']          = '<div class="cart-icon tooltip absolute is-small" title="' . esc_attr( $args['button_text'] ) . '"><strong>+</strong></div>';

		return $args;
	}

	/**
	 * Use parent ID if it exists.
	 *
	 * Not ideal as it loads the parent with no pre-selection,
	 * but it's the best that can be done at the moment.
	 */
	public static function lightbox_button() {
		global $product;

		if ( $product->get_type() !== 'variation' && function_exists( 'flatsome_lightbox_button' ) ) {
			flatsome_lightbox_button();

			return;
		}

		if ( get_theme_mod( 'disable_quick_view', 0 ) ) {
			return;
		}

		// Run Quick View Script
		wp_enqueue_script( 'wc-add-to-cart-variation' );

		global $product;

		echo '  <a class="quick-view" data-prod="' . Iconic_WSSV_Product::get_parent_id( $product ) . '" href="#quick-view">' . __( 'Quick View', 'flatsome' ) . '</a>';
	}

	/**
	 * Add variations to the AJAX queries.
	 *
	 * @param WP_Query $query The WP_Query to be modified.
	 *
	 * @return void
	 */
	public static function add_variations_to_ajax_queries( $query ) {
		$post_types      = is_array( $query->get( 'post_type' ) ) ? $query->get( 'post_type' ) : array( $query->get( 'post_type' ) );
		$allowed_actions = array( 'ux_builder_search_posts', 'ux_builder_get_posts', 'flatsome_ajax_search_products' );

		if ( ! wp_doing_ajax() ) {
			return;
		}

		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

		if ( ! $action || ! in_array( $action, $allowed_actions, true ) ) {
			return;
		}

		if ( ! in_array( 'product', $post_types, true ) ) {
			return;
		}

		Iconic_WSSV_Query::add_variations_to_query( $query );
	}

	/**
	 * Replace "Sale!" with the percentage value of discount in the sale flash.
	 *
	 * @param string     $html
	 * @param WP_Post    $post
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public static function fix_sale_flash_percent( $html, $post, $product ) {
		if ( ! $product->is_type( 'variation' ) || ! function_exists( 'flatsome_percentage_format' ) ) {
			return $html;
		}

		$regular_price  = $product->get_regular_price();
		$sale_price     = $product->get_sale_price();
		$bubble_content = round( ( ( floatval( $regular_price ) - floatval( $sale_price ) ) / floatval( $regular_price ) ) * 100 );
		$bubble_content = flatsome_percentage_format( $bubble_content );
		$html           = str_replace( __( 'Sale!', 'woocommerce' ), $bubble_content, $html );

		return $html;
	}
}
