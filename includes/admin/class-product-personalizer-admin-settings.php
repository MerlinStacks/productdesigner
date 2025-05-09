<?php
/**
 * Admin Settings Class
 *
 * Handles admin menu registration and settings page display.
 *
 * @package ProductPersonalizer
 * @subpackage Admin
 * @since 1.0.0
 */

namespace ProductPersonalizer\Admin;

// Ensure the interface is available. If it's in the same namespace and autoloaded, this is fine.
// Or, include it if necessary: require_once __DIR__ . '/interface-product-personalizer-admin-settings.php';
// For now, assume autoloader or correct include structure handles this.

class Product_Personalizer_Admin_Settings implements Product_Personalizer_Admin_Settings_Interface {
    /**
     * Settings array
     *
     * @var array
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_post_product_personalizer_add_font', array($this, 'handle_add_font_submission'));
    }
    
    /**
     * Initialize the admin settings
     */
    public function init() {
        $this->settings = $this->get_settings();
    }
    
    /**
     * Register the admin menu
     */
    public function register_menu() {
        add_menu_page(
            'Product Personalizer Settings',
            'Product Personalizer',
            'manage_options',
            'product-personalizer-settings',
            array($this, 'display_settings_page'),
            'dashicons-admin-customizer',
            58
        );
    }
    
    /**
     * Display the settings page
     * This method's content needs to be populated with the logic
     * from the `display_product_personalizer_settings()` function
     * currently in `custom-products.php`.
     */
    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h2>Product Personalizer</h2>
            <nav class="nav-tab-wrapper">
                <a href="#modes-global-settings" class="nav-tab nav-tab-active" data-tab="modes-global-settings">Modes & Global Settings</a>
                <a href="#fonts" class="nav-tab" data-tab="fonts">Fonts</a>
                <a href="#color-swatches" class="nav-tab" data-tab="color-swatches">Color Swatches</a>
                <a href="#clipart" class="nav-tab" data-tab="clipart">Clipart</a>
            </nav>

            <div id="tab-content-modes-global-settings" class="tab-content" style="display: block;">
                <h3>Modes & Global Settings</h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Debug Mode</th>
                        <td>
                            <label for="debug_mode">
                                <input type="checkbox" name="debug_mode" id="debug_mode" value="1" <?php checked(get_option('product_personalizer_debug_mode'), 1); ?> />
                                Enable Debug Mode
                            </label>
                            <p class="description">When enabled, this will output additional debugging information.</p>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="tab-content-fonts" class="tab-content" style="display: none;">
                <h3>Fonts</h3>
                <p>Manage font settings here.</p>
                <div class="add-new-font-form">
                    <h3>Add New Font</h3>
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('add_new_font_action', 'add_new_font_nonce'); ?>
                        <input type="hidden" name="action" value="product_personalizer_add_font">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><label for="font_name">Font Name/Family</label></th>
                                <td><input type="text" name="font_name" id="font_name" value="" class="regular-text" required /></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="font_weight">Font Weight</label></th>
                                <td>
                                    <select name="font_weight" id="font_weight">
                                        <option value="normal">Normal</option>
                                        <option value="bold">Bold</option>
                                        <?php for ($i = 100; $i <= 900; $i += 100) : ?>
                                            <option value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="font_style">Font Style</label></th>
                                <td>
                                    <select name="font_style" id="font_style">
                                        <option value="normal">Normal</option>
                                        <option value="italic">Italic</option>
                                    </select>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="font_source">Source</label></th>
                                <td>
                                    <select name="font_source" id="font_source">
                                        <option value="google">Google</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </td>
                            </tr>
                            <tr valign="top" class="custom-font-file-row">
                                <th scope="row"><label for="custom_font_file">Custom Font File</label></th>
                                <td><input type="file" name="custom_font_file" id="custom_font_file" /></td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Add Font">
                        </p>
                    </form>
                </div>

                <?php
                // Instantiate AssetManager
                $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();

                // Fetch fonts
                $fonts = $asset_manager->get_fonts();

