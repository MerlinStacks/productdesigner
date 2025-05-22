<?php
/**
 * CustomKings Plugin Environment-Specific Configuration.
 *
 * This file allows overriding default settings based on the detected environment.
 * It is typically used for development or staging environments to enable debug
 * modes, set different API keys, or adjust other settings without affecting
 * production.
 *
 * This file should NOT be committed to production environments or public repositories.
 *
 * @package CustomKings
 * @subpackage Config
 */

defined( 'ABSPATH' ) || exit;

return [
    'general' => [
        'debug_mode' => true,
        'log_level'  => 'debug', // 'info', 'warning', 'error'
    ],
    'api'     => [
        'base_url' => 'https://dev.api.customkings.com',
        'key'      => getenv('CKPP_API_KEY') ?: (defined('CKPP_API_KEY') ? CKPP_API_KEY : ''), // Load from environment variable or wp-config.php
    ],
    // Add other environment-specific settings here.
];