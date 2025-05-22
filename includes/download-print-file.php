<?php
/**
 * Securely serves print-ready files from a private directory.
 *
 * This script ensures that only authorized users (with 'manage_woocommerce' capability)
 * can download print files, preventing direct public access.
 *
 * @package CustomKings_Product_Personalizer
 * @subpackage Includes
 */

// Ensure WordPress environment is loaded
if ( ! defined( 'ABSPATH' ) ) {
    require_once dirname( __FILE__, 2 ) . '/../../../wp-load.php';
}

// Verify user capability
if ( ! current_user_can( 'manage_woocommerce' ) ) {
    wp_die( __( 'You do not have sufficient permissions to download this file.', 'customkings' ), __( 'Access Denied', 'customkings' ), 403 );
}

// Validate and sanitize the requested file name
$file_name = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';

if ( empty( $file_name ) ) {
    wp_die( __( 'No file specified.', 'customkings' ), __( 'Error', 'customkings' ), 400 );
}

// Construct the full path to the file in the private directory
$upload_dir = wp_upload_dir();
$file_path = $upload_dir['basedir'] . '/ckpp_private_files/' . $file_name;

// Ensure the file exists and is within the private directory
if ( ! file_exists( $file_path ) || strpos( realpath( $file_path ), realpath( $upload_dir['basedir'] . '/ckpp_private_files/' ) ) !== 0 ) {
    wp_die( __( 'File not found or access denied.', 'customkings' ), __( 'Error', 'customkings' ), 404 );
}

// Serve the file
header( 'Content-Description: File Transfer' );
header( 'Content-Type: application/pdf' );
header( 'Content-Disposition: attachment; filename="' . basename( $file_name ) . '"' );
header( 'Content-Transfer-Encoding: binary' );
header( 'Expires: 0' );
header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
header( 'Pragma: public' );
header( 'Content-Length: ' . filesize( $file_path ) );
ob_clean();
flush();
readfile( $file_path );
exit;