<?php

namespace CustomKings\Tests\Helpers;

use WP_UnitTest_Factory;
use WP_Error;

/**
 * Test helper class with common testing utilities.
 */
class TestHelper
{
    /**
     * @var array Cache for generated test data
     */
    private static $test_data_cache = [];
    
    /**
     * @var array Default design configuration
     */
    private static $default_design_config = [
        'elements' => [],
        'settings' => [
            'width' => 800,
            'height' => 600,
            'backgroundColor' => '#ffffff',
            'bleed' => 10,
            'safeZone' => 20,
            'dpi' => 300
        ]
    ];
    
    /**
     * @var array Default element configurations
     */
    private static $default_elements = [
        'text' => [
            'type' => 'text',
            'content' => 'Sample Text',
            'x' => 100,
            'y' => 100,
            'fontSize' => 24,
            'color' => '#000000',
            'fontFamily' => 'Arial',
            'fontWeight' => 'normal',
            'opacity' => 1,
            'rotation' => 0,
            'width' => 200,
            'height' => 50
        ],
        'image' => [
            'type' => 'image',
            'url' => '',
            'x' => 150,
            'y' => 150,
            'width' => 200,
            'height' => 200,
            'opacity' => 1,
            'rotation' => 0,
            'filters' => []
        ],
        'shape' => [
            'type' => 'shape',
            'shape' => 'rectangle',
            'x' => 200,
            'y' => 200,
            'width' => 150,
            'height' => 100,
            'fill' => '#ff0000',
            'stroke' => '#000000',
            'strokeWidth' => 2,
            'opacity' => 0.8,
            'rotation' => 0,
            'rx' => 5,
            'ry' => 5
        ],
        'qr' => [
            'type' => 'qr',
            'content' => 'https://example.com',
            'x' => 250,
            'y' => 250,
            'size' => 100,
            'fgColor' => '#000000',
            'bgColor' => '#ffffff',
            'eccLevel' => 'M',
            'opacity' => 1,
            'rotation' => 0
        ]
    ];
    /**
     * Create a test design post.
     *
     * @param array $args Optional. Arguments for the design post.
     * @return array|WP_Error The created design post and its meta, or WP_Error on failure.
     */
    public static function create_design($args = [])
    {
        $defaults = [
            'post_title' => 'Test Design ' . uniqid(),
            'post_status' => 'publish',
            'post_type' => 'ckpp_design',
            'meta_input' => [
                '_ckpp_design_config' => json_encode([
                    'elements' => [],
                    'settings' => [
                        'width' => 800,
                        'height' => 600,
                    ]
                ]),
                '_ckpp_design_preview' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            ],
        ];

        $args = wp_parse_args($args, $defaults);
        
        $post_id = wp_insert_post($args);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        return [
            'post' => get_post($post_id),
            'meta' => get_post_meta($post_id),
        ];
    }

    /**
     * Create a test product.
     *
     * @param array $args Optional. Arguments for the product.
     * @return \WC_Product|false The created product, or false on failure.
     */
    public static function create_product($args = [])
    {
        $defaults = [
            'name' => 'Test Product ' . uniqid(),
            'type' => 'simple',
            'regular_price' => '19.99',
            'description' => 'Test product description',
            'short_description' => 'Test product short description',
            'manage_stock' => false,
            'stock_status' => 'instock',
            'virtual' => false,
            'downloadable' => false,
        ];

        $args = wp_parse_args($args, $defaults);
        $product = new \WC_Product_Simple();
        
        foreach ($args as $key => $value) {
            $setter = 'set_' . $key;
            if (method_exists($product, $setter)) {
                $product->$setter($value);
            }
        }
        
        return $product->save() ? wc_get_product($product->get_id()) : false;
    }

    /**
     * Set up test data for the plugin.
     *
     * @param array $options Configuration options for test data generation.
     * @return array|WP_Error Array of test data or WP_Error on failure.
     */
    public static function setup_test_data($options = [])
    {
        // Return cached data if available
        if (!empty(self::$test_data_cache)) {
            return self::$test_data_cache;
        }
        
        // Ensure WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_missing', 'WooCommerce is required for testing.');
        }
        
        $default_options = [
            'num_products' => 3,
            'num_designs' => 5,
            'elements_per_design' => 10,
            'generate_images' => true
        ];
        
        $options = wp_parse_args($options, $default_options);
        
        // Create test products
        $products = [];
        for ($i = 0; $i < $options['num_products']; $i++) {
            $is_personalizable = ($i % 2 === 0); // Alternate between personalizable and non-personalizable
            
            $product_args = [
                'name' => $is_personalizable ? 'Personalizable Product ' . ($i + 1) : 'Regular Product ' . ($i + 1),
                'regular_price' => (string)(19.99 + ($i * 5)),
                'meta_data' => [
                    '_ckpp_enable_personalization' => $is_personalizable ? 'yes' : 'no',
                ]
            ];
            
            $product = self::create_product($product_args);
            if ($product) {
                $products[] = $product;
            }
        }
        
