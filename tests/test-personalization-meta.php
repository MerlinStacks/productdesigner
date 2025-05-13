<?php
/**
 * Test saving and loading personalization config in post meta.
 */
class CKPP_PersonalizationMetaTest extends WP_UnitTestCase {
    public function test_save_and_load_personalization_config() {
        $product_id = $this->factory->post->create([ 'post_type' => 'product' ]);
        $config = json_encode([ 'objects' => [ [ 'type' => 'i-text', 'text' => 'Sample' ] ] ]);
        update_post_meta($product_id, '_product_personalization_config_json', $config);
        $loaded = get_post_meta($product_id, '_product_personalization_config_json', true);
        $this->assertEquals($config, $loaded);
        $decoded = json_decode($loaded, true);
        $this->assertEquals('Sample', $decoded['objects'][0]['text']);
    }
} 