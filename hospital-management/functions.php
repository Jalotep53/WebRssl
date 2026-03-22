<?php
/**
 * Hospital Management functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Hospital Management
 */

include get_theme_file_path( 'vendor/wptrt/autoload/src/Hospital_Management_Loader.php' );

$Hospital_Management_Loader = new \WPTRT\Autoload\Hospital_Management_Loader();

$Hospital_Management_Loader->hospital_management_add( 'WPTRT\\Customize\\Section', get_theme_file_path( 'vendor/wptrt/customize-section-button/src' ) );

$Hospital_Management_Loader->hospital_management_register();

if ( ! function_exists( 'hospital_management_setup' ) ) :

	function hospital_management_setup() {

		/*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		*/
		add_theme_support( 'post-formats', array('image','video','gallery','audio',) );

		load_theme_textdomain( 'hospital-management', get_template_directory() . '/languages' );
		add_theme_support( 'woocommerce' );
		add_theme_support( "responsive-embeds" );
		add_theme_support( "align-wide" );
		add_theme_support( "wp-block-styles" );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
        add_image_size('hospital-management-featured-header-image', 2000, 660, true);

        register_nav_menus( array(
            'primary' => esc_html__( 'Primary','hospital-management' ),
	        'footer'=> esc_html__( 'Footer Menu','hospital-management' ),
        ) );

		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		add_theme_support( 'custom-background', apply_filters( 'hospital_management_custom_background_args', array(
			'default-color' => 'f7ebe5',
			'default-image' => '',
		) ) );

		add_theme_support( 'customize-selective-refresh-widgets' );

		add_theme_support( 'custom-logo', array(
			'height'      => 50,
			'width'       => 50,
			'flex-width'  => true,
		) );

		add_editor_style( array( '/editor-style.css' ) );
		add_action('wp_ajax_hospital_management_dismissable_notice', 'hospital_management_dismissable_notice');
		add_action( 'wp_ajax_tm-check-plugin-exists', 'tm_check_plugin_exists' );
		add_action( 'wp_ajax_tm_install_and_activate_plugin', 'tm_install_and_activate_plugin' );
	}
endif;
add_action( 'after_setup_theme', 'hospital_management_setup' );


