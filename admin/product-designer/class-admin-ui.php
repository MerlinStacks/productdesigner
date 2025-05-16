<?php
/**
 * Designer Admin UI Class for CustomKings Product Personalizer
 *
 * Handles UI rendering for admin pages and asset loading
 *
 * @package CustomKingsProductPersonalizer
 * @since 1.1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CKPP_Designer_Admin_UI
 * Handles UI rendering for admin pages and asset loading
 */
class CKPP_Designer_Admin_UI {
    /**
     * Class constructor - empty as no direct hooks needed
     */
    public function __construct() {
        // No direct hook registrations needed
    }

    /**
     * Render the Designs admin page and designer UI.
     */
    public function render_designs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'customkings'));
        }

        // Add debug output
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CKPP: render_designs_page called');
        }
        
        $design_id = isset($_GET['design_id']) ? intval($_GET['design_id']) : 0;
        echo '<div class="wrap"><h1>' . esc_html__( 'Product Personalization Designs', 'customkings' ) . '</h1>';
        if (isset($_GET['ckpp_deleted']) && $_GET['ckpp_deleted'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Design deleted.', 'customkings') . '</p></div>';
        }
        
        if ($design_id) {
            // Designer UI for editing a design
            // Output a hidden div with all templates for JS
            $templates = get_posts([
                'post_type' => 'ckpp_design',
                'numberposts' => -1,
                's' => 'Template:',
            ]);
            $template_list = [];
            foreach ($templates as $tpl) {
                $template_list[] = [
                    'id' => $tpl->ID,
                    'title' => $tpl->post_title,
                ];
            }
            echo '<div id="ckpp-templates-data" data-templates="' . esc_attr(json_encode($template_list)) . '" style="display:none;"></div>';
            // Output a hidden div with all uploaded fonts for JS
            if (class_exists('CKPP_Fonts')) {
                $fonts = CKPP_Fonts::get_fonts();
                $font_list = [];
                foreach ($fonts as $font) {
                    $font_list[] = [
                        'name' => $font->font_name,
                        'url' => $font->font_file
                    ];
                }
                echo '<div id="ckpp-fonts-data" data-fonts="' . esc_attr(json_encode($font_list)) . '" style="display:none;"></div>';
            }
            echo '<div id="ckpp-product-designer-root"></div>';
            $design_title = get_the_title($design_id);
            echo '<script>window.CKPP_DESIGN_ID = ' . $design_id . '; window.CKPP_DESIGN_TITLE = ' . json_encode($design_title) . ';</script>';
        } else {
            // List and create designs
            echo '<a href="' . esc_url( admin_url( 'admin.php?action=ckpp_create_design' ) ) . '" class="button button-primary">' . esc_html__( 'Create New Design', 'customkings' ) . '</a>';
            
            // Check if post type is registered
            if (!post_type_exists('ckpp_design')) {
                echo '<div class="notice notice-error"><p>Error: ckpp_design post type not registered.</p></div>';
            }
            
            $designs = get_posts([ 'post_type' => 'ckpp_design', 'numberposts' => -1 ]);
            if ($designs) {
                echo '<ul style="margin-top:2em;">';
                foreach ($designs as $design) {
                    $delete_url = wp_nonce_url(
                        admin_url('admin.php?action=ckpp_delete_design&design_id=' . $design->ID),
                        'ckpp_delete_design_' . $design->ID
                    );
                    echo '<li>';
                    echo '<a href="' . esc_url( admin_url( 'admin.php?page=ckpp_designs&design_id=' . $design->ID ) ) . '">' . esc_html( $design->post_title ) . '</a>';
                    echo ' <a href="' . esc_url($delete_url) . '" style="color:#a00; margin-left:12px;" onclick="return confirm(\'Are you sure you want to delete this design?\');">[' . esc_html__('Delete', 'customkings') . ']</a>';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>' . esc_html__( 'No designs found.', 'customkings' ) . '</p>';
            }
        }
        echo '</div>';

        // Ensure scripts are loaded
        $this->enqueue_assets('');
    }

    /**
     * Render the Images admin page.
     */
    public function render_images_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'customkings' ) );
        }

        // Add debug output
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CKPP: render_images_page called');
        }
        
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir['basedir'] . '/ckpp_images/';
        $url_base = $upload_dir['baseurl'] . '/ckpp_images/';
        
        echo '<div class="wrap"><h1>' . esc_html__( 'Uploaded Images', 'customkings' ) . '</h1>';
        
        // Add upload form
        echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '" class="ckpp-upload-form">';
        wp_nonce_field('ckpp_upload_image', 'ckpp_upload_image_nonce');
        echo '<input type="hidden" name="action" value="ckpp_upload_image" />';
        echo '<div style="display:flex;gap:10px;margin-bottom:20px;">';
        echo '<input type="file" name="ckpp_image_file" accept="image/*" required />';
        echo '<button type="submit" class="button button-primary">Upload Image</button>';
        echo '</div></form>';
        
        // Check for notices
        if ( isset($_GET['deleted']) && $_GET['deleted'] === '1' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Image deleted.', 'customkings') . '</p></div>';
        }
        if ( isset($_GET['bulk_deleted']) && $_GET['bulk_deleted'] === '1' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Selected images deleted.', 'customkings') . '</p></div>';
        }
        if ( isset($_GET['uploaded']) && $_GET['uploaded'] === '1' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Image uploaded successfully.', 'customkings') . '</p></div>';
        }
        
        // Check if directory exists
        if ( ! file_exists( $dir ) ) {
            if (wp_mkdir_p($dir)) {
                echo '<p>' . esc_html__('Upload directory created successfully. Upload your first image above.', 'customkings') . '</p>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Could not create upload directory: ', 'customkings') . esc_html($dir) . '</p></div>';
            }
            echo '</div>';
            return;
        }
        
        // Get image list with error checking
        if (!is_dir($dir) || !is_readable($dir)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Cannot read from upload directory: ', 'customkings') . esc_html($dir) . '</p></div>';
            echo '</div>';
            return;
        }
        
        $images = [];
        try {
            $files = scandir($dir);
            $images = array_filter($files, function($f) use ($dir) {
                return is_file($dir . $f) && preg_match('/\.(png|jpe?g|gif|svg)$/i', $f);
            });
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Error scanning directory: ', 'customkings') . esc_html($e->getMessage()) . '</p></div>';
            echo '</div>';
            return;
        }
        
        if ( empty( $images ) ) {
            echo '<p>' . esc_html__( 'No images uploaded yet.', 'customkings' ) . '</p>';
            echo '</div>';
            return;
        }
        
        // Search/filter input
        echo '<input type="text" id="ckpp-image-search" placeholder="Search images..." style="margin-bottom:1.5em; width:300px; font-size:15px; padding:4px 8px;" />';
        echo '<div class="ckpp-clipart-grid" id="ckpp-images-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:15px;">';
        
        $has_images = false;
        foreach ( $images as $img ) {
            $file = $dir . $img;
            $url = $url_base . rawurlencode($img);
            $date = date( 'Y-m-d H:i', filemtime( $file ) );
            $size = filesize($file);
            $size_str = $size > 1048576 ? round($size/1048576,2).' MB' : round($size/1024,1).' KB';
            $delete_url = wp_nonce_url( admin_url( 'admin.php?action=ckpp_delete_image&ckpp_delete_image=' . urlencode($img) ), 'ckpp_delete_image_' . $img );
            $has_images = true;
            
            echo '<div class="ckpp-clipart-card ckpp-image-card" data-img-name="' . esc_attr($img) . '" style="border:1px solid #ddd;border-radius:4px;padding:8px;position:relative;">';
            echo '<div class="ckpp-clipart-thumb" style="height:120px;display:flex;align-items:center;justify-content:center;margin-bottom:8px;">';
            echo '<img src="' . esc_url( $url ) . '" alt="" style="width:100%;height:100%;object-fit:contain;max-width:120px;max-height:120px;" />';
            echo '<a href="' . esc_url($delete_url) . '" class="dashicons dashicons-trash" style="position:absolute;top:5px;right:5px;color:#b32d2e;background:rgba(255,255,255,0.7);border-radius:50%;padding:2px;" onclick="return confirm(\'' . esc_js( __( 'Delete this image?', 'customkings' ) ) . '\');"></a>';
            echo '</div>';
            echo '<div class="ckpp-clipart-meta">';
            echo '<div class="ckpp-clipart-name" style="font-weight:bold;margin-bottom:4px;word-break:break-all;">' . esc_html( $img ) . '</div>';
            echo '<div class="ckpp-clipart-tags" style="color:#666;font-size:12px;">' . esc_html( $size_str ) . ' &bull; ' . esc_html( $date ) . '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        if (!$has_images) {
            echo '<div style="padding:2em;text-align:center;color:#888;">' . esc_html__( 'No images uploaded yet.', 'customkings' ) . '</div>';
        }
        
        echo '</div>';
        
        // JS for search/filter
        echo '<script>
        document.getElementById("ckpp-image-search").addEventListener("input", function() {
            var val = this.value.toLowerCase();
            var cards = document.querySelectorAll("#ckpp-images-grid .ckpp-image-card");
            cards.forEach(function(card) {
                var name = card.querySelector(".ckpp-clipart-name").textContent.toLowerCase();
                card.style.display = name.indexOf(val) !== -1 ? "" : "none";
            });
        });
        </script>';
        
        echo '</div>';
    }

    /**
     * Enqueue assets for the designer admin pages.
     *
     * @param string $hook
     */
    public function enqueue_assets( $hook ) {
        if ( isset($_GET['page']) && $_GET['page'] === 'ckpp_designs' ) {
            // Register Pickr if not already registered
            if (!wp_script_is('pickr', 'registered')) {
                wp_register_script('pickr', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr', [], null, true);
                wp_register_style('pickr-classic', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css', [], null);
            }
            // Calculate the correct path to the assets
            $plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
            $plugin_url = plugins_url('', dirname(dirname(__FILE__)));
            
            wp_enqueue_style('pickr-classic');
            wp_localize_script('ckpp-designer', 'CKPPDesigner', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ckpp_designer_nonce'),
                'designId' => isset($_GET['design_id']) ? intval($_GET['design_id']) : 0,
                'pluginUrl' => $plugin_url,
                'debugMode' => defined('WP_DEBUG') && WP_DEBUG,
            ]);
        }
    }
} 