# Security Architecture

This document outlines the security architecture for the WordPress/WooCommerce Product Personalization Plugin, covering authentication, authorization, data validation, and other security considerations.

## 1. Security Principles

The plugin follows these core security principles:

1. **Defense in Depth:** Multiple layers of security controls
2. **Principle of Least Privilege:** Users have only the permissions they need
3. **Secure by Default:** Security-first approach to all features
4. **Input Validation:** All inputs are validated and sanitized
5. **Output Encoding:** All outputs are properly escaped
6. **Secure Communication:** Data in transit is protected
7. **Error Handling:** Errors are handled securely without information leakage
8. **Regular Updates:** Security patches and updates are applied promptly

## 2. Authentication & Authorization

### 2.1 WordPress Authentication Integration

The plugin leverages WordPress's built-in authentication system:

```php
// Example: Checking if user is logged in
if (!is_user_logged_in()) {
    wp_die(__('Access denied. Please log in.', 'product-personalizer'));
}
```

### 2.2 Role-Based Access Control

The plugin implements role-based access control for administrative functions:

| Feature | Required Capability | Description |
|---------|---------------------|-------------|
| Plugin Settings | `manage_options` | Access to global plugin settings |
| Product Configuration | `edit_products` | Ability to configure personalization for products |
| Asset Management | `upload_files` + `manage_options` | Upload and manage assets (fonts, clipart, etc.) |
| Order Management | `edit_shop_orders` | View and manage personalized orders |

```php
// Example: Checking capabilities for admin functions
function check_admin_permissions() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'product-personalizer'));
    }
}
```

### 2.3 REST API Authentication

All REST API endpoints are secured with WordPress authentication:

```php
// Example: Registering a secure REST API endpoint
register_rest_route('product-personalizer/v1', '/products/(?P<id>\d+)/config', [
    'methods' => 'GET',
    'callback' => 'get_product_config',
    'permission_callback' => function() {
        return current_user_can('read');
    }
]);

// Example: Registering an admin-only REST API endpoint
register_rest_route('product-personalizer/v1', '/products/(?P<id>\d+)/config', [
    'methods' => 'POST',
    'callback' => 'save_product_config',
    'permission_callback' => function() {
        return current_user_can('edit_products');
    }
]);
```

### 2.4 Nonce Verification

All form submissions and AJAX requests include nonce verification:

