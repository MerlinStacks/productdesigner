<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Assets;

class AssetsTest extends IntegrationTestCase
{
    protected $assets;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->assets = new CKPP_Assets();
        $this->test_data = TestHelper::setup_test_data();
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        parent::tearDown();
    }
    
    public function test_enqueue_frontend_assets()
    {
        // Set up the test
        global $post;
        
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        $post = get_post($product->get_id());
        
        // Enable personalization for the product
        update_post_meta($product->get_id(), '_ckpp_enable_personalization', 'yes');
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('wp_enqueue_style')
            ->once()
            ->with('ckpp-frontend', \Brain\Monkey\Functions\type('string'), [], '1.0.0');
            
        \Brain\Monkey\Functions\expect('wp_enqueue_script')
            ->once()
            ->with('ckpp-frontend', \Brain\Monkey\Functions\type('string'), ['jquery'], '1.0.0', true);
            
        \Brain\Monkey\Functions\expect('wp_localize_script')
            ->once()
            ->with('ckpp-frontend', 'ckpp_vars', \Brain\Monkey\Functions\type('array'));
        
        // Call the method
        $this->assets->enqueue_frontend_assets();
    }
    
    public function test_enqueue_admin_assets()
    {
        // Set the current screen to the plugin's settings page
        set_current_screen('toplevel_page_ckpp-settings');
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('wp_enqueue_style')
            ->once()
            ->with('ckpp-admin', \Brain\Monkey\Functions::type('string'), [], '1.0.0');
            
        \Brain\Monkey\Functions\expect('wp_enqueue_script')
            ->once()
            ->with('ckpp-admin', \Brain\Monkey\Functions::type('string'), ['jquery'], '1.0.0', true);
            
        \Brain\Monkey\Functions\expect('wp_localize_script')
            ->once()
            ->with('ckpp-admin', 'ckpp_admin_vars', \Brain\Monkey\Functions::type('array'));
        
        // Call the method
        $this->assets->enqueue_admin_assets('toplevel_page_ckpp-settings');
    }
    
    public function test_enqueue_block_editor_assets()
    {
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('wp_enqueue_script')
            ->once()
            ->with('ckpp-block-editor', \Brain\Monkey\Functions::type('string'), ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-components'], '1.0.0', true);
            
        \Brain\Monkey\Functions\expect('wp_localize_script')
            ->once()
            ->with('ckpp-block-editor', 'ckpp_block_editor_vars', \Brain\Monkey\Functions::type('array'));
            
        \Brain\Monkey\Functions\expect('register_block_type')
            ->once()
            ->with('ckpp/design-preview', \Brain\Monkey\Functions::type('array'));
        
        // Call the method
        $this->assets->enqueue_block_editor_assets();
    }
    
    public function test_get_asset_file_path()
    {
        // Test with a valid asset
        $js_path = $this->assets->get_asset_file_path('js/frontend.js');
        $this->assertStringEndsWith('assets/js/frontend.js', $js_path);
        
        // Test with a non-existent asset
        $non_existent = $this->assets->get_asset_file_path('non-existent.js');
        $this->assertFalse($non_existent);
    }
}
