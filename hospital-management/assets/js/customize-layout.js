(function( $ ) {
	wp.customize.bind( 'ready', function() {

		var optPrefix = '#customize-control-hospital_management_options-';
		
		// Label
		function hospital_management_customizer_label( id, title ) {

			// Site Identity

			if ( id === 'custom_logo' || id === 'site_icon' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Global Color Setting

			if ( id === 'hospital_management_global_color' || id === 'hospital_management_heading_color' || id === 'hospital_management_paragraph_color') {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// General Setting

			if ( id === 'hospital_management_scroll_hide' || id === 'hospital_management_preloader_hide' || id === 'hospital_management_sticky_header' || id === 'hospital_management_products_per_row' || id === 'hospital_management_width_option')  {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Colors

			if ( id === 'hospital_management_theme_color' || id === 'background_color' || id === 'background_image' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Header Image

			if ( id === 'header_image' || id === 'hospital_management_post_layout' || id === 'hospital_management_404_page_main_heading' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Social Icon

			if ( id === 'hospital_management_facebook_icon' || id === 'hospital_management_twitter_icon' || id === 'hospital_management_intagram_icon'|| id === 'hospital_management_linkedin_icon'|| id === 'hospital_management_pintrest_icon' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			//  Header

			if ( id === 'hospital_management_topbar_phone_text' || id === 'hospital_management_topbar_mail_text' || id === 'hospital_management_header_sidebar_icon' || id === 'hospital_management_search_setting') {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}


			// Slider

			if ( id === 'hospital_management_top_slider_page1' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Services

			if ( id === 'hospital_management_services_heading' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Footer

			if ( id === 'hospital_management_footer_bg_image' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Post Setting

			if ( id === 'hospital_management_post_page_title' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Single Post Setting

			if ( id === 'hospital_management_single_post_thumb' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-hospital_management_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}
			
		}

	    // Site Identity
		hospital_management_customizer_label( 'custom_logo', 'Logo Setup' );
		hospital_management_customizer_label( 'site_icon', 'Favicon' );

		// Global Color Setting
		hospital_management_customizer_label( 'hospital_management_global_color', 'Global Color' );
		hospital_management_customizer_label( 'hospital_management_heading_color', 'Heading Typography' );
		hospital_management_customizer_label( 'hospital_management_paragraph_color', 'Paragraph Typography' );

		// General Setting
		hospital_management_customizer_label( 'hospital_management_preloader_hide', 'Preloader' );
		hospital_management_customizer_label( 'hospital_management_sticky_header', 'Sticky Header' );
		hospital_management_customizer_label( 'hospital_management_scroll_hide', 'Scroll To Top' );
		hospital_management_customizer_label( 'hospital_management_products_per_row', 'woocommerce Setting' );
		hospital_management_customizer_label( 'hospital_management_width_option', 'Site Width Layouts' );

		// Colors
		hospital_management_customizer_label( 'hospital_management_theme_color', 'Theme Color' );
		hospital_management_customizer_label( 'background_color', 'Colors' );
		hospital_management_customizer_label( 'background_image', 'Image' );

		//Header Image
		hospital_management_customizer_label( 'header_image', 'Header Image' );

		hospital_management_customizer_label( 'hospital_management_post_layout', 'Post Layouts Settings' );

		hospital_management_customizer_label( 'hospital_management_404_page_main_heading', '404 Page Settings' );

		// Social Icon
		hospital_management_customizer_label( 'hospital_management_facebook_icon', 'Facebook' );
		hospital_management_customizer_label( 'hospital_management_twitter_icon', 'Twitter' );
		hospital_management_customizer_label( 'hospital_management_intagram_icon', 'Intagram' );
		hospital_management_customizer_label( 'hospital_management_linkedin_icon', 'Linkedin' );
		hospital_management_customizer_label( 'hospital_management_pintrest_icon', 'Pintrest' );

		// Header
		hospital_management_customizer_label( 'hospital_management_topbar_phone_text', 'Phone Number' );
		hospital_management_customizer_label( 'hospital_management_topbar_mail_text', 'Email Address' );
		hospital_management_customizer_label( 'hospital_management_header_sidebar_icon', 'Sidebar Icon' );
		hospital_management_customizer_label( 'hospital_management_search_setting', 'Header Search' );

		//Slider
		hospital_management_customizer_label( 'hospital_management_top_slider_page1', 'Slider' );

		//Services
		hospital_management_customizer_label( 'hospital_management_services_heading', 'Services' );

		//Footer
		hospital_management_customizer_label( 'hospital_management_footer_bg_image', 'Footer' );
	
		// Post Setting
		hospital_management_customizer_label( 'hospital_management_post_page_title', 'Post Setting' );

		//Single Post Setting
		hospital_management_customizer_label( 'hospital_management_single_post_thumb', 'Single Post Setting' );

	});

})( jQuery );
