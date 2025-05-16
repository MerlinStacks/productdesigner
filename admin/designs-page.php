<?php
/**
 * Direct handler for the Designs page
 */

// This function will be called directly as a callback
function ckpp_render_designs_page() {
    // Simplify the implementation - don't rely on external class
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to access this page.', 'customkings'));
    }
    
    $design_id = isset($_GET['design_id']) ? intval($_GET['design_id']) : 0;
    echo '<div class="wrap ckpp-admin-page">';
    // Add ckpp-admin class to body for CSS scoping
    echo '<script>if(!document.body.classList.contains("ckpp-admin")){document.body.classList.add("ckpp-admin");}</script>';
    // Modern page header with icon
    echo '<div class="ckpp-page-header">';
    echo '<span class="ckpp-header-icon"><span class="dashicons dashicons-art"></span></span>';
    echo '<h1 style="margin:0;">' . esc_html__('Product Designs', 'customkings') . '</h1>';
    echo '</div>';
    
    if (isset($_GET['ckpp_deleted']) && $_GET['ckpp_deleted'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Design deleted successfully.', 'customkings') . '</p></div>';
    }
    
    if ($design_id) {
        // Designer UI for editing a design
        // Load templates for template selection dropdown
        $templates = get_posts([
            'post_type' => 'ckpp_design',
            'numberposts' => -1,
            's' => 'Template:',
        ]);
        $template_list = [];
        foreach ($templates as $tpl) {
            $template_list[] = [
                'id' => $tpl->ID,
                'title' => $tpl->post_title,
            ];
        }
        echo '<div id="ckpp-templates-data" data-templates="' . esc_attr(json_encode($template_list)) . '" style="display:none;"></div>';
        
        // Load fonts if available
        if (class_exists('CKPP_Fonts')) {
            $fonts = CKPP_Fonts::get_fonts();
            $font_list = [];
            foreach ($fonts as $font) {
                $font_list[] = [
                    'name' => $font->font_name,
                    'url' => $font->font_file
                ];
            }
            echo '<div id="ckpp-fonts-data" data-fonts="' . esc_attr(json_encode($font_list)) . '" style="display:none;"></div>';
        }
        
        // Add root div for designer
        echo '<div id="ckpp-product-designer-root"></div>';
        
        // Set up JS variables for designer
        $design_title = get_the_title($design_id);
        echo '<script>
            window.CKPP_DESIGN_ID = ' . $design_id . '; 
            window.CKPP_DESIGN_TITLE = ' . json_encode($design_title) . ';
        </script>';
        
        // Load required scripts
        // Register Pickr if not already registered
        if (!wp_script_is('pickr', 'registered')) {
            wp_register_script('pickr', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr', [], null, true);
            wp_register_style('pickr-classic', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css', [], null);
        }
        
        // Calculate the correct path to the assets
        $plugin_url = plugins_url('', dirname(__FILE__));
        
        // Enqueue designer scripts
        wp_enqueue_script('pickr');
        wp_enqueue_style('pickr-classic');
        wp_enqueue_script('ckpp-designer-bundle', $plugin_url . '/assets/js/designer-bundle.js', ['jquery', 'pickr'], '1.0', true);
    } else {
        // List designs view
        echo '<a href="' . esc_url(admin_url('admin.php?action=ckpp_create_design')) . '" class="ckpp-btn ckpp-btn-primary" style="margin-bottom:1.5em;display:inline-flex;align-items:center;"><span class="dashicons dashicons-plus"></span>' . esc_html__('Create New Design', 'customkings') . '</a>';
        
        // List existing designs
        $designs = get_posts([
            'post_type' => 'ckpp_design',
            'numberposts' => -1
        ]);
        
        if ($designs) {
            echo '<div class="ckpp-design-list" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1.5em;margin-top:2em;">';
            foreach ($designs as $design) {
                $delete_url = wp_nonce_url(
                    admin_url('admin.php?action=ckpp_delete_design&design_id=' . $design->ID),
                    'ckpp_delete_design_' . $design->ID
                );
                $preview_url = get_post_meta($design->ID, '_ckpp_design_preview', true);
                echo '<div class="ckpp-design-item" style="background:#fff;border:1px solid var(--ckpp-border,#e3e7ed);border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:1.5em 1.2em;display:flex;flex-direction:column;gap:1em;align-items:flex-start;">';
                // Show preview thumbnail if available
                if ($preview_url) {
                    echo '<div class="ckpp-design-thumb" style="width:100%;height:120px;display:flex;align-items:center;justify-content:center;background:#f9fafb;border-radius:8px;overflow:hidden;margin-bottom:0.7em;">';
                    echo '<img src="' . esc_url($preview_url) . '" alt="' . esc_attr($design->post_title) . '" style="max-width:100%;max-height:100%;object-fit:contain;">';
                    echo '</div>';
                }
                echo '<span class="ckpp-design-icon dashicons dashicons-art" style="font-size:2em;color:var(--ckpp-accent,#fec610);"></span>';
                echo '<span class="ckpp-design-title" style="font-weight:600;font-size:1.1em;color:#222;">' . esc_html($design->post_title) . '</span>';
                echo '<div class="ckpp-design-actions" style="display:flex;gap:0.7em;">';
                echo '<a href="' . esc_url(admin_url('admin.php?page=ckpp_designs_direct&design_id=' . $design->ID)) . '" class="ckpp-btn ckpp-btn-secondary" style="display:inline-flex;align-items:center;"><span class="dashicons dashicons-edit"></span>' . esc_html__('Edit', 'customkings') . '</a>';
                echo '<a href="' . esc_url($delete_url) . '" class="ckpp-btn ckpp-btn-secondary" style="color:#a00;display:inline-flex;align-items:center;" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this design?', 'customkings')) . '\');"><span class="dashicons dashicons-trash"></span>' . esc_html__('Delete', 'customkings') . '</a>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>' . esc_html__('No designs found.', 'customkings') . '</p>';
        }
    }
    
    echo '</div>';
} 