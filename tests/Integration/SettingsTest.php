<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Admin_Settings;

class SettingsTest extends IntegrationTestCase
{
    protected $admin_settings;
    protected $test_data;
    protected $user_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with administrator role
        $this->user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->user_id);
        
        $this->admin_settings = new CKPP_Admin_Settings();
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
        
        // Clean up options
        delete_option('ckpp_settings');
        
        parent::tearDown();
    }
    
    public function test_add_admin_menu()
    {
        global $submenu, $menu;
        
        // Clear any existing menu items
        $submenu = [];
        $menu = [];
        
        // Call the method
        $this->admin_settings->add_admin_menu();
        
        // Verify the menu was added
        $this->assertArrayHasKey('edit.php?post_type=ckpp_design', $submenu);
        
        // Find the settings page in the submenu
        $settings_found = false;
        foreach ($submenu['edit.php?post_type=ckpp_design'] as $item) {
            if ($item[2] === 'ckpp-settings') {
                $settings_found = true;
                break;
            }
        }
        
        $this->assertTrue($settings_found, 'Settings page was not added to the menu');
    }
    
    public function test_settings_init()
    {
        global $wp_settings_sections, $wp_settings_fields;
        
        // Clear any existing settings
        $wp_settings_sections = [];
        $wp_settings_fields = [];
        
        // Call the method
        $this->admin_settings->settings_init();
        
        // Verify the settings section was added
        $this->assertArrayHasKey('ckpp_settings', $wp_settings_sections);
        $this->assertEquals('General Settings', $wp_settings_sections['ckpp_settings']['title']);
        
        // Verify the settings fields were added
        $this->assertArrayHasKey('ckpp_settings', $wp_settings_fields);
        $this->assertArrayHasKey('ckpp_enable_customizer', $wp_settings_fields['ckpp_settings']['ckpp_settings_general']);
        $this->assertArrayHasKey('ckpp_default_width', $wp_settings_fields['ckpp_settings']['ckpp_settings_general']);
        $this->assertArrayHasKey('ckpp_default_height', $wp_settings_fields['ckpp_settings']['ckpp_settings_general']);
    }
    
    public function test_settings_page()
    {
        // Set up the request
        $_GET['page'] = 'ckpp-settings';
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('settings_fields')
            ->once()
            ->with('ckpp_settings')
            ->andReturn('');
            
        \Brain\Monkey\Functions\expect('do_settings_sections')
            ->once()
            ->with('ckpp_settings')
            ->andReturn('');
            
        \Brain\Monkey\Functions\expect('submit_button')
            ->once()
            ->andReturn('');
        
        // Capture the output
        ob_start();
        $this->admin_settings->settings_page();
        $output = ob_get_clean();
        
        // Verify the output contains the expected elements
        $this->assertStringContainsString('<div class="wrap">', $output);
        $this->assertStringContainsString('<h1>CustomKings Product Personalizer - Settings</h1>', $output);
        $this->assertStringContainsString('<form method="post" action="options.php">', $output);
    }
    
    public function test_sanitize_settings()
    {
        // Set up the input data
        $input = [
            'enable_customizer' => '1',
            'default_width' => '800',
            'default_height' => '600',
            'google_fonts_api_key' => 'test-api-key',
        ];
        
        // Call the method
        $result = $this->admin_settings->sanitize_settings($input);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertEquals('1', $result['enable_customizer']);
        $this->assertEquals(800, $result['default_width']);
        $this->assertEquals(600, $result['default_height']);
        $this->assertEquals('test-api-key', $result['google_fonts_api_key']);
        
        // Test with invalid input
        $input = [
            'enable_customizer' => 'invalid',
            'default_width' => 'invalid',
            'default_height' => 'invalid',
            'google_fonts_api_key' => '<script>alert("xss")</script>',
        ];
        
        // Call the method
        $result = $this->admin_settings->sanitize_settings($input);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertEquals('0', $result['enable_customizer']);
        $this->assertEquals(800, $result['default_width']); // Default value
        $this->assertEquals(600, $result['default_height']); // Default value
        $this->assertEquals('', $result['google_fonts_api_key']); // XSS cleaned
    }
    
    public function test_get_settings()
    {
        // Set up the test data
        $settings = [
            'enable_customizer' => '1',
            'default_width' => '800',
            'default_height' => '600',
            'google_fonts_api_key' => 'test-api-key',
        ];
        
        update_option('ckpp_settings', $settings);
        
        // Call the method
        $result = $this->admin_settings->get_settings();
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertEquals('1', $result['enable_customizer']);
        $this->assertEquals('800', $result['default_width']);
        $this->assertEquals('600', $result['default_height']);
        $this->assertEquals('test-api-key', $result['google_fonts_api_key']);
    }
}
