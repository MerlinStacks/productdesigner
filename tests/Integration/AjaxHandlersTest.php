<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Ajax_Handlers;

class AjaxHandlersTest extends IntegrationTestCase
{
    protected $ajax_handlers;
    protected $test_data;
    protected $user_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with appropriate capabilities
        $this->user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->user_id);
        
        $this->ajax_handlers = new CKPP_Ajax_Handlers();
        $this->test_data = TestHelper::setup_test_data();
        
        // Set up the $_SERVER variables needed for AJAX
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Set up the test environment for AJAX
        add_filter('wp_doing_ajax', '__return_true');
        
        // Suppress headers already sent warning
        if (!defined('WP_TESTS_DOMAIN')) {
            define('WP_TESTS_DOMAIN', 'example.org');
        }
        
        if (!defined('WP_TESTS_EMAIL')) {
            define('WP_TESTS_EMAIL', 'test@example.org');
        }
        
        // Set up the admin-ajax.php file
        if (!function_exists('_wp_admin_ajax_js')) {
            require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';
        }
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
        
        // Reset the AJAX environment
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        unset($_SERVER['REQUEST_METHOD']);
        remove_filter('wp_doing_ajax', '__return_true');
        
        parent::tearDown();
    }
    
    public function test_ajax_save_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
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
                ]
            ],
            'settings' => [
                'width' => 800,
                'height' => 600,
                'background' => '#ffffff',
            ]
        ];
        
        // Set up the request
        $_POST = [
            'action' => 'ckpp_save_design',
            'nonce' => wp_create_nonce('ckpp_ajax_nonce'),
            'title' => 'Test Design',
            'design_data' => json_encode($design_data),
            'preview_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_ajax_nonce', 'nonce', false)
            ->andReturn(true);
            
        // Call the method
        ob_start();
        $this->ajax_handlers->ajax_save_design();
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Verify the response
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('design_id', $response['data']);
        $this->assertArrayHasKey('message', $response['data']);
        
        // Clean up
        if (isset($response['data']['design_id'])) {
            wp_delete_post($response['data']['design_id'], true);
        }
    }
    
    public function test_ajax_get_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        // Set up the request
        $_GET = [
            'action' => 'ckpp_get_design',
            'nonce' => wp_create_nonce('ckpp_ajax_nonce'),
            'design_id' => $design['post']->ID,
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_ajax_nonce', 'nonce', false)
            ->andReturn(true);
            
        // Call the method
        ob_start();
        $this->ajax_handlers->ajax_get_design();
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Verify the response
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('design', $response['data']);
        $this->assertEquals($design['post']->ID, $response['data']['design']['id']);
    }
    
    public function test_ajax_delete_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        $design_id = $design['post']->ID;
        
        // Set up the request
        $_POST = [
            'action' => 'ckpp_delete_design',
            'nonce' => wp_create_nonce('ckpp_ajax_nonce'),
            'design_id' => $design_id,
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_ajax_nonce', 'nonce', false)
            ->andReturn(true);
            
        // Call the method
        ob_start();
        $this->ajax_handlers->ajax_delete_design();
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Verify the response
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('message', $response['data']);
        
        // Verify the design was actually deleted
        $this->assertNull(get_post($design_id));
    }
    
    public function test_ajax_export_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        // Set up the request
        $_GET = [
            'action' => 'ckpp_export_design',
            'nonce' => wp_create_nonce('ckpp_ajax_nonce'),
            'design_id' => $design['post']->ID,
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_ajax_nonce', 'nonce', false)
            ->andReturn(true);
            
        // Mock the headers_sent function to prevent actual header sending
        \Brain\Monkey\Functions\when('headers_sent')
            ->justReturn(false);
            
        // Mock the header function
        \Brain\Monkey\Functions\expect('header')
            ->once()
            ->with('Content-Type: application/json; charset=utf-8')
            ->andReturn(true);
            
        // Mock the wp_die function to capture output
        $output = '';
        \Brain\Monkey\Functions\expect('wp_die')
            ->once()
            ->andReturnUsing(function ($content) use (&$output) {
                $output = $content;
                return '';
            });
            
        // Call the method
        $this->ajax_handlers->ajax_export_design();
        
        // Verify the response
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('export', $response['data']);
        $this->assertArrayHasKey('version', $response['data']['export']);
        $this->assertArrayHasKey('design', $response['data']['export']);
        $this->assertEquals($design['post']->ID, $response['data']['export']['design']['id']);
    }
    
    public function test_ajax_import_design()
    {
        // Create a test export file
        $export_data = [
            'version' => '1.0.0',
            'design' => [
                'post_title' => 'Imported Design',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'ckpp_design',
                'meta_input' => [
                    '_ckpp_design_config' => json_encode([
                        'elements' => [
                            [
                                'type' => 'text',
                                'content' => 'Imported Text',
                                'position' => ['x' => 100, 'y' => 100],
                                'styles' => [
                                    'color' => '#000000',
                                    'fontSize' => 24,
                                    'fontFamily' => 'Arial',
                                ]
                            ]
                        ],
                        'settings' => [
                            'width' => 800,
                            'height' => 600,
                            'background' => '#ffffff',
                        ]
                    ]),
                    '_ckpp_design_preview' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                ]
            ]
        ];
        
        // Create a temporary file for testing
        $temp_file = tempnam(sys_get_temp_dir(), 'ckpp_import_');
        file_put_contents($temp_file, json_encode($export_data));
        
        // Set up the request
        $_FILES = [
            'import_file' => [
                'name' => 'test-import.json',
                'type' => 'application/json',
                'tmp_name' => $temp_file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($temp_file),
            ]
        ];
        
        $_POST = [
            'action' => 'ckpp_import_design',
            'nonce' => wp_create_nonce('ckpp_ajax_nonce'),
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_ajax_nonce', 'nonce', false)
            ->andReturn(true);
            
        // Call the method
        ob_start();
        $this->ajax_handlers->ajax_import_design();
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Clean up the temporary file
        @unlink($temp_file);
        
        // Verify the response
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('design_id', $response['data']);
        $this->assertArrayHasKey('message', $response['data']);
        
        // Verify the design was imported
        $imported_design = get_post($response['data']['design_id']);
        $this->assertNotNull($imported_design);
        $this->assertEquals('Imported Design', $imported_design->post_title);
        
        // Clean up
        if (isset($response['data']['design_id'])) {
            wp_delete_post($response['data']['design_id'], true);
        }
    }
}
