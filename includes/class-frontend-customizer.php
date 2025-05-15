<?php
/**
 * Frontend Customer Customizer for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CKPP_Frontend_Customizer
 * Handles frontend product personalization, AJAX, and WooCommerce integration.
 */
class CKPP_Frontend_Customizer {
    /**
     * Register hooks for scripts, AJAX, and WooCommerce integration.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'output_personalize_button' ] );
        add_action( 'wp_ajax_ckpp_get_product_config', [ $this, 'ajax_get_config' ] );
        add_action( 'wp_ajax_nopriv_ckpp_get_product_config', [ $this, 'ajax_get_config' ] );
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ], 10, 3 );
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_cart_item_data' ], 10, 2 );
        add_action( 'woocommerce_add_order_item_meta', [ $this, 'add_order_item_meta' ], 10, 3 );
        add_action( 'woocommerce_before_order_itemmeta', [ $this, 'admin_order_item_personalization' ], 10, 3 );
        add_action( 'wp_ajax_ckpp_generate_print_file', [ $this, 'ajax_generate_print_file' ] );
        add_action( 'template_redirect', [ $this, 'replace_gallery_with_live_preview' ] );
        if (is_admin()) {
            add_action('admin_menu', [ $this, 'add_print_files_submenu' ]);
        }
    }

    /**
     * Enqueue frontend assets and localize JS strings.
     */
    public function enqueue_assets() {
        if ( is_product() ) {
            wp_enqueue_script( 'ckpp-customizer', plugins_url( '../assets/customizer.js', __FILE__ ), [ 'jquery' ], '1.0', true );
            wp_enqueue_style( 'ckpp-customizer', plugins_url( '../assets/customizer.css', __FILE__ ), [], '1.0' );
            global $post;
            wp_localize_script( 'ckpp-customizer', 'CKPPCustomizer', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ckpp_customizer_nonce' ),
                'productId' => isset( $post->ID ) ? intval( $post->ID ) : 0,
                'closeLabel' => __( 'Close personalization dialog', 'customkings' ),
                'title' => __( 'Personalize Your Product', 'customkings' ),
                'loading' => __( 'Loading personalization options...', 'customkings' ),
                'applyLabel' => __( 'Apply', 'customkings' ),
                'noOptions' => __( 'No personalization options available.', 'customkings' ),
                'textLabel' => __( 'Text %d:', 'customkings' ),
                'loadError' => __( 'Failed to load personalization options.', 'customkings' ),
                'parseError' => __( 'Failed to parse personalization config.', 'customkings' ),
            ] );
        }
    }

    /**
     * Output the Personalize button and modal container on the product page.
     */
    public function output_personalize_button() {
        global $post;
        $assigned_design = get_post_meta( $post->ID, '_ckpp_design_id', true );
        if ( $assigned_design ) {
            // If a design is assigned, do not output the personalize button (live preview will be shown)
            return;
        }
        $config = get_post_meta( $post->ID, '_product_personalization_config_json', true );
        if ( $config ) {
            echo '<button type="button" id="ckpp-personalize-btn" class="button">' . esc_html__( 'Personalize', 'customkings' ) . '</button>';
            echo '<div id="ckpp-customizer-modal" style="display:none;"></div>';
        }
    }

    /**
     * AJAX: Return personalization config for a product. Requires nonce.
     */
    public function ajax_get_config() {
        check_ajax_referer( 'ckpp_customizer_nonce', 'nonce' );
        // error_log('CKPP AJAX handler reached');
        // if ( defined('WP_DEBUG') && WP_DEBUG ) {
        //     error_log('CKPP AJAX: nonce=' . ( isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : 'none' ) . ', productId=' . ( isset($_REQUEST['productId']) ? $_REQUEST['productId'] : 'none' ) . ', user=' . ( is_user_logged_in() ? 'logged-in' : 'guest' ));
        // }
        $product_id = intval( $_GET['productId'] );
        $assigned_design = get_post_meta( $product_id, '_ckpp_design_id', true );
        $config = '';
        if ( $assigned_design ) {
            $config = get_post_meta( $assigned_design, '_ckpp_design_config', true );
        } else {
            $config = get_post_meta( $product_id, '_product_personalization_config_json', true );
        }
        if ( empty( $config ) ) {
            wp_send_json_error( [ 'message' => 'No personalization config found.' ] );
        }
        wp_send_json_success( [ 'config' => $config ] );
    }

    /**
     * Add personalization data to WooCommerce cart item and ensure unique cart item for each personalization.
     *
     * @param array $cart_item_data
     * @param int $product_id
     * @param int $variation_id
     * @return array
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['ckpp_personalization_data'])) {
            $personalization_data = wp_unslash($_POST['ckpp_personalization_data']);
            $cart_item_data['ckpp_personalization_data'] = $personalization_data;
            // This key ensures uniqueness for each personalization
            $cart_item_data['ckpp_personalization_unique'] = md5($personalization_data);
            $cart_item_data['ckpp_is_personalized'] = true;
        }
        return $cart_item_data;
    }

    /**
     * Display personalization data in cart/checkout, with image preview support and block theme compatibility.
     *
     * @param array $item_data
     * @param array $cart_item
     * @return array
     */
    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['ckpp_personalization_data'])) {
            $data = json_decode($cart_item['ckpp_personalization_data'], true);
            if (is_array($data)) {
                // Add a header for personalization details
                $item_data[] = [
                    'name' => esc_html__('Personalization Details', 'customkings'),
                    'value' => '',
                    'display' => '<strong>' . esc_html__('Personalization Details:', 'customkings') . '</strong>',
                ];
                
                foreach ($data as $key => $value) {
                    // Skip empty values
                    if (empty($value)) continue;
                    
                    // Format the label nicely
                    $label = ucwords(str_replace(['_', '-'], ' ', $key));
                    
                    // Handle image values
                    if (is_string($value) && (strpos($value, 'data:image') === 0 || preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $value))) {
                        $img_html = sprintf(
                            '<img src="%s" alt="%s" style="max-width:100px;max-height:80px;display:block;margin:5px 0;" />',
                            esc_url($value),
                            esc_attr($label)
                        );
                        $item_data[] = [
                            'name' => esc_html($label),
                            'value' => $img_html,
                            'display' => $img_html,
                        ];
                    } elseif (is_string($value) && $value !== '') {
                        $item_data[] = [
                            'name' => '- ' . esc_html($label),
                            'value' => esc_html($value),
                            'display' => sprintf(
                                '<span style="display:block;margin:2px 0;">%s: %s</span>',
                                esc_html($label),
                                esc_html($value)
                            ),
                        ];
                    }
                }
            }
        }
        return $item_data;
    }

    /**
     * Save personalization data to order item meta.
     *
     * @param int $item_id
     * @param array $values
     * @param string $cart_item_key
     */
    public function add_order_item_meta( $item_id, $values, $cart_item_key ) {
        if ( isset( $values['ckpp_personalization_data'] ) ) {
            wc_add_order_item_meta( $item_id, '_ckpp_personalization_data', $values['ckpp_personalization_data'] );
        }
    }

    /**
     * Output personalization data and preview in admin order view.
     *
     * @param int $item_id
     * @param WC_Order_Item_Product $item
     * @param WC_Order $order
     */
    public function admin_order_item_personalization( $item_id, $item, $order ) {
        $data = wc_get_order_item_meta( $item_id, '_ckpp_personalization_data', true );
        $product_id = $item->get_product_id();
        $config = get_post_meta( $product_id, '_product_personalization_config_json', true );
        if ( $data ) {
            $arr = json_decode( $data, true );
            echo '<div style="margin:0.5em 0 1em 0;padding:0.5em 1em;background:#f8f8f8;border-left:3px solid #0073aa;">';
            echo '<strong>' . esc_html__( 'Personalization:', 'customkings' ) . '</strong><br />';
            if ( is_array( $arr ) ) {
                foreach ( $arr as $key => $value ) {
                    echo '<div><strong>' . esc_html( ucfirst( $key ) ) . ':</strong> ' . esc_html( $value ) . '</div>';
                }
            }
            // Visual preview
            if ( $config ) {
                $canvas_id = 'ckpp-preview-' . esc_attr( $item_id );
                echo '<div style="margin-top:1em;"><strong>' . esc_html__( 'Preview:', 'customkings' ) . '</strong><br />';
                echo '<canvas id="' . $canvas_id . '" width="300" height="240" style="border:1px solid #ccc;"></canvas></div>';
                echo '<script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function() {
                    if (window.fabric) {
                        var canvas = new fabric.Canvas("' . $canvas_id . '", { selection: false });
                        var config = ' . json_encode( $config ) . ';
                        var data = ' . json_encode( $arr ) . ';
                        if (config && config.objects) {
                            config.objects.forEach(function(obj, idx) {
                                if (obj.type === "i-text" && data["text_"+idx]) {
                                    obj.text = data["text_"+idx];
                                }
                            });
                        }
                        canvas.loadFromJSON(config, function() { canvas.renderAll(); });
                        canvas.discardActiveObject();
                        canvas.selection = false;
                        canvas.forEachObject(function(obj) { obj.selectable = false; });
                    }
                });
                </script>';
                echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.2.4/fabric.min.js"></script>';
            }
            // Print-ready file link placeholder
            $print_url = wc_get_order_item_meta( $item_id, '_ckpp_print_file_url', true );
            echo '<div style="margin-top:1em;">';
            if ( $print_url ) {
                echo '<a href="' . esc_url( $print_url ) . '" class="button" target="_blank">' . esc_html__( 'Download Print-Ready File', 'customkings' ) . '</a>';
            } else {
                echo '<button type="button" class="button ckpp-generate-print" data-item="' . esc_attr( $item_id ) . '">' . esc_html__( 'Generate Print-Ready File', 'customkings' ) . '</button>';
            }
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * AJAX: Generate print-ready PDF for an order item. Requires nonce and capability.
     */
    public function ajax_generate_print_file() {
        check_ajax_referer( 'ckpp_customizer_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( __( 'Unauthorized', 'customkings' ) );
        $item_id = intval( $_POST['itemId'] );
        $order_id = intval( $_POST['orderId'] );
        $item = new WC_Order_Item_Product( $item_id );
        $product_id = $item->get_product_id();
        $config = get_post_meta( $product_id, '_product_personalization_config_json', true );
        $data = wc_get_order_item_meta( $item_id, '_ckpp_personalization_data', true );
        if ( ! $config || ! $data ) wp_send_json_error( __( 'Missing data', 'customkings' ) );
        // Generate PDF (simple placeholder logic)
        if ( ! class_exists( 'TCPDF' ) ) {
            require_once( __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php' );
        }
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 14);
        $pdf->Write(0, 'Personalization Data:', '', 0, 'L', true, 0, false, false, 0);
        $arr = json_decode( $data, true );
        if ( is_array( $arr ) ) {
            foreach ( $arr as $key => $value ) {
                $pdf->Write(0, ucfirst($key) . ': ' . $value, '', 0, 'L', true, 0, false, false, 0);
            }
        }
        $upload_dir = wp_upload_dir();
        $file_name = 'ckpp-print-' . $item_id . '-' . time() . '.pdf';
        $file_path = $upload_dir['path'] . '/' . $file_name;
        $file_url = $upload_dir['url'] . '/' . $file_name;
        $pdf->Output($file_path, 'F');
        wc_update_order_item_meta( $item_id, '_ckpp_print_file_url', $file_url );
        wp_send_json_success( [ 'url' => $file_url ] );
    }

    /**
     * Output the live preview container for the product page.
     *
     * @param bool $force
     */
    public function output_live_preview_container($force = false) {
        global $post, $ckpp_live_preview_shortcode_used, $wpdb;
        // Debug: Log current prefix and table info
        error_log('CKPP: Current prefix is ' . $wpdb->prefix);
        error_log('CKPP: Looking for table ' . $wpdb->prefix . 'ckpp_fonts');
        error_log('CKPP: Tables: ' . print_r($wpdb->get_col('SHOW TABLES'), true));
        if (!$force && !empty($ckpp_live_preview_shortcode_used)) {
            // If shortcode is used, skip hook-based output
            return;
        }
        $assigned_design = get_post_meta( $post->ID, '_ckpp_design_id', true );
        if ( $assigned_design ) {
            $debug_mode = get_option('ckpp_debug_mode', false) ? 'true' : 'false';
            $nonce = wp_create_nonce('ckpp_customizer_nonce');
            // Output custom font CSS for all uploaded fonts
            if ( class_exists('CKPP_Fonts') ) {
                $fonts = CKPP_Fonts::get_fonts();
                // Debug: Log fonts to error log
                error_log('CKPP Frontend Fonts: ' . print_r($fonts, true));
                // Debug: Output font list
                echo '<!-- CKPP DEBUG: Fonts found: ';
                if (isset($fonts) && $fonts) {
                    foreach ($fonts as $font) {
                        echo 'Name: ' . $font->font_name . ', URL: ' . $font->font_file . ' | ';
                    }
                } else {
                    echo 'No fonts found.';
                }
                echo '-->';
                foreach ( $fonts as $font ) {
                    $font_face = esc_attr( $font->font_name );
                    $font_url = esc_url( $font->font_file );
                    echo "<style>@font-face { font-family: '{$font_face}'; src: url('{$font_url}'); font-display: swap; }</style>\n";
                }
                // Debug: Font CSS output complete
                echo '<!-- CKPP DEBUG: Font CSS output complete -->';
            }
            // Output a hidden placeholder; JS will move/show it if a gallery is found
            echo '<div id="ckpp-live-preview" style="display:none;margin-bottom:2em;"></div>';
            echo '<script>window.CKPP_LIVE_PREVIEW = { productId: ' . intval($post->ID) . ', designId: ' . intval($assigned_design) . ', nonce: "' . esc_js($nonce) . '" };
window.CKPP_DEBUG_MODE = ' . $debug_mode . ';</script>';
            if ( get_option('ckpp_debug_mode', false) ) {
                $config = get_post_meta( $assigned_design, '_ckpp_design_config', true );
                echo '<script>window.CKPP_LIVE_PREVIEW_CONFIG = ' . json_encode($config) . ';</script>';
            }
        }
    }

    /**
     * Replace WooCommerce gallery with live preview if enabled.
     */
    public function replace_gallery_with_live_preview() {
        global $ckpp_live_preview_shortcode_used;
        if (!empty($ckpp_live_preview_shortcode_used)) {
            // If shortcode is used, skip hook-based output
            return;
        }
        if ( is_product() ) {
            global $post;
            $assigned_design = get_post_meta( $post->ID, '_ckpp_design_id', true );
            if ( $assigned_design ) {
                remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
                add_action( 'woocommerce_before_single_product_summary', [ $this, 'output_live_preview_container' ], 20 );
            }
        }
    }

    /**
     * Add Print Files submenu to the admin menu.
     */
    public function add_print_files_submenu() {
        add_submenu_page(
            'ckpp_admin',
            __('Print Files', 'customkings'),
            __('Print Files', 'customkings'),
            'manage_woocommerce',
            'ckpp_print_files',
            [ $this, 'render_print_files_page' ]
        );
    }

    /**
     * Render the Print Files admin page.
     */
    public function render_print_files_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'customkings'));
        }
        $upload_dir = wp_upload_dir();
        $files = glob($upload_dir['path'] . '/ckpp-print-*.pdf');
        echo '<div class="wrap"><h1>' . esc_html__('Print Files', 'customkings') . '</h1>';
        if (!$files) {
            echo '<p>' . esc_html__('No print files found.', 'customkings') . '</p></div>';
            return;
        }
        echo '<table class="widefat fixed striped"><thead><tr><th>' . esc_html__('Order Item ID', 'customkings') . '</th><th>' . esc_html__('Order #', 'customkings') . '</th><th>' . esc_html__('File', 'customkings') . '</th></tr></thead><tbody>';
        foreach ($files as $file_path) {
            $file_name = basename($file_path);
            if (preg_match('/ckpp-print-(\d+)-(\d+)\.pdf/', $file_name, $matches)) {
                $item_id = $matches[1];
                $order_id = wc_get_order_id_by_order_item_id($item_id);
                $file_url = $upload_dir['url'] . '/' . $file_name;
                echo '<tr>';
                echo '<td>' . esc_html($item_id) . '</td>';
                echo '<td>' . esc_html($order_id ? $order_id : '-') . '</td>';
                echo '<td><a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_name) . '</a></td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table></div>';
    }
}

// Register the shortcode outside the class for maximum compatibility
if (!function_exists('ckpp_live_preview_shortcode')) {
    function ckpp_live_preview_shortcode() {
        if (class_exists('CKPP_Frontend_Customizer')) {
            $customizer = new CKPP_Frontend_Customizer();
            ob_start();
            $customizer->output_live_preview_container(true); // Force output for shortcode
            return ob_get_clean();
        }
        return '';
    }
    add_shortcode('ckpp_live_preview', 'ckpp_live_preview_shortcode');
}

/**
 * Ensure personalized items are always unique in the cart (block & classic themes).
 * This filter appends a hash of the personalization data to the cart item key.
 */
add_filter('woocommerce_cart_id', function($cart_id, $cart_item, $cart_item_key) {
    if (!empty($cart_item['ckpp_personalization_data'])) {
        $cart_id .= '_' . md5($cart_item['ckpp_personalization_data']);
    }
    return $cart_id;
}, 10, 3); 