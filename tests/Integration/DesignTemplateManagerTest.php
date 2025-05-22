<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Template_Manager;
use Brain\Monkey\Functions;

class DesignTemplateManagerTest extends IntegrationTestCase
{
    protected $template_manager;
    protected $test_data;
    protected $user_id;
    protected $template_dir;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with administrator role
        $this->user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->user_id);
        
        $this->template_manager = new CKPP_Design_Template_Manager();
        $this->test_data = TestHelper::setup_test_data();
        
        // Set up test template directory
        $this->template_dir = WP_CONTENT_DIR . '/ckpp-templates/';
        if (!file_exists($this->template_dir)) {
            mkdir($this->template_dir, 0755, true);
        }
        
        // Create a test template file
        $template_data = [
            'name' => 'Test Template',
            'description' => 'A test template',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'screenshot' => 'screenshot.jpg',
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Sample Text',
                    'position' => ['x' => 100, 'y' => 100],
                    'styles' => [
                        'color' => '#000000',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                    ]
                ]
            ]
        ];
        
        file_put_contents($this->template_dir . 'test-template.json', json_encode($template_data));
        
        // Create a screenshot file
        $im = imagecreatetruecolor(800, 600);
        $bg_color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $bg_color);
        imagejpeg($im, $this->template_dir . 'screenshot.jpg', 90);
        imagedestroy($im);
        
        // Mock WordPress functions
        Functions\when('wp_upload_dir')
            ->justReturn([
                'basedir' => WP_CONTENT_DIR . '/uploads',
                'baseurl' => 'http://example.com/wp-content/uploads',
                'subdir' => '',
                'path' => WP_CONTENT_DIR . '/uploads',
                'url' => 'http://example.com/wp-content/uploads',
            ]);
            
        Functions\when('wp_mkdir_p')
            ->justReturn(true);
            
        Functions\when('wp_generate_attachment_metadata')
            ->justReturn(['file' => 'screenshots/screenshot.jpg']);
            
        Functions\when('wp_insert_attachment')
            ->justReturn(1);
            
        Functions\when('wp_update_attachment_metadata')
            ->justReturn(true);
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up the test user
        if (isset($this->user_id)) {
            wp_delete_user($this->user_id);
        }
        
        // Clean up template files
        if (is_dir($this->template_dir)) {
            array_map('unlink', glob("$this->template_dir/*.*"));
            rmdir($this->template_dir);
        }
        
        parent::tearDown();
    }
    
    public function test_get_available_templates()
    {
        // Call the method
        $templates = $this->template_manager->get_available_templates();
        
        // Verify the result
        $this->assertIsArray($templates);
        $this->assertArrayHasKey('test-template', $templates);
        $this->assertEquals('Test Template', $templates['test-template']['name']);
        $this->assertEquals('A test template', $templates['test-template']['description']);
        $this->assertStringContainsString('screenshot.jpg', $templates['test-template']['screenshot']);
    }
    
    public function test_get_template()
    {
        // Call the method with an existing template
        $template = $this->template_manager->get_template('test-template');
        
        // Verify the result
        $this->assertIsArray($template);
        $this->assertEquals('Test Template', $template['name']);
        $this->assertArrayHasKey('elements', $template);
        $this->assertIsArray($template['elements']);
        $this->assertCount(1, $template['elements']);
        $this->assertEquals('Sample Text', $template['elements'][0]['content']);
        
        // Test with non-existent template
        $non_existent = $this->template_manager->get_template('non-existent');
        $this->assertNull($non_existent);
    }
    
    public function test_import_template()
    {
        // Create a test template file
        $template_data = [
            'name' => 'Imported Template',
            'description' => 'An imported template',
            'version' => '1.0.0',
            'author' => 'Test Importer',
            'screenshot' => 'screenshot-import.jpg',
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Imported Text',
                    'position' => ['x' => 200, 'y' => 200],
                    'styles' => [
                        'color' => '#FF0000',
                        'fontSize' => 18,
                        'fontFamily' => 'Verdana',
                    ]
                ]
            ]
        ];
        
        $temp_file = tempnam(sys_get_temp_dir(), 'template-') . '.json';
        file_put_contents($temp_file, json_encode($template_data));
        
        // Create a test screenshot
        $screenshot_path = sys_get_temp_dir() . '/screenshot-import.jpg';
        $im = imagecreatetruecolor(800, 600);
        $bg_color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $bg_color);
        imagejpeg($im, $screenshot_path, 90);
        imagedestroy($im);
        
        // Call the method
        $result = $this->template_manager->import_template($temp_file, $screenshot_path);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('template_id', $result['data']);
        
        // Clean up
        unlink($temp_file);
        unlink($screenshot_path);
    }
    
    public function test_export_template()
    {
        // First, create a template to export
        $template_data = [
            'name' => 'Export Test',
            'description' => 'Template for export testing',
            'version' => '1.0.0',
            'author' => 'Test Exporter',
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Export Me',
                    'position' => ['x' => 100, 'y' => 100],
                    'styles' => [
                        'color' => '#0000FF',
                        'fontSize' => 20,
                        'fontFamily' => 'Courier',
                    ]
                ]
            ]
        ];
        
        $template_file = $this->template_dir . 'export-test.json';
        file_put_contents($template_file, json_encode($template_data));
        
        // Call the method
        $export_file = $this->template_manager->export_template('export-test');
        
        // Verify the result
        $this->assertFileExists($export_file);
        $this->assertStringEndsWith('.zip', $export_file);
        
        // Clean up
        if (file_exists($export_file)) {
            unlink($export_file);
        }
    }
    
    public function test_delete_template()
    {
        // Create a template to delete
        $template_data = [
            'name' => 'Delete Test',
            'description' => 'Template for deletion testing',
            'version' => '1.0.0',
            'author' => 'Test Deleter',
            'elements' => []
        ];
        
        $template_file = $this->template_dir . 'delete-test.json';
        file_put_contents($template_file, json_encode($template_data));
        
        // Call the method
        $result = $this->template_manager->delete_template('delete-test');
        
        // Verify the result
        $this->assertTrue($result);
        $this->assertFileDoesNotExist($template_file);
    }
}
