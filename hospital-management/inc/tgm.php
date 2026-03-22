<?php

require get_template_directory() . '/inc/class-tgm-plugin-activation.php';

function hospital_management_register_recommended_plugins() {
	$plugins = array(
		array(
			'name'             => __( 'Magnify – Suggestive Search', 'hospital-management' ),
			'slug'             => 'magnify-suggestive-search',
			'source'           => '',
			'required'         => false,
			'force_activation' => false,
		),
	);
	$config = array();
	tgmpa( $plugins, $config );
}
add_action( 'tgmpa_register', 'hospital_management_register_recommended_plugins' );