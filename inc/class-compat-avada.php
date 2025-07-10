<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Avada compatibility Class
 *
 * @since 1.1.13
 */
class Iconic_WSSV_Compat_Avada {
	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'show_check_add_to_cart' ), 99 );
	}

	/**
	 * Show check mark when added to cart.
	 */
	public static function show_check_add_to_cart() {
		if ( ! defined( 'AVADA_VERSION' ) || ! function_exists( 'is_woocommerce' ) ) {
			return;
		}

		$cart_items               = WC()->cart->get_cart();
		$variations_found_in_cart = wp_list_pluck( $cart_items, 'variation_id' );
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function() {
				if ( jQuery( '.products' ).length ) {
					var variationsInCart = <?php echo json_encode( array_values( array_filter( $variations_found_in_cart ) ) ); ?>;

					jQuery( "body" ).on( "click", ".add_to_cart_button.jck_wssv_add_to_cart", function() {
						var a = jQuery( this );
						a.closest( ".product, li" ).find( ".cart-loading" ).find( "i" ).removeClass( "fusion-icon-check-square-o" ).addClass( "fusion-icon-spinner" ),
							a.closest( ".product, li" ).find( ".cart-loading" ).fadeIn(),
							setTimeout( function() {
								a.closest( ".product, li" ).find( ".cart-loading" ).find( "i" ).hide().removeClass( "fusion-icon-spinner" ).addClass( "fusion-icon-check-square-o" ).fadeIn(),
									jQuery( a ).parents( ".fusion-clean-product-image-wrapper, li" ).addClass( "fusion-item-in-cart" )
							}, 2000 );
					} );

					jQuery.each( variationsInCart, function( index, variationId ) {
						var $icon = jQuery( "body" ).find( ".products .post-" + variationId + " .fusion-icon-spinner" );
						$icon.removeClass( "fusion-icon-spinner" ).removeClass( "fusion-icon-spinner" ).addClass( "fusion-icon-check-square-o" );
					} );
				}
			} );
		</script>
		<?php
	}
}
