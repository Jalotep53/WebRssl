<?php
/**
 * Displays top header
 *
 * @package Hospital Management
 */
?>

<div class="top-info text-end pb-2">
	<div class="container">
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-4 align-self-center">
				<div class="social-link">
			      <?php if(get_theme_mod('hospital_management_facebook_url') != ''){ ?>
			        <a href="<?php echo esc_url(get_theme_mod('hospital_management_facebook_url','')); ?>"><i class="<?php echo esc_attr( get_theme_mod('hospital_management_facebook_icon') ); ?>"></i></a>
			      <?php }?>
			      <?php if(get_theme_mod('hospital_management_twitter_url') != ''){ ?>
			        <a href="<?php echo esc_url(get_theme_mod('hospital_management_twitter_url','')); ?>"><i class="<?php echo esc_attr( get_theme_mod('hospital_management_twitter_icon') ); ?>"></i></a>
			      <?php }?>
			      <?php if(get_theme_mod('hospital_management_intagram_url') != ''){ ?>
			        <a href="<?php echo esc_url(get_theme_mod('hospital_management_intagram_url','')); ?>"><i class="<?php echo esc_attr( get_theme_mod('hospital_management_intagram_icon') ); ?>"></i></a>
			      <?php }?>
			      <?php if(get_theme_mod('hospital_management_linkedin_url') != ''){ ?>
			        <a href="<?php echo esc_url(get_theme_mod('hospital_management_linkedin_url','')); ?>"><i class="<?php echo esc_attr( get_theme_mod('hospital_management_linkedin_icon') ); ?>"></i></a>
			      <?php }?>
			      <?php if(get_theme_mod('hospital_management_pintrest_url') != ''){ ?>
			        <a href="<?php echo esc_url(get_theme_mod('hospital_management_pintrest_url','')); ?>"><i class="<?php echo esc_attr( get_theme_mod('hospital_management_pintrest_icon') ); ?>"></i></a>
			      <?php }?>
			    </div>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-8 align-self-center">
				<div class="row">
					<div class="col-lg-6 col-md-6 col-sm-6 align-self-center">
						<?php if ( get_theme_mod('hospital_management_topbar_phone_text') != "" ) {?>
					        <div class=" text-center text-lg-end py-2">
					            <p class="location m-0"><i class="fas fa-phone-volume me-2"></i><a href="tell:<?php echo esc_attr(get_theme_mod('hospital_management_topbar_phone_text')); ?>"><?php echo esc_html(get_theme_mod('hospital_management_topbar_phone_text')); ?></a></p>  
					        </div>
				        <?php }?>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-6 align-self-center">
						<?php if ( get_theme_mod('hospital_management_topbar_mail_text') != "" ) {?>
					        <div class=" text-center text-lg-end py-2">
					            <p class="location m-0"><i class="fas fa-envelope me-2"></i><a href="mailto:<?php echo esc_attr(get_theme_mod('hospital_management_topbar_mail_text')); ?>"><?php echo esc_html(get_theme_mod('hospital_management_topbar_mail_text')); ?></a></p>  
					        </div>
				        <?php }?>
					</div>
				</div>
			</div>
			
		</div>
	</div>
</div>