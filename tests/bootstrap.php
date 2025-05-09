<?php
// tests/bootstrap.php

// Include the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Initialize WP_Mock
WP_Mock::bootstrap();

// You can add other test setup logic here if needed

// Teardown WP_Mock
WP_Mock::tearDown();