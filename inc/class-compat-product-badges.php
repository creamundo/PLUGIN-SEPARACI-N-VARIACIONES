<?php
/**
 * Compatibility with Product Badges plugin.
 *
 * @see https://woocommerce.com/products/product-badges/
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Product Badges compatibility Class
 *
 * @since 1.12.0
 */
class Iconic_WSSV_Compat_Product_Badges {
	/**
	 * Add action and filters
	 *
	 * @since 1.12.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! class_exists( 'WCPB_Product_Badges' ) ) {
			return;
		}

		add_action( 'woocommerce_before_shop_loop_item', array( __CLASS__, 'add_filters_to_get_product_variation_taxonomy_ids' ), 8 );
		add_action( 'woocommerce_before_shop_loop_item', array( __CLASS__, 'remove_filter_to_get_product_variation_taxonomy_ids' ), 11 );
	}

	/**
	 * Add filters to get product variation taxonomy ids.
	 *
	 * The Product Badges plugin retrieves the product category and tag ids
	 * from the variation instead of the parent product. This is correct
	 * only when categories or tags are managed on the variation. If they
	 * are managed on the parent product, we need to retrieve the ids from
	 * the parent product instead.
	 *
	 * We add these filters to intercept the call to get the category and
	 * tag ids and return the correct ids.
	 *
	 * The taxonomies are retrieved in the function WCPB_Product_Badges_Public::badge().
	 *
	 * @since 1.12.0
	 *
	 * @return void
	 */
	public static function add_filters_to_get_product_variation_taxonomy_ids() {
		add_filter( 'woocommerce_product_variation_get_category_ids', array( __CLASS__, 'get_product_variation_category_ids' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_get_tag_ids', array( __CLASS__, 'get_product_variation_tags_ids' ), 10, 2 );
	}

	/**
	 * Remove filters to get product variation taxonomy ids.
	 *
	 * @since 1.12.0
	 *
	 * @return void
	 */
	public static function remove_filter_to_get_product_variation_taxonomy_ids() {
		remove_filter( 'woocommerce_product_variation_get_category_ids', array( __CLASS__, 'get_product_variation_category_ids' ), 10 );
		remove_filter( 'woocommerce_product_variation_get_tag_ids', array( __CLASS__, 'get_product_variation_tags_ids' ), 10 );
	}

	/**
	 * Get product variation category ids.
	 *
	 * If the product variation is managing the product category taxonomy,
	 * return the category ids from the variation. Otherwise, return the
	 * category ids from the parent product.
	 *
	 * @since 1.12.0
	 *
	 * @param array                $value            The category ids.
	 * @param WC_Product_Variation $product_variation The product variation.
	 *
	 * @return array
	 */
	public static function get_product_variation_category_ids( $value, $product_variation ) {
		if ( empty( $product_variation ) ) {
			return $value;
		}

		if ( Iconic_WSSV_Product_Variation::get_manage_taxonomy( $product_variation->get_id(), 'product_cat' ) ) {
			$categories = wp_get_post_terms( $product_variation->get_id(), 'product_cat', array( 'fields' => 'ids' ) );

			if ( is_wp_error( $categories ) ) {
				return $value;
			}

			return $categories;
		}

		$parent_product = wc_get_product( $product_variation->get_parent_id() );

		if ( ! $parent_product ) {
			return $value;
		}

		$value = $parent_product->get_category_ids();

		return $value;
	}

	/**
	 * Get product variation tag ids.
	 *
	 * If the product variation is managing the product tag taxonomy,
	 * return the tag ids from the variation. Otherwise, return the
	 * tag ids from the parent product.
	 *
	 * @since 1.12.0
	 *
	 * @param array                $value            The tag ids.
	 * @param WC_Product_Variation $product_variation The product variation.
	 *
	 * @return array
	 */
	public static function get_product_variation_tags_ids( $value, $product_variation ) {
		if ( empty( $product_variation ) ) {
			return $value;
		}

		if ( Iconic_WSSV_Product_Variation::get_manage_taxonomy( $product_variation->get_id(), 'product_tag' ) ) {
			$tags = wp_get_post_terms( $product_variation->get_id(), 'product_tag', array( 'fields' => 'ids' ) );

			if ( is_wp_error( $tags ) ) {
				return $value;
			}

			return $tags;
		}

		$parent_product = wc_get_product( $product_variation->get_parent_id() );

		if ( ! $parent_product ) {
			return $value;
		}

		$value = $parent_product->get_tag_ids();

		return $value;
	}
}
