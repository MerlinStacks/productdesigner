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
        add_action('admin_post_product_personalizer_delete_font', array($this, 'handle_delete_font_action'));
        add_action('admin_post_product_personalizer_update_font', array($this, 'handle_update_font_submission'));
        add_action('admin_post_product_personalizer_add_color_swatch', array($this, 'handle_add_color_swatch_submission'));
        add_action('admin_post_product_personalizer_delete_color_swatch', array($this, 'handle_delete_color_swatch_action'));
add_action('admin_post_product_personalizer_update_color_swatch', array($this, 'handle_update_color_swatch_submission'));
        add_action('admin_post_product_personalizer_add_clipart', array($this, 'handle_add_clipart_submission'));
        add_action('admin_post_product_personalizer_delete_clipart', array($this, 'handle_delete_clipart_action'));
        add_action('admin_post_product_personalizer_update_clipart', array($this, 'handle_update_clipart_submission'));

        // Add WooCommerce product data tabs and panels
        add_filter('woocommerce_product_data_tabs', array($this, 'add_personalization_product_data_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'display_personalization_product_data_panel'));

        // AJAX handler for saving personalization config
        add_action('wp_ajax_product_personalizer_save_config', array($this, 'ajax_save_personalization_config'));
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

                <?php
                $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
                $font_id = isset($_GET['font_id']) ? absint($_GET['font_id']) : 0;
                $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();

                if ($action === 'edit_font' && $font_id > 0) {
                    $fonts_to_edit = $asset_manager->get_fonts(['id' => $font_id]);
                    $font_to_edit = !empty($fonts_to_edit) ? $fonts_to_edit[0] : null;

                    if ($font_to_edit) {
                        // Display Edit Font Form
                        ?>
                        <div class="edit-font-form">
                            <h3>Edit Font: <?php echo esc_html($font_to_edit['font_family']); ?></h3>
                            <form method="post" enctype="multipart/form-data">
                                <?php wp_nonce_field('update_font_action_' . $font_id, 'update_font_nonce'); ?>
                                <input type="hidden" name="action" value="product_personalizer_update_font">
                                <input type="hidden" name="font_id" value="<?php echo esc_attr($font_id); ?>">
                                <table class="form-table">
                                    <tr valign="top">
                                        <th scope="row"><label for="font_name">Font Name/Family</label></th>
                                        <td><input type="text" name="font_name" id="font_name" value="<?php echo esc_attr($font_to_edit['font_family']); ?>" class="regular-text" required /></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="font_weight">Font Weight</label></th>
                                        <td>
                                            <select name="font_weight" id="font_weight">
                                                <option value="normal" <?php selected($font_to_edit['font_weight'], 'normal'); ?>>Normal</option>
                                                <option value="bold" <?php selected($font_to_edit['font_weight'], 'bold'); ?>>Bold</option>
                                                <?php for ($i = 100; $i <= 900; $i += 100) : ?>
                                                    <option value="<?php echo esc_attr($i); ?>" <?php selected($font_to_edit['font_weight'], $i); ?>><?php echo esc_html($i); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="font_style">Font Style</label></th>
                                        <td>
                                            <select name="font_style" id="font_style">
                                                <option value="normal" <?php selected($font_to_edit['font_style'], 'normal'); ?>>Normal</option>
                                                <option value="italic" <?php selected($font_to_edit['font_style'], 'italic'); ?>>Italic</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="font_source">Source</label></th>
                                        <td>
                                            <select name="font_source" id="font_source">
                                                <option value="google" <?php selected($font_to_edit['source'], 'google'); ?>>Google</option>
                                                <option value="custom" <?php selected($font_to_edit['source'], 'custom'); ?>>Custom</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php if ($font_to_edit['source'] === 'custom') : ?>
                                    <tr valign="top" class="custom-font-file-row">
                                        <th scope="row"><label for="custom_font_file">Custom Font File (Leave empty to keep existing)</label></th>
                                        <td><input type="file" name="custom_font_file" id="custom_font_file" /></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                                <p class="submit">
                                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Update Font">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=product-personalizer-settings&tab=fonts')); ?>" class="button button-secondary">Cancel</a>
                                </p>
                            </form>
                        </div>
                        <?php
                    } else {
                        // Font not found error
                        ?>
                        <div class="error">
                            <p>Error: Font with ID <?php echo esc_html($font_id); ?> not found.</p>
                            <p><a href="<?php echo esc_url(admin_url('admin.php?page=product-personalizer-settings&tab=fonts')); ?>">Back to Fonts List</a></p>
                        </div>
                        <?php
                    }
                } else {
                    // Display Add New Font Form and Existing Fonts List
                    ?>
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
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=product-personalizer-settings&tab=fonts&action=edit_font&font_id=' . $font['id'])); ?>" class="button button-small">Edit</a>
                                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=product_personalizer_delete_font&font_id=' . $font['id'] . '&_wpnonce=' . wp_create_nonce('delete_font_' . $font['id']))); ?>" class="button button-small button-danger">Delete</a>
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
                }
                ?>
            </div>
            <div id="tab-content-color-swatches" class="tab-content" style="display: none;">
                <h3>Color Swatches</h3>
                <p>Manage color swatches here.</p>

                 <?php
                 $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
                 $color_swatch_id = isset($_GET['color_swatch_id']) ? absint($_GET['color_swatch_id']) : 0;
                 $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();

                 if ($action === 'edit_color_swatch' && $color_swatch_id > 0) {
                     $swatches_to_edit = $asset_manager->get_color_swatches(['id' => $color_swatch_id]);
                     $swatch_to_edit = !empty($swatches_to_edit) ? $swatches_to_edit[0] : null;

                     if ($swatch_to_edit) {
                         // Display Edit Color Swatch Form
                         ?>
                         <div class="edit-color-swatch-form">
                             <h3>Edit Color Swatch: <?php echo esc_html($swatch_to_edit['label']); ?></h3>
                             <form method="post">
                                 <?php wp_nonce_field('update_color_swatch_action_' . $color_swatch_id, 'update_color_swatch_nonce'); ?>
                                 <input type="hidden" name="action" value="product_personalizer_update_color_swatch">
                                 <input type="hidden" name="color_swatch_id" value="<?php echo esc_attr($color_swatch_id); ?>">
                                 <table class="form-table">
                                     <tr valign="top">
                                         <th scope="row"><label for="color_swatch_label">Label</label></th>
                                         <td><input type="text" name="color_swatch_label" id="color_swatch_label" value="<?php echo esc_attr($swatch_to_edit['label']); ?>" class="regular-text" required /></td>
                                     </tr>
                                     <tr valign="top">
                                         <th scope="row"><label for="color_swatch_hex">Hex Code</label></th>
                                         <td>
                                             <input type="text" name="color_swatch_hex" id="color_swatch_hex" value="<?php echo esc_attr($swatch_to_edit['hex_code']); ?>" class="regular-text" required />
                                             <input type="color" id="color_swatch_hex_picker" value="<?php echo esc_attr($swatch_to_edit['hex_code']); ?>" style="vertical-align: middle;" />
                                             <p class="description">Enter the hex code or use the color picker.</p>
                                         </td>
                                     </tr>
                                 </table>
                                 <p class="submit">
                                     <input type="submit" name="submit" id="submit" class="button button-primary" value="Update Color Swatch">
                                     <a href="<?php echo esc_url(admin_url('admin.php?page=product-personalizer-settings&tab=color-swatches')); ?>" class="button button-secondary">Cancel</a>
                                 </p>
                             </form>
                         </div>
                         <?php
                     } else {
                         // Color swatch not found error
                         ?>
                         <div class="error">
                             <p>Error: Color Swatch with ID <?php echo esc_html($color_swatch_id); ?> not found.</p>
                             <p><a href="<?php echo esc_url(admin_url('admin.php?page=product-personalizer-settings&tab=color-swatches')); ?>">Back to Color Swatches List</a></p>
                         </div>
                         <?php
                     }
                 } else {
                     // Display Add New Color Swatch Form and Existing Color Swatches List
                     ?>
                     <div class="add-new-color-swatch-form">
                         <h3>Add New Color Swatch</h3>
                         <form method="post">
                             <?php wp_nonce_field('add_new_color_swatch_action', 'add_new_color_swatch_nonce'); ?>
                             <input type="hidden" name="action" value="product_personalizer_add_color_swatch">
                             <table class="form-table">
                                 <tr valign="top">
                                     <th scope="row"><label for="color_swatch_label">Label</label></th>
                                     <td><input type="text" name="color_swatch_label" id="color_swatch_label" value="" class="regular-text" required /></td>
                                 </tr>
                                 <tr valign="top">
                                     <th scope="row"><label for="color_swatch_hex">Hex Code</label></th>
                                     <td>
                                         <input type="text" name="color_swatch_hex" id="color_swatch_hex" value="" class="regular-text" required />
                                         <input type="color" id="color_swatch_hex_picker" value="#000000" style="vertical-align: middle;" />
                                         <p class="description">Enter the hex code or use the color picker.</p>
                                     </td>
                                 </tr>
                             </table>
                             <p class="submit">
                                 <input type="submit" name="submit" id="submit" class="button button-primary" value="Add Color Swatch">
                             </p>
                         </form>
                     </div>

                     <?php
                     $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
                     $color_swatches = $asset_manager->get_color_swatches();

                     if (!empty($color_swatches)) {
                     	?>
                     	<h3>Existing Color Swatches</h3>
                     	<table class="wp-list-table widefat striped">
                     		<thead>
                     			<tr>
                     				<th>Label</th>
                     				<th>Hex Code</th>
                     				<th>Actions</th>
                     			</tr>
                     		</thead>
                     		<tbody>
                     			<?php foreach ($color_swatches as $swatch) : ?>
                     				<tr>
                     					<td><?php echo esc_html($swatch['label']); ?></td>
                     					<td>
                     						<span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo esc_attr($swatch['hex_code']); ?>; vertical-align: middle; margin-right: 5px; border: 1px solid #ccc;"></span>
                     						<?php echo esc_html($swatch['hex_code']); ?>
                     					</td>
                     					<td>
                     						<a href="<?php echo esc_url(admin_url('admin.php?page=product-personalizer-settings&tab=color-swatches&action=edit_color_swatch&color_swatch_id=' . $swatch['id'])); ?>" class="button button-small">Edit</a>
                     						<a href="<?php echo esc_url(admin_url('admin-post.php?action=product_personalizer_delete_color_swatch&color_swatch_id=' . $swatch['id'] . '&_wpnonce=' . wp_create_nonce('delete_color_swatch_' . $swatch['id']))); ?>" class="button button-small button-danger">Delete</a>
                     					</td>
                     				</tr>
                     			<?php endforeach; ?>
                     		</tbody>
                     	</table>
                     	<?php
                     } else {
                     	?>
                     	<p>No color swatches added yet.</p>
                     	<?php
                     }
                     } // Closes the main else block for color swatches (add/list view)
                     ?>
                    </div>
            <div id="tab-content-clipart" class="tab-content" style="display: none;">
                <h3>Clipart</h3>
                <p>Manage clipart assets here.</p>
                <?php
                $clipart_action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
                $clipart_id_to_edit = isset($_GET['clipart_id']) ? absint($_GET['clipart_id']) : 0;
                $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();

                if ($clipart_action === 'edit_clipart' && $clipart_id_to_edit > 0) {
                    $clipart_items_to_edit = $asset_manager->get_clipart(['id' => $clipart_id_to_edit]);
                    $clipart_to_edit = !empty($clipart_items_to_edit) ? $clipart_items_to_edit[0] : null;

                    if ($clipart_to_edit) {
                        // Display Edit Clipart Form
                        ?>
                        <div class="edit-clipart-form">
                            <h3>Edit Clipart: <?php echo esc_html($clipart_to_edit['name']); ?></h3>
                            <form method="post" enctype="multipart/form-data">
                                <?php wp_nonce_field('update_clipart_action_' . $clipart_id_to_edit, 'update_clipart_nonce'); ?>
                                <input type="hidden" name="action" value="product_personalizer_update_clipart">
                                <input type="hidden" name="clipart_id" value="<?php echo esc_attr($clipart_id_to_edit); ?>">
                                <table class="form-table">
                                    <tr valign="top">
                                        <th scope="row"><label for="edit_clipart_name">Name/Label</label></th>
                                        <td><input type="text" name="clipart_name" id="edit_clipart_name" value="<?php echo esc_attr($clipart_to_edit['name']); ?>" class="regular-text" required /></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="edit_clipart_category">Category</label></th>
                                        <td><input type="text" name="clipart_category" id="edit_clipart_category" value="<?php echo esc_attr($clipart_to_edit['category'] ?? ''); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="edit_clipart_tags">Tags (comma-separated)</label></th>
                                        <td><input type="text" name="clipart_tags" id="edit_clipart_tags" value="<?php echo esc_attr(is_array($clipart_to_edit['tags']) ? implode(', ', $clipart_to_edit['tags']) : ($clipart_to_edit['tags'] ?? '')); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row">Current Image</th>
                                        <td>
                                            <?php if (!empty($clipart_to_edit['image_url'])) : ?>
                                                <img src="<?php echo esc_url($clipart_to_edit['image_url']); ?>" alt="<?php echo esc_attr($clipart_to_edit['name']); ?>" style="max-width: 100px; height: auto; border: 1px solid #ddd; padding: 5px;" />
                                            <?php else : ?>
                                                <p>No image available.</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="edit_clipart_file">Replace Clipart File (Leave empty to keep existing)</label></th>
                                        <td><input type="file" name="clipart_file" id="edit_clipart_file" /></td>
                                    </tr>
                                </table>
                                <p class="submit">
                                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Update Clipart">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=product-personalizer-settings&tab=clipart')); ?>" class="button button-secondary">Cancel</a>
                                </p>
                            </form>
                        </div>
                        <?php
                    } else {
                        // Clipart not found error
                        ?>
                        <div class="error">
                            <p>Error: Clipart with ID <?php echo esc_html($clipart_id_to_edit); ?> not found.</p>
                            <p><a href="<?php echo esc_url(admin_url('admin.php?page=product-personalizer-settings&tab=clipart')); ?>">Back to Clipart List</a></p>
                        </div>
                        <?php
                    }
                } else {
                    // Display Add New Clipart Form and Existing Clipart List
                    $clipart_assets = $asset_manager->get_clipart();
                ?>
                <h3>Add New Clipart</h3>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('add_new_clipart_action', 'add_new_clipart_nonce'); ?>
                    <input type="hidden" name="action" value="product_personalizer_add_clipart">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><label for="clipart_name">Name/Label</label></th>
                            <td><input type="text" name="clipart_name" id="clipart_name" value="" class="regular-text" required /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="clipart_category">Category</label></th>
                            <td><input type="text" name="clipart_category" id="clipart_category" value="" class="regular-text" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="clipart_tags">Tags (comma-separated)</label></th>
                            <td><input type="text" name="clipart_tags" id="clipart_tags" value="" class="regular-text" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="clipart_file">Clipart File</label></th>
                            <td><input type="file" name="clipart_file" id="clipart_file" required /></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="Add Clipart">
                    </p>
                </form>
                <?php
                if (!empty($clipart_assets)) {
                    echo '<h3>Existing Clipart</h3>';
                    echo '<table class="wp-list-table widefat striped">';
                    echo '<thead><tr><th>Image</th><th>Name/Label</th><th>Category</th><th>Tags</th><th>Actions</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($clipart_assets as $clipart) {
                        echo '<tr>';
                        echo '<td>';
                        if (!empty($clipart['image_url'])) {
                            echo '<img src="' . esc_url($clipart['image_url']) . '" alt="' . esc_attr($clipart['name']) . '" style="max-width: 50px; height: auto;" />';
                        } else {
                            echo 'No Image';
                        }
                        echo '</td>';
                        echo '<td>' . esc_html($clipart['name']) . '</td>';
                        echo '<td>' . esc_html($clipart['category'] ?? 'Uncategorized') . '</td>';
                        echo '<td>' . esc_html(!empty($clipart['tags']) ? implode(', ', $clipart['tags']) : 'No Tags') . '</td>';
                        echo '<td>';
                        $edit_url = admin_url('admin.php?page=product-personalizer-settings&tab=clipart&action=edit_clipart&clipart_id=' . $clipart['id']);
                        echo '<a href="' . esc_url($edit_url) . '" class="button button-small">Edit</a> ';
                        // Modify the Delete link for clipart
                        $delete_nonce = wp_create_nonce('delete_clipart_' . $clipart['id']);
                        $delete_url = admin_url('admin-post.php?action=product_personalizer_delete_clipart&clipart_id=' . $clipart['id'] . '&_wpnonce=' . $delete_nonce);
                        echo '<a href="' . esc_url($delete_url) . '" class="button button-small button-danger" onclick="return confirm(\'Are you sure you want to delete this clipart?\');">Delete</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo '<p>No clipart added yet.</p>';
                }
            } // End else for edit_clipart action check
                ?>
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
        // Enqueue assets for the main settings page
        if ('toplevel_page_product-personalizer-settings' === $hook) {
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
                array('jquery', 'wp-color-picker'), // Added wp-color-picker for color swatch picker
                '1.0.0', // Consider using a plugin version constant
                true
            );
        }

        // Enqueue assets for the product edit page (for the Personalization tab)
        // Check if get_current_screen() function exists and then check its ID.
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && 'product' === $screen->id) {
                wp_enqueue_script(
                    'product-personalizer-designer-scripts', // Unique handle
                    plugin_dir_url(__FILE__) . '../../assets/js/product-designer.js',
                    array('jquery'), // Add other dependencies if needed, e.g., for fabric.js later
                    '1.0.0', // Consider using a plugin version constant
                    true // Load in footer
                );

                // Localize script with AJAX URL, nonce, and product ID
                global $post;
                $product_id = $post ? $post->ID : 0;

                wp_localize_script(
                    'product-personalizer-designer-scripts',
                    'ppDesignerData',
                    array(
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'save_nonce' => wp_create_nonce('product_personalizer_save_config_nonce'),
                        'product_id' => $product_id,
                        'save_action' => 'product_personalizer_save_config',
                        'saved_config' => get_post_meta($product_id, '_product_personalization_config_json', true) ?: null,
                    )
                );
                // Potentially enqueue specific styles for the designer area too
                // wp_enqueue_style('product-personalizer-designer-styles', plugin_dir_url(__FILE__) . '../../assets/css/product-designer.css');
            }
        }
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

    /**
     * Handles the deletion of a font.
     */
    public function handle_delete_font_action() {
        // 1. Security Checks
        if (!isset($_GET['font_id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_font_' . $_GET['font_id'])) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action.');
        }

        // 2. Data Retrieval & Sanitization
        $font_id = isset($_GET['font_id']) ? sanitize_text_field($_GET['font_id']) : 0;

        if (empty($font_id)) {
            add_settings_error(
                'product-personalizer-settings',
                'font_delete_error',
                'Invalid font ID.',
                'error'
            );
            $this->redirect_to_fonts_tab();
            return;
        }

        // 3. Call AssetManager
        try {
            $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
            $result = $asset_manager->delete_asset($font_id); // Assuming delete_asset handles font deletion

            // 4. Feedback & Redirect
            if (is_wp_error($result)) {
                add_settings_error(
                    'product-personalizer-settings',
                    'font_delete_error',
                    'Failed to delete font. Error: ' . $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'product-personalizer-settings',
                    'font_delete_success',
                    'Font deleted successfully.',
                    'success'
                );
            }
        } catch (\Exception $e) {
            add_settings_error(
                'product-personalizer-settings',
                'font_delete_error',
                'An unexpected error occurred: ' . $e->getMessage(),
                'error'
            );
        }

        $this->redirect_to_fonts_tab();
    }

    /**
     * Handles the submission of the "Update Font" form.
     */
    public function handle_update_font_submission() {
        // 1. Security Checks
        $font_id = isset($_POST['font_id']) ? absint($_POST['font_id']) : 0;

        if (!isset($_POST['update_font_nonce']) || !wp_verify_nonce($_POST['update_font_nonce'], 'update_font_action_' . $font_id)) {
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
        if (empty($font_id) || empty($font_name) || empty($font_source)) {
            add_settings_error(
                'product-personalizer-settings',
                'font_update_error',
                'Invalid font ID, Font Name, or Source.',
                'error'
            );
            $this->redirect_to_fonts_tab();
            return;
        }

        // Prepare metadata for AssetManager
        $metadata = [
            'font_family' => $font_name, // Assuming AssetManager expects 'font_family'
            'font_weight' => $font_weight,
            'font_style' => $font_style,
            'source' => $font_source,
        ];

        // 3. Call AssetManager
        try {
            $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
            $result = $asset_manager->update_asset_metadata(
                $font_id,
                $metadata,
                $font_file // Pass the file array directly
            );

            // 4. Feedback & Redirect
            if (is_wp_error($result)) {
                add_settings_error(
                    'product-personalizer-settings',
                    'font_update_error',
                    'Failed to update font. Error: ' . $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'product-personalizer-settings',
                    'font_update_success',
                    'Font updated successfully.',
                    'success'
                );
            }
        } catch (\Exception $e) {
            add_settings_error(
                'product-personalizer-settings',
                'font_update_error',
                'An unexpected error occurred: ' . $e->getMessage(),
                'error'
            );
        }

        $this->redirect_to_fonts_tab();
    }

    /**
     * Handles the submission of the "Add New Color Swatch" form.
     */
    public function handle_add_color_swatch_submission() {
        // 1. Security Checks
        if (!isset($_POST['add_new_color_swatch_nonce']) || !wp_verify_nonce($_POST['add_new_color_swatch_nonce'], 'add_new_color_swatch_action')) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action.');
        }

        // 2. Data Retrieval & Sanitization
        $label = isset($_POST['color_swatch_label']) ? sanitize_text_field($_POST['color_swatch_label']) : '';
        $hex_code = isset($_POST['color_swatch_hex']) ? sanitize_hex_color($_POST['color_swatch_hex']) : '';

        // Basic validation
        if (empty($label) || empty($hex_code)) {
            add_settings_error(
                'product-personalizer-settings',
                'color_swatch_add_error',
                'Label and Hex Code are required.',
                'error'
            );
            $this->redirect_to_color_swatches_tab();
            return;
        }

        // 3. Call AssetManager
        try {
            $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
            $result = $asset_manager->add_color_swatch(
                [
                    'label' => $label,
                    'hex_code' => $hex_code,
                ]
            );

            // 4. Feedback & Redirect
            if (is_wp_error($result)) {
                add_settings_error(
                    'product-personalizer-settings',
                    'color_swatch_add_error',
                    'Failed to add color swatch. Error: ' . $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'product-personalizer-settings',
                    'color_swatch_add_success',
                    'Color swatch added successfully.',
                    'success'
                );
            }
        } catch (\Exception $e) {
            add_settings_error(
                'product-personalizer-settings',
                'color_swatch_add_error',
                'An unexpected error occurred: ' . $e->getMessage(),
                'error'
            );
        }

        $this->redirect_to_color_swatches_tab();
    }

    /**
     * Handles the deletion of a color swatch.
     */
    public function handle_delete_color_swatch_action() {
        // 1. Security Checks
        if (!isset($_GET['color_swatch_id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_color_swatch_' . $_GET['color_swatch_id'])) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action.');
        }

        // 2. Data Retrieval & Sanitization
        $color_swatch_id = isset($_GET['color_swatch_id']) ? absint($_GET['color_swatch_id']) : 0;

        if (empty($color_swatch_id)) {
            add_settings_error(
                'product-personalizer-settings',
                'color_swatch_delete_error',
                'Invalid color swatch ID.',
                'error'
            );
            $this->redirect_to_color_swatches_tab();
            return;
        }

        // 3. Call AssetManager
        try {
            $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
            $result = $asset_manager->delete_asset($color_swatch_id); // Assuming delete_asset handles color swatch deletion

            // 4. Feedback & Redirect
            if (is_wp_error($result)) {
                add_settings_error(
                    'product-personalizer-settings',
                    'color_swatch_delete_error',
                    'Failed to delete color swatch. Error: ' . $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'product-personalizer-settings',
                    'color_swatch_delete_success',
                    'Color swatch deleted successfully.',
                    'success'
                );
            }
        } catch (\Exception $e) {
            add_settings_error(
                'product-personalizer-settings',
                'color_swatch_delete_error',
                'An unexpected error occurred: ' . $e->getMessage(),
                'error'
            );
        }

        $this->redirect_to_color_swatches_tab();
    }

    /**
     * Redirects the user back to the color swatches tab of the settings page.
     */
    private function redirect_to_color_swatches_tab() {
        $redirect_url = admin_url('admin.php?page=product-personalizer-settings&tab=color-swatches');
        settings_errors(); // Display any accumulated settings errors before redirect
        wp_redirect($redirect_url);
        exit;
    }
