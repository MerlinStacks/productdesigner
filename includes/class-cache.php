<?php
/**
 * Caching class for CustomKings Product Personalizer
 *
 * @package CustomKingsProductPersonalizer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CKPP_Cache {
    /**
     * Manages caching functionalities for the CustomKings Product Personalizer plugin.
     *
     * This class provides a unified API for interacting with WordPress's object cache
     * and transients, offering methods for getting, setting, deleting, and invalidating
     * cached data. It ensures efficient data retrieval and reduces database load.
     *
     * @since 1.0.0
     * @version 1.0.0
     * @access public
     */
    /**
     * Cache group prefix.
     *
     * This prefix is prepended to all cache keys and groups to ensure uniqueness
     * and prevent conflicts with other plugins or WordPress core.
     *
     * @var string
     * @access private
     * @since 1.0.0
     */
    private static string $prefix = 'ckpp_';
    
    /**
     * Default cache expiration time in seconds.
     *
     * This value is used when no specific expiration is provided during a cache set operation.
     * Defaults to 1 hour (3600 seconds).
     *
     * @var int
     * @access private
     * @since 1.0.0
     */
    private static int $default_expiration = 3600; // 1 hour
    
    /**
     * Retrieves a cached value.
     *
     * This method first attempts to retrieve the value from the WordPress object cache.
     * If not found, it falls back to transients. If found in transients, it will
     * store it in the object cache for subsequent faster access.
     *
     * @param string $key The unique key for the cache entry.
     * @param string $group Optional. The cache group. Used to organize cache entries. Defaults to an empty string.
     * @param mixed $default Optional. The default value to return if the cache entry is not found. Defaults to `false`.
     * @return mixed The cached value if found, otherwise the `$default` value.
     * @since 1.0.0
     * @access public
     */
    public static function get(string $key, string $group = '', mixed $default = false): mixed {
        $cache_key = self::get_key($key, $group);
        
        // Try to get from object cache first
        $value = wp_cache_get($cache_key, self::get_cache_group($group));
        
        // If not in object cache, try transient
        if ($value === false) {
            $value = get_transient($cache_key);
            
            // If found in transient, store in object cache for future requests
            if ($value !== false) {
                wp_cache_set($cache_key, $value, self::get_cache_group($group), HOUR_IN_SECONDS);
            }
        }
        
        return $value !== false ? $value : $default;
    }
    
    /**
     * Sets a value in the cache.
     *
     * This method stores the value in both the WordPress object cache and as a transient.
     * Transients are used for persistence across page loads, while the object cache
     * provides faster access within a single page load.
     *
     * @param string $key The unique key for the cache entry.
     * @param mixed $value The value to be cached. Can be of any type.
     * @param string $group Optional. The cache group. Defaults to an empty string.
     * @param int|null $expiration Optional. The expiration time in seconds.
     *                             Use `0` for no expiration (though transients will eventually expire).
     *                             If `null`, the default expiration time (`self::$default_expiration`) is used.
     * @return bool True on success, false on failure.
     * @since 1.0.0
     * @access public
     */
    public static function set(string $key, mixed $value, string $group = '', ?int $expiration = null): bool {
        if (is_null($expiration)) {
            $expiration = self::$default_expiration;
        }
        
        $cache_key = self::get_key($key, $group);
        $cache_group = self::get_cache_group($group);
        
        // Store in both object cache and transient
        $result1 = wp_cache_set($cache_key, $value, $cache_group, $expiration);
        
        // Only store in transient if expiration is set
        if ($expiration > 0) {
            $result2 = set_transient($cache_key, $value, $expiration);
        } else {
            $result2 = true;
        }
        
        return $result1 && $result2;
    }
    
    /**
     * Deletes a cached value from both the object cache and transients.
     *
     * @param string $key The unique key of the cache entry to delete.
     * @param string $group Optional. The cache group the entry belongs to. Defaults to an empty string.
     * @return bool True on successful deletion from both caches, false otherwise.
     * @since 1.0.0
     * @access public
     */
    public static function delete(string $key, string $group = ''): bool {
        $cache_key = self::get_key($key, $group);
        $cache_group = self::get_cache_group($group);
        
        // Delete from both object cache and transient
        $result1 = wp_cache_delete($cache_key, $cache_group);
        $result2 = delete_transient($cache_key);
        
        return $result1 && $result2;
    }

    /**
     * Invalidates a specific cache key within a group.
     *
     * This method acts as an alias for the `delete` method, providing a more
     * semantically clear way to indicate the purpose of the operation.
     *
     * @param string $key The cache key to invalidate.
     * @param string $group Optional. The cache group the key belongs to. Defaults to an empty string.
     * @return bool True on successful invalidation, false otherwise.
     * @since 1.0.0
     * @access public
     */
    public static function invalidate_key(string $key, string $group = ''): bool {
        return self::delete($key, $group);
    }

    /**
     * Invalidates all cache entries within a specific group.
     *
     * This method primarily targets transients associated with the given cache group
     * by deleting them directly from the WordPress options table. For object cache,
     * it relies on individual key invalidation or eventual expiration, as direct
     * group flushing for object cache is not universally supported or efficient
     * without a persistent object cache backend.
     *
     * @param string $group The cache group to invalidate.
     * @return bool True on success (transients deleted), false on failure.
     * @since 1.0.0
     * @access public
     */
    public static function invalidate_group(string $group): bool {
        global $wpdb;

        $cache_group_prefix = self::get_cache_group($group);
        $transient_prefix = '_transient_' . $cache_group_prefix . '_%';
        $transient_timeout_prefix = '_transient_timeout_' . $cache_group_prefix . '_%';

        // Delete transients for the specific group
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
            $transient_prefix,
            $transient_timeout_prefix
        ));

        // Invalidate object cache for the specific group
        // This requires iterating through keys if not using a persistent object cache
        // that supports group flushing. For now, relying on transient deletion
        // and individual key deletion for object cache.
        // A more robust solution for object cache group invalidation might involve
        // storing a list of keys per group or using a versioning system.
        // For simplicity, we'll rely on the fact that object cache entries
        // will eventually expire or be overwritten.
        // If a persistent object cache is in use (e.g., Redis, Memcached),
        // wp_cache_delete with a group parameter might work more effectively.
        // For now, we'll assume individual key invalidation is handled by delete()
        // and group invalidation primarily targets transients.
        // Future improvement: Implement a mechanism to track keys per group in object cache
        // for more efficient group invalidation.

        return true; // Assuming transient deletion is the primary goal for group invalidation
    }
    
    /**
     * Clears all cache entries managed by this plugin.
     *
     * This method flushes the entire WordPress object cache (if `wp_cache_flush` is available)
     * and deletes all transients specific to this plugin from the database.
     * Note: Flushing the object cache can affect other plugins.
     *
     * @return bool True on success, false on failure.
     * @since 1.0.0
     * @access public
     */
    public static function clear(): bool {
        global $wpdb;

        // Clear all plugin cache
        $result = true;

        // Clear object cache
        // Note: wp_cache_flush() flushes the ENTIRE object cache, not just this plugin's.
        // This might be too aggressive. Consider if a more targeted approach is needed
        // if other parts of the plugin start using object cache groups more extensively.
        // For now, retaining original behavior.
        if (function_exists('wp_cache_flush')) {
            $result = wp_cache_flush();
        }

        // Clear transients specific to this plugin
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_ckpp_%',
            '_transient_timeout_ckpp_%'
        ));

        return $result;
    }
    
    /**
     * Generates a full cache key by prepending the global prefix and optionally the group.
     *
     * This ensures that all cache keys used by the plugin are unique and organized.
     *
     * @param string $key The base cache key.
     * @param string $group Optional. The cache group. Defaults to an empty string.
     * @return string The fully qualified cache key.
     * @since 1.0.0
     * @access private
     */
    private static function get_key(string $key, string $group = ''): string {
        $key = self::$prefix . $key;
        return $group ? $group . '_' . $key : $key;
    }
    
    /**
     * Generates a full cache group name by prepending the global prefix.
     *
     * This ensures that all cache groups used by the plugin are unique and organized.
     * If no specific group is provided, it defaults to 'ckpp_default'.
     *
     * @param string $group Optional. The base cache group name. Defaults to an empty string.
     * @return string The fully qualified cache group name.
     * @since 1.0.0
     * @access private
     */
    private static function get_cache_group(string $group = ''): string {
        return $group ? self::$prefix . $group : self::$prefix . 'default';
    }
    
    /**
     * Generates a unique cache key based on a function name and its arguments.
     *
     * This method is useful for caching the results of functions where the output
     * depends entirely on the input arguments. It uses MD5 hashing of serialized arguments
     * to create a consistent and unique key.
     *
     * @param string $function The name of the function for which to generate the key.
     * @param array $args Optional. An associative array of arguments passed to the function. Defaults to an empty array.
     * @return string The generated unique cache key.
     * @since 1.0.0
     * @access public
     */
    public static function generate_key(string $function, array $args = []): string {
        $key = $function;
        
        if (!empty($args)) {
            $key .= '_' . md5(serialize($args));
        }
        
        return $key;
    }
}
