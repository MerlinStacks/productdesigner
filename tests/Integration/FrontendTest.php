<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Frontend;

class FrontendTest extends IntegrationTestCase
{
    protected $frontend;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->frontend = new CKPP_Frontend();
        $this->test_data = TestHelper::setup_test_data();
        
        // Set up the current user as a customer
        $this->user_id = $this->factory->user->create(['role' => 'customer']);
        wp_set_current_user($this->user_id);
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
        
        parent::tearDown();
    }
    
    public function test_enqueue_scripts()
    {
        global $wp_scripts, $wp_styles;
        
        // Clear any existing scripts and styles
        $wp_scripts = new \WP_Scripts();
        $wp_styles = new \WP_Styles();
        
        // Set up the query to be a single product page
        global $wp_query, $post;
        
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        
        // Set the global post to the product
        $post = get_post($product->get_id());
        setup_postdata($post);
        
        // Set the query to be a single product
        $wp_query->is_single = true;
        $wp_query->is_singular = true;
        $wp_query->is_archive = false;
        $wp_query->is_page = false;
        $wp_query->queried_object = $post;
        $wp_query->queried_object_id = $product->get_id();
        $wp_query->post = $post;
        $wp_query->posts = [$post];
        
        // Enable personalization for the product
        update_post_meta($product->get_id(), '_ckpp_enable_personalization', 'yes');
        
        // Call the method
        $this->frontend->enqueue_scripts();
        
        // Check if the scripts and styles are enqueued
        $this->assertTrue(wp_script_is('ckpp-frontend', 'enqueued'));
        $this->assertTrue(wp_script_is('fabric', 'enqueued'));
        $this->assertTrue(wp_style_is('ckpp-frontend', 'enqueued'));
        
        // Check if the localization data is added
        $script_data = $wp_scripts->get_data('ckpp-frontend', 'data');
        $this->assertStringContainsString('var ckpp_vars', $script_data);
        
        // Clean up
        wp_reset_postdata();
    }
    
    public function test_add_customizer_button()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        
        // Enable personalization for the product
        update_post_meta($product->get_id(), '_ckpp_enable_personalization', 'yes');
        
        // Set the global product
        global $product;
        $product = wc_get_product($product->get_id());
        
        // Capture the output
        ob_start();
        $this->frontend->add_customizer_button();
        $output = ob_get_clean();
        
        // Verify the output contains the customizer button
        $this->assertStringContainsString('class="ckpp-customizer-button button"', $output);
        $this->assertStringContainsString('data-product-id="' . $product->get_id() . '"', $output);
    }
    
    public function test_add_customizer_modal()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        
        // Set the global product
        global $product;
        $product = wc_get_product($product->get_id());
        
        // Capture the output
        ob_start();
        $this->frontend->add_customizer_modal();
        $output = ob_get_clean();
        
        // Verify the output contains the customizer modal
        $this->assertStringContainsString('id="ckpp-customizer-modal"', $output);
        $this->assertStringContainsString('class="ckpp-customizer-container"', $output);
        $this->assertStringContainsString('class="ckpp-toolbar"', $output);
        $this->assertStringContainsString('class="ckpp-canvas-container"', $output);
        $this->assertStringContainsString('class="ckpp-sidebar"', $output);
    }
    
    public function test_add_to_cart_validation()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        $design = $this->test_data['designs'][0];
        
        // Enable personalization for the product
        update_post_meta($product->get_id(), '_ckpp_enable_personalization', 'yes');
        
        // Test with valid design data
        $valid = $this->frontend->validate_add_to_cart(
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
            ]
        );
        
        $this->assertTrue($valid);
        
        // Test with missing design data
        $valid = $this->frontend->validate_add_to_cart(true, $product->get_id(), 1, [], []);
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
        $cart_item_data = $this->frontend->add_cart_item_data($cart_item_data, 1, 1, [
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
        
        $result = $this->frontend->get_item_data($item_data, $cart_item);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Design', $result[0]['name']);
        $this->assertStringContainsString('Custom Design', $result[0]['value']);
    }
}
