<?php
/**
 * Database optimization class for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CKPP_DB_Optimizer {
    /**
     * Manages database optimization and cleanup tasks for the CustomKings Product Personalizer plugin.
     *
     * This class provides functionalities to optimize WordPress and WooCommerce database tables,
     * clean up expired transients, and schedule these operations for regular maintenance.
     * It aims to improve database performance and reduce storage overhead.
     *
     * @since 1.0.0
     * @version 1.0.0
     * @access public
     */
    /**
     * Optimizes selected database tables and cleans up expired transients.
     *
     * This method identifies WordPress core and relevant WooCommerce tables,
     * attempts to acquire a lock before optimizing each table, and releases the lock afterwards.
     * It also initiates a cleanup of expired transients.
     *
     * Note: The method name `get_tables_to_optimize` is somewhat misleading as it also
     * performs the optimization. Consider renaming to `optimize_tables` in a future refactor.
     *
     * @return array An associative array containing optimization results, including:
     *               - 'tables': An array of results for each optimized table (success, time, message).
     *               - 'errors': An array of error messages encountered during optimization.
     *               - 'transients_cleaned': Results from the `cleanup_transients` method.
     * @since 1.0.0
     * @access public
     */
    public static function get_tables_to_optimize(): array {
        global $wpdb;
        
        CKPP_Error_Handler::log_security_event('Database Optimization Started', [
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);

        $results = [
            'tables' => [],
            'errors' => [],
            'transients_cleaned' => []
        ];

        // Get all actual tables in the database for validation
        $actual_db_tables = $wpdb->get_col("SHOW TABLES");

        // Get configured tables or use defaults
        $configured_tables = CKPP_Config::get('general.optimize_tables', []);
        
        if (is_string($configured_tables) && !empty($configured_tables)) {
            $configured_tables = array_map('trim', explode(',', $configured_tables));
        } elseif (!is_array($configured_tables)) {
            $configured_tables = [];
        }

        if (empty($configured_tables)) {
            $configured_tables = [
                $wpdb->posts,
                $wpdb->postmeta,
                $wpdb->options,
                $wpdb->terms,
                $wpdb->term_taxonomy,
                $wpdb->term_relationships,
                $wpdb->termmeta
            ];
        }
        
        // Filter configured tables against actual database tables
        $tables = array_filter($configured_tables, function($table_name) use ($actual_db_tables) {
            return in_array($table_name, $actual_db_tables);
        });
        
        // Add WooCommerce tables if they exist
        $woocommerce_tables = [
            'woocommerce_attribute_taxonomies',
            'woocommerce_downloadable_product_permissions',
            'woocommerce_order_items',
            'woocommerce_order_itemmeta',
            'woocommerce_tax_rates',
            'woocommerce_tax_rate_locations',
            'woocommerce_shipping_zones',
            'woocommerce_shipping_zone_locations',
            'woocommerce_shipping_zone_methods',
            'woocommerce_payment_tokens',
            'woocommerce_payment_tokenmeta',
            'woocommerce_log'
        ];
        
        foreach ($woocommerce_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            // Validate table name against actual database tables
            if (in_array($table_name, $actual_db_tables)) {
                $tables[] = $table_name;
            }
        }
        
        // Remove duplicates
        $tables = array_unique($tables);
        
        // Check each table before optimizing
        foreach ($tables as $table) {
            $start_time = microtime(true);
            
            // Check if table is locked
            $locked = $wpdb->get_var($wpdb->prepare("SELECT IS_USED_LOCK(CONCAT('ckpp_lock_', %s))", $table));
            if ($locked) {
                $results['tables'][$table] = [
                    'success' => false,
                    'time' => 0,
                    'message' => 'Table is currently locked'
                ];
                CKPP_Error_Handler::log_security_event('Database Optimization Error: Table Locked', ['table' => $table]);
                $results['errors'][] = "Table $table is locked";
                continue;
            }
            
            // Get table status
            $status = $wpdb->get_row($wpdb->prepare("SHOW TABLE STATUS LIKE %s", $table));
            if (!$status) {
                $results['tables'][$table] = [
                    'success' => false,
                    'time' => 0,
                    'message' => 'Table not found'
                ];
                CKPP_Error_Handler::log_security_event('Database Optimization Error: Table Not Found', ['table' => $table]);
                $results['errors'][] = "Table $table not found";
                continue;
            }
            
            // Skip if table is already optimized
            if ($status->Data_free == 0) {
                $results['tables'][$table] = [
                    'success' => true,
                    'time' => 0,
                    'message' => 'Table already optimized'
                ];
                CKPP_Error_Handler::log_security_event('Database Optimization: Table Already Optimized', ['table' => $table]);
                continue;
            }
            
            // Acquire lock
            $lock_name = "ckpp_lock_" . $table;
            $got_lock = $wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s, 0)", $lock_name));
            if (!$got_lock) {
                $results['tables'][$table] = [
                    'success' => false,
                    'time' => 0,
                    'message' => 'Could not acquire lock'
                ];
                CKPP_Error_Handler::log_security_event('Database Optimization Error: Could Not Acquire Lock', ['table' => $table]);
                $results['errors'][] = "Could not acquire lock for $table";
                continue;
            }
            
            try {
                // Optimize table
                // OPTIMIZE TABLE cannot use $wpdb->prepare for table names directly.
                // $table is already validated against actual_db_tables.
                $result = $wpdb->query("OPTIMIZE TABLE `" . esc_sql($table) . "`");
                $end_time = microtime(true);
                
                $success = $result !== false;
                $message = $success ? 'Optimized successfully' : $wpdb->last_error;

                $results['tables'][$table] = [
                    'success' => $success,
                    'time' => round(($end_time - $start_time) * 1000, 2) . 'ms',
                    'message' => $message
                ];
                
                if ($success) {
                    CKPP_Error_Handler::log_security_event('Database Optimization Success', ['table' => $table, 'time' => $results['tables'][$table]['time']]);
                } else {
                    CKPP_Error_Handler::log_security_event('Database Optimization Failed', ['table' => $table, 'error' => $wpdb->last_error]);
                    $results['errors'][] = "Failed to optimize $table: " . $wpdb->last_error;
                }
            } finally {
                // Release lock
                $wpdb->query($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock_name));
            }
        }
        
        // Clean up transients
        $results['transients_cleaned'] = self::cleanup_transients();
        
        // Update last run time
        update_option('ckpp_last_optimization', current_time('mysql'));

        CKPP_Error_Handler::log_security_event('Database Optimization Completed', [
            'total_tables_optimized' => count($results['tables']),
            'total_errors' => count($results['errors']),
            'transients_cleaned' => $results['transients_cleaned']['transients_deleted'] ?? 0,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        
        return $results;
    }
    
    /**
     * Cleans up expired transients from the WordPress database in batches.
     *
     * This method queries for expired transients and their corresponding timeout options,
     * then deletes them in batches to prevent memory exhaustion on large databases.
     * It also includes a cleanup for orphaned transient timeouts and plugin-specific transients.
     *
     * @param int $batch_size The number of transients to process and delete in each batch. Defaults to 1000.
     * @return array An associative array containing the results of the cleanup, including:
     *               - 'transients_deleted': Total number of transient entries deleted.
     *               - 'transient_timeouts_deleted': Total number of transient timeout entries deleted.
     *               - 'batches_processed': The number of batches processed.
     *               - 'total_time': The total time taken for the cleanup operation in milliseconds.
     * @since 1.0.0
     * @access public
     */
    public static function cleanup_transients(int $batch_size = 1000): array {
        global $wpdb;
        
        $results = [
            'transients_deleted' => 0,
            'transient_timeouts_deleted' => 0,
            'batches_processed' => 0,
            'total_time' => 0
        ];
        
        $start_time = microtime(true);
        
        do {
            // Get batch of expired transients
            $expired = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT a.option_name AS transient, b.option_name AS timeout
                    FROM $wpdb->options a
                    INNER JOIN $wpdb->options b ON b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
                    WHERE a.option_name LIKE %s
                    AND a.option_name NOT LIKE %s
                    AND b.option_value < %d
                    LIMIT %d",
                    '_transient_%',
                    '_transient_timeout_%',
                    time(),
                    $batch_size
                )
            );
            
            if (empty($expired)) {
                break;
            }
            
            // Build deletion query for this batch
            $transients = array_map(function($row) {
                return $row->transient;
            }, $expired);
            
            $timeouts = array_map(function($row) {
                return $row->timeout;
            }, $expired);
            
            // Delete batch
            if (!empty($transients)) {
                $placeholders = implode(', ', array_fill(0, count($transients) + count($timeouts), '%s'));
                $deleted = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $wpdb->options WHERE option_name IN ($placeholders)",
                        array_merge($transients, $timeouts)
                    )
                );
                
                $results['transients_deleted'] += count($transients);
                $results['transient_timeouts_deleted'] += count($timeouts);
            }
            
            $results['batches_processed']++;
            
            // Allow other processes to run
            if (function_exists('sleep')) {
                sleep(1);
            }
            
        } while (!empty($expired));
        
        // Delete orphaned transient timeouts
        $results['transient_timeouts_deleted'] = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s AND option_value < %d",
                '\_transient\_timeout\_%',
                time()
            )
        );
        
        // Clean up plugin-specific transients
        $results['plugin_transients_cleaned'] = $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options 
            WHERE option_name LIKE %s 
            OR option_name LIKE %s",
            '_transient_ckpp_%',
            '_transient_timeout_ckpp_%'
        ));
        
        $results['total_time'] = round((microtime(true) - $start_time) * 1000, 2) . 'ms';
        
        return $results;
    }
    
    /**
     * Retrieves detailed database size information, including overall database size
     * and individual table sizes with data length, index length, total size, rows, and engine.
     *
     * @return array An associative array containing database size information:
     *               - 'database': Total size of the database in human-readable format.
     *               - 'tables': An associative array where keys are table names and values are arrays
     *                           with 'data', 'index', 'total' (all in human-readable format), 'rows', and 'engine'.
     * @since 1.0.0
     * @access public
     */
    public static function get_database_size(): array {
        global $wpdb;
        
        $sizes = [
            'database' => 0,
            'tables' => []
        ];
        
        // Get all tables
        $tables = $wpdb->get_results("SHOW TABLE STATUS", ARRAY_A);
        
        foreach ($tables as $table) {
            $table_size = $table['Data_length'] + $table['Index_length'];
            $sizes['tables'][$table['Name']] = [
                'data' => self::format_size($table['Data_length']),
                'index' => self::format_size($table['Index_length']),
                'total' => self::format_size($table_size),
                'rows' => $table['Rows'],
                'engine' => $table['Engine']
            ];
            $sizes['database'] += $table_size;
        }
        
        $sizes['database'] = self::format_size($sizes['database']);
        
        return $sizes;
    }
    
    /**
     * Formats a size in bytes into a human-readable string (e.g., "1.23 MB").
     *
     * This helper method converts a raw byte count into a more understandable format
     * using standard units like KB, MB, GB, and TB.
     *
     * @param int $bytes The size in bytes to format.
     * @return string The human-readable formatted size string.
     * @since 1.0.0
     * @access private
     */
    private static function format_size(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Schedules the database optimization event based on the configured frequency and time.
     *
     * This method clears any existing scheduled optimization events and then schedules
     * a new one using WordPress's cron system (`wp_schedule_event`). The frequency
     * and time are retrieved from the plugin's configuration.
     *
     * @return void
     * @since 1.0.0
     * @access public
     */
    public static function schedule_optimization(): void {
        $frequency = CKPP_Config::get('general.optimization_frequency', 'weekly');
        $time = CKPP_Config::get('general.optimization_time', '02:00');
        
        // Clear existing schedule
        self::clear_schedule();
        
        if ($frequency === 'disabled') {
            return;
        }
        
        // Parse configured time
        list($hour, $minute) = array_pad(explode(':', $time), 2, '00');
        
        // Calculate next run time based on frequency
        switch ($frequency) {
            case 'daily':
                $next_run = strtotime("tomorrow {$hour}:{$minute}");
                break;
            case 'weekly':
                $next_run = strtotime("next Sunday {$hour}:{$minute}");
                break;
            case 'monthly':
                $next_run = strtotime("first day of next month {$hour}:{$minute}");
                break;
            default:
                $next_run = strtotime("next Sunday {$hour}:{$minute}");
        }
        
        // Schedule the event
        wp_schedule_event(
            $next_run,
            $frequency,
            'ckpp_db_optimization'
        );
        
        // Store last scheduled time
        update_option('ckpp_last_schedule_update', current_time('mysql'));
    }
    
    /**
     * Clears any existing scheduled database optimization events.
     *
     * This method is typically called before scheduling a new event or during plugin deactivation
     * to ensure that no duplicate or unwanted cron jobs are running.
     *
     * @return void
     * @since 1.0.0
     * @access public
     */
    public static function clear_schedule(): void {
        $timestamp = wp_next_scheduled('ckpp_weekly_optimization');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ckpp_weekly_optimization');
        }
    }
}

