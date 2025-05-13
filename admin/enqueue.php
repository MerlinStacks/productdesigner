<?php
// Pickr enqueue is now handled in class-product-designer.php to avoid double-enqueue and ensure correct dependency order.

// Enqueue Pickr color picker for the admin designer page
add_action('admin_enqueue_scripts', function($hook) {
    // Only load on our designer/admin page (main slug is 'ckpp_admin')
    if (isset($_GET['page']) && $_GET['page'] === 'ckpp_admin') {
        // Pickr CSS (Classic theme)
        wp_enqueue_style('pickr-classic', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css', [], null);
        // Pickr JS
        wp_enqueue_script('pickr', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr', [], null, true);
    }
}); 