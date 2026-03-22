<?php
/**
 * Template Name: Home Template
 */

get_header(); ?>

<main id="skip-content">
  <section id="top-slider" >
    <?php $hospital_management_slide_pages = array();
      for ( $hospital_management_count = 1; $hospital_management_count <= 3; $hospital_management_count++ ) {
        $hospital_management_mod = intval( get_theme_mod( 'hospital_management_top_slider_page' . $hospital_management_count ));
        if ( 'page-none-selected' != $hospital_management_mod ) {
          $hospital_management_slide_pages[] = $hospital_management_mod;
        }
      }
      if( !empty($hospital_management_slide_pages) ) :
        $hospital_management_args = array(
          'post_type' => 'page',
          'post__in' => $hospital_management_slide_pages,
          'orderby' => 'post__in'
        );
        $hospital_management_query = new WP_Query( $hospital_management_args );
        if ( $hospital_management_query->have_posts() ) :
          $i = 1;
    ?>
    <div class="owl-carousel" role="listbox">
      <?php  while ( $hospital_management_query->have_posts() ) : $hospital_management_query->the_post(); ?>
        <div class="slide-box">
          <div class="slider-image">
            <?php if(has_post_thumbnail()){
              the_post_thumbnail();
              } else{?>
              <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/img/slider.png" alt="" />
            <?php } ?>
          </div>
          <div class="slider-inner-box">
            <h3 class="m-0"><?php the_title(); ?></h3>
            <p class="content mt-3"><?php echo esc_html( wp_trim_words( get_the_content(), esc_attr(get_theme_mod('hospital_management_slider_excerpt_length', 20)) )); ?><p>
            <div class="slide-btn mt-4">
              <a href="<?php the_permalink(); ?>"><?php esc_html_e('BOOK AN APPOINTMENT','hospital-management'); ?></a>
            </div>
          </div>
        </div>
      <?php $i++; endwhile;
      wp_reset_postdata();?>
    </div>
    <?php else : ?>
      <div class="no-postfound"></div>
    <?php endif;
    endif;?>
  </section>

  <section class="featured py-5">
    <div class="container">
      <div class="ser-heading text-center mb-4">
        <?php if(get_theme_mod('hospital_management_services_heading') != ''){ ?>
          <h3 class="main-heading "><?php echo esc_html(get_theme_mod('hospital_management_services_heading')); ?></h3>
        <?php }?>
        <?php if(get_theme_mod('hospital_management_services_content') != ''){ ?>
          <p class="main-heading"><?php echo esc_html(get_theme_mod('hospital_management_services_content')); ?></p>
        <?php }?>
      </div>
      <div class="row service-box m-0">
        <?php
          $hospital_management_services_cat = get_theme_mod('hospital_management_services_sec_category','');
          if($hospital_management_services_cat){
            $hospital_management_page_query5 = new WP_Query(array( 'category_name' => esc_html($hospital_management_services_cat,'hospital-management'),'posts_per_page' => 5));
            $i=1;
            while( $hospital_management_page_query5->have_posts() ) : $hospital_management_page_query5->the_post(); ?>
              <?php if(get_theme_mod('hospital_management_services_icon'.$i,'fas fa-stethoscope')){ ?>
                <div class="col-lg-4 col-md-6 col-sm-6">
                  <div class="row feature-box mb-4 m-0">
                    <div class="col-lg-4 col-md-4 col-sm-6 service-icon align-self-center text-center">
                      <i class=" <?php echo esc_attr(get_theme_mod('hospital_management_services_icon'.$i,'fas fa-stethoscope')); ?>"></i>
                    </div>
                    <div class="col-lg-8 col-md-8 col-sm-6 ser-content align-self-center">
                      <h4 class="my-2 text-start"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                      <p class="content mt-1"><?php echo esc_html( wp_trim_words( get_the_content(),12 )); ?><p>
                    </div>
                  </div>
                </div>
              <?php }?>
            <?php $i++; endwhile;
          wp_reset_postdata();
        } ?>
      </div>
    </div>
  </section>
  <section id="page-content">
    <div class="container">
      <div class="py-5">
        <?php
          if ( have_posts() ) :
            while ( have_posts() ) : the_post();
              the_content();
            endwhile;
          endif;
        ?>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>