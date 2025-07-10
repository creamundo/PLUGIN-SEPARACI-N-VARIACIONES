<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WSSV_Compat_WP_All_Export.
 *
 * Helper for exporting variation data
 *
 * @class    Iconic_WSSV_Compat_WP_All_Export
 * @version  1.0.0
 * @package  Iconic_WSSV
 * @category Class
 * @author   Iconic
 */
class Iconic_WSSV_Compat_WP_All_Export {
	/**
	 * Init.
	 */
	public static function init() {
		if ( ! Iconic_WSSV_Core_Helpers::is_plugin_active( 'wp-all-export-pro/wp-all-export-pro.php' ) ) {
			return;
		}

		add_filter( 'pmxe_woo_field', array( __CLASS__, 'format_fields' ), 10, 3 );
	}

	/**
	 * Format fields during export.
	 *
	 * @param string $value
	 * @param string $field_name
	 * @param int    $record_id
	 *
	 * @return string
	 */
	public static function format_fields( $value, $field_name, $record_id ) {
		if ( $field_name !== '_visibility' && $field_name !== 'product_visibility' ) {
			return $value;
		}

		$post_type = get_post_type( $record_id );

		if ( $post_type !== 'product_variation' ) {
			return $value;
		}

		$value = Iconic_WSSV_Product_Variation::get_visibility( $record_id );

		return implode( ',', $value );
	}
}
