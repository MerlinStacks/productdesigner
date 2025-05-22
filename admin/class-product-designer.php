<?php
/**
 * Product Designer Admin for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CKPP_Product_Designer
 * Handles admin UI and logic for product personalization designs.
 */
class CKPP_Product_Designer {
    /**
     * Register hooks for CPT, admin UI, AJAX, and actions.
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_design_cpt' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ckpp_save_design', [ $this, 'ajax_save_design' ] );
        add_action( 'wp_ajax_ckpp_load_design', [ $this, 'ajax_load_design' ] );
        add_action( 'admin_action_ckpp_create_design', [ $this, 'handle_create_design' ] );
        add_action( 'admin_action_ckpp_delete_design', [ $this, 'handle_delete_design' ] );
        add_action( 'wp_ajax_ckpp_clone_design', [ $this, 'ajax_clone_design' ] );
        add_action( 'wp_ajax_ckpp_upload_image', [ $this, 'ajax_upload_image' ] );
        add_action( 'admin_action_ckpp_delete_image', [ $this, 'handle_delete_image' ] );
        add_action( 'admin_post_ckpp_delete_image', [ $this, 'handle_delete_image' ] );
        add_action( 'admin_post_ckpp_bulk_delete_images', [ $this, 'handle_bulk_delete_images' ] );
    }

    /**
     * Register the custom post type for designs.
     */
    public function register_design_cpt() {
        register_post_type( 'ckpp_design', [
            'labels' => [
                'name' => __( 'Designs', 'customkings' ),
                'singular_name' => __( 'Design', 'customkings' ),
            ],
            'public' => false,
            'show_ui' => false,
            'supports' => [ 'title' ],
        ] );
    }

