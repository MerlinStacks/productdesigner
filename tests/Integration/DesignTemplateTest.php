<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Template_Manager;
use Brain\Monkey\Functions;

class DesignTemplateTest extends IntegrationTestCase
{
    protected $template_manager;
    protected $test_data;
    protected $admin_user_id;
    protected $customer_user_id;
    protected $templates = [];
    protected $categories = [];
    
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
        
        $this->template_manager = new CKPP_Design_Template_Manager();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create test categories
        $this->categories = [
            'category1' => wp_create_category('Category 1'),
            'category2' => wp_create_category('Category 2')
        ];
        
        // Create test templates
        $this->create_test_templates();
        
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
                'file' => 'test-image.jpg',
                'sizes' => []
            ]);
            
        Functions\when('wp_update_attachment_metadata')
            ->justReturn(true);
            
        Functions\when('wp_get_attachment_url')
            ->justReturn('http://example.com/test-image.jpg');
    }
    
    protected function create_test_templates()
    {
        // Template 1: Basic template
        $this->templates['template1'] = $this->template_manager->create_template([
            'title' => 'Basic Business Card',
            'description' => 'A simple business card template',
            'width' => 800,
            'height' => 600,
            'background' => '#ffffff',
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Your Name',
                    'x' => 50,
                    'y' => 50,
                    'fontSize' => 24,
                    'color' => '#000000',
                    'fontFamily' => 'Arial'
                ],
                [
                    'type' => 'text',
                    'content' => 'Your Title',
                    'x' => 50,
                    'y' => 80,
                    'fontSize' => 16,
                    'color' => '#666666',
                    'fontFamily' => 'Arial'
                ]
            ],
            'categories' => [$this->categories['category1']],
            'tags' => ['business', 'card'],
            'status' => 'publish',
            'author_id' => $this->admin_user_id
        ]);
        
        // Template 2: Template with image
        $this->templates['template2'] = $this->template_manager->create_template([
            'title' => 'Photo Card',
            'description' => 'A template with a photo placeholder',
            'width' => 800,
            'height' => 1000,
            'background' => '#f5f5f5',
            'elements' => [
                [
                    'type' => 'image',
                    'url' => 'http://example.com/placeholder.jpg',
                    'x' => 100,
                    'y' => 100,
                    'width' => 200,
                    'height' => 200,
                    'isPlaceholder' => true
                ],
                [
                    'type' => 'text',
                    'content' => 'Add your photo',
                    'x' => 150,
                    'y' => 320,
                    'fontSize' => 14,
                    'color' => '#999999',
                    'fontFamily' => 'Arial'
                ]
            ],
            'categories' => [$this->categories['category2']],
            'tags' => ['photo', 'card'],
            'status' => 'publish',
            'author_id' => $this->admin_user_id
        ]);
        
        // Template 3: Draft template
        $this->templates['template3'] = $this->template_manager->create_template([
            'title' => 'Coming Soon Template',
            'description' => 'A template that is not yet published',
            'width' => 800,
            'height' => 600,
            'background' => '#ffffff',
            'elements' => [],
            'status' => 'draft',
            'author_id' => $this->admin_user_id
        ]);
        
        // Template 4: Customer template
        $this->templates['customer_template'] = $this->template_manager->create_template([
            'title' => 'Customer Template',
            'description' => 'A template created by a customer',
            'width' => 800,
            'height' => 600,
            'background' => '#ffffff',
            'elements' => [],
            'status' => 'publish',
            'author_id' => $this->customer_user_id
        ]);
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up test templates
        foreach ($this->templates as $template_id) {
            if (get_post($template_id)) {
                wp_delete_post($template_id, true);
            }
        }
        
        // Clean up categories
        foreach ($this->categories as $category_id) {
            wp_delete_term($category_id, 'category');
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
    
    public function test_create_template()
    {
        // Test creating a new template
        $template_data = [
            'title' => 'New Test Template',
            'description' => 'A test template created by the test suite',
            'width' => 1000,
            'height' => 1000,
            'background' => '#f0f0f0',
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Test Text',
                    'x' => 100,
                    'y' => 100,
                    'fontSize' => 20,
                    'color' => '#333333',
                    'fontFamily' => 'Arial'
                ]
            ],
            'categories' => [$this->categories['category1']],
            'tags' => ['test', 'template'],
            'status' => 'publish',
            'author_id' => $this->admin_user_id
        ];
        
        $template_id = $this->template_manager->create_template($template_data);
        
        // Verify the template was created
        $this->assertIsInt($template_id);
        $this->assertGreaterThan(0, $template_id);
        
        // Verify the template post
        $template_post = get_post($template_id);
        $this->assertEquals('ckpp_template', $template_post->post_type);
        $this->assertEquals('publish', $template_post->post_status);
        $this->assertEquals('New Test Template', $template_post->post_title);
        $this->assertEquals('A test template created by the test suite', $template_post->post_content);
        $this->assertEquals($this->admin_user_id, $template_post->post_author);
        
        // Verify the template meta
        $meta = get_post_meta($template_id, '_template_data', true);
        $this->assertIsArray($meta);
        $this->assertEquals(1000, $meta['width']);
        $this->assertEquals(1000, $meta['height']);
        $this->assertEquals('#f0f0f0', $meta['background']);
        $this->assertCount(1, $meta['elements']);
        $this->assertEquals('Test Text', $meta['elements'][0]['content']);
        
        // Verify categories and tags
        $categories = wp_get_post_categories($template_id, ['fields' => 'ids']);
        $this->assertContains($this->categories['category1'], $categories);
        
        $tags = wp_get_post_tags($template_id, ['fields' => 'ids']);
        $tag_names = array_map(function($tag) { return $tag->name; }, $tags);
        $this->assertContains('test', $tag_names);
        $this->assertContains('template', $tag_names);
        
        // Clean up
        wp_delete_post($template_id, true);
    }
    
    public function test_get_template()
    {
        // Get an existing template
        $template = $this->template_manager->get_template($this->templates['template1']);
        
        // Verify the template data
        $this->assertIsArray($template);
        $this->assertEquals($this->templates['template1'], $template['id']);
        $this->assertEquals('Basic Business Card', $template['title']);
        $this->assertEquals('A simple business card template', $template['description']);
        $this->assertEquals(800, $template['width']);
        $this->assertEquals(600, $template['height']);
        $this->assertEquals('#ffffff', $template['background']);
        $this->assertCount(2, $template['elements']);
        $this->assertEquals('Your Name', $template['elements'][0]['content']);
        $this->assertEquals('Your Title', $template['elements'][1]['content']);
        $this->assertContains($this->categories['category1'], $template['categories']);
        $this->assertContains('business', $template['tags']);
        $this->assertContains('card', $template['tags']);
        $this->assertEquals('publish', $template['status']);
        $this->assertEquals($this->admin_user_id, $template['author_id']);
        $this->assertArrayHasKey('created_at', $template);
        $this->assertArrayHasKey('updated_at', $template);
    }
    
    public function test_update_template()
    {
        // Update an existing template
        $update_data = [
            'title' => 'Updated Business Card',
            'description' => 'An updated business card template',
            'width' => 850,
            'height' => 550,
            'background' => '#f9f9f9',
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Updated Name',
                    'x' => 50,
                    'y' => 50,
                    'fontSize' => 24,
                    'color' => '#333333',
                    'fontFamily' => 'Arial'
                ]
            ],
            'categories' => [$this->categories['category2']],
            'tags' => ['updated', 'business', 'card'],
            'status' => 'publish'
        ];
        
        $result = $this->template_manager->update_template($this->templates['template1'], $update_data);
        $this->assertTrue($result);
        
        // Verify the template was updated
        $template = $this->template_manager->get_template($this->templates['template1']);
        
        $this->assertEquals('Updated Business Card', $template['title']);
        $this->assertEquals('An updated business card template', $template['description']);
        $this->assertEquals(850, $template['width']);
        $this->assertEquals(550, $template['height']);
        $this->assertEquals('#f9f9f9', $template['background']);
        $this->assertCount(1, $template['elements']);
        $this->assertEquals('Updated Name', $template['elements'][0]['content']);
        $this->assertContains($this->categories['category2'], $template['categories']);
        $this->assertContains('updated', $template['tags']);
        $this->assertContains('business', $template['tags']);
        $this->assertContains('card', $template['tags']);
    }
    
    public function test_delete_template()
    {
        // Delete a template
        $result = $this->template_manager->delete_template($this->templates['template1']);
        $this->assertTrue($result);
        
        // Verify the template was deleted
        $template = $this->template_manager->get_template($this->templates['template1']);
        $this->assertNull($template);
        
        // Verify the post was actually deleted
        $this->assertNull(get_post($this->templates['template1']));
    }
    
    public function test_get_templates()
    {
        // Get all templates
        $templates = $this->template_manager->get_templates();
        
        // Verify we got the expected number of templates (only published ones)
        $this->assertCount(3, $templates);
        
        // Get templates with filters
        $filters = [
            'category' => $this->categories['category1'],
            'tag' => 'business',
            'search' => 'Business',
            'author' => $this->admin_user_id,
            'status' => 'publish',
            'per_page' => 10,
            'page' => 1
        ];
        
        $filtered_templates = $this->template_manager->get_templates($filters);
        $this->assertCount(1, $filtered_templates);
        $this->assertEquals('Basic Business Card', $filtered_templates[0]['title']);
        
        // Test pagination
        $paged = $this->template_manager->get_templates([
            'per_page' => 1,
            'page' => 1
        ]);
        
        $this->assertCount(1, $paged);
    }
    
    public function test_duplicate_template()
    {
        // Duplicate a template
        $duplicate_id = $this->template_manager->duplicate_template(
            $this->templates['template1'],
            'Duplicated Template',
            $this->admin_user_id
        );
        
        // Verify the duplication
        $this->assertIsInt($duplicate_id);
        $this->assertGreaterThan(0, $duplicate_id);
        
        $original = $this->template_manager->get_template($this->templates['template1']);
        $duplicate = $this->template_manager->get_template($duplicate_id);
        
        $this->assertEquals('Duplicated Template', $duplicate['title']);
        $this->assertEquals($original['description'], $duplicate['description']);
        $this->assertEquals($original['width'], $duplicate['width']);
        $this->assertEquals($original['height'], $duplicate['height']);
        $this->assertEquals($original['background'], $duplicate['background']);
        $this->assertCount(count($original['elements']), $duplicate['elements']);
        $this->assertEquals($original['elements'][0]['content'], $duplicate['elements'][0]['content']);
        
        // Clean up
        wp_delete_post($duplicate_id, true);
    }
    
    public function test_export_import_template()
    {
        // Export a template
        $export_data = $this->template_manager->export_template($this->templates['template1']);
        
        // Verify the export data
        $this->assertIsArray($export_data);
        $this->assertArrayHasKey('version', $export_data);
        $this->assertArrayHasKey('template', $export_data);
        $this->assertEquals('Basic Business Card', $export_data['template']['title']);
        
        // Import the template
        $imported_id = $this->template_manager->import_template($export_data, [
            'status' => 'draft',
            'author_id' => $this->customer_user_id
        ]);
        
        // Verify the import
        $this->assertIsInt($imported_id);
        $this->assertGreaterThan(0, $imported_id);
        
        $imported = $this->template_manager->get_template($imported_id);
        $original = $this->template_manager->get_template($this->templates['template1']);
        
        $this->assertEquals('Basic Business Card (Imported)', $imported['title']);
        $this->assertEquals($original['description'], $imported['description']);
        $this->assertEquals($original['width'], $imported['width']);
        $this->assertEquals($original['height'], $imported['height']);
        $this->assertEquals('draft', $imported['status']);
        $this->assertEquals($this->customer_user_id, $imported['author_id']);
        
        // Clean up
        wp_delete_post($imported_id, true);
    }
    
    public function test_apply_template_to_design()
    {
        // Create a test design
        $design_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_status' => 'draft',
            'post_title' => 'Test Design',
            'post_author' => $this->customer_user_id
        ]);
        
        // Apply a template to the design
        $result = $this->template_manager->apply_template_to_design(
            $this->templates['template1'],
            $design_id,
            $this->customer_user_id
        );
        
        // Verify the template was applied
        $this->assertTrue($result);
        
        // Verify the design was updated with the template data
        $design_data = get_post_meta($design_id, '_design_data', true);
        $template = $this->template_manager->get_template($this->templates['template1']);
        
        $this->assertEquals($template['width'], $design_data['width']);
        $this->assertEquals($template['height'], $design_data['height']);
        $this->assertEquals($template['background'], $design_data['background']);
        $this->assertCount(count($template['elements']), $design_data['elements']);
        
        // Clean up
        wp_delete_post($design_id, true);
    }
}
