<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Post_Type;

class DesignPostTypeTest extends IntegrationTestCase
{
    protected $design_post_type;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->design_post_type = new CKPP_Design_Post_Type();
        $this->test_data = TestHelper::setup_test_data();
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        parent::tearDown();
    }
    
    public function test_register_post_type()
    {
        // Trigger the registration
        $this->design_post_type->register_post_type();
        
        // Verify the post type is registered
        $post_type = get_post_type_object('ckpp_design');
        $this->assertNotNull($post_type);
        $this->assertEquals('Design', $post_type->labels->singular_name);
        $this->assertEquals('Designs', $post_type->labels->name);
        $this->assertTrue($post_type->public);
        $this->assertTrue($post_type->show_ui);
        $this->assertEquals('dashicons-art', $post_type->menu_icon);
    }
    
    public function test_add_meta_boxes()
    {
        global $wp_meta_boxes;
        
        // Set up the screen
        set_current_screen('post-new.php');
        $screen = convert_to_screen('ckpp_design');
        
        // Trigger the action
        do_action('add_meta_boxes', 'ckpp_design', null);
        
        // Verify the meta boxes were added
        $this->assertArrayHasKey('ckpp_design_settings', $wp_meta_boxes['ckpp_design']['normal']['high']);
        $this->assertArrayHasKey('ckpp_design_preview', $wp_meta_boxes['ckpp_design']['normal']['high']);
    }
    
    public function test_render_design_settings_meta_box()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        // Set the current post
        global $post;
        $post = get_post($design['post']->ID);
        setup_postdata($post);
        
        // Capture the output
        ob_start();
        $this->design_post_type->render_design_settings_meta_box($post);
        $output = ob_get_clean();
        
        // Verify the output contains the expected fields
        $this->assertStringContainsString('Design Settings', $output);
        $this->assertStringContainsString('name="_ckpp_design_config"', $output);
        $this->assertStringContainsString('name="_ckpp_design_preview"', $output);
        
        // Clean up
        wp_reset_postdata();
    }
    
    public function test_save_design_meta()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        $post_id = $design['post']->ID;
        
        // Set up the request data
        $_POST['ckpp_design_nonce'] = wp_create_nonce('ckpp_save_design_meta');
        $_POST['_ckpp_design_config'] = json_encode([
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Updated Text',
                    'position' => ['x' => 200, 'y' => 200],
                    'styles' => [
                        'color' => '#ff0000',
                        'fontSize' => 32,
                        'fontFamily' => 'Arial',
                    ]
                ]
            ],
            'settings' => [
                'width' => 1000,
                'height' => 1000,
                'background' => '#ffffff',
            ]
        ]);
        $_POST['_ckpp_design_preview'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        
        // Call the method
        $this->design_post_type->save_design_meta($post_id);
        
        // Verify the meta was saved
        $saved_config = json_decode(get_post_meta($post_id, '_ckpp_design_config', true), true);
        $saved_preview = get_post_meta($post_id, '_ckpp_design_preview', true);
        
        $this->assertEquals('Updated Text', $saved_config['elements'][0]['content']);
        $this->assertEquals(200, $saved_config['elements'][0]['position']['x']);
        $this->assertEquals(1000, $saved_config['settings']['width']);
        $this->assertStringStartsWith('data:image/png;base64,', $saved_preview);
    }
    
    public function test_add_custom_columns()
    {
        $columns = [];
        $result = $this->design_post_type->add_custom_columns($columns);
        
        $this->assertArrayHasKey('preview', $result);
        $this->assertEquals('Preview', $result['preview']);
        $this->assertArrayHasKey('shortcode', $result);
        $this->assertEquals('Shortcode', $result['shortcode']);
    }
    
    public function test_show_custom_columns()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        // Test preview column
        ob_start();
        $this->design_post_type->show_custom_columns('preview', $design['post']->ID);
        $output = ob_get_clean();
        $this->assertStringContainsString('img', $output);
        
        // Test shortcode column
        ob_start();
        $this->design_post_type->show_custom_columns('shortcode', $design['post']->ID);
        $output = ob_get_clean();
        $this->assertStringContainsString('[ckpp_design id="' . $design['post']->ID . '"]', $output);
    }
}
