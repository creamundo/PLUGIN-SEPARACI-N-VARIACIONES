<?php

/**
 * Iconic_WSSV_Query Class.
 *
 * All methods for modify the WooCommerce Query.
 *
 * @since 1.1.5
 */
class Iconic_WSSV_Query {
	/**
	 * Variation IDs with missing parent
	 *
	 * @access protected
	 * @var array $variation_ids_with_missing_parent
	 */
	protected $variation_ids_with_missing_parent = null;

	/**
	 * Init.
	 */
	public static function init() {
		if ( is_admin() ) {
			return;
		}

		add_action( 'woocommerce_product_query', array( __CLASS__, 'add_variations_to_query' ), 50, 1 );
		add_filter( 'woocommerce_shortcode_products_query', array( __CLASS__, 'add_variations_to_shortcode_query' ), 10, 2 );
		add_filter( 'woocommerce_product_related_posts_query', array( __CLASS__, 'add_variations_to_related_products' ), 10, 2 );
		add_filter( 'posts_where', array( __CLASS__, 'posts_where' ), 10, 2 );
		add_action( 'wp', array( __CLASS__, 'maybe_add_variations_to_all_product_queries' ) );
		add_filter( 'query_loop_block_query_vars', array( __CLASS__, 'maybe_add_variations_to_product_queries_blocks' ), 15, 2 );
	}

	/**
	 * Maybe add variations to all product queries.
	 *
	 * @return void
	 */
	public static function maybe_add_variations_to_all_product_queries() {
		global $jck_wssv;

		if ( ! is_admin() && $jck_wssv->settings['general_advanced_add_to_all_queries'] ) {
			// add_variations_to_product_query would check if the the current WP Query has 'product'
			// as post type, if yes, then it would add the variations as well.
			add_action( 'pre_get_posts', array( __CLASS__, 'add_variations_to_product_query' ), 20 );
		}
	}

	/**
	 * Add Variations to all Product queries. If any query has
	 * 'product' as post_type, add 'product_variation' to it.
	 *
	 * @param WP_Query $q The query to be modified.
	 *
	 * @return void
	 */
	public static function add_variations_to_product_query( $q ) {
		$post_type = array_filter( (array) $q->get( 'post_type' ) );
		$post_name = $q->get( 'name' );
		$exclude   = $q->get( 'iconic_ssv_exclude_variations' );

		if ( $exclude ) {
			return;
		}

		// check $post_name to prevent adding variations to the single product page.
		if ( in_array( 'product', $post_type, true ) && empty( $post_name ) ) {
			self::add_variations_to_query( $q );
		}
	}

	/**
	 * Adds Variations to the given WP_Query object.
	 *
	 * @param WP_Query $q The query to be modified.
	 *
	 * @return void
	 */
	public static function add_variations_to_query( $q ) {
		if ( $q->get( 'iconic_ssv_query' ) ) {
			return;
		}

		$conditions = self::get_query_conditions( $q->query_vars );

		foreach ( $conditions as $query_key => $query_value ) {
			$q->set( $query_key, $query_value );
		}
	}

	/**
	 * Returns the WP_Query arguments to add variations to the given query.
	 *
	 * @param array $query_args The query.
	 *
	 * @return array $conditions The arguments to be merged to the WP Query.
	 */
	public static function get_query_conditions( $query_args ) {
		$conditions  = array();
		$post_type   = isset( $query_args['post_type'] ) ? (array) $query_args['post_type'] : array( 'product' );
		$post_type[] = 'product_variation';

		$conditions['post_type'] = array_filter( $post_type );

		// Don't get variations with unpublished parents.
		$unpublished_variable_product_ids = self::get_unpublished_variable_product_ids();
		if ( ! empty( $unpublished_variable_product_ids ) ) {
			$post_parent__not_in               = isset( $query_args['post_parent__not_in'] ) ? (array) $query_args['post_parent__not_in'] : array();
			$conditions['post_parent__not_in'] = array_merge( $post_parent__not_in, $unpublished_variable_product_ids );
		}

		// Don't get variations with missing parents.
		$variation_ids_with_missing_parent = self::get_variation_ids_with_missing_parent();
		if ( ! empty( $variation_ids_with_missing_parent ) ) {
			$post__not_in               = isset( $query_args['post__not_in'] ) ? (array) $query_args['post__not_in'] : array();
			$conditions['post__not_in'] = array_merge( $post__not_in, $variation_ids_with_missing_parent );
		}

		if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) {
			// Update the meta query to include our variations.
			$meta_query               = isset( $query_args['meta_query'] ) ? (array) $query_args['meta_query'] : array();
			$meta_query               = self::update_meta_query( $meta_query );
			$conditions['meta_query'] = $meta_query;
		} else {
			// Update the tax query to include our variations.
			$tax_query               = isset( $query_args['tax_query'] ) ? (array) $query_args['tax_query'] : array();
			$tax_query               = self::update_tax_query( $tax_query );
			$conditions['tax_query'] = $tax_query;
		}

