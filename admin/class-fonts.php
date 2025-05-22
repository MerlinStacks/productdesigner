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
     * Invalidate the font cache.
     */
    private static function invalidate_font_cache() {
        if ( class_exists( 'CKPP_Cache' ) ) {
            CKPP_Cache::delete( 'ckpp_all_fonts', 'assets' );
        }
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
        CKPP_Security::verify_capability('manage_options');
        check_admin_referer( 'ckpp_upload_font' );

        if ( ! isset( $_FILES['ckpp_font_file'] ) || empty( $_FILES['ckpp_font_file']['name'] ) ) {
            CKPP_Error_Handler::log_security_event('Font Upload Failed: No file uploaded', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'No font file uploaded.', 'customkings' ) );
        }

        $file = $_FILES['ckpp_font_file'];
        $allowed_types = [ 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'woff' => 'font/woff', 'woff2' => 'font/woff2' ];
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

        if ( ! array_key_exists( $ext, $allowed_types ) ) {
            CKPP_Error_Handler::log_security_event('Font Upload Failed: Invalid file type', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'file_name' => $file['name'],
                'file_type' => $ext
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'Invalid font file type.', 'customkings' ) );
        }

        $upload = wp_handle_upload( $file, [ 'test_form' => false ] );

        if ( isset( $upload['error'] ) ) {
            CKPP_Error_Handler::log_security_event('Font Upload Failed: wp_handle_upload error', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'file_name' => $file['name'],
                'upload_error' => $upload['error']
            ]);
            CKPP_Error_Handler::handle_admin_error( sprintf( __( 'Failed to upload font file: %s', 'customkings' ), $upload['error'] ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ckpp_fonts';
        $font_name = sanitize_text_field( $_POST['ckpp_font_name'] );
        $font_file_url = esc_url_raw( $upload['url'] );

        $wpdb->insert( $table, [
            'font_name' => $font_name,
            'font_file' => $font_file_url,
        ] );

        if ( $wpdb->last_error ) {
            CKPP_Error_Handler::log_security_event('Font Upload Failed: Database insert error', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'font_name' => $font_name,
                'font_file' => $font_file_url,
                'db_error' => $wpdb->last_error
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'Failed to save font details to database.', 'customkings' ) );
        }

        CKPP_Error_Handler::log_security_event('Font Upload Successful', [
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'font_name' => $font_name,
            'font_file_url' => $font_file_url
        ]);

        self::invalidate_font_cache(); // Invalidate cache on upload
        wp_redirect( add_query_arg( 'ckpp_font_success', 'uploaded', wp_get_referer() ) );
        exit;
    }

    /**
     * Handle font deletion from the admin UI. Requires nonce and capability.
     */
    public function handle_delete() {
        CKPP_Security::verify_capability('manage_options');
        check_admin_referer( 'ckpp_delete_font' );

        $font_id = isset($_POST['font_id']) ? intval($_POST['font_id']) : (isset($_GET['font_id']) ? intval($_GET['font_id']) : 0);
        if ( ! $font_id ) {
            CKPP_Error_Handler::log_security_event('Font Deletion Failed: No font ID specified', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'No font ID specified for deletion.', 'customkings' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ckpp_fonts';
        $font = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $font_id ) );

        if ( ! $font ) {
            CKPP_Error_Handler::log_security_event('Font Deletion Failed: Font not found', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'font_id' => $font_id
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'Font not found for deletion.', 'customkings' ) );
        }

        // Attempt to delete the file
        $file_path = str_replace( wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $font->font_file );
        if ( file_exists( $file_path ) ) {
            if ( ! unlink( $file_path ) ) {
                CKPP_Error_Handler::log_security_event('Font Deletion Failed: Could not delete file', [
                    'user_id' => get_current_user_id(),
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'font_id' => $font_id,
                    'font_name' => $font->font_name,
                    'file_path' => $file_path
                ]);
                CKPP_Error_Handler::handle_admin_error( sprintf( __( 'Failed to delete font file: %s', 'customkings' ), $font->font_name ) );
            }
        } else {
            CKPP_Error_Handler::log_security_event('Font Deletion Warning: File not found on disk', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'font_id' => $font_id,
                'font_name' => $font->font_name,
                'file_path' => $file_path
            ]);
        }

        $deleted = $wpdb->delete( $table, [ 'id' => $font->id ] );

        if ( $deleted === false ) {
            CKPP_Error_Handler::log_security_event('Font Deletion Failed: Database delete error', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'font_id' => $font_id,
                'font_name' => $font->font_name,
                'db_error' => $wpdb->last_error
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'Failed to delete font details from database.', 'customkings' ) );
        }

        CKPP_Error_Handler::log_security_event('Font Deletion Successful', [
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'font_id' => $font_id,
            'font_name' => $font->font_name
        ]);

        self::invalidate_font_cache(); // Invalidate cache on delete
        wp_redirect( add_query_arg( 'ckpp_font_success', 'deleted', wp_get_referer() ) );
        exit;
    }

    /**
     * Get all uploaded fonts from the database.
     *
     * @return array
     */
    public static function get_fonts() {
        if ( class_exists( 'CKPP_Cache' ) ) {
            $fonts = CKPP_Cache::get( 'ckpp_all_fonts', 'assets' );
            if ( false !== $fonts ) {
                return $fonts;
            }
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ckpp_fonts';
        $fonts = $wpdb->get_results( "SELECT * FROM $table ORDER BY uploaded_at DESC" );

        if ( class_exists( 'CKPP_Cache' ) ) {
            CKPP_Cache::set( 'ckpp_all_fonts', $fonts, 'assets', 6 * HOUR_IN_SECONDS );
        }

        return $fonts;
    }
}