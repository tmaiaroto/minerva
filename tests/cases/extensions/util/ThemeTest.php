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
        
        $paths_for_pages_index = array(
            'layout' => array(
                LITHIUM_APP_PATH . '/views/minerva/_plugin//layouts/{:layout}.{:type}.php',
                LITHIUM_APP_PATH . '/views/minerva/layouts/{:layout}.{:type}.php',
                '{:library}/views/layouts/{:layout}.{:type}.php',
                LITHIUM_APP_PATH . '/libraries/minerva/views/layouts/{:layout}.{:type}.php'
            ),
            'template' => array(
                LITHIUM_APP_PATH . '/views/minerva/_plugin//{:controller}/{:template}.{:type}.php',
                LITHIUM_APP_PATH . '/views/minerva/{:controller}/{:template}.{:type}.php',
                '{:library}/views/{:controller}/{:template}.{:type}.php',
                LITHIUM_APP_PATH . '/libraries/minerva/views/{:controller}/{:template}.{:type}.php'
            )
        );
        
        $this->assertEqual($paths_for_pages_index, $result);
        
        // Test for an admin request
        $this->request->params = array(
            'library' => 'minerva',
            'controller' => 'pages',
            'action' => 'index',
            'admin' => 'admin'
        );
        
        $result = Theme::setRenderPaths($this->request);
        
        $paths_for_pages_admin_index = array(
            'layout' => array(
                LITHIUM_APP_PATH . '/views/minerva/_plugin//_admin/layouts/{:layout}.{:type}.php', 
                LITHIUM_APP_PATH . '/views/minerva/_admin/layouts/{:layout}.{:type}.php',
                '{:library}/views/_admin/layouts/{:layout}.{:type}.php',
                LITHIUM_APP_PATH . '/libraries/minerva/views/_admin/layouts/{:layout}.{:type}.php'
            ),
            'template' => array(
                LITHIUM_APP_PATH . '/views/minerva/_plugin//_admin/{:controller}/{:template}.{:type}.php',
                LITHIUM_APP_PATH . '/views/minerva/_admin/{:controller}/{:template}.{:type}.php',
                '{:library}/views/_admin/{:controller}/{:template}.{:type}.php',
                LITHIUM_APP_PATH . '/libraries/minerva/views/_admin/{:controller}/{:template}.{:type}.php'
            )
        );
        
        $this->assertEqual($paths_for_pages_admin_index, $result);
        
        // Test for static render paths
        $this->request->params = array(
            'library' => 'minerva',
            'controller' => 'pages',
            'action' => 'view'
        );
        
        $result = Theme::setRenderPaths($this->request);
        
        $paths_for_static_pages = array(
            'layout' => array(
                LITHIUM_APP_PATH . '/views/minerva/_plugin//layouts/static/{:layout}.{:type}.php',
                LITHIUM_APP_PATH . '/views/minerva/layouts/static/{:layout}.{:type}.php',
                '{:library}/views/layouts/static/{:layout}.{:type}.php',
                LITHIUM_APP_PATH . '/libraries/minerva/views/layouts/static/{:layout}.{:type}.php'
            ),
            'template' => array(
                LITHIUM_APP_PATH . '/views/minerva/_plugin//{:controller}/static/{:template}.{:type}.php',
                LITHIUM_APP_PATH . '/views/minerva/{:controller}/static/{:template}.{:type}.php',
                '{:library}/views/{:controller}/static/{:template}.{:type}.php',
                LITHIUM_APP_PATH . '/libraries/minerva/views/{:controller}/static/{:template}.{:type}.php'
            )
        );
        
        $this->assertEqual($paths_for_static_pages, $result);
        
        // Test for admin static render paths
        $this->request->params = array(
            'library' => 'minerva',
            'controller' => 'pages',
            'action' => 'view',
            'admin' => 'admin'
        );
        
        $result = Theme::setRenderPaths($this->request);
        
        $paths_for_admin_static_pages = array(
            'layout' => array(
                LITHIUM_APP_PATH . '/views/minerva/_plugin//_admin/layouts/static/{:layout}.{:type}.php',
                LITHIUM_APP_PATH . '/views/minerva/_admin/layouts/static/{:layout}.{:type}.php',
                '{:library}/views/_admin/layouts/static/{:layout}.{:type}.php',
                LITHIUM_APP_PATH . '/libraries/minerva/views/_admin/layouts/static/{:layout}.{:type}.php'
            ),
            'template' => array(
                LITHIUM_APP_PATH . '/views/minerva/_plugin//_admin/{:controller}/static/{:template}.{:type}.php',
                LITHIUM_APP_PATH . '/views/minerva/_admin/{:controller}/static/{:template}.{:type}.php',
                '{:library}/views/_admin/{:controller}/static/{:template}.{:type}.php',
                LITHIUM_APP_PATH . '/libraries/minerva/views/_admin/{:controller}/static/{:template}.{:type}.php'
            )
        );
        
        $this->assertEqual($paths_for_admin_static_pages, $result);
        
        // Test with plugin for render paths
        $this->request->params = array(
            'library' => 'minerva',
            'controller' => 'pages',
            'action' => 'index',
            'plugin' => 'minerva_blog'
        );
        
        $result = Theme::setRenderPaths($this->request);
        
        $paths_for_plugin = array(
            'layout' => array(
                LITHIUM_APP_PATH . '/views/minerva/_plugin/minerva_blog/layouts/{:layout}.{:type}.php',
                LITHIUM_APP_PATH . '/views/minerva/layouts/{:layout}.{:type}.php',
                '{:library}/views/layouts/{:layout}.{:type}.php',
                LITHIUM_APP_PATH . '/libraries/minerva/views/layouts/{:layout}.{:type}.php'
            ),
            'template' => array(
                LITHIUM_APP_PATH . '/views/minerva/_plugin/minerva_blog/{:controller}/{:template}.{:type}.php',
                LITHIUM_APP_PATH . '/views/minerva/{:controller}/{:template}.{:type}.php',
                '{:library}/views/{:controller}/{:template}.{:type}.php',
                LITHIUM_APP_PATH . '/libraries/minerva/views/{:controller}/{:template}.{:type}.php'
            )
        );
        
        $this->assertEqual($paths_for_plugin, $result);
        
    }
    
}
?>