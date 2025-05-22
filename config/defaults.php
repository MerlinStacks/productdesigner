<?php
/**
 * Default values for all CustomKings plugin settings.
 *
 * @package CustomKings
 * @subpackage Config
 */

defined( 'ABSPATH' ) || exit;

return [
    'general' => [
        'debug_mode' => false,
        'enable_caching' => true,
        'cache_duration' => 3600, // seconds
        'optimize_tables' => '', // Default to empty string for comma-separated table names
        'optimization_frequency' => 'weekly',
        'optimization_time' => '02:00',
        'accent_color' => '#0073aa',
    ],
    'environment' => [], // This will be populated by environment.php
];