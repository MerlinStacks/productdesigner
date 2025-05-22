<?php

namespace CustomKings\Tests\Unit;

use CustomKings\Tests\TestCase\UnitTestCase;
use CustomKings\CKPP_Utils;
use Brain\Monkey\Functions;

class UtilsTest extends UnitTestCase
{
    protected $utils;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->utils = new CKPP_Utils();
    }
    
    public function test_generate_uuid()
    {
        $uuid1 = $this->utils->generate_uuid();
        $uuid2 = $this->utils->generate_uuid();
        
        // Verify the UUID format (version 4)
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid1
        );
        
        // Verify two generated UUIDs are different
        $this->assertNotEquals($uuid1, $uuid2);
    }
    
    public function test_is_json()
    {
        $this->assertTrue($this->utils->is_json('{}'));
        $this->assertTrue($this->utils->is_json('{"key":"value"}'));
        $this->assertTrue($this->utils->is_json('[]'));
        $this->assertTrue($this->utils->is_json('123'));
        $this->assertTrue($this->utils->is_json('"string"'));
        $this->assertTrue($this->utils->is_json('true'));
        $this->assertTrue($this->utils->is_json('false'));
        $this->assertTrue($this->utils->is_json('null'));
        
        $this->assertFalse($this->utils->is_json(''));
        $this->assertFalse($this->utils->is_json('{'));
        $this->assertFalse($this->utils->is_json('{"key":"value"'));
        $this->assertFalse($this->utils->is_json('string'));
    }
    
    public function test_array_get()
    {
        $array = [
            'key1' => 'value1',
            'nested' => [
                'key2' => 'value2',
                'key3' => [
                    'key4' => 'value4'
                ]
            ]
        ];
        
        // Test getting top-level key
        $this->assertEquals('value1', $this->utils->array_get($array, 'key1'));
        
        // Test getting nested key with dot notation
        $this->assertEquals('value2', $this->utils->array_get($array, 'nested.key2'));
        
        // Test getting deeply nested key
        $this->assertEquals('value4', $this->utils->array_get($array, 'nested.key3.key4'));
        
        // Test default value when key doesn't exist
        $this->assertNull($this->utils->array_get($array, 'nonexistent'));
        $this->assertEquals('default', $this->utils->array_get($array, 'nonexistent', 'default'));
    }
    
    public function test_sanitize_output()
    {
        // Test string sanitization
        $this->assertEquals('test', $this->utils->sanitize_output('test'));
        $this->assertEquals('test', $this->utils->sanitize_output('<script>test</script>'));
        $this->assertEquals('test', $this->utils->sanitize_output(' test '));
        
        // Test array sanitization
        $input = [
            'key1' => ' value1 ',
            'key2' => [' nested ' => ' value2 '],
            'key3' => '<script>alert(1)</script>'
        ];
        
        $expected = [
            'key1' => 'value1',
            'key2' => ['nested' => 'value2'],
            'key3' => ''
        ];
        
        $this->assertEquals($expected, $this->utils->sanitize_output($input));
    }
    
    public function test_format_bytes()
    {
        $this->assertEquals('1 KB', $this->utils->format_bytes(1024));
        $this->assertEquals('1 MB', $this->utils->format_bytes(1024 * 1024));
        $this->assertEquals('1 GB', $this->utils->format_bytes(1024 * 1024 * 1024));
        $this->assertEquals('1 TB', $this->utils->format_bytes(1024 * 1024 * 1024 * 1024));
        $this->assertEquals('1,024 TB', $this->utils->format_bytes(1024 * 1024 * 1024 * 1024 * 1024));
        
        // Test with custom precision
        $this->assertEquals('1.50 KB', $this->utils->format_bytes(1536, 2));
    }
    
    public function test_get_mime_type()
    {
        // Test with filename
        $this->assertEquals('image/png', $this->utils->get_mime_type('test.png'));
        $this->assertEquals('image/jpeg', $this->utils->get_mime_type('test.jpg'));
        $this->assertEquals('image/jpeg', $this->utils->get_mime_type('test.jpeg'));
        $this->assertEquals('image/gif', $this->utils->get_mime_type('test.gif'));
        $this->assertEquals('application/pdf', $this->utils->get_mime_type('test.pdf'));
        $this->assertEquals('application/octet-stream', $this->utils->get_mime_type('test.unknown'));
        
        // Test with URL
        $this->assertEquals('image/png', $this->utils->get_mime_type('http://example.com/test.png'));
        
        // Test with data URL
        $this->assertEquals('image/png', $this->utils->get_mime_type('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    }
    
    public function test_generate_random_string()
    {
        // Test default length (10)
        $str1 = $this->utils->generate_random_string();
        $this->assertEquals(10, strlen($str1));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $str1);
        
        // Test custom length
        $str2 = $this->utils->generate_random_string(20);
        $this->assertEquals(20, strlen($str2));
        
        // Test custom character set
        $str3 = $this->utils->generate_random_string(10, 'abc');
        $this->assertMatchesRegularExpression('/^[abc]+$/', $str3);
    }
    
    public function test_array_to_html_attributes()
    {
        $attributes = [
            'id' => 'test-id',
            'class' => 'test-class',
            'data-test' => 'test-value',
            'disabled' => true,
            'readonly' => false,
        ];
        
        $expected = 'id="test-id" class="test-class" data-test="test-value" disabled';
        $this->assertEquals($expected, $this->utils->array_to_html_attributes($attributes));
    }
    
    public function test_is_associative_array()
    {
        $this->assertTrue($this->utils->is_associative_array(['key' => 'value']));
        $this->assertTrue($this->utils->is_associative_array([1 => 'one', 'two' => 2]));
        $this->assertFalse($this->utils->is_associative_array([]));
        $this->assertFalse($this->utils->is_associative_array([1, 2, 3]));
        $this->assertFalse($this->utils->is_associative_array('string'));
        $this->assertFalse($this->utils->is_associative_array(123));
    }
    
    public function test_kses_allowed_html()
    {
        $allowed = $this->utils->kses_allowed_html();
        
        $this->assertIsArray($allowed);
        $this->assertArrayHasKey('div', $allowed);
        $this->assertArrayHasKey('span', $allowed);
        $this->assertArrayHasKey('a', $allowed);
        $this->assertArrayHasKey('img', $allowed);
        
        // Test with custom tags
        $custom_allowed = $this->utils->kses_allowed_html(['custom-tag']);
        $this->assertArrayHasKey('custom-tag', $custom_allowed);
    }
}
