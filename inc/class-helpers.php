<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WSSV_Helpers.
 *
 * @class    Iconic_WSSV_Helpers
 * @version  1.0.0
 * @author   Iconic
 */
class Iconic_WSSV_Helpers {
	/**
	 * Converts a string (e.g. yes or no) to a bool.
	 *
	 * @since 3.0.0
	 *
	 * @param string $string
	 *
	 * @return bool
	 */
	public static function string_to_bool( $string ) {
		return is_bool( $string ) ? $string : ( 'yes' === $string || 1 === $string || 'true' === $string || '1' === $string );
	}

	/**
	 * Get allowed HTML for title fields.
	 *
	 * @return array
	 */
	public static function wp_kses_allowed_html_title() {
		$allowed_html         = wp_kses_allowed_html();
		$allowed_html['br']   = array();
		$allowed_html['span'] = array();

		return $allowed_html;
	}

	/**
	 * Is query for products and variations?
	 *
	 * @param string $where The WHERE clause of the query.
	 *
	 * @return bool
	 */
	public static function query_has_products_and_variations( $where ) {
		/**
		 * This RegEx tries to match the pattern `post_type in ( 'product', 'product_variation' )`
		 *
		 * +      - one or more
		 * *      - 0 or more
		 * \s     - space
		 * \r     - carriage return (Enter)
		 * [\s\S] - any character
		 */
		$post_type_in_regex = "post_type[\s\r]+IN[\s\r]+\([\s\r]*['|\"](product|product_variation)['|\"],[\s\S]*['|\"](product|product_variation)['|\"][\s\r]*\)";
		/**
		 * This RegEx tries to match the pattern `post_type = 'product' post_type = 'product_variation'`
		 */
		$post_type_equals_regex = "post_type[\s\r]*=[\s\r]*['|\"](product|product_variation)['|\"][\s\S]*post_type[\s\r]*=[\s\r]*['|\"](product|product_variation)['|\"]";

		preg_match( "/{$post_type_in_regex}|{$post_type_equals_regex}/", $where, $matches );

		if ( ! $matches ) {
			return false;
		}

		// Remove the first element that contains the text that matched the full pattern.
		array_shift( $matches );

		$post_types = array_filter( $matches );

		return in_array( 'product', $post_types, true ) && in_array( 'product_variation', $post_types, true );
	}

	/**
	 * Get term name with parent name. E.g. 'Parent » Child'.
	 *
	 * @param WP_Term $term     The term object.
	 * @param string  $taxonomy The taxonomy name.
	 *
	 * @return string
	 */
	public static function get_term_name_with_parent_name( $term, $taxonomy = '' ) {
		if ( empty( $term->name ) ) {
			return '';
		}

		$term_name = $term->name;

		if ( empty( $term->parent ) ) {
			return $term_name;
		}

		$parent_term = get_term_by( 'term_id', $term->parent, $taxonomy );

		if ( empty( $parent_term->name ) ) {
			return $term_name;
		}

		$term_name = $parent_term->name . ' » ' . $term_name;

		return $term_name;
	}

	/**
	 * Filter in variable and variation products.
	 *
	 * If the product IDs are not an array, it will return an empty array.
	 *
	 * @param array $product_ids The product IDs.
	 * @return array
	 */
	public static function filter_in_variable_and_variation_products( $product_ids ) {
		if ( ! is_array( $product_ids ) ) {
			return array();
		}

		/**
		 * Filter whether it should skip filter in variable and variation products.
		 *
		 * This filter can be helpful if you want to filter in all products,
		 * including variable and variation products.
		 *
		 * @since 1.16.0
		 * @hook iconic_wssv_skip_filter_in_variable_and_variation_products
		 * @param  bool $skip_filter Whether skip the filter. Default: false.
		 * @return bool New value
		 */
		$skip_filter = apply_filters( 'iconic_wssv_skip_filter_in_variable_and_variation_products', false );

		if ( $skip_filter ) {
			return $product_ids;
		}

		$filtered_product_ids = array_filter(
			$product_ids,
			function( $product_id ) {
				$product = wc_get_product( $product_id );

				if ( empty( $product ) ) {
					return false;
				}

				return $product->is_type( 'variable' ) || $product->is_type( 'variation' );
			}
		);

		return $filtered_product_ids;
	}

	/**
	 * Get the category IDs to apply the visibility settings.
	 *
	 * This function returns an array following the structure:
	 * array(
	 *   'to_variations'       => array,
	 *   'to_variables'        => array,
	 *   'is_empty'            => bool,
	 *   'has_same_categories' => bool,
	 * )
	 *
	 * @return array
	 */
	public static function get_category_ids_to_apply_visibility_settings() {
		$data = array(
			'to_variations'       => array(),
			'to_variables'        => array(),
			'is_empty'            => true,
			'has_same_categories' => true,
		);

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'iconic-ssv-index' ) ) {
			/**
			 * Filter the category IDs to apply visibility settings.
			 *
			 * The returned data should follow the structure:
			 * array(
			 *    'to_variations'       => array,
			 *    'to_variables'        => array,
			 *    'is_empty'            => bool,
			 *    'has_same_categories' => bool,
			 * )
			 *
			 * @since 1.16.0
			 * @hook iconic_wssv_category_ids_to_apply_visibility_settings
			 * @param  array $data The category_ids and the data if they are empty and have the same categories.
			 * @return array New value
			 */
			return apply_filters( 'iconic_wssv_category_ids_to_apply_visibility_settings', $data );
		}

		$category_ids_to_apply_visibility_settings_to_variations =
			empty( $_POST['iconic_wssv_categories_to_apply_visibility_settings_to_variations'] )
				? array()
				: (array) map_deep( wp_unslash( $_POST['iconic_wssv_categories_to_apply_visibility_settings_to_variations'] ), 'sanitize_text_field' );

		$category_ids_to_apply_visibility_settings_to_variables =
			empty( $_POST['iconic_wssv_categories_to_apply_visibility_settings_to_variables'] )
				? array()
				: (array) map_deep( wp_unslash( $_POST['iconic_wssv_categories_to_apply_visibility_settings_to_variables'] ), 'sanitize_text_field' );

		$data['to_variations'] = $category_ids_to_apply_visibility_settings_to_variations;
		$data['to_variables']  = $category_ids_to_apply_visibility_settings_to_variables;
		$data['is_empty']      = empty( $category_ids_to_apply_visibility_settings_to_variations ) && empty( $category_ids_to_apply_visibility_settings_to_variables );

		if ( ! $data['is_empty'] ) {
			$data['has_same_categories'] =
				empty(
					array_diff(
						$category_ids_to_apply_visibility_settings_to_variations,
						$category_ids_to_apply_visibility_settings_to_variables
					)
				)
				&&
				empty(
					array_diff(
						$category_ids_to_apply_visibility_settings_to_variables,
						$category_ids_to_apply_visibility_settings_to_variations
					)
				);
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks
		return apply_filters( 'iconic_wssv_category_ids_to_apply_visibility_settings', $data );
	}

	/**
	 * Validate `product_visibility` on `set_object_terms`.
	 *
	 * In some scenarios, WooCommerce sets the object's terms and removes
	 * the previous saved value in `product_visibility`. In that cases,
	 * SSV should re-add the `product_visibility`.
	 *
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 * @return bool
	 */
	public static function validate_product_visibility_on_set_object_terms( $taxonomy, $append, $old_tt_ids ) {
		if ( $append ) {
			return false;
		}

		if ( 'product_visibility' !== $taxonomy ) {
			return false;
		}

		if ( empty( $old_tt_ids ) ) {
			return false;
		}

		return true;
	}
}
