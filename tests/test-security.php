<?php
/**
 * Security tests for CustomKings Product Personalizer
 */
class CKPP_Security_Test extends WP_UnitTestCase {
    public function test_ajax_get_config_requires_nonce() {
        // Simulate AJAX request without nonce
        $_GET['action'] = 'ckpp_get_product_config';
        $_GET['productId'] = 1;
        // Remove nonce
        unset($_GET['nonce']);
        // Capture output
        ob_start();
        do_action('wp_ajax_ckpp_get_product_config');
        $output = ob_get_clean();
        $this->assertStringContainsString('error', $output, 'AJAX endpoint should reject missing nonce');
    }

    public function test_font_upload_requires_manage_options() {
        // Simulate a user without manage_options
        $user_id = $this->factory->user->create([ 'role' => 'subscriber' ]);
        wp_set_current_user($user_id);
        $_POST['_wpnonce'] = wp_create_nonce('ckpp_upload_font');
        $_FILES['ckpp_font_file'] = [
            'name' => 'test.ttf',
            'type' => 'font/ttf',
            'tmp_name' => tempnam(sys_get_temp_dir(), 'ttf'),
            'error' => 0,
            'size' => 1000
        ];
        // Capture output
        ob_start();
        do_action('admin_post_ckpp_upload_font');
        $output = ob_get_clean();
        $this->assertStringContainsString('Unauthorized', $output, 'Font upload should require manage_options capability');
    }

    public function test_clipart_upload_requires_manage_options() {
        // Simulate a user without manage_options
        $user_id = $this->factory->user->create([ 'role' => 'subscriber' ]);
        wp_set_current_user($user_id);
        $_POST['_wpnonce'] = wp_create_nonce('ckpp_upload_clipart');
        $_FILES['ckpp_clipart_file'] = [
            'name' => 'test.png',
            'type' => 'image/png',
            'tmp_name' => tempnam(sys_get_temp_dir(), 'png'),
            'error' => 0,
            'size' => 1000
        ];
        $_POST['ckpp_clipart_name'] = 'Test Clipart';
        $_POST['ckpp_clipart_tags'] = 'test';
        // Capture output
        ob_start();
        do_action('admin_post_ckpp_upload_clipart');
        $output = ob_get_clean();
        $this->assertStringContainsString('Unauthorized', $output, 'Clipart upload should require manage_options capability');
    }

    public function test_font_upload_rejects_invalid_type() {
        // Simulate an admin user
        $user_id = $this->factory->user->create([ 'role' => 'administrator' ]);
        wp_set_current_user($user_id);
        $_POST['_wpnonce'] = wp_create_nonce('ckpp_upload_font');
        $_FILES['ckpp_font_file'] = [
            'name' => 'malware.exe',
            'type' => 'application/octet-stream',
            'tmp_name' => tempnam(sys_get_temp_dir(), 'exe'),
            'error' => 0,
            'size' => 1000
        ];
        // Capture output
        ob_start();
        do_action('admin_post_ckpp_upload_font');
        $output = ob_get_clean();
        $this->assertStringContainsString('invalid_type', $output, 'Font upload should reject invalid file types');
    }

    public function test_clipart_upload_rejects_invalid_type() {
        // Simulate an admin user
        $user_id = $this->factory->user->create([ 'role' => 'administrator' ]);
        wp_set_current_user($user_id);
        $_POST['_wpnonce'] = wp_create_nonce('ckpp_upload_clipart');
        $_POST['ckpp_clipart_name'] = 'Test Clipart';
        $_POST['ckpp_clipart_tags'] = 'test';
        $_FILES['ckpp_clipart_file'] = [
            'name' => 'malware.exe',
            'type' => 'application/octet-stream',
            'tmp_name' => tempnam(sys_get_temp_dir(), 'exe'),
            'error' => 0,
            'size' => 1000
        ];
        // Capture output
        ob_start();
        do_action('admin_post_ckpp_upload_clipart');
        $output = ob_get_clean();
        $this->assertStringContainsString('invalid_type', $output, 'Clipart upload should reject invalid file types');
    }

    public function test_clipart_delete_requires_manage_options() {
        // Simulate a user without manage_options
        $user_id = $this->factory->user->create([ 'role' => 'subscriber' ]);
        wp_set_current_user($user_id);
        $_POST['_wpnonce'] = wp_create_nonce('ckpp_delete_clipart');
        $_POST['clipart_id'] = 1;
        // Capture output
        ob_start();
        do_action('admin_post_ckpp_delete_clipart');
        $output = ob_get_clean();
        $this->assertStringContainsString('Unauthorized', $output, 'Clipart delete should require manage_options capability');
    }
} 