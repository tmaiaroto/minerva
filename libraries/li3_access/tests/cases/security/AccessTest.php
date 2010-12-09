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
                'adapter' => 'Simple',
                'filters' => array(
                    function($self, $params, $chain) {
                        // Do something, maybe log something, then continue on.
                        return $chain->next($self, $params, $chain);
                    },
                    function($self, $params, $chain) {
                        if (!$params['user']) {
                            return array('message' => 'Access denied.', 'redirect' => $params['options']['redirect']);
                        } else {
                            return $chain->next($self, $params, $chain);
                        }
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
        
        $expected = array('message' => 'Access denied.', 'redirect' => '/login');
        $result = Access::check('test_access', false, $request, array('redirect' => '/login', 'message' => 'Access denied.'));
        $this->assertEqual($expected, $result);
    }
    
    public function testFilters() {
        $request = new Request();
        
        $expected = array('message' => 'Access denied.', 'redirect' => '/login');
        $result = Access::check('test_access_with_filters', false, $request, array('redirect' => '/login'));
        $this->assertEqual($expected, $result);
    }
    
    public function testNoConfigurations() {
        Access::reset();
        $this->assertIdentical(array(), Access::config());
        $this->expectException("Configuration 'test_no_config' has not been defined.");
        Access::check('test_no_config');
    }
}    
?>