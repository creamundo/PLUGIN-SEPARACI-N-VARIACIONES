<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WSSV_Product_Variation.
 *
 * @class    Iconic_WSSV_Product_Variation
 * @version  1.0.0
 * @author   Iconic
 */
class Iconic_WSSV_Product {
	/**
	 * Run.
	 */
	public static function init() {
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'after_product_object_save' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'product_data_visibility' ), 20 );
	}

	/**
	 * After a product is saved.
	 *
	 * @param WC_Product_Variable $product
	 * @param                     $data_store
	 */
	public static function after_product_object_save( $product, $data_store ) {
		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		self::update_visibility( $product->get_id() );
		self::set_attributes_to_children( $product );
	}

	/**
	 * Assign any attributes "not used for variations" to variations.
	 * This means they show up when filtered.
	 *
	 * @param WC_Product_Variable|int $product
	 */
	public static function set_attributes_to_children( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$variations = $product->get_children();

		if ( empty( $variations ) ) {
			return;
		}

		foreach ( $variations as $i => $variation_id ) {
			Iconic_WSSV_Product_Variation::remove_unused_attributes( $variation_id, $product );

			// Assign all attributes from parent to child when child has "any" attribute value.
			Iconic_WSSV_Product_Variation::apply__any__attribute( $variation_id, $product );
		}

		$attributes = $product->get_attributes();

		if ( empty( $attributes ) ) {
			return;
		}

		global $jck_wssv;

		$product_id = $product->get_id();

		// Remove "non variation" attributes from variations when removed from parent.
		foreach ( $attributes as $taxonomy => $attribute_data ) {
			if ( $attribute_data['is_variation'] ) {
				continue;
			}

			$terms = wp_get_post_terms( $product_id, $taxonomy );

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $variations as $i => $variation_id ) {
				$term_ids = array();

				foreach ( $terms as $term ) {
					$term_ids[] = $term->term_id;
				}

				wp_set_object_terms( $variation_id, $term_ids, $taxonomy );

				$jck_wssv->delete_count_transient( $taxonomy, $terms[0]->term_taxonomy_id );
			}
		}

	}

	/**
	 * On update visibility.
	 *
	 * @param int $product_id
	 */
	public static function update_visibility( $product_id ) {
		if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) {
			return;
		}

		$save   = filter_input( INPUT_POST, 'save' );
		$action = filter_input( INPUT_POST, 'action' );

		if ( $save !== __( 'Update' ) && $action !== 'iconic_wssv_process_product_visibility' ) {
			return;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		$visibility            = self::get_catalog_visibility( $product );
		$exclude_from_filtered = isset( $_POST['iconic_wssv_exclude_from_filtered'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$visibility_terms      = self::get_visibility_term_slugs( $product->get_id() );

		if ( 'iconic_wssv_process_product_visibility' !== $action ) {
			if ( $exclude_from_filtered ) {
				if ( ! in_array( 'exclude-from-filtered', $visibility_terms, true ) ) {
					$visibility_terms[] = 'exclude-from-filtered';
				}
			} else {
				$visibility_terms = Iconic_WSSV::unset_item_by_value( $visibility_terms, 'exclude-from-filtered' );
			}
		}

		// Process visibility setting here.
		if ( 'hidden' === $visibility ) {
			$visibility_terms[] = 'exclude-from-search';
			$visibility_terms[] = 'exclude-from-catalog';
			$visibility_terms[] = 'exclude-from-filtered';
		} elseif ( 'catalog' === $visibility ) {
			$visibility_terms   = Iconic_WSSV::unset_item_by_value( $visibility_terms, 'exclude-from-catalog' );
			$visibility_terms[] = 'exclude-from-search';
		} elseif ( 'search' === $visibility ) {
			$visibility_terms   = Iconic_WSSV::unset_item_by_value( $visibility_terms, 'exclude-from-search' );
			$visibility_terms[] = 'exclude-from-catalog';
		} elseif ( 'visible' === $visibility ) {
			$visibility_terms = Iconic_WSSV::unset_item_by_value( $visibility_terms, 'exclude-from-catalog' );
			$visibility_terms = Iconic_WSSV::unset_item_by_value( $visibility_terms, 'exclude-from-search' );
		}

		$visibility_terms = array_unique( $visibility_terms );

		if ( ! is_wp_error( wp_set_post_terms( $product->get_id(), $visibility_terms, 'product_visibility', false ) ) ) {
			$visibility = self::get_catalog_visibility( $product );
			do_action( 'woocommerce_product_set_visibility', $product->get_id(), $visibility );
		}

		do_action( 'iconic_wssv_product_processed', $product->get_id() );
	}

	/**
	 * Get catalog visibility.
	 *
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public static function get_catalog_visibility( $product ) {
		if ( method_exists( $product, 'get_catalog_visibility' ) ) {
			$visibility = $product->get_catalog_visibility();
		} else {
			$visibility = get_post_meta( $product->get_id(), '_visibility', true );
		}

		return apply_filters( 'iconic_wssv_product_visibility', $visibility, $product );
	}

	/**
	 * Get parent ID.
	 *
	 * @param WC_Product $product
	 *
	 * @return int
	 */
	public static function get_parent_id( $product ) {
		if ( method_exists( $product, 'get_parent_id' ) ) {
			return $product->get_parent_id();
		} else {
			return $product->get_parent();
		}
	}

	public static function product_data_visibility() {
		if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) {
			return;
		}

		global $post;

		if ( 'product' !== $post->post_type ) {
			return;
		}

		$visibility_terms      = self::get_visibility_term_slugs( $post->ID );
		$exclude_from_filtered = in_array( 'exclude-from-filtered', $visibility_terms );
		?>
		<div class="misc-pub-section show_if_variable" style="display: none;">
			<input type="checkbox" name="iconic_wssv_exclude_from_filtered" id="iconic-wssv-exclude-from-filtered" <?php checked( $exclude_from_filtered ); ?> />
			<label for="iconic-wssv-exclude-from-filtered"><?php _e( 'Exclude from filtered results', 'iconic-wssv' ); ?></label>
		</div>
		<?php
	}

	/**
	 * Get visibility term slugs.
	 *
	 * @param int $product_id
	 *
	 * @return array
	 */
	public static function get_visibility_term_slugs( $product_id ) {
		$terms = wp_get_post_terms( $product_id, 'product_visibility' );

		return wp_list_pluck( $terms, 'slug' );
	}
}
