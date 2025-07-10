<?php
/**
 * Compatibility with WooCommerce Multilingual & Multicurrency plugin.
 *
 * @see https://wpml.org/documentation/related-projects/woocommerce-multilingual/
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Multilingual & Multicurrency compatibility Class.
 *
 * @since 1.22.0
 */
class Iconic_WSSV_Compat_WooCommerce_Multilingual {
	/**
	 * Add action and filters
	 *
	 * @since 1.22.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! defined( 'WCML_VERSION' ) ) {
			return;
		}

		add_action( 'wcml_after_sync_product_data', [ __CLASS__, 'sync_variation_data' ], 50, 3 );
	}

	/**
	 * Sync variation data.
	 *
	 * @param int    $original_product_id The original product ID.
	 * @param int    $tr_product_id       The translated product ID.
	 * @param string $lang                The translated language.
	 * @return void
	 */
	public static function sync_variation_data( $original_product_id, $tr_product_id, $lang ) {
		$tr_product = wc_get_product( $tr_product_id );

		if ( ! $tr_product ) {
			return;
		}

		if ( ! $tr_product->is_type( 'variable' ) ) {
			return;
		}

		$tr_variations = $tr_product->get_children();

		foreach ( $tr_variations as $tr_variation_id ) {
			$original_variation_id = (int) Iconic_WSSV_Compat_WPML::get_original_variation_id( $tr_variation_id );

			if ( empty( $original_variation_id ) ) {
				continue;
			}

			if ( $tr_variation_id === $original_variation_id ) {
				continue;
			}

			update_post_meta( $tr_variation_id, '_jck_wssv_display_title', get_post_meta( $original_variation_id, '_jck_wssv_display_title', true ) );

			self::sync_variation_taxonomy( $original_variation_id, $tr_variation_id, $lang, 'product_cat' );
			self::sync_variation_taxonomy( $original_variation_id, $tr_variation_id, $lang, 'product_tag' );
		}
	}

	/**
	 * Sync variation taxonomy.
	 *
	 * @param int    $original_variation_id The original variation ID.
	 * @param int    $tr_variation_id       The translated variation ID.
	 * @param string $lang                  The translated language.
	 * @param string $taxonomy              The taxonomy name.
	 * @return void
	 */
	protected static function sync_variation_taxonomy( $original_variation_id, $tr_variation_id, $lang, $taxonomy ) {
		global $sitepress;

		$current_language = $sitepress->get_current_language();

		update_post_meta( $tr_variation_id, "_manage_{$taxonomy}", get_post_meta( $original_variation_id, "_manage_{$taxonomy}", true ) );

		$original_product_categories = array_filter( (array) get_post_meta( $original_variation_id, "_jck_wssv_variation_{$taxonomy}", true ) );
		$tr_product_categories       = [];

		$sitepress->switch_lang( $lang );
		foreach ( $original_product_categories as $original_category_id ) {
			$tr_id = $sitepress->get_element_trid( $original_category_id, "tax_{$taxonomy}" );

			if ( empty( $tr_id ) ) {
				continue;
			}

			$translations = $sitepress->get_element_translations( $tr_id );

			if ( empty( $translations[ $lang ] ) ) {
				continue;
			}

			$element_id = $translations[ $lang ]->element_id;

			$tr_term = get_term_by( 'term_id', $element_id, $taxonomy );

			if ( empty( $tr_term ) ) {
				continue;
			}

			$tr_product_categories[] = $tr_term->term_id;
		}

		$sitepress->switch_lang( $current_language );

		update_post_meta( $tr_variation_id, "_jck_wssv_variation_{$taxonomy}", $tr_product_categories );
		wp_set_post_terms( $tr_variation_id, $tr_product_categories, $taxonomy );
	}
}
