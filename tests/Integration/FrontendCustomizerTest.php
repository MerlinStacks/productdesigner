<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Frontend_Customizer;

class FrontendCustomizerTest extends IntegrationTestCase
{
    protected $customizer;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->customizer = new CKPP_Frontend_Customizer();
        $this->test_data = TestHelper::setup_test_data();
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        parent::tearDown();
    }
    
    public function test_ajax_get_config()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        
        // Set up the request
        $_POST = [
            'product_id' => $product->get_id(),
            'nonce' => wp_create_nonce('ckpp_customizer_nonce'),
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_customizer_nonce', 'nonce', false)
            ->andReturn(true);
            
        // Call the method
        ob_start();
        $this->customizer->ajax_get_config();
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Verify the response
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('config', $response['data']);
        $this->assertArrayHasKey('design_id', $response['data']);
    }
    
    public function test_ajax_add_to_cart()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        $design = $this->test_data['designs'][0];
        
        // Set up the request
        $_POST = [
            'product_id' => $product->get_id(),
            'design_id' => $design['post']->ID,
            'design_data' => json_encode([
                'elements' => [
                    [
                        'type' => 'text',
                        'content' => 'Test Text',
                        'position' => ['x' => 100, 'y' => 100],
                        'styles' => [
                            'color' => '#000000',
                            'fontSize' => 24,
                            'fontFamily' => 'Arial',
                        ]
                    ]
                ]
            ]),
            'nonce' => wp_create_nonce('ckpp_customizer_nonce'),
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_customizer_nonce', 'nonce', false)
            ->andReturn(true);
            
        // Mock WooCommerce cart functions
        $cart = WC()->cart;
        $this->assertEmpty($cart->get_cart());
        
        // Call the method
        ob_start();
        $this->customizer->ajax_add_to_cart();
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Verify the response
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('redirect', $response['data']);
        
        // Verify the cart
        $cart_items = $cart->get_cart();
        $this->assertNotEmpty($cart_items);
        
        // Clean up
        $cart->empty_cart();
    }
}