function hospital_management_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'hospital_management_content_width', 1170 );
}
add_action( 'after_setup_theme', 'hospital_management_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function hospital_management_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'hospital-management' ),
		'id'            => 'sidebar',
		'description'   => esc_html__( 'Add widgets here.', 'hospital-management' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h5 class="widget-title">',
		'after_title'   => '</h5>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar 1', 'hospital-management' ),
		'id'            => 'sidebar1',
		'description'   => esc_html__( 'Add widgets here.', 'hospital-management' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h5 class="widget-title">',
		'after_title'   => '</h5>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar 2', 'hospital-management' ),
		'id'            => 'sidebar2',
		'description'   => esc_html__( 'Add widgets here.', 'hospital-management' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h5 class="widget-title">',
		'after_title'   => '</h5>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 1', 'hospital-management' ),
		'id'            => 'hospital-management-footer1',
		'description'   => '',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5 class="footer-column-widget-title">',
		'after_title'   => '</h5>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 2', 'hospital-management' ),
		'id'            => 'hospital-management-footer2',
		'description'   => '',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5 class="footer-column-widget-title">',
		'after_title'   => '</h5>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 3', 'hospital-management' ),
		'id'            => 'hospital-management-footer3',
		'description'   => '',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5 class="footer-column-widget-title">',
		'after_title'   => '</h5>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 4', 'hospital-management' ),
		'id'            => 'hospital-management-footer4',
		'description'   => '',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5 class="footer-column-widget-title">',
		'after_title'   => '</h5>',
	) );
}
add_action( 'widgets_init', 'hospital_management_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function hospital_management_scripts() {

	require_once get_theme_file_path( 'inc/wptt-webfont-loader.php' );

	wp_enqueue_style(
		'lato',
		wptt_get_webfont_url( 'https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet"' ),
		array(),
		'1.0'
	);

	wp_enqueue_style( 'hospital-management-block-editor-style', get_theme_file_uri('/assets/css/block-editor-style.css') );

	// load bootstrap css
    wp_enqueue_style( 'bootstrap-css', get_template_directory_uri() . '/assets/css/bootstrap.css');

    wp_enqueue_style( 'owl.carousel-css', get_template_directory_uri() . '/assets/css/owl.carousel.css');

	wp_enqueue_style( 'hospital-management-style', get_stylesheet_uri() );
	require get_parent_theme_file_path( '/custom-option.php' );
	wp_add_inline_style( 'hospital-management-style',$hospital_management_theme_css );

	// fontawesome
	wp_enqueue_style( 'fontawesome-style', get_template_directory_uri() .'/assets/css/fontawesome/css/all.css' );

    wp_enqueue_script('hospital-management-theme-js', get_template_directory_uri() . '/assets/js/theme-script.js', array('jquery'), '', true );

    wp_enqueue_script('owl.carousel-js', get_template_directory_uri() . '/assets/js/owl.carousel.js', array('jquery'), '', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'hospital_management_scripts' );

/**
 * Enqueue Preloader.
 */
function hospital_management_preloader() {

	$hospital_management_theme_color_css = '';
	$hospital_management_preloader_bg_color = get_theme_mod('hospital_management_preloader_bg_color');
	$hospital_management_preloader_dot_1_color = get_theme_mod('hospital_management_preloader_dot_1_color');
	$hospital_management_preloader_dot_2_color = get_theme_mod('hospital_management_preloader_dot_2_color');
  	$hospital_management_preloader2_dot_color = get_theme_mod('hospital_management_preloader2_dot_color');
	$hospital_management_logo_max_height = get_theme_mod('hospital_management_logo_max_height');
	$hospital_management_scroll_bg_color = get_theme_mod('hospital_management_scroll_bg_color');
	$hospital_management_scroll_color = get_theme_mod('hospital_management_scroll_color');
	$hospital_management_scroll_font_size = get_theme_mod('hospital_management_scroll_font_size');
	$hospital_management_scroll_border_radius = get_theme_mod('hospital_management_scroll_border_radius');
	$hospital_management_related_product_display_setting = get_theme_mod('hospital_management_related_product_display_setting', true);

  	if(get_theme_mod('hospital_management_logo_max_height') == '') {
		$hospital_management_logo_max_height = '80';
	}

	if(get_theme_mod('hospital_management_preloader_dot_1_color') == '') {
		$hospital_management_preloader_dot_1_color = '#ffffff';
	}
	if(get_theme_mod('hospital_management_preloader_dot_2_color') == '') {
		$hospital_management_preloader_dot_2_color = '#3F3C6C';
	}

	// Start CSS build
	$hospital_management_theme_color_css = '';

	
	if (!$hospital_management_related_product_display_setting) {
	    $hospital_management_theme_color_css .= '
	        .related.products,
	        .related h2 {
	            display: none !important;
	        }
	    ';
	}

	$hospital_management_theme_color_css .= '
		.custom-logo-link img{
			max-height: '.esc_attr($hospital_management_logo_max_height).'px;
	 	}
		.loading, .loading2{
			background-color: '.esc_attr($hospital_management_preloader_bg_color).';
		 }
		 @keyframes loading {
		  0%,
		  100% {
		  	transform: translatey(-2.5rem);
		    background-color: '.esc_attr($hospital_management_preloader_dot_1_color).';
		  }
		  50% {
		  	transform: translatey(2.5rem);
		    background-color: '.esc_attr($hospital_management_preloader_dot_2_color).';
		  }
		}
		.load hr {
			background-color: '.esc_attr($hospital_management_preloader2_dot_color).';
		}
		a#button{
			background-color: '.esc_attr($hospital_management_scroll_bg_color).';
			color: '.esc_attr($hospital_management_scroll_color).' !important;
			font-size: '.esc_attr($hospital_management_scroll_font_size).'px;
			border-radius: '.esc_attr($hospital_management_scroll_border_radius).'%;
		}
	';
    wp_add_inline_style( 'hospital-management-style',$hospital_management_theme_color_css );

}
add_action( 'wp_enqueue_scripts', 'hospital_management_preloader' );

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * TGM
 */
require get_template_directory() . '/inc/tgm.php';

/** * Posts pagination. */
if ( ! function_exists( 'hospital_management_blog_posts_pagination' ) ) {
	function hospital_management_blog_posts_pagination() {
		$pagination_type = get_theme_mod( 'hospital_management_blog_pagination_type', 'blog-nav-numbers' );
		if ( $pagination_type == 'blog-nav-numbers' ) {
			the_posts_pagination();
		} else {
			the_posts_navigation();
		}
	}
}


function hospital_management_sanitize_select( $input, $setting ){
    $input = sanitize_key($input);
    $choices = $setting->manager->get_control( $setting->id )->choices;
    return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
}

function hospital_management_preloader1(){
	if(get_theme_mod('hospital_management_preloader_type', 'Preloader 1') == 'Preloader 1' ) {
		return true;
	}
	return false;
}

function hospital_management_preloader2(){
	if(get_theme_mod('hospital_management_preloader_type', 'Preloader 1') == 'Preloader 2' ) {
		return true;
	}
	return false;
}

/*dropdown page sanitization*/
function hospital_management_sanitize_dropdown_pages( $page_id, $setting ) {
	$page_id = absint( $page_id );
	return ( 'publish' == get_post_status( $page_id ) ? $page_id : $setting->default );
}

