<?php
/**
 * The template for displaying archive pages
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
                    <?php
                        get_template_part( 'template-parts/patterns');

                    } else {

                        get_template_part('template-parts/content', 'none');

                    } ?>
                </div>

            </main>
        </div>
    </div>

<?php get_footer();