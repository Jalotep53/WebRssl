<?php
/**
 * Displays main header
 *
 * @package Hospital Management
 */
?>

<?php
$hospital_management_sticky_header = get_theme_mod('hospital_management_sticky_header');
    $hospital_management_data_sticky = "false";
    if ($hospital_management_sticky_header) {
    $hospital_management_data_sticky = "true";
    }
?>
<div class="main-header text-center text-md-start" data-sticky="<?php echo esc_attr($hospital_management_data_sticky); ?>">
    <div class="container">
        <div class="row nav-box">
            <div class="col-lg-3 col-md-3 col-sm-5 col-12 logo-box">
                <div class="navbar-brand ">
                    <?php if ( has_custom_logo() ) : ?>
                        <div class="site-logo"><?php the_custom_logo(); ?></div>
                    <?php endif; ?>
                    <?php $hospital_management_blog_info = get_bloginfo( 'name' ); ?>
                        <?php if ( ! empty( $hospital_management_blog_info ) ) : ?>
                            <?php if ( is_front_page() && is_home() ) : ?>
                                <?php if( get_theme_mod('hospital_management_logo_title_text',true) != ''){ ?>
                                    <h1 class="site-title pt-2"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
                                <?php } ?>
                            <?php else : ?>
                                <?php if( get_theme_mod('hospital_management_logo_title_text',true) != ''){ ?>
                                    <p class="site-title "><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
                                <?php } ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php
                            $hospital_management_description = get_bloginfo( 'description', 'display' );
                            if ( $hospital_management_description || is_customize_preview() ) :
                        ?>
                        <?php if( get_theme_mod('hospital_management_theme_description',false) != ''){ ?>
                            <p class="site-description pb-2"><?php echo esc_html($hospital_management_description); ?></p>
                        <?php } ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-7 col-md-7 col-sm-5 col-4 align-self-center header-box">
                <?php get_template_part('template-parts/navigation/nav'); ?>
            </div>
            <div class="col-lg-1 col-md-1 col-sm-1 col-4 align-self-center">
                <?php if(get_theme_mod('hospital_management_search_setting',false) != ''){ ?>
                    <span class="head-search">
                        <div class="header-search-wrapper">
                            <span class="search-main">
                                <i class="fa fa-search"></i>
                            </span>
                            <div class="search-form-main clearfix">
                                <form method="get" class="search-form">
                                  <label>
                                    <input type="search" class="search-field form-control" placeholder="Search …" value="<?php echo esc_attr( get_search_query() ); ?>" name="s">
                                  </label>
                                  <input type="submit" class="search-submit btn btn-primary mt-3" value="Search">
                                </form>
                            </div>
                        </div>
                    </span>
                <?php }?>
            </div>
            <div class="col-lg-1 col-md-1 col-sm-1 col-4 align-self-center text-lg-end">
                <?php if(get_theme_mod('hospital_management_header_sidebar_icon','')){ ?>
                    <?php if(is_active_sidebar( 'sidebar' )) : ?>
                        <span class="header_in">
                            <button type="button" class="toggle" id="toggle">
                               <span></span>
                            </button>
                        </span>
                        <div class="slidebar text-start" id='slidebar'>
                            <div class="sidebar">
                                <?php dynamic_sidebar( 'sidebar' ); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