		// Add idenfier to the query.
		$conditions['iconic_ssv_query'] = 1;

		return $conditions;
	}

	/**
	 * Add variaitons to shortcode queries.
	 *
	 * @param WP_Query $query_args     WP_Query argument.
	 * @param array    $shortcode_args Shortcode arguments.
	 *
	 * @return array $query_args
	 */
	public static function add_variations_to_shortcode_query( $query_args, $shortcode_args ) {
		// Add product variations to the query.

		if ( isset( $query_args['iconic_ssv_query'] ) ) {
			return $query_args;
		}

		$conditions = self::get_query_conditions( $query_args );

		foreach ( $conditions as $query_key => $query_value ) {
			$query_args[ $query_key ] = $query_value;
		}

		return $query_args;
	}

	/**
	 * Get unpublished variable product IDs
	 *
	 * Get's an array of product IDs where the product
	 * is variable and has not been published (i.e. is in the bin)
	 *
	 * @since 1.1.0
	 * @return mixed array
	 */
	public static function get_unpublished_variable_product_ids() {
		static $unpublished_variable_product_ids = null;

		if ( ! is_null( $unpublished_variable_product_ids ) ) {
			return $unpublished_variable_product_ids;
		}

		$unpublished_variable_product_ids = array();

		global $wpdb;

		$product_type = get_term_by( 'slug', 'variable', 'product_type' );

		if ( ! $product_type ) {
			return $unpublished_variable_product_ids;
		}

		$where = "
			WHERE 1=1
			AND ( $wpdb->term_relationships.term_taxonomy_id IN (%d) )
			AND $wpdb->posts.post_type = 'product'
		";

		$prepare_values[] = $product_type->term_taxonomy_id;

		$default_post_statuses = [
			'future',
			'draft',
			'pending',
			'trash',
			'auto-draft',
		];

		$user = wp_get_current_user();

		/**
		 * Filter whether the private status should be added to the
		 * unpublished post statuses.
		 *
		 * @since 1.21.0
		 * @hook iconic_wssv_should_add_private_to_unpublished_variable_post_statuses
		 * @param  bool     $should_add_private_status True is the user is a admin.
		 * @param  string[] $user_roles                The roles the user is part of.
		 * @return bool New value
		 */
		$should_add_private_status = apply_filters(
			'iconic_wssv_should_add_private_to_unpublished_variable_post_statuses',
			! in_array( 'administrator', $user->roles, true ),
			$user->roles
		);

		if ( $should_add_private_status ) {
			$default_post_statuses[] = 'private';
		}

		$post_statuses = $default_post_statuses;

		/**
		 * Filter the unpublished variable product post statuses
		 * used to retrieve the unpublished products.
		 *
		 * @since 1.21.0
		 * @hook iconic_wssv_post_statuses_unplubished_variable_product
		 * @param  array $post_statuses The post statuses.
		 * @return array New value
		 */
		$post_statuses = apply_filters( 'iconic_wssv_post_statuses_unplubished_variable_product', $post_statuses );

		if ( ! is_array( $post_statuses ) ) {
			$post_statuses = $default_post_statuses;
		}

		foreach ( $post_statuses as $post_status ) {
			$post_statuses_placeholders[] = '%s';
			$prepare_values[]             = $post_status;
		}

		$post_statuses_placeholders = join( ', ', $post_statuses_placeholders ?? [] );

		if ( ! empty( $post_statuses_placeholders ) ) {
			$where .= " AND ( $wpdb->posts.post_status IN ( $post_statuses_placeholders ) )";
		}

		$products = $wpdb->get_results(
			// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders
			$wpdb->prepare(
				"
			SELECT *
			FROM $wpdb->posts
			LEFT JOIN $wpdb->term_relationships
			ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)
			$where
			GROUP BY $wpdb->posts.ID
			ORDER BY $wpdb->posts.post_date DESC
			",
				$prepare_values
			),
			// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders
			ARRAY_A
		);

		$unpublished_variable_product_ids = wp_list_pluck( $products, 'ID' );

		return array_map( 'intval', $unpublished_variable_product_ids );
	}

	/**
	 * Get variation IDs with missing parents
	 *
	 * @since 1.1.2
	 * @return mixed bool|array
	 */
	public static function get_variation_ids_with_missing_parent() {
		static $variation_ids_with_missing_parent = null;

		if ( ! is_null( $variation_ids_with_missing_parent ) ) {
			return $variation_ids_with_missing_parent;
		}

		global $wpdb;

		$variation_ids = $wpdb->get_results(
			"
			SELECT  p1.ID
			FROM $wpdb->posts p1
			WHERE p1.post_type = 'product_variation'
			AND p1.post_parent NOT IN (
				SELECT DISTINCT p2.ID
				FROM $wpdb->posts p2
				WHERE p2.post_type = 'product'
			)
			",
			ARRAY_A
		);

		$variation_ids_with_missing_parent = wp_list_pluck( $variation_ids, 'ID' );

		return $variation_ids_with_missing_parent;
	}

	/*
	 * Update meta query
	 *
	 * Add OR parameters to also search for variations with specific visibility
	 *
	 * @param array $meta_query]
	 * @return array
	 */
	public static function update_meta_query( $meta_query ) {
		$index = 0;

		if ( ! empty( $meta_query ) ) {
			foreach ( $meta_query as $index => $meta_query_item ) {
				if ( isset( $meta_query_item['key'] ) && $meta_query_item['key'] == '_visibility' ) {
					$meta_query[ $index ]             = array();
					$meta_query[ $index ]['relation'] = 'OR';

					$meta_query[ $index ]['visibility_visible'] = array(
						'key'     => '_visibility',
						'value'   => 'visible',
						'compare' => 'LIKE',
					);

					if ( is_search() ) {
						$meta_query[ $index ]['visibility_search'] = array(
							'key'     => '_visibility',
							'value'   => 'search',
							'compare' => 'LIKE',
						);
					} else {
						$meta_query[ $index ]['visibility_catalog'] = array(
							'key'     => '_visibility',
							'value'   => 'catalog',
							'compare' => 'LIKE',
						);
					}

					if ( is_filtered() ) {
						$meta_query[ $index ]['visibility_filtered'] = array(
							'key'     => '_visibility',
							'value'   => 'filtered',
							'compare' => 'LIKE',
						);
					}
				}
			}
		}

		return $meta_query;
	}

	/**
	 * Update tax query.
	 *
	 * @param array $tax_query
	 * @param bool  $filtered
	 *
	 * @return array
	 */
	public static function update_tax_query( $tax_query, $filtered = false ) {
		$exclude_from_filtered_term = get_term_by( 'slug', 'exclude-from-filtered', 'product_visibility' );

		if ( $exclude_from_filtered_term && ( $filtered || is_filtered() ) ) {
			if ( empty( $tax_query ) ) {
				$tax_query['relation'] = 'AND';
				$tax_query[]           = array(
					'taxonomy'         => 'product_visibility',
					'field'            => 'term_taxonomy_id',
					'terms'            => array( $exclude_from_filtered_term->term_taxonomy_id ),
					'operator'         => 'NOT IN',
					'include_children' => 1,
				);
			} else {
				$exclude_from_catalog_term = get_term_by( 'slug', 'exclude-from-catalog', 'product_visibility' );

				foreach ( $tax_query as $index => $tax_query_item ) {
					if ( ! is_array( $tax_query_item ) ) {
						continue;
					}

					if ( empty( $tax_query_item['taxonomy'] ) ) {
						continue;
					}

					if ( $tax_query_item['taxonomy'] !== 'product_visibility' ) {
						continue;
					}

					if ( $tax_query_item['operator'] !== 'NOT IN' ) {
						continue;
					}

					// Assign the current visibility query.
					$current_visibility_query                     = $tax_query[ $index ];
					$current_visibility_query['include_children'] = 1;
					$current_visibility_query['terms']            = (array) $current_visibility_query['terms'];
					$current_visibility_query['terms'][]          = $exclude_from_filtered_term->term_taxonomy_id;

					// Create another visibility query choice.
					$alt_visibility_query = $current_visibility_query;

					// Replace 'exclude form catalog' with 'exclude form filtered'.
					foreach ( $alt_visibility_query['terms'] as $term_index => $term_id ) {
						if ( $term_id === $exclude_from_catalog_term->term_taxonomy_id ) {
							unset( $alt_visibility_query['terms'][ $term_index ] );
						}
					}

					// Use both queries with an 'OR' choice.
					$tax_query[ $index ]             = array();
					$tax_query[ $index ]['relation'] = 'OR';
					$tax_query[ $index ][]           = $alt_visibility_query;
					$tax_query[ $index ][]           = $current_visibility_query;
				}
			}
		}

		return $tax_query;
	}

	/**
	 * @param array $query
	 * @param int   $product_id
	 *
	 * @return array
	 */
	public static function add_variations_to_related_products( $query, $product_id ) {
		$find    = "AND p.post_type = 'product'";
		$replace = "AND ( p.post_type = 'product' OR p.post_type = 'product_variation' )";

		$query['where'] = str_replace( $find, $replace, $query['where'] );

		return $query;
	}

	/**
	 * Remove private variations from listings.
	 *
	 * @param $where
	 *
	 * @return mixed
	 */
	public static function posts_where( $where, $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return $where;
		}

		global $wpdb;

		$find    = "OR $wpdb->posts.post_author = 1 AND $wpdb->posts.post_status = 'private'";
		$replace = "OR ( $wpdb->posts.post_author = 1 AND $wpdb->posts.post_status = 'private' AND $wpdb->posts.post_type != 'product_variation' )";

		return str_replace( $find, $replace, $where );
	}


	/**
	 * Maybe add variations to product queries blocks.
	 *
	 * @param array    $query Array containing parameters for WP_Query as parsed by the block context.
	 * @param WP_Block $block Block instance.
	 * @return array The modified query.
	 */
	public static function maybe_add_variations_to_product_queries_blocks( $query, $block ) {
		global $jck_wssv;

		/**
		 * Filter whether to add variations to product queries blocks.
		 *
		 * @since 1.17.0
		 * @hook iconic_wssv_should_add_variations_to_product_queries_blocks
		 * @param  bool     $should_add_product_variation_post_type Default is `Add Variations To All Product Queries` option value.
		 * @param  array    $query                                  Array containing parameters for WP_Query as parsed by the block context.
		 * @param  WP_Block $block                                  Block instance.
		 * @return bool New value
		 */
		$should_add_product_variation_post_type = apply_filters( 'iconic_wssv_should_add_variations_to_product_queries_blocks', $jck_wssv->settings['general_advanced_add_to_all_queries'], $query, $block );

		if ( ! $should_add_product_variation_post_type ) {
			return $query;
		}

		$blocks = array(
			'core/post-template',
			'core/query-pagination-numbers',
			'core/query-pagination-next',
			'core/query-no-results',
			'woocommerce/product-template',
			'woocommerce/product-collection-no-results',
		);

		if ( empty( $block->name ) || ! in_array( $block->name, $blocks, true ) ) {
			return $query;
		}

		if ( empty( $query['post_type'] ) ) {
			return $query;
		}

		if ( ! is_string( $query['post_type'] ) && ! is_array( $query['post_type'] ) ) {
			return $query;
		}

		if ( is_string( $query['post_type'] ) && 'product' !== $query['post_type'] ) {
			return $query;
		}

		if ( is_array( $query['post_type'] ) && ! in_array( 'product', $query['post_type'], true ) ) {
			return $query;
		}

		$tax_query = empty( $query['tax_query'] ) || ! is_array( $query['tax_query'] ) ? array() : $query['tax_query'];

		$product_visibility_terms = wc_get_product_visibility_term_ids();
		$tax_query[]              = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => $product_visibility_terms['exclude-from-catalog'],
			'operator' => 'NOT IN',
		);

		$query['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		$query['post_type'] = array( 'product', 'product_variation' );
		$query['orderby']   = 'menu_order post_title';
		$query['order']     = 'ASC';

		return $query;
	}
}
