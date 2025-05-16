<?php
/*
Plugin Name: CustomKings Product Personalizer
Plugin URI: https://customkings.com.au/
Description: Powerful product personalization for WooCommerce. Visual designer, live preview, image uploads, print-ready files, and more.
Version: 1.1.2
Author: CustomKings
Author URI: https://customkings.com.au/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: customkings
Domain Path: /languages
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'CUSTOMKINGS_PLUGIN_FILE' ) ) {
    define( 'CUSTOMKINGS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'CKPP_VERSION' ) ) {
    define( 'CKPP_VERSION', '1.1.2' );
}

// Always load font management class so fonts are available on frontend
require_once plugin_dir_path( __FILE__ ) . 'admin/class-fonts.php';

// Main plugin class
class CustomKings_Product_Personalizer {
    public function __construct() {
        if ( is_admin() ) {
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin-ui.php';
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-clipart.php';
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-product-designer.php';
            new CKPP_Admin_UI();
            new CKPP_Fonts();
            new CKPP_Clipart();
            new CKPP_Product_Designer();
        }
        // Plugin initialization code will go here
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-frontend-customizer.php';
        new CKPP_Frontend_Customizer();
    }
}

// Initialize the plugin
new CustomKings_Product_Personalizer();

// Add accent color CSS variable to frontend and admin
add_action('admin_head', function() {
    $color = get_option('ckpp_accent_color', '#0073aa');
    if (!preg_match('/^#[a-f0-9]{3,6}$/i', $color)) $color = '#0073aa';
    echo '<style>:root{--ckpp-accent:' . esc_attr($color) . ';}</style>';
});
add_action('wp_head', function() {
    $color = get_option('ckpp_accent_color', '#0073aa');
    if (!preg_match('/^#[a-f0-9]{3,6}$/i', $color)) $color = '#0073aa';
    echo '<style>:root{--ckpp-accent:' . esc_attr($color) . ';}</style>';
}); 