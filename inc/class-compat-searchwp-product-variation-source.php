<?php
/**
 * Custom Product Variation source for SearchWP plugin.
 *
 * Since we save product variation data as metadata,
 * we need to customize some attributes fields to
 * retrieve the right value to be used by SearchWP.
 *
 * @package Iconic_WSSV
 */

/**
 * Iconic_WSSV_Compat_SearchWP_Product_Variation_Source class
 */
class Iconic_WSSV_Compat_SearchWP_Product_Variation_Source extends \SearchWP\Sources\Post {
	/**
	 * Construct
	 */
	public function __construct() {
		parent::__construct( 'product_variation' );

		$this->attributes = array_map(
			function( $attribute ) {
				if ( empty( $attribute['name'] ) || 'title' !== $attribute['name'] ) {
					return $attribute;
				}

				$attribute['data'] = function( $variation_id ) {
					return Iconic_WSSV_Product_Variation::get_title( $variation_id );
				};

				return $attribute;
			},
			$this->attributes()
		);
	}
}
