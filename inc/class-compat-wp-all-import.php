<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WSSV_Compat_WP_All_Import.
 *
 * Helper for importing variation data
 *
 * @class    Iconic_WSSV_Compat_WP_All_Import
 * @version  1.0.0
 * @package  Iconic_WSSV
 * @category Class
 * @author   Iconic
 */
class Iconic_WSSV_Compat_WP_All_Import {
	/**
	 * Init.
	 */
	public static function init() {
		if ( ! Iconic_WSSV_Core_Helpers::is_plugin_active( 'wp-all-import-pro/wp-all-import-pro.php' ) ) {
			return;
		}

		add_filter( 'pmxi_custom_field', array( __CLASS__, 'format_custom_field_value' ), 10, 5 );
		add_action( 'pmxi_saved_post', array( __CLASS__, 'on_variation_save' ), 10, 1 );
	}

	/**
	 * Format custom field data on import.
	 *
	 * @param mixed              $value
	 * @param int                $post_id
	 * @param string             $meta_key
	 * @param        $existing_meta_keys
	 * @param        $instance_id
	 *
	 * @return mixed
	 */
	public static function format_custom_field_value( $value, $post_id, $meta_key, $existing_meta_keys, $instance_id ) {
		if ( $meta_key !== '_visibility' ) {
			return $value;
		}

		$post_type = get_post_type( $post_id );

		if ( $post_type !== 'product' && $post_type !== 'product_variation' ) {
			return $value;
		}

		return empty( $value ) ? array( 'hidden' ) : array_map( 'trim', explode( ',', $value ) );
	}

	/**
	 * On variation save
	 *
	 * @param int $post_id
	 */
	public static function on_variation_save( $post_id ) {
		if ( get_post_type( $post_id ) !== 'product_variation' ) {
			return;
		}

		Iconic_WSSV_Product_Variation::set_taxonomies( $post_id );
		Iconic_WSSV_Product_Variation::set_visibility( $post_id );
		Iconic_WSSV_Product_Variation::set_featured_visibility( $post_id );
	}
}
