<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Admin;

class AdminTest extends IntegrationTestCase
{
    protected $admin;
    protected $test_data;
    protected $user_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = new CKPP_Admin();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create a test user with admin capabilities
        $this->user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->user_id);
        
        // Set up the admin screen
        set_current_screen('edit.php');
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
        
        // Reset the current screen
        $GLOBALS['current_screen'] = null;
        
        parent::tearDown();
    }
    
    public function test_enqueue_admin_scripts()
    {
        global $wp_scripts, $wp_styles;
        
        // Clear any existing scripts and styles
        $wp_scripts = new \WP_Scripts();
        $wp_styles = new \WP_Styles();
        
        // Set the current screen to our plugin's admin page
        set_current_screen('toplevel_page_customkings');
        
        // Call the method
        $this->admin->enqueue_admin_scripts('toplevel_page_customkings');
        
        // Check if the admin scripts and styles are enqueued
        $this->assertTrue(wp_script_is('ckpp-admin', 'enqueued'));
        $this->assertTrue(wp_style_is('ckpp-admin', 'enqueued'));
        
        // Check if the localization data is added
        $script_data = $wp_scripts->get_data('ckpp-admin', 'data');
        $this->assertStringContainsString('var ckpp_admin_vars', $script_data);
    }
    
    public function test_add_admin_menu()
    {
        global $menu, $submenu;
        
        // Clear any existing menu items
        $menu = [];
        $submenu = [];
        
        // Call the method
        $this->admin->add_admin_menu();
        
        // Check if the main menu was added
        $this->assertNotEmpty($menu);
        $this->assertStringContainsString('CustomKings', $menu[0][0]);
        
        // Check if the submenu items were added
        $this->assertArrayHasKey('edit.php?post_type=ckpp_design', $submenu);
        
        $found_dashboard = false;
        $found_designs = false;
        $found_settings = false;
        
        foreach ($submenu['edit.php?post_type=ckpp_design'] as $item) {
            if ($item[0] === 'Dashboard') {
                $found_dashboard = true;
            } elseif ($item[0] === 'Designs') {
                $found_designs = true;
            } elseif ($item[0] === 'Settings') {
                $found_settings = true;
            }
        }
        
        $this->assertTrue($found_dashboard);
        $this->assertTrue($found_designs);
        $this->assertTrue($found_settings);
    }
    
    public function test_render_admin_page()
    {
        // Call the method
        ob_start();
        $this->admin->render_admin_page();
        $output = ob_get_clean();
        
        // Check if the admin page contains the expected elements
        $this->assertStringContainsString('<div class="wrap">', $output);
        $this->assertStringContainsString('<h1>CustomKings Product Personalizer</h1>', $output);
        $this->assertStringContainsString('Welcome to CustomKings Product Personalizer', $output);
    }
    
    public function test_render_designs_page()
    {
        // Call the method
        ob_start();
        $this->admin->render_designs_page();
        $output = ob_get_clean();
        
        // Check if the designs page contains the expected elements
        $this->assertStringContainsString('<div class="wrap">', $output);
        $this->assertStringContainsString('<h1>Designs</h1>', $output);
        $this->assertStringContainsString('Add New Design', $output);
    }
    
    public function test_render_settings_page()
    {
        // Call the method
        ob_start();
        $this->admin->render_settings_page();
        $output = ob_get_clean();
        
        // Check if the settings page contains the expected elements
        $this->assertStringContainsString('<div class="wrap">', $output);
        $this->assertStringContainsString('<h1>Settings</h1>', $output);
        $this->assertStringContainsString('<form method="post" action="options.php">', $output);
    }
    
    public function test_register_settings()
    {
        global $wp_settings_sections, $wp_settings_fields;
        
        // Clear any existing settings
        $wp_settings_sections = [];
        $wp_settings_fields = [];
        
        // Call the method
        $this->admin->register_settings();
        
        // Check if the settings section was registered
        $this->assertArrayHasKey('ckpp_settings', $wp_settings_sections);
        
        // Check if the settings fields were registered
        $this->assertArrayHasKey('ckpp_settings', $wp_settings_fields);
        $this->assertArrayHasKey('ckpp_enable_customizer', $wp_settings_fields['ckpp_settings']['ckpp_settings_general']);
        $this->assertArrayHasKey('ckpp_default_width', $wp_settings_fields['ckpp_settings']['ckpp_settings_general']);
        $this->assertArrayHasKey('ckpp_default_height', $wp_settings_fields['ckpp_settings']['ckpp_settings_general']);
        $this->assertArrayHasKey('ckpp_google_fonts_api_key', $wp_settings_fields['ckpp_settings']['ckpp_settings_general']);
    }
    
    public function test_add_plugin_action_links()
    {
        $links = [
            '<a href="edit.php?post_type=ckpp_design">Manage Designs</a>'
        ];
        
        // Call the method
        $result = $this->admin->add_plugin_action_links($links);
        
        // Check if the settings link was added
        $this->assertCount(2, $result);
        $this->assertStringContainsString('Settings', $result[0]);
        $this->assertStringContainsString('page=ckpp-settings', $result[0]);
        $this->assertEquals($links[0], $result[1]);
    }
    
    public function test_add_plugin_meta_links()
    {
        $links = [
            '<a href="https://wordpress.org/plugins/customkings-product-personalizer/">View details</a>'
        ];
        
        // Call the method
        $result = $this->admin->add_plugin_meta_links($links, 'customkings-product-personalizer/customkings-product-personalizer.php');
        
        // Check if the documentation link was added
        $this->assertCount(2, $result);
        $this->assertStringContainsString('Documentation', $result[0]);
        $this->assertStringContainsString('https://docs.customkings.com', $result[0]);
        $this->assertEquals($links[0], $result[1]);
        
        // Test with a different plugin
        $result = $this->admin->add_plugin_meta_links($links, 'another-plugin/another-plugin.php');
        $this->assertEquals($links, $result);
    }
}
