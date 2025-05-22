<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CustomKings_Product_Personalizer;

class PluginTest extends IntegrationTestCase
{
    protected $plugin;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->plugin = CustomKings_Product_Personalizer::get_instance();
        $this->test_data = TestHelper::setup_test_data();
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Reset the plugin instance
        $reflection = new \ReflectionClass('CustomKings\CustomKings_Product_Personalizer');
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        $instance->setAccessible(false);
        
        parent::tearDown();
    }
    
    public function test_singleton_pattern()
    {
        $instance1 = CustomKings_Product_Personalizer::get_instance();
        $instance2 = CustomKings_Product_Personalizer::get_instance();
        
        $this->assertSame($instance1, $instance2, 'Multiple instances of the plugin class were created');
    }
    
    public function test_define_constants()
    {
        $this->assertTrue(defined('CKPP_PLUGIN_VERSION'));
        $this->assertTrue(defined('CKPP_PLUGIN_DIR'));
        $this->assertTrue(defined('CKPP_PLUGIN_URL'));
        $this->assertTrue(defined('CKPP_PLUGIN_BASENAME'));
        $this->assertTrue(defined('CKPP_PLUGIN_FILE'));
    }
    
    public function test_load_dependencies()
    {
        $this->assertTrue(class_exists('CustomKings\CKPP_Admin'));
        $this->assertTrue(class_exists('CustomKings\CKPP_Frontend'));
        $this->assertTrue(class_exists('CustomKings\CKPP_Design_Post_Type'));
        $this->assertTrue(class_exists('CustomKings\CKPP_Admin_Product_Settings'));
        $this->assertTrue(class_exists('CustomKings\CKPP_Frontend_Customizer'));
        $this->assertTrue(class_exists('CustomKings\CKPP_Shortcodes'));
        $this->assertTrue(class_exists('CustomKings\CKPP_REST_API'));
        $this->assertTrue(class_exists('CustomKings\CKPP_WooCommerce_Integration'));
    }
    
    public function test_init_hooks()
    {
        // Test admin hooks
        $this->assertEquals(10, has_action('admin_enqueue_scripts', [$this->plugin->admin, 'enqueue_scripts']));
        $this->assertEquals(10, has_action('admin_menu', [$this->plugin->admin, 'add_admin_menu']));
        $this->assertEquals(10, has_action('admin_init', [$this->plugin->admin, 'settings_init']));
        
        // Test frontend hooks
        $this->assertEquals(10, has_action('wp_enqueue_scripts', [$this->plugin->frontend, 'enqueue_scripts']));
        $this->assertEquals(10, has_action('woocommerce_before_add_to_cart_button', [$this->plugin->frontend, 'add_customizer_button']));
        
        // Test shortcodes
        $this->assertTrue(shortcode_exists('ckpp_design'));
        $this->assertTrue(shortcode_exists('ckpp_design_button'));
        
        // Test WooCommerce hooks
        $this->assertEquals(10, has_filter('woocommerce_add_cart_item_data', [$this->plugin->woocommerce, 'add_cart_item_data']));
        $this->assertEquals(10, has_filter('woocommerce_get_item_data', [$this->plugin->woocommerce, 'get_item_data']));
        $this->assertEquals(10, has_filter('woocommerce_add_order_item_meta', [$this->plugin->woocommerce, 'add_order_item_meta']));
    }
    
    public function test_activation()
    {
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('flush_rewrite_rules')
            ->once();
            
        // Call the activation method
        $this->plugin->activate();
        
        // Verify the design post type was registered
        $post_type = get_post_type_object('ckpp_design');
        $this->assertNotNull($post_type);
    }
    
    public function test_deactivation()
    {
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('flush_rewrite_rules')
            ->once();
            
        // Call the deactivation method
        $this->plugin->deactivate();
    }
    
    public function test_uninstall()
    {
        // Create test options and transients
        update_option('ckpp_settings', ['test' => 'value']);
        set_transient('ckpp_fonts', ['test' => 'value'], DAY_IN_SECONDS);
        
        // Call the uninstall method
        $this->plugin->uninstall();
        
        // Verify the options and transients were deleted
        $this->assertFalse(get_option('ckpp_settings'));
        $this->assertFalse(get_transient('ckpp_fonts'));
    }
    
    public function test_load_plugin_textdomain()
    {
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('load_plugin_textdomain')
            ->once()
            ->with(
                'customkings-product-personalizer',
                false,
                dirname(plugin_basename(CKPP_PLUGIN_FILE)) . '/languages/'
            )
            ->andReturn(true);
            
        // Call the method
        $this->plugin->load_plugin_textdomain();
    }
    
    public function test_run()
    {
        // Mock the necessary WordPress functions
        \Brain\Monkey\Actions\expectAdded('plugins_loaded')
            ->once()
            ->with([$this->plugin, 'load_plugin_textdomain']);
            
        \Brain\Monkey\Actions\expectAdded('init')
            ->once()
            ->with([$this->plugin, 'init']);
            
        // Call the run method
        $this->plugin->run();
    }
    
    public function test_init()
    {
        // Call the init method
        $this->plugin->init();
        
        // Verify the design post type was registered
        $post_type = get_post_type_object('ckpp_design');
        $this->assertNotNull($post_type);
        
        // Verify the REST API was initialized
        $this->assertInstanceOf('CustomKings\\CKPP_REST_API', $this->plugin->rest_api);
    }
}
