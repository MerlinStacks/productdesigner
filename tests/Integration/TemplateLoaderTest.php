<?php

namespace CustomKings\Tests\Integration;

use CustomKings\Tests\TestCase\IntegrationTestCase;
use CustomKings\Tests\Helpers\TestHelper;
use CustomKings\CKPP_Template_Loader;

class TemplateLoaderTest extends IntegrationTestCase
{
    protected $template_loader;
    protected $test_data;
    protected $template_path;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->template_loader = new CKPP_Template_Loader();
        $this->test_data = TestHelper::setup_test_data();
        
        // Set up a test template directory
        $this->template_path = WP_CONTENT_DIR . '/themes/twentytwentyone/ckpp/';
        if (!file_exists($this->template_path)) {
            mkdir($this->template_path, 0755, true);
        }
    }
    
    protected function tearDown(): void
    {
        if ($this->test_data && !is_wp_error($this->test_data)) {
            TestHelper::cleanup_test_data($this->test_data);
        }
        
        // Clean up test template files
        if (file_exists($this->template_path . 'single-ckpp_design.php')) {
            unlink($this->template_path . 'single-ckpp_design.php');
        }
        
        if (file_exists($this->template_path . 'archive-ckpp_design.php')) {
            unlink($this->template_path . 'archive-ckpp_design.php');
        }
        
        if (file_exists($this->template_path)) {
            rmdir($this->template_path);
        }
        
        parent::tearDown();
    }
    
    public function test_get_template_part()
    {
        // Create a test template file in the theme
        $template_content = '<?php /* Test Template */ ?>\n<div class="ckpp-test-template">Test Content</div>';
        file_put_contents($this->template_path . 'test-template.php', $template_content);
        
        // Capture the output
        ob_start();
        $this->template_loader->get_template_part('test-template');
        $output = ob_get_clean();
        
        // Verify the template was loaded
        $this->assertStringContainsString('Test Content', $output);
    }
    
    public function test_template_loader()
    {
        // Create a test single template in the theme
        $single_template = '<?php /* Single Design Template */ ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
    </header>
    <div class="entry-content">
        <?php the_content(); ?>
    </div>
</article>';
        
        file_put_contents($this->template_path . 'single-ckpp_design.php', $single_template);
        
        // Set up the test post
        $post_id = $this->factory->post->create([
            'post_type' => 'ckpp_design',
            'post_title' => 'Test Design',
            'post_content' => 'This is a test design content.',
            'post_status' => 'publish',
        ]);
        
        // Set up the global post
        global $post;
        $post = get_post($post_id);
        setup_postdata($post);
        
        // Test the template loading
        $template = $this->template_loader->template_loader('single.php');
        
        // Verify the correct template was loaded
        $this->assertEquals($this->template_path . 'single-ckpp_design.php', $template);
        
        // Clean up
        wp_reset_postdata();
    }
    
    public function test_get_template_paths()
    {
        $paths = $this->template_loader->get_template_paths();
        
        // Verify the paths array is not empty
        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);
        
        // Verify the theme path is included
        $theme_path = get_stylesheet_directory() . '/ckpp/';
        $this->assertContains($theme_path, $paths);
        
        // Verify the plugin path is included
        $plugin_path = CKPP_PLUGIN_DIR . 'templates/';
        $this->assertContains($plugin_path, $paths);
    }
    
    public function test_locate_template()
    {
        // Create a test template in the theme
        $template_content = '<?php /* Test Locate Template */ ?>\n<div class="ckpp-test-locate">Test Locate</div>';
        $template_file = $this->template_path . 'test-locate.php';
        file_put_contents($template_file, $template_content);
        
        // Locate the template
        $found = $this->template_loader->locate_template('test-locate.php');
        
        // Verify the template was found
        $this->assertEquals($template_file, $found);
    }
}
