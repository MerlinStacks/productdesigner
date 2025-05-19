<?php
/**
 * Admin UI for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CKPP_Admin_UI {
    public function __construct() {
        // wp_die("DEBUG: CONSTRUCTOR STAGE 1 - Start");

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        // wp_die("DEBUG: CONSTRUCTOR STAGE 2 - After admin_menu");

        add_action( 'admin_init', [ $this, 'register_settings' ] );
        // wp_die("DEBUG: CONSTRUCTOR STAGE 3 - After admin_init");

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
        // wp_die("DEBUG: CONSTRUCTOR STAGE 4 - After admin_enqueue_scripts");

        add_action( 'wp_ajax_ckpp_get_assignments', [ $this, 'ajax_get_assignments' ] );
        // wp_die("DEBUG: CONSTRUCTOR STAGE 5 - After wp_ajax_ckpp_get_assignments");

        add_action( 'wp_ajax_ckpp_save_assignment', [ $this, 'ajax_save_assignment' ] );
        // wp_die("DEBUG: CONSTRUCTOR STAGE 6 - After wp_ajax_ckpp_save_assignment");
        
        // The admin-post action is still commented out for now
        add_action( 'admin_post_ckpp_wipe_reinstall', [ $this, 'handle_wipe_reinstall' ] );
        
        // wp_die("DEBUG: CONSTRUCTOR STAGE 7 - After ALL add_action calls (except admin_post)");
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

    public function register_settings() {
        register_setting( 'ckpp_settings_group', 'ckpp_enabled', [ 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => true ] );
        register_setting( 'ckpp_settings_group', 'ckpp_debug_mode', [ 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => false ] );
        register_setting( 'ckpp_settings_group', 'ckpp_license_key', [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] );
        register_setting( 'ckpp_settings_group', 'ckpp_accent_color', [ 'type' => 'string', 'sanitize_callback' => 'sanitize_hex_color', 'default' => '#fec610' ] );
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
                $this->render_fonts_tab();
                break;
            case 'clipart':
                $this->render_clipart_tab();
                break;
            case 'settings':
            default:
                $this->render_settings_form();
                break;
        }
    }

    private function render_fonts_tab() {
        // Notices
        if ( isset( $_GET['ckpp_font_success'] ) && $_GET['ckpp_font_success'] === 'uploaded' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Font uploaded successfully.', 'customkings' ) . '</p></div>';
        } elseif ( isset( $_GET['ckpp_font_success'] ) && $_GET['ckpp_font_success'] === 'deleted' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Font deleted successfully.', 'customkings' ) . '</p></div>';
        } elseif ( isset( $_GET['ckpp_font_error'] ) ) {
            $error = sanitize_text_field( $_GET['ckpp_font_error'] );
            $msg = '';
            switch ( $error ) {
                case 'no_file': $msg = __( 'No file selected.', 'customkings' ); break;
                case 'invalid_type': $msg = __( 'Invalid file type.', 'customkings' ); break;
                case 'upload_error': $msg = __( 'Upload error.', 'customkings' ); break;
                case 'no_id': $msg = __( 'No font ID specified.', 'customkings' ); break;
                default: $msg = __( 'An error occurred.', 'customkings' ); break;
            }
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
        }
        ?>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ckpp-upload-form">
            <?php wp_nonce_field( 'ckpp_upload_font' ); ?>
            <input type="hidden" name="action" value="ckpp_upload_font" />
            <div class="ckpp-inline-form-row">
                <div class="form-control">
                    <label for="ckpp_font_name" class="screen-reader-text"><?php esc_html_e( 'Font Name', 'customkings' ); ?></label>
                    <input type="text" name="ckpp_font_name" id="ckpp_font_name" required class="regular-text" placeholder="<?php esc_attr_e('Font Name', 'customkings'); ?>" />
                </div>
                <div class="form-control" style="flex:2;min-width:180px;position:relative;">
                    <label class="ckpp-upload-dropzone" id="ckpp-font-dropzone" for="ckpp_font_file">
                        <span class="ckpp-upload-icon dashicons dashicons-upload"></span>
                        <span class="ckpp-upload-label"><?php esc_html_e('Drag & drop font file here, or click to select', 'customkings'); ?></span>
                        <span class="ckpp-upload-filename"></span>
                    </label>
                    <input type="file" name="ckpp_font_file" id="ckpp_font_file" accept=".ttf,.otf,.woff,.woff2" required style="opacity:0;position:absolute;pointer-events:auto;width:100%;height:100%;left:0;top:0;" />
                </div>
                <button type="submit" class="ckpp-upload-btn"><span class="dashicons dashicons-upload"></span><?php esc_html_e( 'Upload Font', 'customkings' ); ?></button>
            </div>
        </form>
        <hr />
        <h3><?php esc_html_e( 'Uploaded Fonts', 'customkings' ); ?></h3>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Font Name', 'customkings' ); ?></th>
                    <th><?php esc_html_e( 'Preview', 'customkings' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'customkings' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( ! class_exists( 'CKPP_Fonts' ) ) return;
                $fonts = CKPP_Fonts::get_fonts();
                if ( $fonts ) :
                    foreach ( $fonts as $font ) :
                        $font_url = esc_url( $font->font_file );
                        $font_name = esc_html( $font->font_name );
                        $font_id = intval( $font->id );
                        $font_face = 'ckpp-font-' . $font_id;
                        echo "<style>@font-face { font-family: '{$font_face}'; src: url('{$font_url}'); }</style>";
                        ?>
                        <tr>
                            <td><?php echo $font_name; ?></td>
                            <td><span style="font-family: '<?php echo esc_attr( $font_face ); ?>', sans-serif; font-size: 1.2em;">The quick brown fox</span></td>
                            <td>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                    <?php wp_nonce_field( 'ckpp_delete_font' ); ?>
                                    <input type="hidden" name="action" value="ckpp_delete_font" />
                                    <input type="hidden" name="font_id" value="<?php echo $font_id; ?>" />
                                    <button type="submit" class="button button-small delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this font?', 'customkings' ) ); ?>');"><?php esc_html_e( 'Delete', 'customkings' ); ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php
                    endforeach;
                else :
                    echo '<tr><td colspan="3">' . esc_html__( 'No fonts uploaded yet.', 'customkings' ) . '</td></tr>';
                endif;
                ?>
            </tbody>
        </table>
        <?php
    }

    private function render_clipart_tab() {
        // Notices
        if ( isset( $_GET['ckpp_clipart_success'] ) && $_GET['ckpp_clipart_success'] === 'uploaded' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Clipart uploaded successfully.', 'customkings' ) . '</p></div>';
        } elseif ( isset( $_GET['ckpp_clipart_success'] ) && $_GET['ckpp_clipart_success'] === 'deleted' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Clipart deleted successfully.', 'customkings' ) . '</p></div>';
        } elseif ( isset( $_GET['ckpp_clipart_error'] ) ) {
            $error = sanitize_text_field( $_GET['ckpp_clipart_error'] );
            $msg = '';
            switch ( $error ) {
                case 'no_file': $msg = __( 'No file selected.', 'customkings' ); break;
                case 'invalid_type': $msg = __( 'Invalid file type.', 'customkings' ); break;
                case 'upload_error': $msg = __( 'Upload error.', 'customkings' ); break;
                case 'no_id': $msg = __( 'No clipart ID specified.', 'customkings' ); break;
                default: $msg = __( 'An error occurred.', 'customkings' ); break;
            }
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
        }
        ?>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ckpp-upload-form">
            <?php wp_nonce_field( 'ckpp_upload_clipart' ); ?>
            <input type="hidden" name="action" value="ckpp_upload_clipart" />
            <div class="ckpp-inline-form-row">
                <div class="form-control">
                    <label for="ckpp_clipart_name" class="screen-reader-text"><?php esc_html_e( 'Clipart Name', 'customkings' ); ?></label>
                    <input type="text" name="ckpp_clipart_name" id="ckpp_clipart_name" required class="regular-text" placeholder="<?php esc_attr_e('Clipart Name', 'customkings'); ?>" />
                </div>
                <div class="form-control" style="flex:2;min-width:180px;position:relative;">
                    <label class="ckpp-upload-dropzone" id="ckpp-clipart-dropzone" for="ckpp_clipart_file">
                        <span class="ckpp-upload-icon dashicons dashicons-upload"></span>
                        <span class="ckpp-upload-label"><?php esc_html_e('Drag & drop clipart file here, or click to select', 'customkings'); ?></span>
                        <span class="ckpp-upload-filename"></span>
                    </label>
                    <input type="file" name="ckpp_clipart_file" id="ckpp_clipart_file" accept=".svg,.png,.jpg,.jpeg,.gif,.webp,image/*" required style="opacity:0;position:absolute;pointer-events:auto;width:100%;height:100%;left:0;top:0;" />
                </div>
                <div class="form-control">
                    <label for="ckpp_clipart_tags" class="screen-reader-text"><?php esc_html_e( 'Tags (comma separated)', 'customkings' ); ?></label>
                    <input type="text" name="ckpp_clipart_tags" id="ckpp_clipart_tags" class="regular-text" placeholder="<?php esc_attr_e('Tags (comma separated)', 'customkings'); ?>" />
                </div>
                <button type="submit" class="ckpp-upload-btn"><span class="dashicons dashicons-upload"></span><?php esc_html_e( 'Upload Clipart', 'customkings' ); ?></button>
            </div>
        </form>
        <hr />
        <h3><?php esc_html_e( 'Uploaded Clipart', 'customkings' ); ?></h3>
        <?php
        if ( ! class_exists( 'CKPP_Clipart' ) ) return;
        $cliparts = CKPP_Clipart::get_clipart();
        // Collect all tags for filter dropdown
        $all_tags = [];
        foreach ( $cliparts as $clip ) {
            $tags = array_filter(array_map('trim', explode(',', $clip->tags)));
            foreach ( $tags as $tag ) {
                if ($tag !== '') $all_tags[$tag] = true;
            }
        }
        ksort($all_tags);
        $selected_tag = isset($_GET['clipart_tag']) ? sanitize_text_field($_GET['clipart_tag']) : '';
        ?>
        <form method="get" class="ckpp-clipart-filter-form" style="margin-bottom:1.5em;display:flex;align-items:center;gap:1em;">
            <?php foreach ($_GET as $k => $v) if ($k !== 'clipart_tag') echo '<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr($v).'" />'; ?>
            <label for="ckpp-clipart-tag-filter" style="font-weight:500;">Tag:</label>
            <select name="clipart_tag" id="ckpp-clipart-tag-filter" onchange="this.form.submit()" style="min-width:120px;">
                <option value=""><?php esc_html_e('All', 'customkings'); ?></option>
                <?php foreach ($all_tags as $tag => $_) : ?>
                    <option value="<?php echo esc_attr($tag); ?>" <?php selected($selected_tag, $tag); ?>><?php echo esc_html($tag); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="ckpp-clipart-grid">
        <?php
        $has_clipart = false;
        foreach ( $cliparts as $clip ) :
            $clip_url = esc_url( $clip->file_url );
            $clip_name = esc_html( $clip->name );
            $clip_tags = esc_html( $clip->tags );
            $clip_id = intval( $clip->id );
            $tags = array_filter(array_map('trim', explode(',', $clip->tags)));
            if ($selected_tag && !in_array($selected_tag, $tags, true)) continue;
            $has_clipart = true;
        ?>
            <div class="ckpp-clipart-card">
                <div class="ckpp-clipart-thumb">
                    <?php if ( $clip_url ) echo '<img src="' . $clip_url . '" alt="" style="width:100%;height:100%;object-fit:contain;" />'; ?>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                        <?php wp_nonce_field( 'ckpp_delete_clipart' ); ?>
                        <input type="hidden" name="action" value="ckpp_delete_clipart" />
                        <input type="hidden" name="clipart_id" value="<?php echo $clip_id; ?>" />
                        <button type="submit" class="ckpp-clipart-delete-btn" title="<?php esc_attr_e('Delete this clipart', 'customkings'); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this clipart?', 'customkings' ) ); ?>');">
                            <span class="ckpp-clipart-delete-svg" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" focusable="false" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="9" cy="9" r="8" stroke="#b32d2e" stroke-width="2" fill="none"/>
                                    <line x1="6" y1="6" x2="12" y2="12" stroke="#b32d2e" stroke-width="2" stroke-linecap="round"/>
                                    <line x1="12" y1="6" x2="6" y2="12" stroke="#b32d2e" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span class="screen-reader-text"><?php esc_html_e('Delete', 'customkings'); ?></span>
                        </button>
                    </form>
                </div>
                <div class="ckpp-clipart-meta">
                    <div class="ckpp-clipart-name"><?php echo $clip_name; ?></div>
                    <div class="ckpp-clipart-tags"><?php echo $clip_tags; ?></div>
                </div>
            </div>
        <?php endforeach;
        if (!$has_clipart) {
            echo '<div style="padding:2em;text-align:center;color:#888;">' . esc_html__( 'No clipart uploaded yet.', 'customkings' ) . '</div>';
        }
        ?>
        </div>
        <?php
    }

    private function render_settings_form() {
        ?>
        <h2><?php esc_html_e( 'Modes & Global Settings', 'customkings' ); ?></h2>
        <?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) : ?>
            <div id="message" class="updated notice is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'customkings' ); ?></p></div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'ckpp_settings_group' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="ckpp_enabled"><?php esc_html_e( 'Enable Plugin', 'customkings' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="ckpp_enabled" name="ckpp_enabled" value="1" <?php checked( 1, get_option( 'ckpp_enabled', 1 ) ); ?> />
                        <span class="description"><?php esc_html_e( 'Enable or disable the product personalizer functionality.', 'customkings' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ckpp_debug_mode"><?php esc_html_e( 'Debug Mode', 'customkings' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="ckpp_debug_mode" name="ckpp_debug_mode" value="1" <?php checked( 1, get_option( 'ckpp_debug_mode', 0 ) ); ?> />
                        <span class="description"><?php esc_html_e( 'Enable debug mode for additional logging and troubleshooting.', 'customkings' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ckpp_license_key"><?php esc_html_e( 'License Key', 'customkings' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="ckpp_license_key" name="ckpp_license_key" value="<?php echo esc_attr( get_option( 'ckpp_license_key', '' ) ); ?>" class="regular-text" />
                        <span class="description"><?php esc_html_e( 'Enter your license key if required.', 'customkings' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Accent Color', 'customkings' ); ?></label>
                    </th>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div id="ckpp-accent-color-preview" aria-label="<?php esc_attr_e('Accent Color', 'customkings'); ?>" tabindex="0" style="display:inline-block;width:36px;height:36px;border-radius:6px;border:1px solid #ccc;vertical-align:middle;cursor:pointer;"></div>
                            <button type="button" id="ckpp-accent-color-picker-btn" aria-label="<?php esc_attr_e('Pick accent color', 'customkings'); ?>" class="button" style="padding:0 10px;height:36px;min-width:36px;font-size:1.2em;line-height:36px;">🎨</button>
                        </div>
                        <label for="ckpp_accent_color" class="screen-reader-text"><?php esc_html_e( 'Accent Color Value', 'customkings' ); ?></label>
                        <input type="hidden" id="ckpp_accent_color" name="ckpp_accent_color" value="<?php echo esc_attr( get_option( 'ckpp_accent_color', '#fec610' ) ); ?>" />
                        <span class="description"><?php esc_html_e( 'Choose the accent color for the plugin. Supports HEX and RGB.', 'customkings' ); ?></span>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php if ( get_option('ckpp_debug_mode', false) ) : ?>
        <div class="ckpp-danger-zone" style="margin-top:2em;border-top:1px solid #ddd;padding-top:1em;">
            <h3 style="color:#a00;margin-bottom:10px;">
                <?php esc_html_e('Danger Zone: Data Reset', 'customkings'); ?>
            </h3>
            <p class="description" style="color:#a00;margin-bottom:15px;">
                <?php esc_html_e('This will delete all plugin data and recreate the database tables. This action cannot be undone.', 'customkings'); ?>
            </p>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="ckpp-wipe-form">
                <?php wp_nonce_field('ckpp_wipe_reinstall'); ?>
                <input type="hidden" name="action" value="ckpp_wipe_reinstall">
                <button type="submit" id="ckpp-wipe-btn" class="button button-secondary" style="background:#a00;color:white;border-color:#a00;">
                    <?php esc_html_e('Wipe & Reinstall Plugin Data', 'customkings'); ?>
                </button>
            </form>
            
            <?php if (isset($_GET['ckpp_wiped']) && $_GET['ckpp_wiped'] === '1'): ?>
                <div class="notice notice-success is-dismissible" style="margin-top:15px;">
                    <p><?php esc_html_e('Plugin data has been wiped and reinstalled successfully.', 'customkings'); ?></p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text"><?php esc_html_e('Dismiss this notice.', 'customkings'); ?></span>
                    </button>
                </div>
            <?php elseif (isset($_GET['ckpp_wipe_error'])): ?>
                <div class="notice notice-error is-dismissible" style="margin-top:15px;">
                    <p><?php echo esc_html(__('Error during operation: ', 'customkings') . urldecode($_GET['ckpp_wipe_error'])); ?></p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text"><?php esc_html_e('Dismiss this notice.', 'customkings'); ?></span>
                    </button>
                </div>
            <?php endif; ?>
            
            <script>
            jQuery(document).ready(function($) {
                // Make notices dismissible
                $('.is-dismissible .notice-dismiss').on('click', function() {
                    $(this).parent().fadeOut(200);
                });
                
                // Add confirmation to form submission
                $('#ckpp-wipe-form').on('submit', function(e) {
                    // First confirmation
                    if (!confirm('<?php echo esc_js(__('Are you sure you want to wipe ALL plugin data? This cannot be undone.', 'customkings')); ?>')) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Second confirmation
                    if (!confirm('<?php echo esc_js(__('Please confirm again: This will permanently delete all plugin data and cannot be recovered. Continue?', 'customkings')); ?>')) {
                        e.preventDefault();
                        return false;
                    }
                    
                    $('#ckpp-wipe-btn').prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'customkings')); ?>');
                    return true;
                });
            });
            </script>
        </div>
        <?php endif; ?>
        <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var previewBox = document.getElementById('ckpp-accent-color-preview');
            var hiddenInput = document.getElementById('ckpp_accent_color');
            var pickrBtn = document.getElementById('ckpp-accent-color-picker-btn');
            var isDebug = <?php echo get_option('ckpp_debug_mode', false) ? 'true' : 'false'; ?>;
            var pickr = Pickr.create({
                el: pickrBtn,
                theme: 'classic',
                default: hiddenInput.value,
                components: {
                    preview: false,
                    opacity: true,
                    hue: true,
                    interaction: {
                        hex: true,
                        rgba: true,
                        input: true,
                        save: true
                    }
                }
            });
            function updatePreview(color) {
                var hex = (color && color.toHEXA) ? color.toHEXA().toString() : (pickr.getColor() ? pickr.getColor().toHEXA().toString() : hiddenInput.value);
                previewBox.style.setProperty('background', hex, 'important');
                hiddenInput.value = hex;
                if (isDebug) {
                    console.log('[CKPP DEBUG] Updating preview to:', hex, 'color:', color, 'pickr.getColor():', pickr.getColor());
                    console.log('[CKPP DEBUG] Preview box element:', previewBox);
                }
            }
            ['init', 'show', 'change', 'changestop', 'save'].forEach(function(ev) {
                pickr.on(ev, updatePreview);
            });
            // Set initial preview
            updatePreview();
            if (isDebug) {
                console.log('[CKPP DEBUG] Pickr initialized on:', pickrBtn, 'with value:', hiddenInput.value);
            }
            // Allow clicking the preview box to open the color picker
            previewBox.addEventListener('click', function() { pickr.show(); });
            previewBox.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    pickr.show();
                }
            });
        });
        </script>
        <style>
        .pcr-app .pcr-preview {
            width: 36px !important;
            height: 36px !important;
            border-radius: 6px !important;
            border: 1px solid #ccc !important;
            margin: 0 auto 8px auto !important;
            box-shadow: none !important;
        }
        /* Hide Pickr's own trigger button */
        .pickr .pcr-button {
            display: none !important;
        }
        </style>
        <?php
    }

    public function render_product_assignments_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( __( 'You do not have permission to access this page.', 'customkings' ) );
        }
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
        if ( ! current_user_can( 'manage_woocommerce' ) || ! check_ajax_referer( 'ckpp_assign_design', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'customkings' ) ] );
        }
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
        if ( ! current_user_can( 'manage_woocommerce' ) || ! check_ajax_referer( 'ckpp_assign_design', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'customkings' ) ] );
        }
        $product_id = intval( $_POST['product_id'] );
        $design_id  = intval( $_POST['design_id'] );
        if ( ! $product_id || get_post_type( $product_id ) !== 'product' ) {
            wp_send_json_error( [ 'message' => __( 'Invalid product.', 'customkings' ) ] );
        }
        update_post_meta( $product_id, '_ckpp_design_id', $design_id );
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
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'customkings'));
        }
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
            echo '<script>
                window.CKPP_DESIGN_ID = ' . $design_id . '; 
                window.CKPP_DESIGN_TITLE = ' . json_encode($design_title) . ';
                window.CKPP_DEBUG_MODE = ' . (get_option('ckpp_debug_mode', false) ? 'true' : 'false') . ';
                window.CKPPDesigner = {
                    ajaxUrl: "' . esc_js(admin_url('admin-ajax.php')) . '",
                    nonce: "' . esc_js($designer_nonce) . '",
                    designId: ' . $design_id . '
                };
            </script>';
        } else {
            // Initialize JS variables for the designs list view
            echo '<script>
                window.CKPP_DEBUG_MODE = ' . (get_option('ckpp_debug_mode', false) ? 'true' : 'false') . ';
                window.CKPPDesigner = {
                    ajaxUrl: "' . esc_js(admin_url('admin-ajax.php')) . '",
                    nonce: "' . esc_js($designer_nonce) . '"
                };
            </script>';
            echo '<a href="' . esc_url( admin_url( 'admin.php?action=ckpp_create_design' ) ) . '" class="button button-primary" style="margin-bottom:1.5em;display:inline-flex;align-items:center;"><span class="dashicons dashicons-plus"></span>' . esc_html__( 'Create New Design', 'customkings' ) . '</a>';
            $designs = get_posts([ 'post_type' => 'ckpp_design', 'numberposts' => -1 ]);
            if ($designs) {
                echo '<div class="ckpp-designs-grid">';
                foreach ($designs as $design) {
                    $delete_url = wp_nonce_url(
                        admin_url('admin.php?action=ckpp_delete_design&design_id=' . $design->ID),
                        'ckpp_delete_design_' . $design->ID
                    );
                    $preview_url = get_post_meta($design->ID, '_ckpp_design_preview', true);
                    echo '<div class="ckpp-design-card">';
                    if ($preview_url && strpos($preview_url, 'data:image/png;base64,') === 0) {
                        echo '<div class="ckpp-design-thumb"><img src="' . esc_attr($preview_url) . '" alt="' . esc_attr($design->post_title) . '" /></div>';
                    } else {
                        echo '<div class="ckpp-design-thumb ckpp-design-thumb-empty"><span class="dashicons dashicons-format-image"></span></div>';
                    }
                    echo '<div class="ckpp-design-title">' . esc_html($design->post_title) . '</div>';
                    echo '<div class="ckpp-design-actions">';
                    echo '<a href="' . esc_url( admin_url( 'admin.php?page=ckpp_designs&design_id=' . $design->ID ) ) . '" class="ckpp-btn ckpp-btn-secondary"><span class="dashicons dashicons-edit"></span> ' . esc_html__('Edit', 'customkings') . '</a>';
                    echo '<a href="' . esc_url($delete_url) . '" class="ckpp-btn ckpp-btn-secondary ckpp-btn-danger" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this design?', 'customkings')) . '\');"><span class="dashicons dashicons-trash"></span> ' . esc_html__('Delete', 'customkings') . '</a>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>' . esc_html__( 'No designs found.', 'customkings' ) . '</p>';
            }
        }
        echo '</div>';
    }

    /**
     * Handle the wipe & reinstall action from admin-post.php
     */
    public function handle_wipe_reinstall() {
        // wp_die("DEBUG: handle_wipe_reinstall function was reached. If you see this, the problem is AFTER this line.");
        
        // Check #1: User Capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('ERROR: Insufficient permissions to perform this action.', 'customkings'), __('Permission Denied', 'customkings'), ['response' => 403]);
        }
        
        // Check #2: Nonce Verification
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'ckpp_wipe_reinstall' ) ) {
            wp_die( __('ERROR: Security check (nonce verification) failed. Please go back, refresh the page, and try again. Ensure cookies are enabled.', 'customkings'), __('Security Check Failed', 'customkings'), ['response' => 403]);
        }
        
        // Check #3: Action Parameter
        if ( !isset($_POST['action']) || $_POST['action'] !== 'ckpp_wipe_reinstall' ) {
             wp_die( __("ERROR: The required 'action' parameter for processing this request is missing or incorrect.", 'customkings'), __('Request Routing Error', 'customkings'), ['response' => 400]);
        }

        // Check #4: Debug Mode
        if (!get_option('ckpp_debug_mode', false)) {
            $referer_url = wp_get_referer();
            $error_msg = __('Debug mode must be enabled to perform this data reset operation.', 'customkings');
            if ($referer_url) {
                wp_redirect(add_query_arg('ckpp_wipe_error', urlencode($error_msg), $referer_url));
            } else {
                wp_die($error_msg . __(' (Could not determine a page to redirect back to.)', 'customkings'), __('Debug Mode Required', 'customkings'), ['response' => 400]);
            }
            exit;
        }
        // wp_die("DEBUG: handle_wipe_reinstall function was reached and initial checks passed.");
        
        // Initialize WP_Filesystem
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
            }
            if (!WP_Filesystem()) {
                 $referer_url = wp_get_referer();
                 $error_msg = __('Could not initialize WordPress Filesystem API. This is required for file operations.', 'customkings');
                 if ($referer_url) {
                    wp_redirect(add_query_arg('ckpp_wipe_error', urlencode($error_msg), $referer_url));
                 } else {
                    wp_die($error_msg, __('Filesystem Error', 'customkings'), ['response' => 500]);
                 }
                 exit;
            }
        }

        try {
            global $wpdb;
            
            // Delete designs
            $designs = get_posts([
                'post_type' => 'ckpp_design',
                'numberposts' => -1,
                'fields' => 'ids',
                'post_status' => 'any'
            ]);
            
            foreach ($designs as $design_id) {
                wp_delete_post($design_id, true);
            }
            
            // Clean product meta
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_ckpp_design_id'");
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_ckpp_design_%'");
            
            // Clean order meta
            if (class_exists('WooCommerce')) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_ckpp_personalization_data'");
                $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_ckpp_print_file_url'");
            }
            
            // Drop tables
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ckpp_fonts");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ckpp_clipart");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ckpp_clipart_tags");

            // Delete uploaded files and directories
            $upload_dir_info = wp_upload_dir();
            $ckpp_dirs_to_delete = [
                $upload_dir_info['basedir'] . '/ckpp_fonts',
                $upload_dir_info['basedir'] . '/ckpp_clipart',
                $upload_dir_info['basedir'] . '/ckpp_customer_uploads',
                $upload_dir_info['basedir'] . '/ckpp_print_files',
            ];
            
            foreach ($ckpp_dirs_to_delete as $dir_path) {
                if ($wp_filesystem->exists($dir_path)) {
                    $wp_filesystem->delete($dir_path, true); // true for recursive deletion
                }
            }
            
            // Reset options
            delete_option('ckpp_enabled');
            delete_option('ckpp_license_key');
            delete_option('ckpp_accent_color');
            
            // Recreate tables
            if (class_exists('CKPP_Fonts')) {
                CKPP_Fonts::create_table();
            }
            
            if (class_exists('CKPP_Clipart')) {
                CKPP_Clipart::create_tables();
            }

            // Recreate directories (WP_Filesystem API handles permissions)
             foreach ($ckpp_dirs_to_delete as $dir_path) {
                if (!$wp_filesystem->exists($dir_path)) {
                    $wp_filesystem->mkdir($dir_path);
                }
            }
            
            // Redirect back to the settings page with success message
            $referer_url = wp_get_referer();
            if ($referer_url) {
                 wp_redirect(add_query_arg('ckpp_wiped', '1', $referer_url));
            } else {
                 // Fallback if no referer, though admin-post should have one
                 wp_die(__('SUCCESS: Plugin data wiped and reinstalled. Could not redirect automatically.', 'customkings'), __('Operation Successful', 'customkings'), ['response' => 200]);
            }
            exit;
        }
        catch (Exception $e) {
            $referer_url = wp_get_referer();
            $error_msg = __('An unexpected error occurred during the data reset operation:', 'customkings') . ' ' . $e->getMessage();
             if ($referer_url) {
                wp_redirect(add_query_arg('ckpp_wipe_error', urlencode($error_msg), $referer_url));
             } else {
                wp_die($error_msg, __('Operation Failed', 'customkings'), ['response' => 500]);
             }
            exit;
        }
    }

    /**
     * Helper function to recursively delete a directory and its contents using WP_Filesystem.
     *
     * @param string $dir Directory path to delete.
     * @return bool True on success, false on failure.
     */
    private function delete_directory_recursively($dir) {
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if (!$wp_filesystem->exists($dir)) {
            return true; // Directory doesn't exist, so success.
        }

        if (!$wp_filesystem->is_dir($dir)) {
            return $wp_filesystem->delete($dir); // It's a file, delete it.
        }

        // It's a directory, delete it recursively.
        return $wp_filesystem->delete($dir, true);
    }
} 