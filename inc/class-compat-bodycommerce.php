<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Divi BodyCommerce compatibility Class.
 */
class Iconic_WSSV_Compat_BodyCommerce {
	/**
	 * Init.
	 */
	public static function init() {
		if ( ! defined( 'DE_DB_AUTHOR' ) ) {
			return;
		}

		add_filter( 'db_archive_module_args', array( __CLASS__, 'add_variations_to_bodycommerce_query' ) );
	}



	public static function add_variations_to_bodycommerce_query( $args ) {
		if ( 'product' !== $args['post_type'] ) {
			return $args;
		}

		$conditions = Iconic_WSSV_Query::get_query_conditions( $args );

		foreach ( $conditions as $query_key => $query_value ) {
			$args[ $query_key ] = $query_value;
		}

		return $args;
	}

}
