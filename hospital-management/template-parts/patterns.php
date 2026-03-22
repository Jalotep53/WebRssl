<?php
/**
 * Template part used for rendering the post layout
 *
 * @package Flower Delivery Shop
 *
 */
?>
<?php
$hospital_management_post_layout = get_theme_mod( 'hospital_management_post_layout','pattern_two_column_right');
/**
 * =============================
 *  TWO COLUMN – RIGHT SIDEBAR
 * =============================
 */
if($hospital_management_post_layout == 'pattern_two_column_right'){ ?>
<div class="content_area col-lg-9 col-md-8">      
    <?php if ( have_posts() ) : ?>
        <div class="row">
            <?php while ( have_posts() ) : the_post(); ?>
                <div class="col-lg-6 col-md-6 col-sm-12 mb-4">
                    <?php get_template_part('template-parts/content', get_post_format()); ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif;
        if( get_theme_mod('hospital_management_post_page_pagination',1) == 1){ 
        hospital_management_blog_posts_pagination();
    } ?>
</div>

    <div class="col-lg-3 col-md-4 sidebar">
        <?php get_sidebar();?>
    </div>
<?php }
/**
 * =============================
 *  TWO COLUMN – LEFT SIDEBAR
 * =============================
 */
else if($hospital_management_post_layout == 'pattern_two_column_left'){ ?>
    <div class="col-lg-3 col-md-4 sidebar">
        <?php get_sidebar();?>
    </div>
    <div class="content_area col-lg-9 col-md-8">      
        <?php if ( have_posts() ) : ?>
            <div class="row">
                <?php while ( have_posts() ) : the_post(); ?>
                    <div class="col-lg-6 col-md-6 col-sm-12 mb-4">
                        <?php get_template_part('template-parts/content', get_post_format()); ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif;
            if( get_theme_mod('hospital_management_post_page_pagination',1) == 1){ 
            hospital_management_blog_posts_pagination();
        } ?>
    </div>
<?php }
/**
 * =============================
 *  ONE COLUMN
 * =============================
 */
else if($hospital_management_post_layout == 'pattern_one_column'){ ?>
    <div class="content_area col-lg-12 col-md-12">      
        <?php if( have_posts() ): ?>
        
            <?php while( have_posts() ): the_post(); ?>
            
                <?php get_template_part('template-parts/content',get_post_format() ); ?>
            <?php endwhile; ?>
        
        <?php else: ?>
            
            <?php get_template_part('template-parts/content','none'); ?>

        <?php endif;
            if( get_theme_mod('hospital_management_post_page_pagination',1) == 1){ 
            hospital_management_blog_posts_pagination();
        } ?>
    </div>
<?php }
/**
 * =============================
 *  THREE COLUMN
 * =============================
 */
else if($hospital_management_post_layout == 'pattern_three_column'){ ?>
    <div class="col-lg-3 col-md-3 sidebar">
        <?php get_sidebar();?>
    </div>
    <div class="content_area col-lg-6 col-md-6">      
        <?php if( have_posts() ): ?>
        
            <?php while( have_posts() ): the_post(); ?>
            
                <?php get_template_part('template-parts/content',get_post_format() ); ?>
            <?php endwhile; ?>
            
        <?php else: ?>
            
            <?php get_template_part('template-parts/content','none'); ?>
            
        <?php endif;
            if( get_theme_mod('hospital_management_post_page_pagination',1) == 1){ 
            hospital_management_blog_posts_pagination();
        } ?>
    </div>
    <div class="col-lg-3 col-md-3 sidebar">
        <?php dynamic_sidebar('sidebar1'); ?>
    </div>
<?php }
/**
 * =============================
 *  FOUR COLUMN
 * =============================
 */
else if($hospital_management_post_layout == 'pattern_four_column'){ ?>
    <div class="col-lg-3 col-md-3 sidebar">
        <?php get_sidebar();?>
    </div>
    <div class="col-lg-3 col-md-3 sidebar">
        <?php dynamic_sidebar('sidebar1'); ?>
    </div>
    <div class="content_area col-lg-3 col-md-3">      
        <?php if( have_posts() ): ?>
        
            <?php while( have_posts() ): the_post(); ?>
            
                <?php get_template_part('template-parts/content',get_post_format() ); ?>
            <?php endwhile; ?>
            
        <?php else: ?>
            
            <?php get_template_part('template-parts/content','none'); ?>
            
        <?php endif;
            if( get_theme_mod('hospital_management_post_page_pagination',1) == 1){ 
            hospital_management_blog_posts_pagination();
        } ?>
    </div>
    <div class="col-lg-3 col-md-3 sidebar">
        <?php dynamic_sidebar('sidebar2'); ?>
    </div>
<?php }
/**
 * =============================
 *   GRID POST
 * =============================
 */
else if($hospital_management_post_layout == 'pattern_grid_post'){ ?>
<div class="content_area col-lg-12 col-md-12">
    <div class="row">        
        <?php if( have_posts() ): ?>
        
            <?php while( have_posts() ): the_post(); ?>
            
               <div class="col-lg-4 mb-4">
                    <?php get_template_part('template-parts/content',get_post_format() ); ?>
                </div>
            <?php endwhile; ?>
            
        <?php else: ?>
            
            <?php get_template_part('template-parts/content','none'); ?>
            
        <?php endif;
            if( get_theme_mod('hospital_management_post_page_pagination',1) == 1){ 
            hospital_management_blog_posts_pagination();
        } ?>
    </div>
</div>
<?php }