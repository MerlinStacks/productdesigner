<?php
/**
 * Schema definition and validation rules for CustomKings plugin settings.
 *
 * This file defines the expected structure, data types, and validation rules
 * for all plugin settings.
 *
 * @package CustomKings
 * @subpackage Config
 */

defined( 'ABSPATH' ) || exit;

return [
    'general' => [
        'debug_mode' => [
            'type' => 'boolean',
            'default' => false,
            'description' => 'Enables or disables debug mode for the plugin.',
        ],
        'enable_caching' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Enables or disables caching for plugin data.',
        ],
        'cache_duration' => [
            'type' => 'integer',
            'default' => 3600,
            'description' => 'Duration in seconds for which cached data remains valid.',
            'validation' => 'min:60|max:86400', // Example validation rule
        ],
    ],
    // Add more setting sections and their schemas here
];