<?php
/**
 * Clipart management for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CKPP_Clipart
 * Handles clipart management for the CustomKings Product Personalizer plugin.
 */
class CKPP_Clipart {
    /**
     * Register hooks for clipart upload and delete actions.
     */
    public function __construct() {
        register_activation_hook( CUSTOMKINGS_PLUGIN_FILE, [ __CLASS__, 'create_tables' ] );
        add_action( 'admin_post_ckpp_upload_clipart', [ $this, 'handle_upload' ] );
        add_action( 'admin_post_ckpp_delete_clipart', [ $this, 'handle_delete' ] );
    }

    /**
     * Invalidate the clipart cache.
     */
    private static function invalidate_clipart_cache() {
        if ( class_exists( 'CKPP_Cache' ) ) {
            CKPP_Cache::delete( 'ckpp_all_clipart', 'assets' );
            CKPP_Cache::delete( 'ckpp_clipart_categories', 'assets' );
        }
    }

    /**
     * Create the custom database tables for clipart and tags.
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $clipart = $wpdb->prefix . 'ckpp_clipart';
        $tags = $wpdb->prefix . 'ckpp_clipart_tags';
        dbDelta( "CREATE TABLE $clipart (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            file_url varchar(255) NOT NULL,
            tags varchar(255) DEFAULT '',
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;" );
        dbDelta( "CREATE TABLE $tags (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;" );
    }

    /**
     * Handle clipart file upload from the admin UI. Requires nonce and capability.
     */
    public function handle_upload() {
        CKPP_Security::verify_capability('manage_options');
        CKPP_Security::verify_ajax_nonce('_wpnonce', 'ckpp_upload_clipart');

        if ( ! isset( $_FILES['ckpp_clipart_file'] ) || empty( $_FILES['ckpp_clipart_file']['name'] ) ) {
            CKPP_Error_Handler::log_security_event('Clipart Upload Failed: No file uploaded', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'No clipart file uploaded.', 'customkings' ) );
        }

        $file = $_FILES['ckpp_clipart_file'];
        $allowed_types = [
            'svg'  => 'image/svg+xml',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp'
        ];
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

