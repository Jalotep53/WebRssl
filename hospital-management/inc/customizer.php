<?php
/**
 * Hospital Management Theme Customizer
 *
 * @link: https://developer.wordpress.org/themes/customize-api/customizer-objects/
 *
 * @package Hospital Management
 */

if ( ! defined( 'HOSPITAL_MANAGEMENT_URL' ) ) {
    define( 'HOSPITAL_MANAGEMENT_URL', esc_url( 'https://www.themagnifico.net/products/hospital-management-wordpress-theme', 'hospital-management') );
}
if ( ! defined( 'HOSPITAL_MANAGEMENT_TEXT' ) ) {
    define( 'HOSPITAL_MANAGEMENT_TEXT', __( 'Hospital Management Pro','hospital-management' ));
}
if ( ! defined( 'HOSPITAL_MANAGEMENT_BUY_TEXT' ) ) {
    define( 'HOSPITAL_MANAGEMENT_BUY_TEXT', __( 'Buy Hospital Management Pro','hospital-management' ));
}

use WPTRT\Customize\Section\Hospital_Management_Button;

add_action( 'customize_register', function( $manager ) {

    $manager->register_section_type( Hospital_Management_Button::class );

    $manager->add_section(
        new Hospital_Management_Button( $manager, 'hospital_management_pro', [
            'title'       => esc_html( HOSPITAL_MANAGEMENT_TEXT,'hospital-management' ),
            'priority'    => 0,
            'button_text' => __( 'GET PREMIUM', 'hospital-management' ),
            'button_url'  => esc_url( HOSPITAL_MANAGEMENT_URL )
        ] )
    );

} );