                if (!empty($fonts)) {
                    ?>
                    <h3>Existing Fonts</h3>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th>Font Name/Family</th>
                                <th>Style</th>
                                <th>Weight</th>
                                <th>Source</th>
                                <th>File URL</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fonts as $font) : ?>
                                <tr>
                                    <td><?php echo esc_html($font['font_family']); ?></td>
                                    <td><?php echo esc_html($font['font_style']); ?></td>
                                    <td><?php echo esc_html($font['font_weight']); ?></td>
                                    <td><?php echo esc_url($font['source']); ?></td>
                                    <td><?php echo esc_url($font['file_url']); ?></td>
                                    <td>
                                        <a href="#" class="button button-small">Edit</a>
                                        <a href="#" class="button button-small button-danger">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php
                } else {
                    ?>
                    <p>No fonts added yet.</p>
                    <?php
                }
                ?>
            </div>
            <div id="tab-content-color-swatches" class="tab-content" style="display: none;">
                <h3>Color Swatches</h3>
                <p>Manage color swatches here.</p>
                <?php // TEST: Ensure Color Swatches tab content area is present ?>
            </div>
            <div id="tab-content-clipart" class="tab-content" style="display: none;">
                <h3>Clipart</h3>
                <p>Manage clipart assets here.</p>
                <?php // TEST: Ensure Clipart tab content area is present ?>
            </div>
        </div>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.nav-tab-wrapper .nav-tab');
                const tabContents = document.querySelectorAll('.tab-content');

                tabs.forEach(tab => {
                    tab.addEventListener('click', function(event) {
                        event.preventDefault();

                        // Deactivate all tabs and hide all content
                        tabs.forEach(t => t.classList.remove('nav-tab-active'));
                        tabContents.forEach(content => content.style.display = 'none');

                        // Activate clicked tab and show its content
                        this.classList.add('nav-tab-active');
                        const activeTabContentId = 'tab-content-' + this.getAttribute('data-tab');
                        const activeTabContent = document.getElementById(activeTabContentId);
                        if (activeTabContent) {
                            activeTabContent.style.display = 'block';
                        }
                        // TEST: Ensure clicking a tab activates it and shows its content.
                        // TEST: Ensure only one tab is active at a time.
                        // TEST: Ensure inactive tab content is hidden.
                    });
                });
            });
        </script>
        <?php
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'product_personalizer_settings_group', // Changed group name for clarity
            'product_personalizer_debug_mode',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false,
            )
        );
        // Register other settings here
    }
    
    /**
     * Render content for a specific tab (placeholder based on architect plan)
     *
     * @param string $tab Tab ID
     */
    public function render_tab_content($tab) {
        // This method will be used by display_settings_page later.
        // For now, it's a structural placeholder.
        echo '<p>Content for tab: ' . esc_html($tab) . '</p>';
    }
    
    /**
     * Save settings (placeholder based on architect plan)
     *
     * @param array $settings Settings to save
     * @return bool Success status
     */
    public function save_settings(array $settings): bool {
        // Actual saving logic for settings not managed by register_setting will go here.
        // For 'product_personalizer_debug_mode', WordPress handles it via register_setting.
        return true;
    }
    
    /**
     * Get current settings
     *
     * @return array Current settings
     */
    public function get_settings(): array {
        $settings = array(
            'debug_mode' => get_option('product_personalizer_debug_mode', false),
        );
        return $settings;
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_product-personalizer-settings' !== $hook) {
            return;
        }
        
        // Ensure asset paths are correct relative to this file's location.
        // plugin_dir_url(__FILE__) from `includes/admin/` will be `.../wp-content/plugins/your-plugin/includes/admin/`
        // So `../../assets/css/admin.css` becomes `.../wp-content/plugins/your-plugin/assets/css/admin.css`
        wp_enqueue_style(
            'product-personalizer-admin-styles', // Unique handle
            plugin_dir_url(__FILE__) . '../../assets/css/admin.css',
            array(),
            '1.0.0' // Consider using a plugin version constant
        );
        
        wp_enqueue_script(
            'product-personalizer-admin-scripts', // Unique handle
            plugin_dir_url(__FILE__) . '../../assets/js/admin.js',
            array('jquery'),
            '1.0.0', // Consider using a plugin version constant
            true
        );
    }

    /**
     * Handles the submission of the "Add New Font" form.
     */
    public function handle_add_font_submission() {
        // 1. Security Checks
        if (!isset($_POST['add_new_font_nonce']) || !wp_verify_nonce($_POST['add_new_font_nonce'], 'add_new_font_action')) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action.');
        }

        // 2. Data Retrieval & Sanitization
        $font_name = isset($_POST['font_name']) ? sanitize_text_field($_POST['font_name']) : '';
        $font_weight = isset($_POST['font_weight']) ? sanitize_text_field($_POST['font_weight']) : '';
        $font_style = isset($_POST['font_style']) ? sanitize_text_field($_POST['font_style']) : '';
        $font_source = isset($_POST['font_source']) ? sanitize_text_field($_POST['font_source']) : '';
        $font_file = isset($_FILES['custom_font_file']) ? $_FILES['custom_font_file'] : null;

        // Basic validation
        if (empty($font_name) || empty($font_source)) {
            add_settings_error(
                'product-personalizer-settings',
                'font_add_error',
                'Font Name and Source are required.',
                'error'
            );
            $this->redirect_to_fonts_tab();
            return;
        }

        // 3. Call AssetManager
        try {
            $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
            $result = $asset_manager->upload_asset(
                [
                    'name' => $font_name,
                    'type' => 'font', // Assuming 'font' is the correct type for AssetManager
                    'metadata' => [
                        'weight' => $font_weight,
                        'style' => $font_style,
                        'source' => $font_source,
                    ],
                ],
                $font_file
            );

            // 4. Feedback & Redirect
            if (is_wp_error($result)) {
                add_settings_error(
                    'product-personalizer-settings',
                    'font_add_error',
                    'Failed to add font. Error: ' . $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'product-personalizer-settings',
                    'font_add_success',
                    'Font added successfully.',
                    'success'
                );
            }
        } catch (\Exception $e) {
            add_settings_error(
                'product-personalizer-settings',
                'font_add_error',
                'An unexpected error occurred: ' . $e->getMessage(),
                'error'
            );
        }

        $this->redirect_to_fonts_tab();
    }

    /**
     * Redirects the user back to the fonts tab of the settings page.
     */
    private function redirect_to_fonts_tab() {
        $redirect_url = admin_url('admin.php?page=product-personalizer-settings&tab=fonts');
        settings_errors(); // Display any accumulated settings errors before redirect
        wp_redirect($redirect_url);
        exit;
    }
}