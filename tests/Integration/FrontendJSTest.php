<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\Tests\Helpers\JSMockHelper;
use CustomKings\CKPP_Frontend;

class FrontendJSTest extends IntegrationTestCase
{
    protected $frontend;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->frontend = new \CustomKings\CKPP_Frontend();
        $this->test_data = TestHelper::setup_test_data();
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        parent::tearDown();
    }
    
    public function test_enqueue_scripts()
    {
        // Mock the necessary WordPress functions
        JSMockHelper::mockEnqueue('customkings-product-personalizer');
        JSMockHelper::mockEnqueue('customkings-fabric', 'script');
        JSMockHelper::mockEnqueue('customkings-frontend', 'style');
        
        // Mock the localization
        JSMockHelper::mockLocalizedScript(
            'customkings-product-personalizer',
            'ckpp_vars',
            [
                'ajax_url' => 'http://example.org/wp-admin/admin-ajax.php',
                'nonce' => 'test-nonce',
                'i18n' => [
                    'error' => 'An error occurred. Please try again.',
                    'saving' => 'Saving...',
                    'saved' => 'Design saved!',
                ],
            ]
        );
        
        // Call the method
        $this->frontend->enqueue_scripts();
        
        // Verify the script was enqueued with dependencies
        $this->assertTrue(\Brain\Monkey\Actions\did('wp_enqueue_scripts'));
    }
    
    public function test_localize_script()
    {
        // Set up test data
        $product = $this->test_data['products'][0];
        $design = $this->test_data['designs'][0];
        
        // Set up the global post
        global $post;
        $post = get_post($product->get_id());
        setup_postdata($post);
        
        // Mock the necessary WordPress functions
        JSMockHelper::mockLocalizedScript(
            'customkings-product-personalizer',
            'ckpp_vars',
            [
                'ajax_url' => 'http://example.org/wp-admin/admin-ajax.php',
                'nonce' => 'test-nonce',
                'product_id' => $product->get_id(),
                'design_id' => $design['post']->ID,
                'i18n' => [
                    'error' => 'An error occurred. Please try again.',
                    'saving' => 'Saving...',
                    'saved' => 'Design saved!',
                ],
            ]
        );
        
        // Call the method
        $this->frontend->localize_script();
        
        // Clean up
        wp_reset_postdata();
    }
    
    public function test_add_to_cart_validation()
    {
        // Mock the necessary WordPress functions
        JSMockHelper::mockAjaxRequest(
            'ckpp_validate_design',
            [
                'success' => true,
                'data' => [
                    'valid' => true,
                    'message' => 'Design is valid',
                ],
            ]
        );
        
        // Mock the add to cart functionality
        \Brain\Monkey\Functions\expect('WC')
            ->once()
            ->andReturn((object) [
                'cart' => (object) [
                    'add_to_cart' => function() { return true; },
                ],
            ]);
        
        // Call the method
        $result = $this->frontend->validate_design_before_add_to_cart(true, 1, 1, [], []);
        
        // Verify the result
        $this->assertTrue($result);
    }
    
    public function test_design_preview_shortcode()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        // Mock the necessary WordPress functions
        JSMockHelper::mockEnqueue('customkings-product-personalizer');
        JSMockHelper::mockLocalizedScript(
            'customkings-product-personalizer',
            'ckpp_vars',
            [
                'ajax_url' => 'http://example.org/wp-admin/admin-ajax.php',
                'nonce' => 'test-nonce',
                'design_id' => $design['post']->ID,
                'i18n' => [
                    'error' => 'An error occurred. Please try again.',
                    'saving' => 'Saving...',
                    'saved' => 'Design saved!',
                ],
            ]
        );
        
        // Call the shortcode
        $output = $this->frontend->design_preview_shortcode(['id' => $design['post']->ID]);
        
        // Verify the output
        $this->assertStringContainsString('ckpp-design-container', $output);
        $this->assertStringContainsString('data-design-id="' . $design['post']->ID . '"', $output);
    }
}
