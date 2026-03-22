<?php
/**
 *Hospital Management - Theme Info Admin Menu
 * @package TheMagnifico52
 * @subpackage Admin
 */
if ( ! class_exists( 'hospital_management_Theme_Info' ) ) {
    class hospital_management_Theme_Info{

        private $config;
        private $theme_name;
        private $theme_slug;
        private $theme_version;
        private $page_title;
        private $menu_title;
        private $tabs;

        /**
         * Constructor.
         */
        public function __construct( $config ) {
            $this->config = $config;
            $this->hospital_management_hospital_management_prepare_class();

            /*admin menu*/
            add_action( 'admin_menu', array( $this, 'hospital_management_tm_admin_menu' ) );

            /* enqueue script and style for about page */
            add_action( 'admin_enqueue_scripts', array( $this, 'hospital_management_style_and_scripts' ) );

            /* ajax callback for dismissable required actions */
            add_action( 'wp_ajax_tm_theme_info_update_recommend_action', array( $this, 'hospital_management_update_recommended_action_callback' ) );
        }

        /**
         * Prepare and setup class properties.
         */
        public function hospital_management_hospital_management_prepare_class() {
            $theme = wp_get_theme();
            $this->theme_name    = esc_html( $theme->get( 'Name' ) );
            $this->theme_slug    = $theme->get_template();
            $this->theme_version = $theme->get( 'Version' );
            $this->page_title    = $this->theme_name . esc_html__( ' Info', 'hospital-management' );
            $this->menu_title    = $this->theme_name . esc_html__( ' Theme', 'hospital-management' );
            $this->tabs          = isset( $this->config['tabs'] ) ? $this->config['tabs'] : array();
        }

        /**
         * Return the valid array of recommended actions.
         * @return array The valid array of recommended actions.
         */
        /**
         * Dismiss required actions
         */
        public function hospital_management_update_recommended_action_callback() {

            check_ajax_referer( 'tm_recommend_action_nonce' );

            if ( ! current_user_can( 'edit_theme_options' ) ) {
                wp_send_json_error( 'Permission denied.' );
                return;
            }

            /*getting for provided array*/
            $recommended_actions = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();

            /*from js action*/
            $action_id = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : '';
            $todo      = isset( $_GET['todo'] ) ? sanitize_key( wp_unslash( $_GET['todo'] ) ) : '';

            /*getting saved actions*/
            $saved_actions = get_option( $this->theme_slug . '_recommended_actions' );

            echo esc_html( wp_unslash( $action_id ) ); /* this is needed and it's the id of the dismissable required action */

            if ( ! empty( $action_id ) ) {

                if( 'reset' == $todo ){
                    $saved_actions_new = array();
                    if ( ! empty( $recommended_actions ) ) {

                        foreach ( $recommended_actions as $recommended_action ) {
                            $saved_actions[ $recommended_action['id'] ] = true;
                        }
                        update_option( $this->theme_slug . '_recommended_actions', $saved_actions_new );
                    }
                }
                /* if the option exists, update the record for the specified id */
                elseif ( !empty( $saved_actions) and is_array( $saved_actions ) ) {

                    switch ( esc_html( $todo ) ) {
                        case 'add';
                            $saved_actions[ $action_id ] = true;
                            break;
                        case 'dismiss';
                            $saved_actions[ $action_id ] = false;
                            break;
                    }
                    update_option( $this->theme_slug . '_recommended_actions', $saved_actions );

                    /* create the new option,with false for the specified id */
                }
                else {
                    $saved_actions_new = array();
                    if ( ! empty( $recommended_actions ) ) {

                        foreach ( $recommended_actions as $recommended_action ) {
                            echo esc_html($recommended_action['id']);
                            echo " ". esc_html($todo);
                            if ( $recommended_action['id'] == $action_id ) {
                                switch ( esc_html( $todo ) ) {
                                    case 'add';
                                        $saved_actions_new[ $action_id ] = true;
                                        break;
                                    case 'dismiss';
                                        $saved_actions_new[ $action_id ] = false;
                                        break;
                                }
                            }
                        }
                    }
                    update_option( $this->theme_slug . '_recommended_actions', $saved_actions_new );
                }
            }
            exit;
        }

        private function hospital_management_get_recommended_actions() {
            $saved_actions = get_option( $this->theme_slug . '_recommended_actions' );
            if ( ! is_array( $saved_actions ) ) {
                $saved_actions = array();
            }
            $recommended_actions = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();
            $valid       = array();
            if( isset( $recommended_actions ) && is_array( $recommended_actions ) ){
                foreach ( $recommended_actions as $recommended_action ) {
                    if (
                        (
                            ! isset( $recommended_action['check'] ) ||
                            ( isset( $recommended_action['check'] ) && ( $recommended_action['check'] == false ) )
                        )
                        &&
                        ( ! isset( $saved_actions[ $recommended_action['id'] ] ) ||
                            ( isset( $saved_actions[ $recommended_action['id']] ) && ($saved_actions[ $recommended_action['id']] == true ) )
                        )
                    ) {
                        $valid[] = $recommended_action;
                    }
                }
            }
            return $valid;
        }

        private function hospital_management_count_recommended_actions() {
            $count = 0;
            $actions_count = $this->hospital_management_get_recommended_actions();
            if ( ! empty( $actions_count ) ) {
                $count = count( $actions_count );
            }
            return $count;
        }
        
        /**
         * Adding Theme Info Menu under Appearance.
         */
        function hospital_management_tm_admin_menu() {

            if ( ! empty( $this->page_title ) && ! empty( $this->menu_title ) ) {
                $count = $this->hospital_management_count_recommended_actions();
                $menu_title = $count > 0 ? $this->menu_title . '<span class="badge-action-count">' . esc_html( $count ) . '</span>' : $this->menu_title;
                /* Example
                 * add_theme_page('My Plugin Theme', 'My Plugin', 'edit_theme_options', 'my-unique-identifier', 'my_plugin_function');
                 * */
                add_theme_page( $this->page_title, $menu_title, 'edit_theme_options', $this->theme_slug . '-info', array(
                    $this,
                    'hospital_management_tm_theme_info_screen_main',
                ) );
            }
        }

        /**
         * Render the info content screen.
         */
        public function hospital_management_tm_theme_info_screen_main() {
            $theme_name_config = esc_attr ( wp_get_theme()->get('Name') );
            if ( ! empty( $this->config['info_title'] ) ) {
                $welcome_title = $this->config['info_title'];
            }
            if ( ! empty( $this->config['info_content'] ) ) {
                $welcome_content = $this->config['info_content'];
            }
            if ( ! empty( $this->config['quick_links'] ) ) {
                $quick_links = $this->config['quick_links'];
            }

            if (
                ! empty( $welcome_title ) ||
                ! empty( $welcome_content ) ||
                ! empty( $quick_links ) ||
                ! empty( $this->tabs )
            ) {
                echo '<div class="wrap about-wrap info-wrap epsilon-wrap">';
                echo '<div class="header-wrap display-grid col-grid-2 align-center">';
                echo '<div class="theme-detail col">';
                if ( ! empty( $welcome_title ) ) {
                    echo '<h1>';
                    echo esc_html( $welcome_title );
                    if ( ! empty( $this->theme_version ) ) {
                        echo esc_html( $this->theme_version ) . ' </sup>';
                    }
                    echo '</h1>';
                }
                if ( ! empty( $welcome_content ) ) {
                    echo '<div class="about-text">' . wp_kses_post( $welcome_content ) . '</div>';
                }

                /*quick links*/
                if( !empty( $quick_links ) && is_array( $quick_links ) ){
                    echo '<p class="quick-links">';
                    foreach ( $quick_links as $quick_key => $quick_link ) {
                        $button = 'button-secondary';
                        if( 'pro_url' == $quick_key ){
                            $button = 'button button-primary button-hero pro-btn';
                        }
                        if( 'bundle_url' == $quick_key ){
                            $button = 'button button-primary button-hero bundle-btn';
                        }
                        echo '<a href="'.esc_url( $quick_link['url'] ).'" class="button button-hero '.esc_attr( $button ).'" target="_blank">'.esc_html( $quick_link['text'] ).'</a>';
                    }
                    echo "</p>";
                }
                echo '</div>';
                echo '<div class="theme-img col">';
                echo '<img src="' . get_theme_file_uri() . '/assets/img/bundle-img.png" alt="screenshot" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</div>';
                echo '</div><!--/header-wrap-->';
                /* Display tabs */
                if ( ! empty( $this->tabs ) ) {
                    $current_tab = isset( $_GET['tab'] ) ? wp_unslash( $_GET['tab'] ) : 'hospital_management_demo_impoter';

                    echo '<h2 class="nav-tab-wrapper wp-clearfix">';
                    $count = $this->hospital_management_count_recommended_actions();
                    foreach ( $this->tabs as $tab_key => $tab_name ) {
                        echo '<a href="' . esc_url( admin_url( 'themes.php?page=' . $this->theme_slug . '-info' ) ) . '&tab=' . esc_attr( $tab_key ) . '" class="nav-tab ' . ( $current_tab == $tab_key ? 'nav-tab-active' : '' ) . '" role="tab" data-toggle="tab">';
                        echo esc_html( $tab_name );
                        if ( $tab_key == 'recommended_actions' ) {
                            if ( $count > 0 ) {
                                echo '<span class="badge-action-count">' . esc_html( $count ) . '</span>';
                            }
                        }
                        echo '</a>';
                    }

                    echo '</h2>';

                    /* Display content for current tab, dynamic method according to key provided*/
                    if ( method_exists( $this, $current_tab ) ) {

                        echo "<div class='changelog point-releases'>";
                        $this->$current_tab();
                        echo "</div>";
                    }
                }
                echo '</div><!--/.wrap.about-wrap-->';
            }
        }

        /**
         * Getting started tab
         */
        public function hospital_management_demo_impoter() {
            echo '<div class="feature-section demo-impoter-tab ">';
            if ( ! empty( $this->config['hospital_management_demo_impoter_steps'] ) ) {
                $hospital_management_demo_impoter_steps = $this->config['hospital_management_demo_impoter_steps'];
                if ( ! empty( $hospital_management_demo_impoter_steps ) ) { ?>
                        <?php
                            // Get current theme data
                            $theme = wp_get_theme();
                            $theme_screenshot  = $theme->get_screenshot();
                            ?>
                            <div class="impoter-box-main">
                                <div class="wrap-main-box">
                                    <div class="main-box">
                                        <h2><?php echo esc_html('Welcome toHospital Management','hospital-management'); ?></h2>
                                        <h3><?php echo esc_html('Create your website in just one click','hospital-management'); ?></h3>
                                        <p><?php echo esc_html('The "Begin Installation" helps you quickly set up your website by importing sample content that mirrors the demo version of the theme. This tool provides you with a ready-made layout and structure, so you can easily see how your site will look and start customizing it right away. It\'s a straightforward way to get your site up and running with minimal effort.','hospital-management'); ?></p>
                                        <p><?php echo esc_html('Click the button below to install the demo content.','hospital-management'); ?></p>
                                        <button id="import-theme-mods" class="button button-primary"><?php echo esc_html('Begin Installation','hospital-management'); ?></button>
                                        <div id="import-response"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="impoter-left">
                                <div class="importer-description">
                                    <div class="importer-buttons">
                                        <a target="_blank" href="<?php echo esc_url( HOSPITAL_MANAGEMENT_GET_PREMIUM_PRO ); ?>" class="btn btn-purple"><?php echo esc_html('Get Premium','hospital-management'); ?></a>
                                        <a target="_blank" href="<?php echo esc_url( HOSPITAL_MANAGEMENT_LIVE_DEMO ); ?>" class="btn btn-green"><?php echo esc_html('View Demo','hospital-management'); ?></a>
                                        <a target="_blank" href="<?php echo esc_url( HOSPITAL_MANAGEMENT_FREE_DOC ); ?>" class="btn btn-yellow"><?php echo esc_html('Lite Doc','hospital-management'); ?></a>
                                        <a target="_blank" href="<?php echo esc_url( HOSPITAL_MANAGEMENT_CONTACT_SUPPORT ); ?>" class="btn btn-blue"><?php echo esc_html('Lite Support','hospital-management'); ?></a>
                                    </div>
                                </div>
                                <div class="importer-screenshot">
                                    <img src="<?php echo esc_url( $theme_screenshot ); ?>" alt="<?php echo esc_attr( $theme->get('Name') ); ?>">
                                </div>
                            </div>
                         
                    <?php }
            }
            echo '</div><!-- .feature-section col-wrap -->';
        }

        /**
         * Getting started tab
         */
        public function hospital_management_pro_templates() {
            echo '<div class="feature-section demo-impoter-tab ">'; ?>
                <div class="pro-search-main">
                    <div class="mnssp-search-box">
                        <div class="mnssp-templates-collections-search">
                            <input type="text" name="mnssp-templates-search" autocomplete="off" placeholder="Search Templates...">
                            <span class="dashicons dashicons-search"></span>
                        </div>
                        <div class="mnssp-templates-collections-filter">
                            <?php $collections_arr = free_mnssp_get_collections(); ?>
                            <select name="mnssp-collections" id="mnssp-collections">
                                <?php foreach ( $collections_arr as $collection ) { ?>
                                    <option value="<?php echo esc_attr($collection->handle); ?>"><?php echo esc_html($collection->title); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="mnssp-templates-grid mnssp-main-grid getstart-free ">
                        <?php $get_filtered_products = free_mnssp_get_filtered_products();
                            if (isset($get_filtered_products['products']) && !empty($get_filtered_products['products'])) {
                                foreach ( $get_filtered_products['products'] as $product ) {

                                    $product_obj = $product->node;
                                    
                                    if (isset($product_obj->inCollection) && !$product_obj->inCollection) {
                                        continue;
                                    }

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
                        ?>
                    </div>

                    <?php if (isset($get_filtered_products['pagination']->hasNextPage) && $get_filtered_products['pagination']->hasNextPage) { ?>
                        <input type="hidden" name="mnssp-end-cursor" value="<?php echo esc_attr(isset($get_filtered_products['pagination']->endCursor) ? $get_filtered_products['pagination']->endCursor : '') ?>">
                    <?php } ?>
                </div>
            <?php echo '</div><!-- .feature-section col-wrap -->';
        }

        /**
         * Recommended Actions tab
         */
        public function hospital_management_check_plugin_status( $slug ) {

            $path = WPMU_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php';
            if ( ! file_exists( $path ) ) {
                $path = WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php';
                if ( ! file_exists( $path ) ) {
                    $path = false;
                }
            }

            if ( file_exists( $path ) ) {
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound

                $needs = is_plugin_active( $slug . '/' . $slug . '.php' ) ? 'deactivate' : 'activate';

                return array( 'status' => is_plugin_active( $slug . '/' . $slug . '.php' ), 'needs' => $needs );
            }

            return array( 'status' => false, 'needs' => 'install' );
        }

        public function hospital_management_create_action_link( $state, $slug ) {

            switch ( $state ) {
                case 'install':
                    return wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => 'install-plugin',
                                'plugin' => $slug
                            ),
                            network_admin_url( 'update.php' )
                        ),
                        'install-plugin_' . $slug
                    );
                    break;

                case 'deactivate':
                    return add_query_arg(
                            array(
                                'action'        => 'deactivate',
                                'plugin'        => rawurlencode( $slug . '/' . $slug . '.php' ),
                                'plugin_status' => 'all',
                                'paged'         => '1',
                                '_wpnonce'      => wp_create_nonce( 'deactivate-plugin_' . $slug . '/' . $slug . '.php' )
                                ),
                            network_admin_url( 'plugins.php' )
                    );
                    break;

                case 'activate':
                    return add_query_arg(
                            array(
                                'action'        => 'activate',
                                'plugin'        => rawurlencode( $slug . '/' . $slug . '.php' ),
                                'plugin_status' => 'all',
                                'paged'         => '1',
                                '_wpnonce'      => wp_create_nonce( 'activate-plugin_' . $slug . '/' . $slug . '.php' )
                            ),
                            network_admin_url( 'plugins.php' )
                    );
                    break;
            }
        }

        public function recommended_actions() {

            $recommended_actions = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();
            $hooray = true;
            if ( ! empty( $recommended_actions ) ) {

                echo '<div class="feature-section action-recommended demo-import-boxed" id="plugin-filter">';

                if ( ! empty( $recommended_actions ) && is_array( $recommended_actions ) ) {

                    /*get saved recommend actions*/
                    $saved_recommended_actions = get_option( $this->theme_slug . '_recommended_actions' );

                    /*defaults values for hospital_management_getting_started array */
                    $defaults = array(
                        'title'         => '',
                        'desc'          => '',
                        'check'         => false,
                        'plugin_slug'   => '',
                        'id'            => ''
                    );
                    foreach ( $recommended_actions as $action_key => $action_value ) {
                        $instance = wp_parse_args( (array) $action_value, $defaults );

                        /*allowed 5 value in array */
                        $title = $instance[ 'title' ];
                        $desc = $instance[ 'desc' ];
                        $check = $instance[ 'check' ];
                        $plugin_slug = $instance[ 'plugin_slug' ];
                        $id = $instance[ 'id' ];

                        $hidden = false;

                        /*magic check for recommended actions*/
                        if (
                            isset( $saved_recommended_actions[ $id ] ) &&
                            $saved_recommended_actions[ $id ] == false ) {
                            $hidden = true;
                        }
                        if ( $hidden ) {
                            echo '';
                        }
                        $done = '';
                        if ( $check ) {
                           $done = 'done';
                        }

                        echo "<div class='tm-theme-installation-action-recommend-box {$done}'>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

                        if ( ! $hidden ) {
                            echo '<span data-action="dismiss" class="dashicons dashicons-visibility tm-theme-installation-recommend-action-button" id="' . esc_attr( $action_value['id'] ) . '"></span>';
                        } else {
                            echo '<span data-action="add" class="dashicons dashicons-hidden tm-theme-installation-recommend-action-button" id="' . esc_attr( $action_value['id'] ) .'"></span>';
                        }

                        if ( ! empty( $title) ) {
                            echo '<h3>' . wp_kses_post( $title ) . '</h3>';
                        }

                        if ( ! empty( $desc ) ) {
                            echo '<p>' . wp_kses_post( $desc ) . '</p>';
                        }

                        if ( ! empty( $plugin_slug ) ) {

                            $active = $this->hospital_management_check_plugin_status( $action_value['plugin_slug'] );
                            $url    = $this->hospital_management_create_action_link( $active['needs'], $action_value['plugin_slug'] );
                            $label  = '';
                            $class  = '';
                            switch ( $active['needs'] ) {

                                case 'install':
                                    $class = 'install-now button';
                                    $label = esc_html__( 'Install', 'hospital-management' );
                                    break;

                                case 'activate':
                                    $class = 'activate-now button button-primary';
                                    $label = esc_html__( 'Activate', 'hospital-management' );

                                    break;

                                case 'deactivate':
                                    $class = 'deactivate-now button';
                                    $label = esc_html__( 'Deactivate', 'hospital-management' );

                                    break;
                            }

                            ?>
                            <p class="plugin-card-<?php echo esc_attr( $action_value['plugin_slug'] ) ?> action_button <?php echo ( $active['needs'] !== 'install' && $active['status'] ) ? 'active' : '' ?>">
                                <a data-slug="<?php echo esc_attr( $action_value['plugin_slug'] ) ?>"
                                   class="<?php echo esc_attr( $class ); ?>"
                                   href="<?php echo esc_url( $url ) ?>"> <?php echo esc_html( $label ) ?> </a>
                            </p>

                            <?php

                        }
                        echo '</div>';
                        $hooray = false;
                    }
                }
                if ( $hooray ){
                    echo '<span class="hooray">' . esc_html__( 'Hooray! There are no recommended actions for you right now.', 'hospital-management' ) . '</span>';
                    echo '<a data-action="reset" id="reset" class="reset-all" href="#">'.esc_html__('Show All Recommended Actions', 'hospital-management').'</a>';
                }
                echo '</div>';
            }
        }

        /**
         * Free vs Pro tab
         */
        public function hospital_management_free_pro() {
            $theme_name_config = esc_attr ( wp_get_theme()->get('Name') );
            $hospital_management_free_pro = isset( $this->config['hospital_management_free_pro'] ) ? $this->config['hospital_management_free_pro'] : array();
            if ( ! empty( $hospital_management_free_pro ) ) {
                /*defaults values for child theme array */
                $defaults = array(
                    'title'=> '',
                    'desc' => '',
                    'free' => '',
                    'pro'  => '',
                );

                if ( ! empty( $hospital_management_free_pro ) && is_array( $hospital_management_free_pro ) ) {
                    echo '<div class="feature-section">';
                    echo '<div id="hospital_management_free_pro" class="hospital-management-theme-installation-tab-pane hospital-management-theme-installation-fre-pro">';
                    echo '<table class="free-pro-table table-light-wrapper hospital-management-theme-installation-text-center">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>' . esc_html__( 'Theme Feature','hospital-management' ) . '</th>';
                    echo '<th>' . esc_html__( 'Basic Version','hospital-management' ) . '</th>';
                    echo '<th>' . esc_html__( 'Premium Version','hospital-management' ) . '</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    foreach ( $hospital_management_free_pro as $feature ) {

                        $instance = wp_parse_args( (array) $feature, $defaults );

                        /*allowed 7 value in array */
                        $title = $instance[ 'title' ];
                        $desc = $instance[ 'desc'];
                        $free = $instance[ 'free'];
                        $pro = $instance[ 'pro'];

                        echo '<tr>';
                        if ( ! empty( $title ) || ! empty( $desc ) ) {
                            echo '<td>';
                            if ( ! empty( $title ) ) {
                                echo '<h3>' . wp_kses_post( $title ) . '</h3>';
                            }
                            if ( ! empty( $desc ) ) {
                                echo '<p>' . wp_kses_post( $desc ) . '</p>';
                            }
                            echo '</td>';
                        }

                        if ( ! empty( $free )) {
                            if( 'yes' === $free ){
                                echo '<td class="only-lite"><span class="dashicons-before dashicons-yes"></span></td>';
                            }
                            elseif ( 'no' === $free ){
                                echo '<td class="only-pro"><span class="dashicons-before dashicons-no-alt"></span></td>';
                            }
                            else{
                                echo '<td class="only-lite">'.esc_html($free ).'</td>';
                            }

                        }
                        if ( ! empty( $pro )) {
                            if( 'yes' === $pro ){
                                echo '<td class="only-lite"><span class="dashicons-before dashicons-yes"></span></td>';
                            }
                            elseif ( 'no' === $pro ){
                                echo '<td class="only-pro"><span class="dashicons-before dashicons-no-alt"></span></td>';
                            }
                            else{
                                echo '<td class="only-lite">'.esc_html($pro ).'</td>';
                            }
                        }
                        echo '</tr>';
                    }

                    echo '<tr>';
                    echo '<td></td>';
                    echo '<td></td>';
                    echo '<td><a href="' . esc_url( HOSPITAL_MANAGEMENT_GET_PREMIUM_PRO ).'" target="_blank" class="button button-primary button-hero compare-pro">Buy Pro</a></td>';
                    echo '</tr>';
                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                    echo '</div>';

                }
            }
        }

        /**
         * Recommended plugins tab
         */
        /*
         * Call plugin api
         */
        public function call_plugin_api( $slug ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound

            if ( false === ( $call_api = get_transient( 'tm_theme_info_plugin_information_' . $slug ) ) ) {
                $call_api = plugins_api( 'plugin_information', array(
                    'slug'   => $slug,
                    'fields' => array(
                        'downloaded'        => false,
                        'rating'            => false,
                        'description'       => false,
                        'short_description' => true,
                        'donate_link'       => false,
                        'tags'              => false,
                        'sections'          => true,
                        'homepage'          => true,
                        'added'             => false,
                        'last_updated'      => false,
                        'compatibility'     => false,
                        'tested'            => false,
                        'requires'          => false,
                        'downloadlink'      => false,
                        'icons'             => true
                    )
                ) );
                set_transient( 'tm_theme_info_plugin_information_' . $slug, $call_api, 30 * MINUTE_IN_SECONDS );
            }

            return $call_api;
        }
        public function get_plugin_icon( $arr ) {

            if ( ! empty( $arr['svg'] ) ) {
                $plugin_icon_url = $arr['svg'];
            } elseif ( ! empty( $arr['2x'] ) ) {
                $plugin_icon_url = $arr['2x'];
            } elseif ( ! empty( $arr['1x'] ) ) {
                $plugin_icon_url = $arr['1x'];
            } else {
                $plugin_icon_url = get_template_directory_uri() . '/assets/img/placeholder_plugin.png';
            }

            return $plugin_icon_url;
        }
        public function hospital_management_recommended_plugins() {
            $hospital_management_recommended_plugins = $this->config['hospital_management_recommended_plugins'];

            if ( ! empty( $hospital_management_recommended_plugins ) ) {
                if ( ! empty( $hospital_management_recommended_plugins ) && is_array( $hospital_management_recommended_plugins ) ) {

                    echo '<div class="feature-section recommended-plugins col-wrap demo-import-boxed" id="plugin-filter">';

                    foreach ( $hospital_management_recommended_plugins as $hospital_management_recommended_plugins_item ) {

                        if ( ! empty( $hospital_management_recommended_plugins_item['slug'] ) ) {
                            $info   = $this->call_plugin_api( $hospital_management_recommended_plugins_item['slug'] );
                            if ( ! empty( $info->icons ) ) {
                                $icon = $this->get_plugin_icon( $info->icons );
                            }

                            $active = $this->hospital_management_check_plugin_status( $hospital_management_recommended_plugins_item['slug'] );

                            if ( ! empty( $active['needs'] ) ) {
                                $url = $this->hospital_management_create_action_link( $active['needs'], $hospital_management_recommended_plugins_item['slug'] );
                            }

                            echo '<div class="col"><div class="col-items plugin_box">';
                            if ( ! empty( $icon ) ) {
                                echo '<img src="'.esc_url( $icon ).'" alt="plugin box image">';
                            }
                            if ( ! empty(  $info->version ) ) {
                                echo '<span class="version">'. ( ! empty( $this->config['hospital_management_recommended_plugins']['version_label'] ) ? esc_html( $this->config['hospital_management_recommended_plugins']['version_label'] ) : '' ) . esc_html( $info->version ).'</span>';
                            }
                            if ( ! empty( $info->author ) ) {
                                echo '<span class="separator"> | </span>' . wp_kses_post( $info->author );
                            }

                            if ( ! empty( $info->name ) && ! empty( $active ) ) {
                                echo '<div class="action_bar ' . ( ( $active['needs'] !== 'install' && $active['status'] ) ? 'active' : '' ) . '">';
                                echo '<span class="plugin_name">' . ( ( $active['needs'] !== 'install' && $active['status'] ) ? 'Active: ' : '' ) . esc_html( $info->name ) . '</span>';
                                echo '</div>';

                                $label = '';

                                switch ( $active['needs'] ) {

                                    case 'install':
                                        $class = 'install-now button';
                                        $label = esc_html__( 'Install', 'hospital-management' );
                                        break;

                                    case 'activate':
                                        $class = 'activate-now button button-primary';
                                        $label = esc_html__( 'Activate', 'hospital-management' );

                                        break;

                                    case 'deactivate':
                                        $class = 'deactivate-now button';
                                        $label = esc_html__( 'Deactivate', 'hospital-management' );

                                        break;
                                }

                                echo '<span class="plugin-card-' . esc_attr( $hospital_management_recommended_plugins_item['slug'] ) . ' action_button ' . ( ( $active['needs'] !== 'install' && $active['status'] ) ? 'active' : '' ) . '">';
                                echo '<a data-slug="' . esc_attr( $hospital_management_recommended_plugins_item['slug'] ) . '" class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
                                echo '</span>';
                            }
                            echo '</div></div><!-- .col.plugin_box -->';
                        }
                    }
                    echo '</div><!-- .recommended-plugins -->';
                }
            }
        }

        /**
         * Changelog tab
         */
        private function hospital_management_parse_changelog() {
            WP_Filesystem();
            global $wp_filesystem;

            $readme_content = $wp_filesystem->get_contents( get_template_directory() . '/readme.txt' );

            if ( is_wp_error( $readme_content ) || empty( $readme_content ) ) {
                return '';
            }

            // Extract only the "Changelog" section
            $changelog = '';
            if ( preg_match( '/==\s*Changelog\s*==(.+?)(==\s*[A-Z][^=]+==|$)/is', $readme_content, $matches ) ) {
                $changelog = trim( $matches[1] );
            }

            return $changelog;
        }

        public function hospital_management_changelog() {
            $hospital_management_changelog = $this->hospital_management_parse_changelog();
            if ( ! empty( $hospital_management_changelog ) ) {
                echo '<div class="featured-section changelog">';
                echo "<pre class='changelog'>";
                echo esc_html($hospital_management_changelog);
                echo "</pre>";
                echo '</div><!-- .featured-section.changelog -->';
            }
        }

        /**
         * Load css and scripts for the about page
         */
        public function hospital_management_style_and_scripts( $hook_suffix ) {

            // this is needed on all admin pages, not just the about page, for the badge action count in the WordPress main sidebar
            wp_enqueue_style( 'hospital-management-theme-installation-css', get_template_directory_uri() . '/inc/theme-installation/assets/css/theme-installation.css' );

            if ( 'appearance_page_' . $this->theme_slug . '-info' == $hook_suffix ) {

                wp_enqueue_script( 'hospital-management-theme-installation-js', get_template_directory_uri() . '/inc/theme-installation/assets/js/theme-installation.js', array( 'jquery' ) );

                wp_enqueue_style( 'plugin-install' );
                wp_enqueue_script( 'plugin-install' );
                wp_enqueue_script( 'updates' );

                $count = $this->hospital_management_count_recommended_actions();
                wp_localize_script( 'hospital-management-theme-installation-js', 'tm_theme_info_box_object', array(
                    'count_actions_recommended' => $count,
                    'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
                    'template_directory'        => get_template_directory_uri(),
                    'nonce'                     => wp_create_nonce('free_mnssp_create_pagination_nonce_action'),
                    'recommend_action_nonce'    => wp_create_nonce('tm_recommend_action_nonce'),
                ) );

            }

        }
    }
}

