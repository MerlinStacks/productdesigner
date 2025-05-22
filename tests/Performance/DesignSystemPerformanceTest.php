<?php

namespace CustomKings\Tests\Performance;

use CustomKings\Tests\TestCase\PerformanceTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Manager;
use CustomKings\CKPP_Design_Renderer;
use Brain\Monkey\Functions;

class DesignSystemPerformanceTest extends PerformanceTestCase
{
    protected $design_manager;
    protected $design_renderer;
    protected $test_data;
    protected $admin_user_id;
    protected $design_ids = [];
    protected $test_assets_dir;
    protected $test_image_path;
    
    // Test configurations
    protected $test_configs = [
        'small' => [
            'designs' => 10,
            'elements_per_design' => 5,
            'image_size' => [200, 200]
        ],
        'medium' => [
            'designs' => 50,
            'elements_per_design' => 15,
            'image_size' => [500, 500]
        ],
        'large' => [
            'designs' => 200,
            'elements_per_design' => 30,
            'image_size' => [1000, 1000]
        ]
    ];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->admin_user_id = $this->factory->user->create([
            'role' => 'administrator',
            'user_email' => 'admin@example.com',
            'display_name' => 'Admin User'
        ]);
        
        wp_set_current_user($this->admin_user_id);
        
        // Initialize components
        $this->design_manager = new CKPP_Design_Manager();
        $this->design_renderer = new CKPP_Design_Renderer();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create test directories
        $upload_dir = wp_upload_dir();
        $this->test_assets_dir = $upload_dir['basedir'] . '/ckpp-test-assets';
        wp_mkdir_p($this->test_assets_dir);
        
