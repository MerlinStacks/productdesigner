<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Shortcode_Handler;

class ShortcodeHandlerTest extends IntegrationTestCase
{
    protected $shortcode_handler;
    protected $test_data;
    protected $user_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with customer role
        $this->user_id = $this->factory->user->create(['role' => 'customer']);
        wp_set_current_user($this->user_id);
        
        $this->shortcode_handler = new CKPP_Shortcode_Handler();
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
        
        // Remove all registered shortcodes
        remove_all_shortcodes();
        
        parent::tearDown();
    }
    
    public function test_register_shortcodes()
    {
        // Call the method
        $this->shortcode_handler->register_shortcodes();
        
        // Verify the shortcodes are registered
        global $shortcode_tags;
        $this->assertArrayHasKey('ckpp_design', $shortcode_tags);
        $this->assertArrayHasKey('ckpp_design_editor', $shortcode_tags);
        $this->assertArrayHasKey('ckpp_design_gallery', $shortcode_tags);
    }
    
    public function test_design_shortcode()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('shortcode_atts')
            ->once()
            ->with(
                [
                    'id' => 0,
                    'width' => '100%',
                    'height' => '500px',
                    'class' => '',
                ],
                ['id' => $design['post']->ID],
                'ckpp_design'
            )
            ->andReturn([
                'id' => $design['post']->ID,
                'width' => '100%',
                'height' => '500px',
                'class' => 'test-class',
            ]);
            
        \Brain\Monkey\Functions\expect('wp_enqueue_style')
            ->once()
            ->with('ckpp-frontend', \Brain\Monkey\Functions::type('string'), [], '1.0.0');
            
        \Brain\Monkey\Functions\expect('wp_enqueue_script')
            ->once()
            ->with('ckpp-frontend', \Brain\Monkey\Functions::type('string'), ['jquery'], '1.0.0', true);
        
        // Call the shortcode
        $output = $this->shortcode_handler->design_shortcode(['id' => $design['post']->ID]);
        
        // Verify the output
        $this->assertStringContainsString('class="ckpp-design-container test-class"', $output);
        $this->assertStringContainsString('data-design-id="' . $design['post']->ID . '"', $output);
    }
    
    public function test_design_editor_shortcode()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('shortcode_atts')
            ->once()
            ->with(
                [
                    'product_id' => 0,
                    'design_id' => 0,
                    'width' => '100%',
                    'height' => '800px',
                    'class' => '',
                ],
                ['product_id' => $product->get_id()],
                'ckpp_design_editor'
            )
            ->andReturn([
                'product_id' => $product->get_id(),
                'design_id' => 0,
                'width' => '100%',
                'height' => '800px',
                'class' => 'test-editor',
            ]);
            
        \Brain\Monkey\Functions\expect('wp_enqueue_style')
            ->once()
            ->with('ckpp-frontend', \Brain\Monkey\Functions::type('string'), [], '1.0.0');
            
        \Brain\Monkey\Functions\expect('wp_enqueue_script')
            ->once()
            ->with('ckpp-frontend', \Brain\Monkey\Functions::type('string'), ['jquery'], '1.0.0', true);
            
        \Brain\Monkey\Functions\expect('wp_localize_script')
            ->once()
            ->with('ckpp-frontend', 'ckpp_vars', \Brain\Monkey\Functions::type('array'));
        
        // Call the shortcode
        $output = $this->shortcode_handler->design_editor_shortcode(['product_id' => $product->get_id()]);
        
        // Verify the output
        $this->assertStringContainsString('class="ckpp-design-editor test-editor"', $output);
        $this->assertStringContainsString('data-product-id="' . $product->get_id() . '"', $output);
    }
    
    public function test_design_gallery_shortcode()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $designs = $this->test_data['designs'];
        $design_ids = array_map(function($design) {
            return $design['post']->ID;
        }, $designs);
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('shortcode_atts')
            ->once()
            ->with(
                [
                    'ids' => '',
                    'columns' => 3,
                    'limit' => 12,
                    'class' => '',
                ],
                ['ids' => implode(',', $design_ids)],
                'ckpp_design_gallery'
            )
            ->andReturn([
                'ids' => implode(',', $design_ids),
                'columns' => 3,
                'limit' => 12,
                'class' => 'test-gallery',
            ]);
            
        \Brain\Monkey\Functions\expect('wp_enqueue_style')
            ->once()
            ->with('ckpp-frontend', \Brain\Monkey\Functions::type('string'), [], '1.0.0');
            
        \Brain\Monkey\Functions\expect('wp_enqueue_script')
            ->once()
            ->with('ckpp-frontend', \Brain\Monkey\Functions::type('string'), ['jquery'], '1.0.0', true);
            
        // Mock the get_posts function
        \Brain\Monkey\Functions\expect('get_posts')
            ->once()
            ->with(\Brain\Monkey\Functions::type('array'))
            ->andReturn(array_map(function($design) {
                return $design['post'];
            }, $designs));
            
        // Mock the get_the_post_thumbnail function
        \Brain\Monkey\Functions\expect('get_the_post_thumbnail')
            ->times(count($designs))
            ->with(\Brain\Monkey\Functions::type('int'), 'medium')
            ->andReturn('<img src="test.jpg" alt="Test Design">');
            
        // Mock the get_permalink function
        \Brain\Monkey\Functions\expect('get_permalink')
            ->times(count($designs))
            ->with(\Brain\Monkey\Functions::type('int'))
            ->andReturn('http://example.com/design/test-design/');
        
        // Call the shortcode
        $output = $this->shortcode_handler->design_gallery_shortcode(['ids' => implode(',', $design_ids)]);
        
        // Verify the output
        $this->assertStringContainsString('class="ckpp-design-gallery test-gallery"', $output);
        $this->assertStringContainsString('class="ckpp-design-gallery__items columns-3"', $output);
        $this->assertStringContainsString('class="ckpp-design-gallery__item"', $output);
    }
}
