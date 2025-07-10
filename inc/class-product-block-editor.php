<?php
/**
 * Integrate with WooCommerce Product Block editor.
 *
 * @see https://github.com/woocommerce/woocommerce/tree/trunk/docs/product-editor-development
 * @package Iconic_WSSV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\GroupInterface;

/**
 * WooCommerce Product Block editor integration Class
 *
 * @since 1.19.0
 */
class Iconic_WSSV_Product_Block_Editor {
	/**
	 * Initialize the hooks.
	 *
	 * @return void
	 */
	public static function init() {
		self::handle_variable_product();
		self::handle_product_variations();
	}

	/**
	 * Handle the variable product to integrate with the Product Block editor.
	 *
	 * @return void
	 */
	protected static function handle_variable_product() {
		add_filter( 'woocommerce_rest_prepare_product_object', [ __CLASS__, 'add_variable_product_data_to_rest_response' ], 10, 3 );

		add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-catalog-catalog-visibility', [ __CLASS__, 'add_variable_product_visibility_field' ] );
		add_action( 'woocommerce_rest_insert_product_object', [ __CLASS__, 'save_variable_product_visibility_field' ], 10, 2 );
	}

	/**
	 * Handle the product variations to integrate with the Product Block editor.
	 *
	 * @return void
	 */
	protected static function handle_product_variations() {
		add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-variation-visibility', [ __CLASS__, 'add_product_variation_visibility_fields' ] );
		add_action( 'woocommerce_block_template_area_product-form_after_add_block_general', [ __CLASS__, 'add_product_variation_manage_categories_section' ] );
		add_action( 'woocommerce_block_template_area_product-form_after_add_block_general', [ __CLASS__, 'add_product_variation_manage_tags_section' ] );
		add_action( 'woocommerce_rest_insert_product_variation_object', [ __CLASS__, 'save_product_variation_fields' ], 10, 2 );

		add_filter( 'woocommerce_rest_prepare_product_variation_object', [ __CLASS__, 'add_product_variation_data_to_rest_response' ], 10, 3 );
	}

	/**
	 * Add product variable data to the Product Block editor REST response.
	 *
	 * @param WP_REST_Response $response The Product Block editor REST response.
	 * @param WC_Product       $product  The edited product.
	 * @param WP_REST_Request  $request  The request to retrieve the product object.
	 * @return WP_REST_Response
	 */
	public static function add_variable_product_data_to_rest_response( WP_REST_Response $response, WC_Product $product, WP_REST_Request $request ) {
		if ( ! FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			return $response;
		}

		if ( 'edit' !== $request->get_param( 'context' ) ) {
			return $response;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return $response;
		}

		$visibility_terms = Iconic_WSSV_Product::get_visibility_term_slugs( $product->get_id() );

		$response->data['iconic_wssv_exclude_from_filtered'] = in_array( 'exclude-from-filtered', $visibility_terms, true );

		return $response;
	}

	/**
	 * Add SSV product variable field to the Product Block editor.
	 *
	 * The SSV field is added after the `Hide from search results` field.
	 *
	 * @param BlockInterface $product_catalog_visibility_field_block The `Hide from search results` field.
	 * @return void
	 */
	public static function add_variable_product_visibility_field( BlockInterface $product_catalog_visibility_field_block ) {
		if ( ! FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			return;
		}

		$parent = $product_catalog_visibility_field_block->get_parent();

		if ( ! method_exists( $parent, 'add_block' ) ) {
			return;
		}

		$parent->add_block(
			[
				'id'             => 'iconic-wssv-exclude-from-filtered',
				'order'          => $product_catalog_visibility_field_block->get_order() + 5,
				'blockName'      => 'woocommerce/product-checkbox-field',
				'attributes'     => [
					'property' => 'iconic_wssv_exclude_from_filtered',
					'label'    => __( 'Hide from filtered results', 'iconic-wssv' ),
				],
				'hideConditions' => [
					[
						'expression' => 'editedProduct.type !== "variable"',
					],
				],
			]
		);
	}

