<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Admin_Settings;

class AdminSettingsTest extends IntegrationTestCase
{
    protected $admin_settings;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up admin user with appropriate capabilities
        $this->admin_user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_user_id);
        
        $this->admin_settings = new CKPP_Admin_Settings();
        $this->test_data = TestHelper::setup_test_data();
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up the test user
        if (isset($this->admin_user_id)) {
            wp_delete_user($this->admin_user_id);
        }
        
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
        $this->assertStringContainsString('<h1>CustomKings Product Personalizer Settings</h1>', $output);
        $this->assertStringContainsString('<form method="post" action="options.php">', $output);
    }
    
    public function test_sanitize_settings()
    {
        $input = [
            'ckpp_enable_customizer' => '1',
            'ckpp_default_width' => '800',
            'ckpp_default_height' => '600',
            'ckpp_custom_css' => 'body { background: #fff; }',
            'ckpp_google_fonts_api_key' => 'test-api-key-123',
        ];
        
        $result = $this->admin_settings->sanitize_settings($input);
        
        $this->assertEquals('1', $result['ckpp_enable_customizer']);
        $this->assertEquals(800, $result['ckpp_default_width']);
        $this->assertEquals(600, $result['ckpp_default_height']);
        $this->assertEquals('body { background: #fff; }', $result['ckpp_custom_css']);
        $this->assertEquals('test-api-key-123', $result['ckpp_google_fonts_api_key']);
        
        // Test with invalid width/height
        $input = [
            'ckpp_default_width' => 'abc',
            'ckpp_default_height' => '-100',
        ];
        
        $result = $this->admin_settings->sanitize_settings($input);
        
        $this->assertEquals(800, $result['ckpp_default_width']); // Should revert to default
        $this->assertEquals(600, $result['ckpp_default_height']); // Should revert to default
    }
    
    public function test_enable_customizer_callback()
    {
        // Capture the output
        ob_start();
        $this->admin_settings->enable_customizer_callback();
        $output = ob_get_clean();
        
        // Verify the output contains the expected elements
        $this->assertStringContainsString('<input type="checkbox"', $output);
        $this->assertStringContainsString('name="ckpp_settings[ckpp_enable_customizer]"', $output);
        $this->assertStringContainsString('Enable Product Customizer', $output);
    }
    
    public function test_default_dimensions_callback()
    {
        // Capture the output for width
        ob_start();
        $this->admin_settings->default_dimensions_callback('width');
        $width_output = ob_get_clean();
        
        // Verify the width output
        $this->assertStringContainsString('<input type="number"', $width_output);
        $this->assertStringContainsString('name="ckpp_settings[ckpp_default_width]"', $width_output);
        $this->assertStringContainsString('value="800"', $width_output);
        $this->assertStringContainsString('placeholder="800"', $width_output);
        
        // Capture the output for height
        ob_start();
        $this->admin_settings->default_dimensions_callback('height');
        $height_output = ob_get_clean();
        
        // Verify the height output
        $this->assertStringContainsString('<input type="number"', $height_output);
        $this->assertStringContainsString('name="ckpp_settings[ckpp_default_height]"', $height_output);
        $this->assertStringContainsString('value="600"', $height_output);
        $this->assertStringContainsString('placeholder="600"', $height_output);
    }
    
    public function test_custom_css_callback()
    {
        // Capture the output
        ob_start();
        $this->admin_settings->custom_css_callback();
        $output = ob_get_clean();
        
        // Verify the output contains the expected elements
        $this->assertStringContainsString('<textarea', $output);
        $this->assertStringContainsString('name="ckpp_settings[ckpp_custom_css]"', $output);
        $this->assertStringContainsString('rows="10"', $output);
        $this->assertStringContainsString('class="large-text code"', $output);
    }
    
    public function test_google_fonts_api_key_callback()
    {
        // Set a test API key
        update_option('ckpp_settings', ['ckpp_google_fonts_api_key' => 'test-api-key-123']);
        
        // Capture the output
        ob_start();
        $this->admin_settings->google_fonts_api_key_callback();
        $output = ob_get_clean();
        
        // Verify the output contains the expected elements
        $this->assertStringContainsString('<input type="text"', $output);
        $this->assertStringContainsString('name="ckpp_settings[ckpp_google_fonts_api_key]"', $output);
        $this->assertStringContainsString('value="test-api-key-123"', $output);
        $this->assertStringContainsString('class="regular-text"', $output);
        
        // Clean up
        delete_option('ckpp_settings');
    }
    
    public function test_add_settings_link()
    {
        $links = [
            '<a href="edit.php?post_type=ckpp_design">Manage Designs</a>'
        ];
        
        $result = $this->admin_settings->add_settings_link($links);
        
        $this->assertCount(2, $result);
        $this->assertStringContainsString('Settings', $result[0]);
        $this->assertStringContainsString('ckpp-settings', $result[0]);
        $this->assertEquals('<a href="edit.php?post_type=ckpp_design">Manage Designs</a>', $result[1]);
    }
}