```php
// Example: Adding a nonce to a form
function render_admin_form() {
    wp_nonce_field('product_personalizer_save_settings', 'product_personalizer_nonce');
    // Form fields...
}

// Example: Verifying a nonce on form submission
function process_admin_form() {
    if (!isset($_POST['product_personalizer_nonce']) || 
        !wp_verify_nonce($_POST['product_personalizer_nonce'], 'product_personalizer_save_settings')) {
        wp_die(__('Security check failed. Please try again.', 'product-personalizer'));
    }
    // Process form...
}

// Example: Adding a nonce to AJAX
function enqueue_admin_scripts() {
    wp_localize_script('product-personalizer-admin', 'productPersonalizer', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('product_personalizer_ajax')
### 2.5 Frontend Security

For frontend personalization, the plugin implements:

1. **Public vs. Private Actions:** Clear separation between actions that require authentication and those that don't
2. **Rate Limiting:** Prevention of abuse through rate limiting on public endpoints
3. **Session Validation:** Ensuring personalization data belongs to the current user's session

```php
// Example: Rate limiting for preview generation
function generate_preview() {
    // Check rate limiting
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $rate_limit_key = 'pp_preview_rate_' . md5($ip_address);
    $rate_count = get_transient($rate_limit_key);
    
    if (false !== $rate_count && $rate_count >= 10) {
        wp_send_json_error(['message' => __('Rate limit exceeded. Please try again later.', 'product-personalizer')]);
    }
    
    // Increment counter
    if (false === $rate_count) {
        set_transient($rate_limit_key, 1, MINUTE_IN_SECONDS);
    } else {
        set_transient($rate_limit_key, $rate_count + 1, MINUTE_IN_SECONDS);
    }
    
    // Process preview generation...
}
```

## 3. Data Validation & Sanitization

### 3.1 Input Validation

All user inputs are validated before processing:

```php
// Example: Validating personalization data
function validate_personalization_data($data) {
    $errors = [];
    
    // Check required structure
    if (!isset($data['areas']) || !is_array($data['areas'])) {
        $errors[] = __('Invalid personalization data structure.', 'product-personalizer');
        return $errors;
    }
    
    foreach ($data['areas'] as $area) {
        if (!isset($area['id']) || !isset($area['options']) || !is_array($area['options'])) {
            $errors[] = __('Invalid area structure.', 'product-personalizer');
            continue;
        }
        
        foreach ($area['options'] as $option) {
            if (!isset($option['id']) || !isset($option['type']) || !isset($option['value'])) {
                $errors[] = __('Invalid option structure.', 'product-personalizer');
                continue;
            }
            
            // Type-specific validation
            switch ($option['type']) {
                case 'text':
                    if (strlen($option['value']) > 100) {
                        $errors[] = __('Text is too long (maximum 100 characters).', 'product-personalizer');
                    }
                    break;
                    
                case 'image':
                    if (!filter_var($option['value'], FILTER_VALIDATE_URL)) {
                        $errors[] = __('Invalid image URL.', 'product-personalizer');
                    }
                    break;
                    
                case 'clipart':
                    if (!is_numeric($option['value'])) {
                        $errors[] = __('Invalid clipart ID.', 'product-personalizer');
                    }
                    break;
                    
                case 'color':
                    if (!preg_match('/^#[a-f0-9]{6}$/i', $option['value'])) {
                        $errors[] = __('Invalid color format.', 'product-personalizer');
                    }
                    break;
                    
                default:
                    $errors[] = __('Unknown option type.', 'product-personalizer');
            }
        }
    }
    
    return $errors;
}
```

### 3.2 Data Sanitization

All data is sanitized before storage:

```php
// Example: Sanitizing personalization data
function sanitize_personalization_data($data) {
    $sanitized = [
        'version' => '1.0',
        'product_id' => absint($data['product_id']),
        'areas' => []
    ];
    
    foreach ($data['areas'] as $area) {
        $sanitized_area = [
            'id' => sanitize_key($area['id']),
            'options' => []
        ];
        
        foreach ($area['options'] as $option) {
            $sanitized_option = [
                'id' => sanitize_key($option['id']),
                'type' => sanitize_key($option['type'])
            ];
            
            // Type-specific sanitization
            switch ($option['type']) {
                case 'text':
                    $sanitized_option['value'] = sanitize_text_field($option['value']);
                    break;
                    
                case 'image':
                    $sanitized_option['value'] = esc_url_raw($option['value']);
                    break;
                    
                case 'clipart':
                    $sanitized_option['value'] = absint($option['value']);
                    break;
                    
                case 'color':
                    $sanitized_option['value'] = sanitize_hex_color($option['value']);
                    break;
            }
            
            if (isset($option['style'])) {
                $sanitized_option['style'] = [];
                
                if (isset($option['style']['font_id'])) {
                    $sanitized_option['style']['font_id'] = absint($option['style']['font_id']);
                }
                
                if (isset($option['style']['font_size'])) {
                    $sanitized_option['style']['font_size'] = absint($option['style']['font_size']);
                }
                
                if (isset($option['style']['color'])) {
                    $sanitized_option['style']['color'] = sanitize_hex_color($option['style']['color']);
                }
                
                if (isset($option['style']['alignment'])) {
                    $valid_alignments = ['left', 'center', 'right'];
                    $sanitized_option['style']['alignment'] = in_array($option['style']['alignment'], $valid_alignments) ? 
                        $option['style']['alignment'] : 'center';
                }
            }
            
            $sanitized_area['options'][] = $sanitized_option;
        }
        
        $sanitized['areas'][] = $sanitized_area;
    }
    
    return $sanitized;
}
```

### 3.3 Output Escaping

All output is properly escaped:

```php
// Example: Escaping output in admin interface
function render_asset_list($assets) {
    echo '<ul class="asset-list">';
    foreach ($assets as $asset) {
        echo '<li>';
        echo '<span class="asset-name">' . esc_html($asset->name) . '</span>';
        echo '<img src="' . esc_url($asset->url) . '" alt="' . esc_attr($asset->name) . '">';
        echo '</li>';
    }
    echo '</ul>';
}

