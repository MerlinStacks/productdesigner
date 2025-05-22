<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Access_Control;
use Brain\Monkey\Functions;

class DesignAccessControlTest extends IntegrationTestCase
{
    protected $access_control;
    protected $test_data;
    protected $admin_user_id;
    protected $editor_user_id;
    protected $customer_user_id;
    protected $other_customer_id;
    protected $design_id;
    protected $private_design_id;
    protected $restricted_design_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with different roles
        $this->admin_user_id = $this->factory->user->create([
            'role' => 'administrator',
            'user_email' => 'admin@example.com',
            'display_name' => 'Admin User'
        ]);
        
        $this->editor_user_id = $this->factory->user->create([
            'role' => 'editor',
            'user_email' => 'editor@example.com',
            'display_name' => 'Editor User'
        ]);
        
        $this->customer_user_id = $this->factory->user->create([
            'role' => 'customer',
            'user_email' => 'customer@example.com',
            'display_name' => 'Test Customer'
        ]);
        
        $this->other_customer_id = $this->factory->user->create([
            'role' => 'customer',
            'user_email' => 'other@example.com',
            'display_name' => 'Other Customer'
        ]);
        
        // Set current user as admin
        wp_set_current_user($this->admin_user_id);
        
        $this->access_control = new CKPP_Design_Access_Control();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create test designs with different access levels
        $this->design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'publish',
            'post_title' => 'Public Design',
            'post_author' => $this->customer_user_id,
        ]);
        
        $this->private_design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'private',
            'post_title' => 'Private Design',
            'post_author' => $this->customer_user_id,
        ]);
        
        $this->restricted_design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'publish',
            'post_title' => 'Restricted Design',
            'post_author' => $this->customer_user_id,
        ]);
        
        // Set up restricted access
        update_post_meta($this->restricted_design_id, '_access_restriction', 'restricted');
        update_post_meta($this->restricted_design_id, '_allowed_roles', ['editor']);
        
        // Mock WordPress functions
        Functions\when('current_user_can')
            ->alias(function($capability, ...$args) {
                // Default WordPress capabilities check
                if ($capability === 'read_private_ckpp_designs') {
                    $user = wp_get_current_user();
                    return in_array('administrator', $user->roles) || 
                           in_array('editor', $user->roles);
                }
                
                // Default to true for other capabilities to avoid permission issues
                return true;
            });
            
        Functions\when('wp_send_json_error')
            ->alias(function($message, $code = 403) {
                return ['success' => false, 'message' => $message, 'code' => $code];
            });
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up test data
        if ($this->design_id) {
            wp_delete_post($this->design_id, true);
        }
        
        if ($this->private_design_id) {
            wp_delete_post($this->private_design_id, true);
        }
        
        if ($this->restricted_design_id) {
            wp_delete_post($this->restricted_design_id, true);
        }
        
        // Clean up users
        $users = [
            $this->admin_user_id,
            $this->editor_user_id,
            $this->customer_user_id,
            $this->other_customer_id
        ];
        
        foreach ($users as $user_id) {
            if (isset($user_id)) {
                wp_delete_user($user_id);
            }
        }
        
        parent::tearDown();
    }
    
    public function test_can_view_design()
    {
        // Public design - anyone can view
        $this->assertTrue($this->access_control->can_view_design($this->design_id));
        
        // Private design - only owner and admins/editors can view
        $this->assertTrue($this->access_control->can_view_design($this->private_design_id, $this->customer_user_id));
        $this->assertTrue($this->access_control->can_view_design($this->private_design_id, $this->admin_user_id));
        $this->assertFalse($this->access_control->can_view_design($this->private_design_id, $this->other_customer_id));
        
        // Restricted design - only specified roles can view
        $this->assertTrue($this->access_control->can_view_design($this->restricted_design_id, $this->editor_user_id));
        $this->assertTrue($this->access_control->can_view_design($this->restricted_design_id, $this->admin_user_id));
        $this->assertFalse($this->access_control->can_view_design($this->restricted_design_id, $this->other_customer_id));
    }
    
    public function test_can_edit_design()
    {
        // Owner can edit their own design
        $this->assertTrue($this->access_control->can_edit_design($this->design_id, $this->customer_user_id));
        
        // Admin can edit any design
        $this->assertTrue($this->access_control->can_edit_design($this->design_id, $this->admin_user_id));
        
        // Other users cannot edit
        $this->assertFalse($this->access_control->can_edit_design($this->design_id, $this->other_customer_id));
        
        // Test with explicit capability check
        $this->assertTrue($this->access_control->can_edit_design($this->design_id, $this->editor_user_id, 'edit_others_ckpp_designs'));
    }
    
    public function test_can_delete_design()
    {
        // Owner can delete their own design
        $this->assertTrue($this->access_control->can_delete_design($this->design_id, $this->customer_user_id));
        
        // Admin can delete any design
        $this->assertTrue($this->access_control->can_delete_design($this->design_id, $this->admin_user_id));
        
        // Other users cannot delete
        $this->assertFalse($this->access_control->can_delete_design($this->design_id, $this->other_customer_id));
    }
    
    public function test_restrict_design_access()
    {
        // Restrict access to editors only
        $result = $this->access_control->restrict_design_access(
            $this->design_id,
            'restricted',
            ['editor']
        );
        
        $this->assertTrue($result);
        
        // Verify the restriction was saved
        $access_restriction = get_post_meta($this->design_id, '_access_restriction', true);
        $allowed_roles = get_post_meta($this->design_id, '_allowed_roles', true);
        
        $this->assertEquals('restricted', $access_restriction);
        $this->assertEquals(['editor'], $allowed_roles);
        
        // Test with invalid restriction type
        $this->assertFalse(
            $this->access_control->restrict_design_access($this->design_id, 'invalid_type')
        );
    }
    
    public function test_get_design_access_level()
    {
        // Test public design
        $access = $this->access_control->get_design_access_level($this->design_id);
        $this->assertEquals('public', $access['type']);
        
        // Test private design
        $access = $this->access_control->get_design_access_level($this->private_design_id);
        $this->assertEquals('private', $access['type']);
        
        // Test restricted design
        $access = $this->access_control->get_design_access_level($this->restricted_design_id);
        $this->assertEquals('restricted', $access['type']);
        $this->assertEquals(['editor'], $access['allowed_roles']);
    }
    
    public function test_validate_design_access() 
    {
        // Test public design access
        $this->assertTrue(
            $this->access_control->validate_design_access($this->design_id, $this->other_customer_id)
        );
        
        // Test private design access (owner)
        $this->assertTrue(
            $this->access_control->validate_design_access($this->private_design_id, $this->customer_user_id)
        );
        
        // Test private design access (non-owner)
        $this->assertInstanceOf(
            'WP_Error',
            $this->access_control->validate_design_access($this->private_design_id, $this->other_customer_id)
        );
        
        // Test restricted design access (allowed role)
        $this->assertTrue(
            $this->access_control->validate_design_access($this->restricted_design_id, $this->editor_user_id)
        );
        
        // Test restricted design access (disallowed role)
        $error = $this->access_control->validate_design_access($this->restricted_design_id, $this->other_customer_id);
        $this->assertInstanceOf('WP_Error', $error);
        $this->assertEquals('access_denied', $error->get_error_code());
    }
    
    public function test_filter_designs_by_access()
    {
        // Create a query to test
        $query = new \WP_Query([
            'post_type' => 'ckpp_design',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        
        // Test as admin (should see all designs)
        wp_set_current_user($this->admin_user_id);
        $filtered = $this->access_control->filter_designs_by_access(clone $query);
        $this->assertContains($this->design_id, $filtered->posts);
        $this->assertContains($this->private_design_id, $filtered->posts);
        $this->assertContains($this->restricted_design_id, $filtered->posts);
        
        // Test as editor (should see public, restricted, and their own private)
        wp_set_current_user($this->editor_user_id);
        $filtered = $this->access_control->filter_designs_by_access(clone $query);
        $this->assertContains($this->design_id, $filtered->posts);
        $this->assertContains($this->restricted_design_id, $filtered->posts);
        
        // Test as owner (should see public and their own private)
        wp_set_current_user($this->customer_user_id);
        $filtered = $this->access_control->filter_designs_by_access(clone $query);
        $this->assertContains($this->design_id, $filtered->posts);
        $this->assertContains($this->private_design_id, $filtered->posts);
        
        // Test as other customer (should only see public)
        wp_set_current_user($this->other_customer_id);
        $filtered = $this->access_control->filter_designs_by_access(clone $query);
        $this->assertContains($this->design_id, $filtered->posts);
        $this->assertNotContains($this->private_design_id, $filtered->posts);
        $this->assertNotContains($this->restricted_design_id, $filtered->posts);
    }
    
    public function test_get_accessible_designs()
    {
        // Test as admin (should see all designs)
        $designs = $this->access_control->get_accessible_designs($this->admin_user_id);
        $this->assertContains($this->design_id, $designs);
        $this->assertContains($this->private_design_id, $designs);
        $this->assertContains($this->restricted_design_id, $designs);
        
        // Test as editor (should see public and restricted)
        $designs = $this->access_control->get_accessible_designs($this->editor_user_id);
        $this->assertContains($this->design_id, $designs);
        $this->assertContains($this->restricted_design_id, $designs);
        $this->assertNotContains($this->private_design_id, $designs);
        
        // Test as owner (should see public and their own private)
        $designs = $this->access_control->get_accessible_designs($this->customer_user_id);
        $this->assertContains($this->design_id, $designs);
        $this->assertContains($this->private_design_id, $designs);
        $this->assertNotContains($this->restricted_design_id, $designs);
        
        // Test as other customer (should only see public)
        $designs = $this->access_control->get_accessible_designs($this->other_customer_id);
        $this->assertContains($this->design_id, $designs);
        $this->assertNotContains($this->private_design_id, $designs);
        $this->assertNotContains($this->restricted_design_id, $designs);
    }
}
