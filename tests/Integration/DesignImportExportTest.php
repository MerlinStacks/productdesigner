<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Importer;
use CustomKings\CKPP_Design_Exporter;
use Brain\Monkey\Functions;

class DesignImportExportTest extends IntegrationTestCase
{
    protected $importer;
    protected $exporter;
    protected $test_data;
    protected $admin_user_id;
    protected $customer_user_id;
    protected $design_id;
    protected $test_assets_dir;
    protected $test_export_file;
    protected $test_import_file;
    protected $test_image_path;
    
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
        
        $this->importer = new CKPP_Design_Importer();
        $this->exporter = new CKPP_Design_Exporter();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create test directories
        $upload_dir = wp_upload_dir();
        $this->test_assets_dir = $upload_dir['basedir'] . '/ckpp-test-assets';
        wp_mkdir_p($this->test_assets_dir);
        
        // Create a test image
        $this->test_image_path = $this->test_assets_dir . '/test-image.jpg';
        $image = imagecreatetruecolor(200, 200);
        $bg_color = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $bg_color);
        imagejpeg($image, $this->test_image_path, 90);
        imagedestroy($image);
        
        // Create a test design
        $this->design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'publish',
            'post_title' => 'Test Design for Export',
            'post_content' => 'This is a test design for export',
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
                ]
            ],
            'settings' => [
                'width' => 800,
                'height' => 600,
                'backgroundColor' => '#ffffff',
                'bleed' => 10,
                'safeZone' => 20,
                'dpi' => 300
            ]
        ]);
        
        // Set up test export/import files
        $this->test_export_file = $this->test_assets_dir . '/test-export.ckpp';
        $this->test_import_file = $this->test_assets_dir . '/test-import.ckpp';
        
        // Create a test import file
        $import_data = [
            'version' => CUSTOM_KINGS_VERSION,
            'export_date' => current_time('mysql'),
            'site_url' => site_url(),
            'design' => [
                'post' => [
                    'post_title' => 'Test Import Design',
                    'post_content' => 'This is a test import design',
                    'post_status' => 'publish',
                    'post_type' => 'ckpp_design',
                    'post_author' => $this->admin_user_id,
                ],
                'meta' => [
                    '_design_data' => [
                        'elements' => [
                            [
                                'type' => 'text',
                                'content' => 'Imported Text',
                                'x' => 150,
                                'y' => 150,
                                'fontSize' => 20,
                                'color' => '#0000ff',
                                'fontFamily' => 'Arial',
                                'fontWeight' => 'normal',
                                'textAlign' => 'left',
                                'opacity' => 1,
                                'rotation' => 0,
                                'width' => 150,
                                'height' => 30
                            ]
                        ],
                        'settings' => [
                            'width' => 600,
                            'height' => 400,
                            'backgroundColor' => '#f5f5f5',
                            'bleed' => 5,
                            'safeZone' => 10,
                            'dpi' => 300
                        ]
                    ]
                ],
                'attachments' => []
            ]
        ];
        
        // Save import file
        file_put_contents(
            $this->test_import_file,
            json_encode($import_data, JSON_PRETTY_PRINT)
        );
        
        // Mock WordPress functions
        Functions\when('wp_upload_dir')
            ->justReturn([
                'basedir' => $upload_dir['basedir'],
                'baseurl' => $upload_dir['baseurl'],
                'path' => $upload_dir['path'],
                'url' => $upload_dir['url'],
                'subdir' => '',
                'error' => false
            ]);
            
        Functions\when('wp_mkdir_p')
            ->alias(function($path) {
                return wp_mkdir_p($path);
            });
            
        Functions\when('wp_generate_attachment_metadata')
            ->justReturn([
                'width' => 200,
                'height' => 200,
                'file' => 'test-image.jpg',
                'sizes' => []
            ]);
            
        Functions\when('wp_update_attachment_metadata')
            ->justReturn(true);
            
        Functions\when('wp_get_attachment_url')
            ->justReturn('http://example.com/test-image.jpg');
            
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
            
        Functions\when('wp_handle_upload')
            ->justReturn([
                'file' => $this->test_image_path,
                'url' => 'http://example.com/test-image.jpg',
                'type' => 'image/jpeg',
                'error' => false
            ]);
            
        Functions\when('wp_check_filetype')
            ->justReturn([
                'ext' => 'jpg',
                'type' => 'image/jpeg',
                'proper_filename' => 'test-image.jpg'
            ]);
            
        Functions\when('wp_generate_attachment_metadata')
            ->justReturn([
                'width' => 200,
                'height' => 200,
                'file' => 'test-image.jpg',
                'sizes' => []
            ]);
            
        Functions\when('wp_insert_attachment')
            ->justReturn(999);
            
        Functions\when('wp_generate_password')
            ->justReturn('test-password');
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up test files
        if (file_exists($this->test_export_file)) {
            unlink($this->test_export_file);
        }
        
        if (file_exists($this->test_import_file)) {
            unlink($this->test_import_file);
        }
        
        if (file_exists($this->test_image_path)) {
            unlink($this->test_image_path);
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
    
    public function test_export_design()
    {
        // Export the design
        $result = $this->exporter->export_design(
            $this->design_id,
            [
                'format' => 'ckpp',
                'include_assets' => true,
                'include_attachments' => true,
                'output' => 'file',
                'output_path' => $this->test_export_file
            ]
        );
        
        // Verify the export was successful
        $this->assertFileExists($this->test_export_file);
        
        // Verify the export file contains the expected data
        $export_data = json_decode(file_get_contents($this->test_export_file), true);
        $this->assertIsArray($export_data);
        $this->assertArrayHasKey('design', $export_data);
        $this->assertArrayHasKey('post', $export_data['design']);
        $this->assertArrayHasKey('meta', $export_data['design']);
        $this->assertArrayHasKey('_design_data', $export_data['design']['meta']);
        $this->assertEquals('Test Design for Export', $export_data['design']['post']['post_title']);
        $this->assertEquals($this->customer_user_id, $export_data['design']['post']['post_author']);
        $this->assertEquals(800, $export_data['design']['meta']['_design_data']['settings']['width']);
        $this->assertEquals(600, $export_data['design']['meta']['_design_data']['settings']['height']);
    }
    
    public function test_import_design()
    {
        // Import the design
        $result = $this->importer->import_design(
            $this->test_import_file,
            [
                'import_attachments' => true,
                'import_assets' => true,
                'post_status' => 'publish',
                'post_author' => $this->admin_user_id,
                'update_existing' => false
            ]
        );
        
        // Verify the import was successful
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
        $this->assertGreaterThan(0, $result['design_id']);
        
        // Verify the imported design
        $imported_design = get_post($result['design_id']);
        $this->assertEquals('Test Import Design', $imported_design->post_title);
        $this->assertEquals('This is a test import design', $imported_design->post_content);
        $this->assertEquals('publish', $imported_design->post_status);
        $this->assertEquals('ckpp_design', $imported_design->post_type);
        $this->assertEquals($this->admin_user_id, $imported_design->post_author);
        
        // Verify the imported design data
        $design_data = get_post_meta($result['design_id'], '_design_data', true);
        $this->assertIsArray($design_data);
        $this->assertArrayHasKey('elements', $design_data);
        $this->assertArrayHasKey('settings', $design_data);
        $this->assertCount(1, $design_data['elements']);
        $this->assertEquals('Imported Text', $design_data['elements'][0]['content']);
        $this->assertEquals(600, $design_data['settings']['width']);
        $this->assertEquals(400, $design_data['settings']['height']);
        $this->assertEquals('#f5f5f5', $design_data['settings']['backgroundColor']);
        
        // Clean up
        if (isset($result['design_id'])) {
            wp_delete_post($result['design_id'], true);
        }
    }
    
    public function test_export_multiple_designs()
    {
        // Create a second design
        $design_id_2 = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'publish',
            'post_title' => 'Test Design 2 for Export',
            'post_content' => 'This is a second test design for export',
            'post_author' => $this->customer_user_id,
        ]);
        
        // Add design data
        update_post_meta($design_id_2, '_design_data', [
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Second Design Text',
                    'x' => 50,
                    'y' => 50,
                    'fontSize' => 18,
                    'color' => '#00ff00',
                    'fontFamily' => 'Arial',
                    'fontWeight' => 'normal',
                    'textAlign' => 'left',
                    'opacity' => 1,
                    'rotation' => 0,
                    'width' => 150,
                    'height' => 30
                ]
            ],
            'settings' => [
                'width' => 400,
                'height' => 300,
                'backgroundColor' => '#ffffff',
                'bleed' => 5,
                'safeZone' => 10,
                'dpi' => 300
            ]
        ]);
        
        // Export multiple designs
        $result = $this->exporter->export_designs(
            [$this->design_id, $design_id_2],
            [
                'format' => 'ckpp',
                'include_assets' => true,
                'include_attachments' => true,
                'output' => 'file',
                'output_path' => $this->test_export_file,
                'zip' => true
            ]
        );
        
        // Verify the export was successful
        $this->assertFileExists($this->test_export_file);
        
        // Verify the export file is a valid ZIP file
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($this->test_export_file) === true);
        
        // Verify the ZIP contains the expected files
        $this->assertNotFalse($zip->locateName('designs.json'));
        $this->assertNotFalse($zip->locateName('assets/'));
        
        // Clean up
        $zip->close();
        wp_delete_post($design_id_2, true);
    }
    
    public function test_import_design_with_assets()
    {
        // Create a test import file with assets
        $import_data = [
            'version' => CUSTOM_KINGS_VERSION,
            'export_date' => current_time('mysql'),
            'site_url' => site_url(),
            'design' => [
                'post' => [
                    'post_title' => 'Test Import Design with Assets',
                    'post_content' => 'This is a test import design with assets',
                    'post_status' => 'publish',
                    'post_type' => 'ckpp_design',
                    'post_author' => $this->admin_user_id,
                ],
                'meta' => [
                    '_design_data' => [
                        'elements' => [
                            [
                                'type' => 'image',
                                'url' => 'assets/test-import-image.jpg',
                                'x' => 100,
                                'y' => 100,
                                'width' => 200,
                                'height' => 200,
                                'opacity' => 1,
                                'rotation' => 0,
                                'filters' => []
                            ]
                        ],
                        'settings' => [
                            'width' => 800,
                            'height' => 600,
                            'backgroundColor' => '#ffffff',
                            'bleed' => 10,
                            'safeZone' => 20,
                            'dpi' => 300
                        ]
                    ]
                ],
                'attachments' => [
                    [
                        'file' => 'assets/test-import-image.jpg',
                        'data' => base64_encode(file_get_contents($this->test_image_path)),
                        'type' => 'image/jpeg'
                    ]
                ]
            ]
        ];
        
        // Save import file
        $import_file = $this->test_assets_dir . '/test-import-with-assets.ckpp';
        file_put_contents(
            $import_file,
            json_encode($import_data, JSON_PRETTY_PRINT)
        );
        
        // Import the design with assets
        $result = $this->importer->import_design(
            $import_file,
            [
                'import_attachments' => true,
                'import_assets' => true,
                'post_status' => 'publish',
                'post_author' => $this->admin_user_id,
                'update_existing' => false
            ]
        );
        
        // Verify the import was successful
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
        $this->assertGreaterThan(0, $result['design_id']);
        
        // Verify the imported design
        $imported_design = get_post($result['design_id']);
        $this->assertEquals('Test Import Design with Assets', $imported_design->post_title);
        
        // Verify the imported design data
        $design_data = get_post_meta($result['design_id'], '_design_data', true);
        $this->assertIsArray($design_data);
        $this->assertCount(1, $design_data['elements']);
        $this->assertEquals('image', $design_data['elements'][0]['type']);
        
        // Verify the image was imported and the URL was updated
        $this->assertStringContainsString('test-import-image', $design_data['elements'][0]['url']);
        
        // Clean up
        if (isset($result['design_id'])) {
            wp_delete_post($result['design_id'], true);
        }
        if (file_exists($import_file)) {
            unlink($import_file);
        }
    }
}
