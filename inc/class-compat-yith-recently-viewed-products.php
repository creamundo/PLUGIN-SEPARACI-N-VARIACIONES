<?php
defined( 'ABSPATH' ) || exit;

/**
 * Yith recently viewed products compatibility Class.
 */
class Iconic_WSSV_Compat_Yith_Recently_Viewed_Products {
	/**
	 * Init.
	 */
	public static function init() {
		if ( ! class_exists( 'YITH_WRVP' ) ) {
			return;
		}

		add_filter( 'yith_wrvp_track_product', array( __CLASS__, 'track_variation' ) );
	}


	/**
	 * This function is executed when single product page is loaded.
	 * Check the URL if the URL has attributes example: /?attribute_pa_color=red
	 * then instead of tracking the Parent product, we will force Yith to
	 * track the specific variation.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return int $product_id Updated product ID of either parent product or variaion.
	 */
	public static function track_variation( $product_id ) {
		global $post;

		if ( 'product' !== $post->post_type ) {
			return $product_id;
		}

		$params = self::get_atts_from_query_string();

		// If params are empty then this is not a variation URL.
		if ( empty( $params ) ) {
			return $product_id;
		}

		// Remove params which dont start with 'attribute_'.
		$attributes = array();
		foreach ( $params as $key => $value ) {
			if ( false === strpos( $key, 'attribute_' ) ) {
				continue;
			}

			$attributes[ $key ] = $value;
		}

		// If attributes are empty then this is not a variation URL.
		if ( empty( $attributes ) ) {
			return $product_id;
		}

		// We have the $attributes now, determine the variation.
		$product = wc_get_product( $product_id );
		if ( class_exists( 'WC_Data_Store' ) ) {
			$data_store                 = WC_Data_Store::load( 'product' );
			$matching_product_variation = $data_store->find_matching_product_variation( $product, $attributes );
		} else {
			$matching_product_variation = $product->get_matching_variation( $attributes );
		}

		return $matching_product_variation ? $matching_product_variation : $product_id;
	}

	/**
	 * Getattributes from Query string.
	 *
	 * @return array
	 */
	public static function get_atts_from_query_string() {
		$atts = array();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET ) ) {
			return $atts;
		}

		foreach ( $_GET as $key => $value ) {
			if ( strpos( $key, 'attribute_' ) !== false ) {
				$atts[ $key ] = wc_clean( $value );
			}
		}

		// phpcs:enable
		return $atts;
	}
}
