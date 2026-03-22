=== Hospital Management ===
Contributors: TheMagnifico52
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 0.4.8
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Tags: wide-blocks, block-styles, custom-logo, one-column, two-columns, grid-layout, sticky-post, custom-background, custom-colors, custom-header, custom-menu, featured-images, flexible-header, threaded-comments, theme-options, left-sidebar, right-sidebar, full-width-template, editor-style, portfolio, education, blog, post-formats

== Description ==

Hospital Management is a user-friendly and visually appealing website template designed for healthcare facilities and medical professionals. With a clean and modern design, this theme provides an efficient platform to create a comprehensive online presence for hospitals, clinics, or medical practices. The theme’s intuitive interface allows easy customization without requiring extensive technical knowledge, making it accessible to a wide range of users. Its responsive design ensures a seamless experience for visitors across various devices, including smartphones and tablets. Key features include organized layouts for medical services, doctor profiles, appointment booking, and emergency contact information. These features enable healthcare providers to efficiently communicate essential details to patients, fostering a sense of trust and transparency. The Hospital Management WordPress Theme is particularly beneficial for showcasing medical services, introducing healthcare professionals, and providing crucial information such as operating hours and location. It simplifies the process of doctor appointments, enhancing the overall patient experience and streamlining administrative tasks for healthcare providers. Whether you're managing a clinic, dental practice, eyecare center, or pharmacy, this theme caters to the unique needs of the medical field. Hospital Management is a valuable tool for healthcare institutions looking to establish a professional online presence. Its user-friendly design and essential features cater to the unique needs of the medical field, providing a visually appealing and functional website that enhances communication with patients and promotes the services offered by healthcare providers. Whether it's for patient management, doctor booking, or offering therapy, surgery, and skincare services, this theme covers it all, ensuring your healthcare website is both comprehensive and accessible.

== Installation ==

1. In your admin panel, go to Appearance -> Themes and click the 'Add New' button.
2. Type in Hospital Management in the search form and press the 'Enter' key on your keyboard.
3. Click on the 'Activate' button to use your new theme right away.
4. Navigate to Appearance > Customize in your admin panel and customize to taste.

== Copyright ==

Hospital Management WordPress Theme, Copyright 2023 TheMagnifico52
Hospital Management is distributed under the terms of the GNU GPL

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

== Frequently Asked Questions ==

= Does this theme support any plugins? =

Hospital Management includes support for Infinite Scroll in Jetpack.

== Credits ==

Hospital Management bundles the following third-party resources:

Bootstrap, Copyright (c) 2011-2024 The Bootstrap Authors
**License:** MIT
Source: https://github.com/twbs/bootstrap

Font Awesome Free 5.6.3 by @fontawesome - https://fontawesome.com
 * License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License)

TGMPA
* GaryJones Copyright (C) 1989, 1991
* https://github.com/TGMPA/TGM-Plugin-Activation/blob/develop/LICENSE.md
* License: GNU General Public License v2.0

OwlCarousel
 * Copyright 2013-2017 David Deutsch
 * https://github.com/OwlCarousel2/OwlCarousel2
 * https://github.com/OwlCarousel2/OwlCarousel2/blob/develop/LICENSE

Bootstrap
 * Bootstrap v5.3.3 (https://getbootstrap.com/)
 * Copyright 2011-2024 The Bootstrap Authors
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)

PSR-4 autoloader
  * Justin Tadlock
  * License: https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
  * Source: https://github.com/WPTRT/autoload

Webfonts Loader
  * https://github.com/WPTT/webfont-loader
  * License: https://github.com/WPTT/webfont-loader/blob/master/LICENSE

CustomizeSectionButton
  * Justin Tadlock
  * Copyright 2019, Justin Tadlock.
  * License: https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
  * https://github.com/WPTRT/customize-section-button

Pxhere Images,
	License: CC0 1.0 Universal (CC0 1.0)
	Source: https://pxhere.com/en/license

	License: CC0 1.0 Universal (CC0 1.0)
	Source: https://pxhere.com/en/photo/1194636

  License: CC0 1.0 Universal (CC0 1.0)
  Source: https://pxhere.com/en/photo/1446755


== Changelog ==

= 0.4.8 =

* Fixed malformed HTML closing tags in admin notice (h2, p).
* Fixed missing output escaping: replaced echo wp_get_theme() with esc_html(), _e() with esc_html_e().
* Fixed security: added check_ajax_referer() and current_user_can() to tm_install_and_activate_plugin(), tm_check_plugin_exists(), demo_importer_ajax_handler() AJAX handlers.
* Fixed security: added nonce verification to hospital_management_dismissable_notice() and hospital_management_ajax_notice_handler().
* Fixed security: added nonce verification and capability check to hospital_management_update_recommended_action_callback() in theme-installation.php.
* Fixed sanitization of $_POST inputs in AJAX handlers using sanitize_key(), sanitize_file_name(), wp_unslash().
* Fixed incorrect esc_attr() usage with multiple arguments in comment date/time display in template-tags.php.
* Fixed esc_url() incorrectly applied to get_comment_author_link() HTML output in template-tags.php.
* Fixed orphaned </div> in index.php when no posts are found.
* Fixed scroll-to-top anchor missing href attribute in footer.php.
* Fixed deprecated get_page_by_title() replaced with get_posts() per WordPress 6.2 handbook.
* Fixed key name mismatch: nr_actions_recommended -> count_actions_recommended in theme-installation localize script.
* Replaced console.log() calls in theme-installation.js with silent error handling.
* Added nonce to recommend-action AJAX call in theme-installation.js.
* Replaced die() with wp_die() in AJAX handler functions.
* Fixed search query value missing esc_attr() in search form (mainheader.php).

