<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Hospital Management
 */

get_header(); ?>

    <div id="skip-content" class="container">
        <div id="primary" class="content-area">
            <main id="main" class="site-main">
                <?php if (have_posts()) {
                    if (is_home() && !is_front_page()) : ?>
                        <header>
                            <h1 class="page-title screen-reader-text"><?php single_post_title(); ?></h1>
                        </header>
                    <?php endif; ?>

                <div class="row">
                    <?php get_template_part( 'template-parts/patterns'); ?>
                </div>

                <?php } else {
                    get_template_part('template-parts/content', 'none');
                } ?>

            </main>
        </div>
    </div>

<?php get_footer();