// Load the JS and CSS.
add_action( 'customize_controls_enqueue_scripts', function() {

    $version = wp_get_theme()->get( 'Version' );

    wp_enqueue_script(
        'hospital-management-customize-section-button',
        get_theme_file_uri( 'vendor/wptrt/customize-section-button/public/js/customize-controls.js' ),
        [ 'customize-controls' ],
        $version,
        true
    );

    wp_enqueue_style(
        'hospital-management-customize-section-button',
        get_theme_file_uri( 'vendor/wptrt/customize-section-button/public/css/customize-controls.css' ),
        [ 'customize-controls' ],
        $version
    );

} );

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function hospital_management_customize_register($wp_customize){

    // Pro Version
    class Hospital_Management_Customize_Pro_Version extends WP_Customize_Control {
        public $type = 'pro_options';

        public function render_content() {
            echo '<span>For More <strong>'. esc_html( $this->label ) .'</strong>?</span>';
            echo '<a href="'. esc_url($this->description) .'" target="_blank">';
                echo '<span class="dashicons dashicons-info"></span>';
                echo '<strong> '. esc_html( HOSPITAL_MANAGEMENT_BUY_TEXT,'hospital-management' ) .'<strong></a>';
            echo '</a>';
        }
    }

    // Custom Controls
    function Hospital_Management_sanitize_custom_control( $input ) {
        return $input;
    }

    $wp_customize->get_setting('blogname')->transport = 'postMessage';
    $wp_customize->get_setting('blogdescription')->transport = 'postMessage';

    $wp_customize->add_setting('hospital_management_logo_title_text', array(
        'default' => true,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_logo_title_text',array(
        'label'          => __( 'Enable Disable Title', 'hospital-management' ),
        'section'        => 'title_tagline',
        'settings'       => 'hospital_management_logo_title_text',
        'type'           => 'checkbox',
    )));

    $wp_customize->add_setting('hospital_management_theme_description', array(
        'default' => false,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_theme_description',array(
        'label'          => __( 'Enable Disable Tagline', 'hospital-management' ),
        'section'        => 'title_tagline',
        'settings'       => 'hospital_management_theme_description',
        'type'           => 'checkbox',
    )));

    //Logo
    $wp_customize->add_setting('hospital_management_logo_max_height',array(
        'default'   => '80',
        'sanitize_callback' => 'hospital_management_sanitize_number_absint'
    ));
    $wp_customize->add_control('hospital_management_logo_max_height',array(
        'label' => esc_html__('Logo Width','hospital-management'),
        'section'   => 'title_tagline',
        'type'      => 'number'
    ));

    // Pro Version
    $wp_customize->add_setting( 'pro_version_logo', array(
        'sanitize_callback' => 'Hospital_Management_sanitize_custom_control'
    ));
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,'pro_version_logo', array(
        'section'     => 'title_tagline',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
        'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
        'priority'    => 100
    )));

    // Global Color Settings
     $wp_customize->add_section('hospital_management_global_color_settings',array(
        'title' => esc_html__('Global Settings','hospital-management'),
        'priority'   => 28,
    ));

     $wp_customize->add_setting( 'hospital_management_global_color', array(
        'default' => '#0088FF',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_global_color', array(
        'description' => __('Change the global color of the theme in one click.', 'hospital-management'),
        'section' => 'hospital_management_global_color_settings',
        'settings' => 'hospital_management_global_color',
    )));

    //Typography option
    $hospital_management_font_array = array(
        ''                       => 'No Fonts',
        'Abril Fatface'          => 'Abril Fatface',
        'Acme'                   => 'Acme',
        'Anton'                  => 'Anton',
        'Architects Daughter'    => 'Architects Daughter',
        'Arimo'                  => 'Arimo',
        'Arsenal'                => 'Arsenal',
        'Arvo'                   => 'Arvo',
        'Alegreya'               => 'Alegreya',
        'Alfa Slab One'          => 'Alfa Slab One',
        'Averia Serif Libre'     => 'Averia Serif Libre',
        'Bangers'                => 'Bangers',
        'Boogaloo'               => 'Boogaloo',
        'Bad Script'             => 'Bad Script',
        'Bitter'                 => 'Bitter',
        'Bree Serif'             => 'Bree Serif',
        'BenchNine'              => 'BenchNine',
        'Cabin'                  => 'Cabin',
        'Cardo'                  => 'Cardo',
        'Courgette'              => 'Courgette',
        'Cherry Swash'           => 'Cherry Swash',
        'Cormorant Garamond'     => 'Cormorant Garamond',
        'Crimson Text'           => 'Crimson Text',
        'Cuprum'                 => 'Cuprum',
        'Cookie'                 => 'Cookie',
        'Chewy'                  => 'Chewy',
        'Days One'               => 'Days One',
        'Dosis'                  => 'Dosis',
        'Droid Sans'             => 'Droid Sans',
        'Economica'              => 'Economica',
        'Fredoka One'            => 'Fredoka One',
        'Fjalla One'             => 'Fjalla One',
        'Francois One'           => 'Francois One',
        'Frank Ruhl Libre'       => 'Frank Ruhl Libre',
        'Gloria Hallelujah'      => 'Gloria Hallelujah',
        'Great Vibes'            => 'Great Vibes',
        'Handlee'                => 'Handlee',
        'Hammersmith One'        => 'Hammersmith One',
        'Inconsolata'            => 'Inconsolata',
        'Indie Flower'           => 'Indie Flower',
        'IM Fell English SC'     => 'IM Fell English SC',
        'Julius Sans One'        => 'Julius Sans One',
        'Josefin Slab'           => 'Josefin Slab',
        'Josefin Sans'           => 'Josefin Sans',
        'Kanit'                  => 'Kanit',
        'Lobster'                => 'Lobster',
        'Lato'                   => 'Lato',
        'Lora'                   => 'Lora',
        'Libre Baskerville'      => 'Libre Baskerville',
        'Lobster Two'            => 'Lobster Two',
        'Merriweather'           => 'Merriweather',
        'Monda'                  => 'Monda',
        'Montserrat'             => 'Montserrat',
        'Muli'                   => 'Muli',
        'Marck Script'           => 'Marck Script',
        'Noto Serif'             => 'Noto Serif',
        'Open Sans'              => 'Open Sans',
        'Overpass'               => 'Overpass',
        'Overpass Mono'          => 'Overpass Mono',
        'Oxygen'                 => 'Oxygen',
        'Orbitron'               => 'Orbitron',
        'Patua One'              => 'Patua One',
        'Pacifico'               => 'Pacifico',
        'Padauk'                 => 'Padauk',
        'Playball'               => 'Playball',
        'Playfair Display'       => 'Playfair Display',
        'PT Sans'                => 'PT Sans',
        'Philosopher'            => 'Philosopher',
        'Permanent Marker'       => 'Permanent Marker',
        'Poiret One'             => 'Poiret One',
        'Quicksand'              => 'Quicksand',
        'Quattrocento Sans'      => 'Quattrocento Sans',
        'Raleway'                => 'Raleway',
        'Rubik'                  => 'Rubik',
        'Roboto'                 => 'Roboto',
        'Rokkitt'                => 'Rokkitt',
        'Russo One'              => 'Russo One',
        'Righteous'              => 'Righteous',
        'Slabo'                  => 'Slabo',
        'Source Sans Pro'        => 'Source Sans Pro',
        'Shadows Into Light Two' => 'Shadows Into Light Two',
        'Shadows Into Light'     => 'Shadows Into Light',
        'Sacramento'             => 'Sacramento',
        'Shrikhand'              => 'Shrikhand',
        'Tangerine'              => 'Tangerine',
        'Ubuntu'                 => 'Ubuntu',
        'VT323'                  => 'VT323',
        'Varela Round'           => 'Varela Round',
        'Vampiro One'            => 'Vampiro One',
        'Vollkorn'               => 'Vollkorn',
        'Volkhov'                => 'Volkhov',
        'Yanone Kaffeesatz'      => 'Yanone Kaffeesatz'
    );

    // Heading Typography
    $wp_customize->add_setting( 'hospital_management_heading_color', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_heading_color', array(
        'label' => __('Heading Color', 'hospital-management'),
        'section' => 'hospital_management_global_color_settings',
        'settings' => 'hospital_management_heading_color',
    )));

    $wp_customize->add_setting('hospital_management_heading_font_family', array(
        'default'           => '',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'hospital_management_sanitize_choices',
    ));
    $wp_customize->add_control( 'hospital_management_heading_font_family', array(
        'section' => 'hospital_management_global_color_settings',
        'label'   => __('Heading Fonts', 'hospital-management'),
        'type'    => 'select',
        'choices' => $hospital_management_font_array,
    ));

    $wp_customize->add_setting('hospital_management_heading_font_size',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_heading_font_size',array(
        'label' => esc_html__('Heading Font Size','hospital-management'),
        'section' => 'hospital_management_global_color_settings',
        'setting' => 'hospital_management_heading_font_size',
        'type'  => 'text'
    ));

    // Paragraph Typography
    $wp_customize->add_setting( 'hospital_management_paragraph_color', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_paragraph_color', array(
        'label' => __('Paragraph Color', 'hospital-management'),
        'section' => 'hospital_management_global_color_settings',
        'settings' => 'hospital_management_paragraph_color',
    )));

    $wp_customize->add_setting('hospital_management_paragraph_font_family', array(
        'default'           => '',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'hospital_management_sanitize_choices',
    ));
    $wp_customize->add_control( 'hospital_management_paragraph_font_family', array(
        'section' => 'hospital_management_global_color_settings',
        'label'   => __('Paragraph Fonts', 'hospital-management'),
        'type'    => 'select',
        'choices' => $hospital_management_font_array,
    ));

    $wp_customize->add_setting('hospital_management_paragraph_font_size',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_paragraph_font_size',array(
        'label' => esc_html__('Paragraph Font Size','hospital-management'),
        'section' => 'hospital_management_global_color_settings',
        'setting' => 'hospital_management_paragraph_font_size',
        'type'  => 'text'
    ));

    // Pro Version
    $wp_customize->add_setting( 'pro_version_color_setting', array(
        'sanitize_callback' => 'Hospital_Management_sanitize_custom_control'
    ));
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,'pro_version_color_setting', array(
        'section'     => 'hospital_management_global_color_settings',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
        'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
        'priority'    => 100
    )));

    // Post Layouts Settings
     $wp_customize->add_section('hospital_management_post_layouts_settings',array(
        'title' => esc_html__('Post Layouts Settings','hospital-management'),
        'priority'   => 30,
    ));

    $wp_customize->add_setting('hospital_management_post_layout',array(
        'default' => 'pattern_two_column_right',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control(new Hospital_Management_Image_Radio_Control($wp_customize, 'hospital_management_post_layout', array(
        'type' => 'select',
        'label' => __('Blog Post Layouts','hospital-management'),
        'section' => 'hospital_management_post_layouts_settings',
        'choices' => array(
            'pattern_one_column' => esc_url(get_template_directory_uri()).'/assets/img/1column.png',
            'pattern_two_column_right' => esc_url(get_template_directory_uri()).'/assets/img/right-sidebar.png',
            'pattern_two_column_left' => esc_url(get_template_directory_uri()).'/assets/img/left-sidebar.png',
            'pattern_three_column' => esc_url(get_template_directory_uri()).'/assets/img/3column.png',
            'pattern_four_column' => esc_url(get_template_directory_uri()).'/assets/img/4column.png',
            'pattern_grid_post' => esc_url(get_template_directory_uri()).'/assets/img/grid.png',
    ))
    ));

    // Pro Version
    $wp_customize->add_setting( 'pro_version_post_layouts_settings', array(
        'sanitize_callback' => 'hospital_management_sanitize_custom_control'
    ) );
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,
            'pro_version_post_layouts_settings', array(
                'section'     => 'hospital_management_post_layouts_settings',
                'type'        => 'pro_options',
                'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
                'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
                'priority'    => 100
            )
        )
    );

    // General Settings
     $wp_customize->add_section('hospital_management_general_settings',array(
        'title' => esc_html__('General Settings','hospital-management'),
        'priority'   => 30,
    ));

     $wp_customize->add_setting('hospital_management_width_option',array(
        'default' => 'Full Width',
        'transport' => 'refresh',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_width_option',array(
        'type' => 'select',
        'section' => 'hospital_management_general_settings',
        'choices' => array(
            'Full Width' => __('Full Width','hospital-management'),
            'Wide Width' => __('Wide Width','hospital-management'),
            'Boxed Width' => __('Boxed Width','hospital-management')
        ),
    ) );

    $wp_customize->add_setting('hospital_management_preloader_hide', array(
        'default' => 0,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_preloader_hide',array(
        'label'          => __( 'Show Theme Preloader', 'hospital-management' ),
        'section'        => 'hospital_management_general_settings',
        'settings'       => 'hospital_management_preloader_hide',
        'type'           => 'checkbox',
    )));

    $wp_customize->add_setting('hospital_management_preloader_type',array(
        'default' => 'Preloader 1',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_preloader_type',array(
        'type' => 'radio',
        'label' => esc_html__('Preloader Type','hospital-management'),
        'section' => 'hospital_management_general_settings',
        'choices' => array(
            'Preloader 1' => __('Preloader 1','hospital-management'),
            'Preloader 2' => __('Preloader 2','hospital-management'),
        ),
    ) );

    $wp_customize->add_setting( 'hospital_management_preloader_bg_color', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_preloader_bg_color', array(
        'label' => esc_html__('Preloader Background Color','hospital-management'),
        'section' => 'hospital_management_general_settings',
        'settings' => 'hospital_management_preloader_bg_color'
    )));

    $wp_customize->add_setting( 'hospital_management_preloader_dot_1_color', array(
        'default' => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_preloader_dot_1_color', array(
        'label' => esc_html__('Preloader First Dot Color','hospital-management'),
        'section' => 'hospital_management_general_settings',
        'settings' => 'hospital_management_preloader_dot_1_color',
        'active_callback' => 'hospital_management_preloader1'
    )));

    $wp_customize->add_setting( 'hospital_management_preloader_dot_2_color', array(
        'default' => '#3F3C6C',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_preloader_dot_2_color', array(
        'label' => esc_html__('Preloader Second Dot Color','hospital-management'),
        'section' => 'hospital_management_general_settings',
        'settings' => 'hospital_management_preloader_dot_2_color',
        'active_callback' => 'hospital_management_preloader1'
    )));

    $wp_customize->add_setting( 'hospital_management_preloader2_dot_color', array(
        'default' => '#0088FF',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_preloader2_dot_color', array(
        'label' => esc_html__('Preloader Dot Color','hospital-management'),
        'section' => 'hospital_management_general_settings',
        'settings' => 'hospital_management_preloader2_dot_color',
        'active_callback' => 'hospital_management_preloader2'
    )));

    $wp_customize->add_setting('hospital_management_sticky_header', array(
      'default' => false,
      'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_sticky_header',array(
          'label'          => __( 'Show Sticky Header', 'hospital-management' ),
          'section'        => 'hospital_management_general_settings',
          'settings'       => 'hospital_management_sticky_header',
          'type'           => 'checkbox',
    )));

    $wp_customize->add_setting('hospital_management_scroll_hide', array(
        'default' => true,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_scroll_hide',array(
        'label'          => __( 'Show Theme Scroll To Top', 'hospital-management' ),
        'section'        => 'hospital_management_general_settings',
        'settings'       => 'hospital_management_scroll_hide',
        'type'           => 'checkbox',
    )));

    $wp_customize->add_setting('hospital_management_scroll_top_position',array(
        'default' => 'Right',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_scroll_top_position',array(
        'type' => 'radio',
        'label' => __( 'Scroll To Top Position', 'hospital-management' ),
        'section' => 'hospital_management_general_settings',
        'choices' => array(
            'Right' => __('Right','hospital-management'),
            'Left' => __('Left','hospital-management'),
            'Center' => __('Center','hospital-management')
        ),
    ) );

    $wp_customize->add_setting( 'hospital_management_scroll_bg_color', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_scroll_bg_color', array(
        'label' => esc_html__('Scroll Top Background Color','hospital-management'),
        'section' => 'hospital_management_general_settings',
        'settings' => 'hospital_management_scroll_bg_color'
    )));

    $wp_customize->add_setting( 'hospital_management_scroll_color', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_scroll_color', array(
        'label' => esc_html__('Scroll Top Color','hospital-management'),
        'section' => 'hospital_management_general_settings',
        'settings' => 'hospital_management_scroll_color'
    )));

    $wp_customize->add_setting('hospital_management_scroll_font_size',array(
        'default'   => '16',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_scroll_font_size',array(
        'label' => __('Scroll Top Font Size','hospital-management'),
        'description' => __('Put in px','hospital-management'),
        'section'   => 'hospital_management_general_settings',
        'type'      => 'number'
    ));

    $wp_customize->add_setting('hospital_management_scroll_border_radius',array(
        'default'   => '0',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_scroll_border_radius',array(
        'label' => __('Scroll Top Border Radius','hospital-management'),
        'description' => __('Put in %','hospital-management'),
        'section'   => 'hospital_management_general_settings',
        'type'      => 'number'
    ));

    // Product Columns
    $wp_customize->add_setting( 'hospital_management_products_per_row' , array(
       'default'           => '3',
       'transport'         => 'refresh',
       'sanitize_callback' => 'hospital_management_sanitize_select',
    ) );

    $wp_customize->add_control('hospital_management_products_per_row', array(
       'label' => __( 'Product per row', 'hospital-management' ),
       'section'  => 'hospital_management_general_settings',
       'type'     => 'select',
       'choices'  => array(
           '2' => '2',
           '3' => '3',
           '4' => '4',
       ),
    ) );

    $wp_customize->add_setting('hospital_management_product_per_page',array(
        'default'   => '9',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_product_per_page',array(
        'label' => __('Product per page','hospital-management'),
        'section'   => 'hospital_management_general_settings',
        'type'      => 'number'
    ));

    //Woocommerce shop page Sidebar
    $wp_customize->add_setting('hospital_management_woocommerce_shop_page_sidebar', array(
        'default' => true,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_woocommerce_shop_page_sidebar',array(
        'label'          => __( 'Hide Shop Page Sidebar', 'hospital-management' ),
        'section'        => 'hospital_management_general_settings',
        'settings'       => 'hospital_management_woocommerce_shop_page_sidebar',
        'type'           => 'checkbox',
    )));

    $wp_customize->add_setting('hospital_management_shop_page_sidebar_layout',array(
        'default' => 'Right Sidebar',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_shop_page_sidebar_layout',array(
        'type' => 'select',
        'label' => __('Woocommerce Shop Page Sidebar','hospital-management'),
        'section' => 'hospital_management_general_settings',
        'choices' => array(
            'Left Sidebar' => __('Left Sidebar','hospital-management'),
            'Right Sidebar' => __('Right Sidebar','hospital-management'),
        ),
    ) );

    // Product Columns
    $wp_customize->add_setting('custom_related_products_number_per_row',array(
        'default'           => '3',
        'transport'         => 'refresh',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('custom_related_products_number_per_row',array(
        'label'       => esc_html__('Related Products Column Count', 'hospital-management'),
        'section'     => 'hospital_management_general_settings',
        'type'        => 'number',
        'input_attrs' => array(
            'step' => 1,
            'min'  => 1,
            'max'  => 4,
        ),
    ));

    // Product Columns
    $wp_customize->add_setting('custom_related_products_number',array(
        'default'           => '3',
        'transport'         => 'refresh',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('custom_related_products_number',array(
        'label'       => esc_html__('Number of Related Products Per Page', 'hospital-management'),
        'section'     => 'hospital_management_general_settings',
        'type'        => 'number',
        'input_attrs' => array(
            'step' => 1,
            'min'  => 1,
            'max'  => 10,
        ),
    ));

    $wp_customize->add_setting('hospital_management_related_product_display_setting', array(
        'default' => true,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_related_product_display_setting',array(
        'label'          => __( 'Show Related Products', 'hospital-management' ),
        'section'        => 'hospital_management_general_settings',
        'settings'       => 'hospital_management_related_product_display_setting',
        'type'           => 'checkbox',
    )));

    //Woocommerce Single Product page Sidebar
    $wp_customize->add_setting('hospital_management_woocommerce_single_product_page_sidebar', array(
        'default' => true,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_woocommerce_single_product_page_sidebar',array(
        'label'          => __( 'Hide Single Product Page Sidebar', 'hospital-management' ),
        'section'        => 'hospital_management_general_settings',
        'settings'       => 'hospital_management_woocommerce_single_product_page_sidebar',
        'type'           => 'checkbox',
    )));

    $wp_customize->add_setting('hospital_management_single_product_sidebar_layout',array(
        'default' => 'Right Sidebar',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_single_product_sidebar_layout',array(
        'type' => 'select',
        'label' => __('Woocommerce Single Product Page Sidebar','hospital-management'),
        'section' => 'hospital_management_general_settings',
        'choices' => array(
            'Left Sidebar' => __('Left Sidebar','hospital-management'),
            'Right Sidebar' => __('Right Sidebar','hospital-management'),
        ),
    ) );

    // Pro Version
    $wp_customize->add_setting( 'pro_version_general_setting', array(
        'sanitize_callback' => 'Hospital_Management_sanitize_custom_control'
    ));
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,'pro_version_general_setting', array(
        'section'     => 'hospital_management_general_settings',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
        'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
        'priority'    => 100
    )));

     // Social Link
    $wp_customize->add_section('hospital_management_social_link',array(
        'title' => esc_html__('Social Links','hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_facebook_icon',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_facebook_icon',array(
        'label' => esc_html__('Facebook Icon','hospital-management'),
        'section' => 'hospital_management_social_link',
        'setting' => 'hospital_management_facebook_icon',
        'type'  => 'text',
        'default' => 'fab fa-facebook-f',
        'description' =>  __('Select font awesome icons <a target="_blank" href="https://fontawesome.com/v5/search?m=free">Click Here</a> for select icon. for eg:-fab fa-facebook-f','hospital-management')
    ));

    $wp_customize->add_setting('hospital_management_facebook_url',array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw'
    ));
    $wp_customize->add_control('hospital_management_facebook_url',array(
        'label' => esc_html__('Facebook Link','hospital-management'),
        'section' => 'hospital_management_social_link',
        'setting' => 'hospital_management_facebook_url',
        'type'  => 'url'
    ));

    $wp_customize->add_setting('hospital_management_twitter_icon',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_twitter_icon',array(
        'label' => esc_html__('Twitter Icon','hospital-management'),
        'section' => 'hospital_management_social_link',
        'setting' => 'hospital_management_twitter_icon',
        'type'  => 'text',
        'default' => 'fab fa-twitter',
        'description' =>  __('Select font awesome icons <a target="_blank" href="https://fontawesome.com/v5/search?m=free">Click Here</a> for select icon. for eg:-fab fa-twitter','hospital-management')
    ));

    $wp_customize->add_setting('hospital_management_twitter_url',array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw'
    ));
    $wp_customize->add_control('hospital_management_twitter_url',array(
        'label' => esc_html__('Twitter Link','hospital-management'),
        'section' => 'hospital_management_social_link',
        'setting' => 'hospital_management_twitter_url',
        'type'  => 'url'
    ));

    $wp_customize->add_setting('hospital_management_intagram_icon',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_intagram_icon',array(
        'label' => esc_html__('Intagram Icon','hospital-management'),
        'section' => 'hospital_management_social_link',
        'setting' => 'hospital_management_intagram_icon',
        'type'  => 'text',
        'default' => 'fab fa-instagram',
        'description' =>  __('Select font awesome icons <a target="_blank" href="https://fontawesome.com/v5/search?m=free">Click Here</a> for select icon. for eg:-fab fa-instagram','hospital-management')
    ));

    $wp_customize->add_setting('hospital_management_intagram_url',array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw'
    ));
    $wp_customize->add_control('hospital_management_intagram_url',array(
        'label' => esc_html__('Intagram Link','hospital-management'),
        'section' => 'hospital_management_social_link',
        'setting' => 'hospital_management_intagram_url',
        'type'  => 'url'
    ));

    $wp_customize->add_setting('hospital_management_linkedin_icon',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_linkedin_icon',array(
        'label' => esc_html__('Linkedin Icon','hospital-management'),
        'section' => 'hospital_management_social_link',
        'setting' => 'hospital_management_linkedin_icon',
        'type'  => 'text',
        'default' => 'fab fa-linkedin-in',
        'description' =>  __('Select font awesome icons <a target="_blank" href="https://fontawesome.com/v5/search?m=free">Click Here</a> for select icon. for eg:-fab fa-linkedin-in','hospital-management')
    ));

    $wp_customize->add_setting('hospital_management_linkedin_url',array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw'
    ));
    $wp_customize->add_control('hospital_management_linkedin_url',array(
        'label' => esc_html__('Linkedin Link','hospital-management'),
        'section' => 'hospital_management_social_link',
        'setting' => 'hospital_management_linkedin_url',
        'type'  => 'url'
    ));

    $wp_customize->add_setting('hospital_management_pintrest_icon',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_pintrest_icon',array(
        'label' => esc_html__('Pinterest Icon','hospital-management'),
        'section' => 'hospital_management_social_link',
        'setting' => 'hospital_management_pintrest_icon',
        'type'  => 'text',
        'default' => 'fab fa-pinterest-p',
        'description' =>  __('Select font awesome icons <a target="_blank" href="https://fontawesome.com/v5/search?m=free">Click Here</a> for select icon. for eg:-fab fa-pinterest-p','hospital-management')
    ));

    $wp_customize->add_setting('hospital_management_pintrest_url',array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw'
    ));
    $wp_customize->add_control('hospital_management_pintrest_url',array(
        'label' => esc_html__('Pinterest Link','hospital-management'),
        'section' => 'hospital_management_social_link',
        'setting' => 'hospital_management_pintrest_url',
        'type'  => 'url'
    ));

    // Pro Version
    $wp_customize->add_setting( 'pro_version_social_setting', array(
        'sanitize_callback' => 'Hospital_Management_sanitize_custom_control'
    ));
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,'pro_version_social_setting', array(
        'section'     => 'hospital_management_social_link',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
        'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
        'priority'    => 100
    )));

    //404 Page Settings
    $wp_customize->add_section('hospital_management_404_page_settings',array(
        'title' => esc_html__(' 404 Page Settings','hospital-management')
    ));

    $wp_customize->add_setting('hospital_management_404_page_main_heading',array(
        'default'           => __( 'Oops! Page Not Found', 'hospital-management' ),
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_404_page_main_heading',array(
        'label' => esc_html__('404 Main Heading','hospital-management'),
        'section' => 'hospital_management_404_page_settings',
        'setting' => 'hospital_management_404_page_main_heading',
        'type'  => 'text'
    ));

    $wp_customize->add_setting('hospital_management_404_page_content_1',array(
        'default'           => __( 'We can’t seem to find the page you’re looking for.', 'hospital-management' ),
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_404_page_content_1',array(
        'label' => esc_html__('404 Main Content 1','hospital-management'),
        'section' => 'hospital_management_404_page_settings',
        'setting' => 'hospital_management_404_page_content_1',
        'type'  => 'text'
    ));

    $wp_customize->add_setting('hospital_management_404_page_text_1',array(
        'default'           => __( 'It looks like nothing was found at this location.', 'hospital-management' ),
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_404_page_text_1',array(
        'label' => esc_html__('404 Text 1','hospital-management'),
        'section' => 'hospital_management_404_page_settings',
        'setting' => 'hospital_management_404_page_text_1',
        'type'  => 'text'
    ));

    $wp_customize->add_setting('hospital_management_404_page_content_2',array(
        'default'           => __( 'Need Help?', 'hospital-management' ),
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_404_page_content_2',array(
        'label' => esc_html__('404 Main Content 2','hospital-management'),
        'section' => 'hospital_management_404_page_settings',
        'setting' => 'hospital_management_404_page_content_2',
        'type'  => 'text'
    ));

    $wp_customize->add_setting('hospital_management_404_page_text_2',array(
        'default'           => __( 'Try searching for what you need below.', 'hospital-management' ),
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_404_page_text_2',array(
        'label' => esc_html__('404 Text 2','hospital-management'),
        'section' => 'hospital_management_404_page_settings',
        'setting' => 'hospital_management_404_page_text_2',
        'type'  => 'text'
    ));

    // Pro Version
    $wp_customize->add_setting( 'pro_version_404_page_settings', array(
        'sanitize_callback' => 'hospital_management_sanitize_custom_control'
    ) );
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,
            'pro_version_404_page_settings', array(
                'section'     => 'hospital_management_404_page_settings',
                'type'        => 'pro_options',
                'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
                'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
                'priority'    => 100
            )
        )
    );

    //Top Header
    $wp_customize->add_section('hospital_management_top_header',array(
        'title' => esc_html__('Top Header Option','hospital-management')
    ));

    $wp_customize->add_setting('hospital_management_topbar_phone_text',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_topbar_phone_text',array(
        'label' => esc_html__('Phone Number','hospital-management'),
        'section' => 'hospital_management_top_header',
        'setting' => 'hospital_management_topbar_phone_text',
        'type'  => 'text'
    ));

    $wp_customize->add_setting('hospital_management_topbar_mail_text',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_topbar_mail_text',array(
        'label' => esc_html__('Mail Id','hospital-management'),
        'section' => 'hospital_management_top_header',
        'setting' => 'hospital_management_topbar_mail_text',
        'type'  => 'text'
    ));

    $wp_customize->add_setting('hospital_management_search_setting', array(
        'default' => false,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_search_setting',array(
        'label'          => __( 'Enable Header Search', 'hospital-management' ),
        'section'        => 'hospital_management_top_header',
        'settings'       => 'hospital_management_search_setting',
        'type'           => 'checkbox',
    )));

    $wp_customize->add_setting('hospital_management_header_sidebar_icon', array(
        'default' => 0,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_header_sidebar_icon',array(
        'label'          => __( 'Show Header Sidebar Icon', 'hospital-management' ),
        'section'        => 'hospital_management_top_header',
        'settings'       => 'hospital_management_header_sidebar_icon',
        'type'           => 'checkbox',
    )));

    // Pro Version
    $wp_customize->add_setting( 'pro_version_top_header_setting', array(
        'sanitize_callback' => 'Hospital_Management_sanitize_custom_control'
    ));
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,'pro_version_top_header_setting', array(
        'section'     => 'hospital_management_top_header',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
        'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
        'priority'    => 100
    )));

    //Menu Settings
    $wp_customize->add_section('hospital_management_menu_settings',array(
        'title' => esc_html__('Menus Settings','hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_menu_font_size',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_menu_font_size',array(
        'label' => esc_html__('Menu Font Size','hospital-management'),
        'section' => 'hospital_management_menu_settings',
        'type'  => 'number'
    ));

    $wp_customize->add_setting('hospital_management_nav_menu_text_transform',array(
        'default'=> 'Capitalize',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_nav_menu_text_transform',array(
        'type' => 'radio',
        'label' => esc_html__('Menu Text Transform','hospital-management'),
        'choices' => array(
            'Uppercase' => __('Uppercase','hospital-management'),
            'Capitalize' => __('Capitalize','hospital-management'),
            'Lowercase' => __('Lowercase','hospital-management'),
        ),
        'section'=> 'hospital_management_menu_settings',
    ));

    $wp_customize->add_setting('hospital_management_nav_menu_font_weight',array(
        'default'=> '700',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_nav_menu_font_weight',array(
        'type' => 'number',
        'label' => esc_html__('Menu Font Weight','hospital-management'),
        'input_attrs' => array(
            'step'             => 100,
            'min'              => 100,
            'max'              => 1000,
        ),
        'section'=> 'hospital_management_menu_settings',
    ));

    // Pro Version
    $wp_customize->add_setting( 'pro_version_menu_setting', array(
        'sanitize_callback' => 'Hospital_Management_sanitize_custom_control'
    ));
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,'pro_version_menu_setting', array(
        'section'     => 'hospital_management_menu_settings',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
        'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
        'priority'    => 100
    )));

    //Slider
    $wp_customize->add_section('hospital_management_top_slider',array(
        'title' => esc_html__('Slider Settings','hospital-management'),
    ));

    for ( $hospital_management_count = 1; $hospital_management_count <= 3; $hospital_management_count++ ) {

        $wp_customize->add_setting( 'hospital_management_top_slider_page' . $hospital_management_count, array(
            'default'           => '',
            'sanitize_callback' => 'hospital_management_sanitize_dropdown_pages'
        ) );
        $wp_customize->add_control( 'hospital_management_top_slider_page' . $hospital_management_count, array(
            'label'    => __( 'Select Slide Page', 'hospital-management' ),
            'description' => __('Slider image size (1400 x 550)','hospital-management'),
            'section'  => 'hospital_management_top_slider',
            'type'     => 'dropdown-pages'
        ) );
    }

    $wp_customize->add_setting('hospital_management_slider_content_layout',array(
        'default'=> 'Left',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_slider_content_layout',array(
        'type' => 'radio',
        'label' => esc_html__('Slider Content Layout','hospital-management'),
        'choices' => array(
            'Left' => __('Left','hospital-management'),
            'Center' => __('Center','hospital-management'),
            'Right' => __('Right','hospital-management'),
        ),
        'section'=> 'hospital_management_top_slider',
    ));

    $wp_customize->add_setting('hospital_management_slider_excerpt_length',array(
        'sanitize_callback' => 'hospital_management_sanitize_number_range',
        'default'  => 20,
    ));
    $wp_customize->add_control('hospital_management_slider_excerpt_length',array(
        'label'       => esc_html__('Slider Excerpt Length', 'hospital-management'),
        'section'     => 'hospital_management_top_slider',
        'type'        => 'range',
        'input_attrs' => array(
            'step' => 1,
            'min'  => 1,
            'max'  => 50,
        ),
    ));

    $wp_customize->add_setting('hospital_management_slider_opacity_setting', array(
        'default' => true,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control( new WP_Customize_Control($wp_customize,'hospital_management_slider_opacity_setting',array(
        'label'    => __( 'Show Image Opacity', 'hospital-management' ),
        'section'  => 'hospital_management_top_slider',
        'type'     => 'checkbox',
    )));

    $wp_customize->add_setting( 'hospital_management_image_opacity_color', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_image_opacity_color', array(
        'label' => __('Slider Image Opacity Color', 'hospital-management'),
        'section' => 'hospital_management_top_slider',
        'settings' => 'hospital_management_image_opacity_color',
    )));

    $wp_customize->add_setting('hospital_management_slider_opacity',array(
        'default'=> '0.7',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_slider_opacity',array(
        'type' => 'select',
        'label' => esc_html__('Slider Image Opacity','hospital-management'),
        'choices' => array(
            '0'   => '0',
            '0.1' => '0.1',
            '0.2' => '0.2',
            '0.3' => '0.3',
            '0.4' => '0.4',
            '0.5' => '0.5',
            '0.6' => '0.6',
            '0.7' => '0.7',
            '0.8' => '0.8',
            '0.9' => '0.9',
            '1'   => '1',
        ),
        'section'=> 'hospital_management_top_slider',
    ));

    // Pro Version
    $wp_customize->add_setting( 'pro_version_slider_setting', array(
        'sanitize_callback' => 'Hospital_Management_sanitize_custom_control'
    ));
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,'pro_version_slider_setting', array(
        'section'     => 'hospital_management_top_slider',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
        'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
        'priority'    => 100
    )));

    // Services
    $wp_customize->add_section('hospital_management_services_section',array(
        'title' => esc_html__('Services Option','hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_services_heading',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_services_heading',array(
        'label' => esc_html__('Service Heading','hospital-management'),
        'section' => 'hospital_management_services_section',
        'setting' => 'hospital_management_services_heading',
        'type'  => 'text'
    ));

    $wp_customize->add_setting('hospital_management_services_content',array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    $wp_customize->add_control('hospital_management_services_content',array(
        'label' => esc_html__('Service Content','hospital-management'),
        'section' => 'hospital_management_services_section',
        'setting' => 'hospital_management_services_content',
        'type'  => 'text'
    ));

    $categories = get_categories();
    $cat_post = array();
    $cat_post[]= 'select';
    $i = 0;
    foreach($categories as $category){
        if($i==0){
            $default = $category->slug;
            $i++;
        }
        $cat_post[$category->slug] = $category->name;
    }

    $wp_customize->add_setting('hospital_management_services_sec_category',array(
        'default'   => 'select',
        'sanitize_callback' => 'hospital_management_sanitize_select',
    ));
    $wp_customize->add_control('hospital_management_services_sec_category',array(
        'type'    => 'select',
        'choices' => $cat_post,
        'label' => __('Select Category to display services','hospital-management'),
        'section' => 'hospital_management_services_section',
    ));

    for ($i=1; $i <=5; $i++) {
        $wp_customize->add_setting('hospital_management_services_icon'.$i,array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        $wp_customize->add_control('hospital_management_services_icon'.$i,array(
            'label' => esc_html__('Services Icon ','hospital-management').$i,
            'section' => 'hospital_management_services_section',
            'setting' => 'hospital_management_services_icon'.$i,
            'type'  => 'text',
            'description' =>  __('Select font awesome icons <a target="_blank" href="https://fontawesome.com/v5/search?m=free">Click Here</a> for select icon. for eg:-fas fa-stethoscope','hospital-management')
        ));
    }

    // Pro Version
    $wp_customize->add_setting( 'pro_version_service_setting', array(
        'sanitize_callback' => 'Hospital_Management_sanitize_custom_control'
    ));
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,'pro_version_service_setting', array(
        'section'     => 'hospital_management_services_section',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
        'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
        'priority'    => 100
    )));

    // Post Settings
     $wp_customize->add_section('hospital_management_post_settings',array(
        'title' => esc_html__('Post Settings','hospital-management'),
        'priority'   =>40,
    ));

     $wp_customize->add_setting('hospital_management_post_page_title',array(
        'sanitize_callback' => 'hospital_management_sanitize_checkbox',
        'default'           => 1,
    ));
    $wp_customize->add_control('hospital_management_post_page_title',array(
        'type'        => 'checkbox',
        'label'       => esc_html__('Enable Post Page Title', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'description' => esc_html__('Check this box to enable title on post page.', 'hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_post_page_meta',array(
        'sanitize_callback' => 'hospital_management_sanitize_checkbox',
        'default'           => 1,
    ));
    $wp_customize->add_control('hospital_management_post_page_meta',array(
        'type'        => 'checkbox',
        'label'       => esc_html__('Enable Post Page Meta', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'description' => esc_html__('Check this box to enable meta on post page.', 'hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_post_page_thumb',array(
        'sanitize_callback' => 'hospital_management_sanitize_checkbox',
        'default'           => 1,
    ));
    $wp_customize->add_control('hospital_management_post_page_thumb',array(
        'type'        => 'checkbox',
        'label'       => esc_html__('Enable Post Page Thumbnail', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'description' => esc_html__('Check this box to enable thumbnail on post page.', 'hospital-management'),
    ));

    $wp_customize->add_setting( 'hospital_management_post_page_image_border_radius', array(
        'default'              => '0',
        'transport'            => 'refresh',
        'sanitize_callback'    => 'hospital_management_sanitize_number_range'
    ) );
    $wp_customize->add_control( 'hospital_management_post_page_image_border_radius', array(
        'label'       => esc_html__( 'Post Page Image Border Radius','hospital-management' ),
        'section'     => 'hospital_management_post_settings',
        'type'        => 'range',
        'input_attrs' => array(
            'step'             => 1,
            'min'              => 1,
            'max'              => 50,
        ),
    ) );

    $wp_customize->add_setting( 'hospital_management_post_page_image_box_shadow', array(
        'default'              => '0',
        'transport'            => 'refresh',
        'sanitize_callback'    => 'hospital_management_sanitize_number_range'
    ) );
    $wp_customize->add_control( 'hospital_management_post_page_image_box_shadow', array(
        'label'       => esc_html__( 'Post Page Image Box Shadow','hospital-management' ),
        'section'     => 'hospital_management_post_settings',
        'type'        => 'range',
        'input_attrs' => array(
            'step'             => 1,
            'min'              => 1,
            'max'              => 50,
        ),
    ) );

    $wp_customize->add_setting('hospital_management_post_page_content',array(
        'sanitize_callback' => 'hospital_management_sanitize_checkbox',
        'default'           => 1,
    ));
    $wp_customize->add_control('hospital_management_post_page_content',array(
        'type'        => 'checkbox',
        'label'       => esc_html__('Enable Post Page Content', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'description' => esc_html__('Check this box to enable content on post page.', 'hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_post_page_excerpt_length',array(
        'sanitize_callback' => 'hospital_management_sanitize_number_range',
        'default'           => 30,
    ));
    $wp_customize->add_control('hospital_management_post_page_excerpt_length',array(
        'label'       => esc_html__('Post Page Excerpt Length', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'type'        => 'range',
        'input_attrs' => array(
            'step'             => 1,
            'min'              => 1,
            'max'              => 50,
        ),
    ));

    $wp_customize->add_setting('hospital_management_post_page_excerpt_suffix',array(
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '[...]',
    ));
    $wp_customize->add_control('hospital_management_post_page_excerpt_suffix',array(
        'type'        => 'text',
        'label'       => esc_html__('Post Page Excerpt Suffix', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'description' => esc_html__('For Ex. [...], etc', 'hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_post_page_pagination',array(
        'sanitize_callback' => 'hospital_management_sanitize_checkbox',
        'default'           => 1,
    ));
    $wp_customize->add_control('hospital_management_post_page_pagination',array(
        'type'        => 'checkbox',
        'label'       => esc_html__('Enable Post Page Pagination', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'description' => esc_html__('Check this box to enable pagination on post page.', 'hospital-management'),
    ));

    $wp_customize->add_setting( 'hospital_management_blog_pagination_type', array(
        'default'           => 'blog-nav-numbers',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control( 'hospital_management_blog_pagination_type', array(
        'section' => 'hospital_management_post_settings',
        'type' => 'select',
        'label' => __( 'Post Pagination Type', 'hospital-management' ),
        'choices' => array(
            'blog-nav-numbers'  => __( 'Numeric', 'hospital-management' ),
            'next-prev' => __( 'Older/Newer Posts', 'hospital-management' ),
        )
    ));

    $wp_customize->add_setting('hospital_management_single_post_thumb',array(
        'sanitize_callback' => 'hospital_management_sanitize_checkbox',
        'default'           => 1,
    ));
    $wp_customize->add_control('hospital_management_single_post_thumb',array(
        'type'        => 'checkbox',
        'label'       => esc_html__('Enable Single Post Thumbnail', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'description' => esc_html__('Check this box to enable post thumbnail on single post.', 'hospital-management'),
    ));

    // Single Post Page Settings
    $wp_customize->add_setting( 'hospital_management_single_post_page_image_border_radius', array(
        'default'              => '0',
        'transport'            => 'refresh',
        'sanitize_callback'    => 'hospital_management_sanitize_number_range'
    ) );
    $wp_customize->add_control( 'hospital_management_single_post_page_image_border_radius', array(
        'label'       => esc_html__( 'Single Post Page Image Border Radius','hospital-management' ),
        'section'     => 'hospital_management_post_settings',
        'type'        => 'range',
        'input_attrs' => array(
            'step'             => 1,
            'min'              => 1,
            'max'              => 50,
        ),
    ) );

    $wp_customize->add_setting( 'hospital_management_single_post_page_image_box_shadow', array(
        'default'              => '0',
        'transport'            => 'refresh',
        'sanitize_callback'    => 'hospital_management_sanitize_number_range'
    ) );
    $wp_customize->add_control( 'hospital_management_single_post_page_image_box_shadow', array(
        'label'       => esc_html__( 'Single Post Page Image Box Shadow','hospital-management' ),
        'section'     => 'hospital_management_post_settings',
        'type'        => 'range',
        'input_attrs' => array(
            'step'             => 1,
            'min'              => 1,
            'max'              => 50,
        ),
    ) );

    $wp_customize->add_setting('hospital_management_single_post_meta',array(
        'sanitize_callback' => 'hospital_management_sanitize_checkbox',
        'default'           => 1,
    ));
    $wp_customize->add_control('hospital_management_single_post_meta',array(
        'type'        => 'checkbox',
        'label'       => esc_html__('Enable Single Post Meta', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'description' => esc_html__('Check this box to enable single post meta such as post date, author, category, comment etc.', 'hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_single_post_title',array(
            'sanitize_callback' => 'hospital_management_sanitize_checkbox',
            'default'           => 1,
    ));
    $wp_customize->add_control('hospital_management_single_post_title',array(
        'type'        => 'checkbox',
        'label'       => esc_html__('Enable Single Post Title', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'description' => esc_html__('Check this box to enable title on single post.', 'hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_single_post_page_content',array(
        'sanitize_callback' => 'hospital_management_sanitize_checkbox',
        'default'           => 1,
    ));
    $wp_customize->add_control('hospital_management_single_post_page_content',array(
        'type'        => 'checkbox',
        'label'       => esc_html__('Enable Single Post Page Content', 'hospital-management'),
        'section'     => 'hospital_management_post_settings',
        'description' => esc_html__('Check this box to enable content on single post page.', 'hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_single_post_navigation_show_hide',array(
        'default' => true,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control('hospital_management_single_post_navigation_show_hide',array(
        'type' => 'checkbox',
        'label' => __('Show / Hide Post Navigation','hospital-management'),
        'section' => 'hospital_management_post_settings',
    ));

    $wp_customize->add_setting('hospital_management_single_post_comment_title',array(
        'default'=> 'Leave a Reply',
        'sanitize_callback' => 'sanitize_text_field'
    ));

    $wp_customize->add_control('hospital_management_single_post_comment_title',array(
        'label' => __('Add Comment Title','hospital-management'),
        'input_attrs' => array(
        'placeholder' => __( 'Leave a Reply', 'hospital-management' ),
        ),
        'section'=> 'hospital_management_post_settings',
        'type'=> 'text'
    ));

    $wp_customize->add_setting('hospital_management_single_post_comment_btn_text',array(
        'default'=> 'Post Comment',
        'sanitize_callback' => 'sanitize_text_field'
    ));

    $wp_customize->add_control('hospital_management_single_post_comment_btn_text',array(
        'label' => __('Add Comment Button Text','hospital-management'),
        'input_attrs' => array(
            'placeholder' => __( 'Post Comment', 'hospital-management' ),
        ),
        'section'=> 'hospital_management_post_settings',
        'type'=> 'text'
    ));

    // Pro Version
    $wp_customize->add_setting( 'pro_version_post_setting', array(
        'sanitize_callback' => 'Hospital_Management_sanitize_custom_control'
    ));
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,'pro_version_post_setting', array(
        'section'     => 'hospital_management_post_settings',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
        'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
        'priority'    => 100
    )));
    
    // Footer
    $wp_customize->add_section('hospital_management_site_footer_section', array(
        'title' => esc_html__('Footer', 'hospital-management'),
    ));

    $wp_customize->add_setting('hospital_management_footer_bg_image',array(
        'default'   => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control( new WP_Customize_Image_Control($wp_customize,'hospital_management_footer_bg_image',array(
        'label' => __('Footer Background Image','hospital-management'),
        'section' => 'hospital_management_site_footer_section',
        'priority' => 1,
    )));

    $wp_customize->add_setting('hospital_management_footer_bg_image_position',array(
        'default'=> 'scroll',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_footer_bg_image_position',array(
        'type' => 'select',
        'label' => __('Footer Background Image Position','hospital-management'),
        'choices' => array(
            'fixed' => __('fixed','hospital-management'),
            'scroll' => __('scroll','hospital-management'),
        ),
        'section'=> 'hospital_management_site_footer_section',
    ));

    $wp_customize->add_setting( 'hospital_management_footer_bg_color', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_footer_bg_color', array(
        'label' => __('Footer Background Color', 'hospital-management'),
        'section' => 'hospital_management_site_footer_section',
        'settings' => 'hospital_management_footer_bg_color',
    )));

    $wp_customize->add_setting( 'hospital_management_footer_content_color', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_footer_content_color', array(
        'label' => __('Footer Content Color', 'hospital-management'),
        'section' => 'hospital_management_site_footer_section',
        'settings' => 'hospital_management_footer_content_color',
    )));

    $wp_customize->add_setting('hospital_management_footer_widget_heading_alignment',array(
        'default' => 'Left',
        'transport' => 'refresh',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_footer_widget_heading_alignment',array(
        'type' => 'select',
        'label' => __('Footer Widget Heading Alignment','hospital-management'),
        'section' => 'hospital_management_site_footer_section',
        'choices' => array(
            'Left' => __('Left','hospital-management'),
            'Center' => __('Center','hospital-management'),
            'Right' => __('Right','hospital-management')
        ),
    ) );

    $wp_customize->add_setting('hospital_management_footer_widget_content_alignment',array(
        'default' => 'Left',
        'transport' => 'refresh',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_footer_widget_content_alignment',array(
        'type' => 'select',
        'label' => __('Footer Widget Content Alignment','hospital-management'),
        'section' => 'hospital_management_site_footer_section',
        'choices' => array(
            'Left' => __('Left','hospital-management'),
            'Center' => __('Center','hospital-management'),
            'Right' => __('Right','hospital-management')
        ),
    ) );

    $wp_customize->add_setting('hospital_management_show_hide_copyright',array(
        'default' => true,
        'sanitize_callback' => 'hospital_management_sanitize_checkbox'
    ));
    $wp_customize->add_control('hospital_management_show_hide_copyright',array(
        'type' => 'checkbox',
        'label' => __('Show / Hide Copyright','hospital-management'),
        'section' => 'hospital_management_site_footer_section',
    ));

    $wp_customize->add_setting('hospital_management_footer_text_setting', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('hospital_management_footer_text_setting', array(
        'label' => __('Replace the footer text', 'hospital-management'),
        'section' => 'hospital_management_site_footer_section',
        'type' => 'text',
    ));

    $wp_customize->add_setting('hospital_management_copyright_content_alignment',array(
        'default' => 'Right',
        'transport' => 'refresh',
        'sanitize_callback' => 'hospital_management_sanitize_choices'
    ));
    $wp_customize->add_control('hospital_management_copyright_content_alignment',array(
        'type' => 'select',
        'label' => __('Copyright Content Alignment','hospital-management'),
        'section' => 'hospital_management_site_footer_section',
        'choices' => array(
            'Left' => __('Left','hospital-management'),
            'Center' => __('Center','hospital-management'),
            'Right' => __('Right','hospital-management')
        ),
    ) );

    $wp_customize->add_setting( 'hospital_management_copyright_text_color', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hospital_management_copyright_text_color', array(
        'label' => __('Copyright Text Color', 'hospital-management'),
        'section' => 'hospital_management_site_footer_section',
        'settings' => 'hospital_management_copyright_text_color',
    )));

    // Pro Version
    $wp_customize->add_setting( 'pro_version_footer_setting', array(
        'sanitize_callback' => 'Hospital_Management_sanitize_custom_control'
    ));
    $wp_customize->add_control( new Hospital_Management_Customize_Pro_Version ( $wp_customize,'pro_version_footer_setting', array(
        'section'     => 'hospital_management_site_footer_section',
        'type'        => 'pro_options',
        'label'       => esc_html__( 'Customizer Options', 'hospital-management' ),
        'description' => esc_url( HOSPITAL_MANAGEMENT_URL ),
        'priority'    => 100
    )));
}
add_action('customize_register', 'hospital_management_customize_register');

/**
 * Render the site title for the selective refresh partial.
 *
 * @return void
 */
function hospital_management_customize_partial_blogname(){
    bloginfo('name');
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @return void
 */
function hospital_management_customize_partial_blogdescription(){
    bloginfo('description');
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function hospital_management_customize_preview_js(){
    wp_enqueue_script('hospital-management-customizer', esc_url(get_template_directory_uri()) . '/assets/js/customizer.js', array('customize-preview'), '20151215', true);
}
add_action('customize_preview_init', 'hospital_management_customize_preview_js');

/*
** Load dynamic logic for the customizer controls area.
*/
function hospital_management_panels_js() {
    wp_enqueue_style( 'hospital-management-customizer-layout-css', get_theme_file_uri( '/assets/css/customizer-layout.css' ) );
    wp_enqueue_script( 'hospital-management-customize-layout', get_theme_file_uri( '/assets/js/customize-layout.js' ), array(), '1.2', true );
}
add_action( 'customize_controls_enqueue_scripts', 'hospital_management_panels_js' );