$theme_name_config = esc_attr ( wp_get_theme()->get('Name') );
$config = array(

    // Main welcome title
    /* translators: %s - Theme Name*/
    'info_title' => sprintf( esc_html__( 'Welcome to %s - ', 'hospital-management' ), $theme_name_config ),

    // Main welcome content
    /* translators: %s - Theme Name*/
    'info_content' => sprintf( esc_html__( '%s is now installed and ready to use. We hope the following information will help and you enjoy using it!', 'hospital-management' ), '<b>'.$theme_name_config.'</b>' ),

    /**
     * Quick links
     */
    'quick_links' => array(
        'theme_url'  => array(
            'text' => __( 'Try the Demo', 'hospital-management' ),
            'url' => esc_url( HOSPITAL_MANAGEMENT_LIVE_DEMO )
        ),
        'pro_url'  => array(
            'text' => __( 'Get Premium', 'hospital-management' ),
            'url'  => esc_url( HOSPITAL_MANAGEMENT_GET_PREMIUM_PRO )
        ),
        'bundle_url'  => array(
            'text' => __( 'Buy All Themes - 120+ Templates', 'hospital-management' ),
            'url'  => esc_url( HOSPITAL_MANAGEMENT_BUNDLE_LINK )
        ),
    ),

    'tabs' => array(
        'hospital_management_demo_impoter'         => esc_html__( 'Demo Impoter', 'hospital-management' ),
        'hospital_management_pro_templates'        => esc_html__( 'Pro Templates', 'hospital-management' ),
        'hospital_management_free_pro'             => esc_html__( 'Compare Free Vs Pro', 'hospital-management' ),
        'hospital_management_recommended_plugins'  => esc_html__( 'Recommended Plugins', 'hospital-management' ),
        'hospital_management_changelog'            => esc_html__( 'Changelog', 'hospital-management' ),
    ),

    /*Getting started tab*/
    'hospital_management_gs_steps' => array(
        'first' => array(
            'title' => esc_html__( 'Checkout Premium', 'hospital-management' ),
            'desc' => esc_html__( 'Our premium theme comes with extended features like demo content import , responsive layouts etc.', 'hospital-management' ),
            'button_label' => esc_html__( 'Get Premium', 'hospital-management' ),
            'button_link' => esc_url( HOSPITAL_MANAGEMENT_GET_PREMIUM_PRO ),
            'is_button' => true,
            'is_new_tab' => true
        ),
        'second' => array (
            'title' => esc_html__( 'Contact Support', 'hospital-management' ),
            'desc' => esc_html__( 'Thank you for tryingHospital Management , feel free to contact us for any support regarding our theme.', 'hospital-management' ),
            'button_label' => esc_html__( 'Contact Support', 'hospital-management' ),
            'button_link' => esc_url( HOSPITAL_MANAGEMENT_CONTACT_SUPPORT ),
            'is_button' => true,
            'is_new_tab' => true
        ),
        'third' => array (
            'title' => esc_html__( 'Review', 'hospital-management' ),
            'desc' => esc_html__( 'If You loveHospital Management theme then we would appreciate your review about our theme.', 'hospital-management' ),
            'button_label' => esc_html__( 'Review', 'hospital-management' ),
            'button_link' => esc_url( HOSPITAL_MANAGEMENT_REVIEW ),
            'is_button' => true,
            'is_new_tab' => true
        ),
        'fourth' => array (
            'title' => esc_html__( 'Go to Customizer', 'hospital-management' ),
            'desc' => esc_html__( 'All Settings, Header & Footer Options and Theme Options are available via Customize screen.', 'hospital-management' ),
            'button_label' => esc_html__( 'Go to Customizer', 'hospital-management' ),
            'button_link' => esc_url( admin_url( 'customize.php' ) ),
            'is_button' => true,
            'is_new_tab' => true
        ),
        'fifth' => array(
            'title' => esc_html__( 'Free Documentation', 'hospital-management' ),
            'desc' => esc_html__( 'Our guide is available if you require any help configuring and setting up the theme. Easy and quick way to setup the theme.', 'hospital-management' ),
            'button_label' => esc_html__( 'Free Documentation', 'hospital-management' ),
            'button_link' => esc_url( HOSPITAL_MANAGEMENT_FREE_DOC ),
            'is_button' => true,
            'is_new_tab' => true
        ),
        'sixth' => array(
            'title' => esc_html__( 'View Theme Demo', 'hospital-management' ),
            'desc' => esc_html__( 'Explore the demo to see how easily you can set up and customize the theme.', 'hospital-management' ),
            'button_label' => esc_html__( 'View Demo', 'hospital-management' ),
            'button_link' => esc_url( HOSPITAL_MANAGEMENT_LIVE_DEMO ),
            'is_button' => true,
            'is_new_tab' => true
        ),
    ),

    /*Getting started tab*/
    'hospital_management_demo_impoter_steps' => array(
        'first' => array(
            'title' => esc_html__( 'Instant Demo Setup', 'hospital-management' ),
            'desc' => esc_html__( 'Import your entire demo content in just one click, including pages, posts, and design elements for a quick setup.', 'hospital-management' ),
            'button_label' => esc_html__( 'Start Demo Import', 'hospital-management' ),
            'button_link' => esc_url( admin_url( 'admin.php?page=theme-importer' ) ),
            'is_button' => true,
            'is_new_tab' => true
        )
    ),

    // recommended actions array.
    'hospital_management_recommended_actions' => array(

    ),

    // Free vs pro array.
    'hospital_management_free_pro' => array(
        array(
            'desc'=> __( 'Header Background Color', 'hospital-management' ),
            'free' => __( 'yes','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Custom Navigation Logo Or Text', 'hospital-management' ),
            'free' => __( 'yes','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Hide Logo Text', 'hospital-management' ),
            'free' => __( 'yes','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Premium Support', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Fully SEO Optimized', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Recent Posts Widget', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Easy Google Fonts', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Pagespeed Plugin', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Only Show Header Image On Front Page', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Show Header Everywhere', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Custom Text On Header Image', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Full Width (Hide Sidebar)', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Only Show Upper Widgets On Front Page', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Replace Copyright Text', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Customize Upper Widgets Colors', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Customize Navigation Color', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Customize Post/Page Color', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Customize Blog Feed Color', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Customize Footer Color', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Customize Sidebar Color', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Customize Background Color', 'hospital-management' ),
            'free' => __( 'no','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
        array(
            'desc'=> __( 'Importable Demo Content', 'hospital-management' ),
            'free' => __( 'yes','hospital-management' ),
            'pro'  => __( 'yes','hospital-management' ),
        ),
    ),

    // Generic Plugins array.
    'hospital_management_recommended_plugins' => array(
        'Magnify – Suggestive Search' => array(
            'slug' => 'magnify-suggestive-search'
        ),
        'Classic Widgets' => array(
            'slug' => 'classic-widgets'
        )
    ),

);
return new hospital_management_Theme_Info( $config );