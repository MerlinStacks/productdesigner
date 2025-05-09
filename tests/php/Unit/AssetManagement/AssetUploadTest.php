<?php
// PHPUnit test for Asset Upload functionality

namespace Tests\Unit\AssetManagement;

use PHPUnit\Framework\TestCase;
use WP_Error; // Include WP_Error for type hinting and assertions


class AssetUploadTest extends TestCase
{
    public function test_valid_asset_upload()
    {
        // Mock WordPress functions
        WP_Mock::userFunction('current_user_can', [
            'return' => true,
        ]);
        WP_Mock::userFunction('wp_handle_upload', [
            'return' => [
                'file' => '/path/to/uploaded/file.jpg',
                'url' => 'http://example.com/wp-content/uploads/file.jpg',
                'type' => 'image/jpeg',
            ],
        ]);
        WP_Mock::userFunction('wp_check_filetype', [
            'return' => [
                'ext' => 'jpg',
                'type' => 'image/jpeg',
            ],
        ]);

        $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();

        $dummy_file = [
            'name' => 'test_image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/php12345',
            'size' => 123456,
            'error' => 0,
        ];

        $result = $asset_manager->uploadAsset($dummy_file, 'image');
        $this->assertIsArray($result);
        $this->assertEquals('image/jpeg', $result['type']);
        $this->assertEquals('http://example.com/wp-content/uploads/file.jpg', $result['url']);
    }

    public function test_upload_asset_with_invalid_file_type()
    {
        WP_Mock::userFunction('current_user_can', [
            'return' => true,
        ]);
        WP_Mock::userFunction('wp_check_filetype', [
            'return' => false,
        ]);

        $asset_manager = new \ProductPersonalizer\AssetManagement\AssetManager();

        $dummy_file = [
            'name' => 'malicious.exe',
            'type' => 'application/octet-stream',
            'tmp_name' => '/tmp/php67890',
            'size' => 654321,
            'error' => 0,
        ];

        $result = $asset_manager->uploadAsset($dummy_file, 'image');
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('upload_failed', $result->get_error_code());
    }
}