<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package Hospital Management
 */
get_header(); ?>

    <div id="skip-content" class="container">
        <div id="primary" class="content-area">
            <main id="main" class="site-main module-border-wrap mb-4">
                <div class="row">
                    <?php if (have_posts()) { ?>
                        
                        <?php
                      
                        /**
                         * Run the loop for the search to output the results.
                         * If you want to overload this in a child theme then include a file
                         * called content-search.php and that will be used instead.
                         */
                        get_template_part( 'template-parts/patterns');

                    }else {

                        get_template_part('template-parts/content', 'none');

                    } ?>
                </div>
            </main>
        </div>
    </div>

<?php get_footer();