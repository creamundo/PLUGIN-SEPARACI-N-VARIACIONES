<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WSSV_Database.
 *
 * @class    Iconic_WSSV_Database
 * @version  1.0.0
 * @category Class
 * @author   Iconic
 */
class Iconic_WSSV_Database {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'plugins_loaded', array( __CLASS__, 'install' ), 100 );
	}

	/**
	 * Install databases.
	 */
	public static function install() {
		global $wpdb;

		if ( empty( self::get_tables() ) ) {
			return;
		}

		foreach ( self::get_tables() as $key => $table ) {
			$table_name          = $wpdb->prefix . $table['name'];
			$version_option_name = sprintf( 'iconic_wssv_db_%s_version', $key );
			$installed_version   = get_option( $version_option_name );

			if ( self::table_exists( $table_name ) && $installed_version >= $table['version'] ) {
				continue;
			}

			$table['schema'] = str_replace( '%%table_name%%', $table_name, $table['schema'] );

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $table['schema'] );

			update_option( $version_option_name, $table['version'] );
		}
	}

	/**
	 * Does the table exist?
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 */
	public static function table_exists( $table_name ) {
		global $wpdb;

		return $table_name === $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );
	}

	/**
	 * Get tables.
	 *
	 * @return array
	 */
	public static function get_tables() {
		return apply_filters( 'iconic_wssv_database_tables', array() );
	}
}
