<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Printing;
use Brain\Monkey\Functions;

class DesignPrintingTest extends IntegrationTestCase
{
    protected $printing;
    protected $test_data;
    protected $admin_user_id;
    protected $customer_user_id;
    protected $design_id;
    protected $print_job_id;
    
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
        
        $this->printing = new CKPP_Design_Printing();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create a test design
        $this->design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'publish',
            'post_title' => 'Test Design for Printing',
            'post_content' => 'This is a test design for printing',
            'post_author' => $this->customer_user_id,
        ]);
        
        // Add design data
        update_post_meta($this->design_id, '_design_data', [
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Test Print Text',
                    'x' => 100,
                    'y' => 100,
                    'fontSize' => 24,
                    'color' => '#000000',
                    'fontFamily' => 'Arial'
                ]
            ],
            'settings' => [
                'width' => 800,
                'height' => 600,
                'backgroundColor' => '#ffffff',
                'bleed' => 10,
                'safeZone' => 20,
                'dpi' => 300
            ]
        ]);
        
        // Create a test print job
        $this->print_job_id = $this->printing->create_print_job([
            'design_id' => $this->design_id,
            'user_id' => $this->customer_user_id,
            'status' => 'pending',
            'print_options' => [
                'paper_size' => 'A4',
                'orientation' => 'portrait',
                'color_mode' => 'color',
                'copies' => 1,
                'finishing' => []
            ],
            'shipping_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => 'Test Company',
                'email' => 'john@example.com',
                'phone' => '1234567890',
                'address_1' => '123 Test St',
                'address_2' => 'Apt 4B',
                'city' => 'Testville',
                'state' => 'TS',
                'postcode' => '12345',
                'country' => 'US'
            ],
            'billing_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => 'Test Company',
                'email' => 'john@example.com',
                'phone' => '1234567890',
                'address_1' => '123 Test St',
                'address_2' => 'Apt 4B',
                'city' => 'Testville',
                'state' => 'TS',
                'postcode' => '12345',
                'country' => 'US'
            ],
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'shipping_method' => 'standard',
            'shipping_cost' => 4.99,
            'subtotal' => 9.99,
            'tax' => 0.90,
            'total' => 15.88
        ]);
        
        // Mock WordPress functions
        Functions\when('wp_upload_dir')
            ->justReturn([
                'basedir' => '/tmp',
                'baseurl' => 'http://example.com/uploads',
                'path' => '/tmp',
                'url' => 'http://example.com/uploads',
                'subdir' => '',
                'error' => false
            ]);
            
        Functions\when('wp_generate_attachment_metadata')
            ->justReturn([
                'width' => 800,
                'height' => 600,
                'file' => 'test-print.jpg',
                'sizes' => []
            ]);
            
        Functions\when('wp_update_attachment_metadata')
            ->justReturn(true);
            
        Functions\when('wp_get_attachment_url')
            ->justReturn('http://example.com/test-print.jpg');
            
        Functions\when('wp_mail')
            ->justReturn(true);
            
        Functions\when('get_bloginfo')
            ->justReturn('Test Site');
            
        Functions\when('home_url')
            ->justReturn('http://example.com');
            
        Functions\when('admin_url')
            ->justReturn('http://example.com/wp-admin/');
            
        Functions\when('get_edit_post_link')
            ->justReturn('http://example.com/wp-admin/post.php?post=' . $this->print_job_id . '&action=edit');
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
        
        if ($this->print_job_id) {
            wp_delete_post($this->print_job_id, true);
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
    
    public function test_create_print_job()
    {
        // Test creating a new print job
        $print_job_data = [
            'design_id' => $this->design_id,
            'user_id' => $this->customer_user_id,
            'status' => 'pending',
            'print_options' => [
                'paper_size' => 'A3',
                'orientation' => 'landscape',
                'color_mode' => 'bw',
                'copies' => 2,
                'finishing' => ['lamination', 'hole_punch']
            ],
            'shipping_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane@example.com',
                'address_1' => '456 Test Ave',
                'city' => 'Test City',
                'state' => 'TC',
                'postcode' => '54321',
                'country' => 'US'
            ],
            'billing_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane@example.com',
                'address_1' => '456 Test Ave',
                'city' => 'Test City',
                'state' => 'TC',
                'postcode' => '54321',
                'country' => 'US'
            ],
            'payment_method' => 'paypal',
            'payment_status' => 'pending',
            'shipping_method' => 'express',
            'shipping_cost' => 9.99,
            'subtotal' => 19.98,
            'tax' => 1.80,
            'total' => 31.77
        ];
        
        $print_job_id = $this->printing->create_print_job($print_job_data);
        
        // Verify the print job was created
        $this->assertIsInt($print_job_id);
        $this->assertGreaterThan(0, $print_job_id);
        
        // Verify the print job post
        $print_job = get_post($print_job_id);
        $this->assertEquals('ckpp_print_job', $print_job->post_type);
        $this->assertEquals('publish', $print_job->post_status);
        $this->assertEquals('Test Design for Printing', $print_job->post_title);
        $this->assertEquals($this->customer_user_id, $print_job->post_author);
        
        // Verify the print job meta
        $meta = get_post_meta($print_job_id, '_print_job_data', true);
        $this->assertIsArray($meta);
        $this->assertEquals($this->design_id, $meta['design_id']);
        $this->assertEquals('pending', $meta['status']);
        $this->assertEquals('A3', $meta['print_options']['paper_size']);
        $this->assertEquals('landscape', $meta['print_options']['orientation']);
        $this->assertEquals('bw', $meta['print_options']['color_mode']);
        $this->assertEquals(2, $meta['print_options']['copies']);
        $this->assertContains('lamination', $meta['print_options']['finishing']);
        $this->assertContains('hole_punch', $meta['print_options']['finishing']);
        $this->assertEquals('Jane', $meta['shipping_address']['first_name']);
        $this->assertEquals('Smith', $meta['shipping_address']['last_name']);
        $this->assertEquals('456 Test Ave', $meta['shipping_address']['address_1']);
        $this->assertEquals('paypal', $meta['payment_method']);
        $this->assertEquals('pending', $meta['payment_status']);
        $this->assertEquals('express', $meta['shipping_method']);
        $this->assertEquals(9.99, $meta['shipping_cost']);
        $this->assertEquals(19.98, $meta['subtotal']);
        $this->assertEquals(1.80, $meta['tax']);
        $this->assertEquals(31.77, $meta['total']);
        
        // Clean up
        wp_delete_post($print_job_id, true);
    }
    
    public function test_get_print_job()
    {
        // Get an existing print job
        $print_job = $this->printing->get_print_job($this->print_job_id);
        
        // Verify the print job data
        $this->assertIsArray($print_job);
        $this->assertEquals($this->print_job_id, $print_job['id']);
        $this->assertEquals($this->design_id, $print_job['design_id']);
        $this->assertEquals($this->customer_user_id, $print_job['user_id']);
        $this->assertEquals('pending', $print_job['status']);
        $this->assertEquals('A4', $print_job['print_options']['paper_size']);
        $this->assertEquals('portrait', $print_job['print_options']['orientation']);
        $this->assertEquals('color', $print_job['print_options']['color_mode']);
        $this->assertEquals(1, $print_job['print_options']['copies']);
        $this->assertEquals('John', $print_job['shipping_address']['first_name']);
        $this->assertEquals('Doe', $print_job['shipping_address']['last_name']);
        $this->assertEquals('stripe', $print_job['payment_method']);
        $this->assertEquals('pending', $print_job['payment_status']);
        $this->assertEquals('standard', $print_job['shipping_method']);
        $this->assertEquals(4.99, $print_job['shipping_cost']);
        $this->assertEquals(9.99, $print_job['subtotal']);
        $this->assertEquals(0.90, $print_job['tax']);
        $this->assertEquals(15.88, $print_job['total']);
        $this->assertArrayHasKey('created_at', $print_job);
        $this->assertArrayHasKey('updated_at', $print_job);
    }
    
    public function test_update_print_job_status()
    {
        // Update the print job status
        $result = $this->printing->update_print_job_status(
            $this->print_job_id,
            'processing',
            'Print job is now being processed',
            $this->admin_user_id
        );
        
        // Verify the update was successful
        $this->assertTrue($result);
        
        // Verify the status was updated
        $print_job = $this->printing->get_print_job($this->print_job_id);
        $this->assertEquals('processing', $print_job['status']);
        
        // Verify the status history was updated
        $history = $this->printing->get_print_job_history($this->print_job_id);
        $this->assertIsArray($history);
        $this->assertCount(2, $history); // Initial status + our update
        $this->assertEquals('processing', $history[0]['status']);
        $this->assertEquals('Print job is now being processed', $history[0]['message']);
        $this->assertEquals($this->admin_user_id, $history[0]['user_id']);
    }
    
    public function test_generate_print_ready_file()
    {
        // Generate a print-ready file
        $file_path = $this->printing->generate_print_ready_file(
            $this->print_job_id,
            'pdf',
            [
                'crop_marks' => true,
                'bleed' => 3,
                'color_profile' => 'CMYK',
                'resolution' => 300
            ]
        );
        
        // Verify the file was generated
        $this->assertFileExists($file_path);
        $this->assertStringEndsWith('.pdf', $file_path);
        
        // Clean up
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    public function test_send_print_notification()
    {
        // Send a print notification
        $result = $this->printing->send_print_notification(
            $this->print_job_id,
            'customer',
            'print_submitted',
            [
                'message' => 'Your print job has been submitted successfully!',
                'tracking_number' => 'TRK123456789',
                'estimated_delivery' => date('Y-m-d', strtotime('+5 days'))
            ]
        );
        
        // Verify the notification was sent
        $this->assertTrue($result);
        
        // Verify the notification was logged
        $notifications = $this->printing->get_print_job_notifications($this->print_job_id);
        $this->assertIsArray($notifications);
        $this->assertCount(1, $notifications);
        $this->assertEquals('customer', $notifications[0]['recipient']);
        $this->assertEquals('print_submitted', $notifications[0]['type']);
        $this->assertEquals('Your print job has been submitted successfully!', $notifications[0]['data']['message']);
    }
    
    public function test_calculate_print_costs()
    {
        // Calculate print costs
        $costs = $this->printing->calculate_print_costs([
            'design_id' => $this->design_id,
            'print_options' => [
                'paper_size' => 'A4',
                'paper_type' => 'gloss',
                'color_mode' => 'color',
                'copies' => 2,
                'finishing' => ['lamination']
            ],
            'shipping_method' => 'express',
            'shipping_country' => 'US',
            'shipping_zip' => '12345'
        ]);
        
        // Verify the cost calculation
        $this->assertIsArray($costs);
        $this->assertArrayHasKey('subtotal', $costs);
        $this->assertArrayHasKey('shipping', $costs);
        $this->assertArrayHasKey('tax', $costs);
        $this->assertArrayHasKey('total', $costs);
        $this->assertArrayHasKey('breakdown', $costs);
        
        // Verify the values are numeric and positive
        $this->assertIsNumeric($costs['subtotal']);
        $this->assertGreaterThan(0, $costs['subtotal']);
        $this->assertIsNumeric($costs['shipping']);
        $this->assertGreaterThanOrEqual(0, $costs['shipping']);
        $this->assertIsNumeric($costs['tax']);
        $this->assertGreaterThanOrEqual(0, $costs['tax']);
        $this->assertIsNumeric($costs['total']);
        $this->assertGreaterThan(0, $costs['total']);
        
        // Verify the breakdown
        $this->assertIsArray($costs['breakdown']);
        $this->assertArrayHasKey('printing', $costs['breakdown']);
        $this->assertArrayHasKey('materials', $costs['breakdown']);
        $this->assertArrayHasKey('finishing', $costs['breakdown']);
        $this->assertArrayHasKey('shipping', $costs['breakdown']);
        $this->assertArrayHasKey('taxes', $costs['breakdown']);
    }
}