// Example: Escaping output in REST API response
function get_product_config_response($product_id) {
    $config = get_post_meta($product_id, '_product_personalization_config_json', true);
    
    if (empty($config)) {
        return new WP_REST_Response(['error' => __('Configuration not found.', 'product-personalizer')], 404);
    }
    
    // No need to escape JSON data as wp_send_json handles it
    return rest_ensure_response(json_decode($config));
}
## 4. File Upload Security

### 4.1 File Validation

All uploaded files are validated:

```php
// Example: Validating file uploads
function validate_asset_upload($file) {
    $errors = [];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = __('File upload failed.', 'product-personalizer');
        return $errors;
    }
    
    // Check file size
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        $errors[] = __('File size exceeds the maximum allowed (5MB).', 'product-personalizer');
    }
    
    // Check file type
    $allowed_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/svg+xml',
        'application/font-sfnt',
        'application/vnd.ms-fontobject',
        'font/ttf',
        'font/otf',
        'font/woff',
        'font/woff2'
    ];
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime_type, $allowed_types)) {
        $errors[] = __('File type not allowed.', 'product-personalizer');
    }
    
    // Additional security checks for SVG files
    if ($mime_type === 'image/svg+xml') {
        $svg_content = file_get_contents($file['tmp_name']);
        
        // Check for potentially malicious content
        $dangerous_patterns = [
            '/<script/',
            '/javascript:/',
            '/eval\(/',
            '/expression\(/',
            '/onload=/',
            '/onclick=/',
            '/onmouseover=/'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $svg_content)) {
                $errors[] = __('SVG file contains potentially malicious content.', 'product-personalizer');
                break;
            }
        }
    }
    
    return $errors;
}
```

### 4.2 Secure File Storage

Files are stored securely:

```php
// Example: Secure file storage
function store_uploaded_asset($file, $type) {
    // Generate secure filename
    $filename = sanitize_file_name($file['name']);
    $filename = wp_unique_filename(wp_upload_dir()['path'], $filename);
    
    // Create type-specific subdirectory
    $upload_dir = wp_upload_dir();
    $asset_dir = $upload_dir['basedir'] . '/product-personalizer/' . sanitize_key($type);
    
    if (!file_exists($asset_dir)) {
        wp_mkdir_p($asset_dir);
        
        // Create .htaccess for additional security
        if ($type === 'fonts') {
            $htaccess_content = "# Restrict access to font files\n";
            $htaccess_content .= "<FilesMatch \"\.(ttf|otf|woff|woff2)$\">\n";
            $htaccess_content .= "  <IfModule mod_headers.c>\n";
            $htaccess_content .= "    Header set Access-Control-Allow-Origin \"*\"\n";
            $htaccess_content .= "  </IfModule>\n";
            $htaccess_content .= "</FilesMatch>\n";
            
            file_put_contents($asset_dir . '/.htaccess', $htaccess_content);
        }
        
        // Create index.php to prevent directory listing
        file_put_contents($asset_dir . '/index.php', '<?php // Silence is golden');
    }
    
    // Move file to final location
    $new_file_path = $asset_dir . '/' . $filename;
    move_uploaded_file($file['tmp_name'], $new_file_path);
    
    // Set correct file permissions
    chmod($new_file_path, 0644);
    
    return [
        'path' => $new_file_path,
        'url' => $upload_dir['baseurl'] . '/product-personalizer/' . $type . '/' . $filename
    ];
}
```

## 5. Database Security

### 5.1 Prepared Statements

All database queries use prepared statements:

```php
// Example: Using prepared statements
function get_assets_by_type($type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'product_personalizer_assets';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM %i WHERE type = %s AND status = %s",
        $table_name, $type, 'active'
    ));
}
```

### 5.2 Data Encryption

Sensitive data is encrypted:

