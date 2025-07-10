<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Relevanssi compatibility Class.
 */
class Iconic_WSSV_Compat_Relevanssi {
	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'change_post_type_string_to_array' ), 1 );
	}

	/**
	 * Convert 'post_type' query argument to array if it is an string.
	 */
	public static function change_post_type_string_to_array() {
		global $wp_query;

		$post_types = $wp_query->query_vars['post_type'];

		if ( is_array( $post_types ) ) {
			return;
		}

		if ( false !== strpos( $post_types, ',' ) ) {
			$wp_query->query_vars['post_type'] = explode( ',', $wp_query->query_vars['post_type'] );
		}
	}
}
