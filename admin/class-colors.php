<?php
/**
 * Color palette and color management for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CKPP_Colors {
    public function __construct() {
        register_activation_hook( CUSTOMKINGS_PLUGIN_FILE, [ __CLASS__, 'create_tables' ] );
        add_action( 'admin_post_ckpp_add_palette', [ $this, 'handle_add_palette' ] );
        add_action( 'admin_post_ckpp_delete_palette', [ $this, 'handle_delete_palette' ] );
        add_action( 'admin_post_ckpp_add_color', [ $this, 'handle_add_color' ] );
        add_action( 'admin_post_ckpp_delete_color', [ $this, 'handle_delete_color' ] );
    }

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $palettes = $wpdb->prefix . 'ckpp_color_palettes';
        $colors = $wpdb->prefix . 'ckpp_colors';
        dbDelta( "CREATE TABLE $palettes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;" );
        dbDelta( "CREATE TABLE $colors (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            palette_id mediumint(9) NOT NULL,
            name varchar(255) NOT NULL,
            hex_code varchar(7) NOT NULL,
            PRIMARY KEY  (id),
            KEY palette_id (palette_id)
        ) $charset_collate;" );
    }

    public function handle_add_palette() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'customkings' ) );
        check_admin_referer( 'ckpp_add_palette' );
        global $wpdb;
        $palettes = $wpdb->prefix . 'ckpp_color_palettes';
        $name = sanitize_text_field( $_POST['palette_name'] );
        if ( $name ) {
            $wpdb->insert( $palettes, [ 'name' => $name ] );
            wp_redirect( add_query_arg( 'ckpp_palette_success', 'added', wp_get_referer() ) );
        } else {
            wp_redirect( add_query_arg( 'ckpp_palette_error', 'no_name', wp_get_referer() ) );
        }
        exit;
    }

    public function handle_delete_palette() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'customkings' ) );
        check_admin_referer( 'ckpp_delete_palette' );
        if ( ! isset( $_POST['palette_id'] ) ) {
            wp_redirect( add_query_arg( 'ckpp_palette_error', 'no_id', wp_get_referer() ) );
            exit;
        }
        global $wpdb;
        $palettes = $wpdb->prefix . 'ckpp_color_palettes';
        $colors = $wpdb->prefix . 'ckpp_colors';
        $palette_id = intval( $_POST['palette_id'] );
        $wpdb->delete( $palettes, [ 'id' => $palette_id ] );
        $wpdb->delete( $colors, [ 'palette_id' => $palette_id ] );
        wp_redirect( add_query_arg( 'ckpp_palette_success', 'deleted', wp_get_referer() ) );
        exit;
    }

    public function handle_add_color() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'customkings' ) );
        check_admin_referer( 'ckpp_add_color' );
        global $wpdb;
        $colors = $wpdb->prefix . 'ckpp_colors';
        $palette_id = intval( $_POST['palette_id'] );
        $name = sanitize_text_field( $_POST['color_name'] );
        $hex = preg_match( '/^#[0-9A-Fa-f]{6}$/', $_POST['color_hex'] ) ? $_POST['color_hex'] : '';
        if ( $palette_id && $name && $hex ) {
            $wpdb->insert( $colors, [ 'palette_id' => $palette_id, 'name' => $name, 'hex_code' => $hex ] );
            wp_redirect( add_query_arg( 'ckpp_color_success', 'added', wp_get_referer() ) );
        } else {
            wp_redirect( add_query_arg( 'ckpp_color_error', 'invalid', wp_get_referer() ) );
        }
        exit;
    }

    public function handle_delete_color() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'customkings' ) );
        check_admin_referer( 'ckpp_delete_color' );
        if ( ! isset( $_POST['color_id'] ) ) {
            wp_redirect( add_query_arg( 'ckpp_color_error', 'no_id', wp_get_referer() ) );
            exit;
        }
        global $wpdb;
        $colors = $wpdb->prefix . 'ckpp_colors';
        $color_id = intval( $_POST['color_id'] );
        $wpdb->delete( $colors, [ 'id' => $color_id ] );
        wp_redirect( add_query_arg( 'ckpp_color_success', 'deleted', wp_get_referer() ) );
        exit;
    }

    public static function get_palettes() {
        global $wpdb;
        $palettes = $wpdb->prefix . 'ckpp_color_palettes';
        return $wpdb->get_results( "SELECT * FROM $palettes ORDER BY name ASC" );
    }

    public static function get_colors( $palette_id ) {
        global $wpdb;
        $colors = $wpdb->prefix . 'ckpp_colors';
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $colors WHERE palette_id = %d ORDER BY name ASC", $palette_id ) );
    }
} 