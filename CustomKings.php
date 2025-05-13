<?php
/*
Plugin Name: CustomKings Product Personalizer
Description: Allows customers to personalize products on WooCommerce with a visual admin designer, live preview, and print-ready file generation.
Version: 0.0.1
Author: CustomKings Personalised Gifts
Author URI: https://customkings.com.au/
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

// Main plugin class
class CustomKings_Product_Personalizer {
    public function __construct() {
        if ( is_admin() ) {
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin-ui.php';
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-fonts.php';
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-colors.php';
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-clipart.php';
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-product-designer.php';
            new CKPP_Admin_UI();
            new CKPP_Fonts();
            new CKPP_Colors();
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