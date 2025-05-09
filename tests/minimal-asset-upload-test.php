<?php
// Minimal test for Asset_Manager::uploadAsset

// Explicitly include the interface and class files
require_once __DIR__ . '/../includes/asset-management/AssetManagerInterface.php';
require_once __DIR__ . '/../includes/asset-management/class-asset-manager.php';

use ProductPersonalizer\AssetManagement\Asset_Manager;

// Mock necessary WordPress functions
if ( ! function_exists( 'wp_upload_dir' ) ) {
    function wp_upload_dir() {
        return [
            'path'    => sys_get_temp_dir() . '/uploads',
            'url'     => 'http://example.com/uploads',
            'basedir' => sys_get_temp_dir() . '/uploads',
            'baseurl' => 'http://example.com/uploads',
            'subdir'  => '',
            'error'   => false,
        ];
    }
}
 if ( ! function_exists( 'wp_mkdir_p' ) ) {
    function wp_mkdir_p( $path ) {
        return is_dir( $path ) || mkdir( $path, 0755, true );
    }
}
 if ( ! function_exists( 'sanitize_file_name' ) ) {
    function sanitize_file_name( $filename ) {
        return $filename; // Simplified for test
    }
}
 if ( ! function_exists( 'wp_unique_filename' ) ) {
    function wp_unique_filename( $dir, $filename ) {
        return $filename; // Simplified for test
    }
}
 if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) {
        return $text; // Simplified for test
    }
}
if ( ! function_exists( 'move_uploaded_file' ) ) {
     function move_uploaded_file( $filename, $destination ) {
         return rename( $filename, $destination ); // Use rename for mocking
     }
 }
 if ( ! function_exists( 'chmod' ) ) {
     function chmod( $filename, $permissions ) {
         return true; // Simplified for test
     }
 }
 if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof \WP_Error; // Use global namespace for WP_Error
    }
}
// Mock finfo_file
if ( ! function_exists( 'finfo_file' ) ) {
    function finfo_file( $finfo, $filename ) {
        return 'image/jpeg'; // Mock return type
    }
}
// Mock finfo_open
if ( ! function_exists( 'finfo_open' ) ) {
    function finfo_open( $flags = FILEINFO_NONE, $mime_encoding = null ) {
        return 'mock_finfo_resource'; // Mock return resource
    }
}
// Mock finfo_close
if ( ! function_exists( 'finfo_close' ) ) {
    function finfo_close( $finfo ) {
        return true; // Mock return
    }
}


// Create a dummy file for testing
$dummy_file = [
    'name'     => 'test_image.jpg',
    'type'     => 'image/jpeg',
    'tmp_name' => tempnam(sys_get_temp_dir(), 'pp_test_upload_'),
    'error'    => UPLOAD_ERR_OK,
    'size'     => 100, // bytes
];
file_put_contents($dummy_file['tmp_name'], 'dummy content');

// Instantiate and call the method
$asset_manager = new Asset_Manager();
$result = $asset_manager->uploadAsset($dummy_file, 'image');

// Perform basic assertions
if ( is_array( $result ) ) {
    echo "Test Passed: uploadAsset returned an array.\n";
    if ( isset( $result['url'] ) ) {
        echo "Test Passed: Result array has 'url' key.\n";
    } else {
        echo "Test Failed: Result array is missing 'url' key.\n";
    }
     if ( isset( $result['type'] ) ) {
        echo "Test Passed: Result array has 'type' key.\n";
    } else {
        echo "Test Failed: Result array is missing 'type' key.\n";
    }
     if ( isset( $result['type'] ) && $result['type'] === 'image' ) {
        echo "Test Passed: Result type is 'image'.\n";
    } else {
        echo "Test Failed: Result type is not 'image'.\n";
    }
     if ( isset( $result['url'] ) && strpos( $result['url'], 'http://example.com/uploads/product-personalizer/image/test_image.jpg' ) !== false ) {
         echo "Test Passed: Result URL contains expected path.\n";
     } else {
         echo "Test Failed: Result URL does not contain expected path.\n";
     }

} elseif ( is_wp_error( $result ) ) {
    echo "Test Failed: uploadAsset returned a WP_Error: " . $result->get_error_message() . "\n";
} else {
    echo "Test Failed: uploadAsset returned unexpected type.\n";
}

// Clean up the dummy file
unlink($dummy_file['tmp_name']);

// Note: Directory created by wp_mkdir_p mock is not cleaned up in this minimal test.

?>