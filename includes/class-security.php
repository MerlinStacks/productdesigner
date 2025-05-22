<?php
/**
 * Security class for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CKPP_Security {
    /**
     * Handles security-related functionalities for the CustomKings Product Personalizer plugin.
     *
     * This class provides static methods for nonce verification, capability checks,
     * input sanitization, rate limiting, and secure file uploads.
     *
     * @since 1.0.0
     * @version 1.0.0
     * @access public
     */
    /**
     * Verify nonce for AJAX requests.
     *
     * Ensures that an AJAX request has a valid nonce to prevent CSRF attacks.
     * If the nonce is invalid, it terminates the script with a 403 Forbidden error.
     *
     * @param string $nonce_name The name of the nonce field in the request (e.g., '_wpnonce').
     * @param string $action The nonce action name that was used when creating the nonce.
     * @return bool True if the nonce is valid.
     * @throws \CKPP_Error_Handler If the security check fails, the script will die with a 403 error.
     * @since 1.0.0
     * @access public
     */
    public static function verify_ajax_nonce(string $nonce_name = 'nonce', string $action = 'ckpp_ajax_nonce'): bool {
        if (!isset($_REQUEST[$nonce_name]) || !wp_verify_nonce($_REQUEST[$nonce_name], $action)) {
            CKPP_Error_Handler::log_security_event('Nonce verification failed', [
                'nonce_name' => $nonce_name,
                'action' => $action,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            CKPP_Error_Handler::handle_ajax_error( __( 'Security check failed', 'customkings' ), [], 403 );
        }
        CKPP_Error_Handler::log_security_event('Nonce verification successful', [
            'nonce_name' => $nonce_name,
            'action' => $action,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        return true;
    }

    /**
     * Verifies if the current user has a specified capability.
     *
     * If the user does not have the required capability, it terminates the script
     * with a 403 Forbidden error via `CKPP_Error_Handler`.
     *
     * @param string $capability The WordPress capability to check (e.g., 'manage_options', 'edit_posts').
     * @return bool True if the current user possesses the specified capability.
     * @throws \CKPP_Error_Handler If the user lacks the required permissions, the script will die with a 403 error.
     * @since 1.0.0
     * @access public
     */
    public static function verify_capability(string $capability = 'manage_options'): bool {
        if (!current_user_can($capability)) {
            CKPP_Error_Handler::log_security_event('Capability check failed', [
                'capability' => $capability,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            CKPP_Error_Handler::handle_ajax_error( __( 'Insufficient permissions', 'customkings' ), [], 403 );
        }
        CKPP_Error_Handler::log_security_event('Capability check successful', [
            'capability' => $capability,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        return true;
    }

    /**
     * Sanitizes input data based on a specified type.
     *
     * This method provides various sanitization routines for different data types,
     * ensuring data integrity and security before processing or storage.
     *
     * @param mixed $data The data to be sanitized. Can be of any type.
     * @param string $type The type of sanitization to apply.
     *                     Supported types: 'text', 'int', 'float', 'bool', 'array', 'json',
     *                     'filename', 'html', 'textarea', 'email', 'url'.
     * @return mixed The sanitized data. Returns `null` if the input `$data` is `null`.
     *               For 'array' type, returns an empty array if input is not an array.
     *               For 'json' type, returns `null` if JSON decoding fails.
     * @since 1.0.0
     * @access public
     */
    public static function sanitize_input(mixed $data, string $type = 'text'): mixed {
        if (is_null($data)) {
            return null;
        }

        switch ($type) {
            case 'int':
                return intval($data);
                
            case 'float':
                return floatval($data);
                
            case 'bool':
                return (bool) $data;
                
            case 'array':
                if (!is_array($data)) {
                    return [];
                }
                return array_map('sanitize_text_field', $data);
                
            case 'json':
                if (is_string($data)) {
                    $decoded = json_decode(wp_unslash($data), true);
                    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
                }
                return is_array($data) ? $data : null;
                
            case 'filename':
                return sanitize_file_name($data);
                
            case 'html':
                return wp_kses_post($data);
                
            case 'textarea':
                return sanitize_textarea_field($data);
                
            case 'email':
                return sanitize_email($data);
                
            case 'url':
                return esc_url_raw($data);
                
            default: // text
                return sanitize_text_field($data);
        }
    }

    /**
     * Checks and enforces rate limiting for specific actions.
     *
     * This method uses WordPress transients to track the number of attempts for a given action
     * within a specified time window. If the limit is exceeded, it sends a JSON error response
     * with a 429 Too Many Requests status and terminates the script.
     *
     * @param string $action The name of the action to rate limit (e.g., 'login_attempts', 'form_submissions').
     * @param int $limit The maximum number of allowed attempts within the time window.
     * @param int $seconds The time window in seconds during which the attempts are counted.
     * @return bool True if the current request is within the allowed rate limit.
     * @throws \WP_Error If the rate limit is exceeded, the script will terminate with a 429 error.
     * @since 1.0.0
     * @access public
     */
    public static function check_rate_limit(string $action = 'default', int $limit = 5, int $seconds = 60): bool {
        $transient_name = 'ckpp_rate_limit_' . md5($action . $_SERVER['REMOTE_ADDR']);
        $attempts = get_transient($transient_name) ?: 0;
        
        if ($attempts >= $limit) {
            wp_send_json_error([
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $seconds
            ], 429);
            wp_die();
        }
        
        set_transient($transient_name, $attempts + 1, $seconds);
        return true;
    }

    /**
     * Handles secure file uploads, including validation, sanitization, and storage.
     *
     * This method performs comprehensive checks on uploaded files, including error status,
     * size limits, and MIME types. It generates a secure filename, creates a dedicated
     * upload directory if necessary (with a .htaccess file to prevent direct access),
     * moves the uploaded file, and sets appropriate file permissions.
     *
     * @param string $file_input The name of the file input field in the `$_FILES` superglobal (e.g., 'my_file_upload').
     * @param string[] $allowed_mime_types An array of allowed MIME types for the upload (e.g., ['image/jpeg', 'image/png']).
     * @param int $max_size The maximum allowed file size in bytes. Defaults to 2MB (2097152 bytes).
     * @return array{url: string, path: string, type: string, name: string, size: int}|\WP_Error An associative array containing file details on successful upload,
     *                                                                                              or a `WP_Error` object on failure.
     * @throws \WP_Error If any validation fails (e.g., no file, upload error, file too large, invalid type)
     *                   or if the file cannot be moved or permissions cannot be set.
     * @since 1.0.0
     * @access public
     */
    public static function handle_file_upload(string $file_input, array $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'], int $max_size = 2097152): array|\WP_Error {
        if (!isset($_FILES[$file_input])) {
            $error = new \WP_Error('no_file', 'No file was uploaded');
            CKPP_Error_Handler::log_security_event('File Upload Failed: ' . $error->get_error_message(), ['file_input' => $file_input, 'error_code' => 'no_file']);
            return $error;
        }

        $file = $_FILES[$file_input];
        
        // Basic validation
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_message = self::get_upload_error_message($file['error']);
            $error = new WP_Error('upload_error', $error_message);
            CKPP_Error_Handler::log_security_event('File Upload Failed: ' . $error->get_error_message(), ['file_input' => $file_input, 'error_code' => $file['error']]);
            return $error;
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            $error = new WP_Error('file_too_large', sprintf(
                'File is too large. Maximum size is %s',
                size_format($max_size)
            ));
            CKPP_Error_Handler::log_security_event('File Upload Failed: ' . $error->get_error_message(), ['file_input' => $file_input, 'file_size' => $file['size'], 'max_size' => $max_size]);
            return $error;
        }
        
        // Verify MIME type
        $filetype = wp_check_filetype($file['name']);
        if (!in_array($filetype['type'], $allowed_mime_types)) {
            $error = new WP_Error('invalid_type', 'Invalid file type. Allowed types: ' . implode(', ', $allowed_mime_types));
            CKPP_Error_Handler::log_security_event('File Upload Failed: ' . $error->get_error_message(), ['file_input' => $file_input, 'file_type' => $filetype['type'], 'allowed_types' => $allowed_mime_types]);
            return $error;
        }
        
        // Generate a secure filename
        $filename = sanitize_file_name($file['name']);
        $filename = wp_unique_filename(WP_CONTENT_DIR . '/uploads/ckpp_uploads', $filename);
        
        // Create upload directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'] . '/ckpp_uploads';
        
        if (!file_exists($upload_path)) {
            wp_mkdir_p($upload_path);
            // Add .htaccess to prevent direct access
            file_put_contents($upload_path . '/.htaccess', "Order Deny,Allow\nDeny from all");
        }
        
        $destination = $upload_path . '/' . $filename;
        
        // Move the file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $error = new WP_Error('upload_failed', 'Failed to move uploaded file');
            CKPP_Error_Handler::log_security_event('File Upload Failed: ' . $error->get_error_message(), ['file_input' => $file_input, 'destination' => $destination]);
            return $error;
        }
        
        // Set proper file permissions
        chmod($destination, 0644);

        CKPP_Error_Handler::log_security_event('File Upload Success', [
            'file_input' => $file_input,
            'filename' => $filename,
            'type' => $filetype['type'],
            'size' => $file['size'],
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        
        return [
            'url' => $upload_dir['baseurl'] . '/ckpp_uploads/' . $filename,
            'path' => $destination,
            'type' => $filetype['type'],
            'name' => $filename,
            'size' => $file['size']
        ];
    }
    
    /**
     * Retrieves a user-friendly error message for a given file upload error code.
     *
     * This private helper method translates PHP's `UPLOAD_ERR_` constants into
     * more descriptive and understandable messages for the end-user or logs.
     *
     * @param int $error_code The upload error code, typically from `$_FILES['file']['error']`.
     * @return string A human-readable error message corresponding to the provided error code.
     * @since 1.0.0
     * @access private
     */
    private static function get_upload_error_message(int $error_code): string {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
}
