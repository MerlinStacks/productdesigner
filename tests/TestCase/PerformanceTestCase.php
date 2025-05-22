<?php

namespace CustomKings\Tests\TestCase;

use CustomKings\Tests\TestCase\WP_UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Base class for performance testing.
 */
class PerformanceTestCase extends WP_UnitTestCase
{
    /**
     * @var array Store performance metrics
     */
    protected $performance_metrics = [];
    
    /**
     * @var string Current test name
     */
    protected $current_test_name = '';
    
    /**
     * @var array Test configuration
     */
    protected $test_config = [];
    
    /**
     * Start a performance test.
     *
     * @param string $test_name Name of the test
     * @param array $config Test configuration
     */
    protected function startPerformanceTest($test_name, $config = [])
    {
        $this->current_test_name = $test_name;
        $this->test_config = $config;
        $this->performance_metrics[$test_name] = [
            'start_time' => microtime(true),
            'end_time' => null,
            'duration' => 0,
            'memory_peak' => 0,
            'measurements' => [],
            'config' => $config
        ];
        
        // Clear any previous measurements
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Start with a clean memory measurement
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        
        gc_collect_cycles();
    }
    
    /**
     * End the current performance test.
     */
    protected function endPerformanceTest()
    {
        if (empty($this->current_test_name) || !isset($this->performance_metrics[$this->current_test_name])) {
            return;
        }
        
        $end_time = microtime(true);
        $test = &$this->performance_metrics[$this->current_test_name];
        $test['end_time'] = $end_time;
        $test['duration'] = $end_time - $test['start_time'];
        $test['memory_peak'] = memory_get_peak_usage(true);
        
        // Output results
        $this->outputPerformanceResults($this->current_test_name, $test);
        
        // Reset current test
        $this->current_test_name = '';
        $this->test_config = [];
    }
    
    /**
     * Start a measurement.
     *
     * @param string $name Measurement name
     * @param array $metadata Additional metadata
     * @return array Measurement data
     */
    protected function startMeasurement($name, $metadata = [])
    {
        if (empty($this->current_test_name)) {
            return [];
        }
        
        $measurement = [
            'name' => $name,
            'start_time' => microtime(true),
            'end_time' => null,
            'duration' => 0,
            'memory_start' => memory_get_usage(true),
            'memory_end' => 0,
            'memory_used' => 0,
            'memory_peak' => 0,
            'metadata' => $metadata
        ];
        
        $this->performance_metrics[$this->current_test_name]['measurements'][$name] = $measurement;
        
        return $measurement;
    }
    
    /**
     * Stop a measurement.
     *
     * @param string $name Measurement name
     * @param array $metadata Additional metadata
     * @return array Measurement data
     */
    protected function stopMeasurement($name, $metadata = [])
    {
        if (empty($this->current_test_name) || 
            !isset($this->performance_metrics[$this->current_test_name]['measurements'][$name])) {
            return [];
        }
        
        $end_time = microtime(true);
        $measurement = &$this->performance_metrics[$this->current_test_name]['measurements'][$name];
        
        $measurement['end_time'] = $end_time;
        $measurement['duration'] = $end_time - $measurement['start_time'];
        $measurement['memory_end'] = memory_get_usage(true);
        $measurement['memory_used'] = $measurement['memory_end'] - $measurement['memory_start'];
        $measurement['memory_peak'] = memory_get_peak_usage(true);
        
        // Merge additional metadata
        if (!empty($metadata)) {
            $measurement['metadata'] = array_merge($measurement['metadata'] ?? [], $metadata);
        }
        
        // Output measurement
        $this->outputMeasurement($name, $measurement);
        
        return $measurement;
    }
    
    /**
     * Record a custom metric.
     *
     * @param string $name Metric name
     * @param mixed $value Metric value
     * @param string $unit Unit of measurement
     * @param array $metadata Additional metadata
     */
    protected function recordMetric($name, $value, $unit = '', $metadata = [])
    {
        if (empty($this->current_test_name)) {
            return;
        }
        
        if (!isset($this->performance_metrics[$this->current_test_name]['metrics'])) {
            $this->performance_metrics[$this->current_test_name]['metrics'] = [];
        }
        
        $this->performance_metrics[$this->current_test_name]['metrics'][$name] = [
            'value' => $value,
            'unit' => $unit,
            'time' => microtime(true) - $this->performance_metrics[$this->current_test_name]['start_time'],
            'memory' => memory_get_usage(true),
            'metadata' => $metadata
        ];
        
        $this->outputMetric($name, $this->performance_metrics[$this->current_test_name]['metrics'][$name]);
    }
    
