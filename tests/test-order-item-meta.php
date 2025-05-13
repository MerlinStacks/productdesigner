<?php
/**
 * Test saving and retrieving personalization data in WooCommerce order item meta.
 */
class CKPP_OrderItemMetaTest extends WC_Unit_Test_Case {
    public function test_save_and_get_order_item_personalization() {
        // Create a product and order
        $product_id = $this->factory->product->create();
        $order = wc_create_order();
        $item_id = $order->add_product( wc_get_product( $product_id ), 1 );
        $personalization = json_encode([ 'text_0' => 'Test', 'dropdown_1' => 'Option 1' ]);
        wc_add_order_item_meta( $item_id, '_ckpp_personalization_data', $personalization );
        $loaded = wc_get_order_item_meta( $item_id, '_ckpp_personalization_data', true );
        $this->assertEquals($personalization, $loaded);
        $decoded = json_decode($loaded, true);
        $this->assertEquals('Test', $decoded['text_0']);
        $this->assertEquals('Option 1', $decoded['dropdown_1']);
    }
} 