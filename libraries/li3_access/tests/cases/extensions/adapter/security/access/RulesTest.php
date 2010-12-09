<?php
/**
 * li3_access plugin for Lithium: the most rad php framework.
 *
 * @author        Tom Maiaroto
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
*/

namespace li3_access\tests\cases\extensions\adapter\security\access;

use \li3_access\security\Access;
use \lithium\net\http\Request;

class RulesTest extends \lithium\test\Unit {

    public function setUp() {
	Access::config(array(
	    'test_rulebased' => array(
	        'adapter' => 'Rules'
	    )
	));
	
	Access::adapter('test_rulebased')->add('testDeny', function($user, $request, $options) {
	    return false;
	});
    }
    
    public function tearDown() {}
    
    public function testCheck() {
	$request = new Request();
	
	// Multiple rules, they should all pass
        $rules = array(
            array('rule' => 'allowAnyUser', 'message' => 'You must be logged in.'),
            array('rule' => 'allowIp', 'message' => 'You can not access this from your location. (IP: ' . $_SERVER['REMOTE_ADDR'] . ')', 'ip' => $_SERVER['REMOTE_ADDR'])
        );
        $expected = array();
        $result = Access::check('test_rulebased', array('username' => 'Tom'), $request, array('rules' => $rules));
        $this->assertEqual($expected, $result);
	
	// Single rule, but it should fail because user is an empty array
	$rules = array('rule' => 'allowAnyUser', 'message' => 'You must be logged in.');
        $expected = array('message' => 'You are not permitted to access this area.', 'redirect' => '/');
        $result = Access::check('test_rulebased', array(), $request, array('rules' => $rules));
        $this->assertEqual($expected, $result);
	// and if false instead of an empty array (because one might typically run Auth:check() which could return false)
	$result = Access::check('test_rulebased', false, $request, array('rules' => $rules));
        $this->assertEqual($expected, $result);
    
	// The add() method to add a rule
	$rules = array(
	    array('rule' => 'testDeny', 'message' => 'Access denied.')
        );
	$expected = array('rule' => 'testDeny', 'message' => 'Access denied.', 'redirect' => '/');
	$result = Access::check('test_rulebased', array('username' => 'Tom'), $request, array('rules' => $rules));
	$this->assertEqual($expected, $result);
	
	// Adding a rule "on the fly" by passing a closure, this rule should pass
	$rules = array(
	    array('rule' => function($user, $request, $options) { return $user['username'] == 'Tom'; }, 'message' => 'Access denied.')
        );
	$expected = array();
	$result = Access::check('test_rulebased', array('username' => 'Tom'), $request, array('rules' => $rules));
	$this->assertEqual($expected, $result);
	
    }
    
}    
?>