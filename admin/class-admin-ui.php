<?php
/**
 * Admin UI for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

require_once CUSTOMKINGS_PLUGIN_DIR . 'admin/class-settings-manager.php';
require_once CUSTOMKINGS_PLUGIN_DIR . 'admin/class-font-manager.php';
require_once CUSTOMKINGS_PLUGIN_DIR . 'admin/class-clipart-manager.php';

class CKPP_Admin_UI {
    private $settings_manager;
    private $font_manager;
    private $clipart_manager;

    public function __construct() {

        $this->settings_manager = new CKPP_Settings_Manager();
        $this->font_manager     = new CKPP_Font_Manager();
        $this->clipart_manager  = new CKPP_Clipart_Manager();

        add_action( 'admin_menu', [ $this, 'register_menu' ] );

        // register_settings is now handled by CKPP_Settings_Manager

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );

        add_action( 'wp_ajax_ckpp_get_assignments', [ $this, 'ajax_get_assignments' ] );

        add_action( 'wp_ajax_ckpp_save_assignment', [ $this, 'ajax_save_assignment' ] );
        
        // The admin-post action is still commented out for now
        add_action( 'admin_post_ckpp_wipe_reinstall', [ $this, 'handle_wipe_reinstall' ] );
        
    }

    public function register_menu() {
        add_menu_page(
            __( 'Product Personalizer', 'customkings' ),
            __( 'Product Personalizer', 'customkings' ),
            'manage_options',
            'ckpp_admin',
            [ $this, 'render_admin_page' ],
            'dashicons-art',
            56
        );
        add_submenu_page(
            'ckpp_admin',
            __( 'Product Assignments', 'customkings' ),
            __( 'Product Assignments', 'customkings' ),
            'manage_woocommerce',
            'ckpp_product_assignments',
            [ $this, 'render_product_assignments_page' ]
        );
        add_submenu_page(
            'ckpp_admin',
            __( 'Designs', 'customkings' ),
            __( 'Designs', 'customkings' ),
            'manage_options',
            'ckpp_designs',
            [ $this, 'render_designs_page' ]
        );
    }

    public function render_admin_page() {
        $tabs = [
            'settings' => __( 'Modes & Global Settings', 'customkings' ),
            'fonts'    => __( 'Fonts', 'customkings' ),
            'clipart'  => __( 'Clipart', 'customkings' ),
        ];
        $active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? $_GET['tab'] : 'settings';
        ?>
        <div class="wrap ckpp-admin">
            <h1><?php esc_html_e( 'Product Personalizer', 'customkings' ); ?></h1>
            <nav class="nav-tab-wrapper" role="tablist">
                <?php foreach ( $tabs as $tab => $label ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ckpp_admin&tab=' . $tab ) ); ?>"
                       class="nav-tab<?php if ( $active_tab === $tab ) echo ' nav-tab-active'; ?>"
                       id="ckpp-tab-<?php echo esc_attr( $tab ); ?>"
                       role="tab"
                       aria-selected="<?php echo $active_tab === $tab ? 'true' : 'false'; ?>"
                       tabindex="0">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="ckpp-tab-content" role="tabpanel" aria-labelledby="ckpp-tab-<?php echo esc_attr( $active_tab ); ?>">
                <?php $this->render_tab_content( $active_tab ); ?>
            </div>
        </div>
        <style>
            .ckpp-admin .nav-tab-wrapper { margin-bottom: 1em; }
            .ckpp-tab-content { background: #fff; padding: 2em; border: 1px solid #ccd0d4; border-top: none; }
        </style>
        <script>
        // Basic keyboard navigation for tabs
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.ckpp-admin .nav-tab');
            tabs.forEach((tab, idx) => {
                tab.addEventListener('keydown', function(e) {
                    if (e.key === 'ArrowRight') {
                        tabs[(idx + 1) % tabs.length].focus();
                    } else if (e.key === 'ArrowLeft') {
                        tabs[(idx - 1 + tabs.length) % tabs.length].focus();
                    }
                });
            });
        });
        </script>
        <?php
    }

    private function render_tab_content( $tab ) {
        switch ( $tab ) {
            case 'fonts':
                $this->font_manager->render_fonts_tab();
                break;
            case 'clipart':
                $this->clipart_manager->render_clipart_tab();
                break;
            case 'settings':
            default:
                $this->settings_manager->render_settings_form();
                break;
        }
    }

    public function render_product_assignments_page() {
        CKPP_Security::verify_capability('manage_woocommerce');
        $nonce = wp_create_nonce( 'ckpp_assign_design' );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Assign Designs to Products', 'customkings' ) . '</h1>';
        echo '<div id="ckpp-assignments-root" data-nonce="' . esc_attr( $nonce ) . '"></div>';
        echo '</div>';
        wp_enqueue_script( 'ckpp-assignments', plugins_url( '../assets/assignments.js', __FILE__ ), array( 'jquery' ), '1.0', true );
        wp_localize_script( 'ckpp-assignments', 'CKPPAssignments', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => $nonce,
        ] );
    }

    public function ajax_get_assignments() {
        CKPP_Security::verify_capability('manage_woocommerce');
        CKPP_Security::verify_ajax_nonce('nonce', 'ckpp_assign_design');
        $paged = max( 1, intval( $_GET['paged'] ?? 1 ) );
        $products = wc_get_products( [ 'limit' => 20, 'page' => $paged, 'status' => 'publish' ] );
        $designs = get_posts( [ 'post_type' => 'ckpp_design', 'post_status' => 'publish', 'numberposts' => -1 ] );
        $assignments = [];
        foreach ( $products as $product ) {
            $assignments[] = [
                'id'        => $product->get_id(),
                'title'     => $product->get_name(),
                'thumbnail' => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ),
                'design_id' => get_post_meta( $product->get_id(), '_ckpp_design_id', true ),
            ];
        }
        wp_send_json_success( [
            'products' => $assignments,
            'designs'  => array_map( function ( $d ) {
                return [ 'id' => $d->ID, 'title' => $d->post_title ];
            }, $designs ),
            'paged'    => $paged,
            'has_more' => count( $products ) === 20,
        ] );
    }

    public function ajax_save_assignment() {
        CKPP_Security::verify_capability('manage_woocommerce');
        CKPP_Security::verify_ajax_nonce('nonce', 'ckpp_assign_design');
        $product_id = intval( $_POST['product_id'] );
        $design_id  = intval( $_POST['design_id'] );
        if ( ! $product_id || get_post_type( $product_id ) !== 'product' ) {
            CKPP_Error_Handler::handle_ajax_error( __( 'Invalid product.', 'customkings' ) );
        }
        $old_design_id = get_post_meta( $product_id, '_ckpp_design_id', true );
        update_post_meta( $product_id, '_ckpp_design_id', $design_id );
        CKPP_Error_Handler::log_security_event('Product Design Assignment Changed', [
            'product_id' => $product_id,
            'old_design_id' => $old_design_id,
            'new_design_id' => $design_id,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        wp_send_json_success();
    }

    /**
     * Enqueue custom admin styles for plugin pages.
     */
    public function enqueue_admin_styles($hook) {
        // Only load on plugin admin pages
        if (
            isset($_GET['page']) &&
            strpos($_GET['page'], 'ckpp_') === 0
        ) {
            wp_enqueue_style(
                'ckpp-admin-ui',
                plugins_url('../assets/admin-ui.css', __FILE__),
                [],
                filemtime(dirname(__DIR__) . '/assets/admin-ui.css')
            );
            wp_enqueue_script(
                'ckpp-admin-upload',
                plugins_url('../assets/admin-order.js', __FILE__),
                ['jquery'],
                filemtime(dirname(__DIR__) . '/assets/admin-order.js'),
                true
            );
            // Register Pickr from CDN
            wp_register_script(
                'pickr',
                'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js',
                [],
                '1.9.0',
                true
            );
            wp_register_style(
                'pickr-classic',
                'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css',
                [],
                '1.9.0'
            );
            // Register local fallback
            wp_register_script(
                'pickr-local',
                plugins_url('assets/pickr.min.js', dirname(__DIR__)),
                [],
                '1.9.0',
                true
            );
            wp_register_style(
                'pickr-classic-local',
                plugins_url('assets/pickr-classic.min.css', dirname(__DIR__)),
                [],
                '1.9.0'
            );
            $page = isset($_GET['page']) ? $_GET['page'] : '';
            $design_id = isset($_GET['design_id']) ? intval($_GET['design_id']) : 0;
            // Enqueue Pickr and designer assets only on the Designs page (edit view)
            if ($page === 'ckpp_designs' && $design_id) {
                wp_enqueue_script('pickr');
                wp_enqueue_style('pickr-classic');
                wp_enqueue_script('ckpp-designer', plugins_url('assets/designer.js', dirname(__FILE__)), ['jquery', 'pickr'], '1.0', true);
                wp_enqueue_style('ckpp-designer', plugins_url('assets/designer.css', dirname(__FILE__)), [], '1.0');
                // Add JS to check if Pickr loaded, and if not, load local fallback
                add_action('admin_footer', function() {
                    ?>
                    <script>
                    if (typeof Pickr === 'undefined') {
                        var s = document.createElement('script');
                        s.src = '<?php echo esc_js(plugins_url('assets/pickr.min.js', dirname(__DIR__))); ?>';
                        document.head.appendChild(s);
                        var l = document.createElement('link');
                        l.rel = 'stylesheet';
                        l.href = '<?php echo esc_js(plugins_url('assets/pickr-classic.min.css', dirname(__DIR__))); ?>';
                        document.head.appendChild(l);
                    }
                    </script>
                    <?php
                });
            }
            // Enqueue design cards CSS only on Designs list view
            if ($page === 'ckpp_designs' && !$design_id) {
                wp_enqueue_style('ckpp-design-cards', plugins_url('assets/design-cards.css', dirname(__FILE__)), [], filemtime(dirname(__DIR__) . '/assets/design-cards.css'));
            }
        }
    }

    public function render_designs_page() {
        CKPP_Security::verify_capability('manage_options');
        // Add debug output
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CKPP: render_designs_page called');
        }
        echo '<!-- CKPP DEBUG: class-admin-ui.php loaded at ' . __FILE__ . ' -->';
        $design_id = isset($_GET['design_id']) ? intval($_GET['design_id']) : 0;
        echo '<div class="wrap"><h1>' . esc_html__( 'Product Personalization Designs', 'customkings' ) . '</h1>';
        if (isset($_GET['ckpp_deleted']) && $_GET['ckpp_deleted'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Design deleted.', 'customkings') . '</p></div>';
        }
        
        // Create designer nonce
        $designer_nonce = wp_create_nonce('ckpp_designer_nonce');
        
        if ($design_id) {
            // Designer UI for editing a design
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
            // Properly initialize the designer JS variables
            ?>
            <script>
                window.CKPP_DESIGN_ID = <?php echo $design_id; ?>; 
                window.CKPP_DESIGN_TITLE = <?php echo json_encode($design_title); ?>;
                window.CKPP_DEBUG_MODE = <?php echo (CKPP_Config::is_debug_mode() ? 'true' : 'false'); ?>;
                window.CKPPDesigner = {
                    ajaxUrl: "<?php echo esc_js(admin_url('admin-ajax.php')); ?>",
                    nonce: "<?php echo esc_js($designer_nonce); ?>",
                    designId: <?php echo $design_id; ?>
                };
            </script>
            <?php
        } else {
            // List designs
            $designs = CKPP_Cache::get('all_designs', 'designs');
            if ($designs === false) {
                $designs = get_posts([
                    'post_type' => 'ckpp_design',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                ]);
                CKPP_Cache::set('all_designs', $designs, 'designs', DAY_IN_SECONDS);
            }

            if (empty($designs)) {
                ?>
                <p><?php esc_html_e('No designs created yet. Click "Add New Design" to get started.', 'customkings'); ?></p>
                <?php
            } else {
                ?>
                <div class="ckpp-design-cards-grid">
                <?php
                foreach ($designs as $design) {
                    $edit_url = admin_url('admin.php?page=ckpp_designs&design_id=' . $design->ID);
                    $delete_url = wp_nonce_url(admin_url('admin.php?action=ckpp_delete_design&design_id=' . $design->ID), 'ckpp_delete_design_' . $design->ID);
                    $preview_image = get_post_meta($design->ID, '_ckpp_design_preview', true);
                    ?>
                    <div class="ckpp-design-card">
                        <div class="ckpp-design-card-thumbnail">
                            <?php if ($preview_image) : ?>
                                <img src="<?php echo esc_url($preview_image); ?>" alt="<?php echo esc_attr($design->post_title); ?> Preview" />
                            <?php else : ?>
                                <div class="ckpp-design-card-no-preview"><?php esc_html_e('No Preview', 'customkings'); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="ckpp-design-card-content">
                            <h3><?php echo esc_html($design->post_title); ?></h3>
                            <div class="ckpp-design-card-actions">
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-primary"><?php esc_html_e('Edit', 'customkings'); ?></a>
                                <a href="<?php echo esc_url($delete_url); ?>" class="button button-secondary delete" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this design?', 'customkings')); ?>');"><?php esc_html_e('Delete', 'customkings'); ?></a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                </div>
                <?php
            }
            ?>
            <p><a href="<?php echo esc_url(admin_url('admin.php?action=ckpp_create_design')); ?>" class="button button-primary"><?php esc_html_e('Add New Design', 'customkings'); ?></a></p>
            <?php
        }
        echo '</div>';
    }

    /**
     * Handle plugin data wipe and reinstall.
     */
    public function handle_wipe_reinstall() {
        CKPP_Security::verify_capability('manage_options');
        // Verify nonce
        CKPP_Security::verify_ajax_nonce('_wpnonce', 'ckpp_wipe_reinstall');

        // Ensure the action is correct
        if ( !isset($_POST['action']) || $_POST['action'] !== 'ckpp_wipe_reinstall' ) {
            CKPP_Error_Handler::log_security_event('Data Wipe/Reinstall Failed: Invalid Action', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'action_param' => $_POST['action'] ?? 'not_set'
            ]);
            CKPP_Error_Handler::handle_admin_error( __("ERROR: The required 'action' parameter for processing this request is missing or incorrect.", 'customkings'), __('Request Routing Error', 'customkings'), 400);
        }

        CKPP_Error_Handler::log_security_event('Data Wipe/Reinstall Initiated', [
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);

        // Only allow in debug mode
        $referer_url = wp_get_referer();
        if (!CKPP_Config::is_debug_mode()) {
            $error_msg = __('Debug mode must be enabled to perform this data reset operation.', 'customkings');
            CKPP_Error_Handler::log_security_event('Data Wipe/Reinstall Failed: Debug Mode Not Enabled', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            if ($referer_url) {
                wp_redirect(add_query_arg('ckpp_wipe_error', urlencode($error_msg), $referer_url));
                exit;
            } else {
                CKPP_Error_Handler::handle_admin_error($error_msg . __(' (Could not determine a page to redirect back to.)', 'customkings'), __('Debug Mode Required', 'customkings'), 400);
            }
        }

        global $wpdb;
        $error_occurred = false;
        $error_msg = '';

        try {
            // Ensure WP_Filesystem is loaded for directory deletion
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
            }
            WP_Filesystem();
            global $wp_filesystem;

            if (!$wp_filesystem) {
                 $error_msg = __('Could not initialize WordPress Filesystem API. This is required for file operations.', 'customkings');
                CKPP_Error_Handler::log_security_event('Data Wipe/Reinstall Failed: Filesystem API Init Error', [
                    'user_id' => get_current_user_id(),
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'error_message' => $error_msg
                ]);
                if ($referer_url) {
                    wp_redirect(add_query_arg('ckpp_wipe_error', urlencode($error_msg), $referer_url));
                    exit;
                } else {
                    CKPP_Error_Handler::handle_admin_error($error_msg, __('Filesystem Error', 'customkings'), 500);
                }
            }

            // 1. Delete all custom post types (designs)
            $designs = get_posts([
                'post_type' => 'ckpp_design',
                'numberposts' => -1,
                'fields' => 'ids',
                'post_status' => 'any'
            ]);
            foreach ($designs as $design_id) {
                wp_delete_post($design_id, true); // true for force delete
            }

            // 2. Delete all plugin-related post meta
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_ckpp_design_id'");
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_ckpp_design_%'");
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_product_personalization_config_json'");

            // 3. Delete WooCommerce order item meta related to personalization
            if (class_exists('WooCommerce')) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_ckpp_personalization_data'");
                $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_ckpp_print_file_url'");
            }

            // 4. Delete custom tables (fonts, clipart)
            if (class_exists('CKPP_Fonts')) {
                CKPP_Fonts::delete_table();
            }
            if (class_exists('CKPP_Clipart')) {
                CKPP_Clipart::delete_table();
            }

            // 5. Delete uploaded files (fonts, clipart, customer uploads, print files)
            $upload_dir_info = wp_upload_dir();
            $ckpp_upload_dirs = [
                $upload_dir_info['basedir'] . '/ckpp_fonts',
                $upload_dir_info['basedir'] . '/ckpp_clipart',
                $upload_dir_info['basedir'] . '/ckpp_customer_uploads',
                $upload_dir_info['basedir'] . '/ckpp_print_files',
            ];

            foreach ($ckpp_upload_dirs as $dir_path) {
                $this->delete_directory_recursively($dir_path);
            }

            // 6. Delete plugin options
            delete_option('ckpp_enabled');
            delete_option('ckpp_license_key');
            delete_option('ckpp_accent_color');
            delete_option('ckpp_debug_mode'); // Also delete debug mode option

            // 7. Reinstall plugin data (recreate tables, etc.)
            // This assumes the plugin's activation hook handles table creation.
            // For a full reinstall, deactivate and reactivate might be needed,
            // but for data wipe, just recreating tables is sufficient.
            if (class_exists('CKPP_Fonts')) {
                CKPP_Fonts::create_table();
            }
            if (class_exists('CKPP_Clipart')) {
                CKPP_Clipart::create_table();
            }

            // Clear all plugin cache after wipe
            CKPP_Cache::clear();

         } catch (Exception $e) {
             $error_occurred = true;
             $error_msg = __('An unexpected error occurred during the data reset operation:', 'customkings') . ' ' . $e->getMessage();
             CKPP_Error_Handler::log_security_event('Data Wipe/Reinstall Failed: Exception Caught', [
                 'user_id' => get_current_user_id(),
                 'ip_address' => $_SERVER['REMOTE_ADDR'],
                 'error_message' => $e->getMessage(),
                 'stack_trace' => $e->getTraceAsString()
             ]);
         }

         if ($error_occurred) {
             CKPP_Error_Handler::log_security_event('Data Wipe/Reinstall Completed: Failed', [
                 'user_id' => get_current_user_id(),
                 'ip_address' => $_SERVER['REMOTE_ADDR'],
                 'final_message' => $error_msg
             ]);
             if ($referer_url) {
                 wp_redirect(add_query_arg('ckpp_wipe_error', urlencode($error_msg), $referer_url));
                 exit;
             } else {
                 CKPP_Error_Handler::handle_admin_error($error_msg, __('Operation Failed', 'customkings'), 500);
             }
         } else {
             CKPP_Error_Handler::log_security_event('Data Wipe/Reinstall Completed: Success', [
                 'user_id' => get_current_user_id(),
                 'ip_address' => $_SERVER['REMOTE_ADDR']
             ]);
             if ($referer_url) {
                  wp_redirect(add_query_arg('ckpp_wiped', '1', $referer_url));
                  exit;
             } else {
                  CKPP_Error_Handler::handle_admin_error(__('SUCCESS: Plugin data wiped and reinstalled. Could not redirect automatically.', 'customkings'), __('Operation Successful', 'customkings'), 200);
             }
         }
    }

    /**
     * Recursively delete a directory and its contents.
     *
     * @param string $dir The directory path.
     * @return bool True on success, false on failure.
     */
    private function delete_directory_recursively($dir) {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
        }
        WP_Filesystem();
        global $wp_filesystem;

        if (!file_exists($dir)) {
            return true; // Directory doesn't exist, so success.
        }

        if (!$wp_filesystem->is_dir($dir)) {
            return unlink($dir); // It's a file, just delete it.
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            if ($wp_filesystem->is_dir("$dir/$file")) {
                $this->delete_directory_recursively("$dir/$file");
            } else {
                $wp_filesystem->delete("$dir/$file");
            }
        }
        return $wp_filesystem->rmdir($dir);
    }
}