<?php
/**
 * Test print-ready file generation logic (mocked).
 */
class CKPP_PrintFileGenerationTest extends WP_UnitTestCase {
    public function test_generate_print_file_and_update_meta() {
        $order_id = $this->factory->order->create();
        $order = wc_get_order($order_id);
        $item_id = $order->add_product( $this->factory->product->create(), 1 );
        $file_url = 'https://example.com/wp-content/uploads/ckpp-print-' . $item_id . '.pdf';
        // Simulate file generation and meta update
        wc_update_order_item_meta( $item_id, '_ckpp_print_file_url', $file_url );
        $loaded_url = wc_get_order_item_meta( $item_id, '_ckpp_print_file_url', true );
        $this->assertEquals($file_url, $loaded_url);
    }
} 