	/**
	 * Save product variable visibility field.
	 *
	 * @param WC_Product      $product The product object saved.
	 * @param WP_REST_Request $request The HTTP request.
	 * @return void
	 */
	public static function save_variable_product_visibility_field( WC_Product $product, WP_REST_Request $request ) {
		if ( ! FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			return;
		}

		$children = $product->get_children();

		if ( empty( $children ) ) {
			return;
		}

		foreach ( $children as $variation_id ) {
			Iconic_WSSV_Product_Variation::set_taxonomies( $variation_id );
			Iconic_WSSV_Product_Variation::refresh_title( $variation_id );
		}

		$exclude_from_filtered = $request->get_param( 'iconic_wssv_exclude_from_filtered' );

		if ( ! is_bool( $exclude_from_filtered ) ) {
			return;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$visibility       = Iconic_WSSV_Product::get_catalog_visibility( $product );
		$visibility_terms = Iconic_WSSV_Product::get_visibility_term_slugs( $product->get_id() );

		if ( $exclude_from_filtered ) {
			if ( ! in_array( 'exclude-from-filtered', $visibility_terms, true ) ) {
				$visibility_terms[] = 'exclude-from-filtered';
			}
		} else {
			$visibility_terms = Iconic_WSSV::unset_item_by_value( $visibility_terms, 'exclude-from-filtered' );
		}

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

		wp_set_post_terms( $product->get_id(), $visibility_terms, 'product_visibility', false );
	}

	/**
	 * Add SSV product variation visibility fields like `Show in Search Results?` and
	 * `Show in Catalog?`.
	 *
	 * @param BlockInterface $wc_product_variation_visibility_field_block The `Hide in product catalog` field block.
	 * @return void
	 */
	public static function add_product_variation_visibility_fields( BlockInterface $wc_product_variation_visibility_field_block ) {
		if ( ! FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			return;
		}

		$parent = $wc_product_variation_visibility_field_block->get_parent();

		if ( ! method_exists( $parent, 'add_block' ) ) {
			return;
		}

		$default_block_config = [
			'order'     => $wc_product_variation_visibility_field_block->get_order() + 5,
			'blockName' => 'woocommerce/product-checkbox-field',
		];

		$parent->add_block(
			wp_parse_args(
				[
					'id'         => 'iconic-wssv-show-in-search-results',
					'attributes' => [
						'property' => 'jck_wssv_variable_show_search',
						'label'    => __( 'Show in Search Results?', 'iconic-wssv' ),
					],
				],
				$default_block_config
			)
		);

		$parent->add_block(
			wp_parse_args(
				[
					'id'         => 'iconic-wssv-show-in-filtered-results',
					'attributes' => [
						'property' => 'jck_wssv_variable_show_filtered',
						'label'    => __( 'Show in Filtered Results?', 'iconic-wssv' ),
					],
				],
				$default_block_config
			)
		);

		$parent->add_block(
			wp_parse_args(
				[
					'id'         => 'iconic-wssv-show-in-catalog',
					'attributes' => [
						'property' => 'jck_wssv_variable_show_catalog',
						'label'    => __( 'Show in Catalog?', 'iconic-wssv' ),
					],
				],
				$default_block_config
			)
		);

		$parent->add_block(
			wp_parse_args(
				[
					'id'         => 'iconic-wssv-featured',
					'attributes' => [
						'property' => 'jck_wssv_variable_featured',
						'label'    => __( 'Featured', 'iconic-wssv' ),
					],
				],
				$default_block_config
			)
		);

		$parent->add_block(
			wp_parse_args(
				[
					'id'         => 'iconic-wssv-disable-add-to-cart',
					'attributes' => [
						'property' => 'jck_wssv_variable_disable_add_to_cart',
						'label'    => __( 'Disable "Add to Cart"?', 'iconic-wssv' ),
						'tooltip'  => __( 'Use the "Select Options" button in product listings instead of "Add to Cart".', 'iconic-wssv' ),
					],
				],
				$default_block_config
			)
		);

		$parent->add_block(
			wp_parse_args(
				[
					'id'         => 'iconic-wssv-listings-only',
					'attributes' => [
						'property' => 'jck_wssv_variable_listings_only',
						'label'    => __( 'Listings Only?', 'iconic-wssv' ),
						'tooltip'  => __( "Enable to only show this variation in product listings. It won't be purchasable on the single product page.", 'iconic-wssv' ),
					],
				],
				$default_block_config
			)
		);
	}

	/**
	 * Add section to manage product variation categories.
	 *
	 * @param GroupInterface $general_group The General group in the Product Block editor.
	 * @return void
	 */
	public static function add_product_variation_manage_categories_section( GroupInterface $general_group ) {
		$section = $general_group->add_section(
			array(
				'id'             => 'iconic-wssv-variation-categories-section',
				'order'          => 35,
				'attributes'     => [
					'title'       => 'Variation categories',
					'description' => __( 'Allow selecting categories at variation level.', 'iconic-wssv' ),
				],
				'hideConditions' => [
					[
						'expression' => '!editedProduct.parent_id',
					],
				],
			)
		);

		$section->add_block(
			array(
				'id'         => 'iconic-wssv-variation-manage-product-cat',
				'order'      => 5,
				'blockName'  => 'woocommerce/product-checkbox-field',
				'attributes' => [
					'property' => 'jck_wssv_variable_manage_product_cat',
					'label'    => __( 'Manage categories?', 'iconic-wssv' ),
				],
			)
		);

		$section->add_block(
			array(
				'id'             => 'iconic-wssv-variation-product-cat',
				'order'          => 5,
				'blockName'      => 'woocommerce/product-taxonomy-field',
				'attributes'     => [
					'slug'               => 'product_cat',
					'property'           => 'jck_wssv_variation_product_cat',
					'label'              => __( 'Categories', 'woocommerce' ),
					'createTitle'        => __( 'Create new category', 'woocommerce' ),
					'parentTaxonomyText' => __( 'Parent category', 'woocommerce' ),
				],
				'hideConditions' => [
					[
						'expression' => '!editedProduct.jck_wssv_variable_manage_product_cat',
					],
				],
			)
		);
	}

	/**
	 * Add section to manage product variation tags.
	 *
	 * @param GroupInterface $general_group The General group in the Product Block editor.
	 * @return void
	 */
	public static function add_product_variation_manage_tags_section( GroupInterface $general_group ) {
		$section = $general_group->add_section(
			array(
				'id'             => 'iconic-wssv-variation-tags-section',
				'order'          => 35,
				'attributes'     => [
					'title'       => 'Variation tags',
					'description' => __( 'Allow selecting tags at variation level.', 'iconic-wssv' ),
				],
				'hideConditions' => [
					[
						'expression' => '!editedProduct.parent_id',
					],
				],
			)
		);

		$section->add_block(
			array(
				'id'         => 'iconic-wssv-variation-manage-product-tag',
				'order'      => 6,
				'blockName'  => 'woocommerce/product-checkbox-field',
				'attributes' => [
					'property' => 'jck_wssv_variable_manage_product_tag',
					'label'    => __( 'Manage tags?', 'iconic-wssv' ),
				],
			)
		);

		$section->add_block(
			array(
				'id'             => 'iconic-wssv-variation-product-tag',
				'order'          => 6,
				'blockName'      => 'woocommerce/product-taxonomy-field',
				'attributes'     => [
					'slug'        => 'product_tag',
					'property'    => 'jck_wssv_variation_product_tag',
					'label'       => __( 'Tags', 'woocommerce' ),
					'createTitle' => __( 'Create new tag', 'woocommerce' ),
				],
				'hideConditions' => [
					[
						'expression' => '!editedProduct.jck_wssv_variable_manage_product_tag',
					],
				],
			)
		);
	}

	/**
	 * Add product variation data to the Product Block editor REST response.
	 *
	 * @param WP_REST_Response     $response  The Product Block editor REST response.
	 * @param WC_Product_Variation $variation The edited product variation.
	 * @param WP_REST_Request      $request   The request to retrieve the product object.
	 * @return WP_REST_Response
	 */
	public static function add_product_variation_data_to_rest_response( WP_REST_Response $response, WC_Product_Variation $variation, WP_REST_Request $request ) {
		if ( ! FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			return $response;
		}

		if ( 'edit' !== $request->get_param( 'context' ) ) {
			return $response;
		}

		$visibility = (array) get_post_meta( $variation->get_id(), '_visibility', true );

		$manage_categories = Iconic_WSSV_Product_Variation::get_manage_taxonomy( $variation->get_id(), 'product_cat' );
		$categories        = self::get_product_variation_terms( $variation->get_id(), 'product_cat' );

		$manage_tags = Iconic_WSSV_Product_Variation::get_manage_taxonomy( $variation->get_id(), 'product_tag' );
		$tags        = self::get_product_variation_terms( $variation->get_id(), 'product_tag' );

		$response->data['jck_wssv_variable_show_search']         = in_array( 'search', $visibility, true );
		$response->data['jck_wssv_variable_show_filtered']       = in_array( 'filtered', $visibility, true );
		$response->data['jck_wssv_variable_show_catalog']        = in_array( 'catalog', $visibility, true );
		$response->data['jck_wssv_variable_featured']            = Iconic_WSSV_Product_Variation::get_featured_visibility( $variation->get_id() );
		$response->data['jck_wssv_variable_disable_add_to_cart'] = Iconic_WSSV_Product_Variation::get_add_to_cart( $variation->get_id() );
		$response->data['jck_wssv_variable_listings_only']       = Iconic_WSSV_Product_Variation::get_listings_only( $variation->get_id() );
		$response->data['jck_wssv_variable_manage_product_cat']  = $manage_categories;
		$response->data['jck_wssv_variation_product_cat']        = $categories;
		$response->data['jck_wssv_variable_manage_product_tag']  = $manage_tags;
		$response->data['jck_wssv_variation_product_tag']        = $tags;

		return $response;
	}

	/**
	 * Get the product variation terms.
	 *
	 * @param int    $variation_id The variation ID.
	 * @param string $taxonomy     The taxonomy slug e.g. `product_cat`.
	 * @return array
	 */
	protected static function get_product_variation_terms( int $variation_id, string $taxonomy ) {
		$terms = [];

		foreach ( Iconic_WSSV_Product_Variation::get_variation_terms_id( $variation_id, $taxonomy ) as $term_id ) {
			$term = get_term_by( 'term_id', $term_id, $taxonomy );

			if ( empty( $term ) ) {
				continue;
			}

			$terms[] = [
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			];
		}

		return $terms;
	}

	/**
	 * Save product variation fields.
	 *
	 * @param WC_Product_Variation $variation The product variation object saved.
	 * @param WP_REST_Request      $request The HTTP request.
	 * @return void
	 */
	public static function save_product_variation_fields( WC_Product_Variation $variation, WP_REST_Request $request ) {
		if ( ! FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			return;
		}

		$featured_param            = $request->get_param( 'jck_wssv_variable_featured' );
		$disable_add_to_cart_param = $request->get_param( 'jck_wssv_variable_disable_add_to_cart' );
		$listings_only_param       = $request->get_param( 'jck_wssv_variable_listings_only' );
		$manage_categories_param   = $request->get_param( 'jck_wssv_variable_manage_product_cat' );
		$categories_param          = $request->get_param( 'jck_wssv_variation_product_cat' );
		$manage_tags_param         = $request->get_param( 'jck_wssv_variable_manage_product_tag' );
		$tags_param                = $request->get_param( 'jck_wssv_variation_product_tag' );
		$visibility_params         = self::get_visibility_params( $variation->get_id(), $request );

		self::set_product_variation_field_value( 'set_featured_visibility', $variation->get_id(), $featured_param );
		self::set_product_variation_field_value( 'set_add_to_cart', $variation->get_id(), $disable_add_to_cart_param );
		self::set_product_variation_field_value( 'set_listings_only', $variation->get_id(), $listings_only_param );
		self::set_product_variation_field_value( 'set_manage_categories', $variation->get_id(), $manage_categories_param );
		self::set_product_variation_field_value( 'set_variation_categories', $variation->get_id(), $categories_param );
		self::set_product_variation_field_value( 'set_manage_tags', $variation->get_id(), $manage_tags_param );
		self::set_product_variation_field_value( 'set_variation_tags', $variation->get_id(), $tags_param );
		self::set_product_variation_field_value( 'set_visibility', $variation->get_id(), $visibility_params );

		Iconic_WSSV_Product_Variation::set_taxonomies( $variation->get_id() );
	}

	/**
	 * Set the product variation field value.
	 *
	 * @param string $method_name  The method name from `Iconic_WSSV_Product_Variation` class.
	 * @param int    $variation_id The variation ID.
	 * @param mixed  $value        The value to be saved.
	 * @return void
	 */
	protected static function set_product_variation_field_value( $method_name, $variation_id, $value ) {
		if ( is_null( $value ) ) {
			return;
		}

		$method = [ 'Iconic_WSSV_Product_Variation', $method_name ];

		if ( ! is_callable( $method ) ) {
			return;
		}

		switch ( $method_name ) {
			case 'set_variation_categories':
			case 'set_variation_tags':
				$value = wp_list_pluck( $value, 'id' );
				break;

			default:
				break;
		}

		$method( $variation_id, $value );
	}

	/**
	 * Get visibility params for product variation.
	 *
	 * @param int             $variation_id The product variation ID.
	 * @param WP_REST_Request $request      The HTTP request.
	 * @return array
	 */
	protected static function get_visibility_params( int $variation_id, WP_REST_Request $request ) {
		$visibility = (array) get_post_meta( $variation_id, '_visibility', true );

		$visibility_params = array_filter(
			[
				'search'   => $request->get_param( 'jck_wssv_variable_show_search' ),
				'filtered' => $request->get_param( 'jck_wssv_variable_show_filtered' ),
				'catalog'  => $request->get_param( 'jck_wssv_variable_show_catalog' ),
			],
			function( $value ) {
				return ! is_null( $value );
			}
		);

		foreach ( $visibility_params as $key => $value ) {
			if ( $value ) {
				$visibility[] = $key;

				continue;
			}

			$visibility = Iconic_WSSV::unset_item_by_value( $visibility, $key );
		}

		$visibility = array_values( $visibility );

		sort( $visibility );

		return $visibility;
	}
}
