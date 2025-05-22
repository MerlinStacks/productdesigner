<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Admin_Product_Settings;

class AdminProductSettingsTest extends IntegrationTestCase
{
    protected $product_settings;
    protected $test_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->product_settings = new CKPP_Admin_Product_Settings();
        $this->test_data = TestHelper::setup_test_data();
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        parent::tearDown();
    }
    
    public function test_add_product_data_tab()
    {
        $tabs = [];
        $expected = [
            'ckpp_personalization' => [
                'label' => 'Personalization',
                'target' => 'ckpp_personalization_product_data',
                'class' => ['show_if_simple', 'show_if_variable'],
                'priority' => 90,
            ]
        ];
        
        $result = $this->product_settings->add_product_data_tab($tabs);
        
        $this->assertArrayHasKey('ckpp_personalization', $result);
        $this->assertEquals($expected['ckpp_personalization']['label'], $result['ckpp_personalization']['label']);
        $this->assertEquals($expected['ckpp_personalization']['target'], $result['ckpp_personalization']['target']);
        $this->assertEquals($expected['ckpp_personalization']['class'], $result['ckpp_personalization']['class']);
        $this->assertEquals($expected['ckpp_personalization']['priority'], $result['ckpp_personalization']['priority']);
    }
    
    public function test_add_product_data_panel()
    {
        ob_start();
        $this->product_settings->add_product_data_panel();
        $output = ob_get_clean();
        
        // Check if the panel HTML contains the required fields
        $this->assertStringContainsString('id="ckpp_personalization_product_data"', $output);
        $this->assertStringContainsString('name="_ckpp_enable_personalization"', $output);
        $this->assertStringContainsString('id="ckpp_design_id"', $output);
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
        
        // Test with personalization enabled
        ob_start();
        $this->product_settings->show_custom_columns('ckpp_personalization', $product->get_id());
        $output = ob_get_clean();
        $this->assertStringContainsString('Yes', $output);
        
        // Test with personalization disabled
        update_post_meta($product->get_id(), '_ckpp_enable_personalization', 'no');
        ob_start();
        $this->product_settings->show_custom_columns('ckpp_personalization', $product->get_id());
        $output = ob_get_clean();
        $this->assertStringContainsString('No', $output);
    }
}