function hospital_management_sanitize_checkbox( $input ) {
  // Boolean check
  return ( ( isset( $input ) && true == $input ) ? true : false );
}

/*radio button sanitization*/
function hospital_management_sanitize_choices( $input, $setting ) {
    global $wp_customize;
    $control = $wp_customize->get_control( $setting->id );
    if ( array_key_exists( $input, $control->choices ) ) {
        return $input;
    } else {
        return $setting->default;
    }
}

function hospital_management_sanitize_number_absint( $number, $setting ) {
	// Ensure $number is an absolute integer (whole number, zero or greater).
	$number = absint( $number );

	// If the input is an absolute integer, return it; otherwise, return the default
	return ( $number ? $number : $setting->default );
}

function hospital_management_sanitize_number_range( $number, $setting ) {
	
	// Ensure input is an absolute integer.
	$number = absint( $number );
	
	// Get the input attributes associated with the setting.
	$atts = $setting->manager->get_control( $setting->id )->input_attrs;
	
	// Get minimum number in the range.
	$min = ( isset( $atts['min'] ) ? $atts['min'] : $number );
	
	// Get maximum number in the range.
	$max = ( isset( $atts['max'] ) ? $atts['max'] : $number );
	
	// Get step.
	$step = ( isset( $atts['step'] ) ? $atts['step'] : 1 );
	
	// If the number is within the valid range, return it; otherwise, return the default
	return ( $min <= $number && $number <= $max && is_int( $number / $step ) ? $number : $setting->default );
}


if ( class_exists( 'WP_Customize_Control' ) ) {
	// Image Toggle Radio Buttpon
	class Hospital_Management_Image_Radio_Control extends WP_Customize_Control {

	    public function render_content() {
	 
	        if (empty($this->choices))
	            return;
	 
	        $name = '_customize-radio-' . $this->id;
	        ?>
	        <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
	        <ul class="controls" id='hospital-management-img-container'>
	            <?php
	            foreach ($this->choices as $value => $label) :
	                $class = ($this->value() == $value) ? 'hospital-management-radio-img-selected hospital-management-radio-img-img' : 'hospital-management-radio-img-img';
	                ?>
	                <li style="display: inline;">
	                    <label>
	                        <input <?php $this->link(); ?>style = 'display:none' type="radio" value="<?php echo esc_attr($value); ?>" name="<?php echo esc_attr($name); ?>" <?php
	                          	$this->link();
	                          	checked($this->value(), $value);
	                          	?> />
	                        <img src='<?php echo esc_url($label); ?>' class='<?php echo esc_attr($class); ?>' />
	                    </label>
	                </li>
	                <?php
	            endforeach;
	            ?>
	        </ul>
	        <?php
	    } 
	}
}

/**
 * Enqueue S Header.
 */
function hospital_management_sticky_header() {

	$hospital_management_sticky_header = get_theme_mod('hospital_management_sticky_header');

	$hospital_management_custom_style= "";

	if($hospital_management_sticky_header != true){

		$hospital_management_custom_style .='.stick_header{';

			$hospital_management_custom_style .='position: static;';

		$hospital_management_custom_style .='}';
	}

	wp_add_inline_style( 'hospital-management-style',$hospital_management_custom_style );

}
add_action( 'wp_enqueue_scripts', 'hospital_management_sticky_header' );

// Change number or products per row to 3
add_filter('loop_shop_columns', 'hospital_management_loop_columns');
if (!function_exists('hospital_management_loop_columns')) {
	function hospital_management_loop_columns() {
		$columns = get_theme_mod( 'hospital_management_products_per_row', 3 );
		return $columns; // 3 products per row
	}
}

//Change number of products that are displayed per page (shop page)
add_filter( 'loop_shop_per_page', 'hospital_management_shop_per_page', 9 );
function hospital_management_shop_per_page( $cols ) {
  	$cols = get_theme_mod( 'hospital_management_product_per_page', 9 );
	return $cols;
}

// Filter to change the number of related products displayed
add_filter( 'woocommerce_output_related_products_args', 'hospital_management_products_args' );
function hospital_management_products_args( $args ) {
    $args['posts_per_page'] = get_theme_mod( 'custom_related_products_number', 6 );
    $args['columns'] = get_theme_mod( 'custom_related_products_number_per_row', 3 );
    return $args;
}

function hospital_management_get_page_id_by_title($hospital_management_pagename){

    $args = array(
        'post_type' => 'page',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'title' => $hospital_management_pagename
    );
    $query = new WP_Query( $args );

    $page_id = '1';
    if (isset($query->post->ID)) {
        $page_id = $query->post->ID;
    }

    return $page_id;
}

function hospital_management_remove_customize_register() {
    global $wp_customize;

    $wp_customize->remove_setting( 'pro_version_footer' );
    $wp_customize->remove_control( 'pro_version_footer' );

}
add_action( 'customize_register', 'hospital_management_remove_customize_register', 11 );

