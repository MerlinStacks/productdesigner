<?php
/**
 * Clipart management for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CKPP_Clipart {
    public function __construct() {
        register_activation_hook( CUSTOMKINGS_PLUGIN_FILE, [ __CLASS__, 'create_tables' ] );
        add_action( 'admin_post_ckpp_upload_clipart', [ $this, 'handle_upload' ] );
        add_action( 'admin_post_ckpp_delete_clipart', [ $this, 'handle_delete' ] );
    }

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

    public function handle_upload() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'customkings' ) );
        check_admin_referer( 'ckpp_upload_clipart' );
        if ( ! isset( $_FILES['ckpp_clipart_file'] ) || empty( $_FILES['ckpp_clipart_file']['name'] ) ) {
            wp_redirect( add_query_arg( 'ckpp_clipart_error', 'no_file', wp_get_referer() ) );
            exit;
        }
        $file = $_FILES['ckpp_clipart_file'];
        $allowed_types = [ 'svg' => 'image/svg+xml', 'png' => 'image/png' ];
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! array_key_exists( $ext, $allowed_types ) ) {
            wp_redirect( add_query_arg( 'ckpp_clipart_error', 'invalid_type', wp_get_referer() ) );
            exit;
        }
        $upload = wp_handle_upload( $file, [ 'test_form' => false ] );
        if ( isset( $upload['error'] ) ) {
            wp_redirect( add_query_arg( 'ckpp_clipart_error', 'upload_error', wp_get_referer() ) );
            exit;
        }
        global $wpdb;
        $clipart = $wpdb->prefix . 'ckpp_clipart';
        $name = sanitize_text_field( $_POST['ckpp_clipart_name'] );
        $tags = sanitize_text_field( $_POST['ckpp_clipart_tags'] );
        $wpdb->insert( $clipart, [
            'name' => $name,
            'file_url' => esc_url_raw( $upload['url'] ),
            'tags' => $tags,
        ] );
        wp_redirect( add_query_arg( 'ckpp_clipart_success', 'uploaded', wp_get_referer() ) );
        exit;
    }

    public function handle_delete() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'customkings' ) );
        check_admin_referer( 'ckpp_delete_clipart' );
        if ( ! isset( $_POST['clipart_id'] ) ) {
            wp_redirect( add_query_arg( 'ckpp_clipart_error', 'no_id', wp_get_referer() ) );
            exit;
        }
        global $wpdb;
        $clipart = $wpdb->prefix . 'ckpp_clipart';
        $clip = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $clipart WHERE id = %d", intval( $_POST['clipart_id'] ) ) );
        if ( $clip ) {
            $file_path = str_replace( wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $clip->file_url );
            if ( file_exists( $file_path ) ) {
                unlink( $file_path );
            }
            $wpdb->delete( $clipart, [ 'id' => $clip->id ] );
        }
        wp_redirect( add_query_arg( 'ckpp_clipart_success', 'deleted', wp_get_referer() ) );
        exit;
    }

    public static function get_clipart() {
        global $wpdb;
        $clipart = $wpdb->prefix . 'ckpp_clipart';
        return $wpdb->get_results( "SELECT * FROM $clipart ORDER BY uploaded_at DESC" );
    }
} 