/**
     * Handles the submission of the "Update Color Swatch" form.
     */
    public function handle_update_color_swatch_submission() {
        // 1. Security Checks
        $color_swatch_id = isset($_POST['color_swatch_id']) ? absint($_POST['color_swatch_id']) : 0;

        if (!isset($_POST['update_color_swatch_nonce']) || !wp_verify_nonce($_POST['update_color_swatch_nonce'], 'update_color_swatch_action_' . $color_swatch_id)) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action.');
        }

        // 2. Data Retrieval & Sanitization
        $label = isset($_POST['color_swatch_label']) ? sanitize_text_field($_POST['color_swatch_label']) : '';
        $hex_code = isset($_POST['color_swatch_hex']) ? sanitize_hex_color($_POST['color_swatch_hex']) : '';

        // Basic validation
        if (empty($color_swatch_id) || empty($label) || empty($hex_code)) {
            add_settings_error(
                'product-personalizer-settings',
                'color_swatch_update_error',
                'Invalid Color Swatch ID, Label, or Hex Code.',
                'error'
            );
            $this->redirect_to_color_swatches_tab();
            return;
        }

        // 3. Call AssetManager
        try {
            $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
            $result = $asset_manager->update_asset_metadata(
                $color_swatch_id,
                [
                    'label' => $label,
                    'hex_code' => $hex_code,
                ]
            );

            // 4. Feedback & Redirect
            if (is_wp_error($result)) {
                add_settings_error(
                    'product-personalizer-settings',
                    'color_swatch_update_error',
                    'Failed to update color swatch. Error: ' . $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'product-personalizer-settings',
                    'color_swatch_update_success',
                    'Color swatch updated successfully.',
                    'success'
                );
            }
        } catch (\Exception $e) {
            add_settings_error(
                'product-personalizer-settings',
                'color_swatch_update_error',
                'An unexpected error occurred: ' . $e->getMessage(),
                'error'
            );
        }

        $this->redirect_to_color_swatches_tab();
    }

    /**
     * Handles the submission of the "Add New Clipart" form.
     */
    public function handle_add_clipart_submission() {
        // 1. Security Checks
        if (!isset($_POST['add_new_clipart_nonce']) || !wp_verify_nonce($_POST['add_new_clipart_nonce'], 'add_new_clipart_action')) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action.');
        }

        // 2. Data Retrieval & Sanitization
        $clipart_name = isset($_POST['clipart_name']) ? sanitize_text_field($_POST['clipart_name']) : '';
        $clipart_category = isset($_POST['clipart_category']) ? sanitize_text_field($_POST['clipart_category']) : '';
        $clipart_tags_raw = isset($_POST['clipart_tags']) ? sanitize_text_field($_POST['clipart_tags']) : '';
        $clipart_tags = !empty($clipart_tags_raw) ? array_map('trim', explode(',', $clipart_tags_raw)) : [];
        $clipart_file = isset($_FILES['clipart_file']) ? $_FILES['clipart_file'] : null;

        // Basic validation
        if (empty($clipart_name) || empty($clipart_file) || $clipart_file['error'] !== UPLOAD_ERR_OK) {
            add_settings_error(
                'product-personalizer-settings',
                'clipart_add_error',
                'Clipart Name and a valid File are required.',
                'error'
            );
            $this->redirect_to_clipart_tab();
            return;
        }

        // 3. Call AssetManager
        try {
            $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
            $result = $asset_manager->upload_asset(
                [
                    'name' => $clipart_name,
                    'type' => 'clipart',
                    'metadata' => [
                        'category' => $clipart_category,
                        'tags' => $clipart_tags,
                    ],
                ],
                $clipart_file
            );

            // 4. Feedback & Redirect
            if (is_wp_error($result)) {
                add_settings_error(
                    'product-personalizer-settings',
                    'clipart_add_error',
                    'Failed to add clipart. Error: ' . $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'product-personalizer-settings',
                    'clipart_add_success',
                    'Clipart added successfully.',
                    'success'
                );
            }
        } catch (\Exception $e) {
            add_settings_error(
                'product-personalizer-settings',
                'clipart_add_error',
                'An unexpected error occurred: ' . $e->getMessage(),
                'error'
            );
        }

        $this->redirect_to_clipart_tab();
    }

