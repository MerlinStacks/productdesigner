<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_WooCommerce_Integration;

class WooCommerceIntegrationTest extends IntegrationTestCase
{
    protected $woocommerce_integration;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure WooCommerce is active
        if (!class_exists('WooCommerce')) {
            $this->markTestSkipped('WooCommerce is not active');
            return;
        }
        
        $this->woocommerce_integration = new CKPP_WooCommerce_Integration();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create a test customer
        $this->customer_id = $this->factory->user->create([
            'role' => 'customer',
            'user_email' => 'test.customer@example.com',
        ]);
        
        wp_set_current_user($this->customer_id);
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up the test customer
        if (isset($this->customer_id)) {
            wp_delete_user($this->customer_id);
        }
        
        // Clear the cart
        WC()->cart->empty_cart();
        
        parent::tearDown();
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
        $valid = $this->woocommerce_integration->validate_add_to_cart(true, $product->get_id(), 1, [], [
            'ckpp_design_id' => $design['post']->ID,
            'ckpp_design_data' => json_encode([
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
            ])
        ]);
        
        $this->assertTrue($valid);
        
        // Test with missing design data
        $valid = $this->woocommerce_integration->validate_add_to_cart(true, $product->get_id(), 1, [], []);
        $this->assertInstanceOf('WP_Error', $valid);
        $this->assertEquals('missing_design_data', $valid->get_error_code());
    }
    
    public function test_add_cart_item_data()
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
                    'content' => 'Test Text',
                    'position' => ['x' => 100, 'y' => 100],
                    'styles' => [
                        'color' => '#000000',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                    ]
                ]
            ]
        ];
        
        $cart_item_data = [];
        $cart_item_data = $this->woocommerce_integration->add_cart_item_data($cart_item_data, 1, 1, [
            'ckpp_design_id' => $design['post']->ID,
            'ckpp_design_data' => json_encode($design_data)
        ]);
        
        $this->assertArrayHasKey('ckpp_design_id', $cart_item_data);
        $this->assertArrayHasKey('ckpp_design_data', $cart_item_data);
        $this->assertEquals($design['post']->ID, $cart_item_data['ckpp_design_id']);
        $this->assertEquals(json_encode($design_data), $cart_item_data['ckpp_design_data']);
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
                    'content' => 'Test Text',
                    'position' => ['x' => 100, 'y' => 100],
                    'styles' => [
                        'color' => '#000000',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                    ]
                ]
            ]
        ];
        
        $item_data = [];
        $cart_item = [
            'ckpp_design_id' => $design['post']->ID,
            'ckpp_design_data' => json_encode($design_data)
        ];
        
        $result = $this->woocommerce_integration->get_item_data($item_data, $cart_item);
        
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
                    'content' => 'Test Text',
                    'position' => ['x' => 100, 'y' => 100],
                    'styles' => [
                        'color' => '#000000',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                    ]
                ]
            ]
        ];
        
        $item_id = 1;
        $values = [
            'ckpp_design_id' => $design['post']->ID,
            'ckpp_design_data' => json_encode($design_data)
        ];
        
        // Mock the necessary functions
        \Brain\Monkey\Functions\expect('wc_add_order_item_meta')
            ->once()
            ->with($item_id, '_ckpp_design_id', $design['post']->ID, true)
            ->andReturn(true);
            
        \Brain\Monkey\Functions\expect('wc_add_order_item_meta')
            ->once()
            ->with($item_id, '_ckpp_design_data', $values['ckpp_design_data'], true)
            ->andReturn(true);
        
        $this->woocommerce_integration->add_order_item_meta($item_id, $values);
    }
    
    public function test_order_item_display_meta()
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
                    'content' => 'Test Text',
                    'position' => ['x' => 100, 'y' => 100],
                    'styles' => [
                        'color' => '#000000',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                    ]
                ]
            ]
        ];
        
        $formatted_meta = [];
        $item = new \WC_Order_Item_Product();
        $item->add_meta_data('_ckpp_design_id', $design['post']->ID, true);
        $item->add_meta_data('_ckpp_design_data', json_encode($design_data), true);
        
        $result = $this->woocommerce_integration->order_item_display_meta($formatted_meta, $item);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Design', $result[0]['key']);
        $this->assertStringContainsString('Custom Design', $result[0]['value']);
    }
    
    public function test_add_cart_preview()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        $design = $this->test_data['designs'][0];
        $design_data = [
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
        ];
        
        $cart_item = [
            'product_id' => $product->get_id(),
            'ckpp_design_id' => $design['post']->ID,
            'ckpp_design_data' => json_encode($design_data)
        ];
        
        ob_start();
        $this->woocommerce_integration->add_cart_preview($cart_item, 'test-cart-item-key');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('ckpp-cart-item-preview', $output);
        $this->assertStringContainsString('data-design-id="' . $design['post']->ID . '"', $output);
        $this->assertStringContainsString('data-product-id="' . $product->get_id() . '"', $output);
    }
}
