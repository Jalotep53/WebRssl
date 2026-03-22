<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Hospital Management
 */
$hospital_management_post_page_title =  get_theme_mod( 'hospital_management_post_page_title', 1 );
$hospital_management_post_page_meta =  get_theme_mod( 'hospital_management_post_page_meta', 1 );
$hospital_management_post_page_content =  get_theme_mod( 'hospital_management_post_page_content', 1 );
?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('article-box'); ?>>
      <?php
        if ( ! is_single() ) {
          // If not a single post, highlight the gallery.
          if ( get_post_gallery() ) {
            echo '<div class="entry-gallery">';
              echo ( get_post_gallery() );
            echo '</div>';
          };
        };
      ?>
      <div class="serv-cont">
        <?php if ($hospital_management_post_page_meta == 1 ) {?>
          <div class="meta-info-box my-2">
            <span class="entry-author"><?php esc_html_e('BY','hospital-management'); ?> <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' )) ); ?>"><?php the_author(); ?></a></span>
            <span class="ms-2"><?php echo esc_html(get_the_date()); ?></span>
          </div>
        <?php }?>
        <div class="post-summery">
          <?php if ($hospital_management_post_page_title == 1 ) {?>
            <?php the_title('<h3 class="entry-title pb-3"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>');?>
          <?php }?>
          <?php if ($hospital_management_post_page_content == 1 ) {?>
            <p><?php echo wp_trim_words( get_the_content(), esc_attr(get_theme_mod('hospital_management_post_page_excerpt_length', 30)) ); ?><?php echo esc_html(get_theme_mod('hospital_management_post_page_excerpt_suffix','[...]')); ?></p>
          <?php }?>
          <a href="<?php the_permalink(); ?>" class="btn-text"><?php esc_html_e('Read More','hospital-management'); ?></a>
        </div>
      </div>
    </article>
