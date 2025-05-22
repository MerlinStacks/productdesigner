<?php

namespace CustomKings\Tests\TestCase;

use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;

/**
 * Base test case for all unit tests.
 */
class UnitTestCase extends PHPUnit_TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Setup which runs before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
