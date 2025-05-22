<?php

namespace CustomKings\Tests\Unit;

use CustomKings\Tests\TestCase\UnitTestCase;
use CustomKings\CKPP_Security;
use Brain\Monkey\Functions;

class SecurityTest extends UnitTestCase
{
    protected $security;

    protected function setUp(): void
    {
        parent::setUp();
        $this->security = new CKPP_Security();
    }

    public function test_verify_nonce_success()
    {
        // Mock the wp_verify_nonce function
        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'action')
            ->andReturn(1);
            
        $this->assertTrue($this->security->verify_nonce('valid_nonce', 'action'));
    }

    public function test_verify_nonce_failure()
    {
        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('invalid_nonce', 'action')
            ->andReturn(false);
            
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Security check failed');
        
        $this->security->verify_nonce('invalid_nonce', 'action');
    }

    public function test_verify_capability_success()
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);
            
        $this->assertTrue($this->security->verify_capability('manage_options'));
    }

    public function test_verify_capability_failure()
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(false);
            
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient permissions');
        
        $this->security->verify_capability('manage_options');
    }

    public function test_sanitize_input()
    {
        // Test string sanitization
        $this->assertEquals('test', $this->security->sanitize_input(' test ', 'text'));
        
        // Test integer sanitization
        $this->assertSame(42, $this->security->sanitize_input('42', 'int'));
        
        // Test array sanitization
        $input = ['key' => ' value '];
        $expected = ['key' => 'value'];
        $this->assertEquals($expected, $this->security->sanitize_input($input, 'array'));
        
        // Test JSON sanitization
        $json = '{"key":"value"}';
        $this->assertEquals(['key' => 'value'], $this->security->sanitize_input($json, 'json'));
    }
}
