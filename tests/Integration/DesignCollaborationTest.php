<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Collaboration;
use Brain\Monkey\Functions;

class DesignCollaborationTest extends IntegrationTestCase
{
    protected $collaboration;
    protected $test_data;
    protected $admin_user_id;
    protected $editor_user_id;
    protected $design_id;
    protected $comment_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->admin_user_id = $this->factory->user->create(['role' => 'administrator']);
        $this->editor_user_id = $this->factory->user->create([
            'role' => 'editor',
            'user_email' => 'editor@example.com',
            'display_name' => 'Editor User'
        ]);
        
        // Set current user as admin
        wp_set_current_user($this->admin_user_id);
        
        $this->collaboration = new CKPP_Design_Collaboration();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create a test design
        $this->design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'publish',
            'post_title' => 'Test Design for Collaboration',
            'post_author' => $this->admin_user_id,
        ]);
        
        // Add test comment
        $this->comment_id = wp_insert_comment([
            'comment_post_ID' => $this->design_id,
            'user_id' => $this->admin_user_id,
            'comment_content' => 'Test comment',
            'comment_approved' => 1,
        ]);
        
        // Mock WordPress functions
        Functions\when('wp_send_json_success')
            ->alias(function($data) {
                return ['success' => true, 'data' => $data];
            });
            
        Functions\when('wp_send_json_error')
            ->alias(function($message, $code = 400) {
                return ['success' => false, 'message' => $message, 'code' => $code];
            });
            
        Functions\when('wp_mail')
            ->justReturn(true);
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
        
        if ($this->comment_id) {
            wp_delete_comment($this->comment_id, true);
        }
        
        // Clean up users
        if (isset($this->admin_user_id)) {
            wp_delete_user($this->admin_user_id);
        }
        
        if (isset($this->editor_user_id)) {
            wp_delete_user($this->editor_user_id);
        }
        
        parent::tearDown();
    }
    
    public function test_add_design_collaborator()
    {
        // Add editor as collaborator
        $result = $this->collaboration->add_collaborator($this->design_id, $this->editor_user_id, 'edit');
        
        // Verify the result
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('collaborator', $result['data']);
        $this->assertEquals($this->editor_user_id, $result['data']['collaborator']['id']);
        $this->assertEquals('edit', $result['data']['collaborator']['role']);
        
        // Verify the collaborator was added to post meta
        $collaborators = get_post_meta($this->design_id, '_design_collaborators', true);
        $this->assertIsArray($collaborators);
        $this->assertArrayHasKey($this->editor_user_id, $collaborators);
        $this->assertEquals('edit', $collaborators[$this->editor_user_id]['role']);
    }
    
    public function test_remove_design_collaborator()
    {
        // First add a collaborator
        $this->collaboration->add_collaborator($this->design_id, $this->editor_user_id, 'edit');
        
        // Now remove the collaborator
        $result = $this->collaboration->remove_collaborator($this->design_id, $this->editor_user_id);
        
        // Verify the result
        $this->assertTrue($result['success']);
        
        // Verify the collaborator was removed from post meta
        $collaborators = get_post_meta($this->design_id, '_design_collaborators', true);
        $this->assertEmpty($collaborators);
    }
    
    public function test_user_can_edit_design()
    {
        // Admin should be able to edit
        $this->assertTrue($this->collaboration->user_can_edit_design($this->design_id, $this->admin_user_id));
        
        // Editor should not be able to edit yet
        $this->assertFalse($this->collaboration->user_can_edit_design($this->design_id, $this->editor_user_id));
        
        // Add editor as collaborator
        $this->collaboration->add_collaborator($this->design_id, $this->editor_user_id, 'edit');
        
        // Now editor should be able to edit
        $this->assertTrue($this->collaboration->user_can_edit_design($this->design_id, $this->editor_user_id));
    }
    
    public function test_add_design_comment()
    {
        // Add a comment
        $comment_data = [
            'design_id' => $this->design_id,
            'content' => 'This is a test comment',
            'x_position' => 100,
            'y_position' => 200,
            'resolved' => false,
        ];
        
        $result = $this->collaboration->add_comment($comment_data, $this->admin_user_id);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('comment', $result['data']);
        
        // Verify the comment was created
        $comment = get_comment($result['data']['comment']['id']);
        $this->assertEquals($this->design_id, $comment->comment_post_ID);
        $this->assertEquals($this->admin_user_id, $comment->user_id);
        $this->assertEquals('This is a test comment', $comment->comment_content);
        
        // Clean up
        wp_delete_comment($comment->comment_ID, true);
    }
    
    public function test_resolve_comment()
    {
        // First add a comment
        $comment_id = wp_insert_comment([
            'comment_post_ID' => $this->design_id,
            'user_id' => $this->admin_user_id,
            'comment_content' => 'Comment to resolve',
            'comment_approved' => 1,
        ]);
        
        // Resolve the comment
        $result = $this->collaboration->resolve_comment($comment_id, $this->admin_user_id);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        
        // Verify the comment was marked as resolved
        $comment_meta = get_comment_meta($comment_id, '_resolved', true);
        $this->assertEquals('1', $comment_meta);
        
        // Verify the resolver user ID was stored
        $resolver_id = get_comment_meta($comment_id, '_resolved_by', true);
        $this->assertEquals($this->admin_user_id, $resolver_id);
        
        // Clean up
        wp_delete_comment($comment_id, true);
    }
    
    public function test_send_collaboration_invitation()
    {
        // Mock the email function
        $email_sent = false;
        $test_email = '';
        $test_subject = '';
        
        Functions\when('wp_mail')
            ->alias(function($to, $subject, $message) use (&$email_sent, &$test_email, &$test_subject) {
                $email_sent = true;
                $test_email = $to;
                $test_subject = $subject;
                return true;
            });
        
        // Send an invitation
        $result = $this->collaboration->send_invitation([
            'design_id' => $this->design_id,
            'email' => 'test@example.com',
            'role' => 'edit',
            'message' => 'Please collaborate on this design',
        ], $this->admin_user_id);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        
        // Verify the email was sent
        $this->assertTrue($email_sent);
        $this->assertEquals('test@example.com', $test_email);
        $this->assertStringContainsString('invitation to collaborate', $test_subject);
    }
    
    public function test_get_design_activity()
    {
        // Add some test activity
        update_post_meta($this->design_id, '_design_activity', [
            [
                'type' => 'edit',
                'user_id' => $this->admin_user_id,
                'timestamp' => time() - 3600, // 1 hour ago
                'details' => 'Updated design elements',
            ],
            [
                'type' => 'comment',
                'user_id' => $this->editor_user_id,
                'timestamp' => time() - 1800, // 30 minutes ago
                'details' => 'Added a comment',
            ],
        ]);
        
        // Get the activity
        $activity = $this->collaboration->get_design_activity($this->design_id);
        
        // Verify the result
        $this->assertIsArray($activity);
        $this->assertCount(2, $activity);
        
        // Verify the structure of each activity item
        foreach ($activity as $item) {
            $this->assertArrayHasKey('type', $item);
            $this->assertArrayHasKey('user_id', $item);
            $this->assertArrayHasKey('timestamp', $item);
            $this->assertArrayHasKey('details', $item);
            $this->assertArrayHasKey('user', $item);
            $this->assertArrayHasKey('time_ago', $item);
        }
        
        // Verify the activity is sorted by timestamp (newest first)
        $this->assertGreaterThanOrEqual(
            $activity[1]['timestamp'],
            $activity[0]['timestamp']
        );
    }
}