/**
     * Handles the deletion of a clipart asset.
     */
    public function handle_delete_clipart_action() {
        // 1. Security Checks
        if (!isset($_GET['clipart_id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_clipart_' . $_GET['clipart_id'])) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action.');
        }

        // 2. Data Retrieval & Sanitization
        $clipart_id = isset($_GET['clipart_id']) ? absint($_GET['clipart_id']) : 0;

        if (empty($clipart_id)) {
            add_settings_error(
                'product-personalizer-settings',
                'clipart_delete_error',
                'Invalid clipart ID.',
                'error'
            );
            $this->redirect_to_clipart_tab();
            return;
        }

        // 3. Call AssetManager
        try {
            $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
            $result = $asset_manager->deleteAsset($clipart_id); // Assuming deleteAsset handles clipart deletion

            // 4. Feedback & Redirect
            if (is_wp_error($result)) {
                add_settings_error(
                    'product-personalizer-settings',
                    'clipart_delete_error',
                    'Failed to delete clipart. Error: ' . $result->get_error_message(),
                    'error'
                );
            } elseif ($result === false && !is_wp_error($result)) { // Check for explicit false if deleteAsset returns bool
                 add_settings_error(
                    'product-personalizer-settings',
                    'clipart_delete_error',
                    'Failed to delete clipart. The operation returned false as AssetManager::deleteAsset might be a stub.',
                    'error'
                );
            }
            else {
                add_settings_error(
                    'product-personalizer-settings',
                    'clipart_delete_success',
                    'Clipart deleted successfully.',
                    'success'
                );
            }
        } catch (\Exception $e) {
            add_settings_error(
                'product-personalizer-settings',
                'clipart_delete_error',
                'An unexpected error occurred: ' . $e->getMessage(),
                'error'
            );
        }

        $this->redirect_to_clipart_tab();
    }
    /**
     * Redirects the user back to the clipart tab of the settings page.
     */
