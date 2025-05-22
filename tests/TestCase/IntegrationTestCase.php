<?php

namespace CustomKings\Tests\TestCase;

use WP_UnitTestCase;
use Brain\Monkey;

/**
 * Base test case for all integration tests.
 */
class IntegrationTestCase extends WP_UnitTestCase
{
    /**
     * Setup which runs before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        
        // Set up any test data or mocks needed for integration tests
        $this->setUpTestData();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
    
    /**
     * Set up test data for integration tests.
     * Override this method in your test classes.
     */
    protected function setUpTestData(): void
    {
        // Can be overridden by child classes
    }
}
