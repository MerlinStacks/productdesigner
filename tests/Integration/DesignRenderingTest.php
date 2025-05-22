<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Renderer;
use Brain\Monkey\Functions;

class DesignRenderingTest extends IntegrationTestCase
{
    protected $renderer;
    protected $test_data;
    protected $admin_user_id;
    protected $customer_user_id;
    protected $design_id;
    protected $test_image_path;
    protected $test_font_path;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->admin_user_id = $this->factory->user->create([
            'role' => 'administrator',
            'user_email' => 'admin@example.com',
            'display_name' => 'Admin User'
        ]);
        
        $this->customer_user_id = $this->factory->user->create([
            'role' => 'customer',
            'user_email' => 'customer@example.com',
            'display_name' => 'Test Customer'
        ]);
        
        // Set current user as admin
        wp_set_current_user($this->admin_user_id);
        
        $this->renderer = new CKPP_Design_Renderer();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create test directories
        $this->test_upload_dir = wp_upload_dir();
        $this->test_assets_dir = $this->test_upload_dir['basedir'] . '/ckpp-test-assets';
        wp_mkdir_p($this->test_assets_dir);
        
        // Create a test image
        $this->test_image_path = $this->test_assets_dir . '/test-image.jpg';
        $image = imagecreatetruecolor(200, 200);
        $bg_color = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $bg_color);
        imagejpeg($image, $this->test_image_path, 90);
        imagedestroy($image);
        
        // Create a test font file
        $this->test_font_path = $this->test_assets_dir . '/test-font.ttf';
        file_put_contents($this->test_font_path, 'dummy font data');
        
        // Create a test design
        $this->design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'publish',
            'post_title' => 'Test Design for Rendering',
            'post_content' => 'This is a test design for rendering',
            'post_author' => $this->customer_user_id,
        ]);
        
        // Add design data
        update_post_meta($this->design_id, '_design_data', [
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Test Text',
                    'x' => 100,
                    'y' => 100,
                    'fontSize' => 24,
                    'color' => '#ff0000',
                    'fontFamily' => 'Arial',
                    'fontWeight' => 'bold',
                    'textAlign' => 'center',
                    'opacity' => 1,
                    'rotation' => 0,
                    'width' => 200,
                    'height' => 50
                ],
                [
                    'type' => 'image',
                    'url' => $this->test_image_path,
                    'x' => 200,
                    'y' => 200,
                    'width' => 100,
                    'height' => 100,
                    'opacity' => 1,
                    'rotation' => 0,
                    'filters' => []
                ],
                [
                    'type' => 'shape',
                    'shape' => 'rectangle',
                    'x' => 300,
                    'y' => 300,
                    'width' => 100,
                    'height' => 50,
                    'fill' => '#0000ff',
                    'stroke' => '#000000',
                    'strokeWidth' => 2,
                    'opacity' => 1,
                    'rotation' => 0,
                    'rx' => 5,
                    'ry' => 5
                ],
                [
                    'type' => 'qr',
                    'content' => 'https://example.com',
                    'x' => 400,
                    'y' => 400,
                    'size' => 100,
                    'fgColor' => '#000000',
                    'bgColor' => '#ffffff',
                    'eccLevel' => 'H',
                    'opacity' => 1,
                    'rotation' => 0
                ]
            ],
            'settings' => [
                'width' => 800,
                'height' => 600,
                'backgroundColor' => '#ffffff',
                'bleed' => 10,
                'safeZone' => 20,
                'dpi' => 300,
                'unit' => 'px',
                'ruler' => true,
                'grid' => false,
                'snapToGrid' => false,
                'gridSize' => 10,
                'guides' => []
            ]
        ]);
        
        // Mock WordPress functions
        Functions\when('wp_upload_dir')
            ->justReturn([
                'basedir' => $this->test_upload_dir['basedir'],
                'baseurl' => $this->test_upload_dir['baseurl'],
                'path' => $this->test_upload_dir['path'],
                'url' => $this->test_upload_dir['url'],
                'subdir' => '',
                'error' => false
            ]);
            
        Functions\when('wp_mkdir_p')
            ->alias(function($path) {
                return wp_mkdir_p($path);
            });
            
        Functions\when('wp_generate_attachment_metadata')
            ->justReturn([
                'width' => 800,
                'height' => 600,
                'file' => 'test-render.jpg',
                'sizes' => []
            ]);
            
        Functions\when('wp_update_attachment_metadata')
            ->justReturn(true);
            
        Functions\when('wp_get_attachment_url')
            ->justReturn('http://example.com/test-render.jpg');
            
        Functions\when('get_attached_file')
            ->alias(function($attachment_id, $unfiltered = false) {
                return $this->test_image_path;
            });
            
        Functions\when('wp_get_attachment_metadata')
            ->justReturn([
                'width' => 200,
                'height' => 200,
                'file' => 'test-image.jpg',
                'sizes' => []
            ]);
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up test files
        if (file_exists($this->test_image_path)) {
            unlink($this->test_image_path);
        }
        
        if (file_exists($this->test_font_path)) {
            unlink($this->test_font_path);
        }
        
        // Clean up test directories
        if (is_dir($this->test_assets_dir)) {
            rmdir($this->test_assets_dir);
        }
        
        // Clean up test design
        if ($this->design_id) {
            wp_delete_post($this->design_id, true);
        }
        
        // Clean up users
        if (isset($this->admin_user_id)) {
            wp_delete_user($this->admin_user_id);
        }
        
        if (isset($this->customer_user_id)) {
            wp_delete_user($this->customer_user_id);
        }
        
        parent::tearDown();
    }
    
    public function test_render_design()
    {
        // Render the design
        $output_path = $this->test_assets_dir . '/test-render.jpg';
        $result = $this->renderer->render_design(
            $this->design_id,
            [
                'format' => 'jpg',
                'quality' => 90,
                'output' => 'file',
                'output_path' => $output_path,
                'scale' => 1,
                'background' => '#ffffff',
                'include_bleed' => true,
                'include_margin' => true,
                'include_crop_marks' => true
            ]
        );
        
        // Verify the render was successful
        $this->assertFileExists($output_path);
        
        // Verify the image dimensions
        $image_info = getimagesize($output_path);
        $this->assertNotFalse($image_info);
        $this->assertEquals(820, $image_info[0]); // 800 + 2*10 (bleed)
        $this->assertEquals(620, $image_info[1]); // 600 + 2*10 (bleed)
        
        // Clean up
        if (file_exists($output_path)) {
            unlink($output_path);
        }
    }
    
    public function test_render_element_text()
    {
        // Render a text element
        $element = [
            'type' => 'text',
            'content' => 'Test Text',
            'x' => 100,
            'y' => 100,
            'fontSize' => 24,
            'color' => '#ff0000',
            'fontFamily' => 'Arial',
            'fontWeight' => 'bold',
            'textAlign' => 'center',
            'opacity' => 1,
            'rotation' => 0,
            'width' => 200,
            'height' => 50,
            'lineHeight' => 1.2,
            'letterSpacing' => 0,
            'textDecoration' => 'none',
            'fontStyle' => 'normal',
            'textTransform' => 'none',
            'padding' => 0,
            'backgroundColor' => 'transparent',
            'borderRadius' => 0,
            'borderWidth' => 0,
            'borderColor' => 'transparent',
            'shadow' => null,
            'textShadow' => null
        ];
        
        $output_path = $this->test_assets_dir . '/test-render-text.jpg';
        $result = $this->renderer->render_element(
            $element,
            [
                'width' => 800,
                'height' => 600,
                'format' => 'jpg',
                'output' => 'file',
                'output_path' => $output_path,
                'background' => '#ffffff'
            ]
        );
        
        // Verify the render was successful
        $this->assertFileExists($output_path);
        
        // Clean up
        if (file_exists($output_path)) {
            unlink($output_path);
        }
    }
    
    public function test_render_element_image()
    {
        // Render an image element
        $element = [
            'type' => 'image',
            'url' => $this->test_image_path,
            'x' => 200,
            'y' => 200,
            'width' => 100,
            'height' => 100,
            'opacity' => 1,
            'rotation' => 0,
            'filters' => [],
            'borderRadius' => 0,
            'borderWidth' => 0,
            'borderColor' => 'transparent',
            'shadow' => null,
            'objectFit' => 'cover',
            'objectPosition' => 'center center',
            'flipX' => false,
            'flipY' => false,
            'crop' => null
        ];
        
        $output_path = $this->test_assets_dir . '/test-render-image.jpg';
        $result = $this->renderer->render_element(
            $element,
            [
                'width' => 800,
                'height' => 600,
                'format' => 'jpg',
                'output' => 'file',
                'output_path' => $output_path,
                'background' => '#ffffff'
            ]
        );
        
        // Verify the render was successful
        $this->assertFileExists($output_path);
        
        // Clean up
        if (file_exists($output_path)) {
            unlink($output_path);
        }
    }
    
    public function test_render_element_shape()
    {
        // Render a shape element
        $element = [
            'type' => 'shape',
            'shape' => 'rectangle',
            'x' => 300,
            'y' => 300,
            'width' => 100,
            'height' => 50,
            'fill' => '#0000ff',
            'stroke' => '#000000',
            'strokeWidth' => 2,
            'opacity' => 1,
            'rotation' => 0,
            'rx' => 5,
            'ry' => 5,
            'shadow' => null
        ];
        
        $output_path = $this->test_assets_dir . '/test-render-shape.jpg';
        $result = $this->renderer->render_element(
            $element,
            [
                'width' => 800,
                'height' => 600,
                'format' => 'jpg',
                'output' => 'file',
                'output_path' => $output_path,
                'background' => '#ffffff'
            ]
        );
        
        // Verify the render was successful
        $this->assertFileExists($output_path);
        
        // Clean up
        if (file_exists($output_path)) {
            unlink($output_path);
        }
    }
    
    public function test_render_element_qr()
    {
        // Render a QR code element
        $element = [
            'type' => 'qr',
            'content' => 'https://example.com',
            'x' => 400,
            'y' => 400,
            'size' => 100,
            'fgColor' => '#000000',
            'bgColor' => '#ffffff',
            'eccLevel' => 'H',
            'opacity' => 1,
            'rotation' => 0
        ];
        
        $output_path = $this->test_assets_dir . '/test-render-qr.jpg';
        $result = $this->renderer->render_element(
            $element,
            [
                'width' => 800,
                'height' => 600,
                'format' => 'jpg',
                'output' => 'file',
                'output_path' => $output_path,
                'background' => '#ffffff'
            ]
        );
        
        // Verify the render was successful
        $this->assertFileExists($output_path);
        
        // Clean up
        if (file_exists($output_path)) {
            unlink($output_path);
        }
    }
    
    public function test_render_thumbnail()
    {
        // Render a thumbnail
        $output_path = $this->test_assets_dir . '/test-thumbnail.jpg';
        $result = $this->renderer->render_thumbnail(
            $this->design_id,
            [
                'width' => 200,
                'height' => 150,
                'format' => 'jpg',
                'quality' => 80,
                'output' => 'file',
                'output_path' => $output_path,
                'background' => '#f5f5f5'
            ]
        );
        
        // Verify the render was successful
        $this->assertFileExists($output_path);
        
        // Verify the thumbnail dimensions
        $image_info = getimagesize($output_path);
        $this->assertNotFalse($image_info);
        $this->assertEquals(200, $image_info[0]);
        $this->assertEquals(150, $image_info[1]);
        
        // Clean up
        if (file_exists($output_path)) {
            unlink($output_path);
        }
    }
    
    public function test_render_preview()
    {
        // Render a preview
        $output_path = $this->test_assets_dir . '/test-preview.jpg';
        $result = $this->renderer->render_preview(
            $this->design_id,
            [
                'width' => 400,
                'height' => 300,
                'format' => 'jpg',
                'quality' => 85,
                'output' => 'file',
                'output_path' => $output_path,
                'background' => '#ffffff',
                'watermark' => [
                    'text' => 'PREVIEW',
                    'color' => '#cccccc',
                    'fontSize' => 48,
                    'opacity' => 0.3,
                    'rotation' => -45
                ]
            ]
        );
        
        // Verify the render was successful
        $this->assertFileExists($output_path);
        
        // Verify the preview dimensions
        $image_info = getimagesize($output_path);
        $this->assertNotFalse($image_info);
        $this->assertEquals(400, $image_info[0]);
        $this->assertEquals(300, $image_info[1]);
        
        // Clean up
        if (file_exists($output_path)) {
            unlink($output_path);
        }
    }
    
    public function test_export_design()
    {
        // Export the design to PDF
        $output_path = $this->test_assets_dir . '/test-export.pdf';
        $result = $this->renderer->export_design(
            $this->design_id,
            [
                'format' => 'pdf',
                'output' => 'file',
                'output_path' => $output_path,
                'title' => 'Test Design Export',
                'author' => 'Test User',
                'subject' => 'Test Design Export',
                'keywords' => 'test, design, export',
                'creator' => 'CustomKings Product Personalizer',
                'producer' => 'CustomKings',
                'bleed' => 10,
                'crop_marks' => true,
                'color_profile' => 'CMYK',
                'resolution' => 300
            ]
        );
        
        // Verify the export was successful
        $this->assertFileExists($output_path);
        
        // Clean up
        if (file_exists($output_path)) {
            unlink($output_path);
        }
    }
}
