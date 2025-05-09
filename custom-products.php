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
function register_product_personalizer_menu() {
    add_menu_page(
        'Product Personalizer Settings', // $page_title
        'Product Personalizer',          // $menu_title
        'manage_options',                // $capability
        'product-personalizer-settings', // $menu_slug
        'display_product_personalizer_settings', // $function
        'dashicons-admin-customizer',    // $icon_url
        58                               // $position
    );
}
add_action('admin_menu', 'register_product_personalizer_menu');

/**
 * Placeholder callback function for the Product Personalizer page.
 */
function display_product_personalizer_settings() {
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
            <?php // TEST: Ensure Fonts tab content area is present ?>
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

// Main plugin class or initialization code can be added here in future