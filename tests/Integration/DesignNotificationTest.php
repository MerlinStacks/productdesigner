<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Notifications;
use Brain\Monkey\Functions;

class DesignNotificationTest extends IntegrationTestCase
{
    protected $notifications;
    protected $test_data;
    protected $admin_user_id;
    protected $customer_user_id;
    protected $design_id;
    
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
        
        $this->notifications = new CKPP_Design_Notifications();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create a test design
        $this->design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'pending',
            'post_title' => 'Test Design for Notifications',
            'post_author' => $this->customer_user_id,
        ]);
        
        // Mock WordPress functions
        $this->email_sent = false;
        $this->email_to = '';
        $this->email_subject = '';
        $this->email_message = '';
        $this->email_headers = '';
        
        Functions\when('wp_mail')
            ->alias(function($to, $subject, $message, $headers = '') {
                $this->email_sent = true;
                $this->email_to = $to;
                $this->email_subject = $subject;
                $this->email_message = $message;
                $this->email_headers = $headers;
                return true;
            });
            
        Functions\when('get_bloginfo')
            ->justReturn('Test Site');
            
        Functions\when('home_url')
            ->justReturn('http://example.com');
            
        Functions\when('get_edit_post_link')
            ->justReturn('http://example.com/wp-admin/post.php?post=' . $this->design_id . '&action=edit');
            
        Functions\when('get_permalink')
            ->justReturn('http://example.com/designs/test-design');
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
        
        // Clean up users
        if (isset($this->admin_user_id)) {
            wp_delete_user($this->admin_user_id);
        }
        
        if (isset($this->customer_user_id)) {
            wp_delete_user($this->customer_user_id);
        }
        
        parent::tearDown();
    }
    
    public function test_send_design_submitted_notification()
    {
        // Reset email tracking
        $this->reset_email_tracking();
        
        // Send design submitted notification
        $result = $this->notifications->send_design_submitted_notification(
            $this->design_id,
            $this->customer_user_id
        );
        
        // Verify the result
        $this->assertTrue($result);
        
        // Verify email was sent to admin
        $this->assertTrue($this->email_sent);
        $this->assertStringContainsString('admin@example.com', $this->email_to);
        $this->assertStringContainsString('New Design Submitted for Approval', $this->email_subject);
        $this->assertStringContainsString('A new design has been submitted for approval', $this->email_message);
        $this->assertStringContainsString('Test Design for Notifications', $this->email_message);
        $this->assertStringContainsString('Test Customer', $this->email_message);
        $this->assertStringContainsString(
            'http://example.com/wp-admin/post.php?post=' . $this->design_id . '&action=edit',
            $this->email_message
        );
    }
    
    public function test_send_design_approved_notification()
    {
        // Reset email tracking
        $this->reset_email_tracking();
        
        // Send design approved notification
        $result = $this->notifications->send_design_approved_notification(
            $this->design_id,
            $this->admin_user_id,
            'Great job on the design!'
        );
        
        // Verify the result
        $this->assertTrue($result);
        
        // Verify email was sent to customer
        $this->assertTrue($this->email_sent);
        $this->assertStringContainsString('customer@example.com', $this->email_to);
        $this->assertStringContainsString('Your Design Has Been Approved', $this->email_subject);
        $this->assertStringContainsString('Your design has been approved', $this->email_message);
        $this->assertStringContainsString('Test Design for Notifications', $this->email_message);
        $this->assertStringContainsString('Great job on the design!', $this->email_message);
        $this->assertStringContainsString(
            'http://example.com/designs/test-design',
            $this->email_message
        );
    }
    
    public function test_send_design_rejected_notification()
    {
        // Reset email tracking
        $this->reset_email_tracking();
        
        // Send design rejected notification
        $result = $this->notifications->send_design_rejected_notification(
            $this->design_id,
            $this->admin_user_id,
            'Please make the following changes',
            'The colors need to match our brand guidelines.'
        );
        
        // Verify the result
        $this->assertTrue($result);
        
        // Verify email was sent to customer
        $this->assertTrue($this->email_sent);
        $this->assertStringContainsString('customer@example.com', $this->email_to);
        $this->assertStringContainsString('Your Design Requires Changes', $this->email_subject);
        $this->assertStringContainsString('Your design requires some changes', $this->email_message);
        $this->assertStringContainsString('Test Design for Notifications', $this->email_message);
        $this->assertStringContainsString('The colors need to match our brand guidelines', $this->email_message);
        $this->assertStringContainsString(
            'http://example.com/designs/test-design',
            $this->email_message
        );
    }
    
    public function test_send_design_comment_notification()
    {
        // Reset email tracking
        $this->reset_email_tracking();
        
        // Create a test comment
        $comment_id = $this->factory->comment->create([
            'comment_post_ID' => $this->design_id,
            'user_id' => $this->admin_user_id,
            'comment_content' => 'Have you considered using a different font?',
            'comment_approved' => 1,
        ]);
        
        // Send comment notification
        $result = $this->notifications->send_design_comment_notification(
            $comment_id,
            $this->design_id,
            $this->admin_user_id,
            $this->customer_user_id
        );
        
        // Verify the result
        $this->assertTrue($result);
        
        // Verify email was sent to customer
        $this->assertTrue($this->email_sent);
        $this->assertStringContainsString('customer@example.com', $this->email_to);
        $this->assertStringContainsString('New Comment on Your Design', $this->email_subject);
        $this->assertStringContainsString('Admin User has commented on your design', $this->email_message);
        $this->assertStringContainsString('Have you considered using a different font?', $this->email_message);
        $this->assertStringContainsString(
            'http://example.com/designs/test-design#comment-' . $comment_id,
            $this->email_message
        );
        
        // Clean up
        wp_delete_comment($comment_id, true);
    }
    
    public function test_send_design_assigned_notification()
    {
        // Create a designer user
        $designer_id = $this->factory->user->create([
            'role' => 'editor',
            'user_email' => 'designer@example.com',
            'display_name' => 'Design Team Member'
        ]);
        
        // Reset email tracking
        $this->reset_email_tracking();
        
        // Send design assigned notification
        $result = $this->notifications->send_design_assigned_notification(
            $this->design_id,
            $this->admin_user_id,
            $designer_id,
            'Please review this design for the upcoming campaign.'
        );
        
        // Verify the result
        $this->assertTrue($result);
        
        // Verify email was sent to the designer
        $this->assertTrue($this->email_sent);
        $this->assertStringContainsString('designer@example.com', $this->email_to);
        $this->assertStringContainsString('You\'ve Been Assigned to a Design', $this->email_subject);
        $this->assertStringContainsString('You\'ve been assigned to review a design', $this->email_message);
        $this->assertStringContainsString('Test Design for Notifications', $this->email_message);
        $this->assertStringContainsString('Please review this design for the upcoming campaign', $this->email_message);
        $this->assertStringContainsString(
            'http://example.com/wp-admin/post.php?post=' . $this->design_id . '&action=edit',
            $this->email_message
        );
        
        // Clean up
        wp_delete_user($designer_id);
    }
    
    public function test_send_custom_notification()
    {
        // Reset email tracking
        $this->reset_email_tracking();
        
        // Send custom notification
        $result = $this->notifications->send_custom_notification(
            $this->customer_user_id,
            'Custom Notification',
            'This is a test notification with some <strong>HTML</strong> content.',
            ['design_id' => $this->design_id]
        );
        
        // Verify the result
        $this->assertTrue($result);
        
        // Verify email was sent
        $this->assertTrue($this->email_sent);
        $this->assertStringContainsString('customer@example.com', $this->email_to);
        $this->assertEquals('Custom Notification', $this->email_subject);
        $this->assertStringContainsString('This is a test notification with some <strong>HTML</strong> content', $this->email_message);
        $this->assertStringContainsString(
            'http://example.com/designs/test-design',
            $this->email_message
        );
    }
    
    public function test_notification_preferences()
    {
        // Set up notification preferences for the customer
        update_user_meta($this->customer_user_id, 'ckpp_notification_preferences', [
            'design_approved' => false,
            'design_rejected' => true,
            'design_commented' => true,
            'design_assigned' => false,
        ]);
        
        // Test with disabled notification
        $this->reset_email_tracking();
        $result = $this->notifications->send_design_approved_notification(
            $this->design_id,
            $this->admin_user_id
        );
        $this->assertFalse($this->email_sent, 'Email should not be sent when notification is disabled');
        
        // Test with enabled notification
        $this->reset_email_tracking();
        $result = $this->notifications->send_design_rejected_notification(
            $this->design_id,
            $this->admin_user_id,
            'Please make changes'
        );
        $this->assertTrue($this->email_sent, 'Email should be sent when notification is enabled');
    }
    
    protected function reset_email_tracking()
    {
        $this->email_sent = false;
        $this->email_to = '';
        $this->email_subject = '';
        $this->email_message = '';
        $this->email_headers = '';
    }
}
