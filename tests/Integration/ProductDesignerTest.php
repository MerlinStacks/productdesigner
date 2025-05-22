<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\CKPP_Product_Designer;

class ProductDesignerTest extends IntegrationTestCase
{
    protected $designer;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->designer = new CKPP_Product_Designer();
    }
    
    public function test_design_cpt_registration()
    {
        // Trigger the registration of the custom post type
        $this->designer->register_design_cpt();
        
        // Verify the post type is registered
        $post_type = get_post_type_object('ckpp_design');
        $this->assertNotNull($post_type);
        $this->assertEquals('Design', $post_type->labels->singular_name);
        $this->assertEquals('Designs', $post_type->labels->name);
    }
    
    public function test_admin_menu_items_added()
    {
        global $submenu;
        
        // Clear any existing menu items
        $submenu = [];
        
        // Trigger the menu registration
        $this->designer->add_admin_menu();
        
        // Verify the menu items were added
        $this->assertArrayHasKey('edit.php?post_type=ckpp_design', $submenu);
    }
    
    public function test_ajax_save_design()
    {
        // Create a test user with admin capabilities
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        
        // Set up the test data
        $_POST = [
            'title' => 'Test Design',
            'config' => '{"elements":[]}',
            'preview' => 'data:image/png;base64,iVBOR',
            'nonce' => wp_create_nonce('ckpp_designer_nonce')
        ];
        
        // Mock the necessary WordPress functions
        \Brain\Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('ckpp_designer_nonce', 'nonce', false)
            ->andReturn(true);
            
        // Call the method directly
        $this->designer->ajax_save_design();
        
        // Get the response
        $response = json_decode($this->getActualOutput(), true);
        
        // Verify the response
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('designId', $response['data']);
        $this->assertArrayHasKey('message', $response['data']);
        
        // Clean up
        wp_delete_post($response['data']['designId'], true);
    }
}