/**
     * Handles the submission of the "Update Clipart" form.
     */
    public function handle_update_clipart_submission() {
        // 1. Security Checks
        if (!isset($_POST['clipart_id'])) {
            wp_die('Missing clipart ID.');
        }
        $clipart_id = absint($_POST['clipart_id']);

        // Nonce check. check_admin_referer will die if nonce is invalid.
        check_admin_referer('update_clipart_action_' . $clipart_id, 'update_clipart_nonce');

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action.');
        }

        // 2. Data Retrieval & Sanitization
        $clipart_name = isset($_POST['clipart_name']) ? sanitize_text_field($_POST['clipart_name']) : '';
        $clipart_category = isset($_POST['clipart_category']) ? sanitize_text_field($_POST['clipart_category']) : '';
        $clipart_tags_raw = isset($_POST['clipart_tags']) ? sanitize_text_field($_POST['clipart_tags']) : '';
        // Ensure tags are processed correctly, even if empty or just spaces after sanitization
        $clipart_tags = !empty(trim($clipart_tags_raw)) ? array_map('trim', explode(',', $clipart_tags_raw)) : [];
        
        $clipart_file = null;
        // Check if a file was uploaded and there were no errors
        if (isset($_FILES['clipart_file']) && !empty($_FILES['clipart_file']['tmp_name']) && $_FILES['clipart_file']['error'] === UPLOAD_ERR_OK) {
            $clipart_file = $_FILES['clipart_file'];
        }

        // Basic validation
        if (empty($clipart_name)) {
            add_settings_error(
                'product-personalizer-settings',
                'clipart_update_error',
                'Clipart Name/Label is required.',
                'error'
            );
            // Store errors in a transient to display after redirect
            set_transient('settings_errors', get_settings_errors(), 30);
            $this->redirect_to_clipart_tab(); // This function calls exit()
        }

        // 3. Prepare data for AssetManager
        $asset_data = [
            'name' => $clipart_name,
            // 'type' => 'clipart', // Type is usually implicit when updating by ID
            'metadata' => [
                'category' => $clipart_category,
                'tags' => $clipart_tags,
            ],
        ];

        // 4. Call AssetManager
        try {
            $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
            // Assuming AssetManager has an `update_asset_metadata` method.
            // This method should handle both metadata update and optional file replacement.
            $result = $asset_manager->update_asset_metadata($clipart_id, $asset_data, $clipart_file);

            // 5. Feedback & Redirect
            if (is_wp_error($result)) {
                add_settings_error(
                    'product-personalizer-settings',
                    'clipart_update_error',
                    'Failed to update clipart. Error: ' . $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'product-personalizer-settings',
                    'clipart_update_success',
                    'Clipart updated successfully.',
                    'success'
                );
            }
        } catch (\Exception $e) {
            add_settings_error(
                'product-personalizer-settings',
                'clipart_update_exception',
                'An error occurred while updating clipart: ' . $e->getMessage(),
                'error'
            );
        }
        // Store errors in a transient to display after redirect
        set_transient('settings_errors', get_settings_errors(), 30);
        $this->redirect_to_clipart_tab(); // This function calls exit()
    }
    private function redirect_to_clipart_tab() {
        $redirect_url = admin_url('admin.php?page=product-personalizer-settings&tab=clipart');
        settings_errors(); // Display any accumulated settings errors before redirect
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Add Personalization Product Data Tab.
     *
     * @param array $tabs Existing tabs.
     * @return array Modified tabs.
     */
    public function add_personalization_product_data_tab($tabs) {
        $tabs['personalization'] = array(
            'label'    => __('Personalization', 'product-personalizer'),
            'target'   => 'product_personalization_data',
            'class'    => array('show_if_simple', 'show_if_variable'), // Adjust as needed
            'priority' => 60, // Adjust as needed
        );
        return $tabs;
    }

    /**
     * Display Personalization Product Data Panel Content.
     */
    public function display_personalization_product_data_panel() {
        echo '<div id="product_personalization_data" class="panel woocommerce_options_panel hidden">';
        echo '<h2>' . esc_html__('Product Personalization Setup', 'product-personalizer') . '</h2>';
        global $post;

        // Instantiate AssetManager
        $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();
        $available_fonts = $asset_manager->get_fonts();
        $available_color_swatches = $asset_manager->get_color_swatches();
        $available_clipart = $asset_manager->get_clipart(); // Fetch clipart

        $product = wc_get_product($post->ID);
        $image_url = '';
        if ($product) {
            $image_id = $product->get_image_id();
            if ($image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
            }
        }

        echo '<div id="product_personalization_canvas_area" style="border:1px solid #ccc; position:relative; width: 400px; height: 400px; margin-top: 15px; margin-bottom: 15px; overflow: hidden;">';
        if ($image_url) {
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr__('Product Image', 'product-personalizer') . '" style="max-width:100%; max-height:100%; display:block; margin:auto;">';
        } else {
            echo '<p style="text-align:center; padding: 20px;">' . esc_html__('No product image set. Please set a product image first.', 'product-personalizer') . '</p>';
        }
        echo '</div>';

        // Properties Panel
        echo '<div id="personalization_area_properties_panel" style="display:none; border:1px solid #ccc; padding:10px; margin-top:10px;">';
        echo '<h4>' . esc_html__('Area Properties', 'product-personalizer') . '</h4>';
        echo '<div>';
        echo '<label for="personalization_area_name">' . esc_html__('Name/Label:', 'product-personalizer') . '</label>';
        echo '<input type="text" id="personalization_area_name" name="personalization_area_name" />';
        echo '</div>';

        // Font Dropdown
        echo '<div>';
        echo '<label for="personalization_area_font">' . esc_html__('Font:', 'product-personalizer') . '</label>';
        echo '<select id="personalization_area_font" name="personalization_area_font">';
        echo '<option value="">' . esc_html__('-- Select Font --', 'product-personalizer') . '</option>';
        if (!empty($available_fonts)) {
            foreach ($available_fonts as $font) {
                echo '<option value="' . esc_attr($font['id']) . '">' . esc_html($font['font_family']) . ' (' . esc_html($font['font_weight']) . ' ' . esc_html($font['font_style']) . ')</option>';
            }
        }
        echo '</select>';
        echo '</div>';

        // Color Dropdown
        echo '<div>';
        echo '<label for="personalization_area_color">' . esc_html__('Color:', 'product-personalizer') . '</label>';
        echo '<select id="personalization_area_color" name="personalization_area_color">';
        echo '<option value="">' . esc_html__('-- Select Color --', 'product-personalizer') . '</option>';
        if (!empty($available_color_swatches)) {
            foreach ($available_color_swatches as $color_swatch) {
                echo '<option value="' . esc_attr($color_swatch['hex_code']) . '">' . esc_html($color_swatch['label']) . ' (' . esc_html($color_swatch['hex_code']) . ')</option>';
            }
        }
        echo '</select>';
        echo '</div>';

        // Personalization Type Dropdown
        echo '<div>';
        echo '<label for="personalization_area_type">' . esc_html__('Personalization Type:', 'product-personalizer') . '</label>';
        echo '<select id="personalization_area_type" name="personalization_area_type">';
        echo '<option value="">' . esc_html__('-- Select Type --', 'product-personalizer') . '</option>';
        echo '<option value="text">' . esc_html__('Text', 'product-personalizer') . '</option>';
        echo '<option value="image">' . esc_html__('Image/Clipart', 'product-personalizer') . '</option>';
        echo '</select>';
        echo '</div>';

        // Text Options Panel (initially hidden)
        echo '<div id="text_options_panel" style="display:none; margin-top:10px; padding-top:10px; border-top:1px solid #eee;">';
        echo '<h4>' . esc_html__('Text Options', 'product-personalizer') . '</h4>';
        echo '<div>';
        echo '<label for="personalization_text_default">' . esc_html__('Default Text:', 'product-personalizer') . '</label>';
        echo '<input type="text" id="personalization_text_default" name="personalization_text_default" />';
        echo '</div>';
        echo '<div>';
        echo '<label for="personalization_text_maxlength">' . esc_html__('Max Length:', 'product-personalizer') . '</label>';
        echo '<input type="number" id="personalization_text_maxlength" name="personalization_text_maxlength" />';
        echo '</div>';
        echo '</div>';

        // Image/Clipart Options Panel (initially hidden)
        echo '<div id="image_options_panel" style="display:none; margin-top:10px; padding-top:10px; border-top:1px solid #eee;">';
        echo '<h4>' . esc_html__('Image/Clipart Options', 'product-personalizer') . '</h4>';
        echo '<div>';
        echo '<label for="personalization_image_clipart_select">' . esc_html__('Select Clipart:', 'product-personalizer') . '</label>';
        echo '<select id="personalization_image_clipart_select" name="personalization_image_clipart_select">';
        echo '<option value="">' . esc_html__('-- Select Clipart --', 'product-personalizer') . '</option>';
        if (!empty($available_clipart)) {
            foreach ($available_clipart as $clipart_item) {
                // Assuming $clipart_item is an array with 'id' and 'name' keys
                // Adjust if it's an object or has different property names
                $clipart_id = isset($clipart_item['id']) ? $clipart_item['id'] : (isset($clipart_item->id) ? $clipart_item->id : '');
                $clipart_name = isset($clipart_item['name']) ? $clipart_item['name'] : (isset($clipart_item->name) ? $clipart_item->name : '');
                echo '<option value="' . esc_attr($clipart_id) . '">' . esc_html($clipart_name) . '</option>';
            }
        }
        echo '</select>';
        echo '</div>';
        // <!-- More image/clipart options can be added here later -->
        echo '</div>';

        echo '<!-- More properties will be added here later -->';
        echo '</div>';

        // Placeholder for other designer UI components
        echo '</div>'; // Close personalization_settings_panel

        // Save Configuration Button
        echo '<div style="margin-top: 20px;">';
        echo '<button type="button" id="save_personalization_config_button" class="button button-primary">' . __('Save Configuration', 'product-personalizer') . '</button>';
        echo '<span id="personalization_save_status" style="margin-left: 10px;"></span>';
        echo '</div>';
    }

    /**
     * AJAX handler for saving personalization configuration.
     */
    public function ajax_save_personalization_config() {
        // 1. Verify Nonce
        check_ajax_referer('product_personalizer_save_config_nonce', 'nonce');

        // 2. Check User Capabilities
        if (!current_user_can('edit_products')) {
            wp_send_json_error(array('message' => __('You do not have permission to save this configuration.', 'product-personalizer')), 403);
            return;
        }

        // 3. Get and Sanitize Data
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        // stripslashes_deep because wp_ajax might add them to JSON string which could be an array of objects
        $config_data_json = isset($_POST['config_data']) ? stripslashes_deep($_POST['config_data']) : '';


        if (empty($product_id)) {
            wp_send_json_error(array('message' => __('Invalid Product ID.', 'product-personalizer')), 400);
            return;
        }

        // Allow saving empty configuration to clear it
        if (empty($config_data_json)) {
            update_post_meta($product_id, '_product_personalization_config_json', '');
            wp_send_json_success(array('message' => __('Configuration cleared successfully.', 'product-personalizer')));
            return;
        }

        // 4. Validate JSON
        // json_decode expects a string. If stripslashes_deep turned it into an array, this will fail.
        // However, config_data is sent as JSON.stringify, so it should be a string.
        $config_data_array = json_decode($config_data_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => __('Invalid configuration data format: ', 'product-personalizer') . json_last_error_msg() . '. Input (first 200 chars): ' . esc_html(substr($config_data_json, 0, 200))), 400);
            return;
        }

        // At this point, $config_data_array is a PHP array.
        // For saving, we need the JSON string. $config_data_json is the original validated JSON string.
        // If further sanitization of $config_data_array elements were done, it would need to be re-encoded.
        // Example of deeper sanitization (if needed):
        // if (is_array($config_data_array)) {
        //     foreach ($config_data_array as &$area) {
        //         // Sanitize each expected field in $area
        //         if (isset($area['name'])) $area['name'] = sanitize_text_field($area['name']);
        //         if (isset($area['x'])) $area['x'] = absint($area['x']);
        //         if (isset($area['y'])) $area['y'] = absint($area['y']);
        //         if (isset($area['width'])) $area['width'] = absint($area['width']);
        //         if (isset($area['height'])) $area['height'] = absint($area['height']);
        //         if (isset($area['font_id'])) $area['font_id'] = sanitize_text_field($area['font_id']); // or absint if it's an ID
        //         if (isset($area['color_hex'])) $area['color_hex'] = sanitize_hex_color($area['color_hex']);
        //     }
        //     $config_data_to_save = wp_json_encode($config_data_array);
        // } else {
        //    wp_send_json_error(array('message' => __('Configuration data must be an array of areas.', 'product-personalizer')), 400);
        //    return;
        // }
        // For now, we save the $config_data_json as it was validated.

        // 5. Save to Post Meta
        $result = update_post_meta($product_id, '_product_personalization_config_json', $config_data_json);

        if ($result === false) {
            // update_post_meta returns false if the value is the same or on error.
            $existing_meta = get_post_meta($product_id, '_product_personalization_config_json', true);
            if ($existing_meta === $config_data_json) {
                 wp_send_json_success(array('message' => __('Configuration is already up to date.', 'product-personalizer')));
            } else {
                wp_send_json_error(array('message' => __('Could not save configuration. An unknown error occurred.', 'product-personalizer')));
            }
        } elseif (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            // $result is meta_id on success
            wp_send_json_success(array('message' => __('Configuration saved successfully.', 'product-personalizer')));
        }
    }
} // This is the closing brace for the class Product_Personalizer_Admin_Settings