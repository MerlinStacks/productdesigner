<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Ajax_Handlers;

class AjaxTest extends IntegrationTestCase
{
    protected $ajax_handlers;
    protected $test_data;
    protected $user_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with customer role
        $this->user_id = $this->factory->user->create(['role' => 'customer']);
        wp_set_current_user($this->user_id);
        
        $this->ajax_handlers = new CKPP_Ajax_Handlers();
        $this->test_data = TestHelper::setup_test_data();
        
        // Set up the $_SERVER variables needed for AJAX
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Set up the test environment for AJAX
        add_filter('wp_doing_ajax', '__return_true');
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
        
        $design = $this->test_data['designs'][0];
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
            ]
        ];
        
        // Set up the request
        $_POST = [
            'action' => 'ckpp_save_design',
            'design_id' => $design['post']->ID,
            'design_data' => json_encode($design_data),
            'preview' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'nonce' => wp_create_nonce('ckpp_ajax_nonce'),
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_ajax_nonce', 'nonce', false)
            ->andReturn(true);
            
        \Brain\Monkey\Functions\expect('current_user_can')
            ->with('edit_post', $design['post']->ID)
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
        $this->assertArrayHasKey('message', $response['data']);
        $this->assertEquals('Design saved successfully', $response['data']['message']);
        
        // Verify the design data was saved
        $saved_design_data = get_post_meta($design['post']->ID, '_ckpp_design_config', true);
        $this->assertIsArray(json_decode($saved_design_data, true));
        $this->assertEquals($design_data, json_decode($saved_design_data, true));
    }
    
    public function test_ajax_get_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
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
            ]
        ];
        
        // Save the design data
        update_post_meta($design['post']->ID, '_ckpp_design_config', json_encode($design_data));
        
        // Set up the request
        $_GET = [
            'action' => 'ckpp_get_design',
            'design_id' => $design['post']->ID,
            'nonce' => wp_create_nonce('ckpp_ajax_nonce'),
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
        $this->assertEquals($design_data, $response['data']['design']);
    }
    
    public function test_ajax_export_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
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
            ]
        ];
        
        // Save the design data
        update_post_meta($design['post']->ID, '_ckpp_design_config', json_encode($design_data));
        
        // Set up the request
        $_GET = [
            'action' => 'ckpp_export_design',
            'design_id' => $design['post']->ID,
            'nonce' => wp_create_nonce('ckpp_ajax_nonce'),
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_ajax_nonce', 'nonce', false)
            ->andReturn(true);
            
        \Brain\Monkey\Functions\expect('wp_send_json_success')
            ->once()
            ->with(\Brain\Monkey\Functions\type('array'));
            
        // Call the method
        $this->ajax_handlers->ajax_export_design();
    }
}