if ( ! function_exists( 'hospital_management_file_setup' ) ) :

	function hospital_management_file_setup() {

		/**
		 * Customizer additions.
		 */
		require get_template_directory() . '/inc/customizer.php';

		if ( ! defined( 'HOSPITAL_MANAGEMENT_CONTACT_SUPPORT' ) ) {

			define( 'FREE_MNSSP_API_URL', 'https://license.themagnifico.net/api/general/' );

			define('HOSPITAL_MANAGEMENT_CONTACT_SUPPORT',__('https://wordpress.org/support/theme/hospital-management/','hospital-management'));
		}
		if ( ! defined( 'HOSPITAL_MANAGEMENT_REVIEW' ) ) {
			define('HOSPITAL_MANAGEMENT_REVIEW',__('https://wordpress.org/support/theme/hospital-management/reviews/','hospital-management'));
		}
		if ( ! defined( 'HOSPITAL_MANAGEMENT_LIVE_DEMO' ) ) {
			define('HOSPITAL_MANAGEMENT_LIVE_DEMO',__('https://demo.themagnifico.net/hospital-management/','hospital-management'));
		}
		if ( ! defined( 'HOSPITAL_MANAGEMENT_GET_PREMIUM_PRO' ) ) {
			define('HOSPITAL_MANAGEMENT_GET_PREMIUM_PRO',__('https://www.themagnifico.net/products/hospital-management-wordpress-theme','hospital-management'));
		}
		if ( ! defined( 'HOSPITAL_MANAGEMENT_PRO_DOC' ) ) {
			define('HOSPITAL_MANAGEMENT_PRO_DOC',__('https://demo.themagnifico.net/eard/wathiqa/hospital-management-pro-doc/','hospital-management'));
		}
		if ( ! defined( 'HOSPITAL_MANAGEMENT_FREE_DOC' ) ) {
			define('HOSPITAL_MANAGEMENT_FREE_DOC',__('https://demo.themagnifico.net/eard/wathiqa/hospital-management-free-doc/','hospital-management'));
		}
		if ( ! defined( 'HOSPITAL_MANAGEMENT_BUNDLE_LINK' ) ) {
			define('HOSPITAL_MANAGEMENT_BUNDLE_LINK',__('https://www.themagnifico.net/products/wordpress-theme-bundle','hospital-management'));
		}

	}
endif;
add_action( 'after_setup_theme', 'hospital_management_file_setup' );

function tm_install_and_activate_plugin() {

	check_ajax_referer( 'theme_importer_nonce' );

	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( 'Permission denied.' );
		return;
	}

	$post_plugin_details = isset( $_POST['plugin_details'] ) ? $_POST['plugin_details'] : array();
	$plugin_text_domain = isset( $post_plugin_details['plugin_text_domain'] ) ? sanitize_key( wp_unslash( $post_plugin_details['plugin_text_domain'] ) ) : '';
	$plugin_main_file		=	isset( $post_plugin_details['plugin_main_file'] ) ? sanitize_file_name( wp_unslash( $post_plugin_details['plugin_main_file'] ) ) : '';
	$plugin_url					=	isset( $post_plugin_details['plugin_url'] ) ? esc_url_raw( wp_unslash( $post_plugin_details['plugin_url'] ) ) : '';

	$plugin = array(
		'text_domain'	=> $plugin_text_domain,
		'path' 				=> $plugin_url,
		'install' 		=> $plugin_text_domain . '/' . $plugin_main_file
	);

	wp_cache_flush();

	$plugin_path = plugin_basename( trim( $plugin['install'] ) );


	$activate_plugin = activate_plugin( $plugin_path );

	if($activate_plugin) {

		echo $activate_plugin;

	} else {
		echo $activate_plugin;
	}

	$msg = 'installed';

	$response = array( 'status' => true, 'msg' => $msg );
	wp_send_json( $response );
	exit;
}

function tm_check_plugin_exists() {

		check_ajax_referer( 'theme_importer_nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( 'Permission denied.' );
			return;
		}

		$plugin_text_domain = isset( $_POST['plugin_text_domain'] ) ? sanitize_key( wp_unslash( $_POST['plugin_text_domain'] ) ) : '';
		$main_plugin_file 	= isset( $_POST['main_plugin_file'] ) ? sanitize_file_name( wp_unslash( $_POST['main_plugin_file'] ) ) : '';
		$plugin_path = $plugin_text_domain . '/' . $main_plugin_file;

		$get_plugins					= get_plugins();
		$is_plugin_installed	= false;
		$activation_status 		= false;
		if ( isset( $get_plugins[$plugin_path] ) ) {
		$is_plugin_installed = true;

		$activation_status = is_plugin_active( $plugin_path );
		}
		wp_send_json_success(
		array(
		'install_status'  =>	$is_plugin_installed,
			'active_status'		=>	$activation_status,
			'plugin_path'			=>	$plugin_path,
			'plugin_slug'			=>	$plugin_text_domain
		)
		);
}

