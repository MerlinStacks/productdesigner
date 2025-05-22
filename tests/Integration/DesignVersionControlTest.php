<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Version_Control;
use Brain\Monkey\Functions;

class DesignVersionControlTest extends IntegrationTestCase
{
    protected $version_control;
    protected $test_data;
    protected $admin_user_id;
    protected $customer_user_id;
    protected $design_id;
    protected $version_data = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->admin_user_id = $this->factory->user->create([
            'role' => 'administrator',
            'user_email' => 'admin@example.com',
            'display_name' => 'Admin User'
        ]);
        
        $this->customer_user_id = $this->factory->user->create([
            'role' => 'customer',
            'user_email' => 'customer@example.com',
            'display_name' => 'Test Customer'
        ]);
        
        // Set current user as admin
        wp_set_current_user($this->admin_user_id);
        
        $this->version_control = new CKPP_Design_Version_Control();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create a test design
        $this->design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'publish',
            'post_title' => 'Test Design for Version Control',
            'post_content' => 'Initial design content',
            'post_author' => $this->customer_user_id,
        ]);
        
        // Set up initial design meta
        update_post_meta($this->design_id, '_design_data', [
            'elements' => [
                ['type' => 'text', 'content' => 'Initial Text', 'x' => 100, 'y' => 100]
            ],
            'settings' => [
                'width' => 800,
                'height' => 600,
                'backgroundColor' => '#ffffff'
            ]
        ]);
        
        // Create some test versions
        $this->create_test_versions();
        
        // Mock WordPress functions
        Functions\when('current_time')
            ->justReturn('2023-01-01 12:00:00');
            
        Functions\when('wp_generate_uuid4')
            ->alias(function() {
                static $counter = 0;
                $counter++;
                return 'version-' . $counter;
            });
    }
    
    protected function create_test_versions()
    {
        // Version 1: Initial version
        $this->version_data['v1'] = $this->version_control->create_version(
            $this->design_id,
            [
                'elements' => [
                    ['type' => 'text', 'content' => 'Initial Text', 'x' => 100, 'y' => 100]
                ],
                'settings' => [
                    'width' => 800,
                    'height' => 600,
                    'backgroundColor' => '#ffffff'
                ]
            ],
            'Initial version',
            $this->customer_user_id
        );
        
        // Version 2: Updated text
        $this->version_data['v2'] = $this->version_control->create_version(
            $this->design_id,
            [
                'elements' => [
                    ['type' => 'text', 'content' => 'Updated Text', 'x' => 100, 'y' => 100]
                ],
                'settings' => [
                    'width' => 800,
                    'height' => 600,
                    'backgroundColor' => '#ffffff'
                ]
            ],
            'Updated text content',
            $this->customer_user_id
        );
        
        // Version 3: Added image
        $this->version_data['v3'] = $this->version_control->create_version(
            $this->design_id,
            [
                'elements' => [
                    ['type' => 'text', 'content' => 'Updated Text', 'x' => 100, 'y' => 100],
                    ['type' => 'image', 'url' => 'http://example.com/image.jpg', 'x' => 200, 'y' => 200, 'width' => 100, 'height' => 100]
                ],
                'settings' => [
                    'width' => 800,
                    'height' => 600,
                    'backgroundColor' => '#ffffff'
                ]
            ],
            'Added image element',
            $this->admin_user_id
        );
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up test data
        if ($this->design_id) {
            // Delete all versions first
            $versions = $this->version_control->get_versions($this->design_id);
            foreach ($versions as $version) {
                $this->version_control->delete_version($version['id']);
            }
            
            // Then delete the design
            wp_delete_post($this->design_id, true);
        }
        
        // Clean up users
        if (isset($this->admin_user_id)) {
            wp_delete_user($this->admin_user_id);
        }
        
        if (isset($this->customer_user_id)) {
            wp_delete_user($this->customer_user_id);
        }
        
        parent::tearDown();
    }
    
    public function test_create_version()
    {
        // Test creating a new version
        $version_data = [
            'elements' => [
                ['type' => 'text', 'content' => 'New Version', 'x' => 50, 'y' => 50]
            ],
            'settings' => [
                'width' => 800,
                'height' => 600,
                'backgroundColor' => '#f0f0f0'
            ]
        ];
        
        $version = $this->version_control->create_version(
            $this->design_id,
            $version_data,
            'Created a new version with updated text',
            $this->customer_user_id
        );
        
        // Verify the version was created correctly
        $this->assertIsArray($version);
        $this->assertArrayHasKey('id', $version);
        $this->assertArrayHasKey('design_id', $version);
        $this->assertEquals($this->design_id, $version['design_id']);
        $this->assertEquals('Created a new version with updated text', $version['description']);
        $this->assertEquals($this->customer_user_id, $version['user_id']);
        $this->assertArrayHasKey('created_at', $version);
        
        // Verify the version data was stored correctly
        $this->assertArrayHasKey('elements', $version['data']);
        $this->assertCount(1, $version['data']['elements']);
        $this->assertEquals('New Version', $version['data']['elements'][0]['content']);
    }
    
    public function test_get_versions()
    {
        // Get all versions for the design
        $versions = $this->version_control->get_versions($this->design_id);
        
        // Verify we got the expected number of versions
        $this->assertCount(3, $versions);
        
        // Verify the versions are in the correct order (newest first)
        $this->assertEquals('Added image element', $versions[0]['description']);
        $this->assertEquals('Updated text content', $versions[1]['description']);
        $this->assertEquals('Initial version', $versions[2]['description']);
        
        // Verify each version has the expected structure
        foreach ($versions as $version) {
            $this->assertArrayHasKey('id', $version);
            $this->assertArrayHasKey('design_id', $version);
            $this->assertArrayHasKey('description', $version);
            $this->assertArrayHasKey('user_id', $version);
            $this->assertArrayHasKey('created_at', $version);
            $this->assertArrayHasKey('data', $version);
        }
    }
    
    public function test_get_version()
    {
        // Get a specific version by ID
        $versions = $this->version_control->get_versions($this->design_id);
        $version_id = $versions[1]['id']; // Get the middle version
        
        $version = $this->version_control->get_version($version_id);
        
        // Verify we got the correct version
        $this->assertIsArray($version);
        $this->assertEquals($version_id, $version['id']);
        $this->assertEquals('Updated text content', $version['description']);
        $this->assertEquals($this->customer_user_id, $version['user_id']);
        
        // Test getting a non-existent version
        $non_existent = $this->version_control->get_version('non-existent-version');
        $this->assertNull($non_existent);
    }
    
    public function test_restore_version()
    {
        // Get the second version (which has updated text)
        $versions = $this->version_control->get_versions($this->design_id);
        $version_to_restore = $versions[1]; // Second version (updated text)
        
        // Restore the version
        $result = $this->version_control->restore_version(
            $version_to_restore['id'],
            $this->admin_user_id,
            'Restored previous version with updated text'
        );
        
        // Verify the restore was successful
        $this->assertTrue($result);
        
        // Get the updated design data
        $design_data = get_post_meta($this->design_id, '_design_data', true);
        
        // Verify the design data was restored correctly
        $this->assertIsArray($design_data);
        $this->assertArrayHasKey('elements', $design_data);
        $this->assertCount(1, $design_data['elements']);
        $this->assertEquals('Updated Text', $design_data['elements'][0]['content']);
        
        // Verify a new version was created for the restore
        $versions_after_restore = $this->version_control->get_versions($this->design_id);
        $this->assertCount(4, $versions_after_restore);
        $this->assertEquals('Restored previous version with updated text', $versions_after_restore[0]['description']);
    }
    
    public function test_delete_version()
    {
        // Get a version to delete
        $versions = $this->version_control->get_versions($this->design_id);
        $version_to_delete = $versions[1]; // Second version
        $version_id = $version_to_delete['id'];
        
        // Delete the version
        $result = $this->version_control->delete_version($version_id);
        
        // Verify the delete was successful
        $this->assertTrue($result);
        
        // Verify the version no longer exists
        $deleted_version = $this->version_control->get_version($version_id);
        $this->assertNull($deleted_version);
        
        // Verify the version was removed from the list
        $remaining_versions = $this->version_control->get_versions($this->design_id);
        $this->assertCount(2, $remaining_versions);
        $this->assertNotContains($version_id, array_column($remaining_versions, 'id'));
    }
    
    public function test_get_version_count()
    {
        // Get the version count
        $count = $this->version_control->get_version_count($this->design_id);
        
        // Verify the count is correct
        $this->assertEquals(3, $count);
        
        // Test with a non-existent design
        $non_existent_count = $this->version_control->get_version_count(999999);
        $this->assertEquals(0, $non_existent_count);
    }
    
    public function test_get_latest_version()
    {
        // Get the latest version
        $latest = $this->version_control->get_latest_version($this->design_id);
        
        // Verify we got the latest version
        $this->assertIsArray($latest);
        $this->assertEquals('Added image element', $latest['description']);
        $this->assertEquals($this->admin_user_id, $latest['user_id']);
        
        // Verify the version has the expected data
        $this->assertArrayHasKey('elements', $latest['data']);
        $this->assertCount(2, $latest['data']['elements']);
        $this->assertEquals('image', $latest['data']['elements'][1]['type']);
    }
}
