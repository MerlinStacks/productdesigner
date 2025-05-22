<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Admin_Product_Settings;

class ProductSettingsTest extends IntegrationTestCase
{
    protected $product_settings;
    protected $test_data;
    protected $user_id;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with administrator role
        $this->user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->user_id);
        
        $this->product_settings = new CKPP_Admin_Product_Settings();
        $this->test_data = TestHelper::setup_test_data();
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up the test user
        if (isset($this->user_id)) {
            wp_delete_user($this->user_id);
        }
        
        parent::tearDown();
    }
    
    public function test_add_product_data_tab()
    {
        $tabs = [];
        $expected = [
            'label' => 'Personalization',
            'target' => 'ckpp_personalization_product_data',
            'class' => ['show_if_simple', 'show_if_variable'],
            'priority' => 90,
        ];
        
        $result = $this->product_settings->add_product_data_tab($tabs);
        
        $this->assertArrayHasKey('ckpp_personalization', $result);
        $this->assertEquals($expected['label'], $result['ckpp_personalization']['label']);
        $this->assertEquals($expected['target'], $result['ckpp_personalization']['target']);
        $this->assertEquals($expected['class'], $result['ckpp_personalization']['class']);
        $this->assertEquals($expected['priority'], $result['ckpp_personalization']['priority']);
    }
    
    public function test_add_product_data_fields()
    {
        global $post;
        
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        $design = $this->test_data['designs'][0];
        
        // Set the current post
        $post = get_post($product->get_id());
        setup_postdata($post);
        
        // Enable personalization for the product
        update_post_meta($product->get_id(), '_ckpp_enable_personalization', 'yes');
        update_post_meta($product->get_id(), '_ckpp_design_id', $design['post']->ID);
        
        // Capture the output
        ob_start();
        $this->product_settings->add_product_data_fields();
        $output = ob_get_clean();
        
        // Verify the output contains the expected fields
        $this->assertStringContainsString('id="ckpp_personalization_product_data"', $output);
        $this->assertStringContainsString('name="_ckpp_enable_personalization"', $output);
        $this->assertStringContainsString('checked="checked"', $output);
        $this->assertStringContainsString('name="_ckpp_design_id"', $output);
        $this->assertStringContainsString('value="' . $design['post']->ID . '"', $output);
        
        // Clean up
        wp_reset_postdata();
    }
    
    public function test_save_product_data()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        $design = $this->test_data['designs'][0];
        
        // Set up the request data
        $_POST['_ckpp_enable_personalization'] = 'yes';
        $_POST['_ckpp_design_id'] = $design['post']->ID;
        
        // Call the method
        $this->product_settings->save_product_data($product->get_id());
        
        // Verify the meta was saved
        $this->assertEquals('yes', get_post_meta($product->get_id(), '_ckpp_enable_personalization', true));
        $this->assertEquals($design['post']->ID, (int) get_post_meta($product->get_id(), '_ckpp_design_id', true));
    }
    
    public function test_add_custom_columns()
    {
        $columns = [];
        $result = $this->product_settings->add_custom_columns($columns);
        
        $this->assertArrayHasKey('ckpp_personalization', $result);
        $this->assertEquals('Personalization', $result['ckpp_personalization']);
    }
    
    public function test_show_custom_columns()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $product = $this->test_data['products'][0];
        $design = $this->test_data['designs'][0];
        
        // Enable personalization for the product
        update_post_meta($product->get_id(), '_ckpp_enable_personalization', 'yes');
        update_post_meta($product->get_id(), '_ckpp_design_id', $design['post']->ID);
        
        // Test the personalization column
        ob_start();
        $this->product_settings->show_custom_columns('ckpp_personalization', $product->get_id());
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Enabled', $output);
        $this->assertStringContainsString('Design ID: ' . $design['post']->ID, $output);
    }
}