/**
 * Get CSS
 */

function hospital_management_getpage_css($hook) {
	wp_register_script( 'admin-notice-script', get_template_directory_uri() . '/inc/admin/js/admin-notice-script.js', array( 'jquery' ) );
    wp_localize_script('admin-notice-script','hospital_management',
		array('admin_ajax'	=>	admin_url('admin-ajax.php'),'wpnonce'  =>	wp_create_nonce('hospital_management_dismissed_notice_nonce')
		)
	);
	wp_enqueue_script('admin-notice-script');

    wp_localize_script( 'admin-notice-script', 'hospital_management_ajax_object',
        array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
    );
	if ( 'appearance_page_hospital-management-info' != $hook ) {
		return;
	}
}
add_action( 'admin_enqueue_scripts', 'hospital_management_getpage_css' );

//Admin Notice For Getstart
function hospital_management_ajax_notice_handler() {
    check_ajax_referer( 'hospital_management_dismissed_notice_nonce', 'wpnonce' );
    if ( isset( $_POST['type'] ) ) {
        $type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
        update_option( 'dismissed-' . $type, TRUE );
    }
    wp_die();
}
add_action( 'wp_ajax_hospital_management_dismissed_notice_handler', 'hospital_management_ajax_notice_handler' );

function hospital_management_deprecated_hook_admin_notice() {

    $dismissed = get_user_meta(get_current_user_id(), 'hospital_management_dismissable_notice', true);
    if ( !$dismissed) { ?>
        <div class="updated notice notice-success is-dismissible notice-get-started-class" data-notice="get_started" style="background: #f7f9f9; padding: 20px 10px; display: flex;">
	    	<div class="tm-admin-image">
	    		<img style="width: 100%;max-width: 320px;line-height: 40px;display: inline-block;vertical-align: top;border: 2px solid #ddd;border-radius: 4px;" src="<?php echo esc_url(get_stylesheet_directory_uri()) .'/screenshot.png'; ?>" />
	    	</div>
	    	<div class="tm-admin-content" style="padding-left: 30px; align-self: center">
	    		<h2 style="font-weight: 600;line-height: 1.3; margin: 0px;"><?php esc_html_e('Thank You For Choosing ', 'hospital-management'); ?><?php echo esc_html( wp_get_theme() ); ?></h2>
	    		<p style="color: #3c434a; font-weight: 400; margin-bottom: 30px;"><?php esc_html_e('Get Started With Theme By Clicking On Getting Started.', 'hospital-management'); ?></p>
    			<a class="admin-notice-btn button button-primary button-hero" href="<?php echo esc_url( admin_url( 'themes.php?page=hospital-management-info' )); ?>"><?php esc_html_e( 'Get started', 'hospital-management' ) ?></a>
	        	<a class="admin-notice-btn button button-primary button-hero notice-pro-btn" target="_blank" href="<?php echo esc_url( HOSPITAL_MANAGEMENT_GET_PREMIUM_PRO ); ?>"><?php esc_html_e( 'Get Premium ', 'hospital-management' ) ?></a>
	        	<a class="admin-notice-btn button button-primary button-hero notice-bundle-btn" target="_blank" href="<?php echo esc_url( HOSPITAL_MANAGEMENT_BUNDLE_LINK ); ?>"><?php esc_html_e( 'Buy All Themes - 120+ Templates', 'hospital-management' ) ?></a>
	        	<span style="padding-top: 15px; display: inline-block; padding-left: 8px;">
	        	<span style="padding-top: 15px; display: inline-block; padding-left: 8px;">
	        	<span class="dashicons dashicons-admin-links"></span>
	        	<a class="admin-notice-btn"	 target="_blank" href="<?php echo esc_url( HOSPITAL_MANAGEMENT_LIVE_DEMO ); ?>"><?php esc_html_e( 'View Demo', 'hospital-management' ) ?></a>
	        	</span>
	    	</div>
        </div>
    <?php }
}

add_action( 'admin_notices', 'hospital_management_deprecated_hook_admin_notice' );

function hospital_management_switch_theme() {
    delete_user_meta(get_current_user_id(), 'hospital_management_dismissable_notice');
}
add_action('after_switch_theme', 'hospital_management_switch_theme');
function hospital_management_dismissable_notice() {
    check_ajax_referer( 'hospital_management_dismissed_notice_nonce', 'wpnonce' );
    update_user_meta(get_current_user_id(), 'hospital_management_dismissable_notice', true);
    wp_die();
}
// Demo Content Code

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu page to trigger theme import
add_action('admin_menu', 'demo_importer_admin_page');