        if ( ! array_key_exists( $ext, $allowed_types ) ) {
            CKPP_Error_Handler::log_security_event('Clipart Upload Failed: Invalid file type', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'file_name' => $file['name'],
                'file_type' => $ext
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'Invalid clipart file type.', 'customkings' ) );
        }

        $upload = wp_handle_upload( $file, [ 'test_form' => false ] );

        if ( isset( $upload['error'] ) ) {
            CKPP_Error_Handler::log_security_event('Clipart Upload Failed: wp_handle_upload error', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'file_name' => $file['name'],
                'upload_error' => $upload['error']
            ]);
            CKPP_Error_Handler::handle_admin_error( sprintf( __( 'Failed to upload clipart file: %s', 'customkings' ), $upload['error'] ) );
        }

        global $wpdb;
        $clipart_table = $wpdb->prefix . 'ckpp_clipart';
        $name = sanitize_text_field( $_POST['ckpp_clipart_name'] );
        $tags = sanitize_text_field( $_POST['ckpp_clipart_tags'] );
        $file_url = esc_url_raw( $upload['url'] );

        $wpdb->insert( $clipart_table, [
            'name' => $name,
            'file_url' => $file_url,
            'tags' => $tags,
        ] );

        if ( $wpdb->last_error ) {
            CKPP_Error_Handler::log_security_event('Clipart Upload Failed: Database insert error', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'clipart_name' => $name,
                'file_url' => $file_url,
                'db_error' => $wpdb->last_error
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'Failed to save clipart details to database.', 'customkings' ) );
        }

        CKPP_Error_Handler::log_security_event('Clipart Upload Successful', [
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'clipart_name' => $name,
            'file_url' => $file_url,
            'tags' => $tags
        ]);

        self::invalidate_clipart_cache(); // Invalidate cache on upload
        wp_redirect( add_query_arg( 'ckpp_clipart_success', 'uploaded', wp_get_referer() ) );
        exit;
    }

    /**
     * Handle clipart deletion from the admin UI. Requires nonce and capability.
     */
    public function handle_delete() {
        CKPP_Security::verify_capability('manage_options');
        CKPP_Security::verify_ajax_nonce('_wpnonce', 'ckpp_delete_clipart');

        $clipart_id = isset( $_POST['clipart_id'] ) ? intval( $_POST['clipart_id'] ) : 0;
        if ( ! $clipart_id ) {
            CKPP_Error_Handler::log_security_event('Clipart Deletion Failed: No clipart ID specified', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'No clipart ID specified for deletion.', 'customkings' ) );
        }

        global $wpdb;
        $clipart_table = $wpdb->prefix . 'ckpp_clipart';
        $clip = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $clipart_table WHERE id = %d", $clipart_id ) );

        if ( ! $clip ) {
            CKPP_Error_Handler::log_security_event('Clipart Deletion Failed: Clipart not found', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'clipart_id' => $clipart_id
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'Clipart not found for deletion.', 'customkings' ) );
        }

        // Attempt to delete the file
        $file_path = str_replace( wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $clip->file_url );
        if ( file_exists( $file_path ) ) {
            if ( ! unlink( $file_path ) ) {
                CKPP_Error_Handler::log_security_event('Clipart Deletion Failed: Could not delete file', [
                    'user_id' => get_current_user_id(),
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'clipart_id' => $clipart_id,
                    'clipart_name' => $clip->name,
                    'file_path' => $file_path
                ]);
                CKPP_Error_Handler::handle_admin_error( sprintf( __( 'Failed to delete clipart file: %s', 'customkings' ), $clip->name ) );
            }
        } else {
            CKPP_Error_Handler::log_security_event('Clipart Deletion Warning: File not found on disk', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'clipart_id' => $clipart_id,
                'clipart_name' => $clip->name,
                'file_path' => $file_path
            ]);
        }

        $deleted = $wpdb->delete( $clipart_table, [ 'id' => $clip->id ] );

        if ( $deleted === false ) {
            CKPP_Error_Handler::log_security_event('Clipart Deletion Failed: Database delete error', [
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'clipart_id' => $clipart_id,
                'clipart_name' => $clip->name,
                'db_error' => $wpdb->last_error
            ]);
            CKPP_Error_Handler::handle_admin_error( __( 'Failed to delete clipart details from database.', 'customkings' ) );
        }

        CKPP_Error_Handler::log_security_event('Clipart Deletion Successful', [
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'clipart_id' => $clipart_id,
            'clipart_name' => $clip->name
        ]);

        self::invalidate_clipart_cache(); // Invalidate cache on delete
        wp_redirect( add_query_arg( 'ckpp_clipart_success', 'deleted', wp_get_referer() ) );
        exit;
    }

    /**
     * Get all uploaded clipart from the database.
     *
     * @return array
     */
    public static function get_clipart() {
        if ( class_exists( 'CKPP_Cache' ) ) {
            $clipart_data = CKPP_Cache::get( 'ckpp_all_clipart', 'assets' );
            if ( false !== $clipart_data ) {
                return $clipart_data;
            }
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ckpp_clipart';
        $clipart_data = $wpdb->get_results( "SELECT * FROM $table ORDER BY uploaded_at DESC" );

        if ( class_exists( 'CKPP_Cache' ) ) {
            CKPP_Cache::set( 'ckpp_all_clipart', $clipart_data, 6 * HOUR_IN_SECONDS, 'assets' );
        }

        return $clipart_data;
    }

    /**
     * Get all unique clipart categories (tags) from the database.
     *
     * @return array
     */
    public static function get_clipart_categories() {
        if ( class_exists( 'CKPP_Cache' ) ) {
            $categories = CKPP_Cache::get( 'ckpp_clipart_categories', 'assets' );
            if ( false !== $categories ) {
                return $categories;
            }
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ckpp_clipart';
        $results = $wpdb->get_col( "SELECT DISTINCT tags FROM $table WHERE tags != ''" );
        $categories = [];
        foreach ( $results as $tags_string ) {
            $tags_array = array_map( 'trim', explode( ',', $tags_string ) );
            $categories = array_merge( $categories, $tags_array );
        }
        $categories = array_unique( array_filter( $categories ) );
        sort( $categories );

        if ( class_exists( 'CKPP_Cache' ) ) {
            CKPP_Cache::set( 'ckpp_clipart_categories', $categories, 6 * HOUR_IN_SECONDS, 'assets' );
        }

        return $categories;
    }
}