= 0.4.7

* Added pro button in global setting.
* Added section heading in global setting.
* Added Pro button in all section.
* Added secion heading in all section.
* Added 404 settings.
* Updated 404 page.
* Added sticky header.
* Added post settings.
* Added single post settings.

= 0.4.6

* Made some changes.

= 0.4.5

* Added post layouts settings option.
* Updated activation notice.
* Updated getstarted.
* Resolved permalink issue.
* Resolved error for global color.
* Resolved error for menu pages in demo import.
* Resolved error for blog page pagination type option.
* Resolved error for single post page sidebar.
* Resolved error for single page sidebar.
* Resolved error for shop page sidebar.
* Resolved error for single product page sidebar.

= 0.4.4

* Made some changes.

= 0.4.3

* Made some changes.

= 0.4.2

* Added bundle notice.

= 0.4.1

* Update 404 page.
* Added 404 page setting.

= 0.4.0

* Minor fixes and improvements.

= 0.3.9

* Added woocommerce related product column setting.
* Added woocommerce related product per page setting.
* Added woocommerce related product show setting.

= 0.3.8

* Updated Getstart.
* Added some Getstart files.

= 0.3.7

* Added menu CSS.
* Updated sidebar CSS.
* Added default image on single page.
* Added default sidebar.
* Added default CSS.
* Resolved textdomain error.

= 0.3.6

* Added footer background color option in customizer.
* Added footer content color option in customizer.
* Added copyright text color option in customizer.
* Added single page sidebar layout option in customizer.
* Updated POT file.

= 0.3.5

* Added blog page pagination type option in customizer.
* Added number of posts per row option in customizer.
* Added blog page sidebar position option in customizer.
* Added single post sidebar position option in customizer.
* Added show/hide single post tag option in customizer.
* Updated POT file.

= 0.3.4

* Added preloader type option in customizer.
* Added preloader color option in customizer.
* Added show/hide slider image opacity option in customizer.
* Added slider image opacity color option in customizer.
* Added slider image opacity option in customizer.
* Updated POT file.

= 0.3.3

* Added menu font size option in customizer.
* Added menu font weight option in customizer.
* Added slider content layout option in customizer.
* Added slider excerpt length option in customizer.
* Updated POT file.

= 0.3.2

* Added blog page excerpt length option in customizer.
* Added blog page excerpt suffix option in customizer.
* Added show/hide blog page navigation in customizer.
* Added demo importer.
* Updated POT file.

= 0.3.1

* Added scroll to top background color option in customizer.
* Added scroll to top color option in customizer.
* Added scroll to top font size option in customizer.
* Added scroll to top border radius option in customizer.
* Updated POT file.

= 0.3

* Added product per page setting.
* Added show/hide single product page sidebar option in customizer.
* Added single product page sidebar layout option in customizer.
* Added show/hide shop page sidebar option in customizer.
* Added shop page sidebar layout option in customizer.

= 0.2.9

* Added typography option in customizer.
* Added POT file.
* Added description.

= 0.2.8

* Added global color option in customizer.
* Added site width layout option in customizer.
* Added nav menus text transform option in customizer.

= 0.2.7

* Added copyright content alignment option in customizer.
* Added show/hide post page content option in customizer.  
* Added footer content alignment option in customizer.
* Added show/hide single post page content option in customizer.

= 0.2.6

* Added single post page image border radius option in customizer.
* Added single post page image box shadow option in customizer.
* Added footer background image option in customizer.
* Added footer widget heading alignment option in customizer.
* Added footer background image position option in customizer.

= 0.2.5

* Updated archive product template.
* Added default image in archive post.
* Updated comment CSS.
* Updated PHP version.
* Updated URLs.

= 0.2.3

* Added default image for slider.
* Added default size for slider in customizer.
* Added CSS for slider height.
* Added latest Bootstrap version.
* Added free doc button in Get Started.
* Added CSS in media for container width.
* Added CSS for submenu.
* Replaced Get Start function.
* Replaced admin JS.
* Added search in header.
* Done responsive.
* Done homepage CSS.

= 0.2.2

* Added upsell button in different sections in customizer.
* Done shop page product CSS in responsive media.
* Done search form and search page CSS.

= 0.2.1

* Added on/off footer copyright option in footer text section.
* Added footer credit link.
* Added post settings in customizer.
* Added post page thumbnail on/off setting in customizer.
* Added post page title on/off setting in customizer.
* Added post page meta on/off setting in customizer.

= 0.2

* Resolved off-canvas menu issue.

= 0.1

* Initial release.