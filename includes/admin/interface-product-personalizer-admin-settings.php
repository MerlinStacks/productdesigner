<?php
/**
 * Admin Settings Interface
 *
 * Defines the interface for the Admin Settings component.
 *
 * @package ProductPersonalizer
 * @subpackage Admin
 * @since 1.0.0
 */

namespace ProductPersonalizer\Admin;

interface Product_Personalizer_Admin_Settings_Interface {
    /**
     * Register the admin menu
     */
    public function register_menu();
    
    /**
     * Display the settings page
     */
    public function display_settings_page();
    
    /**
     * Register settings
     */
    public function register_settings();
    
    /**
     * Save settings
     * 
     * @param array $settings Settings to save
     * @return bool Success status
     */
    public function save_settings(array $settings): bool;
    
    /**
     * Get current settings
     * 
     * @return array Current settings
     */
    public function get_settings(): array;
}