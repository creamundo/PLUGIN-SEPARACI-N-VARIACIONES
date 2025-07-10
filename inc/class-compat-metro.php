<?php
/**
 * Comatibility with Metro theme.
 *
 * See: https://themeforest.net/item/metro-minimal-woocommerce-wordpress-theme/24204259
 *
 * @package Iconic_WSSV
 * @since 1.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Iconic_WSSV_Compat_Metro Class.
 */
class Iconic_WSSV_Compat_Metro {

	/**
	 * Init.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'iconic_wssv_skip_order_by_menu_order_post_clauses', array( __CLASS__, 'apply_ssv_order_by_on_load_more' ) );
	}

	/**
	 * Update to use the same ORDER BY clause on the load more feature.
	 *
	 * @param bool $skip_menu_order Whether we should skip or not. Default is is_admin().
	 *
	 * @return bool.
	 */
	public static function apply_ssv_order_by_on_load_more( $skip_menu_order ) {
		$action = filter_input( INPUT_POST, 'action' );

		if ( empty( $action ) || 'rdtheme_loadmore' !== $action ) {
			return $skip_menu_order;
		}

		return false;
	}

}
