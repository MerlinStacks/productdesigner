<?php

namespace CustomKings\Tests\Unit;

use CustomKings\Tests\TestCase\UnitTestCase;
use CustomKings\CKPP_Admin_Notices;
use Brain\Monkey\Functions;

class AdminNoticesTest extends UnitTestCase
{
    protected $admin_notices;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->admin_notices = new CKPP_Admin_Notices();
    }
    
    public function test_add_notice()
    {
        // Add a notice
        $this->admin_notices->add_notice('test_notice', 'This is a test notice', 'success');
        
        // Get the notices
        $notices = $this->get_private_property($this->admin_notices, 'notices');
        
        // Verify the notice was added
        $this->assertArrayHasKey('test_notice', $notices);
        $this->assertEquals('This is a test notice', $notices['test_notice']['message']);
        $this->assertEquals('success', $notices['test_notice']['type']);
    }
    
    public function test_remove_notice()
    {
        // Add a notice
        $this->admin_notices->add_notice('test_notice', 'This is a test notice', 'success');
        
        // Remove the notice
        $this->admin_notices->remove_notice('test_notice');
        
        // Get the notices
        $notices = $this->get_private_property($this->admin_notices, 'notices');
        
        // Verify the notice was removed
        $this->assertArrayNotHasKey('test_notice', $notices);
    }
    
    public function test_display_notices()
    {
        // Add a notice
        $this->admin_notices->add_notice('test_notice', 'This is a test notice', 'success');
        
        // Mock the WordPress functions
        Functions\expect('esc_html__')
            ->once()
            ->andReturn('Dismiss');
            
        Functions\expect('wp_nonce_field')
            ->once()
            ->andReturn('<input type="hidden" name="_wpnonce" value="test" />');
        
        // Capture the output
        ob_start();
        $this->admin_notices->display_notices();
        $output = ob_get_clean();
        
        // Verify the output contains the notice
        $this->assertStringContainsString('This is a test notice', $output);
        $this->assertStringContainsString('notice-success', $output);
    }
    
    public function test_handle_dismiss()
    {
        // Set up the request
        $_POST['ckpp_dismiss_notice'] = 'test_notice';
        $_POST['_wpnonce'] = wp_create_nonce('ckpp_dismiss_notice');
        
        // Add a notice
        $this->admin_notices->add_notice('test_notice', 'This is a test notice', 'success');
        
        // Call the method
        $this->admin_notices->handle_dismiss();
        
        // Get the notices
        $notices = $this->get_private_property($this->admin_notices, 'notices');
        
        // Verify the notice was dismissed
        $this->assertArrayNotHasKey('test_notice', $notices);
    }
    
    /**
     * Helper method to access private/protected properties
     */
    protected function get_private_property($object, $property)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
