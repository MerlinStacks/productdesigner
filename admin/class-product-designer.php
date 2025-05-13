<?php
/**
 * Product Designer Admin for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CKPP_Product_Designer {
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
        add_action( 'admin_post_ckpp_bulk_delete_images', [ $this, 'handle_bulk_delete_images' ] );
    }

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

    public function add_admin_menu() {
        add_submenu_page(
            'ckpp_admin',
            __( 'Designs', 'customkings' ),
            __( 'Designs', 'customkings' ),
            'manage_options',
            'ckpp_designs',
            [ $this, 'render_designs_page' ]
        );
        add_submenu_page(
            'ckpp_admin',
            __( 'Images', 'customkings' ),
            __( 'Images', 'customkings' ),
            'manage_options',
            'ckpp_images',
            [ $this, 'render_images_page' ]
        );
    }

    public function render_designs_page() {
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
    }

    public function enqueue_assets( $hook ) {
        if ( isset($_GET['page']) && $_GET['page'] === 'ckpp_designs' ) {
            // Register Pickr if not already registered
            if (!wp_script_is('pickr', 'registered')) {
                wp_register_script('pickr', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr', [], null, true);
                wp_register_style('pickr-classic', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css', [], null);
            }
            wp_enqueue_script( 'ckpp-designer', plugins_url( '../assets/designer.js', __FILE__ ), [ 'jquery', 'pickr' ], '1.0', true );
            wp_enqueue_style( 'ckpp-designer', plugins_url( '../assets/designer.css', __FILE__ ), [], '1.0' );
            wp_enqueue_style( 'pickr-classic' );
            wp_localize_script( 'ckpp-designer', 'CKPPDesigner', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ckpp_designer_nonce' ),
                'designId' => isset($_GET['design_id']) ? intval($_GET['design_id']) : 0,
            ] );
        }
    }

    public function ajax_save_design() {
        check_ajax_referer( 'ckpp_designer_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( __( 'Unauthorized', 'customkings' ) );
        $design_id = intval( $_POST['designId'] );
        $title = sanitize_text_field( $_POST['title'] );
        $config = wp_unslash( $_POST['config'] );
        if ( $design_id ) {
            wp_update_post([ 'ID' => $design_id, 'post_title' => $title ]);
            update_post_meta( $design_id, '_ckpp_design_config', $config );
        } else {
            $design_id = wp_insert_post([ 'post_type' => 'ckpp_design', 'post_title' => $title, 'post_status' => 'publish' ]);
            update_post_meta( $design_id, '_ckpp_design_config', $config );
        }
        wp_send_json_success([ 'designId' => $design_id ]);
    }

    public function ajax_load_design() {
        check_ajax_referer( 'ckpp_designer_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( __( 'Unauthorized', 'customkings' ) );
        $design_id = intval( $_GET['designId'] );
        $config = get_post_meta( $design_id, '_ckpp_design_config', true );
        $title = get_the_title( $design_id );
        wp_send_json_success([ 'config' => $config, 'title' => $title ]);
    }

    public function handle_create_design() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'customkings' ) );
        }
        // Create a new design post
        $design_id = wp_insert_post([
            'post_type' => 'ckpp_design',
            'post_title' => __( 'Untitled Design', 'customkings' ),
            'post_status' => 'publish',
        ]);
        if ( $design_id ) {
            wp_redirect( admin_url( 'admin.php?page=ckpp_designs&design_id=' . $design_id ) );
            exit;
        } else {
            wp_die( __( 'Failed to create design.', 'customkings' ) );
        }
    }

    public function handle_delete_design() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'customkings' ) );
        }
        $design_id = isset($_GET['design_id']) ? intval($_GET['design_id']) : 0;
        if ( ! $design_id ) {
            wp_die( __( 'Invalid design ID.', 'customkings' ) );
        }
        check_admin_referer( 'ckpp_delete_design_' . $design_id );
        wp_delete_post( $design_id, true );
        wp_redirect( admin_url( 'admin.php?page=ckpp_designs&ckpp_deleted=1' ) );
        exit;
    }

    public function ajax_clone_design() {
        check_ajax_referer( 'ckpp_designer_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( __( 'Unauthorized', 'customkings' ) );
        $source_id = intval($_POST['designId']);
        $title = sanitize_text_field($_POST['title']);
        $config = get_post_meta($source_id, '_ckpp_design_config', true);
        if (!$config) wp_send_json_error( __( 'Source design not found.', 'customkings' ) );
        $new_id = wp_insert_post([
            'post_type' => 'ckpp_design',
            'post_title' => $title,
            'post_status' => 'publish',
        ]);
        if ($new_id) {
            update_post_meta($new_id, '_ckpp_design_config', $config);
            wp_send_json_success([ 'designId' => $new_id ]);
        } else {
            wp_send_json_error( __( 'Failed to create template.', 'customkings' ) );
        }
    }

    public function ajax_upload_image() {
        check_ajax_referer( 'ckpp_designer_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( __( 'Unauthorized', 'customkings' ) );
        if ( empty( $_FILES['file'] ) ) wp_send_json_error( __( 'No file uploaded.', 'customkings' ) );
        $file = $_FILES['file'];
        $allowed = [ 'image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/svg+xml' ];
        if ( ! in_array( $file['type'], $allowed ) ) wp_send_json_error( __( 'Invalid file type.', 'customkings' ) );
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/ckpp_images/';
        if ( ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
        }
        $filename = wp_unique_filename( $target_dir, $file['name'] );
        $target_file = $target_dir . $filename;
        if ( ! move_uploaded_file( $file['tmp_name'], $target_file ) ) {
            wp_send_json_error( __( 'Failed to move uploaded file.', 'customkings' ) );
        }
        $url = $upload_dir['baseurl'] . '/ckpp_images/' . $filename;
        wp_send_json_success([ 'url' => $url ]);
    }

    public function render_images_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'customkings' ) );
        }
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
        echo '<input type="text" id="ckpp-image-search" placeholder="Search images..." style="margin-bottom:1em; width:300px; font-size:15px; padding:4px 8px;" />';
        // Bulk delete form
        echo '<form method="post" action="' . esc_url( admin_url('admin-post.php') ) . '" id="ckpp-bulk-delete-form" onsubmit="return confirm(\'Delete selected images?\');">';
        echo '<input type="hidden" name="action" value="ckpp_bulk_delete_images" />';
        wp_nonce_field('ckpp_bulk_delete_images', 'ckpp_bulk_delete_nonce');
        echo '<button type="submit" name="ckpp_bulk_delete" class="button" style="margin-bottom:1em;">Bulk Delete</button>';
        echo '<table class="widefat fixed striped" id="ckpp-images-table" style="max-width:900px;">';
        echo '<thead><tr><th><input type="checkbox" id="ckpp-select-all" /></th><th>Preview</th><th>File Name</th><th>Size</th><th>Upload Date</th><th>Actions</th></tr></thead><tbody>';
        foreach ( $images as $img ) {
            $file = $dir . $img;
            $url = $url_base . rawurlencode($img);
            $date = date( 'Y-m-d H:i', filemtime( $file ) );
            $size = filesize($file);
            $size_str = $size > 1048576 ? round($size/1048576,2).' MB' : round($size/1024,1).' KB';
            $delete_url = wp_nonce_url( admin_url( 'admin.php?action=ckpp_delete_image&ckpp_delete_image=' . urlencode($img) ), 'ckpp_delete_image_' . $img );
            echo '<tr>';
            echo '<td><input type="checkbox" name="ckpp_bulk_images[]" value="' . esc_attr($img) . '" /></td>';
            echo '<td><img src="' . esc_url( $url ) . '" style="max-width:80px;max-height:80px;" alt="" /></td>';
            echo '<td>' . esc_html( $img ) . '</td>';
            echo '<td>' . esc_html( $size_str ) . '</td>';
            echo '<td>' . esc_html( $date ) . '</td>';
            echo '<td><a href="' . esc_url( $delete_url ) . '" style="color:#a00;" onclick="return confirm(\'Delete this image?\');">Delete</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</form>';
        // JS for search/filter and select all
        echo '<script>
        document.getElementById("ckpp-image-search").addEventListener("input", function() {
            var val = this.value.toLowerCase();
            var rows = document.querySelectorAll("#ckpp-images-table tbody tr");
            rows.forEach(function(row) {
                var name = row.cells[2].textContent.toLowerCase();
                row.style.display = name.indexOf(val) !== -1 ? "" : "none";
            });
        });
        document.getElementById("ckpp-select-all").addEventListener("change", function() {
            var checked = this.checked;
            document.querySelectorAll("#ckpp-images-table tbody input[type=checkbox]").forEach(function(cb) { cb.checked = checked; });
        });
        </script>';
        echo '</div>';
    }

    public function handle_delete_image() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'customkings' ) );
        }
        if ( ! isset($_GET['ckpp_delete_image']) ) {
            wp_die( __( 'No image specified.', 'customkings' ) );
        }
        $img = basename( $_GET['ckpp_delete_image'] );
        check_admin_referer( 'ckpp_delete_image_' . $img );
        $upload_dir = wp_upload_dir();
        $file = $upload_dir['basedir'] . '/ckpp_images/' . $img;
        if ( file_exists( $file ) ) {
            unlink( $file );
        }
        wp_redirect( admin_url( 'admin.php?page=ckpp_images&deleted=1' ) );
        exit;
    }

    // Handle bulk delete
    public function handle_bulk_delete_images() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'customkings' ) );
        }
        if ( ! isset($_POST['ckpp_bulk_images']) || ! is_array($_POST['ckpp_bulk_images']) ) {
            wp_redirect( admin_url( 'admin.php?page=ckpp_images' ) );
            exit;
        }
        check_admin_referer('ckpp_bulk_delete_images', 'ckpp_bulk_delete_nonce');
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir['basedir'] . '/ckpp_images/';
        foreach ( $_POST['ckpp_bulk_images'] as $img ) {
            $file = $dir . basename($img);
            if ( file_exists( $file ) ) {
                unlink( $file );
            }
        }
        wp_redirect( admin_url( 'admin.php?page=ckpp_images&bulk_deleted=1' ) );
        exit;
    }
} 