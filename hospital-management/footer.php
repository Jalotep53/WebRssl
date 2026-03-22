<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Hospital Management
 */
?>

<footer id="colophon" class="site-footer border-top">
    <div class="container">
    	<div class="footer-column">
	      	<div class="row">
		        <div class="col-lg-3 col-md-3 col-sm-6 col-12">
		          	<?php if (is_active_sidebar('hospital-management-footer1')) : ?>
                        <?php dynamic_sidebar('hospital-management-footer1'); ?>
                    <?php else : ?>
                        <aside id="search" class="widget" role="complementary" aria-label="<?php esc_attr_e( 'firstsidebar', 'hospital-management' ); ?>">
                            <h5 class="widget-title"><?php esc_html_e( 'About Us', 'hospital-management' ); ?></h5>
                            <div class="textwidget">
                            	<p><?php esc_html_e( 'Nam malesuada nulla nisi, ut faucibus magna congue nec. Ut libero tortor, tempus at auctor in, molestie at nisi. In enim ligula, consequat eu feugiat a.', 'hospital-management' ); ?></p>
                            </div>
                        </aside>
                    <?php endif; ?>
		        </div>
		        <div class="col-lg-3 col-md-3 col-sm-6 col-12">
		            <?php if (is_active_sidebar('hospital-management-footer2')) : ?>
                        <?php dynamic_sidebar('hospital-management-footer2'); ?>
                    <?php else : ?>
                        <aside id="pages" class="widget">
                            <h5 class="widget-title"><?php esc_html_e( 'Useful Links', 'hospital-management' ); ?></h5>
                            <ul class="mt-4">
                            	<li><?php esc_html_e( 'Home', 'hospital-management' ); ?></li>
                            	<li><?php esc_html_e( 'Tournaments', 'hospital-management' ); ?></li>
                            	<li><?php esc_html_e( 'Reviews', 'hospital-management' ); ?></li>
                            	<li><?php esc_html_e( 'About Us', 'hospital-management' ); ?></li>
                            </ul>
                        </aside>
                    <?php endif; ?>
		        </div>
		        <div class="col-lg-3 col-md-3 col-sm-6 col-12">
		            <?php if (is_active_sidebar('hospital-management-footer3')) : ?>
                        <?php dynamic_sidebar('hospital-management-footer3'); ?>
                    <?php else : ?>
                        <aside id="pages" class="widget">
                            <h5 class="widget-title"><?php esc_html_e( 'Information', 'hospital-management' ); ?></h5>
                            <ul class="mt-4">
                            	<li><?php esc_html_e( 'FAQ', 'hospital-management' ); ?></li>
                            	<li><?php esc_html_e( 'Site Maps', 'hospital-management' ); ?></li>
                            	<li><?php esc_html_e( 'Privacy Policy', 'hospital-management' ); ?></li>
                            	<li><?php esc_html_e( 'Contact Us', 'hospital-management' ); ?></li>
                            </ul>
                        </aside>
                    <?php endif; ?>
		        </div>
		        <div class="col-lg-3 col-md-3 col-sm-6 col-12">
		            <?php if (is_active_sidebar('hospital-management-footer4')) : ?>
                        <?php dynamic_sidebar('hospital-management-footer4'); ?>
                    <?php else : ?>
                        <aside id="pages" class="widget">
                            <h5 class="widget-title"><?php esc_html_e( 'Get In Touch', 'hospital-management' ); ?></h5>
                            <ul class="mt-4">
                            	<li><?php esc_html_e( 'Via Carlo Montù 78', 'hospital-management' ); ?><br><?php esc_html_e( '22021 Bellagio CO, Italy', 'hospital-management' ); ?></li>
                            	<li><?php esc_html_e( '+11 6254 7855', 'hospital-management' ); ?></li>
                            	<li><?php esc_html_e( 'support@example.com', 'hospital-management' ); ?></li>
                            </ul>
                        </aside>
                    <?php endif; ?>
		        </div>
	      	</div>
		</div>
    	<?php if (get_theme_mod('hospital_management_show_hide_copyright', true)) {?>
	        <div class="site-info">
	            <div class="footer-menu-left text-center">
	            	<?php  if( ! get_theme_mod('hospital_management_footer_text_setting') ){ ?>
					    <a target="_blank" href="<?php echo esc_url('https://wordpress.org/'); ?>">
							<?php
							/* translators: %s: CMS name, i.e. WordPress. */
							printf( esc_html__( 'Proudly powered by %s', 'hospital-management' ), 'WordPress' );
							?>
					    </a>
					    <span class="sep mr-1"> | </span>

					    <span>
                            <a href="<?php echo esc_url('https://www.themagnifico.net/products/free-hospital-wordpress-theme'); ?>">
				              	<?php
				                /* translators: 1: Theme name,  */
				                printf( esc_html__( ' %1$s ', 'hospital-management' ),'Hospital WordPress Theme' );
				              	?>
			              	</a>
				          	<?php
				              /* translators: 1: Theme author. */
				              printf( esc_html__( 'by %1$s.', 'hospital-management' ),'TheMagnifico'  );
				            ?>

	        			</span>
					<?php }?>
					<?php echo esc_html(get_theme_mod('hospital_management_footer_text_setting')); ?>
	            </div>
	        </div>
		<?php } ?>
	    <?php if(get_theme_mod('hospital_management_scroll_hide',true)){ ?>
	    	<a href="#" id="button"><?php esc_html_e('TOP','hospital-management'); ?></a>
	    <?php } ?>
    </div>
</footer>
</div>

<?php wp_footer(); ?>

</body>
</html>