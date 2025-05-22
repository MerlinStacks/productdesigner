<?php

namespace CustomKings\Tests\Integration;

use WP_REST_Request;
use WP_REST_Server;
use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_REST_API;

class RestAPITest extends IntegrationTestCase
{
    protected $rest_api;
    protected $test_data;
    protected $server;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize the REST server
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server();
        do_action('rest_api_init');
        
        $this->rest_api = new CKPP_REST_API();
        $this->test_data = TestHelper::setup_test_data();
        
        // Create a test user with admin capabilities
        $this->user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->user_id);
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Reset the REST server
        global $wp_rest_server;
        $wp_rest_server = null;
        
        parent::tearDown();
    }
    
    public function test_register_routes()
    {
        $routes = $this->server->get_routes();
        
        // Check if our routes are registered
        $this->assertArrayHasKey('/customkings/v1/designs', $routes);
        $this->assertArrayHasKey('/customkings/v1/designs/(?P<id>\d+)', $routes);
        $this->assertArrayHasKey('/customkings/v1/designs/(?P<id>\d+)/preview', $routes);
    }
    
    public function test_get_designs()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        $request = new WP_REST_Request('GET', '/customkings/v1/designs');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals($design['post']->ID, $data[0]['id']);
        $this->assertEquals($design['post']->post_title, $data[0]['title']);
    }
    
    public function test_get_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        $request = new WP_REST_Request('GET', '/customkings/v1/designs/' . $design['post']->ID);
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertEquals($design['post']->ID, $data['id']);
        $this->assertEquals($design['post']->post_title, $data['title']);
        $this->assertArrayHasKey('config', $data);
        $this->assertArrayHasKey('preview', $data);
    }
    
    public function test_create_design()
    {
        $request = new WP_REST_Request('POST', '/customkings/v1/designs');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode([
            'title' => 'New Test Design',
            'config' => [
                'elements' => [
                    [
                        'type' => 'text',
                        'content' => 'New Design',
                        'position' => ['x' => 100, 'y' => 100],
                        'styles' => [
                            'color' => '#000000',
                            'fontSize' => 24,
                            'fontFamily' => 'Arial',
                        ]
                    ]
                ],
                'settings' => [
                    'width' => 800,
                    'height' => 600,
                    'background' => '#ffffff',
                ]
            ],
            'preview' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
        ]));
        
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(201, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('New Test Design', $data['title']);
        $this->assertArrayHasKey('config', $data);
        $this->assertArrayHasKey('preview', $data);
        
        // Clean up
        wp_delete_post($data['id'], true);
    }
    
    public function test_update_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        $request = new WP_REST_Request('PUT', '/customkings/v1/designs/' . $design['post']->ID);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode([
            'title' => 'Updated Design Title',
            'config' => [
                'elements' => [
                    [
                        'type' => 'text',
                        'content' => 'Updated Text',
                        'position' => ['x' => 200, 'y' => 200],
                        'styles' => [
                            'color' => '#ff0000',
                            'fontSize' => 32,
                            'fontFamily' => 'Arial',
                        ]
                    ]
                ],
                'settings' => [
                    'width' => 1000,
                    'height' => 800,
                    'background' => '#f0f0f0',
                ]
            ],
            'preview' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
        ]));
        
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertEquals($design['post']->ID, $data['id']);
        $this->assertEquals('Updated Design Title', $data['title']);
        $this->assertEquals('Updated Text', $data['config']['elements'][0]['content']);
        $this->assertEquals(1000, $data['config']['settings']['width']);
    }
    
    public function test_delete_design()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        $design_id = $design['post']->ID;
        
        $request = new WP_REST_Request('DELETE', '/customkings/v1/designs/' . $design_id);
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertTrue($data['deleted']);
        $this->assertEquals($design_id, $data['previous']['id']);
        
        // Verify the design was actually deleted
        $this->assertNull(get_post($design_id));
    }
    
    public function test_get_design_preview()
    {
        if (is_wp_error($this->test_data)) {
            $this->markTestSkipped('Test data setup failed: ' . $this->test_data->get_error_message());
            return;
        }
        
        $design = $this->test_data['designs'][0];
        
        $request = new WP_REST_Request('GET', '/customkings/v1/designs/' . $design['post']->ID . '/preview');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // Verify the response headers
        $headers = $response->get_headers();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertStringStartsWith('image/png', $headers['Content-Type']);
        
        // Verify the response body is not empty
        $this->assertNotEmpty($response->get_data());
    }
}
