<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WSSV_Settings.
 *
 * @class    Iconic_WSSV_Settings
 * @version  1.0.0
 * @author   Iconic
 */
class Iconic_WSSV_Settings {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	/**
	 * Init.
	 */
	public static function init() {
		global $jck_wssv;

		if ( empty( $jck_wssv ) ) {
			return;
		}

		$jck_wssv->set_settings();
	}
}
