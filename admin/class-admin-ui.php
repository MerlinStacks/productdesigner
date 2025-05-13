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
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        if ( is_admin() ) {
            add_action( 'wp_ajax_ckpp_get_assignments', [ $this, 'ajax_get_assignments' ] );
            add_action( 'wp_ajax_ckpp_save_assignment', [ $this, 'ajax_save_assignment' ] );
        }
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
    }

    public function register_settings() {
        register_setting( 'ckpp_settings_group', 'ckpp_enabled', [ 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => true ] );
        register_setting( 'ckpp_settings_group', 'ckpp_debug_mode', [ 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => false ] );
        register_setting( 'ckpp_settings_group', 'ckpp_license_key', [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] );
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
        <h2><?php esc_html_e( 'Fonts', 'customkings' ); ?></h2>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'ckpp_upload_font' ); ?>
            <input type="hidden" name="action" value="ckpp_upload_font" />
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ckpp_font_name"><?php esc_html_e( 'Font Name', 'customkings' ); ?></label></th>
                    <td><input type="text" name="ckpp_font_name" id="ckpp_font_name" required class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ckpp_font_file"><?php esc_html_e( 'Font File', 'customkings' ); ?></label></th>
                    <td><input type="file" name="ckpp_font_file" id="ckpp_font_file" accept=".ttf,.otf,.woff,.woff2" required /></td>
                </tr>
            </table>
            <?php submit_button( __( 'Upload Font', 'customkings' ) ); ?>
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
        <h2><?php esc_html_e( 'Clipart', 'customkings' ); ?></h2>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'ckpp_upload_clipart' ); ?>
            <input type="hidden" name="action" value="ckpp_upload_clipart" />
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ckpp_clipart_name"><?php esc_html_e( 'Clipart Name', 'customkings' ); ?></label></th>
                    <td><input type="text" name="ckpp_clipart_name" id="ckpp_clipart_name" required class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ckpp_clipart_file"><?php esc_html_e( 'Clipart File', 'customkings' ); ?></label></th>
                    <td><input type="file" name="ckpp_clipart_file" id="ckpp_clipart_file" accept=".svg,.png" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ckpp_clipart_tags"><?php esc_html_e( 'Tags (comma separated)', 'customkings' ); ?></label></th>
                    <td><input type="text" name="ckpp_clipart_tags" id="ckpp_clipart_tags" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button( __( 'Upload Clipart', 'customkings' ) ); ?>
        </form>
        <hr />
        <h3><?php esc_html_e( 'Uploaded Clipart', 'customkings' ); ?></h3>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Preview', 'customkings' ); ?></th>
                    <th><?php esc_html_e( 'Name', 'customkings' ); ?></th>
                    <th><?php esc_html_e( 'Tags', 'customkings' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'customkings' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( ! class_exists( 'CKPP_Clipart' ) ) return;
                $cliparts = CKPP_Clipart::get_clipart();
                if ( $cliparts ) :
                    foreach ( $cliparts as $clip ) :
                        $clip_url = esc_url( $clip->file_url );
                        $clip_name = esc_html( $clip->name );
                        $clip_tags = esc_html( $clip->tags );
                        $clip_id = intval( $clip->id );
                        ?>
                        <tr>
                            <td><?php if ( $clip_url ) echo '<img src="' . $clip_url . '" alt="" style="max-width:48px;max-height:48px;" />'; ?></td>
                            <td><?php echo $clip_name; ?></td>
                            <td><?php echo $clip_tags; ?></td>
                            <td>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                    <?php wp_nonce_field( 'ckpp_delete_clipart' ); ?>
                                    <input type="hidden" name="action" value="ckpp_delete_clipart" />
                                    <input type="hidden" name="clipart_id" value="<?php echo $clip_id; ?>" />
                                    <button type="submit" class="button button-small delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this clipart?', 'customkings' ) ); ?>');"><?php esc_html_e( 'Delete', 'customkings' ); ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php
                    endforeach;
                else :
                    echo '<tr><td colspan="4">' . esc_html__( 'No clipart uploaded yet.', 'customkings' ) . '</td></tr>';
                endif;
                ?>
            </tbody>
        </table>
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
            </table>
            <?php submit_button(); ?>
        </form>
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
} 