```php
// Example: Encrypting sensitive data
function encrypt_sensitive_data($data) {
    if (empty($data)) {
        return '';
    }
    
    $encryption_key = get_option('product_personalizer_encryption_key');
    
    if (!$encryption_key) {
        $encryption_key = wp_generate_password(64, true, true);
        update_option('product_personalizer_encryption_key', $encryption_key);
    }
    
    $method = 'aes-256-cbc';
    $iv_length = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    $encrypted = openssl_encrypt(
        $data,
        $method,
        $encryption_key,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    return base64_encode($iv . $encrypted);
}

// Example: Decrypting sensitive data
function decrypt_sensitive_data($encrypted_data) {
    if (empty($encrypted_data)) {
        return '';
    }
    
    $encryption_key = get_option('product_personalizer_encryption_key');
    
    if (!$encryption_key) {
        return '';
    }
    
    $encrypted_data = base64_decode($encrypted_data);
    $method = 'aes-256-cbc';
    $iv_length = openssl_cipher_iv_length($method);
    $iv = substr($encrypted_data, 0, $iv_length);
    $encrypted = substr($encrypted_data, $iv_length);
    
    return openssl_decrypt(
        $encrypted,
        $method,
        $encryption_key,
        OPENSSL_RAW_DATA,
        $iv
    );
}
```
```
    ]);
## 6. Client-Side Security

### 6.1 Cross-Site Scripting (XSS) Prevention

The plugin implements multiple layers of XSS protection:

```php
// Example: Sanitizing data for JavaScript
function localize_script_data() {
    $product_id = get_the_ID();
    $config = get_post_meta($product_id, '_product_personalization_config_json', true);
    
    if (empty($config)) {
        $config = '{}';
    }
    
    wp_localize_script('product-personalizer-frontend', 'productPersonalizer', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('product_personalizer_frontend'),
        'product_id' => absint($product_id),
        'config' => json_decode($config), // wp_localize_script handles JSON encoding and escaping
        'i18n' => [
            'error' => esc_html__('An error occurred.', 'product-personalizer'),
            'success' => esc_html__('Personalization applied successfully.', 'product-personalizer')
        ]
    ]);
}
```

### 6.2 Content Security Policy

The plugin implements a Content Security Policy for admin pages:

```php
// Example: Adding Content Security Policy
function add_security_headers() {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'product-personalizer') {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:;");
    }
}
add_action('send_headers', 'add_security_headers');
```

### 6.3 CSRF Protection

In addition to WordPress nonces, the plugin implements additional CSRF protection for critical operations:

```php
// Example: Additional CSRF token validation
function validate_csrf_token() {
    if (!isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        wp_send_json_error(['message' => __('CSRF token missing.', 'product-personalizer')]);
    }
    
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    $user_id = get_current_user_id();
    $stored_token = get_transient('pp_csrf_token_' . $user_id);
    
    if (!$stored_token || $token !== $stored_token) {
        wp_send_json_error(['message' => __('Invalid CSRF token.', 'product-personalizer')]);
    }
}

