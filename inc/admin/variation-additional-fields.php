<div class="iconic-wssv-display-options">

	<strong><?php _e( 'Display Options', 'iconic-wssv' ); ?></strong>

	<div class="form-row form-row-full">
		<?php
		woocommerce_wp_text_input(
			array(
				'id'    => "jck_wssv_display_title[$loop]",
				'label' => __( 'Title', 'iconic-wssv' ),
				'type'  => 'text',
				'value' => get_post_meta( $variation->ID, '_jck_wssv_display_title', true ),
			)
		);
		?>
	</div>

</div>

<?php Iconic_WSSV_Product_Variation::product_variation_taxonomy_term_search_field( $variation->ID, 'product_cat', __( 'Categories', 'iconic-wssv' ) ); ?>

<?php Iconic_WSSV_Product_Variation::product_variation_taxonomy_term_search_field( $variation->ID, 'product_tag', __( 'Tags', 'iconic-wssv' ) ); ?>
