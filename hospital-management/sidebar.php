<?php
/**
 * The sidebar containing the main widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Hospital Management
 */

?>

<aside id="secondary" class="widget-area">
	<div class="sidebar">
		<?php if ( is_active_sidebar( 'sidebar' ) ) : ?>

            <?php dynamic_sidebar( 'sidebar' ); ?>

        <?php else : ?>

            <!-- Search Widget -->
            <section id="search" class="widget widget_search">
                <h5 class="widget-title"><?php esc_html_e( 'Search', 'hospital-management' ); ?></h5>
                <?php get_search_form(); ?>
            </section>

            <!-- Archives Widget -->
            <section id="archives" class="widget widget_archive">
                <h5 class="widget-title"><?php esc_html_e( 'Archives List', 'hospital-management' ); ?></h5>
                <ul>
                    <?php wp_get_archives( array( 'type' => 'monthly' ) ); ?>
                </ul>
            </section>

            <!-- Recent Posts Widget -->
            <section id="recent-posts-widget" class="widget widget_recent_posts">
                <h5 class="widget-title"><?php esc_html_e( 'Recent Posts', 'hospital-management' ); ?></h5>
                <ul>
                    <?php
                    $recent_posts = wp_get_recent_posts( array(
                        'numberposts' => 5,
                        'post_status' => 'publish',
                    ) );
                    foreach ( $recent_posts as $post ) :
                    ?>
                        <li>
                            <a href="<?php echo esc_url( get_permalink( $post['ID'] ) ); ?>">
                                <?php echo esc_html( $post['post_title'] ); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <!-- Meta Widget -->
            <section id="meta" class="widget widget_meta">
                <h5 class="widget-title"><?php esc_html_e( 'Meta', 'hospital-management' ); ?></h5>
                <ul>
                    <?php wp_register(); ?>
                    <li><?php wp_loginout(); ?></li>
                    <?php wp_meta(); ?>
                </ul>
            </section>

            <!-- Categories Widget -->
            <section id="categories" class="widget widget_categories">
                <h5 class="widget-title"><?php esc_html_e( 'Categories', 'hospital-management' ); ?></h5>
                <ul>
                    <?php wp_list_categories( array( 'title_li' => '' ) ); ?>
                </ul>
            </section>

            <!-- Tag Cloud Widget -->
            <section id="tags" class="widget widget_tag_cloud">
                <h5 class="widget-title"><?php esc_html_e( 'Tags', 'hospital-management' ); ?></h5>
                <div class="tagcloud">
                    <?php wp_tag_cloud( array(
                        'smallest' => 10,
                        'largest'  => 22,
                        'unit'     => 'px',
                        'number'   => 20
                    ) ); ?>
                </div>
            </section>

        <?php endif; ?>
	</div>
</aside>