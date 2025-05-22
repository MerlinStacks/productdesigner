<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Shortcodes;

class ShortcodesTest extends IntegrationTestCase
{
    protected $shortcodes;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->shortcodes = new CKPP_Shortcodes();
        $this->test_data = TestHelper::setup_test_data();
        
        // Register the shortcodes
        $this->shortcodes->register_shortcodes();
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Unregister the shortcodes
        remove_shortcode('ckpp_design');
        remove_shortcode('ckpp_design_button');
        
        parent::tearDown();
    }
    
    public function test_design_shortcode()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        // Test with design ID
        $output = do_shortcode('[ckpp_design id="' . $design['post']->ID . '"]');
        $this->assertStringContainsString('class="ckpp-design-container"', $output);
        $this->assertStringContainsString('data-design-id="' . $design['post']->ID . '"', $output);
        
        // Test with custom class
        $output = do_shortcode('[ckpp_design id="' . $design['post']->ID . '" class="custom-class"]');
        $this->assertStringContainsString('class="ckpp-design-container custom-class"', $output);
        
        // Test with custom width and height
        $output = do_shortcode('[ckpp_design id="' . $design['post']->ID . '" width="500" height="300"]');
        $this->assertStringContainsString('width="500"', $output);
        $this->assertStringContainsString('height="300"', $output);
        
        // Test with invalid design ID
        $output = do_shortcode('[ckpp_design id="99999"]');
        $this->assertEquals('', $output);
    }
    
    public function test_design_button_shortcode()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        $product = $this->test_data['products'][0];
        
        // Test with product ID and design ID
        $output = do_shortcode('[ckpp_design_button product_id="' . $product->get_id() . '" design_id="' . $design['post']->ID . '"]');
        $this->assertStringContainsString('class="ckpp-design-button button"', $output);
        $this->assertStringContainsString('data-product-id="' . $product->get_id() . '"', $output);
        $this->assertStringContainsString('data-design-id="' . $design['post']->ID . '"', $output);
        
        // Test with custom text and class
        $output = do_shortcode('[ckpp_design_button product_id="' . $product->get_id() . '" design_id="' . $design['post']->ID . '" text="Customize Now" class="custom-button"]');
        $this->assertStringContainsString('>Customize Now</button>', $output);
        $this->assertStringContainsString('class="ckpp-design-button button custom-button"', $output);
        
        // Test with invalid product ID
        $output = do_shortcode('[ckpp_design_button product_id="99999" design_id="' . $design['post']->ID . '"]');
        $this->assertStringContainsString('Invalid product', $output);
        
        // Test with invalid design ID
        $output = do_shortcode('[ckpp_design_button product_id="' . $product->get_id() . '" design_id="99999"]');
        $this->assertStringContainsString('Invalid design', $output);
    }
    
    public function test_shortcode_attributes()
    {
        $defaults = [
            'id' => 0,
            'product_id' => 0,
            'design_id' => 0,
            'width' => '',
            'height' => '',
            'class' => '',
            'text' => 'Customize',
        ];
        
        // Test default attributes
        $attributes = $this->shortcodes->shortcode_atts($defaults, []);
        $this->assertEquals($defaults, $attributes);
        
        // Test with custom attributes
        $custom_attrs = [
            'id' => '123',
            'width' => '500',
            'height' => '300',
            'class' => 'custom-class',
            'custom_attr' => 'value',
        ];
        
        $expected = $defaults;
        $expected['id'] = 123;
        $expected['width'] = '500';
        $expected['height'] = '300';
        $expected['class'] = 'custom-class';
        
        $attributes = $this->shortcodes->shortcode_atts($defaults, $custom_attrs);
        $this->assertEquals($expected, $attributes);
    }
    
    public function test_shortcode_rendering()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        $product = $this->test_data['products'][0];
        
        // Test design shortcode rendering
        $output = $this->shortcodes->design_shortcode(['id' => $design['post']->ID]);
        $this->assertStringContainsString('class="ckpp-design-container"', $output);
        $this->assertStringContainsString('data-design-id="' . $design['post']->ID . '"', $output);
        
        // Test design button shortcode rendering
        $output = $this->shortcodes->design_button_shortcode([
            'product_id' => $product->get_id(),
            'design_id' => $design['post']->ID,
            'text' => 'Customize Design',
            'class' => 'custom-button',
        ]);
        
        $this->assertStringContainsString('class="ckpp-design-button button custom-button"', $output);
        $this->assertStringContainsString('data-product-id="' . $product->get_id() . '"', $output);
        $this->assertStringContainsString('data-design-id="' . $design['post']->ID . '"', $output);
        $this->assertStringContainsString('>Customize Design</button>', $output);
        
        // Test with invalid product ID
        $output = $this->shortcodes->design_button_shortcode([
            'product_id' => 99999,
            'design_id' => $design['post']->ID,
        ]);
        $this->assertStringContainsString('Invalid product', $output);
        
        // Test with invalid design ID
        $output = $this->shortcodes->design_button_shortcode([
            'product_id' => $product->get_id(),
            'design_id' => 99999,
        ]);
        $this->assertStringContainsString('Invalid design', $output);
    }
}
