<?php
/**
 * Font management for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CKPP_Fonts
 * Handles font management for the CustomKings Product Personalizer plugin.
 */
class CKPP_Fonts {
    /**
     * Register hooks for font upload and delete actions.
     */
    public function __construct() {
        register_activation_hook( CUSTOMKINGS_PLUGIN_FILE, [ __CLASS__, 'create_table' ] );
        add_action( 'admin_post_ckpp_upload_font', [ $this, 'handle_upload' ] );
        add_action( 'admin_post_ckpp_delete_font', [ $this, 'handle_delete' ] );
    }

    /**
     * Create the custom database table for fonts.
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ckpp_fonts';
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            font_name varchar(255) NOT NULL,
            font_file varchar(255) NOT NULL,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql );
    }

    /**
     * Handle font file upload from the admin UI. Requires nonce and capability.
     */
    public function handle_upload() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'customkings' ) );
        }
        check_admin_referer( 'ckpp_upload_font' );
        if ( ! isset( $_FILES['ckpp_font_file'] ) || empty( $_FILES['ckpp_font_file']['name'] ) ) {
            wp_redirect( add_query_arg( 'ckpp_font_error', 'no_file', wp_get_referer() ) );
            exit;
        }
        $file = $_FILES['ckpp_font_file'];
        $allowed_types = [ 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'woff' => 'font/woff', 'woff2' => 'font/woff2' ];
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! array_key_exists( $ext, $allowed_types ) ) {
            wp_redirect( add_query_arg( 'ckpp_font_error', 'invalid_type', wp_get_referer() ) );
            exit;
        }
        $upload = wp_handle_upload( $file, [ 'test_form' => false ] );
        if ( isset( $upload['error'] ) ) {
            wp_redirect( add_query_arg( 'ckpp_font_error', 'upload_error', wp_get_referer() ) );
            exit;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ckpp_fonts';
        $wpdb->insert( $table, [
            'font_name' => sanitize_text_field( $_POST['ckpp_font_name'] ),
            'font_file' => esc_url_raw( $upload['url'] ),
        ] );
        wp_redirect( add_query_arg( 'ckpp_font_success', 'uploaded', wp_get_referer() ) );
        exit;
    }

    /**
     * Handle font deletion from the admin UI. Requires nonce and capability.
     */
    public function handle_delete() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'customkings' ) );
        }
        check_admin_referer( 'ckpp_delete_font' );
        $font_id = isset($_POST['font_id']) ? intval($_POST['font_id']) : (isset($_GET['font_id']) ? intval($_GET['font_id']) : 0);
        if ( ! $font_id ) {
            wp_redirect( add_query_arg( 'ckpp_font_error', 'no_id', wp_get_referer() ) );
            exit;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ckpp_fonts';
        $font = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $font_id ) );
        if ( $font ) {
            // Attempt to delete the file
            $file_path = str_replace( wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $font->font_file );
            if ( file_exists( $file_path ) ) {
                unlink( $file_path );
            }
            $wpdb->delete( $table, [ 'id' => $font->id ] );
        }
        wp_redirect( add_query_arg( 'ckpp_font_success', 'deleted', wp_get_referer() ) );
        exit;
    }

    /**
     * Get all uploaded fonts from the database.
     *
     * @return array
     */
    public static function get_fonts() {
        global $wpdb;
        $table = $wpdb->prefix . 'ckpp_fonts';
        return $wpdb->get_results( "SELECT * FROM $table ORDER BY uploaded_at DESC" );
    }
} 