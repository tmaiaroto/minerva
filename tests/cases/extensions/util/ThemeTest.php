<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
*/
namespace minerva\tests\cases\extensions\util;

use minerva\extensions\util\Theme;
use lithium\action\Request;

class ThemeTest extends \lithium\test\Unit {
    
    var $request;
    
    public function setUp() {
        $this->request = new Request();
    }

    public function tearDown() {}
    
    public function testGetCoreStaticControllers() {
        $result = Theme::getCoreStaticControllers();
        $this->assertEqual(array('pages', 'blocks', 'menus'), $result);
    }
    
    public function testSetRenderPaths() {
        $result = Theme::setRenderPaths($this->request);
        $this->assertNull($result);
        
        // Test for a basic request
        $this->request->params = array(
            'library' => 'minerva',
            'controller' => 'pages',
            'action' => 'index'
        );
        
        $result = Theme::setRenderPaths($this->request);
        $this->assertTrue(count($result['layout'] == 3) && count($result['template'] == 3));
        
        // Test for an admin request
        $this->request->params = array(
            'library' => 'minerva',
            'controller' => 'pages',
            'action' => 'index',
            'admin' => 'admin'
        );
        
        $result = Theme::setRenderPaths($this->request);
        $this->assertEqual(LITHIUM_APP_PATH . '/views/minerva/_admin/layouts/{:layout}.{:type}.php', $result['layout'][0]);
        
        // Test for static render paths
        $this->request->params = array(
            'library' => 'minerva',
            'controller' => 'pages',
            'action' => 'view'
        );
        
        $result = Theme::setRenderPaths($this->request);
        $this->assertEqual(LITHIUM_APP_PATH . '/views/minerva/layouts/static/{:layout}.{:type}.php', $result['layout'][0]);
        
        // Test for admin static render paths
        $this->request->params = array(
            'library' => 'minerva',
            'controller' => 'pages',
            'action' => 'view',
            'admin' => 'admin'
        );
        
        $result = Theme::setRenderPaths($this->request);
        $this->assertEqual(LITHIUM_APP_PATH . '/views/minerva/_admin/layouts/static/{:layout}.{:type}.php', $result['layout'][0]);
        
        // Test with plugin for render paths
        $this->request->params = array(
            'library' => 'minerva',
            'controller' => 'pages',
            'action' => 'index',
            'plugin' => 'minerva_blog'
        );
        
        $result = Theme::setRenderPaths($this->request);
        $this->assertEqual(LITHIUM_APP_PATH . '/views/minerva/_plugin/minerva_blog/layouts/{:layout}.{:type}.php', $result['layout'][0]);
        
        // Test with plugin for static render paths
        $this->request->params = array(
            'library' => 'minerva',
            'controller' => 'pages',
            'action' => 'view',
            'plugin' => 'minerva_blog'
        );
        
        $result = Theme::setRenderPaths($this->request);
        $this->assertEqual(LITHIUM_APP_PATH . '/views/minerva/_plugin/minerva_blog/layouts/static/{:layout}.{:type}.php', $result['layout'][0]);        
    }
    
}
?>