<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Approval;
use Brain\Monkey\Functions;

class DesignApprovalTest extends IntegrationTestCase
{
    protected $approval;
    protected $test_data;
    protected $admin_user_id;
    protected $customer_user_id;
    protected $design_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->admin_user_id = $this->factory->user->create(['role' => 'administrator']);
        $this->customer_user_id = $this->factory->user->create([
            'role' => 'customer',
            'user_email' => 'customer@example.com',
            'display_name' => 'Test Customer'
        ]);
        
        // Set current user as admin
        wp_set_current_user($this->admin_user_id);
        
        $this->approval = new CKPP_Design_Approval();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create a test design
        $this->design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'pending',
            'post_title' => 'Test Design for Approval',
            'post_author' => $this->customer_user_id,
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
            
        // Mock the email function for testing
        $this->email_sent = false;
        $this->email_to = '';
        $this->email_subject = '';
        
        Functions\when('wp_mail')
            ->alias(function($to, $subject, $message) {
                $this->email_sent = true;
                $this->email_to = $to;
                $this->email_subject = $subject;
                return true;
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
        
        // Clean up users
        if (isset($this->admin_user_id)) {
            wp_delete_user($this->admin_user_id);
        }
        
        if (isset($this->customer_user_id)) {
            wp_delete_user($this->customer_user_id);
        }
        
        parent::tearDown();
    }
    
    public function test_submit_for_approval()
    {
        // Set current user as customer
        wp_set_current_user($this->customer_user_id);
        
        // Submit for approval
        $result = $this->approval->submit_for_approval($this->design_id, $this->customer_user_id);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        
        // Verify the design status was updated
        $design = get_post($this->design_id);
        $this->assertEquals('pending', $design->post_status);
        
        // Verify the approval status was set
        $approval_status = get_post_meta($this->design_id, '_approval_status', true);
        $this->assertEquals('pending', $approval_status);
        
        // Verify the submission date was recorded
        $submission_date = get_post_meta($this->design_id, '_submission_date', true);
        $this->assertNotEmpty($submission_date);
        
        // Verify admin notification was sent
        $this->assertTrue($this->email_sent);
        $this->assertStringContainsString('admin@example.com', $this->email_to);
        $this->assertStringContainsString('Design Submitted for Approval', $this->email_subject);
    }
    
    public function test_approve_design()
    {
        // First submit for approval
        $this->approval->submit_for_approval($this->design_id, $this->customer_user_id);
        
        // Reset email tracking
        $this->email_sent = false;
        $this->email_to = '';
        $this->email_subject = '';
        
        // Approve the design
        $result = $this->approval->update_approval_status($this->design_id, 'approved', 'Looks good!', $this->admin_user_id);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        
        // Verify the design status was updated
        $design = get_post($this->design_id);
        $this->assertEquals('publish', $design->post_status);
        
        // Verify the approval status was updated
        $approval_status = get_post_meta($this->design_id, '_approval_status', true);
        $this->assertEquals('approved', $approval_status);
        
        // Verify the approval date was recorded
        $approval_date = get_post_meta($this->design_id, '_approval_date', true);
        $this->assertNotEmpty($approval_date);
        
        // Verify the approver was recorded
        $approver_id = get_post_meta($this->design_id, '_approved_by', true);
        $this->assertEquals($this->admin_user_id, $approver_id);
        
        // Verify the approval note was saved
        $approval_note = get_post_meta($this->design_id, '_approval_note', true);
        $this->assertEquals('Looks good!', $approval_note);
        
        // Verify customer notification was sent
        $this->assertTrue($this->email_sent);
        $this->assertStringContainsString('customer@example.com', $this->email_to);
        $this->assertStringContainsString('Design Approved', $this->email_subject);
    }
    
    public function test_reject_design()
    {
        // First submit for approval
        $this->approval->submit_for_approval($this->design_id, $this->customer_user_id);
        
        // Reset email tracking
        $this->email_sent = false;
        $this->email_to = '';
        $this->email_subject = '';
        
        // Reject the design
        $result = $this->approval->update_approval_status(
            $this->design_id, 
            'rejected', 
            'Needs adjustments to the layout.',
            $this->admin_user_id
        );
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        
        // Verify the design status was updated to draft
        $design = get_post($this->design_id);
        $this->assertEquals('draft', $design->post_status);
        
        // Verify the approval status was updated
        $approval_status = get_post_meta($this->design_id, '_approval_status', true);
        $this->assertEquals('rejected', $approval_status);
        
        // Verify the rejection note was saved
        $rejection_note = get_post_meta($this->design_id, '_rejection_note', true);
        $this->assertEquals('Needs adjustments to the layout.', $rejection_note);
        
        // Verify customer notification was sent
        $this->assertTrue($this->email_sent);
        $this->assertStringContainsString('customer@example.com', $this->email_to);
        $this->assertStringContainsString('Design Requires Changes', $this->email_subject);
    }
    
    public function test_get_approval_history()
    {
        // Submit for approval
        $this->approval->submit_for_approval($this->design_id, $this->customer_user_id);
        
        // Add some approval history
        $history = [
            [
                'status' => 'submitted',
                'user_id' => $this->customer_user_id,
                'timestamp' => time() - 3600,
                'note' => 'Initial submission'
            ],
            [
                'status' => 'rejected',
                'user_id' => $this->admin_user_id,
                'timestamp' => time() - 1800,
                'note' => 'Needs adjustments'
            ],
            [
                'status' => 'submitted',
                'user_id' => $this->customer_user_id,
                'timestamp' => time() - 900,
                'note' => 'Resubmitted with changes'
            ]
        ];
        
        update_post_meta($this->design_id, '_approval_history', $history);
        
        // Get the approval history
        $result = $this->approval->get_approval_history($this->design_id);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Verify the structure of each history item
        foreach ($result as $item) {
            $this->assertArrayHasKey('status', $item);
            $this->assertArrayHasKey('user_id', $item);
            $this->assertArrayHasKey('user', $item);
            $this->assertArrayHasKey('timestamp', $item);
            $this->assertArrayHasKey('time_ago', $item);
            $this->assertArrayHasKey('note', $item);
        }
        
        // Verify the history is sorted by timestamp (newest first)
        $this->assertGreaterThanOrEqual(
            $result[1]['timestamp'],
            $result[0]['timestamp']
        );
        $this->assertGreaterThanOrEqual(
            $result[2]['timestamp'],
            $result[1]['timestamp']
        );
    }
    
    public function test_get_pending_approvals()
    {
        // Create multiple designs with different statuses
        $pending_designs = [];
        
        // Create 3 pending designs
        for ($i = 0; $i < 3; $i++) {
            $design_id = $this->factory->post->create([
                'post_type' => 'ckpp_design',
                'post_status' => 'pending',
                'post_title' => 'Pending Design ' . ($i + 1),
                'post_author' => $this->customer_user_id,
            ]);
            
            update_post_meta($design_id, '_approval_status', 'pending');
            $pending_designs[] = $design_id;
        }
        
        // Create an approved design (should not be in pending list)
        $approved_design = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'publish',
            'post_title' => 'Approved Design',
            'post_author' => $this->customer_user_id,
        ]);
        update_post_meta($approved_design, '_approval_status', 'approved');
        
        // Get pending approvals
        $result = $this->approval->get_pending_approvals();
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Verify each design has the expected structure
        foreach ($result as $design) {
            $this->assertArrayHasKey('ID', $design);
            $this->assertArrayHasKey('post_title', $design);
            $this->assertArrayHasKey('post_author', $design);
            $this->assertArrayHasKey('author_name', $design);
            $this->assertArrayHasKey('submission_date', $design);
            $this->assertArrayHasKey('time_ago', $design);
            
            // Verify these are the pending designs we created
            $this->assertContains($design['ID'], $pending_designs);
        }
        
        // Clean up
        foreach ($pending_designs as $design_id) {
            wp_delete_post($design_id, true);
        }
        wp_delete_post($approved_design, true);
    }
}
