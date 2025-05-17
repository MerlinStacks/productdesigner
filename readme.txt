=== CustomKings Product Personalizer ===
Contributors: customkings
Tags: woocommerce, personalization, product designer, customizer, print-ready
Requires at least: 5.8
Tested up to: 6.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
A powerful WooCommerce plugin that allows customers to personalize products with a visual admin designer, live preview, and print-ready file generation.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/customkings-product-personalizer` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin settings via the new 'Product Personalizer' menu in the WordPress admin.

== Frequently Asked Questions ==
= Is this plugin accessible? =
Yes, all modals and forms are keyboard accessible and use ARIA roles/labels.

= Can I add my own fonts, colors, and clipart? =
Yes, the admin UI allows you to upload and manage fonts, color swatches, and clipart.

= Does it support print-ready file generation? =
Yes, admins can generate and download print-ready PDF files for each order item.

== Screenshots ==
1. Admin product designer UI
2. Customer personalization modal
3. Order view with personalization preview

== Changelog ==
= 1.1.4 – 2025-06-09 =
* Design cards in the admin now show live preview thumbnails. No more broken image icons—previews update instantly when you save a design.
* Modern, accessible card grid for designs. Cleaner, more robust logic for preview images.
* Fixed double/duplicate admin pages and menu items.
* Fixed redirect logic for creating and deleting designs.
* Fixed saving and loading of design previews.
* Improved reliability of AJAX save/load for designer.
* Codebase cleanup: removed legacy files and duplicate logic. All admin UI now lives in a single, modern code path.

= 1.1.2 – 2025-05-17 =
* Reliable image deletion from the Images admin page (no more blank screens or errors).
* Personalization details (including uploaded images) always show on cart and checkout pages.
* Updated to use the correct WooCommerce order item meta hook for compatibility.
* Bug fixes: no duplicate field labels, improved admin error handling, minor accessibility and UI improvements.

= 1.1.1 - 2025-05-16 =
* New: You can now change all plugin accent colors from the settings page using a modern color picker (supports HEX and RGB).
* Improved: Layers panel in the designer is now more modern and user-friendly, with a clean bar layout and better icons.
* Improved: Images admin area now uses a visual grid layout, just like the Clipart section, making it easier to browse and manage uploads.
* Improved: Changelog and documentation are now more user-friendly and easier to read.
* Fixed: Various small bugs and accessibility improvements throughout the admin UI.

= 1.0.0 =
* Initial full-featured release: admin designer, customer customizer, order view, print-ready files, accessibility, and tests.

= 1.1.3 – 2025-05-18 =
* Accent color picker UI is now clean and accessible: only one preview box, a dedicated button, and no duplicate or hidden color boxes.
* Preview box is keyboard accessible and can be clicked to open the color picker.
* All debug/test text and extra UI elements have been removed from the color picker UI.
* Bug fixes for Pickr overlay, preview, and CSS issues.

= 1.0.1 =
* Enhancement: Live preview now shows uploaded images in real time, replacing image placeholders with the actual uploaded image.

== Upgrade Notice ==
= 1.0.0 =
First stable release. 