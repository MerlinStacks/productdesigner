<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_WooCommerce_Integration;

class WooCommerceTest extends IntegrationTestCase
{
    protected $woocommerce_integration;
    protected $test_data;
    protected $user_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure WooCommerce is active
        if (!class_exists('WooCommerce')) {
            $this->markTestSkipped('WooCommerce is not active');
            return;
        }
        
        // Create a test user with customer role
        $this->user_id = $this->factory->user->create(['role' => 'customer']);
        wp_set_current_user($this->user_id);
        
        $this->woocommerce_integration = new CKPP_WooCommerce_Integration();
        $this->test_data = TestHelper::setup_test_data();
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
        
        // Clear the cart
        if (function_exists('WC') && WC()->cart) {
            WC()->cart->empty_cart();
        }
        
        parent::tearDown();
    }
    
    public function test_add_cart_item_data()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        $design = $this->test_data['designs'][0];
        
        // Set up the cart item data
        $cart_item_data = [];
        $form_data = [
            'ckpp_design_id' => $design['post']->ID,
            'ckpp_design_data' => json_encode([
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
            ])
        ];
        
        // Call the method
        $result = $this->woocommerce_integration->add_cart_item_data($cart_item_data, $product->get_id(), 0, $form_data);
        
        // Verify the result
        $this->assertArrayHasKey('ckpp_design_id', $result);
        $this->assertArrayHasKey('ckpp_design_data', $result);
        $this->assertEquals($design['post']->ID, $result['ckpp_design_id']);
        $this->assertIsArray(json_decode($result['ckpp_design_data'], true));
    }
    
    public function test_get_item_data()
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
        
        // Set up the cart item data
        $cart_item = [
            'ckpp_design_id' => $design['post']->ID,
            'ckpp_design_data' => json_encode($design_data)
        ];
        
        // Call the method
        $result = $this->woocommerce_integration->get_item_data([], $cart_item);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Design', $result[0]['name']);
        $this->assertStringContainsString('Custom Design', $result[0]['value']);
    }
    
    public function test_add_order_item_meta()
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
        
        // Create a mock order item
        $item = new \WC_Order_Item_Product();
        $item->update_meta_data('_ckpp_design_id', $design['post']->ID);
        $item->update_meta_data('_ckpp_design_data', json_encode($design_data));
        
        // Call the method
        $this->woocommerce_integration->add_order_item_meta($item->get_id(), $item, 0);
        
        // Verify the meta was added
        $saved_design_id = $item->get_meta('_ckpp_design_id');
        $saved_design_data = $item->get_meta('_ckpp_design_data');
        
        $this->assertEquals($design['post']->ID, $saved_design_id);
        $this->assertEquals($design_data, json_decode($saved_design_data, true));
    }
    
    public function test_add_to_cart_validation()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        $design = $this->test_data['designs'][0];
        
        // Test with valid design data
        $valid = $this->woocommerce_integration->validate_add_to_cart(
            true,
            $product->get_id(),
            1,
            [],
            [
                'ckpp_design_id' => $design['post']->ID,
                'ckpp_design_data' => json_encode([
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
                ])
            ]
        );
        
        $this->assertTrue($valid);
        
        // Test with missing design data
        $invalid = $this->woocommerce_integration->validate_add_to_cart(
            true,
            $product->get_id(),
            1,
            [],
            []
        );
        
        $this->assertInstanceOf('WP_Error', $invalid);
        $this->assertEquals('missing_design_data', $invalid->get_error_code());
    }
}
