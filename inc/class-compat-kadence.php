<?php
/**
 * Comatibility with Kadence theme.
 *
 * See: https://wordpress.org/themes/kadence/
 *
 * @package Iconic_WSSV
 * @since 1.10.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Iconic_WSSV_Compat_Kadence Class.
 */
class Iconic_WSSV_Compat_Kadence {

	/**
	 * Init.
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! function_exists( 'Kadence\kadence' ) ) {
			return;
		}

		add_action( 'kadence_entry_archive_hero', array( __CLASS__, 'fix_category_description' ), 5 );
	}

	/**
	 * Fix the category description when the archive page
	 * slug is `product_variation_archive`.
	 *
	 * When the archive page has only product variations,
	 * the slug is `product_variation_archive`. However,
	 * by default, Kadence shows the category description
	 * only when the slug is `product_archive`.
	 *
	 * @param string $slug The archive page slug.
	 *
	 * @return void.
	 */
	public static function fix_category_description( $slug ) {
		if ( 'product_variation_archive' !== $slug ) {
			return;
		}

		if ( ! function_exists( '\Kadence\kadence_entry_archive_header' ) ) {
			return;
		}

		remove_action( 'kadence_entry_archive_hero', 'Kadence\kadence_entry_archive_header', 10 );

		\Kadence\kadence_entry_archive_header( 'product_archive' );
	}

}
