<?php

namespace CustomKings\Tests\Unit;

use CustomKings\Tests\TestCase\UnitTestCase;
use CustomKings\CKPP_Design_Exporter;
use Brain\Monkey\Functions;
use Mockery;

class DesignExporterTest extends UnitTestCase
{
    protected $exporter;
    protected $design_id = 123;
    protected $design_data = [
        'elements' => [
            [
                'type' => 'text',
                'content' => 'Test Design',
                'position' => ['x' => 100, 'y' => 100],
                'styles' => [
                    'color' => '#000000',
                    'fontSize' => 24,
                    'fontFamily' => 'Arial',
                ]
            ]
        ]
    ];
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new CKPP_Design_Exporter();
        
        // Mock the necessary WordPress functions
        Functions\when('get_post_meta')
            ->justReturn(json_encode($this->design_data));
            
        Functions\when('get_post')
            ->justReturn((object) [
                'post_title' => 'Test Design',
                'post_content' => 'Test design description',
                'post_author' => 1,
            ]);
            
        Functions\when('get_user_by')
            ->justReturn((object) [
                'display_name' => 'Test User',
                'user_email' => 'test@example.com',
            ]);
    }
    
    public function test_export_design_json()
    {
        // Call the method
        $result = $this->exporter->export_design($this->design_id, 'json');
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('filename', $result['data']);
        $this->assertArrayHasKey('content', $result['data']);
        $this->assertStringEndsWith('.json', $result['data']['filename']);
        
        // Verify the content
        $content = json_decode($result['data']['content'], true);
        $this->assertEquals('Test Design', $content['title']);
        $this->assertEquals('Test design description', $content['description']);
        $this->assertEquals('Test User', $content['author']['name']);
        $this->assertEquals($this->design_data['elements'], $content['elements']);
    }
    
    public function test_export_design_png()
    {
        // Mock the image generation
        $image_mock = Mockery::mock('overload:Imagick');
        $image_mock->shouldReceive('newImage')
            ->once()
            ->with(800, 600, 'white', 'png');
            
        $image_mock->shouldReceive('setImageFormat')
            ->once()
            ->with('png');
            
        $image_mock->shouldReceive('getImageBlob')
            ->once()
            ->andReturn('binary image data');
            
        $image_mock->shouldReceive('clear')
            ->once();
            
        $image_mock->shouldReceive('destroy')
            ->once();
        
        // Call the method
        $result = $this->exporter->export_design($this->design_id, 'png');
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('filename', $result['data']);
        $this->assertArrayHasKey('content', $result['data']);
        $this->assertStringEndsWith('.png', $result['data']['filename']);
        $this->assertEquals('binary image data', $result['data']['content']);
    }
    
    public function test_export_design_pdf()
    {
        // Mock the PDF generation
        $pdf_mock = Mockery::mock('overload:TCPDF');
        $pdf_mock->shouldReceive('AddPage')
            ->once();
            
        $pdf_mock->shouldReceive('SetFont')
            ->once()
            ->with('helvetica', 'B', 16);
            
        $pdf_mock->shouldReceive('Cell')
            ->once()
            ->with(0, 10, 'Test Design', 0, 1, 'C');
            
        $pdf_mock->shouldReceive('SetFont')
            ->once()
            ->with('helvetica', '', 12);
            
        $pdf_mock->shouldReceive('MultiCell')
            ->once()
            ->with(0, 10, 'Test design description', 0, 'C');
            
        $pdf_mock->shouldReceive('Output')
            ->once()
            ->with('', 'S')
            ->andReturn('binary pdf data');
        
        // Call the method
        $result = $this->exporter->export_design($this->design_id, 'pdf');
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('filename', $result['data']);
        $this->assertArrayHasKey('content', $result['data']);
        $this->assertStringEndsWith('.pdf', $result['data']['filename']);
        $this->assertEquals('binary pdf data', $result['data']['content']);
    }
    
    public function test_export_design_invalid_format()
    {
        // Call the method with an invalid format
        $result = $this->exporter->export_design($this->design_id, 'invalid');
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Invalid export format', $result['message']);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
