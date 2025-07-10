<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WPML Class
 *
 * Only loads if WPML is active. Adds some helper to make sure
 * variation settings are correct based on original product
 *
 * @since 1.1.1
 */
class Iconic_WSSV_Compat_WPML {
	/**
	 * Init.
	 */
	public static function init() {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return;
		}

		add_action( 'save_post', array( __CLASS__, 'set_visibility' ), 10, 1 );

		add_filter( 'iconic_wssv_not_indexed_product_count_query', array( __CLASS__, 'add_language_code_condition' ), 5 );
		add_filter( 'iconic_wssv_l10n_admin_script_data', array( __CLASS__, 'add_data_to_admin_script' ) );
		add_filter( 'iconic_wssv_indexed_product_count_query_args', array( __CLASS__, 'suppress_filters_for_all_languages' ) );
		add_filter( 'iconic_wssv_process_product_query_args', array( __CLASS__, 'suppress_filters_for_all_languages' ) );
	}

	/**
	 * Save: Set visibility on save,
	 * based on original variation ID
	 *
	 * @param int $post_id
	 */
	public static function set_visibility( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( 'product_variation' !== $post_type ) {
			return;
		}

		$original_id = self::get_original_variation_id( $post_id );

		if ( $original_id == $post_id ) {
			return;
		}

		$visibility = get_post_meta( $original_id, '_visibility', true );

		if ( ! empty( $visibility ) ) {
			update_post_meta( $post_id, '_visibility', $visibility );
		} else {
			delete_post_meta( $post_id, '_visibility' );
		}
	}

	/**
	 * Helper: Get original variation ID
	 *
	 * If this is a translated variation,
	 * get the original ID.
	 *
	 * @param int $id
	 *
	 * @return int
	 */
	public static function get_original_variation_id( $id ) {
		$wpml_original_variation_id = get_post_meta( $id, '_wcml_duplicate_of_variation', true );

		if ( $wpml_original_variation_id ) {
			$id = $wpml_original_variation_id;
		}

		return $id;
	}

	/**
	 * Add the language code condition to the SQL query.
	 *
	 * @param string $query the SQL query to count the products not indexed.
	 * @return string
	 */
	public static function add_language_code_condition( $query ) {
		if ( ! is_string( $query ) ) {
			return $query;
		}

		if ( ! function_exists( 'wpml_get_current_language' ) ) {
			return $query;
		}

		$current_language = wpml_get_current_language();

		if ( 'all' === $current_language ) {
			return $query;
		}

		global $wpdb;

		$query = str_ireplace(
			array(
				'LEFT JOIN',
				'WHERE',
			),
			array(
				"LEFT JOIN {$wpdb->prefix}icl_translations wpml_translations ON {$wpdb->prefix}posts.ID = wpml_translations.element_id AND wpml_translations.element_type = CONCAT('post_', {$wpdb->prefix}posts.post_type) LEFT JOIN",
				$wpdb->prepare(
					'WHERE wpml_translations.language_code = %s AND ',
					$current_language
				),
			),
			$query
		);

		return $query;
	}

	/**
	 * Suppress filters when the current language is `all`.
	 *
	 * @param array $query_args The query args used in WP_Query.
	 * @return array
	 */
	public static function suppress_filters_for_all_languages( $query_args ) {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'iconic-ssv-index' ) ) {
			return $query_args;
		}

		if ( empty( $_POST['current_language'] ) ) {
			return $query_args;
		}

		$current_language = sanitize_text_field( wp_unslash( $_POST['current_language'] ) );

		if ( 'all' !== $current_language ) {
			return $query_args;
		}

		$query_args['suppress_filters'] = true;

		return $query_args;
	}

	/**
	 * Add data to admin script.
	 *
	 * This function adds the extra data to be sent on
	 * the `get_product_count` and `process_product_visibility`
	 * requests.
	 *
	 * Since it's a AJAX request, it's necessary to send the
	 * current language.
	 *
	 * @param array $script_data The script data.
	 * @return array
	 */
	public static function add_data_to_admin_script( $script_data ) {
		if ( ! is_array( $script_data ) ) {
			return $script_data;
		}

		if ( ! function_exists( 'wpml_get_current_language' ) ) {
			return $script_data;
		}

		$script_data['get_product_count_request_data']          = array(
			'current_language' => wpml_get_current_language(),
		);
		$script_data['process_product_visibility_request_data'] = array(
			'current_language' => wpml_get_current_language(),
		);

		return $script_data;
	}
}
