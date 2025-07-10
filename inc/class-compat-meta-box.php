<?php
/**
 * Compatibility with Meta Box plugin.
 *
 * @see https://metabox.io/
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Meta Box compatibility Class.
 *
 * @since 1.25.0
 */
class Iconic_WSSV_Compat_Meta_Box {
	/**
	 * Add action and filters
	 *
	 * @since 1.25.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! defined( 'RWMB_VER' ) ) {
			return;
		}

		add_filter( 'rwmb_meta', [ __CLASS__, 'get_meta_from_parent_product' ], 50, 4 );
	}

	/**
	 * Get the meta from parent product.
	 *
	 * @param mixed    $value   The value.
	 * @param string   $key     Meta key.
	 * @param array    $args    Array of arguments.
	 * @param int|null $post_id Post ID. null for current post.
	 * @return mixed
	 */
	public static function get_meta_from_parent_product( $value, $key, $args, $post_id ) {
		if ( ! empty( $value ) ) {
			return $value;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $value;
		}

		if ( ! $product->is_type( 'variation' ) ) {
			return $value;
		}

		$parent_value = rwmb_meta( $key, $args, $product->get_parent_id() );

		return $parent_value;
	}
}
