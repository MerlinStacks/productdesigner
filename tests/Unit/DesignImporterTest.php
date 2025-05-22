<?php

namespace CustomKings\Tests\Unit;

use CustomKings\Tests\TestCase\UnitTestCase;
use CustomKings\CKPP_Design_Importer;
use Brain\Monkey\Functions;
use Mockery;
use WP_Error;
use ReflectionClass;
use RuntimeException;
use ZipArchive;

class DesignImporterTest extends UnitTestCase
{
    protected $importer;
    protected $user_id = 1;
    protected $test_file;
    protected $test_assets_dir;
    protected $mock_file_upload = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test assets directory
        $this->test_assets_dir = sys_get_temp_dir() . '/ckpp_test_assets';
        if (!file_exists($this->test_assets_dir)) {
            mkdir($this->test_assets_dir, 0755, true);
        }
        
        // Initialize importer with test settings
        $this->importer = new CKPP_Design_Importer([
            'max_file_size' => 10 * 1024 * 1024, // 10MB in bytes
            'allowed_mime_types' => [
                'application/json' => ['.json'],
                'application/zip' => ['.zip']
            ],
            'upload_dir' => $this->test_assets_dir,
            'temp_dir' => sys_get_temp_dir()
        ]);
        
        // Create a test design file
        $this->test_file = $this->test_assets_dir . '/test_design.json';
        $design_data = [
            'version' => '1.0.0',
            'name' => 'Imported Design',
            'title' => 'Test Design',
            'description' => 'This is an imported design for testing',
            'author' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'url' => 'https://example.com'
            ],
            'created' => current_time('mysql'),
            'modified' => current_time('mysql'),
            'elements' => [
                [
                    'id' => 'text_1',
                    'type' => 'text',
                    'content' => 'Test Design',
                    'x' => 100,
                    'y' => 100,
                    'width' => 200,
                    'height' => 50,
                    'rotation' => 0,
                    'opacity' => 1,
                    'zIndex' => 1,
                    'locked' => false,
                    'visible' => true,
                    'metadata' => [
                        'created' => current_time('mysql'),
                        'modified' => current_time('mysql')
                    ],
                    'styles' => [
                        'color' => '#000000',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                        'fontWeight' => 'normal',
                        'fontStyle' => 'normal',
                        'textAlign' => 'left',
                        'textDecoration' => 'none',
                        'lineHeight' => 1.2,
                        'letterSpacing' => 0
                    ]
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
                'guides' => [],
                'metadata' => [
                    'created' => current_time('mysql'),
                    'modified' => current_time('mysql'),
                    'version' => '1.0.0',
                ]
            ],
            'assets' => [
                'images' => [],
                'fonts' => [],
                'templates' => []
            ]
        ];
        
        file_put_contents($this->test_file, json_encode($design_data, JSON_PRETTY_PRINT));
        
        // Mock file upload data
        $this->mock_file_upload = [
            'name' => 'test_design.json',
            'type' => 'application/json',
            'tmp_name' => $this->test_file,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($this->test_file)
        ];
        
        // Mock WordPress functions
        $this->mockWordPressFunctions();
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up test files
        if (file_exists($this->test_file)) {
            unlink($this->test_file);
        }
        
