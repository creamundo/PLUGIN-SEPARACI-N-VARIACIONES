<?php
/**
 * Plugin Name: WooCommerce Show Single Variations by Iconic
 * Plugin URI: https://iconicwp.com/products/woocommerce-show-single-variations/
 * Description: Show WooCommerce product variations in the shop.
 * Version: 1.25.0
 * Author: Iconic
 * Author URI: https://iconicwp.com
 * Text Domain: iconic-wssv
 * WC requires at least: 2.6.14
 * WC tested up to: 9.8.5
 * Requires PHP: 7.4
 */

use Iconic_WSSV_NS\StellarWP\ContainerContract\ContainerInterface;


class Iconic_WSSV {
	public $slug = 'iconic-wssv';

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public static $name = 'WooCommerce Show Single Variations';

	public static $version = '1.25.0';

	public $plugin_path;

	public $plugin_url;

	public $theme = false;

	/**
	 * Variable to hold settings.
	 *
	 * @var array|null
	 */
	public $settings = null;

	/**
	 * The singleton instance of the plugin.
	 *
	 * @var Iconic_WSSV
	 */
	private static $instance;

	/**
	 * The DI container.
	 *
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * Construct the plugin
	 */
	public function __construct() {
		$this->define_constants();
		$this->load_classes();

		$this->container = new Iconic_WSSV_Core_Container();

		if ( ! Iconic_WSSV_Core_Helpers::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return;
		}

		add_action( 'init', array( $this, 'textdomain' ) );
		add_action( 'init', array( $this, 'initiate_hook' ) );

		// Declare compatibility with High-Performance Order Storage (HPOS).
		add_action(
			'before_woocommerce_init',
			function() {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				}
			}
		);
	}

	/**
	 * Instantiate a single instance of our plugin.
	 *
	 * @return Iconic_WSSV
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the DI container.
	 *
	 * @return ContainerInterface
	 */
	public function container() {
		return $this->container;
	}

	/**
	 * Load textdomain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'iconic-wssv', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Define Constants.
	 */
	private function define_constants() {
		$this->define( 'ICONIC_WSSV_FILE', __FILE__ );
		$this->define( 'ICONIC_WSSV_PATH', plugin_dir_path( ICONIC_WSSV_FILE ) );
		$this->define( 'ICONIC_WSSV_URL', plugin_dir_url( ICONIC_WSSV_FILE ) );
		$this->define( 'ICONIC_WSSV_INC_PATH', ICONIC_WSSV_PATH . 'inc/' );
		$this->define( 'ICONIC_WSSV_VENDOR_PATH', ICONIC_WSSV_INC_PATH . 'vendor/' );
		$this->define( 'ICONIC_WSSV_IS_ENVATO', false );
		$this->define( 'ICONIC_WSSV_BASENAME', plugin_basename( ICONIC_WSSV_FILE ) );
		$this->define( 'ICONIC_WSSV_PLUGIN_PATH_FILE', str_replace( trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) ), '', wp_normalize_path( ICONIC_WSSV_FILE ) ) );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name
	 * @param string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Load classes
	 */
	private function load_classes() {
		require_once ICONIC_WSSV_PATH . 'vendor-prefixed/autoload.php';
		require_once ICONIC_WSSV_INC_PATH . 'class-core-autoloader.php';
		require_once ICONIC_WSSV_INC_PATH . 'Integrations/WooCommerce/class-filter-by-stock-status.php';
		require_once ICONIC_WSSV_INC_PATH . 'Integrations/WooCommerce/Analytics/class-categories-report.php';

		Iconic_WSSV_Core_Autoloader::run(
			array(
				'prefix'   => 'Iconic_WSSV_',
				'inc_path' => ICONIC_WSSV_INC_PATH,
			)
		);

		$this->init_license();
		$this->init_telemetry();

		Iconic_WSSV_Core_Settings::run(
			array(
				'vendor_path'   => ICONIC_WSSV_VENDOR_PATH,
				'title'         => 'WooCommerce Show Single Variations',
				'version'       => self::$version,
				'menu_title'    => 'Show Single Variations',
				'settings_path' => ICONIC_WSSV_INC_PATH . 'admin/settings.php',
				'option_group'  => 'iconic_wssv',
				'docs'          => array(
					'collection'      => 'woocommerce-show-single-variations/',
					'troubleshooting' => 'woocommerce-show-single-variations/ssv-troubleshooting/',
					'getting-started' => 'woocommerce-show-single-variations/ssv-getting-started/',
				),
				'cross_sells'   => array(
					'iconic-woothumbs',
					'iconic-woo-attribute-swatches',
				),
			)
		);

		Iconic_WSSV_Database::run();
		Iconic_WSSV_Settings::run();
		Iconic_WSSV_Index::run();
		Iconic_WSSV_Query::init();
		Iconic_WSSV_Ajax::init();
		Iconic_WSSV_Term_Counts::init();
		Iconic_WSSV_Product::init();
		Iconic_WSSV_Product_Variation::init();
		Iconic_WSSV_Menu_Order::init();
		Iconic_WSSV_Most_Recent_Order::init();
		Iconic_WSSV_Popularity_Order::init();
		Iconic_WSSV_Rating_Order::init();
		Iconic_WSSV_Compat_Woodmart::init();
		Iconic_WSSV_Compat_Relevanssi::init();
		Iconic_WSSV_Checkout::init();
		Iconic_WSSV_Compat_WPML::init();
		Iconic_WSSV_Product_Block_Editor::init();
		Iconic_WSSV_Filter_By_Stock_Status::init();
		Iconic_WSSV_Rest::init();

		add_action( 'plugins_loaded', array( 'Iconic_WSSV_Core_Onboard', 'run' ), 10 );

		self::init_theme_compatibility();

		add_action( 'init', array( 'Iconic_WSSV_Compat_WP_All_Import', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_WP_All_Export', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_FacetWP', 'init_early' ), 1 );
		add_action( 'facetwp_init', array( 'Iconic_WSSV_Compat_FacetWP', 'init' ), 9 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_BeRocket_Ajax_Filters', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_XforWoo', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_Elementor', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_Yith_Recently_Viewed_Products', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_BodyCommerce', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_Yith_Ajax_Filters', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_WC_Wholesale_Prices', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_SearchWP', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_Sumo_Subscriptions', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_Product_Badges', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_Perfect_Brands_WooCommerce', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_YITH_WooCommerce_Badges_Management', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_Woolentor', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_Ajax_Search_Lite', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_Upsolution_Core', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_WooCommerce_Multilingual', 'init' ), 10 );
		add_action( 'init', array( 'Iconic_WSSV_Compat_Meta_Box', 'init' ), 10 );
		add_action( 'init', [ 'Iconic_WSSV_Compat_Goya', 'init' ], 10 );
		add_action( 'init', [ 'Iconic_WSSV_Compat_WooCommerce_Product_Filters', 'init' ], 10 );
		add_action( 'init', [ 'Iconic_WSSV_Categories_Report', 'init' ], 10 );
	}

	/**
	 * Init licence class.
	 */
	public function init_license() {
		// Allows us to transfer Freemius license.
		if ( file_exists( ICONIC_WSSV_PATH . 'class-core-freemius-sdk.php' ) ) {
			require_once ICONIC_WSSV_PATH . 'class-core-freemius-sdk.php';

			new Iconic_WSSV_Core_Freemius_SDK(
				array(
					'plugin_path'        => ICONIC_WSSV_PATH,
					'plugin_file'        => ICONIC_WSSV_FILE,
					'uplink_plugin_slug' => 'iconic-wssv',
					'freemius'           => array(
						'id'         => '1036',
						'slug'       => 'show-single-variations',
						'public_key' => 'pk_e6402c968382fd116b38f146a3c83',
					),
				)
			);
		}

		Iconic_WSSV_Core_License_Uplink::run(
			array(
				'basename'        => ICONIC_WSSV_BASENAME,
				'plugin_slug'     => 'iconic-wssv',
				'plugin_name'     => self::$name,
				'plugin_version'  => self::$version,
				'plugin_path'     => ICONIC_WSSV_PLUGIN_PATH_FILE,
				'plugin_class'    => self::class,
				'option_group'    => 'iconic_wssv',
				'urls'            => array(
					'product' => 'https://iconicwp.com/products/woocommerce-show-single-variations/',
				),
				'container_class' => self::class,
				'license_class'   => Iconic_WSSV_Core_Uplink_Helper::class,
			)
		);
	}

	/**
	 * Init telemetry class.
	 *
	 * @return void
	 */
	public function init_telemetry() {
		Iconic_WSSV_Core_Telemetry::run(
			array(
				'file'                  => __FILE__,
				'plugin_slug'           => 'iconic-wssv',
				'option_group'          => 'iconic_wssv',
				'plugin_name'           => self::$name,
				'plugin_url'            => ICONIC_WSSV_URL,
				'opt_out_settings_path' => 'sections/license/fields',
				'container_class'       => self::class,
			)
		);
	}

	/**
	 * Set settings.
	 */
	public function set_settings() {
		$this->settings = Iconic_WSSV_Core_Settings::$settings;
	}

	/**
	 * Init theme compatibility.
	 */
	public static function init_theme_compatibility() {
		$theme      = wp_get_theme();
		$class_name = sprintf( 'Iconic_WSSV_Compat_%s', ucwords( $theme->template ) );

		if ( ! class_exists( $class_name ) ) {
			return;
		}

		add_action( 'init', array( $class_name, 'init' ), 10 );
	}

	/**
	 * Run after the current user is set (http://codex.wordpress.org/Plugin_API/Action_Reference)
	 */
	public function initiate_hook() {
		// Force license activation
		$license_key_option = 'stellarwp_uplink_license_key_iconic-wssv';
		$license_status_option = 'stellarwp_uplink_license_key_status_iconic-wssv';
		$license_timeout_option = 'stellarwp_uplink_license_key_status_iconic-wssv_timeout';

		// Only update if not already valid
		if (get_option($license_status_option) !== 'valid') {
			// Set a dummy license key
			update_option($license_key_option, Iconic_WSSV_Core_Uplink_Helper::DATA);
			// Set the license status to valid
			update_option($license_status_option, 'valid');
			// Set a long timeout (1 year in seconds)
			update_option($license_timeout_option, time() + (365 * DAY_IN_SECONDS));
		}

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

			add_action( 'woocommerce_variation_options', array( $this, 'add_variation_checkboxes' ), 9, 3 );
			add_action(
				'woocommerce_product_after_variable_attributes',
				array(
					$this,
					'add_variation_additional_fields',
				),
				10,
				3
			);
			add_action(
				'woocommerce_variable_product_bulk_edit_actions',
				array(
					$this,
					'add_variation_bulk_edit_actions',
				),
				10
			);
			add_action( 'woocommerce_bulk_edit_variations_default', array( $this, 'bulk_edit_variations' ), 10, 4 );

			add_action( 'wp_ajax_jck_wssv_add_to_cart', array( $this, 'add_to_cart' ) );
			add_action( 'wp_ajax_nopriv_jck_wssv_add_to_cart', array( $this, 'add_to_cart' ) );

			add_action( 'set_object_terms', array( $this, 'set_variation_terms' ), 10, 6 );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

			add_filter( 'post_class', array( $this, 'add_post_classes_in_loop' ) );
			add_filter( 'woocommerce_product_is_visible', array( $this, 'filter_variation_visibility' ), 10, 2 );

			add_filter( 'post_type_link', array( $this, 'change_variation_permalink' ), 10, 2 );
			add_filter(
				'woocommerce_loop_add_to_cart_link',
				array(
					$this,
					'change_variation_add_to_cart_link',
				),
				10,
				2
			);

			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_to_cart_text' ), 10, 2 );
			add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'add_to_cart_url' ), 10, 2 );

			add_filter( 'post_class', array( $this, 'product_post_class' ), 20, 3 );

			add_action( 'delete_transient_wc_term_counts', array( $this, 'delete_term_counts_transient' ), 10, 1 );

			add_filter(
				'woocommerce_price_filter_post_type',
				array(
					$this,
					'add_product_variation_to_price_filter',
				),
				10,
				1
			);
		}

		add_action( 'save_post', array( $this, 'on_product_save' ), 100, 1 );

		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 3 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'process_shop_order' ), 10, 2 );

		$this->add_hooks_on_variation_save();
		$this->handle_wc_scheduled_sales();
		$this->register_taxonomy_for_object_type();
	}

	/**
	 * Admin styles.
	 */
	public function admin_styles() {
		$screen = get_current_screen();

		if (
			! Iconic_WSSV_Core_Settings::is_settings_page() &&
			( empty( $screen ) || 'product' !== $screen->id )
		) {
			return;
		}

		wp_register_style(
			'iconic-woo-show-single-variations-styles',
			ICONIC_WSSV_URL . 'assets/admin/css/main.min.css',
			array(
				'woocommerce_admin_styles',
				'wc-admin-layout',
			),
			self::$version
		);

		wp_enqueue_style( 'iconic-woo-show-single-variations-styles' );
	}

	/**
	 * Admin scripts.
	 */
	public function admin_scripts() {
		$screen = get_current_screen();

		if (
			! Iconic_WSSV_Core_Settings::is_settings_page() &&
			( empty( $screen ) || 'product' !== $screen->id )
		) {
			return;
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script(
			'iconic-woo-show-single-variations-scripts',
			ICONIC_WSSV_URL . 'assets/admin/js/main' . $min . '.js',
			array(
				'jquery',
				'wc-enhanced-select',
			),
			self::$version,
			true
		);

		/**
		 * Filter the data to be localized in the admin script.
		 *
		 * @since 1.10.0
		 * @hook iconic_wssv_l10n_admin_script_data
		 * @param  array $script_data The data to be localized
		 * @return array New value
		 */
		$script_data = apply_filters( 'iconic_wssv_l10n_admin_script_data', array() );

		wp_localize_script( 'iconic-woo-show-single-variations-scripts', 'jck_wssv_vars', $script_data );
		wp_localize_script( 'iconic-woo-show-single-variations-scripts', 'jckWssvVars', $script_data );

		wp_enqueue_script( 'iconic-woo-show-single-variations-scripts' );
	}

	/**
	 * Frontend scripts.
	 */
	public function frontend_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( $this->slug . '_scripts', ICONIC_WSSV_URL . 'assets/frontend/js/main' . $min . '.js', array( 'jquery' ), self::$version, true );

		wp_enqueue_script( $this->slug . '_scripts' );

		$vars = array(
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( $this->slug ),
			'pluginSlug' => $this->slug,
		);

		wp_localize_script( $this->slug . '_scripts', 'jck_wssv_vars', $vars );
		wp_localize_script( $this->slug . '_scripts', 'jckWssvVars', $vars );
	}

	/**
	 * Helper: Get filtered variation ids
	 *
	 * @return [arr]
	 */
	public function get_filtered_variation_ids() {
		global $_chosen_attributes;

		$variation_ids = array();

		$args = array(
			'post_type'      => 'product_variation',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => '_visibility',
					'value'   => 'filtered',
					'compare' => 'LIKE',
				),
			),
		);

		$min_price = isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : false;
		$max_price = isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : false;

		if ( $min_price !== false && $max_price !== false ) {
			$args['meta_query'][] = array(
				'key'     => '_price',
				'value'   => array( $min_price, $max_price ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}

		if ( $_chosen_attributes && ! empty( $_chosen_attributes ) ) {
			$i = 10;
			foreach ( $_chosen_attributes as $attribute_key => $attribute_data ) {
				$attribute_meta_key = sprintf( 'attribute_%s', $attribute_key );

				$attribute_term_slugs = array();

				foreach ( $attribute_data['terms'] as $attribute_term_id ) {
					$attribute_term         = get_term_by( 'id', $attribute_term_id, $attribute_key );
					$attribute_term_slugs[] = $attribute_term->slug;
				}

				if ( $attribute_data['query_type'] == 'or' ) {
					$args['meta_query'][ $i ] = array(
						'key'     => $attribute_meta_key,
						'value'   => $attribute_term_slugs,
						'compare' => 'IN',
					);
				} else {
					$args['meta_query'][ $i ] = array(
						'relation' => 'AND',
					);

					foreach ( $attribute_term_slugs as $attribute_term_slug ) {
						$args['meta_query'][ $i ][] = array(
							'key'     => $attribute_meta_key,
							'value'   => $attribute_term_slug,
							'compare' => '=',
						);
					}
				}

				$i ++;
			}
		}

		$variations = new WP_Query( $args );

		if ( $variations->have_posts() ) {
			while ( $variations->have_posts() ) {
				$variations->the_post();

				$variation_ids[] = get_the_id();
			}
		}

		wp_reset_postdata();

		return $variation_ids;
	}

	/**
	 * Frontend: Add relevant product classes to loop item
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public function add_post_classes_in_loop( $classes ) {
		global $product;

		if ( ! $product || ! is_object( $product ) || ! $product->is_type( 'variation' ) ) {
			return $classes;
		}

		$classes   = array_diff( $classes, array( 'hentry', 'post' ) );
		$classes[] = 'product';
		// Improve compatibility with themes that rely on the type-product class
		$classes[] = 'type-product';

		return $classes;
	}

	/**
	 * Admin: Add variation checkboxes
	 *
	 * @param  string $loop
	 * @param  array  $variation_data
	 * @param  object $variation
	 */
	public function add_variation_checkboxes( $loop, $variation_data, $variation ) {
		include 'inc/admin/variation-checkboxes.php';
	}

	/**
	 * Admin: Add variation options
	 *
	 * @param  string $loop
	 * @param  array  $variation_data
	 * @param  object $variation
	 */
	public function add_variation_additional_fields( $loop, $variation_data, $variation ) {
		include 'inc/admin/variation-additional-fields.php';
	}

	/**
	 * Admin: Add variation bulk edit actions
	 */
	public function add_variation_bulk_edit_actions() {
		include 'inc/admin/variation-bulk-edit-actions.php';
	}

	/**
	 * Admin: Bulk edit actions
	 *
	 * @param  string $bulk_action
	 * @param  array  $data
	 * @param  int    $product_id
	 * @param  array  $variations
	 */
	public function bulk_edit_variations( $bulk_action, $data, $product_id, $variations ) {
		if ( method_exists( $this, "variation_bulk_action_$bulk_action" ) ) {
			call_user_func( array( $this, "variation_bulk_action_$bulk_action" ), $variations );
			$this->delete_term_counts_transient();
		}
	}

	/**
	 * Helper: Unset array item by the value
	 *
	 * @param  array  $array
	 * @param  string $value
	 *
	 * @return array
	 */
	public static function unset_item_by_value( $array, $value ) {
		if ( ( $key = array_search( $value, $array ) ) !== false ) {
			unset( $array[ $key ] );
		}

		return $array;
	}

	/**
	 * Admin: Bulk Action - Toggle Show in (x)
	 *
	 * @param array  $variations
	 * @param string $show
	 */
	private function variation_bulk_action_toggle_show_in( $variations, $show ) {
		foreach ( $variations as $i => $variation_id ) {
			$visibility = (array) get_post_meta( $variation_id, '_visibility', true );
			$visibility = self::unset_item_by_value( $visibility, 'hidden' );

			if ( in_array( $show, $visibility ) ) {
				$visibility = self::unset_item_by_value( $visibility, $show );

				if ( $show == 'filtered' ) {
					$this->add_attributes_to_variation( $variation_id, false, 'remove' );
				}
			} else {
				$visibility[] = $show;

				if ( $show == 'filtered' ) {
					$this->add_attributes_to_variation( $variation_id, false, 'add' );
				}
			}

			if ( empty( $visibility ) ) {
				$visibility[] = 'hidden';
			}

			Iconic_WSSV_Product_Variation::set_taxonomies( $variation_id );
			Iconic_WSSV_Product_Variation::set_visibility( $variation_id, $visibility );
			$this->delete_term_counts_transient();
		}
	}

	/**
	 * Admin: Bulk Action - Toggle Show in Search
	 *
	 * @param  array $variations
	 */
	private function variation_bulk_action_toggle_show_in_search( $variations ) {
		$this->variation_bulk_action_toggle_show_in( $variations, 'search' );
	}

	/**
	 * Admin: Bulk Action - Toggle Show in Filtered
	 *
	 * @param  array $variations
	 */
	private function variation_bulk_action_toggle_show_in_filtered( $variations ) {
		$this->variation_bulk_action_toggle_show_in( $variations, 'filtered' );
	}

	/**
	 * Admin: Bulk Action - Toggle Show in Catalog
	 *
	 * @param  array $variations
	 */
	private function variation_bulk_action_toggle_show_in_catalog( $variations ) {
		$this->variation_bulk_action_toggle_show_in( $variations, 'catalog' );
	}

	/**
	 * Admin: Bulk Action - Toggle Featured
	 *
	 * @param array $variations
	 */
	private function variation_bulk_action_toggle_featured( $variations ) {
		foreach ( $variations as $variation_id ) {
			$featured = get_post_meta( $variation_id, '_featured', true ) !== 'yes';
			Iconic_WSSV_Product_Variation::set_featured_visibility( $variation_id, $featured );
		}
	}

	/**
	 * Admin: Bulk Action - Toggle Disable "Add to Cart"
	 *
	 * @param  array $variations
	 */
	private function variation_bulk_action_toggle_disable_add_to_cart( $variations ) {
		foreach ( $variations as $variation_id ) {
			$disable_add_to_cart = get_post_meta( $variation_id, '_disable_add_to_cart', true );

			if ( $disable_add_to_cart ) {
				delete_post_meta( $variation_id, '_disable_add_to_cart' );
			} else {
				update_post_meta( $variation_id, '_disable_add_to_cart', true );
			}
		}
	}

	/**
	 * Admin: Save variation options
	 *
	 * @param int $variation_id
	 * @param int $i
	 */
	public function save_product_variation( $variation_id, $i = false ) {
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

		$data = array(
			'visibility'           => Iconic_WSSV_Product_Variation::get_posted_visibility_settings( $i ),
			// phpcs:disable WordPress.Security.NonceVerification
			'add_to_cart'          => false !== $i ? isset( $_POST['jck_wssv_variable_disable_add_to_cart'][ $i ] ) : Iconic_WSSV_Product_Variation::get_add_to_cart( $variation_id ),
			'featured_visibility'  => false !== $i ? isset( $_POST['jck_wssv_variable_featured'][ $i ] ) : Iconic_WSSV_Product_Variation::get_featured_visibility( $variation_id ),
			'listings_only'        => false !== $i ? isset( $_POST['jck_wssv_variable_listings_only'][ $i ] ) : Iconic_WSSV_Product_Variation::get_listings_only( $variation_id ),
			'manage_categories'    => false !== $i ? isset( $_POST['jck_wssv_variable_manage_product_cat'][ $i ] ) : Iconic_WSSV_Product_Variation::get_manage_taxonomy( $variation_id, 'product_cat' ),
			'manage_tags'          => false !== $i ? isset( $_POST['jck_wssv_variable_manage_product_tag'][ $i ] ) : Iconic_WSSV_Product_Variation::get_manage_taxonomy( $variation_id, 'product_tag' ),
			'title'                => false !== $i && isset( $_POST['jck_wssv_display_title'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['jck_wssv_display_title'][ $i ] ) ) : get_post_meta( $variation_id, '_jck_wssv_display_title', true ),
			// phpcs:enable WordPress.Security.NonceVerification
			'variation_categories' => Iconic_WSSV_Product_Variation::get_posted_variation_terms( $variation_id, 'product_cat' ),
			'variation_tags'       => Iconic_WSSV_Product_Variation::get_posted_variation_terms( $variation_id, 'product_tag' ),
		);

		if ( empty( $data['visibility'] ) ) {
			// Bluk: If this is a bulk process and data is empty, variation should be hidden.
			if ( 'iconic_wssv_process_product_visibility' === $action ) {
				$data['visibility'] = array( 'hidden' );
			} elseif ( false === $i ) {
				$data['visibility'] = Iconic_WSSV_Product_Variation::get_visibility( $variation_id );
			} else {
				$data['visibility'] = array( 'hidden' );
			}
		}

		// Bulk: Don't overwrite visibility if it already has meta and overwrite is false.
		if ( 'iconic_wssv_process_product_visibility' === $action ) {
			$overwrite           = (int) filter_input( INPUT_POST, 'iconic_wssv_variation_visibility_overwrite', FILTER_SANITIZE_NUMBER_INT );
			$has_visibility_meta = get_post_meta( $variation_id, '_visibility', true );

			if ( $has_visibility_meta && ! $overwrite ) {
				$data['visibility'] = Iconic_WSSV_Product_Variation::get_visibility( $variation_id );
			}
		}

		// Save data.
		foreach ( $data as $method => $value ) {
			$method_name = sprintf( 'set_%s', $method );
			call_user_func_array( array( 'Iconic_WSSV_Product_Variation', $method_name ), array( $variation_id, $value ) );
		}
	}

	/**
	 * Frontend: Change variation permalink
	 *
	 * @param  string $url
	 * @param  string $post
	 *
	 * @return string
	 */
	public function change_variation_permalink( $url, $post ) {
		if ( 'product_variation' == $post->post_type ) {
			$variation = wc_get_product( absint( $post->ID ) );

			return $this->get_variation_url( $variation );
		}

		return $url;
	}

	/**
	 * Helper: Get variation URL
	 *
	 * @param  string $variation
	 *
	 * @return string
	 */
	public function get_variation_url( $variation ) {
		$url                 = '';
		$variation_parent_id = method_exists( $variation, 'get_parent_id' ) ? $variation->get_parent_id() : $variation->parent->id;

		if ( $variation_parent_id ) {
			$variation_data             = array_filter( wc_get_product_variation_attributes( $variation->get_id() ) );
			$parent_product_url         = get_the_permalink( $variation_parent_id );
			$url_encoded_variation_data = array_map( 'rawurlencode', $variation_data );

			$url = add_query_arg( $url_encoded_variation_data, $parent_product_url );
		}

		return $url;
	}

	/**
	 * Frontend: Change variation add to cart link
	 *
	 * @param string     $anchor
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public function change_variation_add_to_cart_link( $anchor, $product ) {
		$product_id = $product->get_id();

		if ( empty( $product_id ) ) {
			return $anchor;
		}

		$product_type = method_exists( $product, 'get_type' ) ? $product->get_type() : $product->product_type;

		if ( $product_type !== 'variation' ) {
			return $anchor;
		}

		$is_purchasable      = $this->is_purchasable( $product );
		$disable_add_to_cart = (bool) get_post_meta( $product_id, '_disable_add_to_cart', true );
		$button_class        = $is_purchasable && $product->is_in_stock() ? 'add_to_cart add_to_cart_button' : '';

		if ( $is_purchasable && ( ! $disable_add_to_cart && 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) ) ) {
			$button_class .= ' jck_wssv_add_to_cart';
		}

		$args = apply_filters(
			'iconic_wssv_button_args',
			array(
				'href'           => $product->add_to_cart_url(),
				'product_id'     => $product_id,
				'sku'            => $product->get_sku(),
				'qty'            => 1,
				'button_class'   => $button_class,
				'product_type'   => $product_type,
				'button_text'    => $this->get_add_to_cart_button_text( $product ),
				'attributes'     => array(
					'data-product_id'  => $product->get_id(),
					'data-product_sku' => $product->get_sku(),
					'aria-label'       => $product->add_to_cart_description(),
					'rel'              => 'nofollow',
				),
				'is_purchasable' => $is_purchasable,
			),
			$product
		);

		$attributes         = array();
		$args['attributes'] = array_filter( (array) $args['attributes'] );

		if ( ! empty( $args['attributes'] ) && is_array( $args['attributes'] ) ) {
			foreach ( $args['attributes'] as $attribute => $attribute_value ) {
				$attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		return sprintf(
			'<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" data-quantity="%s" class="button %s product_type_%s" data-variation_id="%s" %s>%s</a>',
			esc_url( $args['href'] ),
			esc_attr( $args['product_id'] ),
			esc_attr( $args['sku'] ),
			esc_attr( $args['qty'] ),
			esc_attr( apply_filters( 'jck_wssv_add_to_cart_button_class', $args['button_class'] ) ),
			esc_attr( $args['product_type'] ),
			esc_html( $args['product_id'] ),
			implode( ' ', $attributes ),
			$args['button_text']
		);
	}

	/**
	 * Helper: Get add to cart button text
	 *
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public function get_add_to_cart_button_text( $product ) {
		return apply_filters( 'iconic_wssv_add_to_cart_button_text', $product->add_to_cart_text(), $product );
	}

	/**
	 * Helper: Is product variation?
	 *
	 * @param  int $id
	 *
	 * @return [bool]
	 */
	public function is_product_variation( $id ) {
		$post_type = get_post_type( $id );

		return $post_type == 'product_variation' ? true : false;
	}

	/**
	 * Admin: Get variation checkboxes
	 *
	 * @param  object $variation
	 * @param  int    $index
	 *
	 * @return [arr]
	 */
	public function get_variation_checkboxes( $variation, $index ) {
		$visibility          = get_post_meta( $variation->ID, '_visibility', true );
		$featured            = get_post_meta( $variation->ID, '_featured', true );
		$disable_add_to_cart = get_post_meta( $variation->ID, '_disable_add_to_cart', true );
		$listings_only       = get_post_meta( $variation->ID, '_listings_only', true );
		$manage_categories   = get_post_meta( $variation->ID, '_manage_product_cat', true );
		$manage_tags         = get_post_meta( $variation->ID, '_manage_product_tag', true );

		$checkboxes = array(
			array(
				'class'   => 'jck_wssv_variable_show_search',
				'name'    => sprintf( 'jck_wssv_variable_show_search[%d]', $index ),
				'id'      => sprintf( 'jck_wssv_variable_show_search-%d', $index ),
				'checked' => is_array( $visibility ) && in_array( 'search', $visibility ) ? true : false,
				'label'   => __( 'Show in Search Results?', 'iconic-wssv' ),
			),
			array(
				'class'   => 'jck_wssv_variable_show_filtered',
				'name'    => sprintf( 'jck_wssv_variable_show_filtered[%d]', $index ),
				'id'      => sprintf( 'jck_wssv_variable_show_filtered-%d', $index ),
				'checked' => is_array( $visibility ) && in_array( 'filtered', $visibility ) ? true : false,
				'label'   => __( 'Show in Filtered Results?', 'iconic-wssv' ),
			),
			array(
				'class'   => 'jck_wssv_variable_show_catalog',
				'name'    => sprintf( 'jck_wssv_variable_show_catalog[%d]', $index ),
				'id'      => sprintf( 'jck_wssv_variable_show_catalog-%d', $index ),
				'checked' => is_array( $visibility ) && in_array( 'catalog', $visibility ) ? true : false,
				'label'   => __( 'Show in Catalog?', 'iconic-wssv' ),
			),
			array(
				'class'   => 'jck_wssv_variable_featured',
				'name'    => sprintf( 'jck_wssv_variable_featured[%d]', $index ),
				'id'      => sprintf( 'jck_wssv_variable_featured-%d', $index ),
				'checked' => $featured === 'yes' ? true : false,
				'label'   => __( 'Featured', 'iconic-wssv' ),
			),
			array(
				'class'   => 'jck_wssv_variable_disable_add_to_cart',
				'name'    => sprintf( 'jck_wssv_variable_disable_add_to_cart[%d]', $index ),
				'id'      => sprintf( 'jck_wssv_variable_disable_add_to_cart-%d', $index ),
				'checked' => $disable_add_to_cart ? true : false,
				'label'   => __( 'Disable "Add to Cart"?', 'iconic-wssv' ),
				'desc'    => 'Use the "Select Options" button in product listings instead of "Add to Cart".',
			),
			array(
				'class'   => 'jck_wssv_variable_listings_only',
				'name'    => sprintf( 'jck_wssv_variable_listings_only[%d]', $index ),
				'id'      => sprintf( 'jck_wssv_variable_listings_only-%d', $index ),
				'checked' => $listings_only ? true : false,
				'label'   => __( 'Listings Only?', 'iconic-wssv' ),
				'desc'    => "Enable to only show this variation in product listings. It won't be purchasable on the single product page.",
			),
			array(
				'class'   => 'jck_wssv_variable_manage_product_cat',
				'name'    => sprintf( 'jck_wssv_variable_manage_product_cat[%d]', $index ),
				'id'      => sprintf( 'jck_wssv_variable_manage_product_cat-%d', $index ),
				'checked' => (bool) $manage_categories,
				'label'   => __( 'Manage categories?', 'iconic-wssv' ),
				'desc'    => __( 'Allow selecting categories at variation level.', 'iconic-wssv' ),
			),
			array(
				'class'   => 'jck_wssv_variable_manage_product_tag',
				'name'    => sprintf( 'jck_wssv_variable_manage_product_tag[%d]', $index ),
				'id'      => sprintf( 'jck_wssv_variable_manage_product_tag-%d', $index ),
				'checked' => (bool) $manage_tags,
				'label'   => __( 'Manage tags?', 'iconic-wssv' ),
				'desc'    => __( 'Allow selecting tags at variation level.', 'iconic-wssv' ),
			),
		);

		return $checkboxes;
	}

	/**
	 * Helper: Filter variaiton visibility
	 *
	 * Set variation to is_visible() if the options are selected
	 *
	 * @param  bool $visible
	 * @param  bool $id
	 *
	 * @return [bool]
	 */
	public function filter_variation_visibility( $visible, $id ) {
		global $product;

		if ( ! is_object( $product ) ) {
			return $visible;
		}

		if ( method_exists( $product, 'get_id' ) ) {
			$visibility = get_post_meta( $product->get_id(), '_visibility', true );

			if ( is_array( $visibility ) ) {
				// visible in search

				if ( $this->is_visible_when( 'search', $product->get_id() ) ) {
					$visible = true;
				}

				// visible in filtered

				if ( $this->is_visible_when( 'filtered', $product->get_id() ) ) {
					$visible = true;
				}

				// visible in catalog

				if ( $this->is_visible_when( 'catalog', $product->get_id() ) ) {
					$visible = true;
				}
			}
		}

		return $visible;
	}

	/**
	 * Helper: Is visible when...
	 *
	 * Check if a variation is visible when search, filtered, catalog
	 *
	 * @param string $when Possible values 'search', 'filtered', 'catalog'.
	 * @param int    $id   Product ID.
	 *
	 * @return bool
	 */
	public function is_visible_when( $when, $id ) {
		$visibility = get_post_meta( $id, '_visibility', true );

		if ( is_array( $visibility ) ) {
			// Visible in search.
			if ( is_search() && in_array( $when, $visibility ) ) {
				return true;
			}

			// Visible in filtered.
			if ( is_filtered() && in_array( $when, $visibility ) ) {
				return true;
			}

			// Visible in catalog.
			if ( ! is_filtered() && ! is_search() && in_array( $when, $visibility ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Ajax: Add to cart
	 */
	public static function add_to_cart() {
		ob_start();

		$product_id           = apply_filters( 'jck_wssv_add_to_cart_product_id', absint( $_POST['product_id'] ) );
		$variation_id         = apply_filters( 'jck_wssv_add_to_cart_variation_id', absint( $_POST['variation_id'] ) );
		$quantity             = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( $_POST['quantity'] );
		$passed_validation    = apply_filters( 'jck_wssv_add_to_cart_validation', true, $variation_id, $quantity );
		$product_status       = get_post_status( $variation_id );
		$variation            = new WC_Product_Variation( absint( $variation_id ) );
		$variation_attributes = $variation->get_variation_attributes();

		if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation_attributes ) && 'publish' === $product_status ) {
			do_action( 'woocommerce_ajax_added_to_cart', $variation_id );
			if ( get_option( 'woocommerce_cart_redirect_after_add' ) == 'yes' ) {
				wc_add_to_cart_message( $product_id );
			}

			$wc_ajax = new WC_AJAX();

			// Return fragments
			$wc_ajax->get_refreshed_fragments();
		} else {
			// If there was an error adding to the cart, redirect to the product page to show any errors
			$data = array(
				'error'       => true,
				'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id ),
			);

			wp_send_json( $data );
		}

		wp_die();
	}

	/**
	 * Add product_variation to tags and categories
	 */
	public function register_taxonomy_for_object_type() {
		$taxonomies = Iconic_WSSV_Product_Variation::get_taxonomies();

		if ( empty( $taxonomies ) ) {
			return;
		}

		foreach ( $taxonomies as $taxonomy ) {
			register_taxonomy_for_object_type( $taxonomy, 'product_variation' );
		}
	}

	/**
	 * Admin: Save variation attributes
	 *
	 * @param int      $variation_id
	 * @param int|bool $i
	 * @param bool     $force
	 */
	public function add_attributes_to_variation( $variation_id, $i = false, $force = false ) {
		$posted_visibility = Iconic_WSSV_Product_Variation::get_posted_visibility_settings( $i );
		$show_in_filtered  = in_array( 'filtered', $posted_visibility, true );
		$attributes        = wc_get_product_variation_attributes( $variation_id );

		// If $i is not set - i.e. bulk edit or API call - get the current visibility
		// and ensure attributes are set if the variation is visible in filtered.
		if ( ! $i && empty( $posted_visibility ) ) {
			$visibility       = Iconic_WSSV_Product_Variation::get_visibility( $variation_id );
			$show_in_filtered = in_array( 'filtered', $visibility, true );
		}

		if ( $attributes && ! empty( $attributes ) ) {
			foreach ( $attributes as $taxonomy => $value ) {
				$taxonomy = str_replace( 'attribute_', '', $taxonomy );
				$term     = get_term_by( 'slug', $value, $taxonomy );

				delete_transient( 'wc_layered_nav_counts_' . $taxonomy );

				// If we're forcing attributes to be added, or the variation
				// is set to show in filtered.
				if ( 'add' === $force || $show_in_filtered ) {
					wp_set_object_terms( $variation_id, $value, $taxonomy );
				} elseif ( $term && ( 'remove' === $force ) ) {
					$products_in_term = wc_get_term_product_ids( $term->term_id, $taxonomy );

					$key = array_search( $variation_id, $products_in_term, true );
					if ( false !== $key ) {
						unset( $products_in_term[ $key ] );
					}

					update_term_meta( $term->term_id, 'product_ids', $products_in_term );
					wp_remove_object_terms( $variation_id, $term->term_id, $taxonomy );
				}

				if ( $term ) {
					$this->delete_count_transient( $taxonomy, $term->term_taxonomy_id );
				}
			}
		}
	}

	/**
	 * Admin: Fired when a product's terms have been set.
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object terms.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	public function set_variation_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		$post_type = get_post_type( $object_id );

		if ( $post_type === 'product' ) {
			return;
		}

		$taxonomies = Iconic_WSSV_Product_Variation::get_taxonomies();

		if ( empty( $taxonomies ) ) {
			return;
		}

		if ( in_array( $taxonomy, $taxonomies ) ) {
			$variations = get_children(
				array(
					'post_parent' => $object_id,
					'post_type'   => 'product_variation',
				),
				ARRAY_A
			);

			if ( $variations && ! empty( $variations ) ) {
				$variation_ids = array_keys( $variations );

				foreach ( $variation_ids as $variation_id ) {
					wp_set_object_terms( $variation_id, $terms, $taxonomy, $append );
				}
			}
		}
	}

	/**
	 * Admin: Clean variation attributes
	 *
	 * @param  int $variation_id
	 */
	public function clean_variation_attributes( $variation_id ) {
		$taxonomies = get_object_taxonomies( 'product_variation', 'names' );

		if ( $taxonomies && ! empty( $taxonomies ) ) {
			$attributes = array_filter(
				$taxonomies,
				function ( $v ) {
					return substr( $v, 0, 3 ) === 'pa_';
				}
			);

			if ( ! empty( $attributes ) ) {
				foreach ( $attributes as $attribute ) {
					$terms = wp_get_object_terms( $variation_id, $attribute, array( 'fields' => 'ids' ) );
					wp_remove_object_terms( $variation_id, $terms, $attribute );
				}
			}
		}
	}

	/**
	 * Frontend: is_purchasable
	 *
	 * @param  object $product
	 *
	 * @return bool
	 */
	public function is_purchasable( $product ) {
		$purchasable = $product->is_purchasable();
		$product_id  = $product->get_id();

		if ( ! $product_id ) {
			return $purchasable;
		}

		$disable_add_to_cart = get_post_meta( $product_id, '_disable_add_to_cart', true );
		$listings_only       = Iconic_WSSV_Product_Variation::get_listings_only( $product_id );

		if ( $disable_add_to_cart || $listings_only ) {
			$purchasable = false;
		} else {
			$variation_data = wc_get_product_variation_attributes( $product_id );

			if ( empty( $variation_data ) ) {
				return $purchasable;
			}

			foreach ( $variation_data as $value ) {
				if ( ! empty( $value ) ) {
					continue;
				}

				$purchasable = false;
			}
		}

		return $purchasable;
	}

	/**
	 * Frontend: Add to Cart Text
	 *
	 * @param  string $text
	 * @param  object $product
	 *
	 * @return string
	 */
	public function add_to_cart_text( $text, $product = false ) {
		if ( ! $product ) {
			global $product;
		}

		if ( $product->get_type() !== 'variation' ) {
			return $text;
		}

		if ( ! $this->is_purchasable( $product ) || ! $product->is_in_stock() ) {
			$text = __( 'Select options', 'woocommerce' );
		}

		return $text;
	}

	/**
	 * Frontend: Add to Cart URL
	 *
	 * @param  string $url
	 * @param  object $product
	 *
	 * @return string
	 */
	public function add_to_cart_url( $url, $product ) {
		$product_type = method_exists( $product, 'get_type' ) ? $product->get_type() : $product->product_type;

		if ( $product->get_id() && $product_type === 'variation' ) {
			$url = $this->is_purchasable( $product ) && $product->is_in_stock() ? $url : $this->get_variation_url( $product );
		}

		return $url;
	}

	/**    =============================
	 *
	 * Get Woo Version Number
	 *
	 * @return mixed bool/str NULL or Woo version number
	 */
	public function get_woo_version_number() {
		// If get_plugins() isn't available, require it
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Create the plugins folder and file variables
		$plugin_folder = get_plugins( '/' . 'woocommerce' );
		$plugin_file   = 'woocommerce.php';

		// If the plugin version number is set, return it
		if ( isset( $plugin_folder[ $plugin_file ]['Version'] ) ) {
			return $plugin_folder[ $plugin_file ]['Version'];
		} else {
			// Otherwise return null
			return null;
		}
	}

	/**
	 * Admin: When the order status changes
	 *
	 * @param int $order_id
	 * @param str $old_status
	 * @param str $new_status
	 */
	public function order_status_changed( $order_id, $old_status, $new_status ) {
		$accepted_status = array( 'completed', 'processing', 'on-hold' );

		if ( in_array( $new_status, $accepted_status ) ) {
			$this->record_variation_sales( $order_id );
		}
	}

	/**
	 * Admin: When an Admin manually creates an order
	 *
	 * @param int $post_id
	 * @param obj $post
	 */
	public function process_shop_order( $post_id, $post ) {
		$accepted_status = array( 'wc-completed', 'wc-processing', 'wc-on-hold' );

		if ( in_array( $post->post_status, $accepted_status ) ) {
			$this->record_variation_sales( $post_id );
		}
	}

	/**
	 * Helper: Record variaiton sales
	 *
	 * Updates the variation sales count for an order
	 *
	 * @param int $order_id
	 */
	public function record_variation_sales( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( empty( $order ) ) {
			return;
		}

		if ( 'yes' === $order->get_meta( '_recorded_variation_sales' ) ) {
			return;
		}

		if ( sizeof( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item ) {
				if ( $item['variation_id'] > 0 ) {
					$sales  = (int) get_post_meta( $item['variation_id'], 'total_sales', true );
					$sales += (int) $item['qty'];
					if ( $sales ) {
						update_post_meta( $item['variation_id'], 'total_sales', $sales );
					}
				}
			}
		}

		$order->update_meta_data( '_recorded_variation_sales', 'yes' );
		$order->save();

		/**
		 * Called when sales for an order are recorded
		 *
		 * @param int $order_id order id
		 */
		do_action( 'woocommerce_recorded_variation_sales', $order_id );
	}

	/**
	 * Delete term counts transient
	 *
	 * When recount terms is run in backend of woo,
	 * delete our additional term counts transient, too.
	 */
	public function delete_term_counts_transient() {
		delete_transient( 'jck_wssv_term_counts' );
	}

	/**
	 * Helper: Get current view
	 *
	 * @return str
	 */
	public function get_current_view() {
		if ( is_search() ) {
			return 'search';
		}

		if ( is_filtered() ) {
			return 'filtered';
		}

		return 'catalog';
	}

	/**
	 * Frontend: Taxonomies to change term counts for
	 *
	 * @param arr $taxonomies
	 *
	 * @return arr
	 */
	public function term_count_taxonomies( $taxonomies ) {
		$attributes = wc_get_attribute_taxonomies();

		if ( $attributes && ! empty( $attributes ) ) {
			foreach ( $attributes as $attribute ) {
				$taxonomies[] = sprintf( 'pa_%s', $attribute->attribute_name );
			}
		}

		return $taxonomies;
	}

	/**
	 * Admin: On product save
	 *
	 * @param int $post_id
	 */
	public function on_product_save( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( $post_type != 'product' ) {
			return;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$this->update_children( $post_id );
		$this->delete_term_counts_transient();
	}

	/**
	 * Update children.
	 *
	 * @param int $post_id
	 */
	public function update_children( $post_id ) {
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return;
		}

		if ( ! $product->is_type( 'variable' ) ) {
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
	}

	/**
	 * Add hooks on variation save.
	 *
	 * @return void
	 */
	public function add_hooks_on_variation_save() {
		add_action( 'woocommerce_save_product_variation', [ self::instance(), 'on_variation_save' ], 100, 2 );
		add_action( 'woocommerce_new_product_variation', [ self::instance(), 'on_variation_save' ], 10 );
		add_action( 'woocommerce_update_product_variation', [ self::instance(), 'on_variation_save' ], 10 );
	}

	/**
	 * Remove hooks on variation save.
	 *
	 * @return void
	 */
	public function remove_hooks_on_variation_save() {
		remove_action( 'woocommerce_save_product_variation', [ self::instance(), 'on_variation_save' ], 100 );
		remove_action( 'woocommerce_new_product_variation', [ self::instance(), 'on_variation_save' ], 10 );
		remove_action( 'woocommerce_update_product_variation', [ self::instance(), 'on_variation_save' ], 10 );
	}

	/**
	 * Admin: On variation save
	 *
	 * @param int      $variation_id
	 * @param int|bool $i
	 */
	public function on_variation_save( $variation_id, $i = false ) {
		$action = filter_input( INPUT_POST, 'action' );

		if ( $action === 'woocommerce_save_variations' && $i === false ) {
			return;
		}

		/**
		 * Filter the actions to skip on variation save.
		 *
		 * Some actions have to be skipped otherwise it can
		 * remove the variation taxonomy meta data.
		 *
		 * @since 1.23.0
		 * @hook iconic_wssv_actions_to_skip_on_variation_save
		 * @param  array  $actions_to_skip The actions to skip.
		 * @param  string $action          The current action from $_POST['action'].
		 * @param  int    $variation_id    The variation ID.
		 * @return array New value
		 */
		$actions_to_skip = apply_filters( 'iconic_wssv_actions_to_skip_on_variation_save', $this->get_actions_to_skip(), $action, $variation_id );

		if ( in_array( $action, $actions_to_skip, true ) ) {
			return;
		}

		$wc_ajax = filter_input( INPUT_GET, 'wc-ajax' );

		if ( 'checkout' === $wc_ajax ) {
			return;
		}

		if ( WC()->is_rest_api_request() ) {
			return;
		}

		$post_type = false;

		if (
			! empty( $_POST['woocommerce_meta_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) &&
			! empty( $_POST['post_type'] )
		) {
			$post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );
		}

		if ( ( 'editpost' === $action && 'shop_order' === $post_type ) ) {
			return;
		}

		$this->save_product_variation( $variation_id, $i );
		Iconic_WSSV_Product_Variation::set_taxonomies( $variation_id );
		$this->add_attributes_to_variation( $variation_id, $i, 'add' );
		$this->delete_term_counts_transient();

		// Clear Widget Cache.
		$this->clear_wc_widget_cache();
	}

	/**
	 * Helper: Delete count transient
	 *
	 * @param str $taxonomy
	 * @param int $taxonomy_id
	 */
	public function delete_count_transient( $taxonomy, $taxonomy_id ) {
		$transient_name = 'wc_ln_count_' . md5( sanitize_key( $taxonomy ) . sanitize_key( $taxonomy_id ) );
		delete_transient( $transient_name );
	}

	/**
	 * Add product type (product_variation) to post class
	 *
	 * @since 1.1.0
	 *
	 * @param string[] $classes An array of post class names.
	 * @param string[] $class   An array of additional class names added to the post.
	 * @param int      $post_id The post ID.
	 *
	 * @return string[] Array of class names.
	 */
	public function product_post_class( $classes, $class = '', $post_id = '' ) {
		if ( ! $post_id || 'product_variation' !== get_post_type( $post_id ) ) {
			return $classes;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $classes;
		}

		$product_parent = wc_get_product( $product->get_parent_id() );

		if ( $product_parent ) {
			$classes = array_merge(
				$classes,
				wc_get_product_taxonomy_class( $product_parent->get_category_ids(), 'product_cat' ),
				wc_get_product_taxonomy_class( $product_parent->get_tag_ids(), 'product_tag' )
			);
		}

		if ( version_compare( $this->get_woo_version_number(), '3.0.0', '>=' ) ) {
			return $classes;
		}

		$product_type = method_exists( $product, 'get_type' ) ? $product->get_type() : $product->product_type;

		$classes[] = wc_get_loop_class();
		$classes[] = method_exists( $product, 'get_stock_status' ) ? $product->get_stock_status() : $product->stock_status;

		if ( $product->is_on_sale() ) {
			$classes[] = 'sale';
		}
		if ( $product->is_featured() ) {
			$classes[] = 'featured';
		}
		if ( $product->is_downloadable() ) {
			$classes[] = 'downloadable';
		}
		if ( $product->is_virtual() ) {
			$classes[] = 'virtual';
		}
		if ( $product->is_sold_individually() ) {
			$classes[] = 'sold-individually';
		}
		if ( $product->is_taxable() ) {
			$classes[] = 'taxable';
		}
		if ( $product->is_shipping_taxable() ) {
			$classes[] = 'shipping-taxable';
		}
		if ( $product->is_purchasable() ) {
			$classes[] = 'purchasable';
		}
		if ( $product_type ) {
			$classes[] = 'product-type-' . $product_type;
		}

		$key = array_search( 'hentry', $classes, true );
		if ( false !== $key ) {
			unset( $classes[ $key ] );
		}

		return $classes;
	}

	/**
	 * Add product_variation to price filter widget
	 *
	 * @param arr $post_types
	 *
	 * @return arr
	 */
	public function add_product_variation_to_price_filter( $post_types ) {
		$post_types[] = 'product_variation';

		return $post_types;
	}

	/**
	 * Invert number.
	 *
	 * @param int $number
	 *
	 * @return string
	 */
	public static function invert_number( $number ) {
		$decimal = 1 / $number;
		$decimal = explode( '.', $decimal );

		return $decimal[1];
	}

	/**
	 * Toggle array value.
	 *
	 * @param array $array
	 * @param mixed $value
	 *
	 * @return array
	 */
	public static function toggle_array_value( $array, $value ) {
		if ( $key = array_search( $value, $array ) !== false ) {
			unset( $array[ $key ] );

			return $array;
		}

		$array[] = $value;

		return $array;
	}

	/**
	 * Clear WC_Widget cache.
	 */
	public function clear_wc_widget_cache() {
		global $wp_widget_factory;

		// Return if widgets are empty.
		if ( empty( $wp_widget_factory->widgets ) ) {
			return;
		}

		foreach ( $wp_widget_factory->widgets as $wp_widget ) {
			// Only clear cache if it's a WooCommerce widget.
			if ( ! is_a( $wp_widget, 'WC_Widget' ) ) {
				continue;
			}

			$wp_widget->flush_widget_cache();
		}
	}

	/**
	 * Handle the start and end of scheduled sales.
	 *
	 * It prevents removing the taxonomies from variation
	 * level when the schedule sales are set by WooCommerce.
	 *
	 * @return void
	 */
	protected function handle_wc_scheduled_sales() {
		add_action( 'wc_before_products_starting_sales', [ $this, 'remove_hooks_on_variation_save' ] );
		add_action( 'wc_after_products_starting_sales', [ $this, 'add_hooks_on_variation_save' ] );

		add_action( 'wc_before_products_ending_sales', [ $this, 'remove_hooks_on_variation_save' ] );
		add_action( 'wc_after_products_ending_sales', [ $this, 'add_hooks_on_variation_save' ] );
	}

	/**
	 * Get AJAX actions to skip on variation save.
	 *
	 * @return array
	 */
	protected function get_actions_to_skip() {
		return array_merge(
			$this->get_actions_to_skip_default(),
			$this->get_actions_to_skip_settings()
		);
	}

	/**
	 * Get the default AJAX actions to skip on variation save.
	 *
	 * @return array
	 */
	protected function get_actions_to_skip_default() {
		return [
			'iconic-woothumbs_bulk_save',
			'woocommerce_bulk_edit_variations',
			'edit_order',
		];
	}

	/**
	 * Ge the AJAX actions to skip on variation save
	 * from admin settings.
	 *
	 * @return array
	 */
	protected function get_actions_to_skip_settings() {
		$actions_to_skip = $this->settings['compatibility-tab_compatibility-section_actions_to_skip_on_variation_save'] ?? null;

		if ( ! is_string( $actions_to_skip ) ) {
			return [];
		}

		$actions_to_skip = preg_split( '/\R/', $actions_to_skip );

		if ( ! is_array( $actions_to_skip ) ) {
			return [];
		}

		$actions_to_skip = array_map( 'trim', array_filter( $actions_to_skip ) );

		return $actions_to_skip;
	}
}

$GLOBALS['jck_wssv'] = Iconic_WSSV::instance();