        // Create a test image
        $this->test_image_path = $this->test_assets_dir . '/test-image.jpg';
        $image = imagecreatetruecolor(1000, 1000);
        $bg_color = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $bg_color);
        imagejpeg($image, $this->test_image_path, 90);
        imagedestroy($image);
        
        // Mock WordPress functions
        $this->mockWordPressFunctions();
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up test designs
        foreach ($this->design_ids as $design_id) {
            if ($design_id) {
                wp_delete_post($design_id, true);
            }
        }
        
        // Clean up test files
        if (file_exists($this->test_image_path)) {
            unlink($this->test_image_path);
        }
        
        // Clean up test directories
        if (is_dir($this->test_assets_dir)) {
            array_map('unlink', glob($this->test_assets_dir . '/*'));
            rmdir($this->test_assets_dir);
        }
        
        // Clean up user
        if (isset($this->admin_user_id)) {
            wp_delete_user($this->admin_user_id);
        }
        
        parent::tearDown();
    }
    
    protected function mockWordPressFunctions()
    {
        $upload_dir = wp_upload_dir();
        
        Functions\when('wp_upload_dir')
            ->justReturn([
                'basedir' => $upload_dir['basedir'],
                'baseurl' => $upload_dir['baseurl'],
                'path' => $upload_dir['path'],
                'url' => $upload_dir['url'],
                'subdir' => '',
                'error' => false
            ]);
            
        Functions\when('wp_mkdir_p')
            ->alias(function($path) {
                return wp_mkdir_p($path);
            });
            
        Functions\when('wp_generate_attachment_metadata')
            ->justReturn([
                'width' => 1000,
                'height' => 1000,
                'file' => 'test-image.jpg',
                'sizes' => []
            ]);
            
        Functions\when('wp_update_attachment_metadata')
            ->justReturn(true);
            
        Functions\when('wp_get_attachment_url')
            ->justReturn('http://example.com/test-image.jpg');
            
        Functions\when('get_attached_file')
            ->alias(function($attachment_id, $unfiltered = false) {
                return $this->test_image_path;
            });
            
        Functions\when('wp_get_attachment_metadata')
            ->justReturn([
                'width' => 1000,
                'height' => 1000,
                'file' => 'test-image.jpg',
                'sizes' => []
            ]);
            
        // Mock other WordPress functions as needed
        Functions\when('wp_insert_attachment')
            ->justReturn(999);
            
        Functions\when('wp_generate_attachment_metadata')
            ->justReturn([
                'width' => 1000,
                'height' => 1000,
                'file' => 'test-image.jpg',
                'sizes' => []
            ]);
            
        Functions\when('wp_update_attachment_metadata')
            ->justReturn(true);
            
        Functions\when('set_post_thumbnail')
            ->justReturn(true);
            
        Functions\when('add_post_meta')
            ->justReturn(true);
            
        Functions\when('update_post_meta')
            ->justReturn(true);
            
        Functions\when('wp_slash')
            ->returnArg();
    }
    
    protected function generateTestDesign($config_key)
    {
        $config = $this->test_configs[$config_key];
        $elements = [];
        
        // Generate elements
        for ($i = 0; $i < $config['elements_per_design']; $i++) {
            $element_type = $i % 4; // Distribute element types
            
            switch ($element_type) {
                case 0: // Text
                    $elements[] = [
                        'type' => 'text',
                        'content' => 'Test Text ' . $i,
                        'x' => rand(0, 700),
                        'y' => rand(0, 500),
                        'fontSize' => rand(12, 36),
                        'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                        'fontFamily' => 'Arial',
                        'fontWeight' => rand(0, 1) ? 'normal' : 'bold',
                        'opacity' => rand(5, 10) / 10,
                        'rotation' => rand(0, 45),
                        'width' => 200,
                        'height' => 50
                    ];
                    break;
                    
                case 1: // Image
                    $elements[] = [
                        'type' => 'image',
                        'url' => $this->test_image_path,
                        'x' => rand(0, 700),
                        'y' => rand(0, 500),
                        'width' => rand(50, 200),
                        'height' => rand(50, 200),
                        'opacity' => rand(5, 10) / 10,
                        'rotation' => rand(0, 45),
                        'filters' => []
                    ];
                    break;
                    
                case 2: // Shape
                    $elements[] = [
                        'type' => 'shape',
                        'shape' => rand(0, 1) ? 'rectangle' : 'circle',
                        'x' => rand(0, 700),
                        'y' => rand(0, 500),
                        'width' => rand(50, 200),
                        'height' => rand(50, 200),
                        'fill' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                        'stroke' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                        'strokeWidth' => rand(1, 5),
                        'opacity' => rand(5, 10) / 10,
                        'rotation' => rand(0, 45),
                        'rx' => 5,
                        'ry' => 5
                    ];
                    break;
                    
                case 3: // QR Code
                    $elements[] = [
                        'type' => 'qr',
                        'content' => 'https://example.com/' . uniqid(),
                        'x' => rand(0, 700),
                        'y' => rand(0, 500),
                        'size' => rand(50, 150),
                        'fgColor' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                        'bgColor' => '#ffffff',
                        'eccLevel' => 'M',
                        'opacity' => 1,
                        'rotation' => 0
                    ];
                    break;
            }
        }
        
        // Create design data
        $design_data = [
            'elements' => $elements,
            'settings' => [
                'width' => 800,
                'height' => 600,
                'backgroundColor' => '#ffffff',
                'bleed' => 10,
                'safeZone' => 20,
                'dpi' => 300
            ]
        ];
        
        return $design_data;
    }
    
    public function testDesignCreationPerformance()
    {
        $this->startPerformanceTest('design_creation');
        
        foreach ($this->test_configs as $config_key => $config) {
            $this->startMeasurement($config_key . '_design_creation');
            
            $design_ids = [];
            for ($i = 0; $i < $config['designs']; $i++) {
                $design_data = $this->generateTestDesign($config_key);
                
                $design_id = $this->design_manager->create_design([
                    'title' => 'Performance Test Design ' . $config_key . ' ' . ($i + 1),
                    'description' => 'Performance test design with ' . $config['elements_per_design'] . ' elements',
                    'design_data' => $design_data,
                    'status' => 'publish',
                    'user_id' => $this->admin_user_id,
                    'meta' => [
                        'test_config' => $config_key,
                        'element_count' => $config['elements_per_design']
                    ]
                ]);
                
                $this->assertGreaterThan(0, $design_id);
                $design_ids[] = $design_id;
            }
            
            $this->stopMeasurement($config_key . '_design_creation', [
                'designs' => $config['designs'],
                'elements_per_design' => $config['elements_per_design'],
                'total_elements' => $config['designs'] * $config['elements_per_design']
            ]);
            
            // Store design IDs for cleanup
            $this->design_ids = array_merge($this->design_ids, $design_ids);
        }
        
        $this->endPerformanceTest();
    }
    
    public function testDesignRenderingPerformance()
    {
        // First, create test designs
        $test_designs = [];
        foreach ($this->test_configs as $config_key => $config) {
            $design_data = $this->generateTestDesign($config_key);
            
            $design_id = $this->design_manager->create_design([
                'title' => 'Rendering Test Design ' . $config_key,
                'description' => 'Rendering performance test design with ' . $config['elements_per_design'] . ' elements',
                'design_data' => $design_data,
                'status' => 'publish',
                'user_id' => $this->admin_user_id
            ]);
            
            $this->assertGreaterThan(0, $design_id);
            $test_designs[$config_key] = $design_id;
            $this->design_ids[] = $design_id;
        }
        
        // Test rendering performance
        $this->startPerformanceTest('design_rendering');
        
        foreach ($test_designs as $config_key => $design_id) {
            $config = $this->test_configs[$config_key];
            
            $this->startMeasurement($config_key . '_design_rendering');
            
            // Render to different formats
            $formats = ['jpg', 'png', 'pdf'];
            foreach ($formats as $format) {
                $output_file = $this->test_assets_dir . '/render-' . $config_key . '-' . $format . '.' . $format;
                
                $result = $this->design_renderer->render_design(
                    $design_id,
                    [
                        'format' => $format,
                        'output' => 'file',
                        'output_path' => $output_file,
                        'quality' => 90,
                        'background' => '#ffffff'
                    ]
                );
                
                $this->assertFileExists($output_file);
                
                // Clean up
                if (file_exists($output_file)) {
                    unlink($output_file);
                }
            }
            
            $this->stopMeasurement($config_key . '_design_rendering', [
                'elements' => $config['elements_per_design'],
                'formats' => $formats
            ]);
        }
        
        $this->endPerformanceTest();
    }
    
    public function testDesignQueryPerformance()
    {
        // First, create test designs
        $total_designs = 0;
        foreach ($this->test_configs as $config_key => $config) {
            for ($i = 0; $i < $config['designs']; $i++) {
                $design_data = $this->generateTestDesign($config_key);
                
                $design_id = $this->design_manager->create_design([
                    'title' => 'Query Test Design ' . $config_key . ' ' . ($i + 1),
                    'description' => 'Query performance test design',
                    'design_data' => $design_data,
                    'status' => 'publish',
                    'user_id' => $this->admin_user_id,
                    'meta' => [
                        'test_config' => $config_key,
                        'element_count' => $config['elements_per_design']
                    ]
                ]);
                
                $this->assertGreaterThan(0, $design_id);
                $this->design_ids[] = $design_id;
                $total_designs++;
            }
        }
        
        // Test query performance
        $this->startPerformanceTest('design_queries');
        
        // Test get_designs with different parameters
        $test_cases = [
            'get_all_designs' => [
                'params' => ['posts_per_page' => -1],
                'expected' => $total_designs
            ],
            'get_paginated_designs' => [
                'params' => ['posts_per_page' => 10, 'paged' => 1],
                'expected' => 10
            ],
            'filter_by_user' => [
                'params' => ['author' => $this->admin_user_id, 'posts_per_page' => -1],
                'expected' => $total_designs
            ],
            'search_designs' => [
                'params' => ['s' => 'Query Test', 'posts_per_page' => -1],
                'expected' => $total_designs
            ]
        ];
        
        foreach ($test_cases as $test_name => $test_case) {
            $this->startMeasurement($test_name);
            
            $designs = $this->design_manager->get_designs($test_case['params']);
            $this->assertCount($test_case['expected'], $designs);
            
            $this->stopMeasurement($test_name, [
                'params' => $test_case['params'],
                'found' => count($designs)
            ]);
        }
        
        $this->endPerformanceTest();
    }
    
    public function testConcurrentDesignAccess()
    {
        // First, create a test design
        $design_data = $this->generateTestDesign('small');
        
        $design_id = $this->design_manager->create_design([
            'title' => 'Concurrent Access Test Design',
            'description' => 'Test design for concurrent access',
            'design_data' => $design_data,
            'status' => 'publish',
            'user_id' => $this->admin_user_id
        ]);
        
        $this->assertGreaterThan(0, $design_id);
        $this->design_ids[] = $design_id;
        
        // Test concurrent access
        $this->startPerformanceTest('concurrent_design_access');
        
        $iterations = 10;
        $concurrent_users = 5;
        $total_operations = $iterations * $concurrent_users;
        
        $this->startMeasurement('concurrent_reads');
        
        for ($i = 0; $i < $iterations; $i++) {
            $promises = [];
            
            for ($j = 0; $j < $concurrent_users; $j++) {
                $promises[] = function() use ($design_id) {
                    $design = $this->design_manager->get_design($design_id);
                    $this->assertArrayHasKey('id', $design);
                    $this->assertEquals($design_id, $design['id']);
                    return true;
                };
            }
            
            $results = $this->executeConcurrently($promises);
            $this->assertCount($concurrent_users, $results);
            $this->assertContainsOnly('bool', $results);
        }
        
        $this->stopMeasurement('concurrent_reads', [
            'iterations' => $iterations,
            'concurrent_users' => $concurrent_users,
            'total_operations' => $total_operations
        ]);
        
        $this->endPerformanceTest();
    }
    
    public function testMemoryUsage()
    {
        $this->startPerformanceTest('memory_usage');
        
        // Test memory usage with different numbers of designs
        foreach ([10, 50, 100] as $num_designs) {
            $this->startMeasurement('load_' . $num_designs . '_designs');
            
            $designs = [];
            $start_memory = memory_get_usage(true);
            
            for ($i = 0; $i < $num_designs; $i++) {
                $design_data = $this->generateTestDesign('small');
                $designs[] = $design_data;
                
                // Track memory usage
                if (($i + 1) % 10 === 0) {
                    $current_memory = memory_get_usage(true);
                    $memory_per_design = ($current_memory - $start_memory) / ($i + 1);
                    
                    $this->recordMetric(
                        'memory_per_design_' . $num_designs,
                        $memory_per_design / 1024, // Convert to KB
                        'KB/design',
                        ['designs_loaded' => $i + 1]
                    );
                }
            }
            
            $end_memory = memory_get_usage(true);
            $total_memory = $end_memory - $start_memory;
            
            $this->stopMeasurement('load_' . $num_designs . '_designs', [
                'designs_loaded' => $num_designs,
                'memory_used' => $this->formatBytes($total_memory),
                'memory_per_design' => $this->formatBytes($total_memory / $num_designs)
            ]);
            
            // Clean up
            unset($designs);
            gc_collect_cycles();
        }
        
        $this->endPerformanceTest();
    }
}
