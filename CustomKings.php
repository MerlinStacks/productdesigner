<?php
/*
Plugin Name: CustomKings Product Personalizer
Plugin URI: https://customkings.com.au/
Description: Product personalization and live preview for WooCommerce.
Version: 1.0.4
Author: CustomKings
Author URI: https://customkings.com.au/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: customkings
Domain Path: /languages
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('CUSTOMKINGS_PLUGIN_FILE')) {
    define('CUSTOMKINGS_PLUGIN_FILE', __FILE__);
}

if (!defined('CUSTOMKINGS_PLUGIN_DIR')) {
    define('CUSTOMKINGS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('CUSTOMKINGS_PLUGIN_URL')) {
    define('CUSTOMKINGS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('CKPP_VERSION')) {
    define('CKPP_VERSION', '1.0.4');
}

// Include required files
require_once CUSTOMKINGS_PLUGIN_DIR . 'includes/class-security.php';
require_once CUSTOMKINGS_PLUGIN_DIR . 'includes/class-cache.php';
require_once CUSTOMKINGS_PLUGIN_DIR . 'includes/class-db-optimizer.php';
require_once CUSTOMKINGS_PLUGIN_DIR . 'includes/class-error-handler.php';
require_once CUSTOMKINGS_PLUGIN_DIR . 'includes/class-config.php';

// Main plugin class
class CustomKings_Product_Personalizer {
    /**
     * Class instances that need to be kept track of
     */
    private $frontend_customizer = null;
    private $admin_ui = null;
    private $product_designer = null;
    
    public function __construct() {
        // Load plugin textdomain
        add_action('plugins_loaded', [$this, 'load_textdomain']);

        // Initialize security measures
        $this->init_security();
        
        // Initialize caching
        $this->init_caching();
        
        // Initialize database optimization
        $this->init_db_optimization();
        
        // Always load font management class so fonts are available on frontend
        // require_once CUSTOMKINGS_PLUGIN_DIR . 'admin/class-fonts.php';
        // new CKPP_Fonts();
        
        // Initialize WooCommerce integrations on plugins_loaded
        add_action('plugins_loaded', [$this, 'init_woocommerce_integrations']);
        
        // Admin-specific initialization - only load the classes, register menus at the right time
        add_action('plugins_loaded', [$this, 'init_admin_integrations']);

        // Initialize DB optimization hooks
        add_action('plugins_loaded', [$this, 'init_db_optimization_hooks']);
    }

    /**
     * Initialize WooCommerce integrations.
     */
    public function init_woocommerce_integrations() {
        if (class_exists('WooCommerce')) {
            require_once CUSTOMKINGS_PLUGIN_DIR . 'includes/class-frontend-customizer.php';
            $this->frontend_customizer = new CKPP_Frontend_Customizer();
        }
    }

    /**
     * Initialize admin-specific integrations.
     */
    public function init_admin_integrations() {
        if (is_admin() && class_exists('WooCommerce')) { // Ensure WooCommerce is loaded for admin classes
            require_once CUSTOMKINGS_PLUGIN_DIR . 'admin/class-admin-ui.php';
            require_once CUSTOMKINGS_PLUGIN_DIR . 'admin/class-clipart.php';
            require_once CUSTOMKINGS_PLUGIN_DIR . 'admin/class-product-designer.php';
            
            // Instantiate Admin UI early so its admin-post hooks are registered
            $this->admin_ui = new CKPP_Admin_UI();
            // Instantiate Product Designer early so its AJAX hooks are registered
            $this->product_designer = new CKPP_Product_Designer();
            
            // Handle admin menu registration properly
            add_action('admin_menu', [$this, 'register_admin_menus'], 9); // Priority 9 to run before other admin_menu hooks
            
            // Add settings link to plugins page
            add_filter('plugin_action_links_' . plugin_basename(CUSTOMKINGS_PLUGIN_FILE), [$this, 'add_settings_link']);
        }
    }

    /**
     * Register all admin menus in the proper order
     */
    /**
     * Load the plugin textdomain for internationalization.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'customkings',
            false,
            dirname(plugin_basename(CUSTOMKINGS_PLUGIN_FILE)) . '/languages/'
        );
    }
    /**
     * Initialize security measures
     */
    private function init_security() {
        // Set secure session cookie parameters
        
        // Add security headers
        add_action('send_headers', function() {
            if (!headers_sent()) {
                header('X-Content-Type-Options: nosniff');
                header('X-Frame-Options: SAMEORIGIN');
                header('X-XSS-Protection: 1; mode=block');
                
                if (is_ssl()) {
                    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
                }
            }
        });
    }
    
    /**
     * Initialize caching
     */
    private function init_caching() {
        // Initialize cache with default settings
        add_action('init', function() {
            // Clear cache on certain actions
            if (isset($_GET['clear_ckpp_cache'])) {
                CKPP_Security::verify_capability('manage_options');
                CKPP_Cache::clear();
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>CustomKings cache cleared successfully.</p></div>';
                });
            }
        });
    }
    
    /**
     * Initialize database optimization
     */
    private function init_db_optimization() {
        // Add manual optimization action
        add_action('wp_ajax_ckpp_optimize_database', function() {
            CKPP_Security::verify_capability('manage_options');
            CKPP_Security::verify_ajax_nonce('nonce', 'ckpp_admin_nonce');
            
            $results = CKPP_DB_Optimizer::optimize_tables();
            wp_send_json_success([
                'message' => 'Database optimized successfully',
                'results' => $results
            ]);
        });
    }

    /**
     * Initialize database optimization hooks.
     */
    public function init_db_optimization_hooks() {
        if (class_exists('WooCommerce')) { // Only add these hooks if WooCommerce is active
            // Adds custom cron schedules for WordPress.
            add_filter('cron_schedules', function(array $schedules): array {
                $schedules['monthly'] = array(
                    'interval' => 30 * DAY_IN_SECONDS,
                    'display' => __('Once Monthly', 'customkings')
                );
                return $schedules;
            });

            // Schedules the database optimization event when the plugin is loaded.
            CKPP_DB_Optimizer::schedule_optimization();
        }
    }
    
    /**
     * Register admin menus
     */
    public function register_admin_menus() {
        // Admin UI and Product Designer are already instantiated.
        // Their constructors handle their respective menu and AJAX hook registrations.
        
        // Initialize other admin classes that might be dependent on the main menu
        new CKPP_Clipart();
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=customkings-settings') . '">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
new CustomKings_Product_Personalizer();


// Add accent color CSS variable to frontend and admin
add_action('admin_head', function() {
    $color = CKPP_Config::get('general.accent_color', '#0073aa');
    if (!preg_match('/^#[a-f0-9]{3,6}$/i', $color)) $color = '#0073aa';
    echo '<style>:root{--ckpp-accent:' . esc_attr($color) . ';}</style>';
});
add_action('wp_head', function() {
    $color = CKPP_Config::get('general.accent_color', '#0073aa');
    if (!preg_match('/^#[a-f0-9]{3,6}$/i', $color)) $color = '#0073aa';
    echo '<style>:root{--ckpp-accent:' . esc_attr($color) . ';}</style>';
});

// Filter to suppress the _load_textdomain_just_in_time notice for WooCommerce and CustomKings.
// This is a temporary workaround and should be removed if a proper solution is found.
add_filter('doing_it_wrong_trigger_error', function($trigger_error, $function, $message, $version) {
    if ($function === '_load_textdomain_just_in_time' && (strpos($message, 'woocommerce') !== false || strpos($message, 'customkings') !== false)) {
        return false; // Suppress the error
    }
    return $trigger_error;
}, 10, 4);

// Changelog:
// 1.0.4: Security: Added comprehensive security measures including input validation, output escaping, and capability checks. Performance: Implemented caching and database optimization. (2025-05-20)
// 1.0.3: Fix: Removed scrollbars from designer interface and fixed font selection issues. Designer is now properly centered on the page regardless of WordPress sidebar. (2025-05-19)
// 1.0.2: Fix: Resolved issue where live preview image was not consistently displaying on cart and checkout pages. (2025-05-19)
// 1.0.1: Enhancement: Live preview now shows uploaded images in real time, replacing image placeholders with the actual uploaded image.