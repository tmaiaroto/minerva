<?php
/**
 * li3_flash_message plugin for Lithium: the most rad php framework.
 *
 * @copyright     Copyright 2010, Michael Hüneburg
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_flash_message\tests\cases\extensions\storage;

use \li3_flash_message\extensions\storage\FlashMessage;
use \lithium\storage\Session;

class FlashMessageTest extends \lithium\test\Unit {

	public function _init() {
		Session::config(array(
			'flash_message' => array(
				'adapter' => '\lithium\tests\mocks\storage\session\adapter\MockPhp'
			)
		));
	}
	
	public function tearDown() {
		Session::delete('FlashMessage');
	}
	
	public function testSet() {
		FlashMessage::set('Foo');
		$expected = array('message' => 'Foo', 'atts' => array());
		$result = Session::read('FlashMessage.default', array('name' => 'flash_message'));
		$this->assertEqual($expected, $result);
		
		FlashMessage::set('Foo 2', array('type' => 'notice'));
		$expected = array('message' => 'Foo 2', 'atts' => array('type' => 'notice'));
		$result = Session::read('FlashMessage.default', array('name' => 'flash_message'));
		$this->assertEqual($expected, $result);
		
		FlashMessage::set('Foo 3', array(), 'TestKey');
		$expected = array('message' => 'Foo 3', 'atts' => array());
		$result = Session::read('FlashMessage.TestKey', array('name' => 'flash_message'));
		$this->assertEqual($expected, $result);
	}
	
	public function testGet() {
		FlashMessage::set('Foo');
		$expected = array('message' => 'Foo', 'atts' => array());
		$result = FlashMessage::get();
		$this->assertEqual($expected, $result);
		
		FlashMessage::set('Foo 2', array('type' => 'notice'));
		$expected = array('message' => 'Foo 2', 'atts' => array('type' => 'notice'));
		$result = FlashMessage::get();
		$this->assertEqual($expected, $result);
		
		FlashMessage::set('Foo 3', array(), 'TestKey');
		$expected = array('message' => 'Foo 3', 'atts' => array());
		$result = FlashMessage::get('TestKey');
		$this->assertEqual($expected, $result);
	}
	
	public function testClear() {
		FlashMessage::set('Foo');
		FlashMessage::clear();
		$result = Session::read('FlashMessage.default', array('name' => 'flash_message'));
		$this->assertNull($result);
		
		FlashMessage::set('Foo 2', array(), 'TestKey');
		FlashMessage::clear('TestKey');
		$result = Session::read('FlashMessage.TestKey', array('name' => 'flash_message'));
		$this->assertNull($result);
		
		FlashMessage::set('Foo 3', array(), 'TestKey2');
		FlashMessage::set('Foo 4', array(), 'TestKey3');
		FlashMessage::clear(null);
		$result = Session::read('FlashMessage', array('name' => 'flash_message'));
		$this->assertNull($result);
	}

}

?>