<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
*/
namespace minerva\tests\cases\extensions\util;

use minerva\extensions\util\Util;
use minerva\tests\mocks\data\MockPage;

class UtilTest extends \lithium\test\Unit {
    
    public function setUp() {}

    public function tearDown() {}
    
    public function testListTypes() {
        $result = Util::listTypes();
        $this->assertTrue(is_array($result));
        
        $result = Util::listTypes('Page', array('exclude_minerva' => true));
        $this->assertTrue(!in_array('minerva\models\Page', $result));
    }
    
    public function testFormatDotOrder() {
        $result = Util::formatDotOrder('title.asc');
        $this->assertEqual(array('title', 'asc'), $result);
    }
    
    public function testUniqueUrl() {
        $result = Util::uniqueUrl(array(
            'url' => 'test-page',
            'model' => 'minerva\tests\mocks\data\MockPage'
        ));
        $this->assertEqual('test-page-1', $result);
        
    }
    
    public function testUniqueString() {
        $result = Util::uniqueString();
        $this->assertTrue(is_string($result) && !empty($result));
        
        $result = Util::uniqueString(array('hash' => 'sha1'));
        $this->assertEqual(40, strlen($result));
        
        $result = Util::uniqueString(array('hash' => 'md5'));
        $this->assertEqual(32, strlen($result));
    }
    
    public function testInArrayRecursive() {
        $haystack = array(
            'big' => array(
                'deep' => array(
                    'array' => 'foo'
                )
            )
        );
        $result = Util::inArrayRecursive('foo', $haystack);
        $this->assertTrue($result);
        
        $haystack = array(
            'big' => array(
                'foo'
            )
        );
        $result = Util::inArrayRecursive('foo', $haystack);
        $this->assertTrue($result);
        
        $result = Util::inArrayRecursive('', $haystack);
        $this->assertFalse($result);
        
        $result = Util::inArrayRecursive('', array());
        $this->assertFalse($result);
        
        $result = Util::inArrayRecursive('foo', array());
        $this->assertFalse($result);
    }
    
}
?>