        // Create test designs
        $designs = [];
        for ($i = 0; $i < $options['num_designs']; $i++) {
            $design_elements = [];
            
            // Generate random elements
            for ($j = 0; $j < $options['elements_per_design']; $j++) {
                $element_type = array_rand(self::$default_elements);
                $element = self::generate_design_element($element_type, $j);
                
                if ($element) {
                    $design_elements[] = $element;
                }
            }
            
            $design_config = self::$default_design_config;
            $design_config['elements'] = $design_elements;
            
            $design = self::create_design([
                'post_title' => 'Test Design ' . ($i + 1),
                'post_content' => 'Test design with ' . count($design_elements) . ' elements',
                'post_status' => 'publish',
                'meta_input' => [
                    '_ckpp_design_config' => json_encode($design_config),
                    '_ckpp_design_preview' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                ]
            ]);
            
            if (!is_wp_error($design)) {
                $designs[] = $design;
                
                // Assign design to a product if it's personalizable
                $product_index = $i % count($products);
                if (isset($products[$product_index]) && $products[$product_index]->get_meta('_ckpp_enable_personalization') === 'yes') {
                    $products[$product_index]->update_meta_data('_ckpp_design_id', $design['post']->ID);
                    $products[$product_index]->save();
                }
            }
        }
        
        // Cache the test data
        self::$test_data_cache = [
            'products' => $products,
            'designs' => $designs,
            'options' => $options
        ];
        
        return self::$test_data_cache;
    }

    /**
     * Generate a design element of the specified type.
     *
     * @param string $type Element type (text, image, shape, qr).
     * @param int $index Optional. Index for generating unique content.
     * @return array|false The generated element or false on failure.
     */
    public static function generate_design_element($type, $index = 0)
    {
        if (!isset(self::$default_elements[$type])) {
            return false;
        }
        
        $element = self::$default_elements[$type];
        
        // Make each element slightly different
        $element['x'] = rand(0, 700);
        $element['y'] = rand(0, 500);
        $element['rotation'] = rand(0, 45);
        $element['opacity'] = rand(5, 10) / 10;
        
        switch ($type) {
            case 'text':
                $element['content'] = 'Text Element ' . ($index + 1);
                $element['fontSize'] = rand(12, 36);
                $element['color'] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                $element['fontWeight'] = rand(0, 1) ? 'normal' : 'bold';
                break;
                
            case 'image':
                $element['width'] = rand(50, 200);
                $element['height'] = rand(50, 200);
                // In a real test, you might want to use a test image
                break;
                
            case 'shape':
                $element['shape'] = rand(0, 1) ? 'rectangle' : 'circle';
                $element['fill'] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                $element['stroke'] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                $element['strokeWidth'] = rand(1, 5);
                break;
                
            case 'qr':
                $element['content'] = 'https://example.com/qr-' . uniqid();
                $element['fgColor'] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                break;
        }
        
        return $element;
    }
    
    /**
     * Generate a random string of specified length.
     *
     * @param int $length Length of the string to generate.
     * @return string Random string.
     */
    public static function random_string($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $result;
    }
    
    /**
     * Clean up test data.
     *
     * @param array $data Test data to clean up.
     * @return bool True on success, false on failure.
     */
    public static function cleanup_test_data($data)
    {
        if (empty($data['products']) && empty($data['designs'])) {
            return false;
        }
        
        $result = true;
        
        // Clean up products
        if (!empty($data['products'])) {
            foreach ($data['products'] as $product) {
                if ($product && is_a($product, 'WC_Product')) {
                    $deleted = wp_delete_post($product->get_id(), true);
                    if (false === $deleted) {
                        $result = false;
                    }
                }
            }
        }
        
        // Clean up designs
        if (!empty($data['designs'])) {
            foreach ($data['designs'] as $design) {
                if (is_array($design) && isset($design['post'])) {
                    $deleted = wp_delete_post($design['post']->ID, true);
                    if (false === $deleted) {
                        $result = false;
                    }
                }
            }
        }
        
        // Clear cache
        self::$test_data_cache = [];
        
        return $result;
    }
    
    /**
     * Get the default design configuration.
     *
     * @return array Default design configuration.
     */
    public static function get_default_design_config()
    {
        return self::$default_design_config;
    }
    
    /**
     * Get the default element configuration for a specific type.
     *
     * @param string $type Element type.
     * @return array|false Element configuration or false if type doesn't exist.
     */
    public static function get_default_element_config($type)
    {
        return isset(self::$default_elements[$type]) ? self::$default_elements[$type] : false;
    }
}
