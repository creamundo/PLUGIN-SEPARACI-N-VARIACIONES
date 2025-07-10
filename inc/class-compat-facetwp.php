<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * FacetWP compatibility Class
 *
 * @since 1.1.10
 */
class Iconic_WSSV_Compat_FacetWP {
	/**
	 * Init early.
	 */
	public static function init_early() {
		if ( ! function_exists( 'FWP' ) ) {
			return;
		}

		add_filter( 'facetwp_settings_admin', array( __CLASS__, 'remove_enable_variations_setting' ), 10, 2 );
		add_filter( 'facetwp_enable_product_variations', '__return_true' );
	}

	/**
	 * Init.
	 */
	public static function init() {
		if ( ! function_exists( 'FWP' ) ) {
			return;
		}

		add_filter( 'woocommerce_is_filtered', array( __CLASS__, 'is_filtered' ) );
		add_filter( 'facetwp_indexer_post_facet', array( __CLASS__, 'index_woo_values' ), 10, 2 );
		add_filter( 'facetwp_indexer_post_facet', array( __CLASS__, 'skip_hidden_parents' ), 1, 2 );
		add_filter( 'facetwp_facet_where', array( __CLASS__, 'update_facet_where' ), 10 );
		add_filter( 'facetwp_indexer_query_args', array( __CLASS__, 'indexer_query_args' ), 10 );
		add_filter( 'facetwp_search_query_args', array( __CLASS__, 'search_query_args' ), 10 );
		add_filter( 'facetwp_builder_query_data', array( __CLASS__, 'add_product_variation_post_type_to_query_data' ) );

		self::remove_anonymous_filters();

		add_filter( 'facetwp_index_row', [ __CLASS__, 'check_parent_product_visibilty_terms' ], 30 );
	}

	/**
	 * Remove enable variations settings as it's always true
	 * with SSV. See facetwp_enable_product_variations filter.
	 *
	 * @param $settings
	 * @param $fwp_settings_admin
	 *
	 * @return mixed
	 */
	public static function remove_enable_variations_setting( $settings, $fwp_settings_admin ) {
		unset( $settings['woocommerce']['fields']['wc_enable_variations'] );

		return $settings;
	}

