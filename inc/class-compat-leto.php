<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Leto theme compatibility Class
 *
 * @since 1.1.13
 */
class Iconic_WSSV_Compat_Leto {
	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'get_template_part_template-parts/woocommerce', array( __CLASS__, 'quick_view_switch_to_parent' ), 10, 2 );
		add_filter( 'woocommerce_dropdown_variation_attribute_options_args', array( __CLASS__, 'quick_view_select_variation_options' ), 10, 1 );
	}

	/**
	 * Switch quick view to use parent product.
	 *
	 * @param string $slug
	 * @param string $name
	 */
	public static function quick_view_switch_to_parent( $slug, $name ) {
		if ( $name !== 'modal' ) {
			return;
		}

		global $product;

		if ( is_a( $product, 'WC_Product_Variation' ) ) {
			$GLOBALS['ssv_leto_switch_back'] = $product;
			$parent_product                  = $product->get_parent_id();

			$product     = wc_get_product( $parent_product );
			$post_object = get_post( $product->get_id() );
			setup_postdata( $GLOBALS['post'] =& $post_object );
		}
	}

	/**
	 * Preselect variation options in quick view.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public static function quick_view_select_variation_options( $args = array() ) {
		if ( empty( $GLOBALS['ssv_leto_switch_back'] ) ) {
			return $args;
		}

		$variation_attributes = $GLOBALS['ssv_leto_switch_back']->get_attributes();

		if ( isset( $variation_attributes[ $args['attribute'] ] ) ) {
			$args['selected'] = $variation_attributes[ $args['attribute'] ];
		}

		return $args;
	}
}
