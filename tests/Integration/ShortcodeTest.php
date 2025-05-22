<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Shortcodes;

class ShortcodeTest extends IntegrationTestCase
{
    protected $shortcodes;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->shortcodes = new CKPP_Shortcodes();
        $this->test_data = TestHelper::setup_test_data();
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        parent::tearDown();
    }
    
    public function test_register_shortcodes()
    {
        global $shortcode_tags;
        
        // Trigger the registration
        $this->shortcodes->register_shortcodes();
        
        // Verify the shortcodes are registered
        $this->assertArrayHasKey('ckpp_design', $shortcode_tags);
        $this->assertArrayHasKey('ckpp_design_button', $shortcode_tags);
    }
    
    public function test_design_shortcode()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        // Test with valid design ID
        $output = do_shortcode('[ckpp_design id="' . $design['post']->ID . '"]');
        
        // Verify the output contains the expected elements
        $this->assertStringContainsString('ckpp-design-container', $output);
        $this->assertStringContainsString('data-design-id="' . $design['post']->ID . '"', $output);
        
        // Test with invalid design ID
        $output = do_shortcode('[ckpp_design id="999999"]');
        $this->assertStringContainsString('Design not found', $output);
    }
    
    public function test_design_button_shortcode()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        $product = $this->test_data['products'][0];
        
        // Test with both design_id and product_id
        $output = do_shortcode('[ckpp_design_button design_id="' . $design['post']->ID . '" product_id="' . $product->get_id() . '"]');
        
        // Verify the output contains the expected elements
        $this->assertStringContainsString('ckpp-design-button', $output);
        $this->assertStringContainsString('data-design-id="' . $design['post']->ID . '"', $output);
        $this->assertStringContainsString('data-product-id="' . $product->get_id() . '"', $output);
        
        // Test with only product_id (should get design from product meta)
        $output = do_shortcode('[ckpp_design_button product_id="' . $product->get_id() . '"]');
        $this->assertStringContainsString('data-design-id="' . $design['post']->ID . '"', $output);
        
        // Test with invalid product_id
        $output = do_shortcode('[ckpp_design_button product_id="999999"]');
        $this->assertStringContainsString('Invalid product', $output);
    }
    
    public function test_design_shortcode_attributes() 
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        // Test with custom width and height
        $output = do_shortcode('[ckpp_design id="' . $design['post']->ID . '" width="500" height="300"]');
        $this->assertStringContainsString('width: 500px', $output);
        $this->assertStringContainsString('height: 300px', $output);
        
        // Test with custom class
        $output = do_shortcode('[ckpp_design id="' . $design['post']->ID . '" class="my-custom-class"]');
        $this->assertStringContainsString('class="ckpp-design-container my-custom-class"', $output);
    }
}
