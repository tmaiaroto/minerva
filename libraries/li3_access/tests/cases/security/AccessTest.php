<?php
/**
 * li3_access plugin for Lithium: the most rad php framework.
 *
 * @author        Tom Maiaroto
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
*/

namespace li3_access\tests\cases\security;

use \li3_access\security\Access;
use \lithium\net\http\Request;

class AccessTest extends \lithium\test\Unit {

    public function setUp() {
        Access::config(array(
            'test_access' => array(
                'adapter' => 'Simple'
            ),
            'test_access_with_filters' => array(
                'filters' => array(
                    function($self, $params, $chain) {
                        // Any config can have filters that get applied
                        var_dump('filter on check, applied from Access::confg() in minerva_boostrap.php');
                        exit();
                        return $chain->next($self, $params, $chain);
                    }
                )
            )
        ));
    }

    public function tearDown() {}
    
    public function testCheck() {
        $request = new Request();
        
        $expected = array();
        $result = Access::check('test_access', array('username' => 'Tom'), $request);
        $this->assertEqual($expected, $result);
        
        $expected = array('message' => 'You are not permitted to access this area.', 'redirect' => '/');
        $result = Access::check('test_access', false, $request);
        $this->assertEqual($expected, $result);
    }
    
}    
?>