function demo_importer_admin_page() {
    add_theme_page(
        'Demo Theme Importer',     // Page title
        'Theme Importer',          // Menu title
        'manage_options',          // Capability
        'theme-importer',          // Menu slug
        'demo_importer_page',      // Callback function
    );
}

// Display the page content with the button
function demo_importer_page() {
    ?>
    <div class="wrap-main-box">
        <div class="main-box">
            <h2><?php echo esc_html('Welcome to Camping Zone','hospital-management'); ?></h2>
            <h3><?php echo esc_html('Create your website in just one click','hospital-management'); ?></h3>
            <hr>
            <p><?php echo esc_html('The "Begin Installation" helps you quickly set up your website by importing sample content that mirrors the demo version of the theme. This tool provides you with a ready-made layout and structure, so you can easily see how your site will look and start customizing it right away. It\'s a straightforward way to get your site up and running with minimal effort.','hospital-management'); ?></p>
            <p><?php echo esc_html('Click the button below to install the demo content.','hospital-management'); ?></p>
            <hr>
            <button id="import-theme-mods" class="button button-primary"><?php echo esc_html('Begin Installation','hospital-management'); ?></button>
            <div id="import-response"></div>
        </div>
    </div>
    <?php
}

// Add the AJAX action to trigger theme mods import
add_action('wp_ajax_import_theme_mods', 'demo_importer_ajax_handler');

// Handle the AJAX request
function demo_importer_ajax_handler() {

    check_ajax_referer( 'theme_importer_nonce' );

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_send_json_error( 'Permission denied.' );
        return;
    }

    // Sample data to import
    $theme_mods_data = array(
        'header_textcolor' => '000000',  // Example: change header text color
        'background_color' => 'ffffff',  // Example: change background color
        'custom_logo'      => 123,       // Example: set a custom logo by attachment ID
        'footer_text'      => 'Custom Footer Text', // Example: custom footer text
    );

    // Call the function to import theme mods
    if (demo_theme_importer($theme_mods_data)) {
        // After importing theme mods, create the menu
        create_demo_menu();
        wp_send_json_success(array(
        	'msg' => 'Theme mods and widgets imported successfully.',
        	'redirect' => home_url()
        ));
    } else {
        wp_send_json_error('Failed to import theme mods.');
    }

    wp_die();
}

// Function to set theme mods
function demo_theme_importer($import_data) {
    if (is_array($import_data)) {
        foreach ($import_data as $mod_name => $mod_value) {
            set_theme_mod($mod_name, $mod_value);
        }
        return true;
    } else {
        return false;
    }
}

