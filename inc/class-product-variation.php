<?php

use Automattic\WooCommerce\Utilities\NumberUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Iconic_WSSV_Product_Variation.
 *
 * @class    Iconic_WSSV_Product_Variation
 * @version  1.0.0
 * @author   Iconic
 */
class Iconic_WSSV_Product_Variation {
	/**
	 * Run.
	 */
	public static function init() {
		add_filter( 'woocommerce_product_variation_get_average_rating', array( __CLASS__, 'get_average_rating' ), 10, 2 );
		add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'is_visible' ), 10, 2 );
		add_filter( 'woocommerce_product_title', array( __CLASS__, 'variation_title' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_title', array( __CLASS__, 'variation_title' ), 10, 4 );
		add_filter( 'woocommerce_cart_item_permalink', array( __CLASS__, 'cart_item_permalink' ), 10, 3 );
		add_filter( 'woocommerce_variable_children_args', array( __CLASS__, 'variable_children_args' ), 10, 3 );
		add_filter( 'query', array( __CLASS__, 'remove_listing_only_variations_from_query' ), 10 );
		add_action( 'comment_post', array( __CLASS__, 'assign_ratings_to_child_products' ), 11 );
		add_action( 'woocommerce_product_duplicate_before_save', array( __CLASS__, 'duplicate_variations_visiblity' ), 10, 2 );
		add_action( 'woocommerce_product_duplicate_before_save', [ __CLASS__, 'duplicate_taxonomies_added_at_variation_level' ], 15, 2 );
		add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'mark_listing_only_products_as_non_purchasable' ), 10, 2 );
		add_filter( 'post_thumbnail_id', array( __CLASS__, 'maybe_get_parent_post_thumbnail_id' ), 20, 2 );
		add_filter( 'iconic_wssv_l10n_admin_script_data', array( __CLASS__, 'localize_select_woo_options' ) );
	}

	/**
	 * Filter is_visible.
	 *
	 * @param bool $visible
	 * @param int  $product_id
	 *
	 * @return bool
	 */
	public static function is_visible( $visible, $product_id ) {
		if ( get_post_type( $product_id ) !== 'product_variation' ) {
			return $visible;
		}

		$action  = filter_input( INPUT_POST, 'action' );
		$wc_ajax = filter_input( INPUT_GET, 'wc-ajax' );

		if ( $action === 'woocommerce_get_refreshed_fragments' || $wc_ajax === 'get_refreshed_fragments' ) {
			// If variation is in cart, always return true.
			return true;
		}

		$visibility = self::get_visibility( $product_id );

		if ( in_array( 'hidden', $visibility ) ) {
			return false;
		}

		$parent_id     = wp_get_post_parent_id( $product_id );
		$parent_status = get_post_status( $parent_id );

		if ( $parent_status !== 'publish' ) {
			return false;
		}

		return true;
	}

	/**
	 * Filter variation title.
	 *
	 * @param string $title
	 * @param        $product
	 * @param        $title_base
	 * @param        $title_suffix
	 *
	 * @return string
	 */
	public static function variation_title( $title, $product, $title_base = false, $title_suffix = false ) {
		$product_id = $product->get_id();

		if ( ! $product->is_type( 'variation' ) || empty( $product_id ) ) {
			return $title;
		}

		return self::get_title( $product_id );
	}

	/**
	 * Set catalog visibility.
	 *
	 * @param int   $variation_id
	 * @param array $visibility
	 * @param bool  $meta_only
	 *
	 * @return bool
	 */
	public static function set_visibility( $variation_id, $visibility = null, $meta_only = false ) {
		$set_visibility     = true;
		$current_visibility = get_post_meta( $variation_id, '_visibility', true );
		$visibility         = is_null( $visibility ) ? self::get_visibility( $variation_id ) : $visibility;
		$visibility         = array_filter( $visibility );

		sort( $visibility );

		update_post_meta( $variation_id, '_visibility', $visibility, $current_visibility );

		if ( $meta_only ) {
			return $set_visibility;
		}

		if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			$set_visibility = false;
			$variation      = wc_get_product( $variation_id );
			$terms          = array();
			$visibility     = implode( '-', $visibility );

			switch ( $visibility ) {
				case 'catalog-filtered':
					$terms[] = 'exclude-from-search';
					break;
				case 'catalog-search':
					$terms[] = 'exclude-from-filtered';
					break;
				case 'catalog':
					$terms[] = 'exclude-from-search';
					$terms[] = 'exclude-from-filtered';
					break;
				case 'filtered-search':
					$terms[] = 'exclude-from-catalog';
					break;
				case 'search':
					$terms[] = 'exclude-from-catalog';
					$terms[] = 'exclude-from-filtered';
					break;
				case 'filtered':
					$terms[] = 'exclude-from-catalog';
					$terms[] = 'exclude-from-search';
					break;
				case 'hidden':
					$terms[] = 'exclude-from-catalog';
					$terms[] = 'exclude-from-search';
					$terms[] = 'exclude-from-filtered';
					break;
			}

			$rated_term = self::get_product_visibility_rated_term( $variation );

			if ( $rated_term ) {
				$terms[] = $rated_term;
			}

			if ( $variation ) {
				$stock_status = $variation->get_stock_status();
				if ( $stock_status === 'outofstock' ) {
					$terms[] = 'outofstock';
				}
			}

			if ( ! is_wp_error( wp_set_post_terms( $variation_id, $terms, 'product_visibility', false ) ) ) {
				delete_transient( 'wc_featured_products' );
				do_action( 'woocommerce_product_set_visibility', $variation_id, $terms );
				$set_visibility = true;
			}
		}

		do_action( 'iconic_wssv_product_processed', $variation_id );

		return $set_visibility;
	}

	/**
	 * Get `product_visibility` rated term value.
	 *
	 * @param WC_Product_Variation $variation The product variation.
	 * @return null|string
	 */
	protected static function get_product_visibility_rated_term( $variation ) {
		if ( empty( $variation ) || ! is_a( $variation, 'WC_Product_Variation' ) ) {
			return null;
		}

		$parent_product = wc_get_product( $variation->get_parent_id() );

		if ( ! $parent_product ) {
			return null;
		}

		$rating = min( 5, NumberUtil::round( $parent_product->get_average_rating(), 0 ) );

		if ( $rating <= 0 ) {
			return null;
		}

		return 'rated-' . $rating;
	}

	/**
	 * Set featured visibility.
	 *
	 * @param int  $variation_id
	 * @param bool $featured
	 * @param bool $meta_only
	 *
	 * @return bool
	 */
	public static function set_featured_visibility( $variation_id, $featured = null, $meta_only = false ) {
		$set_featured = true;
		$featured     = is_null( $featured ) ? Iconic_WSSV_Helpers::string_to_bool( get_post_meta( $variation_id, '_featured', true ) ) : $featured;

		if ( $featured ) {
			update_post_meta( $variation_id, '_featured', 'yes' );
		} else {
			delete_post_meta( $variation_id, '_featured' );
		}

		if ( $meta_only ) {
			return $set_featured;
		}

		if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			if ( $featured ) {
				$set_featured = wp_set_object_terms( $variation_id, 'featured', 'product_visibility', true );
			} else {
				$set_featured = wp_remove_object_terms( $variation_id, 'featured', 'product_visibility' );
			}
		}

		// @phpstan-ignore-next-line (It seems a false positive: `is_wp_error(true) will always evaluate to false`)
		if ( is_wp_error( $set_featured ) ) {
			return false;
		}

		delete_transient( 'wc_featured_products' );

		return true;
	}

	/**
	 * Add main product taxonomies to variation.
	 *
	 * @param int $variation_id
	 */
	public static function set_taxonomies( $variation_id ) {
		$taxonomies        = self::get_taxonomies();
		$manage_categories = self::get_manage_taxonomy( $variation_id, 'product_cat' );
		$manage_tags       = self::get_manage_taxonomy( $variation_id, 'product_tag' );

		if ( $manage_categories && ! in_array( 'product_cat', $taxonomies, true ) ) {
			$taxonomies[] = 'product_cat';
		}

		if ( $manage_tags && ! in_array( 'product_tag', $taxonomies, true ) ) {
			$taxonomies[] = 'product_tag';
		}

		if ( empty( $taxonomies ) ) {
			return;
		}

		self::maybe_delete_variation_taxonomy(
			$variation_id,
			array(
				'product_cat' => empty( $manage_categories ),
				'product_tag' => empty( $manage_tags ),
			)
		);

		$parent_product_id = wp_get_post_parent_id( $variation_id );

		foreach ( $taxonomies as $taxonomy ) {
			if (
				( 'product_cat' === $taxonomy && $manage_categories ) ||
				( 'product_tag' === $taxonomy && $manage_tags )
			) {
				$terms = array_filter(
					self::get_variation_terms_id( $variation_id, $taxonomy ),
					/**
					 * Filter out terms that don't exist.
					 */
					function( $term_id ) use ( $taxonomy ) {
						return get_term_by( 'term_id', $term_id, $taxonomy );
					}
				);
			} else {
				$terms = (array) wp_get_post_terms( $parent_product_id, $taxonomy, array( 'fields' => 'ids' ) );
			}

			wp_set_post_terms( $variation_id, array_filter( $terms ), $taxonomy );
		}
	}

	/**
	 * Get visibility.
	 *
	 * @param int $variation_id
	 *
	 * @return array
	 */
	public static function get_visibility( $variation_id ) {
		$visibility = get_post_meta( $variation_id, '_visibility', true );

		if ( ! is_array( $visibility ) || empty( $visibility ) ) {
			return array( 'hidden' );
		}

		return $visibility;
	}

	/**
	 * Get featured visibility.
	 *
	 * @param int $variation_id
	 *
	 * @return bool
	 */
	public static function get_featured_visibility( $variation_id ) {
		return get_post_meta( $variation_id, '_featured', true );
	}

	/**
	 * Get add to cart setting.
	 *
	 * @param int $variation_id
	 *
	 * @return bool
	 */
	public static function get_add_to_cart( $variation_id ) {
		return get_post_meta( $variation_id, '_disable_add_to_cart', true );
	}

	/**
	 * Get listings only setting.
	 *
	 * @param int $variation_id
	 *
	 * @return bool value of _listings_only meta data.
	 */
	public static function get_listings_only( $variation_id ) {
		/**
		 * Cache to store _listing_only meta data for all variations.
		 */
		static $listing_only_cache = array();

		// Check if value exists in cache.
		if ( isset( $listing_only_cache[ $variation_id ] ) ) {
			return $listing_only_cache[ $variation_id ];
		}

		// Fetch _listings_only meta data for all the sibling variations and save it in the cache
		// So if this data is queried for another variation_id we will not need to run another
		// SQL query.
		$variation = wc_get_product( $variation_id );

		if ( empty( $variation ) ) {
			return false;
		}

		$parent_id = $variation->get_parent_id();

		if ( empty( $parent_id ) ) {
			return false;
		}

		global $wpdb;

		$child_ids     = $wpdb->get_col( $wpdb->prepare( "select ID from {$wpdb->prefix}posts where post_parent = %d", $parent_id ) );
		$listings_only = $wpdb->get_results( $wpdb->prepare( "SELECT m.post_id, m.meta_value FROM {$wpdb->prefix}posts p, {$wpdb->prefix}postmeta m WHERE p.ID = m.post_id and p.post_parent = %d and meta_key = '_listings_only'", $parent_id ) );

		$listings_only_formatted = wp_list_pluck( $listings_only, 'meta_value', 'post_id' );

		// Save in the cache.
		foreach ( $child_ids as $loop_variation_id ) {
			$listing_only_cache[ $loop_variation_id ] = isset( $listings_only_formatted[ $loop_variation_id ] ) ? true : false;
		}

		return $listing_only_cache[ $variation_id ];
	}

	/**
	 * Get manage taxonomy setting. If true, it's possible to update the taxonomy
	 * at the variation level.
	 *
	 * @param int    $variation_id The variation ID.
	 * @param string $taxonomy     The taxonomy to check.
	 *
	 * @return bool
	 */
	public static function get_manage_taxonomy( $variation_id, $taxonomy ) {
		return filter_var( get_post_meta( $variation_id, '_manage_' . $taxonomy, true ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Set total sales.
	 *
	 * @param int $variation_id
	 *
	 * @return bool
	 */
	public static function set_total_sales( $variation_id ) {
		$total_sales = self::get_variation_sales( $variation_id );
		update_post_meta( $variation_id, 'total_sales', $total_sales );

		do_action( 'iconic_wssv_set_total_sales', $variation_id, $total_sales );

		return true;
	}

	/**
	 * Get total variation sales
	 *
	 * @param int $variation_id
	 *
	 * @return int
	 */
	public static function get_variation_sales( $variation_id ) {
		global $wpdb;

		$total_sales = $wpdb->get_var(
			$wpdb->prepare(
				"
                SELECT SUM(`quantities`.`meta_value`)
                FROM `{$wpdb->prefix}woocommerce_order_itemmeta` as `itemmeta`
                 LEFT JOIN  `{$wpdb->prefix}woocommerce_order_itemmeta` AS  `quantities` ON `itemmeta`.`order_item_id` = `quantities`.`order_item_id`
                  AND `quantities`.`meta_key` = '_qty'
                 LEFT JOIN `{$wpdb->prefix}woocommerce_order_items` as `items` ON `items`.`order_item_id`=`itemmeta`.`order_item_id`
                WHERE `itemmeta`.`meta_key` = '_variation_id'
                 AND `itemmeta`.`meta_value` = %d
                ",
				$variation_id
			)
		);

		return apply_filters( 'iconic_wssv_variation_total_sales', $total_sales );
	}

	/**
	 * Inherit parent rating.
	 *
	 * @param float      $value
	 * @param WC_Product $product
	 *
	 * @return float
	 */
	public static function get_average_rating( $value, $product ) {
		$parent_product = wc_get_product( $product->get_parent_id() );

		if ( ! $parent_product ) {
			return $value;
		}

		return $parent_product->get_average_rating();
	}

	/**
	 * Get variation title based on settings.
	 *
	 * @param int         $variation_id
	 * @param null|string $title
	 *
	 * @return string
	 */
	public static function get_title( $variation_id, $title = null ) {
		if ( ! is_null( $title ) && ! empty( $title ) ) {
			return $title;
		}

		$saved_title = get_post_meta( $variation_id, '_jck_wssv_display_title', true );

		if ( ! empty( $saved_title ) ) {
			return $saved_title;
		}

		global $jck_wssv;

		$variation_attributes = wc_get_product_variation_attributes( $variation_id );
		$parent_id            = wp_get_post_parent_id( $variation_id );
		$parent_title         = get_the_title( $parent_id );

		if ( 'attribute' === $jck_wssv->settings['general_variation_settings_title_format'] ) {
			/**
			 * Filter the attributes used in the title of the variation product.
			 *
			 * @since 1.9.0
			 * @hook iconic_wssv_variation_attributes_used_in_the_title
			 * @param  array $variation_attributes The variation attributes.
			 * @param  int   $variation_id         The variation ID.
			 * @return array New value
			 */
			$variation_attributes = apply_filters( 'iconic_wssv_variation_attributes_used_in_the_title', $variation_attributes, $variation_id );

			$title_suffix = wc_get_formatted_variation( $variation_attributes, true, false );

			/**
			 * Filter the variation title with attributes appended.
			 *
			 * @since 1.9.0
			 * @hook iconic_wssv_variation_title_with_attributes
			 * @param  string $title_with_attributes The variation title with attributes appended.
			 * @param  int    $variation_id          The variation ID.
			 * @return string New value
			 */
			$title_with_attributes = apply_filters( 'iconic_wssv_variation_title_with_attributes', $parent_title . ' - ' . $title_suffix, $variation_id );

			return $title_with_attributes;
		}

		return $parent_title;
	}

	/**
	 * Set variation title.
	 *
	 * @param int    $variation_id
	 * @param string $title
	 */
	public static function set_title( $variation_id, $title ) {
		global $wpdb;

		$meta_title   = $title;
		$title        = self::get_title( $variation_id, $meta_title );
		$allowed_html = Iconic_WSSV_Helpers::wp_kses_allowed_html_title();
		$title        = wp_kses( $title, $allowed_html );

		update_post_meta( $variation_id, '_jck_wssv_display_title', $meta_title );
		$wpdb->update( $wpdb->posts, array( 'post_title' => $title ), array( 'ID' => $variation_id ) );
	}

	/**
	 * Refresh title based on meta.
	 *
	 * @param int $variation_id
	 */
	public static function refresh_title( $variation_id ) {
		global $wpdb;

		$title = self::get_title( $variation_id );

		if ( empty( $title ) ) {
			return;
		}

		$wpdb->update( $wpdb->posts, array( 'post_title' => $title ), array( 'ID' => $variation_id ) );
	}

	/**
	 * Set add to cart.
	 *
	 * @param int  $variation_id
	 * @param bool $add_to_cart
	 */
	public static function set_add_to_cart( $variation_id, $add_to_cart ) {
		self::set_checkbox_meta( $variation_id, '_disable_add_to_cart', $add_to_cart );
	}

	/**
	 * Set listings only.
	 *
	 * @param int  $variation_id
	 * @param bool $listings_only
	 */
	public static function set_listings_only( $variation_id, $listings_only ) {
		self::set_checkbox_meta( $variation_id, '_listings_only', $listings_only );
	}

	/**
	 * Set `Manage categories?`.
	 *
	 * @param int  $variation_id The variation ID.
	 * @param bool $manage_categories_value The value.
	 */
	public static function set_manage_categories( $variation_id, $manage_categories_value ) {
		self::set_checkbox_meta( $variation_id, '_manage_product_cat', $manage_categories_value );
	}

	/**
	 * Set variation categories.
	 *
	 * @param int   $variation_id The variation ID.
	 * @param array $categories   The categories ID.
	 */
	public static function set_variation_categories( $variation_id, $categories ) {
		update_post_meta( $variation_id, '_jck_wssv_variation_product_cat', $categories );
	}

	/**
	 * Set variation tags.
	 *
	 * @param int   $variation_id The variation ID.
	 * @param array $tags         The tags ID.
	 */
	public static function set_variation_tags( $variation_id, $tags ) {
		update_post_meta( $variation_id, '_jck_wssv_variation_product_tag', $tags );
	}

	/**
	 * Get variation terms ID.
	 *
	 * @param int    $variation_id The variation ID.
	 * @param string $taxonomy     The taxonomy.
	 * @return array
	 */
	public static function get_variation_terms_id( $variation_id, $taxonomy ) {
		switch ( $taxonomy ) {
			case 'product_cat':
			case 'product_tag':
				$terms = get_post_meta( $variation_id, '_jck_wssv_variation_' . $taxonomy, true );

				return array_map( 'intval', array_filter( (array) $terms ) );

			default:
				return array();
		}
	}

	/**
	 * Delete the variation taxonomy if we don't need to manage it.
	 *
	 * @param int   $variation_id The variation ID.
	 * @param array $taxonomies   The array of taxonomies.
	 * @return void
	 */
	public static function maybe_delete_variation_taxonomy( $variation_id, $taxonomies ) {
		if ( ! is_array( $taxonomies ) ) {
			return;
		}

		foreach ( $taxonomies as $taxonomy => $should_delete ) {
			if ( $should_delete ) {
				delete_post_meta( $variation_id, '_jck_wssv_variation_' . $taxonomy );
			}
		}
	}

	/**
	 * Set `Manage tags?`.
	 *
	 * @param int  $variation_id The variation ID.
	 * @param bool $manage_tags_value The value.
	 */
	public static function set_manage_tags( $variation_id, $manage_tags_value ) {
		self::set_checkbox_meta( $variation_id, '_manage_product_tag', $manage_tags_value );
	}

	/**
	 * Set generic checkbox meta.
	 *
	 * @param $variation_id
	 * @param $key
	 * @param $value
	 */
	public static function set_checkbox_meta( $variation_id, $key, $value ) {
		if ( ! $value ) {
			delete_post_meta( $variation_id, $key );

			return;
		}

		update_post_meta( $variation_id, $key, $value );
	}

	/**
	 * Get variation taxonomies.
	 *
	 * @param int $parent_product_id
	 *
	 * @return array
	 */
	public static function get_taxonomies( $parent_product_id = null ) {
		return apply_filters(
			'iconic_wssv_variation_taxonomies',
			array(
				'product_cat',
				'product_tag',
			)
		);
	}

	/**
	 * Ignore custom visibility setting in cart.
	 *
	 * @param string $permalink
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @return string
	 */
	public static function cart_item_permalink( $permalink, $cart_item, $cart_item_key ) {
		$_product = $cart_item['data'];

		remove_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'is_visible' ), 10 );
		// If variation is in cart, always return true.
		$permalink = $_product->get_permalink( $cart_item );
		add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'is_visible' ), 10, 2 );

		return $permalink;
	}

	/**
	 * Don't get "listings only" children. Should be more performant
	 * than removing them after the query.
	 *
	 * @param array               $args    Array of `get_posts` args.
	 * @param WC_Product_Variable $product Product object.
	 * @param bool                $visible Is visible.
	 *
	 * @return array
	 */
	public static function variable_children_args( $args, $product, $visible ) {
		// Get current meta query.
		$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

		// Add our meta query.
		$meta_query[] = array(
			'key'     => '_listings_only',
			'compare' => 'NOT EXISTS',
		);

		$args['meta_query'] = $meta_query;

		return $args;
	}

	/**
	 * Modify the query when fetching variations via ajax to remove listings only variations.
	 * Specifically, this should remove listings only variations from the
	 * "find_matching_product_variation" function.
	 *
	 * @param string $query SQL query.
	 *
	 * @return string
	 */
	public static function remove_listing_only_variations_from_query( $query ) {
		// Checks if this is a matching variation query as there are no actions/filters
		// available in WC_Product_Data_Store_CPT->find_matching_product_variation().
		$is_matching_variation_query = strpos( $query, 'SELECT postmeta.post_id, postmeta.meta_key, postmeta.meta_value, posts.menu_order' ) !== false && strpos( $query, "post_type = 'product_variation'" ) !== false;

		if ( ! $is_matching_variation_query ) {
			return $query;
		}

		global $wpdb;

		// Ensures that none of the results are listings only.
		$exclude_query = "AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} AS listings_only WHERE listings_only.post_id = postmeta.post_id AND listings_only.meta_key = '_listings_only' ) ORDER BY";

		return str_replace( 'ORDER BY', $exclude_query, $query );
	}

	/**
	 * Get attributes assigned to variation which are not "used for variations".
	 *
	 * @param int $variation_id
	 */
	public static function get_non_variation_attributes( $variation_id ) {
		global $wpdb;

		$attributes = array();

		$sql = "
		SELECT taxonomies.taxonomy FROM {$wpdb->prefix}term_relationships as relationships
		LEFT JOIN {$wpdb->prefix}term_taxonomy as taxonomies on relationships.term_taxonomy_id = taxonomies.term_taxonomy_id
		WHERE relationships.object_id = %d
		AND taxonomies.taxonomy LIKE 'pa_%'
		";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $variation_id ), ARRAY_A );

		if ( ! $results ) {
			return $attributes;
		}

		$attributes = wp_list_pluck( $results, 'taxonomy' );

		return $attributes;
	}

	/**
	 * Get an array of unused attributes for a variation. Unused means it's not "used for variations"
	 * or it doesn't belong to the parent product.
	 *
	 * @param WC_Product_Variation|int $variation
	 * @param WC_Product_Variable|bool $product
	 *
	 * @return array
	 */
	public static function get_unused_attributes( $variation, $product = false ) {
		if ( is_numeric( $variation ) ) {
			$variation = wc_get_product( $variation );
		}

		if ( empty( $variation ) ) {
			return array();
		}

		if ( ! $product ) {
			$product = wc_get_product( $variation->get_parent_id() );
		}

		if ( ! is_a( $product, 'WC_Product_Variable' ) ) {
			return array();
		}

		$product_attributes   = array_keys( $product->get_attributes() );
		$variation_attributes = self::get_non_variation_attributes( $variation->get_id() );

		return array_diff( $variation_attributes, $product_attributes );
	}

	/**
	 * Remove any attributes of a variation which don't belong to the parent.
	 *
	 * @param WC_Product_Variation|int $variation
	 * @param WC_Product_Variable|bool $product
	 */
	public static function remove_unused_attributes( $variation, $product = false ) {
		$variation_id      = is_numeric( $variation ) ? $variation : $variation->get_id();
		$unused_attributes = self::get_unused_attributes( $variation );

		if ( empty( $unused_attributes ) ) {
			return;
		}

		foreach ( $unused_attributes as $unused_attribute ) {
			wp_set_object_terms( $variation_id, array(), $unused_attribute );
		}
	}

	/**
	 * Assign ratings to child product. Runs on hook 'comment_post'.
	 *
	 * @return void
	 */
	public static function assign_ratings_to_child_products() {
		$rating          = intval( filter_input( INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT ) );
		$comment_post_id = absint( filter_input( INPUT_POST, 'comment_post_ID', FILTER_SANITIZE_NUMBER_INT ) );

		if ( $rating && $comment_post_id && 'product' === get_post_type( $comment_post_id ) ) {
			self::sync_average_rating_to_child( $comment_post_id );
		}
	}

	/**
	 * Sync ratings downwards from Parent to Child. Updates both meta and wc_product_meta_lookup table.
	 *
	 * @param int $product_id
	 *
	 * @return void
	 */
	public static function sync_average_rating_to_child( $product_id ) {
		global $wpdb;

		$product = wc_get_product( $product_id );

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$average_rating    = $product->get_average_rating();
		$rating_count      = $product->get_rating_count();
		$child_product_ids = $product->get_children();

		$query = $wpdb->prepare(
			"
			UPDATE {$wpdb->prefix}wc_product_meta_lookup
			SET rating_count = %d, average_rating = %f
			WHERE product_id IN
			( SELECT ID FROM $wpdb->posts WHERE post_parent = %d )
		",
			$rating_count,
			$average_rating,
			$product_id
		);

		$wpdb->query( $query );

		foreach ( $child_product_ids as $child_product_id ) {
			update_post_meta( $child_product_id, '_wc_average_rating', $average_rating );
			update_post_meta( $child_product_id, '_wc_review_count', $rating_count );

			self::set_visibility( $child_product_id );
		}
	}

	/**
	 * Assign all attributes from parent to child when child has "any" attribute value.
	 * Otherwise variations don't show up in the filtered counts/results.
	 *
	 * @param int        $variation_id Variation Product ID.
	 * @param WC_Product $product      Parent Product.
	 *
	 * @return void
	 */
	public static function apply__any__attribute( $variation_id, $product ) {
		$attributes = $product->get_attributes();

		if ( empty( $attributes ) ) {
			return;
		}

		foreach ( $attributes as $taxonomy => $attribute ) {
			if ( empty( $attribute ) || ! $attribute->is_taxonomy() ) {
				continue;
			}

			$terms = get_the_terms( $variation_id, $taxonomy );

			// set parent's attribute to variation if $terms is empty.
			if ( empty( $terms ) ) {
				$terms    = $attribute->get_terms();
				$term_ids = wp_list_pluck( $terms, 'term_id' );
				wp_set_object_terms( $variation_id, $term_ids, $taxonomy );
			}
		}
	}

	/**
	 * Copy product visiblity when product is duplicated.
	 *
	 * @param WC_Product $duplicate The duplicated Product.
	 * @param WC_Product $product   The original product.
	 *
	 * @return void
	 */
	public static function duplicate_variations_visiblity( $duplicate, $product ) {
		$taxonomies_to_copy = array( 'product_visibility' );
		$meta_to_copy       = array( '_visibility', '_featured', '_disable_add_to_cart', '_listings_only' );

		// Opt-out for non-variation products.
		if ( ! $product->is_type( 'variation' ) ) {
			return;
		}

		// Because the duplicate product is unsaved and doesn't have an ID.
		$duplicate->save();

		if ( ! $duplicate->get_ID() ) {
			return;
		}

		// Copy taxonomies.
		foreach ( $taxonomies_to_copy as $taxonomy ) {
			$terms = get_the_terms( $product->get_id(), $taxonomy );
			if ( $terms && ! is_wp_error( $terms ) ) {
				wp_set_object_terms( $duplicate->get_ID(), wp_list_pluck( $terms, 'term_id' ), $taxonomy );
			}
		}

		// Copy meta data.
		foreach ( $meta_to_copy as $meta_key ) {
			$value = get_post_meta( $product->get_ID(), $meta_key, true );
			update_post_meta( $duplicate->get_ID(), $meta_key, $value );
		}
	}

	/**
	 * Assign any attributes "not used for variations" to variations.
	 * This means they show up when filtered.
	 *
	 * @param WC_Product_Variation|int $product Variation Product.
	 */
	public static function set_parent_attributes_to_variation( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product->is_type( 'variation' ) ) {
			return;
		}

		$parent_id = $product->get_parent_id();
		$parent    = wc_get_product( $parent_id );

		if ( empty( $parent ) ) {
			return;
		}

		self::remove_unused_attributes( $product->get_ID(), $parent );

		// Assign all attributes from parent to child when child has "any" attribute value.
		self::apply__any__attribute( $product->get_ID(), $parent );

		$attributes = $parent->get_attributes();

		if ( empty( $attributes ) ) {
			return;
		}

		global $jck_wssv;

		// Remove "non variation" attributes from variations when removed from parent.
		foreach ( $attributes as $taxonomy => $attribute_data ) {
			if ( $attribute_data['is_variation'] ) {
				continue;
			}

			$terms = wp_get_post_terms( $parent->get_id(), $taxonomy );

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			$term_ids = array();

			foreach ( $terms as $term ) {
				$term_ids[] = $term->term_id;
			}

			wp_set_object_terms( $product->get_ID(), $term_ids, $taxonomy );

			$jck_wssv->delete_count_transient( $taxonomy, $terms[0]->term_taxonomy_id );
		}
	}

	/**
	 * Get posted visibility settings.
	 *
	 * @param bool $index
	 *
	 * @return array
	 */
	public static function get_posted_visibility_settings( $index = false ) {
		$visibility = array();

		$require_array = false !== $index ? FILTER_REQUIRE_ARRAY : null;

		$posted = array(
			'catalog'  => filter_input( INPUT_POST, 'jck_wssv_variable_show_catalog', FILTER_DEFAULT, $require_array ),
			'filtered' => filter_input( INPUT_POST, 'jck_wssv_variable_show_filtered', FILTER_DEFAULT, $require_array ),
			'search'   => filter_input( INPUT_POST, 'jck_wssv_variable_show_search', FILTER_DEFAULT, $require_array ),
		);

		foreach ( $posted as $view => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			if ( is_array( $value ) && ( false !== $index ) && empty( $value[ $index ] ) ) {
				continue;
			}

			$visibility[] = $view;
		}

		return $visibility;
	}

	/**
	 * Get variation categories/tags send via POST.
	 *
	 * @param int    $variation_id The variation ID.
	 * @param string $taxonomy     The taxonomy.
	 * @return array
	 */
	public static function get_posted_variation_terms( $variation_id, $taxonomy ) {
		$terms = array();

		if ( empty( $variation_id ) || empty( $taxonomy ) ) {
			return $terms;
		}

		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

		// Handle when the index is running.
		if ( 'iconic_wssv_process_product_visibility' === $action ) {
			return get_post_meta( $variation_id, '_jck_wssv_variation_' . $taxonomy, true );
		}

		$variation_post_key = 'jck_wssv_variation_' . $taxonomy;

		if ( ! isset( $_POST[ $variation_post_key ][ $variation_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $terms;
		}

		$terms = array_filter(
			array_map(
				'sanitize_text_field',
				// phpcs:ignore WordPress.Security.NonceVerification
				wp_unslash( (array) $_POST[ $variation_post_key ][ $variation_id ] )
			)
		);

		return $terms;
	}

	/**
	 * Disable add to cart for 'listing only' variations.
	 *
	 * @param bool       $is_purchasable Whether the variation can be purchased.
	 * @param WC_Product $product        Product.
	 *
	 * @return bool
	 */
	public static function mark_listing_only_products_as_non_purchasable( $is_purchasable, $product ) {
		if ( ! $product->is_type( 'variation' ) ) {
			return $is_purchasable;
		}

		return $is_purchasable && ! self::get_listings_only( $product->get_id() );
	}

	/**
	 * Fallback to parent post (variable product) thumbnail ID if
	 * product variation thumbnail ID is empty.
	 *
	 * @param int|false        $thumbnail_id Post thumbnail ID or false if the post does not exist.
	 * @param int|WP_Post|null $post         Post ID or WP_Post object. Default is global $post.
	 * @return int|false
	 */
	public static function maybe_get_parent_post_thumbnail_id( $thumbnail_id, $post ) {
		if ( is_admin() || ! empty( $thumbnail_id ) || empty( $post ) || 'product_variation' !== $post->post_type ) {
			return $thumbnail_id;
		}

		$parent_post_thubmnail_id = get_post_thumbnail_id( $post->post_parent );

		if ( empty( $parent_post_thubmnail_id ) ) {
			return $thumbnail_id;
		}

		return $parent_post_thubmnail_id;
	}

	/**
	 * Get selectWoo options.
	 *
	 * @see https://select2.org/configuration/options-api.
	 * @param array $script_data The script data.
	 * @return array
	 */
	public static function localize_select_woo_options( $script_data ) {
		$default_options = array(
			'minimumInputLength' => 1,
			'multiple'           => true,
			'width'              => '100%',
			'allowClear'         => true,
			'ajax'               => array(
				'url'      => admin_url( 'admin-ajax.php' ),
				'dataType' => 'json',
				'delay'    => 250,
				'cache'    => true,
			),
		);

		$options = array(
			'categories' => $default_options,
			'tags'       => $default_options,
		);

		$script_data['select_woo_options'] = $options;

		return $script_data;
	}

	/**
	 * Output a select field to search for taxonomy terms.
	 *
	 * @param int    $variation_id The variation ID.
	 * @param string $taxonomy     The taxonomy to search.
	 * @param string $label        The field label.
	 * @return void
	 */
	public static function product_variation_taxonomy_term_search_field( $variation_id, $taxonomy, $label ) {
		if ( ! is_admin() || empty( $variation_id ) || empty( $taxonomy ) ) {
			return;
		}

		?>
		<div class="iconic-wssv-variation-<?php echo esc_attr( $taxonomy ); ?>" style="display: none;">
			<div class="form-row form-row-full">
				<p class="form-field">
					<label><?php echo esc_html( $label ); ?></label>
					<select
						class="iconic-wsb-form__product-search"
						name="jck_wssv_variation_<?php echo esc_attr( $taxonomy ); ?>[<?php echo esc_attr( $variation_id ); ?>][]"
						multiple="multiple"
					>
						<?php
						foreach ( self::get_variation_terms_id( $variation_id, $taxonomy ) as $term_id ) {
							$term = get_term_by( 'term_id', $term_id, $taxonomy );

							if ( empty( $term ) ) {
								continue;
							}

							$term_name = Iconic_WSSV_Helpers::get_term_name_with_parent_name( $term, $taxonomy );
							?>
								<option
									selected
									value="<?php echo esc_attr( $term_id ); ?>"
								>
									<?php echo esc_html( wp_strip_all_tags( $term_name ) ); ?>
								</option>
							<?php
						}
						?>
					</select>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Duplicate taxonomies added at variation level.
	 *
	 * @param WC_Product $duplicate        The duplicated variation.
	 * @param WC_Product $original_product the original variation.
	 * @return void
	 */
	public static function duplicate_taxonomies_added_at_variation_level( $duplicate, $original_product ) {
		if ( ! $original_product->is_type( 'variation' ) ) {
			return;
		}

		$taxonomies = [ 'product_cat', 'product_tag' ];

		foreach ( $taxonomies as $taxonomy ) {
			if ( empty( get_post_meta( $original_product->get_id(), "_manage_{$taxonomy}", true ) ) ) {
				continue;
			}

			$original_product_terms = self::get_variation_terms_id( $original_product->get_id(), $taxonomy );

			if ( empty( $original_product_terms ) ) {
				continue;
			}

			add_action(
				'woocommerce_update_product_variation',
				function( $variation_id ) use ( $duplicate, $original_product_terms, $taxonomy ) {
					if ( $variation_id !== $duplicate->get_id() ) {
						return;
					}

					update_post_meta( $duplicate->get_id(), "_jck_wssv_variation_{$taxonomy}", $original_product_terms );
					wp_set_object_terms( $duplicate->get_id(), $original_product_terms, $taxonomy );
				}
			);
		}
	}
}