    /**
     * Execute functions concurrently.
     *
     * @param array $functions Array of callables to execute
     * @param int $concurrency Maximum number of concurrent executions
     * @return array Results of each function
     */
    protected function executeConcurrently(array $functions, $concurrency = 5)
    {
        $results = [];
        $running = [];
        $index = 0;
        
        do {
            // Start new processes until we reach concurrency limit
            while (count($running) < $concurrency && $index < count($functions)) {
                $process = $functions[$index];
                $running[$index] = $process;
                $index++;
            }
            
            // If no more processes to run, break the loop
            if (empty($running)) {
                break;
            }
            
            // Execute the next process
            $current_index = key($running);
            $current_process = current($running);
            
            try {
                $results[$current_index] = $current_process();
            } catch (\Exception $e) {
                $results[$current_index] = $e;
            }
            
            // Remove the completed process
            unset($running[$current_index]);
            
        } while ($index < count($functions) || !empty($running));
        
        // Sort results by original index
        ksort($results);
        
        return $results;
    }
    
    /**
     * Output performance results.
     *
     * @param string $test_name
     * @param array $test_data
     */
    protected function outputPerformanceResults($test_name, $test_data)
    {
        $output = "\n\n";
        $output .= str_repeat('=', 80) . "\n";
        $output .= "PERFORMANCE TEST: " . $test_name . "\n";
        $output .= str_repeat('-', 80) . "\n";
        
        if (!empty($test_data['config'])) {
            $output .= "Configuration:\n";
            foreach ($test_data['config'] as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $output .= sprintf("  %-20s: %s\n", $key, $value);
            }
            $output .= str_repeat('-', 80) . "\n";
        }
        
        $output .= sprintf("Duration: %.4f seconds\n", $test_data['duration']);
        $output .= sprintf("Memory Peak: %s\n", $this->formatBytes($test_data['memory_peak']));
        
        if (!empty($test_data['metrics'])) {
            $output .= "\nMetrics:\n";
            foreach ($test_data['metrics'] as $name => $metric) {
                $output .= sprintf("  %-30s: %s %s\n", 
                    $name, 
                    is_float($metric['value']) ? number_format($metric['value'], 4) : $metric['value'],
                    $metric['unit']
                );
            }
        }
        
        if (!empty($test_data['measurements'])) {
            $output .= "\nMeasurements:\n";
            $output .= str_repeat('-', 100) . "\n";
            $output .= sprintf("%-30s %12s %12s %12s %12s\n", 'Name', 'Duration (s)', 'Memory', 'Peak', 'Items/s');
            $output .= str_repeat('-', 100) . "\n";
            
            foreach ($test_data['measurements'] as $name => $measurement) {
                $items_per_second = 0;
                if (isset($measurement['metadata']['items']) && $measurement['duration'] > 0) {
                    $items_per_second = $measurement['metadata']['items'] / $measurement['duration'];
                }
                
                $output .= sprintf("%-30s %12.4f %12s %12s %12.2f\n",
                    $name,
                    $measurement['duration'],
                    $this->formatBytes($measurement['memory_used']),
                    $this->formatBytes($measurement['memory_peak']),
                    $items_per_second
                );
                
                // Output metadata if present
                if (!empty($measurement['metadata'])) {
                    foreach ($measurement['metadata'] as $key => $value) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        $output .= sprintf("  %-28s: %s\n", $key, $value);
                    }
                }
            }
        }
        
        $output .= str_repeat('=', 80) . "\n\n";
        
        // Output to console
        fwrite(STDERR, $output);
        
        // Also save to a log file
        $log_dir = WP_CONTENT_DIR . '/ckpp-logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/performance-' . date('Y-m-d') . '.log';
        file_put_contents($log_file, $output, FILE_APPEND);
    }
    
    /**
     * Output a single measurement.
     *
     * @param string $name
     * @param array $measurement
     */
    protected function outputMeasurement($name, $measurement)
    {
        $output = sprintf("[%s] %-40s | Duration: %.4fs | Memory: %s | Peak: %s\n",
            date('Y-m-d H:i:s'),
            $name,
            $measurement['duration'],
            $this->formatBytes($measurement['memory_used']),
            $this->formatBytes($measurement['memory_peak'])
        );
        
        if (!empty($measurement['metadata'])) {
            foreach ($measurement['metadata'] as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $output .= sprintf("  %-20s: %s\n", $key, $value);
            }
        }
        
        fwrite(STDERR, $output);
    }
    
    /**
     * Output a single metric.
     *
     * @param string $name
     * @param array $metric
     */
    protected function outputMetric($name, $metric)
    {
        $output = sprintf("[%s] %-40s | %s %s\n",
            date('Y-m-d H:i:s'),
            $name,
            is_float($metric['value']) ? number_format($metric['value'], 4) : $metric['value'],
            $metric['unit']
        );
        
        if (!empty($metric['metadata'])) {
            foreach ($metric['metadata'] as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $output .= sprintf("  %-20s: %s\n", $key, $value);
            }
        }
        
        fwrite(STDERR, $output);
    }
    
    /**
     * Format bytes to a human-readable string.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