	/**
	 * Remove anonymous methods.
	 */
	public static function remove_anonymous_filters() {
		global $wp_filter;

		$remove = array(
			'facetwp_index_row'                   => array(
				'FacetWP_Integration_WooCommerce' => array(
					'attribute_variations',
				),
			),
			'facetwp_filtered_post_ids'           => array(
				'FacetWP_Integration_WooCommerce' => array(
					'process_variations',
				),
			),
			'facetwp_indexer_post_facet_defaults' => array(
				'FacetWP_Integration_WooCommerce' => array(
					'force_taxonomy',
				),
			),
		);

		foreach ( $remove as $filter_name => $removable ) {
			if ( empty( $wp_filter[ $filter_name ] ) ) {
				continue;
			}

			foreach ( $wp_filter[ $filter_name ]->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $hook_key => $hook_data ) {
					if ( ! is_array( $hook_data['function'] ) ) {
						continue;
					}

					foreach ( $removable as $class => $methods ) {
						foreach ( $methods as $method ) {
							if ( get_class( $hook_data['function'][0] ) === $class && $method === $hook_data['function'][1] ) {
								unset( $wp_filter[ $filter_name ]->callbacks[ $priority ][ $hook_key ] );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Modify is filtered.
	 *
	 * @param bool $bool
	 *
	 * @return bool
	 */
	public static function is_filtered( $bool ) {
		if ( empty( $_GET ) ) {
			return $bool;
		}

		foreach ( $_GET as $key => $value ) {
			if ( strpos( $key, 'fwp_' ) === 0 || strpos( $key, '_' ) === 0 ) {
				$bool = true;
				break;
			}
		}

		return $bool;
	}

	/**
	 * Index Woo values for variations.
	 *
	 * This is essentially a copy of index_woo_values() in facetwp/includes/integrations/woocommerce/woocommerce.php
	 *
	 * @param $return
	 * @param $params
	 *
	 * @return bool
	 */
	public static function index_woo_values( $return, $params ) {
		$facet     = $params['facet'];
		$defaults  = $params['defaults'];
		$post_id   = (int) $defaults['post_id'];
		$post_type = get_post_type( $post_id );

		if ( 'product_variation' !== $post_type || empty( $facet['source'] ) ) {
			return $return;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $return;
		}

		// Index out of stock products?
		$index_all = ( 'yes' === FWP()->helper->get_setting( 'wc_index_all', 'no' ) );
		$index_all = apply_filters( 'facetwp_index_all_products', $index_all );

		if ( ! $index_all && 'product_variation' === $post_type ) {
			if ( ! $product || ! $product->is_in_stock() ) {
				return true; // skip
			}
		}

		// Custom woo fields.
		if ( 0 === strpos( $facet['source'], 'woo/' ) ) {
			$source = substr( $facet['source'], 4 );

			if ( 'price' === $source || 'sale_price' === $source || 'regular_price' === $source ) {
				$method_name                     = "get_$source";
				$price                           = $product->$method_name();
				$defaults['facet_value']         = $price;
				$defaults['facet_display_value'] = $price;

				FWP()->indexer->index_row( $defaults );
			} elseif ( 'average_rating' === $source ) {
				$parent_product                  = wc_get_product( $product->get_parent_id() );
				$rating                          = $parent_product->get_average_rating();
				$defaults['facet_value']         = $rating;
				$defaults['facet_display_value'] = $rating;

				FWP()->indexer->index_row( $defaults );
			} elseif ( 'stock_status' === $source ) {
				$in_stock                        = $product->is_in_stock();
				$defaults['facet_value']         = (int) $in_stock;
				$defaults['facet_display_value'] = $in_stock ? __( 'In Stock', 'fwp-front' ) : __( 'Out of Stock', 'fwp-front' );

				FWP()->indexer->index_row( $defaults );
			} elseif ( 'on_sale' === $source ) {
				if ( $product->is_on_sale() ) {
					$defaults['facet_value']         = 1;
					$defaults['facet_display_value'] = __( 'On Sale', 'fwp-front' );

					FWP()->indexer->index_row( $defaults );
				}
			} elseif ( 'featured' === $source ) {
				if ( $product->is_featured() ) {
					$defaults['facet_value']         = 1;
					$defaults['facet_display_value'] = __( 'Featured', 'fwp-front' );

					FWP()->indexer->index_row( $defaults );
				}
			} elseif ( 'product_type' === $source ) {
				$type                            = $product->get_type();
				$defaults['facet_value']         = $type;
				$defaults['facet_display_value'] = $type;

				FWP()->indexer->index_row( $defaults );
			}

			return true; // skip.
		}

		return $return;
	}

	/**
	 * Skip parents hidden from filters.
	 *
	 * @param bool  $return
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function skip_hidden_parents( $return, $params ) {
		$facet     = $params['facet'];
		$defaults  = $params['defaults'];
		$post_id   = (int) $defaults['post_id'];
		$post_type = get_post_type( $post_id );

		if ( 'product' !== $post_type || empty( $facet['source'] ) ) {
			return $return;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product->is_type( 'variable' ) ) {
			return $return;
		}

		$exclude_from_filtered_term = get_term_by( 'slug', 'exclude-from-filtered', 'product_visibility' );
		$excluded                   = has_term( $exclude_from_filtered_term->term_id, 'product_visibility', $post_id );

		if ( $excluded ) {
			return true; // Skip.
		}

		return $return;
	}

	/**
	 * Add "filter only" variations to pre-filtered IDs. otherwise
	 * it only counts catalog visible product variations.
	 *
	 * @param array $post_ids The post IDs.
	 *
	 * @return array
	 */
	public static function pre_filtered_post_ids( $post_ids ) {
		if ( is_filtered() ) {
			return $post_ids;
		}

		$variation_ids    = self::get_filter_visible_variation_ids();
		$product_ids_diff = array_diff( $post_ids, $variation_ids );

		/**
		 * Remove variations that are not visible in the filtered results.
		 *
		 * The $posts_id can hold variations that have only the option
		 * `Show in Catalog?` checked.
		 */
		foreach ( $product_ids_diff as $key => $product_id ) {
			$is_product_variation = 'product_variation' === get_post_type( $product_id );

			if ( ! $is_product_variation ) {
				continue;
			}

			unset( $post_ids[ $key ] );
		}

		add_filter( 'facetwp_preload_force_query', '__return_true' );

		$post_ids = array_unique( array_merge( $post_ids, $variation_ids ) );

		return $post_ids;
	}

	/**
	 * Get IDs of variations visible when filtering.
	 *
	 * @return array
	 */
	public static function get_filter_visible_variation_ids() {
		$variation_ids              = array();
		$exclude_from_filtered_term = get_term_by( 'slug', 'exclude-from-filtered', 'product_visibility' );

		$args = array(
			'post_type'      => 'product_variation',
			'posts_per_page' => - 1,
		);

		if ( $exclude_from_filtered_term ) {
			$args['tax_query'][] = array(
				'relation' => 'AND',
				array(
					'taxonomy'         => 'product_visibility',
					'field'            => 'term_taxonomy_id',
					'terms'            => array( $exclude_from_filtered_term->term_taxonomy_id ),
					'operator'         => 'NOT IN',
					'include_children' => 1,
				),
			);
		}

		// If the current page is a product category archive then we want to fetch products from current category only.
		if ( is_product_category() ) {
			$args['tax_query'][] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_taxonomy_id',
				'terms'            => get_queried_object_id(),
				'operator'         => 'IN',
				'include_children' => 1,
			);
		}

		// If the current page is a product tag archive then we want to fetch products from current tag only.
		if ( is_product_tag() ) {
			$args['tax_query'][] = array(
				'taxonomy'         => 'product_tag',
				'field'            => 'term_taxonomy_id',
				'terms'            => get_queried_object_id(),
				'operator'         => 'IN',
				'include_children' => 1,
			);
		}

		$query = new WP_Query( $args );

		if ( empty( $query->posts ) ) {
			return $variation_ids;
		}

		$variation_ids = wp_list_pluck( $query->posts, 'ID' );

		return $variation_ids;
	}

	/**
	 * Explicitly add "product_variation" to query as it isn't
	 * included in "any" because it's private.
	 *
	 * @param array $args The query args.
	 *
	 * @return array
	 */
	public static function indexer_query_args( $args ) {
		if ( empty( $args['post_type'] ) ) {
			return $args;
		}

		if ( is_string( $args['post_type'] ) && 'any' === $args['post_type'] ) {
			/**
			 * When post_type is `any`, WP_Query retrieves the post types
			 * calling get_post_types. We're doing the same plus adding
			 * product_variation.
			 *
			 * Reference: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-query.php#L2444
			 */
			$post_types   = array_values( get_post_types( array( 'exclude_from_search' => false ) ) );
			$post_types[] = 'product_variation';

			$args['post_type'] = $post_types;

			return $args;
		}

		if (
			( is_string( $args['post_type'] ) && 'product' === $args['post_type'] ) ||
			( is_array( $args['post_type'] ) && in_array( 'product', $args['post_type'], true ) )
		) {
			$args['post_type'] = array( 'product', 'product_variation' );

			return $args;
		}

		return $args;
	}

	/**
	 * Add "product_variation" post type to search query
	 *
	 * The Search facet type uses a default WP_Query to filter
	 * the posts (FacetWP_Facet_Search::filter_posts). Since the
	 * post_type parameter is not set, it falls back to the
	 * default behavior of WP_Query i.e. all public post types.
	 *
	 * We do the same behavior in this function and add the
	 * product_variation post type if there is the product
	 * post type.
	 *
	 * @param array $search_args Array of WP_Query parameters.
	 * @return array
	 */
	public static function search_query_args( $search_args ) {
		$post_types = get_post_types( array( 'exclude_from_search' => false ) );

		if ( empty( $post_types['product'] ) ) {
			return $search_args;
		}

		$post_types['product_variation'] = 'product_variation';

		$search_args['post_type'] = $post_types;

		$exclude_from_search_term = get_term_by( 'slug', 'exclude-from-search', 'product_visibility' );

		$search_args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => array( $exclude_from_search_term->term_taxonomy_id ),
			'operator' => 'NOT IN',
		);

		return $search_args;
	}

	/**
	 * Add product_variation post type when the page is using a FacetWP
	 * template to show products and the option "Add Variations To All
	 * Product Queries" is enabled.
	 *
	 * @param array $templates The templates settings.
	 * @return array
	 */
	public static function add_product_variation_post_type_in_facetwp_templates( $templates ) {
		global $jck_wssv;

		/**
		 * Since a query executed by the FacetWP template is not
		 * the main query, we only add product_variation post
		 * type if the option "Add Variations To All Product Queries"
		 * is enabled.
		 */
		if ( is_admin() || ! $jck_wssv->settings['general_advanced_add_to_all_queries'] || ! is_array( $templates ) ) {
			return $templates;
		}

		/**
		 * Check if the template is using the product post type.
		 */
		$has_product_post_type = false;
		foreach ( $templates as $template_key => $template ) {
			if ( empty( $template['query_obj']['post_type'] ) || ! is_array( $template['query_obj']['post_type'] ) ) {
				continue;
			}

			foreach ( $template['query_obj']['post_type'] as $post_type ) {
				if ( empty( $post_type['value'] ) ) {
					continue;
				}

				if ( 'product' === $post_type['value'] ) {
					$has_product_post_type = true;
					break;
				}
			}

			if ( $has_product_post_type ) {
				$templates[ $template_key ]['query_obj']['post_type'][] = array(
					'label' => 'Product variations',
					'value' => 'product_variation',
				);

				$has_product_post_type = false;
			}
		}

		return $templates;
	}

	/**
	 * Add product variation post type as an option in the Fetch field
	 *
	 * @param array $query_data The data for the query builder.
	 * @return array
	 */
	public static function add_product_variation_post_type_to_query_data( $query_data ) {

		if ( empty( $query_data['post_types'] ) ) {
			return $query_data;
		}

		$has_product_post_type = ! empty( wp_list_filter( $query_data['post_types'], array( 'value' => 'product' ) ) );

		if ( ! $has_product_post_type ) {
			return $query_data;
		}

		$query_data['post_types'][] = array(
			'label' => __( 'Product variations', 'fwp-front' ),
			'value' => 'product_variation',
		);

		return $query_data;
	}

	/**
	 * Update the facet where clause.
	 *
	 * @param string $where_clause The where clause used to load the available choices.
	 * @return string
	 */
	public static function update_facet_where( $where_clause ) {
		if ( is_filtered() ) {
			return $where_clause;
		}

		$post_ids = FWP()->unfiltered_post_ids;

		$variation_ids    = self::get_filter_visible_variation_ids();
		$product_ids_diff = array_diff( $post_ids, $variation_ids );

		/**
		 * Remove variations that are not visible in the filtered results.
		 *
		 * The $posts_id can hold variations that have only the option
		 * `Show in Catalog?` checked.
		 */
		foreach ( $product_ids_diff as $key => $product_id ) {
			$is_product_variation = 'product_variation' === get_post_type( $product_id );

			if ( ! $is_product_variation ) {
				continue;
			}

			unset( $post_ids[ $key ] );
		}

		$post_ids = array_unique( array_merge( $post_ids, $variation_ids ) );

		if ( empty( $post_ids ) ) {
			return $where_clause;
		}

		$where_clause = ' AND post_id IN (' . implode( ',', $post_ids ) . ')';

		return $where_clause;
	}

	/**
	 * Check the parent product visibility terms.
	 *
	 * If there is a filter by attributes and the variations have
	 * the option `Show in Filtered Results?` unchecked, it's
	 * necessary to check the variable (parent) product visibility
	 * terms.
	 *
	 * Otherwise, the product terms will not be added to the
	 * filter by attributes.
	 *
	 * Scenario handled:
	 * - Variable product (visible)
	 * -- Variation A (excluded from filtered results)
	 * -- Variation B (excluded from filtered results)
	 *
	 * @param array $params The FacetWP params.
	 * @return array
	 */
	public static function check_parent_product_visibilty_terms( $params ) {
		if ( empty( $params['facet_source'] ) ) {
			return $params;
		}

		$post_id = (int) $params['post_id'];

		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $params;
		}

		if ( ! $product->is_type( 'variation' ) ) {
			return $params;
		}

		if ( ! str_contains( $params['facet_source'], 'tax/pa_' ) ) {
			return $params;
		}

		$variation_visibility = Iconic_WSSV_Product_Variation::get_visibility( $product->get_id() );

		if ( in_array( 'filtered', $variation_visibility, true ) ) {
			return $params;
		}

		$parent_id = $product->get_parent_id();

		if ( empty( $parent_id ) ) {
			return $params;
		}

		$visibility_terms = Iconic_WSSV_Product::get_visibility_term_slugs( $parent_id );

		if ( in_array( 'exclude-from-filtered', $visibility_terms, true ) ) {
			return $params;
		}

		$parent_product = wc_get_product( $parent_id );

		if ( ! $parent_product ) {
			return $params;
		}

		$params['variation_id'] = $product->get_id();
		$params['post_id']      = $parent_product->get_id();

		return $params;
	}
}
