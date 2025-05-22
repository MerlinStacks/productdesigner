<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\Tests\Helpers\JSMockHelper;
use CustomKings\CKPP_Admin;

class AdminJSTest extends IntegrationTestCase
{
    protected $admin;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = new \CustomKings\CKPP_Admin();
        $this->test_data = TestHelper::setup_test_data();
        
        // Set up the admin screen
        set_current_screen('edit-ckpp_design');
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        parent::tearDown();
    }
    
    public function test_admin_enqueue_scripts()
    {
        // Mock the necessary WordPress functions
        JSMockHelper::mockEnqueue('customkings-admin', 'script');
        JSMockHelper::mockEnqueue('customkings-fabric', 'script');
        JSMockHelper::mockEnqueue('customkings-admin', 'style');
        
        // Mock the localization
        JSMockHelper::mockLocalizedScript(
            'customkings-admin',
            'ckpp_admin_vars',
            [
                'ajax_url' => 'http://example.org/wp-admin/admin-ajax.php',
                'nonce' => 'test-nonce',
                'i18n' => [
                    'saving' => 'Saving...',
                    'saved' => 'Settings saved!',
                    'error' => 'An error occurred. Please try again.',
                    'confirm_delete' => 'Are you sure you want to delete this design?',
                ],
            ]
        );
        
        // Call the method
        $this->admin->enqueue_scripts('edit-ckpp_design');
        
        // Verify the script was enqueued
        $this->assertTrue(\Brain\Monkey\Actions\did('admin_enqueue_scripts'));
    }
    
    public function test_admin_localize_script()
    {
        // Mock the necessary WordPress functions
        JSMockHelper::mockLocalizedScript(
            'customkings-admin',
            'ckpp_admin_vars',
            [
                'ajax_url' => 'http://example.org/wp-admin/admin-ajax.php',
                'nonce' => 'test-nonce',
                'i18n' => [
                    'saving' => 'Saving...',
                    'saved' => 'Settings saved!',
                    'error' => 'An error occurred. Please try again.',
                    'confirm_delete' => 'Are you sure you want to delete this design?',
                ],
            ]
        );
        
        // Call the method
        $this->admin->localize_script();
    }
    
    public function test_admin_ajax_save_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        $post_id = $design['post']->ID;
        
        // Set up the request
        $_POST = [
            'post_id' => $post_id,
            'design_data' => json_encode([
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
            ]),
            'preview_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'nonce' => wp_create_nonce('ckpp_admin_nonce'),
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_admin_nonce', 'nonce', false)
            ->andReturn(true);
            
        // Call the method
        ob_start();
        $this->admin->ajax_save_design();
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Verify the response
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('message', $response['data']);
        
        // Verify the meta was saved
        $saved_config = json_decode(get_post_meta($post_id, '_ckpp_design_config', true), true);
        $this->assertEquals('Test Design', $saved_config['elements'][0]['content']);
    }
    
    public function test_admin_ajax_delete_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        $post_id = $design['post']->ID;
        
        // Set up the request
        $_POST = [
            'design_id' => $post_id,
            'nonce' => wp_create_nonce('ckpp_admin_nonce'),
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_admin_nonce', 'nonce', false)
            ->andReturn(true);
            
        // Call the method
        ob_start();
        $this->admin->ajax_delete_design();
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Verify the response
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('message', $response['data']);
        
        // Verify the design was deleted
        $this->assertNull(get_post($post_id));
    }
}
