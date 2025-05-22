<?php
/**
 * CustomKings Product Personalizer Error Handler.
 *
 * @package CustomKings_Product_Personalizer
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles standardized error reporting and logging for the CustomKings Product Personalizer plugin.
 *
 * This class provides static methods to manage and log errors encountered during plugin
 * execution, distinguishing between admin-facing and AJAX-specific errors.
 *
 * @since 1.0.0
 * @version 1.0.0
 * @access public
 */
class CKPP_Error_Handler {

	/**
	 * Handles errors for admin-facing actions, typically terminating execution with `wp_die()`.
	 *
	 * This method logs the error internally and then displays a user-friendly error page
	 * to the administrator, preventing further script execution.
	 *
	 * @param string $message     The error message to display to the user and log.
	 * @param string $title       Optional. The title for the error page. Defaults to an empty string.
	 * @param int    $status_code Optional. The HTTP status code to send with the response. Defaults to 403 (Forbidden).
	 * @return void This function terminates script execution.
	 * @uses   wp_die()
	 * @uses   self::log_error()
	 * @since 1.0.0
	 * @access public
	 */
	public static function handle_admin_error( string $message, string $title = '', int $status_code = 403 ): void {
		self::log_error( 'Admin Error: ' . $message, [ 'title' => $title, 'status_code' => $status_code ] );
		wp_die( esc_html( $message ), esc_html( $title ), [ 'response' => $status_code ] );
	}

	/**
	 * Handles errors specifically for AJAX callbacks, typically sending a JSON error response.
	 *
	 * This method logs the error internally and then sends a JSON response with an error
	 * message and optional additional data, terminating the script execution.
	 *
	 * @param string $message     The error message to send in the JSON response and log.
	 * @param array  $data        Optional. Additional data to include in the JSON error response. Defaults to an empty array.
	 * @param int    $status_code Optional. The HTTP status code to send with the JSON response. Defaults to 400 (Bad Request).
	 * @return void This function terminates script execution by sending a JSON response.
	 * @uses   wp_send_json_error()
	 * @uses   self::log_error()
	 * @since 1.0.0
	 * @access public
	 */
	public static function handle_ajax_error( string $message, array $data = [], int $status_code = 400 ): void {
		self::log_error( 'AJAX Error: ' . $message, [ 'data' => $data, 'status_code' => $status_code ] );
		wp_send_json_error(
			[
				'message' => esc_html( $message ),
				'data'    => $data,
			],
			$status_code
		);
	}

	/**
	 * Logs errors internally, primarily for debugging purposes in development environments.
	 *
	 * When `WP_DEBUG` is enabled, this method outputs error messages to the PHP error log,
	 * including any provided context. In a production environment, this logging might be
	 * suppressed or integrated with a more robust logging system.
	 *
	 * @param string $message The error message to be logged.
	 * @param array  $context Optional. An associative array providing additional contextual data for the log entry. Defaults to an empty array.
	 * @return void
	 * @uses   error_log()
	 * @uses   wp_json_encode()
	 * @since 1.0.0
	 * @access private
	 */
	private static function log_error( string $message, array $context = [] ): void {
	 if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	 	error_log( 'CKPP_Error: ' . $message . ' ' . wp_json_encode( $context ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	 }
	 // Future: Integrate with a more robust logging system (e.g., Monolog, custom DB table).
	}

	/**
	 * Logs security-relevant events, always logging regardless of WP_DEBUG status.
	 *
	 * This method is intended for critical security events that must always be logged,
	 * even in production environments where WP_DEBUG might be disabled.
	 *
	 * @param string $message The security event message to be logged.
	 * @param array  $context Optional. Additional contextual data for the log entry.
	 * @return void
	 * @uses   error_log()
	 * @uses   wp_json_encode()
	 * @since 1.0.0
	 * @access public
	 */
	public static function log_security_event( string $message, array $context = [] ): void {
	 error_log( 'CKPP_Security_Event: ' . $message . ' ' . wp_json_encode( $context ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}