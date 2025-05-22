<?php
/**
 * CustomKings Plugin Configuration Class.
 *
 * Manages plugin settings, providing a centralized access point for
 * default, environment-specific, and user-defined configurations.
 *
 * @package CustomKings
 * @subpackage Config
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CKPP_Config' ) ) {
    class CKPP_Config {

        /**
         * Option name for general plugin settings stored in the WordPress options table.
         *
         * @var string
         * @since 1.0.0
         * @access public
         */
        const OPTION_GENERAL = 'ckpp_general_settings';

        /**
         * Option name for environment-specific settings.
         *
         * This constant is used to identify the option where environment-specific
         * configurations might be stored or referenced, though typically these are
         * loaded from `config/environment.php`.
         *
         * @var string
         * @since 1.0.0
         * @access public
         */
        const OPTION_ENVIRONMENT = 'ckpp_environment_settings';

        /**
         * Cached settings array.
         *
         * Stores the merged configuration settings after they have been loaded
         * from defaults, environment files, and the database.
         *
         * @var array<string, mixed>|null
         * @since 1.0.0
         * @access private
         */
        private static ?array $settings = null;

        /**
         * Detected environment type.
         *
         * Stores the determined environment type (e.g., 'development', 'staging', 'production')
         * to avoid repeated detection.
         *
         * @var string|null
         * @since 1.0.0
         * @access private
         */
        private static ?string $environment_type = null;

        /**
         * Retrieves a configuration setting using a dot-separated key.
         *
         * Settings are loaded and merged in a specific order of precedence:
         * 1. Default settings (from `config/defaults.php`)
         * 2. Environment-specific settings (from `config/environment.php`, if applicable and not in production)
         * 3. Database-stored general settings (from WordPress options table)
         *
         * @param string $key     The dot-separated key of the setting to retrieve (e.g., 'general.debug_mode', 'api.endpoint').
         * @param mixed  $default The default value to return if the specified key is not found in the configuration.
         * @return mixed The setting value if found, otherwise the provided `$default` value.
         * @since 1.0.0
         * @access public
         */
        public static function get( string $key, mixed $default = null ): mixed {
            if ( is_null( self::$settings ) ) {
                self::load_settings();
            }

            $keys = explode( '.', $key );
            $value = self::$settings;

            foreach ( $keys as $segment ) {
                if ( is_array( $value ) && isset( $value[ $segment ] ) ) {
                    $value = $value[ $segment ];
                } else {
                    return $default;
                }
            }

            return $value;
        }

        /**
         * Detects and returns the current environment type for the WordPress installation.
         *
         * This method determines the environment type by prioritizing the `WP_ENVIRONMENT_TYPE`
         * constant. If not defined, it checks `WP_DEBUG` to infer a 'development' environment.
         * Otherwise, it defaults to 'production'. The detected environment type is cached
         * for subsequent calls.
         *
         * @return string The detected environment type (e.g., 'development', 'staging', 'production').
         * @since 1.0.0
         * @access public
         */
        public static function get_environment(): string {
            if ( ! is_null( self::$environment_type ) ) {
                return self::$environment_type;
            }

            if ( defined( 'WP_ENVIRONMENT_TYPE' ) && ! empty( WP_ENVIRONMENT_TYPE ) ) {
                self::$environment_type = WP_ENVIRONMENT_TYPE;
            } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                self::$environment_type = 'development';
            } else {
                self::$environment_type = 'production';
            }

            return self::$environment_type;
        }

        /**
         * Checks whether the plugin's debug mode is currently enabled.
         *
         * This method retrieves the 'general.debug_mode' setting from the configuration.
         *
         * @return bool True if debug mode is enabled, false otherwise.
         * @since 1.0.0
         * @access public
         */
        public static function is_debug_mode(): bool {
            return (bool) self::get( 'general.debug_mode', false );
        }

        /**
         * Clears the internally cached configuration settings and detected environment type.
         *
         * Calling this method forces the `get()` method to reload all settings
         * from their sources (defaults, environment files, and database) on its
         * next invocation. This is useful for applying dynamic changes to settings.
         *
         * @return void
         * @since 1.0.0
         * @access public
         */
        public static function clear_cache(): void {
            self::$settings = null;
            self::$environment_type = null; // Also clear environment type on cache clear.
        }

        /**
         * Loads and merges all configuration settings from various sources into a single cached array.
         *
         * This private method is responsible for compiling the final configuration by
         * merging default settings, environment-specific overrides, and user-defined
         * settings stored in the WordPress database. The merge order ensures proper
         * precedence.
         *
         * @return void
         * @since 1.0.0
         * @access private
         */
        private static function load_settings(): void {
            $defaults = require CUSTOMKINGS_PLUGIN_DIR . 'config/defaults.php';
            $environment_settings = [];

            // Load environment-specific settings only if not in production.
            if ( 'production' !== self::get_environment() && file_exists( CUSTOMKINGS_PLUGIN_DIR . 'config/environment.php' ) ) {
                $environment_settings = require CUSTOMKINGS_PLUGIN_DIR . 'config/environment.php';
            }

            // Get general settings from WordPress options, forcing a database read to bypass object cache.
            $db_general_settings = get_option( self::OPTION_GENERAL, [], true );

            // Merge settings with precedence: defaults < environment < database.
            self::$settings = array_replace_recursive( $defaults, $environment_settings, $db_general_settings );
        }
    }
}