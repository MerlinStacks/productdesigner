<?php
/**
 * PHPUnit bootstrap file for CustomKings Product Personalizer
 */

// Ensure tests are run from the plugin root
$_tests_dir = getenv('WP_TESTS_DIR') ?: 'C:/Users/ratte/AppData/Local/Temp/wordpress-tests-lib';

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find WordPress test library in $_tests_dir.\n";
    exit(1);
}

// Load WordPress test functions
require_once $_tests_dir . '/includes/functions.php';

// Load the plugin
function _manually_load_plugin() {
    require dirname(__DIR__) . '/CustomKings.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php'; 