<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'wpsf_register_settings_iconic_wssv', 'iconic_wssv_settings' );

/**
 * WooCommerce Show Single variations Settings
 *
 * @param array $wpsf_settings
 *
 * @return array
 */
function iconic_wssv_settings( $wpsf_settings ) {
	$wpsf_settings['tabs']     = isset( $wpsf_settings['tabs'] ) ? $wpsf_settings['tabs'] : array();
	$wpsf_settings['sections'] = isset( $wpsf_settings['sections'] ) ? $wpsf_settings['sections'] : array();

	$wpsf_settings['tabs'][] = array(
		'id'    => 'general',
		'title' => __( 'General', 'iconic-wssv' ),
	);

	$wpsf_settings['tabs'][] = [
		'id'    => 'compatibility-tab',
		'title' => esc_html__( 'Compatibility Settings', 'iconic-wssv' ),
	];

	// General.
	$wpsf_settings['sections']['variation_settings'] = array(
		'tab_id'              => 'general',
		'section_id'          => 'variation_settings',
		'section_title'       => __( 'Variation Settings', 'iconic-wssv' ),
		'section_description' => '',
		'section_order'       => 20,
		'fields'              => array(
			array(
				'id'       => 'title_format',
				'title'    => __( 'Variation Title Format', 'iconic-wssv' ),
				'subtitle' => __( 'Determines how your variation titles are formatted by default. You can also set a custom title on a per-variation basis in the variation edit screen.', 'iconic-wssv' ),
				'type'     => 'select',
				'default'  => 'parent',
				'choices'  => array(
					'parent'    => __( 'Inherit parent title', 'iconic-wssv' ),
					'attribute' => __( 'Append variation attributes', 'iconic-wssv' ),
				),
			),
			array(
				'id'       => 'custom_order_by_popularity',
				'title'    => __( 'Use custom order by popularity', 'iconic-wssv' ),
				'subtitle' => __( 'By default, when WooCommerce sorts by popularity, product variations can appear before the parent. Enable this option to keep variations after their parent.', 'iconic-wssv' ),
				'type'     => 'checkbox',
				'default'  => '',
			),
			array(
				'id'       => 'custom_order_by_average_rating',
				'title'    => __( 'Use custom order by average rating', 'iconic-wssv' ),
				'subtitle' => __( 'By default, when WooCommerce sorts by average rating, product variations can appear before the parent. Enable this option to keep variations after their parent.', 'iconic-wssv' ),
				'type'     => 'checkbox',
				'default'  => '',
			),
		),
	);

	$wpsf_settings['sections']['advanced'] = array(
		'tab_id'              => 'general',
		'section_id'          => 'advanced',
		'section_title'       => __( 'Advanced Settings', 'iconic-wssv' ),
		'section_description' => '',
		'section_order'       => 20,
		'fields'              => array(
			array(
				'id'       => 'add_to_all_queries',
				'title'    => __( 'Add Variations To All Product Queries', 'iconic-wssv' ),
				'subtitle' => __( 'Automatically add configured variations to all product query instances in the frontend, including all custom WP_Query instances and Query Loop blocks.', 'iconic-wssv' ),
				'type'     => 'checkbox',
				'default'  => '',
			),
		),
	);

	$wpsf_settings['sections'][] = [
		'tab_id'              => 'compatibility-tab',
		'section_id'          => 'compatibility-section',
		'section_title'       => __( 'Compatibility settings', 'iconic-wssv' ),
		'section_description' => '',
		'section_order'       => 10,
		'fields'              => [
			[
				'id'       => 'actions_to_skip_on_variation_save',
				'title'    => __( 'Bypass variation saving for these AJAX events', 'iconic-wssv' ),
				'subtitle' => __( 'When a variation is saved, it is re-indexed and some data like categories and tags are synced with the parent product based on its settings. Add custom AJAX events e.g my-ajax-action to this field, one per line, to bypass re-indexing and syncing the saved products.', 'iconic-wssv' ),
				'type'     => 'textarea',
			],
		],
	];

	return $wpsf_settings;
}
