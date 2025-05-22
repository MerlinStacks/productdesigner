<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Design_Analytics;
use Brain\Monkey\Functions;

class DesignAnalyticsTest extends IntegrationTestCase
{
    protected $analytics;
    protected $test_data;
    protected $user_id;
    protected $design_ids = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with administrator role
        $this->user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->user_id);
        
        $this->analytics = new CKPP_Design_Analytics();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create test designs for analytics
        $this->create_test_designs();
    }
    
    protected function create_test_designs()
    {
        // Create test designs with various dates and statuses
        $dates = [
            date('Y-m-d H:i:s', strtotime('-1 day')), // 1 day ago
            date('Y-m-d H:i:s', strtotime('-2 days')), // 2 days ago
            date('Y-m-d H:i:s', strtotime('-1 week')), // 1 week ago
            date('Y-m-d H:i:s', strtotime('-1 month')), // 1 month ago
        ];
        
        $statuses = ['publish', 'draft', 'trash', 'publish'];
        
        foreach ($dates as $i => $date) {
            $this->design_ids[] = $this->factory->post->create([
                'post_type' => 'ckpp_design',
                'post_status' => $statuses[$i],
                'post_date' => $date,
                'post_author' => $this->user_id,
            ]);
            
            // Add view and download counts
            update_post_meta($this->design_ids[$i], '_design_views', ($i + 1) * 10);
            update_post_meta($this->design_ids[$i], '_design_downloads', $i + 1);
        }
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up test designs
        foreach ($this->design_ids as $design_id) {
            wp_delete_post($design_id, true);
        }
        
        // Clean up the test user
        if (isset($this->user_id)) {
            wp_delete_user($this->user_id);
        }
        
        parent::tearDown();
    }
    
    public function test_track_design_view()
    {
        $design_id = $this->design_ids[0];
        
        // Initial view count
        $initial_views = (int) get_post_meta($design_id, '_design_views', true);
        
        // Track a view
        $this->analytics->track_design_view($design_id);
        
        // Verify the view count increased
        $new_views = (int) get_post_meta($design_id, '_design_views', true);
        $this->assertEquals($initial_views + 1, $new_views);
        
        // Verify the view was logged in the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'ckpp_design_analytics';
        $view_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE design_id = %d AND event_type = 'view'",
            $design_id
        ));
        
        $this->assertGreaterThan(0, $view_count);
    }
    
    public function test_track_design_download()
    {
        $design_id = $this->design_ids[0];
        
        // Initial download count
        $initial_downloads = (int) get_post_meta($design_id, '_design_downloads', true);
        
        // Track a download
        $this->analytics->track_design_download($design_id);
        
        // Verify the download count increased
        $new_downloads = (int) get_post_meta($design_id, '_design_downloads', true);
        $this->assertEquals($initial_downloads + 1, $new_downloads);
        
        // Verify the download was logged in the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'ckpp_design_analytics';
        $download_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE design_id = %d AND event_type = 'download'",
            $design_id
        ));
        
        $this->assertGreaterThan(0, $download_count);
    }
    
    public function test_get_design_stats()
    {
        $design_id = $this->design_ids[0];
        
        // Get stats
        $stats = $this->analytics->get_design_stats($design_id);
        
        // Verify the result
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('views', $stats);
        $this->assertArrayHasKey('downloads', $stats);
        $this->assertArrayHasKey('conversion_rate', $stats);
        
        // Verify the values are numeric
        $this->assertIsNumeric($stats['views']);
        $this->assertIsNumeric($stats['downloads']);
        $this->assertIsNumeric($stats['conversion_rate']);
        
        // Verify conversion rate calculation
        if ($stats['views'] > 0) {
            $expected_rate = ($stats['downloads'] / $stats['views']) * 100;
            $this->assertEquals(round($expected_rate, 2), $stats['conversion_rate']);
        }
    }
    
    public function test_get_popular_designs()
    {
        // Get popular designs
        $popular_designs = $this->analytics->get_popular_designs([
            'limit' => 2,
            'timeframe' => 'month',
        ]);
        
        // Verify the result
        $this->assertIsArray($popular_designs);
        $this->assertLessThanOrEqual(2, count($popular_designs));
        
        // If we have results, verify the structure
        if (!empty($popular_designs)) {
            $first_design = $popular_designs[0];
            $this->assertArrayHasKey('design_id', $first_design);
            $this->assertArrayHasKey('title', $first_design);
            $this->assertArrayHasKey('views', $first_design);
            $this->assertArrayHasKey('downloads', $first_design);
            
            // Verify sorting by views (descending)
            if (count($popular_designs) > 1) {
                $this->assertGreaterThanOrEqual(
                    $popular_designs[1]['views'],
                    $popular_designs[0]['views']
                );
            }
        }
    }
    
    public function test_get_analytics_data()
    {
        // Get analytics data for the last 7 days
        $analytics_data = $this->analytics->get_analytics_data([
            'timeframe' => 'week',
            'group_by' => 'day',
        ]);
        
        // Verify the result
        $this->assertIsArray($analytics_data);
        $this->assertArrayHasKey('labels', $analytics_data);
        $this->assertArrayHasKey('datasets', $analytics_data);
        
        // Verify datasets
        $this->assertIsArray($analytics_data['datasets']);
        $this->assertGreaterThan(0, count($analytics_data['datasets']));
        
        // Check for required dataset fields
        foreach ($analytics_data['datasets'] as $dataset) {
            $this->assertArrayHasKey('label', $dataset);
            $this->assertArrayHasKey('data', $dataset);
            $this->assertArrayHasKey('borderColor', $dataset);
            $this->assertArrayHasKey('fill', $dataset);
            
            // Data should have same length as labels
            $this->assertCount(count($analytics_data['labels']), $dataset['data']);
        }
    }
    
    public function test_export_analytics()
    {
        // Get analytics data as CSV
        $csv_data = $this->analytics->export_analytics([
            'timeframe' => 'month',
            'format' => 'csv',
        ]);
        
        // Verify the result
        $this->assertIsString($csv_data);
        $this->assertStringContainsString('Date,Views,Downloads,Conversion Rate', $csv_data);
        
        // Count the number of lines (header + data)
        $lines = explode("\n", trim($csv_data));
        $this->assertGreaterThan(1, count($lines));
        
        // Verify data format
        $data_line = $lines[1];
        $data_parts = str_getcsv($data_line);
        $this->assertCount(4, $data_parts); // Date, Views, Downloads, Conversion Rate
        
        // Verify numeric values
        $this->assertIsNumeric(trim($data_parts[1])); // Views
        $this->assertIsNumeric(trim($data_parts[2])); // Downloads
    }
}