// Function to create demo menu
function create_demo_menu() {

    // Page import process
    $menu_structure = [

        [
            'title' => 'Home',
            'slug'  => 'home',
            'template' => 'page-template/home-template.php',
        ],
        [
            'title' => 'About',
            'slug'  => 'about',
            'content' => "
                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum. It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</p>
            "
        ],
        [
            'title' => 'Doctors',
            'slug'  => 'doctors',
            'content' => "
                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum. It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</p>
            "
        ],
        [
            'title' => 'Services',
            'slug'  => 'services',
            'content' => "
                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum. It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</p>
            "
        ],
        [
            'title' => 'Blog',
            'slug'  => 'blog',
        ],
        [
            'title' => 'Pages',
            'slug'  => 'pages',
        ],
    ];

    // ----------------------------------------------------
    // Do not modify below this line unless needed
    // ----------------------------------------------------

    $created_pages = [];

    $menu_name = 'Primary Menu';
    $location  = 'primary';
    $menu = wp_get_nav_menu_object($menu_name);

    $menu_id = (!$menu) ? wp_create_nav_menu($menu_name) : $menu->term_id;

    $create_page_and_menu = function($item, $parent_menu_id = 0, $parent_page_id = 0) 
        use (&$create_page_and_menu, &$created_pages, $menu_id) {

        $pages = get_posts( array( 'post_type' => 'page', 'title' => $item['title'], 'post_status' => 'all', 'numberposts' => 1 ) );
        $page = ! empty( $pages ) ? $pages[0] : null;

        if (!$page) {
            $page_id = wp_insert_post([
                'post_title'   => $item['title'],
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_name'    => $item['slug'],
                'post_parent'  => $parent_page_id,
                'post_content' => !empty($item['content']) ? $item['content'] : '',
            ]);

            if (!empty($item['template'])) {
                update_post_meta($page_id, '_wp_page_template', $item['template']);
            }

        } else {
            $page_id = $page->ID;
        }

        $created_pages[$item['title']] = $page_id;

        $menu_item_id = wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'     => $item['title'],
            'menu-item-object'    => 'page',
            'menu-item-object-id' => $page_id,
            'menu-item-type'      => 'post_type',
            'menu-item-parent-id' => $parent_menu_id,
            'menu-item-status'    => 'publish'
        ]);

        if (!empty($item['children'])) {
            foreach ($item['children'] as $child) {
                $create_page_and_menu($child, $menu_item_id, $page_id);
            }
        }
    };

    foreach ($menu_structure as $item) {
        $create_page_and_menu($item);
    }

    if (!empty($created_pages['Home'])) {
        update_option('page_on_front', $created_pages['Home']);
        update_option('show_on_front', 'page');
    }

    if (!empty($created_pages['Blog'])) {
        update_option('page_for_posts', $created_pages['Blog']);
    }

    $locations = get_theme_mod('nav_menu_locations');
    $locations[$location] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
    
    //Top Bar
    set_theme_mod( 'hospital_management_facebook_icon', 'fab fa-facebook-f' );
    set_theme_mod( 'hospital_management_facebook_url', 'www.facebook.com' );
    set_theme_mod( 'hospital_management_twitter_icon', 'fab fa-twitter' );
    set_theme_mod( 'hospital_management_twitter_url', 'www.twitter.com' );
    set_theme_mod( 'hospital_management_intagram_icon', 'fab fa-instagram' );
    set_theme_mod( 'hospital_management_intagram_url', 'www.instagram.com' );
    set_theme_mod( 'hospital_management_linkedin_icon', 'fab fa-linkedin-in' );
    set_theme_mod( 'hospital_management_linkedin_url', 'www.linkedin.com' );
    set_theme_mod( 'hospital_management_pintrest_icon', 'fab fa-pinterest-p' );
    set_theme_mod( 'hospital_management_pintrest_url', 'www.pinterest.com' );

    //Top Bar
    set_theme_mod( 'hospital_management_topbar_phone_text', '+1-123-556-5523' );
    set_theme_mod( 'hospital_management_topbar_mail_text', 'support@example.com' );
    set_theme_mod( 'hospital_management_search_setting', true );
    set_theme_mod( 'hospital_management_header_sidebar_icon', true );

    //Slider
    for($i=1;$i<=3;$i++){
        $title = 'Your Partner In Health And Wellness';
        $content = 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using';
            // Create post object
        $my_post = array(
         	'post_title'    => wp_strip_all_tags( $title ),
         	'post_content'  => $content,
         	'post_status'   => 'publish',
         	'post_type'     => 'page',
        );

         // Insert the post into the database
         $post_id = wp_insert_post( $my_post );

         if ($post_id) {
	        // Set the theme mod for the slider page
	        set_theme_mod('hospital_management_top_slider_page' . $i, $post_id);

	         $image_url = get_template_directory_uri().'/assets/img/slider-img.png';

			$image_id = media_sideload_image($image_url, $post_id, null, 'id');

		        if (!is_wp_error($image_id)) {
		            // Set the downloaded image as the post's featured image
		            set_post_thumbnail($post_id, $image_id);
		        }
      	}
    }

    //About Us
    set_theme_mod( 'hospital_management_services_heading', 'Our Healthcare Service' );
    set_theme_mod( 'hospital_management_services_content', 'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout.' );
    set_theme_mod( 'hospital_management_services_icon1', 'fas fa-stethoscope' );
    set_theme_mod( 'hospital_management_services_icon2', 'fas fa-ambulance' );
    set_theme_mod( 'hospital_management_services_icon3', 'fas fa-baby' );
    set_theme_mod( 'hospital_management_services_icon4', 'fas fa-brain' );
    set_theme_mod( 'hospital_management_services_icon5', 'fas fa-heart' );

	// Import Categories
	$categories = [
	    ['name' => 'Services', 'slug' => 'services']
	];

	$category_ids = [];
	foreach ($categories as $category) {
	    $term = wp_insert_term($category['name'], 'category', ['slug' => $category['slug']]);
	    if (!is_wp_error($term)) {
	        $category_ids[$category['slug']] = $term['term_id'];
	    }
	}

	// Import Posts
	$posts = [
	    ['title' => 'Emergency Department', 
	    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 
	    'categories' => ['services']],
	    ['title' => 'Pediatric Department', 
    	'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 
    	'categories' => ['services']],
	    ['title' => 'General Physician', 
	    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 
	    'categories' => ['services']],
	    ['title' => 'Neurology Department', 
	    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 
	    'categories' => ['services']],
	    ['title' => 'Cardiology Department', 
	    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 
	    'categories' => ['services']],
	];

	foreach ($posts as $post) {
	    $post_categories = array_map(function ($slug) use ($category_ids) {
	        return $category_ids[$slug];
	    }, $post['categories']);
	    
	    $post_id = wp_insert_post([
	        'post_title'    => $post['title'],
	        'post_content'  => $post['content'],
	        'post_status'   => 'publish',
	        'post_category' => $post_categories,
	    ]);
	}

    set_theme_mod( 'hospital_management_services_sec_category', 'services' );
}
// Enqueue necessary scripts
add_action('admin_enqueue_scripts', 'demo_importer_enqueue_scripts');