        if (is_dir($this->test_assets_dir)) {
            array_map('unlink', glob($this->test_assets_dir . '/*'));
            rmdir($this->test_assets_dir);
        }
    }
    
    protected function mockWordPressFunctions()
    {
        // Mock file handling functions
        Functions\when('wp_upload_bits')
            ->justReturn([
                'file' => $this->test_file,
                'url' => 'http://example.com/uploads/test_design.json',
                'error' => false
            ]);
            
        Functions\when('wp_handle_upload')
            ->justReturn([
                'file' => $this->test_file,
                'url' => 'http://example.com/uploads/test_design.json',
                'type' => 'application/json',
                'error' => false
            ]);
            
        Functions\when('wp_mkdir_p')
            ->alias(function($path) {
                if (!file_exists($path)) {
                    return mkdir($path, 0755, true);
                }
                return true;
            });
            
        Functions\when('wp_generate_attachment_metadata')
            ->justReturn([
                'width' => 800,
                'height' => 600,
                'file' => 'test_design.json',
                'sizes' => []
            ]);
            
        Functions\when('wp_update_attachment_metadata')
            ->justReturn(true);
            
        Functions\when('wp_insert_attachment')
            ->justReturn(123);
            
        Functions\when('wp_generate_attachment_metadata')
            ->justReturn([]);
            
        Functions\when('wp_get_attachment_url')
            ->justReturn('http://example.com/uploads/test_design.json');
            
        Functions\when('get_attached_file')
            ->justReturn($this->test_file);
            
        Functions\when('wp_get_attachment_metadata')
            ->justReturn([
                'width' => 800,
                'height' => 600,
                'file' => 'test_design.json',
                'sizes' => []
            ]);
            
        // Mock post and user functions
        Functions\when('wp_insert_post')
            ->justReturn(1);
            
        Functions\when('wp_update_post')
            ->justReturn(1);
            
        Functions\when('get_current_user_id')
            ->justReturn($this->user_id);
            
        Functions\when('current_time')
            ->justReturn(current_time('mysql'));
            
        // Mock metadata functions
        Functions\when('add_post_meta')
            ->justReturn(true);
            
        Functions\when('update_post_meta')
            ->justReturn(true);
            
        Functions\when('get_post_meta')
            ->justReturn([]);
            
        // Mock file system functions
        Functions\when('wp_upload_dir')
            ->justReturn([
                'path' => $this->test_assets_dir,
                'url' => 'http://example.com/uploads',
                'subdir' => '',
                'basedir' => $this->test_assets_dir,
                'baseurl' => 'http://example.com/uploads',
                'error' => false
            ]);
            
        Functions\when('wp_check_filetype')
            ->justReturn([
                'ext' => 'json',
                'type' => 'application/json',
                'proper_filename' => 'test_design.json'
            ]);
            
        Functions\when('wp_check_filetype_and_ext')
            ->justReturn([
                'ext' => 'json',
                'type' => 'application/json',
                'proper_filename' => 'test_design.json'
            ]);
            
        Functions\when('wp_handle_sideload')
            ->justReturn([
                'file' => $this->test_file,
                'url' => 'http://example.com/uploads/test_design.json',
                'type' => 'application/json',
                'error' => false
            ]);
            
        // Mock WP_Filesystem
        $mock_filesystem = Mockery::mock('WP_Filesystem_Direct');
        $mock_filesystem->shouldReceive('exists')->andReturn(true);
        $mock_filesystem->shouldReceive('is_dir')->andReturn(true);
        $mock_filesystem->shouldReceive('mkdir')->andReturn(true);
        $mock_filesystem->shouldReceive('put_contents')->andReturn(true);
        $mock_filesystem->shouldReceive('get_contents')->andReturn(file_get_contents($this->test_file));
        $mock_filesystem->shouldReceive('delete')->andReturn(true);
        
        $GLOBALS['wp_filesystem'] = $mock_filesystem;
    }
    
    /**
     * Test importing a design from a file
     */
    public function test_import_design()
    {
        $result = $this->importer->import($this->test_file);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
    }
    
    /**
     * Test importing a design with invalid file path
     */
    public function test_import_invalid_file_path()
    {
        $result = $this->importer->import('/path/to/nonexistent/file.json');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }
    
    /**
     * Test importing a design with invalid JSON
     */
    public function test_import_invalid_json()
    {
        $invalid_json_file = $this->test_assets_dir . '/invalid.json';
        file_put_contents($invalid_json_file, 'invalid json');
        
        $result = $this->importer->import($invalid_json_file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        
        unlink($invalid_json_file);
    }
    
    /**
     * Test importing a design with missing required fields
     */
    public function test_import_missing_required_fields()
    {
        $invalid_design = [
            'name' => 'Invalid Design',
            // Missing 'elements' and 'settings' which are required
        ];
        
        $invalid_file = $this->test_assets_dir . '/invalid_design.json';
        file_put_contents($invalid_file, json_encode($invalid_design));
        
        $result = $this->importer->import($invalid_file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        
        unlink($invalid_file);
    }
    
    /**
     * Test importing a design with assets
     */
    public function test_import_with_assets()
    {
        // Create a test asset file
        $asset_file = $this->test_assets_dir . '/test_asset.png';
        file_put_contents($asset_file, 'test image content');
        
        // Create a design with asset references
        $design_with_assets = json_decode(file_get_contents($this->test_file), true);
        $design_with_assets['assets'] = [
            'images' => [
                'test_asset' => [
                    'url' => 'http://example.com/test_asset.png',
                    'path' => 'images/test_asset.png',
                    'type' => 'image/png',
                    'name' => 'Test Asset',
                    'size' => 1234
                ]
            ]
        ];
        
        $design_with_assets_file = $this->test_assets_dir . '/design_with_assets.json';
        file_put_contents($design_with_assets_file, json_encode($design_with_assets));
        
        // Mock the download_url function
        Functions\when('download_url')
            ->justReturn($asset_file);
            
        $result = $this->importer->import($design_with_assets_file, [
            'download_assets' => true,
            'asset_base_url' => 'http://example.com/'
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertIsArray($result['assets']);
        $this->assertArrayHasKey('downloaded', $result['assets']);
        $this->assertGreaterThan(0, $result['assets']['downloaded']);
        
        unlink($design_with_assets_file);
        unlink($asset_file);
    }
    
    /**
     * Test importing a design with a ZIP archive
     */
    public function test_import_zip_archive()
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('The ZipArchive class is not available');
        }
        
        // Create a test zip file
        $zip_file = $this->test_assets_dir . '/test_design.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zip_file, ZipArchive::CREATE) !== true) {
            $this->markTestSkipped('Could not create test zip file');
        }
        
        // Add the design file to the zip
        $zip->addFile($this->test_file, 'design.json');
        
        // Add a test asset
        $asset_content = 'test asset content';
        $zip->addFromString('assets/test_asset.txt', $asset_content);
        
        $zip->close();
        
        // Test importing the zip file
        $result = $this->importer->import($zip_file, [
            'extract_zip' => true
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
        $this->assertArrayHasKey('extracted_files', $result);
        $this->assertIsArray($result['extracted_files']);
        $this->assertNotEmpty($result['extracted_files']);
        
        // Clean up
        unlink($zip_file);
    }
    
    /**
     * Test importing a design with invalid ZIP archive
     */
    public function test_import_invalid_zip()
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('The ZipArchive class is not available');
        }
        
        // Create an invalid zip file
        $invalid_zip = $this->test_assets_dir . '/invalid.zip';
        file_put_contents($invalid_zip, 'not a zip file');
        
        $result = $this->importer->import($invalid_zip, [
            'extract_zip' => true
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        
        unlink($invalid_zip);
    }
    
    /**
     * Test importing a design with custom post type mapping
     */
    public function test_import_with_custom_post_type()
    {
        $custom_post_type = 'custom_design';
        
        // Mock the register_post_type function
        Functions\when('register_post_type')
            ->justReturn(new \stdClass());
            
        // Register the custom post type
        register_post_type($custom_post_type, [
            'public' => true,
            'label' => 'Custom Design',
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields']
        ]);
        
        $result = $this->importer->import($this->test_file, [
            'post_type' => $custom_post_type
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
        
        // Clean up
        unregister_post_type($custom_post_type);
    }
    
    /**
     * Test importing a design with custom meta fields
     */
    public function test_import_with_custom_meta()
    {
        $custom_meta = [
            '_custom_field_1' => 'Custom Value 1',
            '_custom_field_2' => 'Custom Value 2'
        ];
        
        $result = $this->importer->import($this->test_file, [
            'meta' => $custom_meta
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
    }
    
    /**
     * Test importing a design with a custom author
     */
    public function test_import_with_custom_author()
    {
        $author_id = 2; // Different from the default user_id (1)
        
        // Mock the get_user_by function
        Functions\when('get_user_by')
            ->justReturn((object)['ID' => $author_id]);
            
        $result = $this->importer->import($this->test_file, [
            'author_id' => $author_id
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
    }
    
    /**
     * Test importing a design with a file that exceeds the maximum allowed size
     */
    public function test_import_file_exceeds_max_size()
    {
        // Create a large file that exceeds the 10MB limit
        $large_file = $this->test_assets_dir . '/large_design.json';
        $large_content = str_repeat('a', 11 * 1024 * 1024); // 11MB
        file_put_contents($large_file, $large_content);
        
        $result = $this->importer->import($large_file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('exceeds the maximum file size', $result['message']);
        
        unlink($large_file);
    }
    
    /**
     * Test importing a design with an invalid file type
     */
    public function test_import_invalid_file_type()
    {
        $invalid_file = $this->test_assets_dir . '/invalid_file.txt';
        file_put_contents($invalid_file, 'This is not a valid design file');
        
        $result = $this->importer->import($invalid_file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('Invalid file type', $result['message']);
        
        unlink($invalid_file);
    }
    
    /**
     * Test concurrent imports to ensure thread safety
     */
    public function test_concurrent_imports()
    {
        $results = [];
        $iterations = 5;
        
        // Create multiple test files
        $test_files = [];
        for ($i = 0; $i < $iterations; $i++) {
            $test_file = $this->test_assets_dir . "/test_design_$i.json";
            $design_data = [
                'version' => '1.0.0',
                'name' => "Test Design $i",
                'title' => "Test Design $i",
                'elements' => [['id' => "el_$i", 'type' => 'text', 'content' => "Test $i"]],
                'settings' => ['width' => 800, 'height' => 600]
            ];
            file_put_contents($test_file, json_encode($design_data));
            $test_files[] = $test_file;
        }
        
        // Import all files concurrently
        $imports = [];
        foreach ($test_files as $file) {
            $imports[] = $this->importer->import($file);
        }
        
        // Verify all imports were successful
        foreach ($imports as $result) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            $this->assertTrue($result['success']);
            $this->assertArrayHasKey('design_id', $result);
        }
        
        // Clean up test files
        foreach ($test_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Test memory usage with a large design
     */
    public function test_memory_usage_with_large_design()
    {
        // Create a large design with many elements
        $large_design = [
            'version' => '1.0.0',
            'name' => 'Large Design',
            'title' => 'Large Design',
            'elements' => [],
            'settings' => ['width' => 800, 'height' => 600]
        ];
        
        // Add 1000 elements
        for ($i = 0; $i < 1000; $i++) {
            $large_design['elements'][] = [
                'id' => "el_$i",
                'type' => 'text',
                'content' => "Element $i",
                'x' => rand(0, 700),
                'y' => rand(0, 500),
                'styles' => [
                    'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                    'fontSize' => rand(10, 36)
                ]
            ];
        }
        
        $large_file = $this->test_assets_dir . '/large_design.json';
        file_put_contents($large_file, json_encode($large_design));
        
        $memory_before = memory_get_usage();
        $result = $this->importer->import($large_file);
        $memory_after = memory_get_usage();
        $memory_used = $memory_after - $memory_before;
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        
        // Verify memory usage is reasonable (less than 10MB for this test)
        $this->assertLessThan(10 * 1024 * 1024, $memory_used, "Memory usage should be less than 10MB, used: " . ($memory_used / 1024 / 1024) . "MB");
        
        unlink($large_file);
    }
    
    /**
     * Test importing a design with a featured image
     */
    public function test_import_with_featured_image()
    {
        // Create a test image
        $image_path = $this->test_assets_dir . '/test-image.jpg';
        file_put_contents($image_path, file_get_contents('https://via.placeholder.com/800x600'));
        
        // Mock media handling functions
        $attachment_id = 123;
        
        Functions\when('media_handle_sideload')
            ->justReturn($attachment_id);
            
        Functions\when('set_post_thumbnail')
            ->justReturn(true);
        
        // Create a design with featured image reference
        $design_with_image = json_decode(file_get_contents($this->test_file), true);
        $design_with_image['featured_image'] = [
            'url' => 'http://example.com/test-image.jpg',
            'path' => 'images/test-image.jpg',
            'title' => 'Test Featured Image',
            'alt' => 'Test Image',
            'caption' => 'Test Caption',
            'description' => 'Test Description'
        ];
        
        $design_file = $this->test_assets_dir . '/design_with_image.json';
        file_put_contents($design_file, json_encode($design_with_image));
        
        // Mock the download_url function
        Functions\when('download_url')
            ->justReturn($image_path);
            
        $result = $this->importer->import($design_file, [
            'download_assets' => true,
            'asset_base_url' => 'http://example.com/'
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
        $this->assertArrayHasKey('featured_image_id', $result);
        $this->assertEquals($attachment_id, $result['featured_image_id']);
        
        // Clean up
        unlink($design_file);
        unlink($image_path);
    }
    
    /**
     * Test importing a design with taxonomies
     */
    public function test_import_with_taxonomies()
    {
        // Register a test taxonomy
        register_taxonomy('design_category', 'design', [
            'label' => 'Design Categories',
            'hierarchical' => true,
        ]);
        
        // Create test terms
        $parent_term_id = 10;
        $child_term_id = 11;
        
        // Mock term functions
        Functions\when('term_exists')
            ->justReturn(false);
            
        Functions\when('wp_insert_term')
            ->returnArg(0); // Return the term name as the term object
            
        Functions\when('get_term_by')
            ->justReturn((object)[
                'term_id' => $parent_term_id,
                'name' => 'Test Category',
                'slug' => 'test-category',
                'term_group' => 0,
                'term_taxonomy_id' => 1,
                'taxonomy' => 'design_category',
                'description' => '',
                'parent' => 0,
                'count' => 0
            ]);
        
        // Create a design with taxonomy terms
        $design_with_tax = json_decode(file_get_contents($this->test_file), true);
        $design_with_tax['taxonomies'] = [
            'design_category' => [
                'Test Category' => [
                    'name' => 'Test Category',
                    'slug' => 'test-category',
                    'description' => 'Test category description',
                    'parent' => '',
                    'meta' => [
                        'custom_field' => 'Custom Value'
                    ]
                ],
                'Child Category' => [
                    'name' => 'Child Category',
                    'slug' => 'child-category',
                    'description' => 'Child category description',
                    'parent' => 'Test Category',
                    'meta' => [
                        'custom_field' => 'Child Value'
                    ]
                ]
            ]
        ];
        
        $design_file = $this->test_assets_dir . '/design_with_tax.json';
        file_put_contents($design_file, json_encode($design_with_tax));
        
        $result = $this->importer->import($design_file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('taxonomies', $result);
        $this->assertArrayHasKey('design_category', $result['taxonomies']);
        $this->assertCount(2, $result['taxonomies']['design_category']);
        
        // Clean up
        unlink($design_file);
    }
    
    /**
     * Test importing a design with custom capabilities
     */
    public function test_import_with_custom_capabilities()
    {
        $role_name = 'design_importer';
        $capabilities = [
            'edit_designs' => true,
            'delete_designs' => true,
            'publish_designs' => true,
            'edit_others_designs' => true
        ];
        
        // Mock role and capability functions
        $role = Mockery::mock('WP_Role');
        $role->shouldReceive('add_cap')->times(count($capabilities));
        
        Functions\when('get_role')
            ->with($role_name)
            ->justReturn($role);
            
        // Create a design with custom capabilities
        $design_with_caps = json_decode(file_get_contents($this->test_file), true);
        $design_with_caps['capabilities'] = $capabilities;
        
        $design_file = $this->test_assets_dir . '/design_with_caps.json';
        file_put_contents($design_file, json_encode($design_with_caps));
        
        $result = $this->importer->import($design_file, [
            'role' => $role_name
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        
        // Clean up
        unlink($design_file);
    }
    
    /**
     * Test updating a design with revision history
     */
    public function test_update_design_with_revisions()
    {
        // Import initial design
        $initial_design = json_decode(file_get_contents($this->test_file), true);
        $initial_design['version'] = '1.0.0';
        $initial_design['description'] = 'Initial version';
        
        $design_file = $this->test_assets_dir . '/design_v1.json';
        file_put_contents($design_file, json_encode($initial_design));
        
        // Mock post functions for initial import
        $design_id = 123;
        Functions\when('wp_insert_post')
            ->justReturn($design_id);
            
        // Mock revision functions
        $revision_id = 456;
        Functions\when('wp_save_post_revision')
            ->justReturn($revision_id);
            
        $result = $this->importer->import($design_file, [
            'save_revisions' => true
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals($design_id, $result['design_id']);
        
        // Update the design
        $updated_design = $initial_design;
        $updated_design['version'] = '1.0.1';
        $updated_design['description'] = 'Updated version';
        $updated_design['elements'][0]['content'] = 'Updated content';
        
        $updated_file = $this->test_assets_dir . '/design_v2.json';
        file_put_contents($updated_file, json_encode($updated_design));
        
        // Mock get_post_meta to return the original design ID
        Functions\when('get_post_meta')
            ->andReturnUsing(function($post_id, $key, $single) use ($design_id) {
                if ($key === '_design_import_id') {
                    return $design_id;
                }
                return [];
            });
            
        $update_result = $this->importer->import($updated_file, [
            'update_existing' => true,
            'save_revisions' => true
        ]);
        
        $this->assertIsArray($update_result);
        $this->assertArrayHasKey('success', $update_result);
        $this->assertTrue($update_result['success']);
        $this->assertEquals($design_id, $update_result['design_id']);
        $this->assertArrayHasKey('revision_id', $update_result);
        $this->assertEquals($revision_id, $update_result['revision_id']);
        
        // Clean up
        unlink($design_file);
        unlink($updated_file);
    }
    
    /**
     * Test rolling back to a previous version
     */
    public function test_rollback_to_previous_version()
    {
        $design_id = 123;
        $revision_id = 456;
        
        // Mock get_post to return the design post
        Functions\when('get_post')
            ->justReturn((object)[
                'ID' => $design_id,
                'post_type' => 'design',
                'post_status' => 'publish'
            ]);
            
        // Mock get_post_meta to return the design ID
        Functions\when('get_post_meta')
            ->andReturnUsing(function($post_id, $key, $single) use ($design_id) {
                if ($key === '_design_import_id') {
                    return $design_id;
                }
                return [];
            });
            
        // Mock wp_restore_post_revision
        Functions\when('wp_restore_post_revision')
            ->justReturn($revision_id);
            
        // Mock get_post_meta for revision data
        $revision_data = [
            'version' => '1.0.0',
            'description' => 'Rolled back version'
        ];
        
        Functions\when('get_metadata')
            ->andReturnUsing(function($type, $id, $key) use ($revision_data) {
                if ($key === '_design_data') {
                    return [serialize($revision_data)];
                }
                return [];
            });
            
        $result = $this->importer->rollback_to_version($design_id, $revision_id);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals($design_id, $result['design_id']);
        $this->assertEquals($revision_id, $result['revision_id']);
    }
    
    /**
     * Test handling of version conflicts
     */
    public function test_version_conflict_resolution()
    {
        $design_id = 123;
        $remote_version = '2.0.0';
        $local_version = '1.0.0';
        
        // Create a design with a newer version
        $conflicting_design = json_decode(file_get_contents($this->test_file), true);
        $conflicting_design['version'] = $remote_version;
        
        $design_file = $this->test_assets_dir . '/conflicting_design.json';
        file_put_contents($design_file, json_encode($conflicting_design));
        
        // Mock get_post_meta to return local version
        Functions\when('get_post_meta')
            ->andReturnUsing(function($post_id, $key, $single) use ($design_id, $local_version) {
                if ($key === '_design_import_id') {
                    return $design_id;
                }
                if ($key === '_design_version') {
                    return $local_version;
                }
                return [];
            });
            
        // Test with 'skip' strategy (default)
        $result = $this->importer->import($design_file, [
            'update_existing' => true,
            'version_conflict_strategy' => 'skip'
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals('version_conflict', $result['code']);
        
        // Test with 'override' strategy
        $override_result = $this->importer->import($design_file, [
            'update_existing' => true,
            'version_conflict_strategy' => 'override'
        ]);
        
        $this->assertIsArray($override_result);
        $this->assertArrayHasKey('success', $override_result);
        $this->assertTrue($override_result['success']);
        
        // Clean up
        unlink($design_file);
    }
    
    /**
     * Test cleanup of temporary files after import
     */
    public function test_temp_file_cleanup()
    {
        // Create a temporary file that should be cleaned up
        $temp_dir = sys_get_temp_dir() . '/ckpp_import_' . uniqid();
        mkdir($temp_dir, 0755, true);
        
        $temp_file = $temp_dir . '/temp_file.tmp';
        file_put_contents($temp_file, 'temporary content');
        
        // Mock the cleanup method to track if it's called
        $mock_importer = Mockery::mock(get_class($this->importer))->makePartial();
        $mock_importer->shouldReceive('cleanup_temp_files')
            ->once()
            ->andReturn(true);
        
        // Trigger cleanup
        $result = $mock_importer->cleanup_temp_files($temp_dir);
        
        $this->assertTrue($result);
        $this->assertDirectoryDoesNotExist($temp_dir);
    }
    
    /**
     * Test cleanup of old revisions
     */
    public function test_cleanup_old_revisions()
    {
        $design_id = 123;
        $max_revisions = 5;
        $total_revisions = 10; // More than max
        
        // Create mock revisions
        $revisions = [];
        for ($i = 0; $i < $total_revisions; $i++) {
            $revisions[] = (object)[
                'ID' => 1000 + $i,
                'post_parent' => $design_id,
                'post_type' => 'revision',
                'post_date' => date('Y-m-d H:i:s', strtotime("-$i hours"))
            ];
        }
        
        // Mock get_children to return revisions
        Functions\when('get_children')
            ->with([
                'post_parent' => $design_id,
                'post_type' => 'revision',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'DESC'
            ])
            ->justReturn($revisions);
            
        // Mock wp_delete_post to track deletions
        $deleted_ids = [];
        Functions\when('wp_delete_post')
            ->andReturnUsing(function($post_id) use (&$deleted_ids) {
                $deleted_ids[] = $post_id;
                return true;
            });
        
        // Trigger cleanup
        $result = $this->importer->cleanup_old_revisions($design_id, $max_revisions);
        
        // Should keep $max_revisions and delete the rest
        $expected_deletions = $total_revisions - $max_revisions;
        $this->assertCount($expected_deletions, $deleted_ids);
        $this->assertTrue($result);
    }
    
    /**
     * Test handling of failed imports
     */
    public function test_failed_import_cleanup()
    {
        // Create a test file that will cause an import failure
        $invalid_design = [
            'version' => '1.0.0',
            'name' => 'Invalid Design',
            // Missing required fields
        ];
        
        $design_file = $this->test_assets_dir . '/failed_design.json';
        file_put_contents($design_file, json_encode($invalid_design));
        
        // Mock the import to throw an exception
        $mock_importer = Mockery::mock(get_class($this->importer))->makePartial();
        $mock_importer->shouldReceive('validate_design_data')
            ->andThrow(new \Exception('Invalid design data'));
            
        // Mock cleanup method
        $cleanup_called = false;
        $mock_importer->shouldReceive('cleanup_after_failure')
            ->once()
            ->andReturnUsing(function() use (&$cleanup_called) {
                $cleanup_called = true;
                return true;
            });
        
        try {
            $mock_importer->import($design_file);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid design data', $e->getMessage());
        }
        
        $this->assertTrue($cleanup_called, 'Cleanup method was not called after import failure');
        
        // Clean up
        unlink($design_file);
    }
    
    /**
     * Test resource cleanup after rollback
     */
    public function test_rollback_cleanup()
    {
        $design_id = 123;
        $revision_id = 456;
        $asset_id = 789;
        
        // Mock get_post to return the design post
        Functions\when('get_post')
            ->justReturn((object)[
                'ID' => $design_id,
                'post_type' => 'design',
                'post_status' => 'publish'
            ]);
            
        // Mock get_attached_media to return an asset
        $asset = (object)[
            'ID' => $asset_id,
            'post_mime_type' => 'image/jpeg',
            'guid' => 'http://example.com/wp-content/uploads/test.jpg'
        ];
        
        Functions\when('get_attached_media')
            ->with('', $design_id)
            ->andReturn([$asset]);
            
        // Mock wp_delete_attachment
        $deleted_assets = [];
        Functions\when('wp_delete_attachment')
            ->andReturnUsing(function($id, $force_delete) use (&$deleted_assets) {
                $deleted_assets[] = $id;
                return true;
            });
            
        // Mock get_post_meta for revision data
        $revision_data = [
            'version' => '1.0.0',
            'description' => 'Rolled back version',
            'assets' => [
                'images' => [
                    'test' => [
                        'id' => $asset_id,
                        'url' => 'http://example.com/wp-content/uploads/test.jpg',
                        'path' => 'uploads/test.jpg'
                    ]
                ]
            ]
        ];
        
        Functions\when('get_metadata')
            ->andReturnUsing(function($type, $id, $key) use ($revision_data) {
                if ($key === '_design_data') {
                    return [serialize($revision_data)];
                }
                return [];
            });
            
        // Perform rollback with cleanup
        $result = $this->importer->rollback_to_version($design_id, $revision_id, [
            'cleanup_assets' => true
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertContains($asset_id, $deleted_assets);
    }
    
    /**
     * Test basic design export
     */
    public function test_export_design()
    {
        $design_id = 123;
        $design_data = [
            'version' => '1.0.0',
            'name' => 'Test Design',
            'elements' => [
                ['id' => 'el1', 'type' => 'text', 'content' => 'Test Element']
            ]
        ];
        
        // Mock get_post
        Functions\when('get_post')
            ->justReturn((object)[
                'ID' => $design_id,
                'post_title' => 'Test Design',
                'post_type' => 'design',
                'post_status' => 'publish'
            ]);
            
        // Mock get_post_meta
        Functions\when('get_post_meta')
            ->andReturnUsing(function($id, $key, $single) use ($design_id, $design_data) {
                if ($id === $design_id && $key === '_design_data' && $single) {
                    return [serialize($design_data)];
                }
                return [];
            });
            
        // Test export
        $result = $this->importer->export($design_id);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($design_data['name'], $result['data']['name']);
        $this->assertCount(1, $result['data']['elements']);
    }
    
    /**
     * Test export with included assets
     */
    public function test_export_with_assets()
    {
        $design_id = 123;
        $asset_id = 456;
        $asset_url = 'http://example.com/wp-content/uploads/test.jpg';
        $asset_path = ABSPATH . 'wp-content/uploads/test.jpg';
        
        $design_data = [
            'version' => '1.0.0',
            'name' => 'Design with Assets',
            'elements' => [
                [
                    'id' => 'el1',
                    'type' => 'image',
                    'url' => $asset_url,
                    'path' => 'uploads/test.jpg'
                ]
            ]
        ];
        
        // Create a test image file
        if (!file_exists(dirname($asset_path))) {
            mkdir(dirname($asset_path), 0755, true);
        }
        file_put_contents($asset_path, file_get_contents('https://via.placeholder.com/800x600'));
        
        // Mock WordPress functions
        Functions\when('get_post')->justReturn((object)[
            'ID' => $design_id,
            'post_title' => 'Design with Assets',
            'post_type' => 'design'
        ]);
        
        Functions\when('get_post_meta')
            ->andReturnUsing(function($id, $key, $single) use ($design_id, $design_data) {
                if ($id === $design_id && $key === '_design_data' && $single) {
                    return [serialize($design_data)];
                }
                return [];
            });
            
        Functions\when('wp_get_attachment_url')
            ->with($asset_id)
            ->justReturn($asset_url);
            
        // Test export with assets
        $result = $this->importer->export($design_id, [
            'include_assets' => true,
            'asset_base_path' => ABSPATH . 'wp-content/'
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('test.jpg', $result['assets']);
        $this->assertStringContainsString('data:image/jpeg;base64,', $result['assets']['test.jpg']);
        
        // Clean up
        unlink($asset_path);
        @rmdir(dirname($asset_path));
    }
    
    /**
     * Test export of specific design version
     */
    public function test_export_specific_version()
    {
        $design_id = 123;
        $revision_id = 456;
        $version = '1.0.1';
        
        $revision_data = [
            'version' => $version,
            'name' => 'Versioned Design',
            'elements' => [
                ['id' => 'el1', 'type' => 'text', 'content' => 'Versioned Element']
            ]
        ];
        
        // Mock get_post for design
        Functions\when('get_post')
            ->andReturnUsing(function($id) use ($design_id, $revision_id) {
                if ($id === $design_id) {
                    return (object)[
                        'ID' => $design_id,
                        'post_title' => 'Versioned Design',
                        'post_type' => 'design'
                    ];
                }
                if ($id === $revision_id) {
                    return (object)[
                        'ID' => $revision_id,
                        'post_parent' => $design_id,
                        'post_type' => 'revision'
                    ];
                }
                return null;
            });
            
        // Mock get_post_meta for revision data
        Functions\when('get_metadata')
            ->andReturnUsing(function($type, $id, $key) use ($design_id, $revision_id, $revision_data) {
                if ($id === $revision_id && $key === '_design_data') {
                    return [serialize($revision_data)];
                }
                return [];
            });
            
        // Mock wp_get_post_revisions
        Functions\when('wp_get_post_revisions')
            ->with($design_id, ['numberposts' => 1, 'post_status' => 'any'])
            ->andReturn([
                $revision_id => (object)[
                    'ID' => $revision_id,
                    'post_date' => current_time('mysql')
                ]
            ]);
            
        // Test export specific version
        $result = $this->importer->export($design_id, [
            'version' => $version
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals($version, $result['data']['version']);
        $this->assertEquals('Versioned Design', $result['data']['name']);
    }
    
    /**
     * Test batch import of multiple designs
     */
    public function test_batch_import_designs()
    {
        $design_count = 5;
        $design_files = [];
        $designs = [];
        
        // Create test design files
        for ($i = 1; $i <= $design_count; $i++) {
            $design = [
                'version' => '1.0.0',
                'name' => "Test Design $i",
                'elements' => [
                    ['id' => "el$i", 'type' => 'text', 'content' => "Element $i"]
                ]
            ];
            
            $design_file = $this->test_assets_dir . "/batch_design_$i.json";
            file_put_contents($design_file, json_encode($design));
            
            $designs[] = $design;
            $design_files[] = $design_file;
        }
        
        // Mock wp_insert_post to return incremental IDs
        $post_id = 100;
        Functions\when('wp_insert_post')
            ->andReturnUsing(function() use (&$post_id) {
                return $post_id++;
            });
            
        // Test batch import
        $results = $this->importer->batch_import($design_files);
        
        $this->assertIsArray($results);
        $this->assertCount($design_count, $results);
        
        foreach ($results as $i => $result) {
            $this->assertArrayHasKey('success', $result);
            $this->assertTrue($result['success']);
            $this->assertArrayHasKey('design_id', $result);
            $this->assertEquals("Test Design " . ($i + 1), $result['data']['name']);
        }
        
        // Clean up
        foreach ($design_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Test batch export of multiple designs
     */
    public function test_batch_export_designs()
    {
        $design_count = 3;
        $design_ids = range(100, 100 + $design_count - 1);
        $exported_data = [];
        
        // Mock get_posts to return design posts
        $posts = [];
        foreach ($design_ids as $i => $id) {
            $design = [
                'version' => '1.0.0',
                'name' => "Export Design " . ($i + 1),
                'elements' => [
                    ['id' => "el$i", 'type' => 'text', 'content' => "Export Element $i"]
                ]
            ];
            
            $posts[] = (object)[
                'ID' => $id,
                'post_title' => "Export Design " . ($i + 1),
                'post_type' => 'design',
                'post_status' => 'publish'
            ];
            
            // Mock get_post_meta for each design
            Functions\when('get_post_meta')
                ->with($id, '_design_data', true)
                ->andReturn(serialize($design));
                
            $exported_data[$id] = $design;
        }
        
        Functions\when('get_posts')
            ->with([
                'post_type' => 'design',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'post__in' => $design_ids,
                'orderby' => 'post__in'
            ])
            ->andReturn($posts);
            
        // Test batch export
        $results = $this->importer->batch_export($design_ids);
        
        $this->assertIsArray($results);
        $this->assertCount($design_count, $results);
        
        foreach ($results as $result) {
            $this->assertArrayHasKey('success', $result);
            $this->assertTrue($result['success']);
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey($result['design_id'], $exported_data);
            $this->assertEquals(
                $exported_data[$result['design_id']]['name'],
                $result['data']['name']
            );
        }
    }
    
    /**
     * Test performance of large batch imports
     */
    public function test_large_batch_performance()
    {
        $large_count = 50; // Reduced from 1000 for testing purposes
        $batch_size = 10;
        
        // Create a large number of test designs
        $designs = [];
        for ($i = 0; $i < $large_count; $i++) {
            $designs[] = [
                'version' => '1.0.0',
                'name' => "Performance Design " . ($i + 1),
                'elements' => [
                    ['id' => "el$i", 'type' => 'text', 'content' => "Element $i"]
                ]
            ];
        }
        
        // Mock wp_insert_post to track performance
        $imported_count = 0;
        $start_time = microtime(true);
        
        Functions\when('wp_insert_post')
            ->andReturnUsing(function() use (&$imported_count) {
                $imported_count++;
                return 1000 + $imported_count;
            });
            
        // Test batch import with chunking
        $results = [];
        $chunks = array_chunk($designs, $batch_size);
        
        foreach ($chunks as $chunk) {
            $chunk_results = $this->importer->batch_import($chunk, [
                'batch_size' => $batch_size
            ]);
            $results = array_merge($results, $chunk_results);
        }
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        // Assert all designs were imported
        $this->assertCount($large_count, $results);
        $this->assertEquals($large_count, $imported_count);
        
        // Log performance metrics
        $avg_time_per_design = ($execution_time / $large_count) * 1000; // ms per design
        $this->assertLessThan(100, $avg_time_per_design, "Average import time should be less than 100ms per design");
        
        // Output performance metrics
        fwrite(STDERR, sprintf(
            "\nPerformance: Imported %d designs in %.2f seconds (avg %.2f ms/design)\n",
            $large_count,
            $execution_time,
            $avg_time_per_design
        ));
    }
    
    /**
     * Test error handling in batch operations
     */
    public function test_batch_error_handling()
    {
        $valid_design = [
            'version' => '1.0.0',
            'name' => 'Valid Design',
            'elements' => [
                ['id' => 'el1', 'type' => 'text', 'content' => 'Valid Element']
            ]
        ];
        
        $invalid_design = [
            'version' => '1.0.0',
            // Missing required 'name' field
            'elements' => [
                ['id' => 'el2', 'type' => 'text', 'content' => 'Invalid Element']
            ]
        ];
        
        // Create test files
        $valid_file = $this->test_assets_dir . '/valid_design.json';
        $invalid_file = $this->test_assets_dir . '/invalid_design.json';
        
        file_put_contents($valid_file, json_encode($valid_design));
        file_put_contents($invalid_file, json_encode($invalid_design));
        
        // Test batch import with one valid and one invalid design
        $results = $this->importer->batch_import([$valid_file, $invalid_file], [
            'continue_on_error' => true
        ]);
        
        $this->assertCount(2, $results);
        
        // Check valid design result
        $this->assertTrue($results[0]['success']);
        $this->assertEquals('Valid Design', $results[0]['data']['name']);
        
        // Check invalid design result
        $this->assertFalse($results[1]['success']);
        $this->assertArrayHasKey('code', $results[1]);
        $this->assertEquals('missing_required_field', $results[1]['code']);
        
        // Clean up
        unlink($valid_file);
        unlink($invalid_file);
    }
    
    /**
     * Test REST API endpoint for importing a design
     */
    public function test_rest_api_import()
    {
        // Mock the REST API server
        $server = $this->getMockBuilder('WP_REST_Server')
            ->setMethods(['send_headers', 'set_status'])
            ->getMock();
            
        $request = new WP_REST_Request('POST', '/ckpp/v1/designs/import');
        $request->set_header('content-type', 'application/json');
        
        // Create a test design
        $design_data = [
            'version' => '1.0.0',
            'name' => 'API Import Test',
            'elements' => [
                ['id' => 'el1', 'type' => 'text', 'content' => 'API Element']
            ]
        ];
        
        // Create a temporary file for testing
        $temp_file = $this->test_assets_dir . '/api_import_test.json';
        file_put_contents($temp_file, json_encode($design_data));
        
        // Mock the file upload
        $_FILES = [
            'file' => [
                'name' => 'api_import_test.json',
                'type' => 'application/json',
                'tmp_name' => $temp_file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($temp_file)
            ]
        ];
        
        // Mock the importer
        $mock_importer = Mockery::mock('CKPP_Design_Importer');
        $mock_importer->shouldReceive('import')
            ->with($temp_file, [])
            ->andReturn([
                'success' => true,
                'design_id' => 123,
                'data' => $design_data
            ]);
            
        // Set up the controller
        $controller = new CKPP_REST_Designs_Controller($mock_importer);
        $controller->register_routes();
        
        // Test the endpoint
        $response = $controller->import_design($request);
        
        // Verify the response
        $this->assertInstanceOf('WP_REST_Response', $response);
        $data = $response->get_data();
        
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertEquals('API Import Test', $data['data']['name']);
        
        // Clean up
        unlink($temp_file);
        unset($_FILES);
    }
    
    /**
     * Test REST API permissions
     */
    public function test_rest_api_permissions()
    {
        // Create a test user with and without capabilities
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        $subscriber_user = $this->factory->user->create(['role' => 'subscriber']);
        
        // Test admin user permissions
        wp_set_current_user($admin_user);
        $this->assertTrue(CKPP_REST_Designs_Controller::check_permissions());
        
        // Test subscriber user permissions (should fail)
        wp_set_current_user($subscriber_user);
        $this->assertWPError(CKPP_REST_Designs_Controller::check_permissions());
        
        // Test unauthenticated user (should fail)
        wp_set_current_user(0);
        $this->assertWPError(CKPP_REST_Designs_Controller::check_permissions());
    }
    
    /**
     * Test REST API export endpoint
     */
    public function test_rest_api_export()
    {
        $design_id = 123;
        $design_data = [
            'version' => '1.0.0',
            'name' => 'API Export Test',
            'elements' => [
                ['id' => 'el1', 'type' => 'text', 'content' => 'Export Element']
            ]
        ];
        
        // Create a mock request
        $request = new WP_REST_Request('GET', "/ckpp/v1/designs/$design_id/export");
        $request->set_param('include_assets', true);
        
        // Mock the importer
        $mock_importer = Mockery::mock('CKPP_Design_Importer');
        $mock_importer->shouldReceive('export')
            ->with($design_id, ['include_assets' => true])
            ->andReturn([
                'success' => true,
                'data' => $design_data,
                'assets' => []
            ]);
            
        // Set up the controller
        $controller = new CKPP_REST_Designs_Controller($mock_importer);
        $controller->register_routes();
        
        // Test the endpoint
        $response = $controller->export_design($request);
        
        // Verify the response
        $this->assertInstanceOf('WP_REST_Response', $response);
        $data = $response->get_data();
        
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertEquals('API Export Test', $data['data']['name']);
    }
    
    /**
     * Test REST API batch operations
     */
    public function test_rest_api_batch_operations()
    {
        // Create test designs
        $designs = [
            [
                'version' => '1.0.0',
                'name' => 'Batch Design 1',
                'elements' => [['id' => 'el1', 'type' => 'text', 'content' => 'Element 1']]
            ],
            [
                'version' => '1.0.0',
                'name' => 'Batch Design 2',
                'elements' => [['id' => 'el2', 'type' => 'text', 'content' => 'Element 2']]
            ]
        ];
        
        // Create a mock request
        $request = new WP_REST_Request('POST', '/ckpp/v1/designs/batch-import');
        $request->set_header('content-type', 'application/json');
        $request->set_body(json_encode(['designs' => $designs]));
        
        // Mock the importer
        $mock_importer = Mockery::mock('CKPP_Design_Importer');
        $mock_importer->shouldReceive('batch_import')
            ->with($designs, [])
            ->andReturn([
                ['success' => true, 'design_id' => 101, 'data' => $designs[0]],
                ['success' => true, 'design_id' => 102, 'data' => $designs[1]]
            ]);
            
        // Set up the controller
        $controller = new CKPP_REST_Designs_Controller($mock_importer);
        $controller->register_routes();
        
        // Test the endpoint
        $response = $controller->batch_import_designs($request);
        
        // Verify the response
        $this->assertInstanceOf('WP_REST_Response', $response);
        $data = $response->get_data();
        
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['results']);
        $this->assertEquals('Batch Design 1', $data['results'][0]['data']['name']);
        $this->assertEquals('Batch Design 2', $data['results'][1]['data']['name']);
    }
    
    /**
     * Test handling of malicious input
     */
    public function test_malicious_input_handling()
    {
        // Test XSS in design name
        $xss_design = [
            'version' => '1.0.0',
            'name' => '<script>alert("XSS")</script>',
            'elements' => [
                [
                    'id' => 'el1',
                    'type' => 'text',
                    'content' => 'Test Content',
                    'styles' => [
                        'color' => 'red',
                        'onmouseover' => 'alert(1)' // XSS attempt
                    ]
                ]
            ]
        ];
        
        // Test SQL injection in metadata
        $sql_injection_design = [
            'version' => '1.0.0',
            'name' => 'SQL Test',
            'meta' => [
                'sql_injection' => "'; DROP TABLE wp_posts; --"
            ]
        ];
        
        // Test path traversal in asset paths
        $path_traversal_design = [
            'version' => '1.0.0',
            'name' => 'Path Traversal Test',
            'assets' => [
                'images' => [
                    'malicious' => [
                        'path' => '../../wp-config.php' // Path traversal attempt
                    ]
                ]
            ]
        ];
        
        // Test each malicious pattern
        $test_cases = [
            'xss' => $xss_design,
            'sql_injection' => $sql_injection_design,
            'path_traversal' => $path_traversal_design
        ];
        
        foreach ($test_cases as $test_name => $malicious_design) {
            $temp_file = $this->test_assets_dir . "/malicious_$test_name.json";
            file_put_contents($temp_file, json_encode($malicious_design));
            
            // Test that the import fails with invalid data
            $result = $this->importer->import($temp_file, [
                'validate_security' => true
            ]);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            $this->assertFalse($result['success']);
            $this->assertArrayHasKey('code', $result);
            $this->assertEquals('security_validation_failed', $result['code']);
            
            unlink($temp_file);
        }
    }
    
    /**
     * Test file system permission handling
     */
    public function test_filesystem_permission_handling()
    {
        // Create a read-only directory
        $readonly_dir = $this->test_assets_dir . '/readonly';
        mkdir($readonly_dir, 0444, true);
        
        // Test import with read-only directory
        $design = [
            'version' => '1.0.0',
            'name' => 'Permission Test',
            'elements' => [
                ['id' => 'el1', 'type' => 'text', 'content' => 'Test Content']
            ]
        ];
        
        $temp_file = $readonly_dir . '/permission_test.json';
        file_put_contents($temp_file, json_encode($design));
        
        // Test import with read-only directory
        $result = $this->importer->import($temp_file, [
            'asset_base_path' => $readonly_dir
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        
        // Clean up
        @unlink($temp_file);
        @rmdir($readonly_dir);
    }
    
    /**
     * Test memory and resource limits
     */
    public function test_memory_and_resource_limits()
    {
        // Create a large design that exceeds memory limits
        $large_design = [
            'version' => '1.0.0',
            'name' => 'Memory Test',
            'elements' => []
        ];
        
        // Add a large number of elements to consume memory
        for ($i = 0; $i < 10000; $i++) {
            $large_design['elements'][] = [
                'id' => "el$i",
                'type' => 'text',
                'content' => str_repeat('x', 10000) // 10KB per element
            ];
        }
        
        $temp_file = $this->test_assets_dir . '/memory_test.json';
        file_put_contents($temp_file, json_encode($large_design));
        
        // Set a low memory limit for testing
        $original_memory_limit = ini_get('memory_limit');
        ini_set('memory_limit', '10M');
        
        // Test import with memory constraints
        $result = $this->importer->import($temp_file, [
            'memory_limit' => '5M'
        ]);
        
        // Restore original memory limit
        ini_set('memory_limit', $original_memory_limit);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals('memory_limit_exceeded', $result['code']);
        
        // Clean up
        unlink($temp_file);
    }
    
    /**
     * Test data sanitization
     */
    public function test_data_sanitization()
    {
        $unsanitized_design = [
            'version' => '1.0.0',
            'name' => '  Test Design  ', // Extra whitespace
            'description' => '<script>alert(1)</script>Test', // HTML/JS
            'elements' => [
                [
                    'id' => 'el1',
                    'type' => 'text',
                    'content' => '   Trim Me   ',
                    'styles' => [
                        'color' => '  red  ',
                        'fontSize' => '  16px  '
                    ]
                ]
            ]
        ];
        
        $temp_file = $this->test_assets_dir . '/sanitization_test.json';
        file_put_contents($temp_file, json_encode($unsanitized_design));
        
        // Mock the sanitization callback
        $mock_sanitizer = Mockery::mock('CKPP_Design_Sanitizer');
        $mock_sanitizer->shouldReceive('sanitize')
            ->with($unsanitized_design)
            ->andReturnUsing(function($data) {
                // Trim all string values
                array_walk_recursive($data, function(&$value, $key) {
                    if (is_string($value)) {
                        $value = trim($value);
                        $value = wp_kses_post($value); // Strip dangerous HTML
                    }
                });
                return $data;
            });
            
        // Test import with sanitization
        $this->importer->set_sanitizer($mock_sanitizer);
        $result = $this->importer->import($temp_file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        
        // Verify sanitization
        $sanitized_data = $result['data'];
        $this->assertEquals('Test Design', $sanitized_data['name']);
        $this->assertEquals('Test', $sanitized_data['description']);
        $this->assertEquals('Trim Me', $sanitized_data['elements'][0]['content']);
        $this->assertEquals('red', $sanitized_data['elements'][0]['styles']['color']);
        $this->assertEquals('16px', $sanitized_data['elements'][0]['styles']['fontSize']);
        
        // Clean up
        unlink($temp_file);
    }
    
    /**
     * Test complete import-export-import cycle
     */
    public function test_complete_import_export_cycle()
    {
        // 1. Create a test design
        $original_design = [
            'version' => '1.0.0',
            'name' => 'Roundtrip Test',
            'description' => 'Test complete import-export cycle',
            'elements' => [
                [
                    'id' => 'el1',
                    'type' => 'text',
                    'content' => 'Test Content',
                    'styles' => [
                        'color' => 'red',
                        'fontSize' => '16px'
                    ]
                ]
            ]
        ];
        
        // 2. Import the design
        $import_file = $this->test_assets_dir . '/roundtrip_import.json';
        file_put_contents($import_file, json_encode($original_design));
        
        $import_result = $this->importer->import($import_file);
        $this->assertTrue($import_result['success']);
        $imported_design_id = $import_result['design_id'];
        
        // 3. Export the design
        $export_result = $this->importer->export($imported_design_id, [
            'include_assets' => true
        ]);
        $this->assertTrue($export_result['success']);
        
        // 4. Save exported data to a new file
        $export_file = $this->test_assets_dir . '/roundtrip_export.json';
        file_put_contents($export_file, json_encode($export_result['data']));
        
        // 5. Import the exported design
        $import_exported_result = $this->importer->import($export_file);
        $this->assertTrue($import_exported_result['success']);
        
        // 6. Compare the original and re-imported designs
        $this->assertEquals(
            $original_design['name'],
            $import_exported_result['data']['name'],
            'Design name should match after roundtrip'
        );
        
        // 7. Clean up
        unlink($import_file);
        unlink($export_file);
        wp_delete_post($imported_design_id, true);
        wp_delete_post($import_exported_result['design_id'], true);
    }
    
    /**
     * Test concurrent imports
     */
    public function test_concurrent_imports()
    {
        $test_design = [
            'version' => '1.0.0',
            'name' => 'Concurrent Test',
            'elements' => [
                ['id' => 'el1', 'type' => 'text', 'content' => 'Test Content']
            ]
        ];
        
        // Create multiple test files
        $test_files = [];
        $num_concurrent = 5;
        
        for ($i = 0; $i < $num_concurrent; $i++) {
            $test_file = $this->test_assets_dir . "/concurrent_test_$i.json";
            $test_design['name'] = "Concurrent Test $i";
            file_put_contents($test_file, json_encode($test_design));
            $test_files[] = $test_file;
        }
        
        // Run imports in parallel using multiple processes
        $results = [];
        $promises = [];
        
        foreach ($test_files as $file) {
            $promises[] = \React\Promise\resolve($file)->then(function($file) {
                return $this->importer->import($file);
            });
        }
        
        // Wait for all imports to complete
        $results = \React\Promise\all($promises)->wait();
        
        // Verify all imports were successful
        foreach ($results as $i => $result) {
            $this->assertTrue($result['success'], "Import $i failed: " . ($result['message'] ?? ''));
            $this->assertArrayHasKey('design_id', $result);
            $this->assertGreaterThan(0, $result['design_id']);
            
            // Clean up
            wp_delete_post($result['design_id'], true);
            unlink($test_files[$i]);
        }
    }
    
    /**
     * Test import with external assets
     */
    public function test_import_with_external_assets()
    {
        // Create a test image
        $test_image = $this->test_assets_dir . '/test_image.jpg';
        $image_data = file_get_contents('https://via.placeholder.com/800x600');
        file_put_contents($test_image, $image_data);
        
        // Create a design with external assets
        $design_with_assets = [
            'version' => '1.0.0',
            'name' => 'Design with Assets',
            'elements' => [
                [
                    'id' => 'el1',
                    'type' => 'image',
                    'src' => 'test_image.jpg',
                    'alt' => 'Test Image'
                ]
            ],
            'assets' => [
                'test_image.jpg' => [
                    'content' => base64_encode($image_data),
                    'type' => 'image/jpeg',
                    'path' => 'test_image.jpg'
                ]
            ]
        ];
        
        // Save design to file
        $design_file = $this->test_assets_dir . '/design_with_assets.json';
        file_put_contents($design_file, json_encode($design_with_assets));
        
        // Import the design
        $result = $this->importer->import($design_file, [
            'asset_base_path' => $this->test_assets_dir
        ]);
        
        // Verify import was successful
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
        $this->assertGreaterThan(0, $result['design_id']);
        
        // Verify assets were processed
        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('test_image.jpg', $result['assets']);
        $this->assertTrue($result['assets']['test_image.jpg']['success']);
        
        // Clean up
        wp_delete_post($result['design_id'], true);
        unlink($design_file);
        unlink($test_image);
    }
    
    /**
     * Test design versioning and updates
     */
    public function test_design_versioning_and_updates()
    {
        // Initial design version 1.0.0
        $design_v1 = [
            'version' => '1.0.0',
            'name' => 'Versioned Design',
            'elements' => [
                ['id' => 'el1', 'type' => 'text', 'content' => 'Initial Version']
            ]
        ];
        
        // Import version 1
        $file_v1 = $this->test_assets_dir . '/version_v1.json';
        file_put_contents($file_v1, json_encode($design_v1));
        $result_v1 = $this->importer->import($file_v1);
        $this->assertTrue($result_v1['success']);
        $design_id = $result_v1['design_id'];
        
        // Update to version 2.0.0
        $design_v2 = [
            'version' => '2.0.0',
            'name' => 'Versioned Design',
            'elements' => [
                ['id' => 'el1', 'type' => 'text', 'content' => 'Updated Version'],
                ['id' => 'el2', 'type' => 'shape', 'shape' => 'circle']
            ]
        ];
        
        // Import version 2 as an update
        $file_v2 = $this->test_assets_dir . '/version_v2.json';
        file_put_contents($file_v2, json_encode($design_v2));
        $result_v2 = $this->importer->import($file_v2, [
            'update_existing' => $design_id
        ]);
        
        // Verify update was successful
        $this->assertTrue($result_v2['success']);
        $this->assertEquals($design_id, $result_v2['design_id']);
        
        // Export the updated design
        $export_result = $this->importer->export($design_id);
        $this->assertTrue($export_result['success']);
        
        // Verify version was updated
        $this->assertEquals('2.0.0', $export_result['data']['version']);
        $this->assertCount(2, $export_result['data']['elements']);
        
        // Clean up
        unlink($file_v1);
        unlink($file_v2);
        wp_delete_post($design_id, true);
    }
    
    /**
     * Performance benchmark tests
     */
    
    /**
     * Benchmark import performance with different design sizes
     * @dataProvider designSizeProvider
     */
    public function test_benchmark_import_performance($element_count, $max_time_seconds)
    {
        // Generate a design with the specified number of elements
        $design = [
            'version' => '1.0.0',
            'name' => "Performance Test ($element_count elements)",
            'elements' => []
        ];
        
        for ($i = 0; $i < $element_count; $i++) {
            $design['elements'][] = [
                'id' => "el_$i",
                'type' => 'text',
                'content' => "Element $i",
                'styles' => [
                    'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                    'fontSize' => (12 + $i % 8) . 'px'
                ]
            ];
        }
        
        $temp_file = $this->test_assets_dir . "/benchmark_{$element_count}_elements.json";
        file_put_contents($temp_file, json_encode($design));
        
        // Measure memory before import
        $start_memory = memory_get_usage();
        
        // Start timer
        $start_time = microtime(true);
        
        // Perform import
        $result = $this->importer->import($temp_file);
        
        // Calculate metrics
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $execution_time = $end_time - $start_time;
        $memory_used = $end_memory - $start_memory;
        
        // Output benchmark results
        $this->output->writeln(sprintf(
            "\n[Benchmark] Imported %d elements in %.4f seconds (%.2f MB memory used)",
            $element_count,
            $execution_time,
            $memory_used / 1024 / 1024
        ));
        
        // Assert performance meets expectations
        $this->assertLessThan(
            $max_time_seconds,
            $execution_time,
            "Import of $element_count elements took longer than expected"
        );
        
        // Clean up
        if (isset($result['design_id'])) {
            wp_delete_post($result['design_id'], true);
        }
        unlink($temp_file);
    }
    
    /**
     * Benchmark export performance
     */
    public function test_benchmark_export_performance()
    {
        // Create a test design with a reasonable number of elements
        $design = [
            'version' => '1.0.0',
            'name' => 'Export Benchmark',
            'elements' => []
        ];
        
        // Add 1000 elements for benchmarking
        for ($i = 0; $i < 1000; $i++) {
            $design['elements'][] = [
                'id' => "el_$i",
                'type' => 'text',
                'content' => "Element $i",
                'styles' => [
                    'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                    'fontSize' => (12 + $i % 8) . 'px'
                ]
            ];
        }
        
        // Import the test design
        $temp_file = $this->test_assets_dir . '/export_benchmark.json';
        file_put_contents($temp_file, json_encode($design));
        $import_result = $this->importer->import($temp_file);
        $this->assertTrue($import_result['success']);
        
        // Measure export performance
        $start_time = microtime(true);
        $export_result = $this->importer->export($import_result['design_id']);
        $export_time = microtime(true) - $start_time;
        
        // Output benchmark results
        $this->output->writeln(sprintf(
            "\n[Benchmark] Exported design in %.4f seconds",
            $export_time
        ));
        
        // Clean up
        wp_delete_post($import_result['design_id'], true);
        unlink($temp_file);
    }
    
    /**
     * Benchmark concurrent imports
     */
    public function test_benchmark_concurrent_imports()
    {
        $concurrent_imports = [1, 5, 10];
        $element_count = 100;
        
        foreach ($concurrent_imports as $concurrency) {
            $designs = [];
            $files = [];
            
            // Generate test designs
            for ($i = 0; $i < $concurrency; $i++) {
                $design = [
                    'version' => '1.0.0',
                    'name' => "Concurrent Test $i",
                    'elements' => []
                ];
                
                for ($j = 0; $j < $element_count; $j++) {
                    $design['elements'][] = [
                        'id' => "el_{$i}_{$j}",
                        'type' => 'text',
                        'content' => "Element $i-$j"
                    ];
                }
                
                $file = $this->test_assets_dir . "/concurrent_benchmark_$i.json";
                file_put_contents($file, json_encode($design));
                $files[] = $file;
                $designs[] = $design;
            }
            
            // Run concurrent imports
            $start_time = microtime(true);
            $promises = [];
            
            foreach ($files as $file) {
                $promises[] = \React\Promise\resolve($file)->then(function($file) {
                    return $this->importer->import($file);
                });
            }
            
            $results = \React\Promise\all($promises)->wait();
            $total_time = microtime(true) - $start_time;
            
            // Output benchmark results
            $this->output->writeln(sprintf(
                "\n[Benchmark] Completed %d concurrent imports in %.4f seconds (%.4f seconds per import)",
                $concurrency,
                $total_time,
                $total_time / $concurrency
            ));
            
            // Clean up
            foreach ($results as $result) {
                if (isset($result['design_id'])) {
                    wp_delete_post($result['design_id'], true);
                }
            }
            
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
    
    /**
     * Data provider for design size benchmarks
     */
    public function designSizeProvider()
    {
        return [
            'small_design' => [10, 1],    // 10 elements, max 1 second
            'medium_design' => [100, 3],   // 100 elements, max 3 seconds
            'large_design' => [1000, 10],  // 1000 elements, max 10 seconds
        ];
    }
    
    /**
     * Set up output for benchmark tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
    }
        Functions\when('wp_insert_post')
            ->justReturn(1);
            
        Functions\when('wp_update_post')
            ->justReturn(1);
            
        Functions\when('get_current_user_id')
            ->justReturn($this->user_id);
            
        Functions\when('current_time')
            ->justReturn(current_time('mysql'));
            
        // Mock metadata functions
        Functions\when('add_post_meta')
            ->justReturn(true);
            
        Functions\when('update_post_meta')
            ->justReturn(true);
            
        Functions\when('get_post_meta')
            ->justReturn([]);
            
        // Mock file system functions
        Functions\when('wp_upload_dir')
            ->justReturn([
                'path' => $this->test_assets_dir,
                'url' => 'http://example.com/uploads',
                'subdir' => '',
                'basedir' => $this->test_assets_dir,
                'baseurl' => 'http://example.com/uploads',
                'error' => false
            ]);
            
        Functions\when('wp_check_filetype')
            ->justReturn([
                'ext' => 'json',
                'type' => 'application/json',
                'proper_filename' => 'test_design.json'
            ]);
            
        Functions\when('wp_check_filetype_and_ext')
            ->justReturn([
                'ext' => 'json',
                'type' => 'application/json',
                'proper_filename' => 'test_design.json'
            ]);
            
        Functions\when('wp_handle_sideload')
            ->justReturn([
                'file' => $this->test_file,
                'url' => 'http://example.com/uploads/test_design.json',
                'type' => 'application/json',
                'error' => false
            ]);
            
        // Mock WP_Filesystem
        $mock_filesystem = Mockery::mock('WP_Filesystem_Direct');
        $mock_filesystem->shouldReceive('exists')->andReturn(true);
        $mock_filesystem->shouldReceive('is_dir')->andReturn(true);
        $mock_filesystem->shouldReceive('mkdir')->andReturn(true);
        $mock_filesystem->shouldReceive('put_contents')->andReturn(true);
        $mock_filesystem->shouldReceive('get_contents')->andReturn(file_get_contents($this->test_file));
        $mock_filesystem->shouldReceive('delete')->andReturn(true);
        
        $GLOBALS['wp_filesystem'] = $mock_filesystem;
    }
    
    /**
     * Test importing a design from a file
     */
    public function test_import_design()
    {
        $result = $this->importer->import($this->test_file);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
    }
    
    /**
     * Test importing a design with invalid file path
     */
    public function test_import_invalid_file_path()
    {
        $result = $this->importer->import('/path/to/nonexistent/file.json');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }
    
    /**
     * Test importing a design with invalid JSON
     */
    public function test_import_invalid_json()
    {
        $invalid_json_file = $this->test_assets_dir . '/invalid.json';
        file_put_contents($invalid_json_file, 'invalid json');
        
        $result = $this->importer->import($invalid_json_file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        
        unlink($invalid_json_file);
    }
    
    /**
     * Test importing a design with missing required fields
     */
    public function test_import_missing_required_fields()
    {
        $invalid_design = [
            'name' => 'Invalid Design',
            // Missing 'elements' and 'settings' which are required
        ];
        
        $invalid_file = $this->test_assets_dir . '/invalid_design.json';
        file_put_contents($invalid_file, json_encode($invalid_design));
        
        $result = $this->importer->import($invalid_file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        
        unlink($invalid_file);
    }
    
    /**
     * Test importing a design with assets
     */
    public function test_import_with_assets()
    {
        // Create a test asset file
        $asset_file = $this->test_assets_dir . '/test_asset.png';
        file_put_contents($asset_file, 'test image content');
        
        // Create a design with asset references
        $design_with_assets = json_decode(file_get_contents($this->test_file), true);
        $design_with_assets['assets'] = [
            'images' => [
                'test_asset' => [
                    'url' => 'http://example.com/test_asset.png',
                    'path' => 'images/test_asset.png',
                    'type' => 'image/png',
                    'name' => 'Test Asset',
                    'size' => 1234
                ]
            ]
        ];
        
        $design_with_assets_file = $this->test_assets_dir . '/design_with_assets.json';
        file_put_contents($design_with_assets_file, json_encode($design_with_assets));
        
        // Mock the download_url function
        Functions\when('download_url')
            ->justReturn($asset_file);
            
        $result = $this->importer->import($design_with_assets_file, [
            'download_assets' => true,
            'asset_base_url' => 'http://example.com/'
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertIsArray($result['assets']);
        $this->assertArrayHasKey('downloaded', $result['assets']);
        $this->assertGreaterThan(0, $result['assets']['downloaded']);
        
        unlink($design_with_assets_file);
        unlink($asset_file);
    }
    
    /**
     * Test importing a design with a ZIP archive
     */
    public function test_import_zip_archive()
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('The ZipArchive class is not available');
        }
        
        // Create a test zip file
        $zip_file = $this->test_assets_dir . '/test_design.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zip_file, ZipArchive::CREATE) !== true) {
            $this->markTestSkipped('Could not create test zip file');
        }
        
        // Add the design file to the zip
        $zip->addFile($this->test_file, 'design.json');
        
        // Add a test asset
        $asset_content = 'test asset content';
        $zip->addFromString('assets/test_asset.txt', $asset_content);
        
        $zip->close();
        
        // Test importing the zip file
        $result = $this->importer->import($zip_file, [
            'extract_zip' => true
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('design_id', $result);
        $this->assertArrayHasKey('extracted_files', $result);
        $this->assertIsArray($result['extracted_files']);
        $this->assertNotEmpty($result['extracted_files']);
        
        // Clean up
        unlink($zip_file);
    }
    
    /**
            
    $result = $this->importer->import($design_with_assets_file, [
        'download_assets' => true,
        'asset_base_url' => 'http://example.com/'
    ]);
    
    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertTrue($result['success']);
    $this->assertArrayHasKey('design_id', $result);
    $this->assertArrayHasKey('assets', $result);
    $this->assertIsArray($result['assets']);
    $this->assertArrayHasKey('downloaded', $result['assets']);
    $this->assertGreaterThan(0, $result['assets']['downloaded']);
    
    unlink($design_with_assets_file);
    unlink($asset_file);
}

/**
 * Test importing a design with a ZIP archive
 */
public function test_import_zip_archive()
{
    if (!class_exists('ZipArchive')) {
        $this->markTestSkipped('The ZipArchive class is not available');
    }
    
    // Create a test zip file
    $zip_file = $this->test_assets_dir . '/test_design.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zip_file, ZipArchive::CREATE) !== true) {
        $this->markTestSkipped('Could not create test zip file');
    }
    
    // Add the design file to the zip
    $zip->addFile($this->test_file, 'design.json');
    
    // Add a test asset
    $asset_content = 'test asset content';
    $zip->addFromString('assets/test_asset.txt', $asset_content);
    
    $zip->close();
    
    // Test importing the zip file
    $result = $this->importer->import($zip_file, [
        'extract_zip' => true
    ]);
    
    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertTrue($result['success']);
    $this->assertArrayHasKey('design_id', $result);
    $this->assertArrayHasKey('extracted_files', $result);
    $this->assertIsArray($result['extracted_files']);
    $this->assertNotEmpty($result['extracted_files']);
    
    // Clean up
    unlink($zip_file);
}

/**
 * Test importing a design with invalid ZIP archive
 */
public function test_import_invalid_zip()
{
    if (!class_exists('ZipArchive')) {
        $this->markTestSkipped('The ZipArchive class is not available');
    }
    
    // Create an invalid zip file
    $invalid_zip = $this->test_assets_dir . '/invalid.zip';
    file_put_contents($invalid_zip, 'not a zip file');
    
    $result = $this->importer->import($invalid_zip, [
        'extract_zip' => true
    ]);
    
    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('message', $result);
    
    unlink($invalid_zip);
}

/**
 * Test importing a design with custom post type mapping
 */
public function test_import_with_custom_post_type()
{
    $custom_post_type = 'custom_design';
    
    // Mock the register_post_type function
    Functions\when('register_post_type')
        ->justReturn(new \stdClass());
            
    // Register the custom post type
    register_post_type($custom_post_type, [
        'public' => true,
        'label' => 'Custom Design',
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields']
    ]);
    
    $result = $this->importer->import($this->test_file, [
        'post_type' => $custom_post_type
    ]);
    
    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertTrue($result['success']);
    $this->assertArrayHasKey('design_id', $result);
    
    // Clean up
    unregister_post_type($custom_post_type);
}

/**
 * Test importing a design with custom meta fields
 */
public function test_import_with_custom_meta()
{
    $custom_meta = [
        '_custom_field_1' => 'Custom Value 1',
        '_custom_field_2' => 'Custom Value 2'
    ];
    
    $result = $this->importer->import($this->test_file, [
        'meta' => $custom_meta
    ]);
    
    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertTrue($result['success']);
    $this->assertArrayHasKey('design_id', $result);
}

/**
 * Test importing a design with a custom author
 */
public function test_import_with_custom_author()
{
    $author_id = 2; // Different from the default user_id (1)
    
    // Mock the get_user_by function
    Functions\when('get_user_by')
        ->justReturn((object)['ID' => $author_id]);
            
    $result = $this->importer->import($this->test_file, [
        'author_id' => $author_id
    ]);
    
    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertTrue($result['success']);
    $this->assertArrayHasKey('design_id', $result);
}

protected function tearDown(): void
{
    // Clean up the test file
    if (file_exists($this->test_file)) {
        unlink($this->test_file);
    }
    
    Mockery::close();
    parent::tearDown();
}
