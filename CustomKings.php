<?php
/*
Plugin Name: CustomKings Product Personalizer
Plugin URI: https://customkings.com.au/
Description: Product personalization and live preview for WooCommerce.
Version: 1.0.2
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
    define( 'CKPP_VERSION', '1.0.2' );
}

// Main plugin class
class CustomKings_Product_Personalizer {
    /**
     * Class instances that need to be kept track of
     */
    private $frontend_customizer = null;
    private $admin_ui = null;
    private $product_designer = null;
    
    public function __construct() {
        // Always load font management class so fonts are available on frontend
        require_once plugin_dir_path( __FILE__ ) . 'admin/class-fonts.php';
        new CKPP_Fonts();
        
        // Load frontend customizer for all contexts
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-frontend-customizer.php';
        $this->frontend_customizer = new CKPP_Frontend_Customizer();
        
        // Admin-specific initialization - only load the classes, register menus at the right time
        if (is_admin()) {
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin-ui.php';
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-clipart.php';
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-product-designer.php';
            
            // Instantiate Admin UI early so its admin-post hooks are registered
            $this->admin_ui = new CKPP_Admin_UI();
            // Instantiate Product Designer early so its AJAX hooks are registered
            $this->product_designer = new CKPP_Product_Designer();
            
            // Handle admin menu registration properly
            add_action('admin_menu', array($this, 'register_admin_menus'), 9); // Priority 9 to run before other admin_menu hooks
        }
    }
    
    /**
     * Register all admin menus in the proper order
     */
    public function register_admin_menus() {
        // Admin UI and Product Designer are already instantiated.
        // Their constructors handle their respective menu and AJAX hook registrations.
        
        // Initialize other admin classes that might be dependent on the main menu
        new CKPP_Clipart();
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

// Changelog:
// 1.0.2: Fix: Resolved issue where live preview image was not consistently displaying on cart and checkout pages. (2025-05-19)
// 1.0.1: Enhancement: Live preview now shows uploaded images in real time, replacing image placeholders with the actual uploaded image. 