function demo_importer_enqueue_scripts() {
    wp_enqueue_script(
        'demo-theme-importer',
        get_template_directory_uri() . '/assets/js/theme-importer.js', // Path to your JS file
        array('jquery'),
        null,
        true
    );

    wp_enqueue_style('demo-importer-style', get_template_directory_uri() . '/assets/css/importer.css', array(), '');

    // Localize script to pass AJAX URL to JS
    wp_localize_script(
        'demo-theme-importer',
        'demoImporter',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('theme_importer_nonce')
        )
    );
}

/**
 * Theme Info.
 */
function hospital_management_theme_info_load() {
	require get_theme_file_path( '/inc/theme-installation/theme-installation.php' );
}
add_action( 'init', 'hospital_management_theme_info_load' );

// Getstart Function

function free_mnssp_get_filtered_products($cursor = '', $search = '', $collection = 'pro') {
    $endpoint_url = FREE_MNSSP_API_URL . 'getFilteredProducts';

    $remote_post_data = array(
        'collectionHandle' => $collection,
        'productHandle' => $search,
        'paginationParams' => array(
            "first" => 12,
            "afterCursor" => $cursor,
            "beforeCursor" => "",
            "reverse" => true
        )
    );

    $body = wp_json_encode($remote_post_data);

    $options = [
        'body' => $body,
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ];
    $response = wp_remote_post($endpoint_url, $options);

    if (!is_wp_error($response)) {
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body);

        if (isset($response_body->data) && !empty($response_body->data)) {
            if (isset($response_body->data->products) && !empty($response_body->data->products)) {
                return  array(
                    'products' => $response_body->data->products,
                    'pagination' => $response_body->data->pageInfo
                );
            }
        }
        return [];
    }
    
    return [];
}

function free_mnssp_get_filtered_products_ajax() {
    $cursor = isset($_POST['cursor']) ? sanitize_text_field(wp_unslash($_POST['cursor'])) : '';
    $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
    $collection = isset($_POST['collection']) ? sanitize_text_field(wp_unslash($_POST['collection'])) : 'pro';

    check_ajax_referer('free_mnssp_create_pagination_nonce_action', 'mnssp_pagination_nonce');

    $get_filtered_products = free_mnssp_get_filtered_products($cursor, $search, $collection);
    ob_start();
    if (isset($get_filtered_products['products']) && !empty($get_filtered_products['products'])) {
        foreach ( $get_filtered_products['products'] as $product ) {

            $product_obj = $product->node;
            
            if (isset($product_obj->inCollection) && !$product_obj->inCollection) {
                continue;
            }

            $product_obj = $product->node;

            $demo_url = isset($product->node->metafield->value) ? $product->node->metafield->value : '';
            $product_url = isset($product->node->onlineStoreUrl) ? $product->node->onlineStoreUrl : '';
            $image_src = isset($product->node->images->edges[0]->node->src) ? $product->node->images->edges[0]->node->src : '';
            $price = isset($product->node->variants->edges[0]->node->price) ? '$' . $product->node->variants->edges[0]->node->price : ''; ?>

            <div class="mnssp-grid-item">
                <div class="mnssp-image-wrap">
                    <img src="<?php echo esc_url($image_src); ?>" alt="<?php echo esc_attr($product_obj->title); ?>">
                    <div class="mnssp-image-overlay">
                        <a class="mnssp-demo-url mnssp-btn" href="<?php echo esc_attr($demo_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html('Demo'); ?></a>
                        <a class="mnssp-buy-now mnssp-btn" href="<?php echo esc_attr($product_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html('Buy Now'); ?></a>
                    </div>
                </div>
                <footer>
                    <h3><?php echo esc_html($product_obj->title); ?></h3>
                </footer>
                <div class="mnssp-grid-item-price">Price: <?php echo esc_html($price); ?></div>
            </div>
        <?php }
    }
    $output = ob_get_clean();

    $pagination = isset($get_filtered_products['pagination']) ?  $get_filtered_products['pagination'] : [];
    wp_send_json(array(
        'content' => $output,
        'pagination' => $pagination
    ));
}

add_action('wp_ajax_free_mnssp_get_filtered_products', 'free_mnssp_get_filtered_products_ajax');
add_action('wp_ajax_nopriv_free_mnssp_get_filtered_products', 'free_mnssp_get_filtered_products_ajax');

function free_mnssp_get_collections() {
    
    $endpoint_url = FREE_MNSSP_API_URL . 'getCollections';

    $options = [
        'body' => [],
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ];
    $response = wp_remote_post($endpoint_url, $options);

    if (!is_wp_error($response)) {
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body);

        if (isset($response_body->data) && !empty($response_body->data)) {
           return  $response_body->data;
        }
        return  [];
    }

    return  [];
}