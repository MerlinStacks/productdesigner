<?php
/*
Plugin Name: Custom Products
Plugin URI: https://example.com/custom-products
Description: A plugin to manage custom products within WordPress.
Version: 0.1.0
Author: Your Name
Author URI: https://example.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: custom-products
Domain Path: /languages
*/

// Basic security check to prevent direct access
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
// Register the admin menu for Product Personalizer
// Include the admin settings interface and class
require_once plugin_dir_path(__FILE__) . 'includes/admin/interface-product-personalizer-admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/class-product-personalizer-admin-settings.php';

// Initialize admin settings
function initialize_product_personalizer_admin_settings() {
    // Ensure the namespace is correct when instantiating
    $admin_settings = new \ProductPersonalizer\Admin\Product_Personalizer_Admin_Settings();
    $admin_settings->init();
}
add_action('plugins_loaded', 'initialize_product_personalizer_admin_settings');