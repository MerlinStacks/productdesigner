<?php

namespace CustomKings\Tests\Helpers;

/**
 * Helper class for mocking JavaScript functions in PHPUnit tests.
 */
class JSMockHelper
{
    /**
     * Mock a JavaScript function call.
     *
     * @param string $function_name The name of the function to mock.
     * @param mixed $return_value The value the function should return.
     * @param int $times How many times the function is expected to be called.
     */
    public static function mockJSFunction($function_name, $return_value = null, $times = 1)
    {
        \Brain\Monkey\Functions\expect('wp_add_inline_script')
            ->times($times)
            ->with(
                'customkings-product-personalizer',
                \Brain\Monkey\Functions\type('string')->andReturnUsing(
                    function ($script) use ($function_name, $return_value) {
                        // Verify the script contains the expected function call
                        $this->assertStringContainsString(
                            "function {$function_name}",
                            $script,
                            "The script does not contain the expected function: {$function_name}"
                        );
                        
                        // If a return value is provided, verify it's included
                        if ($return_value !== null) {
                            $expected_return = json_encode($return_value, JSON_PRETTY_PRINT);
                            $this->assertStringContainsString(
                                $expected_return,
                                $script,
                                "The function does not return the expected value: {$expected_return}"
                            );
                        }
                        
                        return true;
                    }
                ),
                'after'
            );
    }
    
    /**
     * Mock an AJAX request.
     *
     * @param string $action The AJAX action to mock.
     * @param array $response The response data to return.
     * @param int $status_code The HTTP status code to return.
     */
    public static function mockAjaxRequest($action, $response = ['success' => true], $status_code = 200)
    {
        \Brain\Monkey\Functions\expect('wp_remote_post')
            ->once()
            ->with(
                \Brain\Monkey\Functions\type('string'),
                [
                    'body' => [
                        'action' => $action,
                        'nonce' => 'test-nonce',
                    ],
                ]
            )
            ->andReturn([
                'response' => ['code' => $status_code],
                'body' => json_encode($response),
            ]);
    }
    
    /**
     * Mock a WordPress localized script.
     *
     * @param string $handle The script handle.
     * @param string $object_name The name of the JavaScript object.
     * @param array $data The data to localize.
     */
    public static function mockLocalizedScript($handle, $object_name, $data = [])
    {
        \Brain\Monkey\Functions\expect('wp_localize_script')
            ->once()
            ->with(
                $handle,
                $object_name,
                $data
            );
    }
    
    /**
     * Mock a WordPress enqueue function.
     *
     * @param string $handle The script/style handle to enqueue.
     * @param string $type Either 'script' or 'style'.
     */
    public static function mockEnqueue($handle, $type = 'script')
    {
        $function = $type === 'script' ? 'wp_enqueue_script' : 'wp_enqueue_style';
        
        \Brain\Monkey\Functions\expect($function)
            ->once()
            ->with(
                $handle,
                \Brain\Monkey\Functions\type('string'),
                \Brain\Monkey\Functions\type('array'),
                \Brain\Monkey\Functions\type('string')
            )
            ->andReturn(true);
    }
}