// Hooks the database optimization function to the custom cron event.
add_action('ckpp_db_optimization', ['CKPP_DB_Optimizer', 'get_tables_to_optimize']); // Note: 'optimize_tables' is the actual function name, but the action calls 'get_tables_to_optimize' which performs the optimization.

// Cleans up the scheduled cron event when the plugin is deactivated.
register_deactivation_hook(__FILE__, ['CKPP_DB_Optimizer', 'clear_schedule']);

// Adds a manual optimization endpoint for administrators.
add_action('admin_post_ckpp_optimize_db', function(): void {
    /**
     * Handles the manual database optimization request from the WordPress admin.
     *
     * This function performs capability checks, verifies the admin nonce,
     * triggers the database optimization, and then redirects the user back
     * to the settings page with optimization results.
     *
     * @throws \CKPP_Error_Handler If the user does not have sufficient permissions.
     * @since 1.0.0
     * @access public
     */
    if (!current_user_can('manage_options')) {
        CKPP_Error_Handler::handle_admin_error( __( 'You do not have sufficient permissions to access this page.', 'customkings' ) );
    }
    
    check_admin_referer('ckpp_optimize_db');

    CKPP_Error_Handler::log_security_event('Manual Database Optimization Triggered', [
        'user_id' => get_current_user_id(),
        'ip_address' => $_SERVER['REMOTE_ADDR']
    ]);
    
    $results = CKPP_DB_Optimizer::get_tables_to_optimize(); // Calls the method that performs optimization
    
    // Redirect back with results
    $redirect = add_query_arg([
        'page' => 'ckpp_admin',
        'tab' => 'settings',
        'optimization_complete' => 1,
        'success' => empty($results['errors']) ? 1 : 0, // Check if there are any errors
        'errors' => count($results['errors'])
    ], admin_url('admin.php'));
    
    wp_redirect($redirect);
    exit;
});
