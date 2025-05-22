<?php

namespace CustomKings\Tests\Unit;

use CustomKings\Tests\TestCase\UnitTestCase;
use CustomKings\CKPP_Design_Preview_Generator;
use Brain\Monkey\Functions;
use Mockery;

class DesignPreviewGeneratorTest extends UnitTestCase
{
    protected $preview_generator;
    protected $test_image_path;
    protected $test_output_dir;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->preview_generator = new CKPP_Design_Preview_Generator();
        
        // Set up test directories
        $this->test_image_path = sys_get_temp_dir() . '/test-image.jpg';
        $this->test_output_dir = sys_get_temp_dir() . '/ckpp-previews/';
        
        // Create a test image
        $im = imagecreatetruecolor(800, 600);
        $bg_color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $bg_color);
        imagejpeg($im, $this->test_image_path, 90);
        imagedestroy($im);
        
        // Create output directory if it doesn't exist
        if (!file_exists($this->test_output_dir)) {
            mkdir($this->test_output_dir, 0755, true);
        }
        
        // Mock WordPress functions
        Functions\when('wp_upload_dir')
            ->justReturn([
                'basedir' => $this->test_output_dir,
                'baseurl' => 'http://example.com/wp-content/uploads/ckpp-previews',
                'subdir' => '/ckpp-previews',
                'path' => $this->test_output_dir,
                'url' => 'http://example.com/wp-content/uploads/ckpp-previews',
            ]);
            
        Functions\when('wp_mkdir_p')
            ->justReturn(true);
    }
    
    public function test_generate_preview()
    {
        // Skip if Imagick is not available
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick extension is not available');
            return;
        }
        
        $design_data = [
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Test Design',
                    'position' => ['x' => 100, 'y' => 100],
                    'styles' => [
                        'color' => '#000000',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                    ]
                ],
                [
                    'type' => 'image',
                    'url' => $this->test_image_path,
                    'position' => ['x' => 200, 'y' => 200],
                    'size' => ['width' => 100, 'height' => 100],
                ]
            ]
        ];
        
        // Call the method
        $result = $this->preview_generator->generate_preview($design_data, 800, 600);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('preview_url', $result['data']);
        $this->assertArrayHasKey('preview_path', $result['data']);
        
        // Verify the file was created
        $this->assertFileExists($result['data']['preview_path']);
        
        // Clean up
        if (file_exists($result['data']['preview_path'])) {
            unlink($result['data']['preview_path']);
        }
    }
    
    public function test_generate_preview_with_background()
    {
        // Skip if Imagick is not available
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick extension is not available');
            return;
        }
        
        $design_data = [
            'background' => [
                'type' => 'image',
                'url' => $this->test_image_path,
            ],
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Test Design',
                    'position' => ['x' => 100, 'y' => 100],
                    'styles' => [
                        'color' => '#000000',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                    ]
                ]
            ]
        ];
        
        // Call the method
        $result = $this->preview_generator->generate_preview($design_data, 800, 600);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        
        // Clean up
        if (isset($result['data']['preview_path']) && file_exists($result['data']['preview_path'])) {
            unlink($result['data']['preview_path']);
        }
    }
    
    public function test_generate_preview_with_invalid_data()
    {
        // Test with invalid design data
        $result = $this->preview_generator->generate_preview([], 800, 600);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }
    
    public function test_generate_thumbnail()
    {
        // Skip if Imagick is not available
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick extension is not available');
            return;
        }
        
        // First generate a preview
        $design_data = [
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Test Thumbnail',
                    'position' => ['x' => 100, 'y' => 100],
                    'styles' => [
                        'color' => '#000000',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                    ]
                ]
            ]
        ];
        
        $preview_result = $this->preview_generator->generate_preview($design_data, 800, 600);
        
        if (!$preview_result['success']) {
            $this->markTestSkipped('Failed to generate preview for thumbnail test');
            return;
        }
        
        // Generate thumbnail
        $result = $this->preview_generator->generate_thumbnail($preview_result['data']['preview_path'], 200, 150);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('thumbnail_url', $result['data']);
        $this->assertArrayHasKey('thumbnail_path', $result['data']);
        
        // Verify the file was created
        $this->assertFileExists($result['data']['thumbnail_path']);
        
        // Get image dimensions
        $image_info = getimagesize($result['data']['thumbnail_path']);
        $this->assertEquals(200, $image_info[0]);
        $this->assertEquals(150, $image_info[1]);
        
        // Clean up
        if (file_exists($preview_result['data']['preview_path'])) {
            unlink($preview_result['data']['preview_path']);
        }
        if (file_exists($result['data']['thumbnail_path'])) {
            unlink($result['data']['thumbnail_path']);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->test_image_path)) {
            unlink($this->test_image_path);
        }
        
        // Clean up output directory
        if (is_dir($this->test_output_dir)) {
            array_map('unlink', glob("$this->test_output_dir/*.*"));
            rmdir($this->test_output_dir);
        }
        
        parent::tearDown();
    }
}