// Example: Generating CSRF token
function generate_csrf_token() {
    $user_id = get_current_user_id();
    $token = bin2hex(random_bytes(32));
    set_transient('pp_csrf_token_' . $user_id, $token, HOUR_IN_SECONDS);
    return $token;
}
```

## 7. Error Handling & Logging

### 7.1 Secure Error Handling

Errors are handled securely without exposing sensitive information:

```php
// Example: Secure error handling
function handle_error($error, $is_public = false) {
    // Log the detailed error
    error_log('Product Personalizer Error: ' . print_r($error, true));
    
    // Return a sanitized error for public display
    if ($is_public) {
        return [
            'success' => false,
            'message' => __('An error occurred. Please try again.', 'product-personalizer')
        ];
    } else {
        // More detailed error for admin users
        return [
            'success' => false,
            'message' => __('An error occurred.', 'product-personalizer'),
            'details' => is_wp_error($error) ? $error->get_error_message() : $error
        ];
    }
}
```

### 7.2 Security Logging

The plugin logs security-related events:

```php
// Example: Security logging
function log_security_event($event_type, $details = []) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $username = $user ? $user->user_login : 'Guest';
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $log_entry = sprintf(
        '[%s] Security Event: %s | User: %s (ID: %d) | IP: %s | Details: %s',
        date('Y-m-d H:i:s'),
        $event_type,
        $username,
        $user_id,
        $ip,
        json_encode($details)
    );
    
    error_log($log_entry);
}
```

## 8. Third-Party Integration Security

### 8.1 PDF Generation Security

The plugin implements security measures for PDF generation:

```php
// Example: Secure PDF generation
function generate_secure_pdf($personalization_data) {
    // Validate data before processing
    $validation_errors = validate_personalization_data($personalization_data);
    if (!empty($validation_errors)) {
        return new WP_Error('validation_failed', implode(' ', $validation_errors));
    }
    
    // Use a secure PDF library (e.g., TCPDF, FPDF)
    require_once plugin_dir_path(__FILE__) . 'vendor/tcpdf/tcpdf.php';
    
    // Create PDF with security settings
    $pdf = new TCPDF();
    $pdf->SetCreator('Product Personalizer');
    $pdf->SetAuthor('Product Personalizer');
    $pdf->SetTitle('Personalized Product');
    $pdf->SetSubject('Personalized Product');
    $pdf->SetKeywords('personalized, product');
    
    // Set PDF security (optional)
    $pdf->SetProtection(['print', 'copy'], '', null, 3);
    
    // Generate PDF content
    // ...
    
    // Save to secure location
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/product-personalizer/pdfs';
    
    if (!file_exists($pdf_dir)) {
        wp_mkdir_p($pdf_dir);
        
        // Create .htaccess to protect PDF files
        $htaccess_content = "# Protect PDF files\n";
        $htaccess_content .= "<Files *.pdf>\n";
        $htaccess_content .= "  <IfModule mod_authz_core.c>\n";
        $htaccess_content .= "    Require all denied\n";
        $htaccess_content .= "  </IfModule>\n";
        $htaccess_content .= "  <IfModule !mod_authz_core.c>\n";
        $htaccess_content .= "    Order deny,allow\n";
        $htaccess_content .= "    Deny from all\n";
        $htaccess_content .= "  </IfModule>\n";
        $htaccess_content .= "</Files>\n";
        
        file_put_contents($pdf_dir . '/.htaccess', $htaccess_content);
        
        // Create index.php to prevent directory listing
        file_put_contents($pdf_dir . '/index.php', '<?php // Silence is golden');
    }
    
    // Generate secure filename
    $filename = 'personalized_' . uniqid() . '.pdf';
    $pdf_path = $pdf_dir . '/' . $filename;
    
    // Save PDF
    $pdf->Output($pdf_path, 'F');
    
    // Return secure URL with authentication token
    $token = bin2hex(random_bytes(16));
    set_transient('pp_pdf_token_' . $token, $filename, DAY_IN_SECONDS);
    
    return add_query_arg([
### 8.2 External API Security

When integrating with external APIs:

```php
// Example: Secure external API communication
function call_external_api($endpoint, $data) {
    // Get API credentials
    $api_key = get_option('product_personalizer_api_key');
    $api_secret = get_option('product_personalizer_api_secret');
    
    if (!$api_key || !$api_secret) {
        return new WP_Error('missing_credentials', __('API credentials not configured.', 'product-personalizer'));
    }
    
    // Prepare request
    $url = 'https://api.example.com/' . $endpoint;
    $timestamp = time();
    $nonce = wp_generate_password(12, false);
    
    // Create signature
    $signature_data = $api_key . $timestamp . $nonce . json_encode($data);
    $signature = hash_hmac('sha256', $signature_data, $api_secret);
    
    // Make request
    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'X-API-Key' => $api_key,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature
        ],
        'body' => json_encode($data),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = wp_remote_retrieve_body($response);
    $code = wp_remote_retrieve_response_code($response);
    
    if ($code !== 200) {
        return new WP_Error('api_error', sprintf(__('API error: %s', 'product-personalizer'), $code));
    }
    
    return json_decode($body, true);
}
```

## 9. Security Testing & Monitoring

### 9.1 Security Testing Procedures

The plugin includes security testing procedures:

1. **Static Analysis:** Code is analyzed for security vulnerabilities
2. **Dynamic Testing:** Functionality is tested for security issues
3. **Penetration Testing:** Simulated attacks are performed to identify vulnerabilities

### 9.2 Security Monitoring

The plugin implements security monitoring:

```php
// Example: Security monitoring
function monitor_security_events() {
    // Check for suspicious activity
    $failed_logins = get_transient('pp_failed_logins');
    
    if ($failed_logins && $failed_logins > 5) {
        // Log potential brute force attack
        log_security_event('potential_brute_force', [
            'failed_logins' => $failed_logins,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        // Implement temporary IP ban
        set_transient('pp_ip_ban_' . $_SERVER['REMOTE_ADDR'], true, HOUR_IN_SECONDS);
    }
    
    // Check for banned IPs
    if (get_transient('pp_ip_ban_' . $_SERVER['REMOTE_ADDR'])) {
        wp_die(__('Access temporarily restricted due to suspicious activity.', 'product-personalizer'));
    }
}
```

## 10. Security Update Process

### 10.1 Vulnerability Management

The plugin implements a vulnerability management process:

1. **Monitoring:** Security advisories and vulnerability databases are monitored
2. **Assessment:** Vulnerabilities are assessed for impact and exploitability
3. **Remediation:** Security patches are developed and released
4. **Communication:** Users are notified of security updates

### 10.2 Security Update Deployment

Security updates are deployed through the WordPress plugin update system:

```php
// Example: Checking for security updates
function check_security_updates() {
    $current_version = PRODUCT_PERSONALIZER_VERSION;
    $api_url = 'https://api.example.com/product-personalizer/security-updates';
    
    $response = wp_remote_get($api_url);
    
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['security_version']) && version_compare($current_version, $data['security_version'], '<')) {
            // Add admin notice
            add_action('admin_notices', function() use ($data) {
                ?>
                <div class="notice notice-error">
                    <p>
                        <strong><?php _e('Security Update Available', 'product-personalizer'); ?></strong>
                        <?php printf(__('A security update (version %s) is available for Product Personalizer. Please update as soon as possible.', 'product-personalizer'), esc_html($data['security_version'])); ?>
                    </p>
                </div>
                <?php
            });
        }
    }
}
add_action('admin_init', 'check_security_updates');
```

## 11. Security Compliance

### 11.1 GDPR Compliance

The plugin implements GDPR compliance measures:

1. **Data Minimization:** Only necessary data is collected and stored
2. **Data Protection:** Personal data is protected through encryption and access controls
3. **Data Retention:** Data is only retained for as long as necessary
4. **Data Subject Rights:** Users can access, export, and delete their data

```php
// Example: GDPR data export
function export_personal_data($email_address, $page = 1) {
    $user = get_user_by('email', $email_address);
    
    if (!$user) {
        return [
            'data' => [],
            'done' => true
        ];
    }
    
    $user_id = $user->ID;
    $export_items = [];
    
    // Get personalization data for user's orders
    global $wpdb;
    $order_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_customer_user' AND meta_value = %d",
        $user_id
    ));
    
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            continue;
        }
        
        foreach ($order->get_items() as $item_id => $item) {
            $personalization_data = wc_get_order_item_meta($item_id, '_personalization_data', true);
            
            if (!empty($personalization_data)) {
                $export_items[] = [
                    'group_id' => 'product_personalizer',
                    'group_label' => __('Product Personalizations', 'product-personalizer'),
                    'item_id' => 'order-' . $order_id . '-item-' . $item_id,
                    'data' => [
                        [
                            'name' => __('Order ID', 'product-personalizer'),
                            'value' => $order_id
                        ],
                        [
                            'name' => __('Product', 'product-personalizer'),
                            'value' => $item->get_name()
                        ],
                        [
                            'name' => __('Personalization Data', 'product-personalizer'),
                            'value' => json_encode($personalization_data)
                        ]
                    ]
                ];
            }
        }
    }
    
    return [
        'data' => $export_items,
        'done' => true
    ];
}

// Example: GDPR data erasure
function erase_personal_data($email_address, $page = 1) {
    $user = get_user_by('email', $email_address);
    
    if (!$user) {
        return [
            'items_removed' => false,
            'items_retained' => false,
            'messages' => [],
            'done' => true
        ];
    }
    
    $user_id = $user->ID;
    $items_removed = false;
    $messages = [];
    
    // Get personalization data for user's orders
    global $wpdb;
    $order_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_customer_user' AND meta_value = %d",
        $user_id
    ));
    
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            continue;
        }
        
        foreach ($order->get_items() as $item_id => $item) {
            $personalization_data = wc_get_order_item_meta($item_id, '_personalization_data', true);
            
            if (!empty($personalization_data)) {
                // Anonymize personalization data
                wc_update_order_item_meta($item_id, '_personalization_data', 'Removed per user request');
                
                // Delete any preview images
                $preview_url = wc_get_order_item_meta($item_id, '_personalization_preview_url', true);
                if (!empty($preview_url)) {
                    $preview_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $preview_url);
                    if (file_exists($preview_path)) {
                        unlink($preview_path);
                    }
                    wc_update_order_item_meta($item_id, '_personalization_preview_url', '');
                }
                
                // Delete any print files
                $print_file_url = wc_get_order_item_meta($item_id, '_personalization_print_file_url', true);
                if (!empty($print_file_url)) {
                    $print_file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $print_file_url);
                    if (file_exists($print_file_path)) {
                        unlink($print_file_path);
                    }
                    wc_update_order_item_meta($item_id, '_personalization_print_file_url', '');
                }
                
                $items_removed = true;
                $messages[] = sprintf(__('Removed personalization data for order #%d', 'product-personalizer'), $order_id);
            }
        }
    }
    
    return [
        'items_removed' => $items_removed,
        'items_retained' => false,
        'messages' => $messages,
        'done' => true
    ];
}
```

### 11.2 PCI Compliance

For plugins that handle payment information, PCI compliance measures are implemented:

1. **No Storage of Card Data:** The plugin never stores credit card data
2. **Secure Transmission:** All payment data is transmitted securely
3. **Vendor Compliance:** Only PCI-compliant payment processors are used

## 12. Security Documentation

### 12.1 Security Policy

The plugin includes a security policy document that outlines:

1. **Security Practices:** The security measures implemented in the plugin
2. **Vulnerability Reporting:** How to report security vulnerabilities
3. **Security Updates:** How security updates are handled
4. **Data Protection:** How user data is protected

### 12.2 Security Changelog

The plugin maintains a security changelog that documents:

1. **Security Fixes:** Security issues that have been fixed
2. **Vulnerability Details:** Details of vulnerabilities after they have been fixed
3. **Mitigation Measures:** Steps taken to mitigate security risks
        'action' => 'pp_download_pdf',
        'token' => $token
    ], admin_url('admin-ajax.php'));
}

// Example: Secure PDF download
function handle_pdf_download() {
    if (!isset($_GET['token'])) {
        wp_die(__('Invalid request.', 'product-personalizer'));
    }
    
    $token = sanitize_key($_GET['token']);
    $filename = get_transient('pp_pdf_token_' . $token);
    
    if (!$filename) {
        wp_die(__('Invalid or expired token.', 'product-personalizer'));
    }
    
    $upload_dir = wp_upload_dir();
    $pdf_path = $upload_dir['basedir'] . '/product-personalizer/pdfs/' . $filename;
    
    if (!file_exists($pdf_path)) {
        wp_die(__('File not found.', 'product-personalizer'));
    }
    
    // Delete token after use
    delete_transient('pp_pdf_token_' . $token);
    
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($pdf_path));
    readfile($pdf_path);
    exit;
}
```
}

// Example: Verifying a nonce in AJAX
function handle_ajax_request() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'product_personalizer_ajax')) {
        wp_send_json_error(['message' => __('Security check failed.', 'product-personalizer')]);
    }
    // Process AJAX request...
}