    /**
     * Add admin submenu pages for Designs and Images.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ckpp_admin',
            __( 'Images', 'customkings' ),
            __( 'Images', 'customkings' ),
            'manage_options',
            'ckpp_images',
            [ $this, 'render_images_page' ]
        );
    }

    /**
     * Enqueue assets for the designer admin page.
     *
     * @param string $hook
     */
    public function enqueue_assets( $hook ) {
        if ( isset($_GET['page']) && $_GET['page'] === 'ckpp_images' ) {
            // Register Pickr if not already registered
            if (!wp_script_is('pickr', 'registered')) {
                wp_register_script('pickr', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr', [], null, true);
                wp_register_style('pickr-classic', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css', [], null);
            }
            wp_enqueue_script( 'ckpp-designer', plugins_url( '../assets/designer.js', __FILE__ ), [ 'jquery', 'pickr' ], '1.0', true );
            wp_enqueue_style( 'ckpp-designer', plugins_url( '../assets/designer.css', __FILE__ ), [], '1.0' );
            wp_enqueue_style( 'pickr-classic' );
            if (wp_script_is('ckpp-designer', 'enqueued')) {
                wp_localize_script( 'ckpp-designer', 'CKPPDesigner', [
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'ckpp_designer_nonce' ),
                    'designId' => isset($_GET['design_id']) ? intval($_GET['design_id']) : 0,
                ] );
            }
        }
    }

    /**
     * AJAX: Save a design's configuration. Requires nonce and capability.
     * 
     * @return void Sends JSON response
     */
    public function ajax_save_design() {
        // Verify nonce and capability using our security class
        CKPP_Security::verify_ajax_nonce('nonce', 'ckpp_designer_nonce');
        CKPP_Security::verify_capability('manage_options');
        CKPP_Security::check_rate_limit('ckpp_save_design', 5, 60); // 5 requests per 60 seconds
        
        $debug_mode = CKPP_Config::is_debug_mode();
        
        try {
            // Sanitize and validate input
            $design_id = CKPP_Security::sanitize_input($_POST['designId'] ?? 0, 'int');
            $title = CKPP_Security::sanitize_input($_POST['title'] ?? '');
            $config = CKPP_Security::sanitize_input($_POST['config'] ?? '', 'json');
            $preview = CKPP_Security::sanitize_input($_POST['preview'] ?? '');
            
            // Validate required fields
            if (empty($title) || empty($config)) {
                throw new Exception(__('Missing required fields', 'customkings'));
            }
            
            // Debug logging
            if ($debug_mode) {
                error_log('CKPP Debug: Saving design ID: ' . $design_id);
                error_log('CKPP Debug: Design title: ' . $title);
            }
            
            // Validate design ID exists if updating
            if ($design_id) {
                $post_type = get_post_type($design_id);
                $post_status = get_post_status($design_id);
                
                // Check if design exists and is not in trash
                if ($post_type !== 'ckpp_design' || $post_status === 'trash') {
                    if ($debug_mode) {
                        error_log('CKPP Debug: Invalid design ID, not found, or trashed: ' . $design_id . ', type: ' . $post_type . ', status: ' . $post_status);
                    }
                    throw new Exception(__('Design not found', 'customkings'));
                }
            }
            
            if ($design_id) {
                // Update existing design
                $result = wp_update_post([
                    'ID' => $design_id, 
                    'post_title' => $title
                ], true);
                
                if (is_wp_error($result)) {
                    throw new Exception($result->get_error_message());
                }
                
                update_post_meta($design_id, '_ckpp_design_config', $config);
                CKPP_Cache::set('design_config_' . $design_id, $config, 'designs', HOUR_IN_SECONDS);
                
                if ($preview && strpos($preview, 'data:image/png;base64,') === 0) {
                    update_post_meta($design_id, '_ckpp_design_preview', $preview);
                    CKPP_Cache::set('design_preview_' . $design_id, $preview, 'designs', DAY_IN_SECONDS);
                }
            } else {
                // Create new design
                $result = wp_insert_post([
                    'post_type' => 'ckpp_design',
                    'post_title' => $title,
                    'post_status' => 'publish'
                ], true);
                
                if (is_wp_error($result)) {
                    throw new Exception($result->get_error_message());
                }
                
                $design_id = $result;
                update_post_meta($design_id, '_ckpp_design_config', $config);
                CKPP_Cache::set('design_config_' . $design_id, $config, 'designs', HOUR_IN_SECONDS);
                
                if ($preview && strpos($preview, 'data:image/png;base64,') === 0) {
                    update_post_meta($design_id, '_ckpp_design_preview', $preview);
                    CKPP_Cache::set('design_preview_' . $design_id, $preview, 'designs', DAY_IN_SECONDS);
                }
            }
            
            // Invalidate cache for this specific design and the all_designs list
            if ($design_id) {
                CKPP_Cache::invalidate_key('design_config_' . $design_id, 'designs');
                CKPP_Cache::invalidate_key('design_preview_' . $design_id, 'designs');
            }
            CKPP_Cache::invalidate_key('all_designs', 'designs'); // Invalidate the list of all designs
            
            if ($debug_mode) {
                error_log('CKPP Debug: Design saved successfully, ID: ' . $design_id);
            }

            CKPP_Error_Handler::log_security_event(
                $design_id ? 'Design Updated Successfully' : 'Design Created Successfully',
                [
                    'design_id' => $design_id,
                    'title' => $title,
                    'user_id' => get_current_user_id(),
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                ]
            );
            
            wp_send_json_success([
                'designId' => $design_id,
                'message' => $design_id ? __('Design updated successfully', 'customkings') : __('Design created successfully', 'customkings')
            ]);
            
        } catch (Exception $e) {
            if (isset($debug_mode) && $debug_mode) {
                error_log('CKPP Error in ajax_save_design: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
            }
            CKPP_Error_Handler::log_security_event('Design Save Failed', [
                'design_id' => $design_id ?? 'new',
                'title' => $title ?? 'N/A',
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            CKPP_Error_Handler::handle_ajax_error( $e->getMessage(), [ 'code' => $e->getCode() ?: 500 ] );
        }
    }

    /**
     * AJAX: Load a design's configuration. Requires nonce and capability.
     */
    public function ajax_load_design() {
        CKPP_Security::verify_ajax_nonce('nonce', 'ckpp_designer_nonce');
        CKPP_Security::verify_capability('manage_options');
        
        $debug_mode = CKPP_Config::is_debug_mode();
        
        // Validate required parameters
        if ( ! isset( $_GET['designId'] ) ) {
            if ( $debug_mode ) {
                error_log('CKPP Debug: Missing design ID in load request');
            }
            CKPP_Error_Handler::handle_ajax_error( __( 'Missing design ID', 'customkings' ) );
        }
        
        $design_id = CKPP_Security::sanitize_input( $_GET['designId'] ?? 0, 'int' );
        
        if ( $debug_mode ) {
            error_log('CKPP Debug: Loading design ID: ' . $design_id);
        }
        
        // Validate design exists
        if ( ! $design_id ) {
            if ( $debug_mode ) {
                error_log('CKPP Debug: Missing design ID: ' . $design_id);
            }
            CKPP_Error_Handler::handle_ajax_error( __( 'Design not found', 'customkings' ) );
        }
        
        // Check post type and status
        $post_type = get_post_type( $design_id );
        $post_status = get_post_status( $design_id );
        if ( $post_type !== 'ckpp_design' || $post_status === 'trash' ) {
            if ( $debug_mode ) {
                error_log('CKPP Debug: Invalid design ID, not found, or trashed: ' . $design_id . ', type: ' . $post_type . ', status: ' . $post_status);
            }
            CKPP_Error_Handler::handle_ajax_error( __( 'Design not found', 'customkings' ) );
        }
        
        try {
            // Try to get config from cache first
            $config = CKPP_Cache::get('design_config_' . $design_id, 'designs');
            $preview = CKPP_Cache::get('design_preview_' . $design_id, 'designs');

            if ($config === false) {
                $config = get_post_meta( $design_id, '_ckpp_design_config', true );
                CKPP_Cache::set('design_config_' . $design_id, $config, 'designs', HOUR_IN_SECONDS);
            }

            if ($preview === false) {
                $preview = get_post_meta( $design_id, '_ckpp_design_preview', true );
                CKPP_Cache::set('design_preview_' . $design_id, $preview, 'designs', DAY_IN_SECONDS);
            }

            $title = get_the_title( $design_id );
            
            // Handle empty config
            if ( empty( $config ) ) {
                if ( $debug_mode ) {
                    error_log('CKPP Debug: Empty config for design ID: ' . $design_id);
                }
                // Return empty object rather than null to prevent JS errors
                $config = '{}';
            }
            
            // Ensure we return decoded config to avoid double-encoding issues
            $config_data = $config;
            // Only decode if it's a string and valid JSON
            if (is_string($config)) {
                $decoded = json_decode($config, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $config_data = $decoded;
                    if ($debug_mode) {
                        error_log('CKPP Debug: Returning decoded config object to prevent double-encoding');
                    }
                }
            }
            
            if ( $debug_mode ) {
                error_log('CKPP Debug: Successfully loaded design: ' . $title);
            }

            CKPP_Error_Handler::log_security_event('Design Loaded Successfully', [
                'design_id' => $design_id,
                'title' => $title,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            
            wp_send_json_success([
                'config' => $config_data,
                'title' => $title,
                'preview' => $preview, // Include preview in the response
                'message' => __( 'Design loaded successfully', 'customkings' )
            ]);
            
        } catch ( Exception $e ) {
            if ( $debug_mode ) {
                error_log('CKPP Debug: Exception loading design: ' . $e->getMessage());
            }
            CKPP_Error_Handler::log_security_event('Design Load Failed', [
                'design_id' => $design_id ?? 'N/A',
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'error_message' => $e->getMessage()
            ]);
            CKPP_Error_Handler::handle_ajax_error( __( 'Error loading design', 'customkings' ), [ 'error' => $e->getMessage() ] );
        }
    }

    /**
     * Handle creation of a new design post.
     */
    public function handle_create_design() {
        CKPP_Security::verify_capability('manage_options');
        // Create a new design post
        $design_id = wp_insert_post([
            'post_type' => 'ckpp_design',
            'post_title' => __( 'Untitled Design', 'customkings' ),
            'post_status' => 'publish',
        ]);
        if ( $design_id ) {
            // Invalidate the all_designs cache when a new design is created
            CKPP_Cache::invalidate_key('all_designs', 'designs');
            CKPP_Error_Handler::log_security_event('Design Created via Admin Action', [
                'design_id' => $design_id,
                'title' => get_the_title($design_id),
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            wp_redirect( admin_url( 'admin.php?page=ckpp_designs&design_id=' . $design_id ) );
            exit;
        } else {
            CKPP_Error_Handler::log_security_event('Design Creation Failed via Admin Action', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'error_message' => 'Failed to insert post'
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'Failed to create design.', 'customkings' ) );
        }
    }

    /**
     * Handle deletion of a design post. Requires nonce and capability.
     */
    public function handle_delete_design() {
        CKPP_Security::verify_capability('manage_options');
        $design_id = isset($_GET['design_id']) ? intval($_GET['design_id']) : 0;
        if ( ! $design_id ) {
            CKPP_Error_Handler::handle_admin_error( __( 'Invalid design ID.', 'customkings' ) );
        }
        CKPP_Security::verify_ajax_nonce('_wpnonce', 'ckpp_delete_design_' . $design_id);
        
        // Invalidate cache for this specific design before deletion
        CKPP_Cache::invalidate_key('design_config_' . $design_id, 'designs');
        CKPP_Cache::invalidate_key('design_preview_' . $design_id, 'designs');
        CKPP_Cache::invalidate_key('all_designs', 'designs'); // Invalidate the list of all designs

        $deleted_post = wp_delete_post( $design_id, true );
        if ( $deleted_post ) {
            CKPP_Error_Handler::log_security_event('Design Deleted Successfully', [
                'design_id' => $design_id,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            wp_redirect( admin_url( 'admin.php?page=ckpp_designs&ckpp_deleted=1' ) );
            exit;
        } else {
            CKPP_Error_Handler::log_security_event('Design Deletion Failed', [
                'design_id' => $design_id,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'error_message' => 'wp_delete_post returned false'
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'Failed to delete design.', 'customkings' ) );
        }
    }

    /**
     * AJAX: Clone a design. Requires nonce and capability.
     */
    public function ajax_clone_design() {
        CKPP_Security::verify_ajax_nonce('nonce', 'ckpp_designer_nonce');
        CKPP_Security::verify_capability('manage_options');
        $source_id = CKPP_Security::sanitize_input($_POST['designId'] ?? 0, 'int');
        $title = CKPP_Security::sanitize_input($_POST['title'] ?? '', 'text');
        $config = get_post_meta($source_id, '_ckpp_design_config', true);
        if ( ! $config ) {
            CKPP_Error_Handler::handle_ajax_error( __( 'Source design not found.', 'customkings' ) );
        }
        $new_id = wp_insert_post([
            'post_type' => 'ckpp_design',
            'post_title' => $title,
            'post_status' => 'publish',
        ]);
        if ($new_id) {
            update_post_meta($new_id, '_ckpp_design_config', $config);
            // Invalidate the all_designs cache when a new design is cloned
            CKPP_Cache::invalidate_key('all_designs', 'designs');
            CKPP_Error_Handler::log_security_event('Design Cloned Successfully', [
                'source_design_id' => $source_id,
                'new_design_id' => $new_id,
                'new_design_title' => $title,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            wp_send_json_success([ 'designId' => $new_id ]);
        } else {
            CKPP_Error_Handler::log_security_event('Design Clone Failed', [
                'source_design_id' => $source_id,
                'new_design_title' => $title,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'error_message' => 'Failed to insert new post for clone'
            ]);
            CKPP_Error_Handler::handle_ajax_error( __( 'Failed to create template.', 'customkings' ) );
        }
    }

    /**
     * AJAX: Upload an image for use in designs. Requires nonce and capability.
     */
    public function ajax_upload_image() {
        CKPP_Security::verify_ajax_nonce('nonce', 'ckpp_designer_nonce');
        CKPP_Security::verify_capability('manage_options');
        CKPP_Security::check_rate_limit('ckpp_upload_image', 5, 60); // 5 requests per 60 seconds

        $allowed_mime_types = [
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/gif',
            'image/svg+xml',
        ];
        $upload_result = CKPP_Security::handle_file_upload(
            'file',
            'ckpp_images',
            $allowed_mime_types
        );

        if ( is_wp_error( $upload_result ) ) {
            CKPP_Error_Handler::handle_ajax_error( $upload_result->get_error_message() );
        }

        wp_send_json_success( [ 'url' => $upload_result['url'] ] );
    }

    /**
     * Render the Images admin page.
     */
    public function render_images_page() {
        CKPP_Security::verify_capability('manage_options');
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir['basedir'] . '/ckpp_images/';
        $url_base = $upload_dir['baseurl'] . '/ckpp_images/';
        echo '<div class="wrap"><h1>' . esc_html__( 'Uploaded Images', 'customkings' ) . '</h1>';
        if ( isset($_GET['deleted']) && $_GET['deleted'] === '1' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Image deleted.', 'customkings') . '</p></div>';
        }
        if ( isset($_GET['bulk_deleted']) && $_GET['bulk_deleted'] === '1' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Selected images deleted.', 'customkings') . '</p></div>';
        }
        if ( isset($_GET['ckpp_delete_error']) ) {
            $err = sanitize_text_field($_GET['ckpp_delete_error']);
            $msg = '';
            switch ($err) {
                case 'unauthorized':
                    $msg = __('You are not authorized to delete images.', 'customkings'); break;
                case 'invalid_nonce':
                    $msg = __('Security check failed. Please try again.', 'customkings'); break;
                case 'no_image':
                    $msg = __('No image specified for deletion.', 'customkings'); break;
                case 'not_found':
                    $msg = __('Image file not found. It may have already been deleted.', 'customkings'); break;
                case 'delete_failed':
                    $msg = __('Failed to delete the image file. Check file permissions.', 'customkings'); break;
                default:
                    $msg = __('Unknown error occurred while deleting image.', 'customkings');
            }
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        }
        if ( ! file_exists( $dir ) ) {
            echo '<p>' . esc_html__( 'No images uploaded yet.', 'customkings' ) . '</p>';
            echo '</div>';
            return;
        }
        $images = array_filter( scandir( $dir ), function($f) use ($dir) {
            return is_file( $dir . $f ) && preg_match( '/\.(png|jpe?g|gif|svg)$/i', $f );
        });
        if ( empty( $images ) ) {
            echo '<p>' . esc_html__( 'No images uploaded yet.', 'customkings' ) . '</p>';
            echo '</div>';
            return;
        }
        // Search/filter input
        echo '<input type="text" id="ckpp-image-search" placeholder="Search images..." style="margin-bottom:1.5em; width:300px; font-size:15px; padding:4px 8px;" />';
        echo '<div class="ckpp-clipart-grid" id="ckpp-images-grid">';
        $has_images = false;
        foreach ( $images as $img ) {
            $file = $dir . $img;
            $url = $url_base . rawurlencode($img);
            $date = date( 'Y-m-d H:i', filemtime( $file ) );
            $size = filesize($file);
            $size_str = $size > 1048576 ? round($size/1048576,2).' MB' : round($size/1024,1).' KB';
            $delete_url = wp_nonce_url( admin_url( 'admin.php?action=ckpp_delete_image&ckpp_delete_image=' . urlencode($img) ), 'ckpp_delete_image_' . $img );
            $has_images = true;
            echo '<div class="ckpp-clipart-card ckpp-image-card" data-img-name="' . esc_attr($img) . '">';
            echo '<div class="ckpp-clipart-thumb">';
            echo '<img src="' . esc_url( $url ) . '" alt="" style="width:100%;height:100%;object-fit:contain;max-width:120px;max-height:120px;" />';
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
            wp_nonce_field( 'ckpp_delete_image_' . $img );
            echo '<input type="hidden" name="action" value="ckpp_delete_image" />';
            echo '<input type="hidden" name="ckpp_delete_image" value="' . esc_attr($img) . '" />';
            echo '<button type="submit" class="ckpp-clipart-delete-btn" title="' . esc_attr__('Delete this image', 'customkings') . '" onclick="return confirm(\'' . esc_js( __( 'Delete this image?', 'customkings' ) ) . '\');">';
            echo '<span class="ckpp-clipart-delete-svg" aria-hidden="true">';
            echo '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" focusable="false" xmlns="http://www.w3.org/2000/svg">';
            echo '<circle cx="9" cy="9" r="8" stroke="#b32d2e" stroke-width="2" fill="none"/>';
            echo '<line x1="6" y1="6" x2="12" y2="12" stroke="#b32d2e" stroke-width="2" stroke-linecap="round"/>';
            echo '<line x1="12" y1="6" x2="6" y2="12" stroke="#b32d2e" stroke-width="2" stroke-linecap="round"/>';
            echo '</svg>';
            echo '</span>';
            echo '</button>';
            echo '</form>';
            echo '</div>';
            echo '<div class="ckpp-clipart-meta">';
            echo '<div class="ckpp-clipart-name">' . esc_html( $img ) . '</div>';
            echo '<div class="ckpp-clipart-tags">' . esc_html( $size_str ) . ' &bull; ' . esc_html( $date ) . '</div>';
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
     * Handle deletion of a single image. Requires capability.
     */
    public function handle_delete_image() {
        CKPP_Security::verify_capability('manage_options');
        $img = null;
        $error = '';
        if ( isset($_GET['ckpp_delete_image']) ) {
            $img = basename( $_GET['ckpp_delete_image'] );
            CKPP_Security::verify_ajax_nonce('_wpnonce', 'ckpp_delete_image_' . $img);
        } elseif ( isset($_POST['ckpp_delete_image']) ) {
            $img = basename( $_POST['ckpp_delete_image'] ); // Corrected typo here
            CKPP_Security::verify_ajax_nonce('_wpnonce', 'ckpp_delete_image_' . $img);
        } else {
            $error = 'no_image';
        }
        $upload_dir = wp_upload_dir();
        $file = $img ? $upload_dir['basedir'] . '/ckpp_images/' . $img : '';
        if ( !$error && $img ) {
            if ( file_exists( $file ) ) {
                if ( ! unlink( $file ) ) {
                    $error = 'delete_failed';
                }
            } else {
                $error = 'not_found';
            }
        }
        if ( $error ) {
            CKPP_Error_Handler::log_security_event('Image Delete Failed', [
                'image' => $img ?? 'N/A',
                'error' => $error,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            $redirect_url = add_query_arg( 'ckpp_delete_error', $error, admin_url( 'admin.php?page=ckpp_images' ) );
        } else {
            CKPP_Error_Handler::log_security_event('Image Deleted Successfully', [
                'image' => $img,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            $redirect_url = add_query_arg( 'deleted', '1', admin_url( 'admin.php?page=ckpp_images' ) );
        }
        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Handle bulk deletion of images. Requires capability.
     */
    public function handle_bulk_delete_images() {
        CKPP_Security::verify_capability('manage_options');
        if ( ! isset($_POST['ckpp_bulk_images']) || ! is_array($_POST['ckpp_bulk_images']) ) {
            wp_redirect( admin_url( 'admin.php?page=ckpp_images' ) );
            exit;
        }
        CKPP_Security::verify_ajax_nonce('ckpp_bulk_delete_nonce', 'ckpp_bulk_delete_images');
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir['basedir'] . '/ckpp_images/';
        $deleted_count = 0;
        $failed_count = 0;
        $failed_images = [];
        
        foreach ( $_POST['ckpp_bulk_images'] as $img ) {
            $file = $dir . basename($img);
            if ( file_exists( $file ) ) {
                if (unlink( $file )) {
                    $deleted_count++;
                } else {
                    $failed_count++;
                    $failed_images[] = $img;
                }
            }
        }

        if ($failed_count > 0) {
            CKPP_Error_Handler::log_security_event('Bulk Image Delete Partially Failed', [
                'deleted_count' => $deleted_count,
                'failed_count' => $failed_count,
                'failed_images' => $failed_images,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
        } else {
            CKPP_Error_Handler::log_security_event('Bulk Image Delete Successful', [
                'deleted_count' => $deleted_count,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
        }
        
        wp_redirect( admin_url( 'admin.php?page=ckpp_images&bulk_deleted=1' ) );
        exit;
    }
}