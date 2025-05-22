<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Frontend_Form_Handler;

class FrontendFormHandlerTest extends IntegrationTestCase
{
    protected $form_handler;
    protected $test_data;
    protected $user_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with customer role
        $this->user_id = $this->factory->user->create(['role' => 'customer']);
        wp_set_current_user($this->user_id);
        
        $this->form_handler = new CKPP_Frontend_Form_Handler();
        $this->test_data = TestHelper::setup_test_data();
        
        // Set up the $_SERVER variables needed for form submission
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_REFERER'] = 'http://example.com/test-page';
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
        
        // Reset the form submission data
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['HTTP_REFERER']);
        
        parent::tearDown();
    }
    
    public function test_process_design_submission()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
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
        
        // Set up the form submission
        $_POST = [
            'ckpp_design_nonce' => wp_create_nonce('ckpp_save_design'),
            'action' => 'save_design',
            'product_id' => $product->get_id(),
            'design_data' => json_encode($design_data),
            'design_preview' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('wp_redirect')
            ->once()
            ->with(\Brain\Monkey\Functions::type('string'))
            ->andReturn(true);
            
        \Brain\Monkey\Functions\expect('wp_die')
            ->never();
        
        // Call the method
        $this->form_handler->process_design_submission();
        
        // Verify the design was saved
        $designs = get_posts([
            'post_type' => 'ckpp_design',
            'author' => $this->user_id,
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        
        $this->assertCount(1, $designs);
        $this->assertEquals('My Custom Design', $designs[0]->post_title);
        
        // Clean up
        wp_delete_post($designs[0]->ID, true);
    }
    
    public function test_process_design_submission_invalid_nonce()
    {
        // Set up the form submission with an invalid nonce
        $_POST = [
            'ckpp_design_nonce' => 'invalid-nonce',
            'action' => 'save_design',
        ];
        
        // Expect wp_die to be called
        \Brain\Monkey\Functions\expect('wp_die')
            ->once()
            ->with('Security check failed', 'Error', ['response' => 403]);
        
        // Call the method
        $this->form_handler->process_design_submission();
    }
    
    public function test_process_design_submission_missing_data()
    {
        // Set up the form submission with missing data
        $_POST = [
            'ckpp_design_nonce' => wp_create_nonce('ckpp_save_design'),
            'action' => 'save_design',
            // Missing product_id and design_data
        ];
        
        // Expect wp_die to be called
        \Brain\Monkey\Functions\expect('wp_die')
            ->once()
            ->with('Missing required data', 'Error', ['response' => 400]);
        
        // Call the method
        $this->form_handler->process_design_submission();
    }
    
    public function test_process_design_submission_invalid_product()
    {
        // Set up the form submission with an invalid product ID
        $_POST = [
            'ckpp_design_nonce' => wp_create_nonce('ckpp_save_design'),
            'action' => 'save_design',
            'product_id' => 999999, // Non-existent product
            'design_data' => json_encode(['elements' => []]),
        ];
        
        // Expect wp_die to be called
        \Brain\Monkey\Functions\expect('wp_die')
            ->once()
            ->with('Invalid product', 'Error', ['response' => 400]);
        
        // Call the method
        $this->form_handler->process_design_submission();
    }
    
    public function test_handle_ajax_request()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
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
        
        // Set up the AJAX request
        $_POST = [
            'nonce' => wp_create_nonce('ckpp_ajax_nonce'),
            'action' => 'ckpp_save_design',
            'product_id' => $product->get_id(),
            'design_data' => json_encode($design_data),
            'preview' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_ajax_nonce', 'nonce', false)
            ->andReturn(true);
            
        \Brain\Monkey\Functions\expect('wp_send_json_success')
            ->once()
            ->with(\Brain\Monkey\Functions::type('array'));
        
        // Call the method
        $this->form_handler->handle_ajax_request();
    }
}
