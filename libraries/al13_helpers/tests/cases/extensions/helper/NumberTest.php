<?php
/**
 * NumberTest file
 *
 * @copyright     Copyright 2010, alkemann
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace al13_helpers\tests\cases\extensions\helper;

use \al13_helpers\extensions\helper\Number;
use \lithium\tests\mocks\template\helper\MockFormRenderer;

/**
 * NumberHelperTest class
 *
 */
class NumberTest extends \lithium\test\Unit {

	/**
	 * helper property
	 *
	 * @var mixed null
	 * @access public
	 */
	var $helper = null;
	
	/**
	 * setUp method
	 *
	 * @access public
	 * @return void
	 */
	function setUp() {
		$this->Number = new Number(array('context' => new MockFormRenderer()));
	}
	
	/**
	 * tearDown method
	 *
	 * @access public
	 * @return void
	 */
	function tearDown() {
		unset($this->Number);
	}
	
	/**
	 * testFormatAndCurrency method
	 *
	 * @access public
	 * @return void
	 */
	function testFormatAndCurrency() {
		$value = '100100100';

		$result = $this->Number->format($value, '#');
		$expected = '#100,100,100';
		$this->assertEqual($expected, $result);

		$result = $this->Number->format($value, 3);
		$expected = '100,100,100.000';
		$this->assertEqual($expected, $result);

		$result = $this->Number->format($value);
		$expected = '100,100,100';
		$this->assertEqual($expected, $result);

		$result = $this->Number->format($value, '-');
		$expected = '100-100-100';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value);
		$expected = '$100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, '#');
		$expected = '#100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, false);
		$expected = '100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD');
		$expected = '$100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;100.100.100,00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '&#163;100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, '', array(
			'thousands' =>' ', 'after' => '€', 'decimals' => ',', 'zero' => 'Gratuit'
		));
		$expected = '100 100 100,00€';
		$this->assertEqual($expected, $result);

	}
	
	/**
	 * testCurrencyPositive method
	 *
	 * @access public
	 * @return void
	 */
	function testCurrencyPositive() {
		$value = '100100100';

		$result = $this->Number->currency($value);
		$expected = '$100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD', array('before'=> '#'));
		$expected = '#100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, false);
		$expected = '100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD');
		$expected = '$100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;100.100.100,00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '&#163;100,100,100.00';
		$this->assertEqual($expected, $result);
	}
	
	/**
	 * testCurrencyNegative method
	 *
	 * @access public
	 * @return void
	 */
	function testCurrencyNegative() {
		$value = '-100100100';

		$result = $this->Number->currency($value);
		$expected = '($100,100,100.00)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '(&#8364;100.100.100,00)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '(&#163;100,100,100.00)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD', array('negative'=>'-'));
		$expected = '-$100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR', array('negative'=>'-'));
		$expected = '-&#8364;100.100.100,00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('negative'=>'-'));
		$expected = '-&#163;100,100,100.00';
		$this->assertEqual($expected, $result);

	}
	
	/**
	 * testCurrencyCentsPositive method
	 *
	 * @access public
	 * @return void
	 */
	function testCurrencyCentsPositive() {
		$value = '0.99';

		$result = $this->Number->currency($value, 'USD');
		$expected = '99c';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '99c';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '99p';
		$this->assertEqual($expected, $result);

	}
	
	/**
	 * testCurrencyCentsNegative method
	 *
	 * @access public
	 * @return void
	 */
	function testCurrencyCentsNegative() {
		$value = '-0.99';

		$result = $this->Number->currency($value, 'USD');
		$expected = '(99c)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '(99c)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '(99p)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD', array('negative'=>'-'));
		$expected = '-99c';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR', array('negative'=>'-'));
		$expected = '-99c';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('negative'=>'-'));
		$expected = '-99p';
		$this->assertEqual($expected, $result);

	}
	
	/**
	 * testCurrencyZero method
	 *
	 * @access public
	 * @return void
	 */
	function testCurrencyZero() {
		$value = '0';

		$result = $this->Number->currency($value, 'USD');
		$expected = '$0.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;0,00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '&#163;0.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('zero'=> 'FREE!'));
		$expected = 'FREE!';
		$this->assertEqual($expected, $result);

	}
	
	/**
	 * testCurrencyOptions method
	 *
	 * @access public
	 * @return void
	 */
	function testCurrencyOptions() {
		$value = '1234567.89';

		$result = $this->Number->currency($value, null, array('before'=>'GBP'));
		$expected = 'GBP1,234,567.89';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('places'=>0));
		$expected = '&#163;1,234,568';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('escape'=>true));
		$expected = '&amp;#163;1,234,567.89';
		$this->assertEqual($expected, $result);

	}
	
	/**
	 * testToReadableSize method
	 *
	 * @access public
	 * @return void
	 */
	function testToReadableSize() {
		$result = $this->Number->toReadableSize(0);
		$expected = '0 Bytes';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1);
		$expected = '1 Byte';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(45);
		$expected = '45 Bytes';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1023);
		$expected = '1023 Bytes';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024);
		$expected = '1 KB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*512);
		$expected = '512 KB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024-1);
		$expected = '1.00 MB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*512);
		$expected = '512.00 MB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024-1);
		$expected = '1.00 GB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024*512);
		$expected = '512.00 GB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024*1024-1);
		$expected = '1.00 TB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024*1024*512);
		$expected = '512.00 TB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024*1024*1024-1);
		$expected = '1024.00 TB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024*1024*1024*1024);
		$expected = (1024 * 1024) . '.00 TB';
		$this->assertEqual($expected, $result);
	}
	
	/**
	 * testToPercentage method
	 *
	 * @access public
	 * @return void
	 */
	function testToPercentage() {
		$result = $this->Number->toPercentage(45, 0);
		$expected = '45%';
		$this->assertEqual($result, $expected);

		$result = $this->Number->toPercentage(45, 2);
		$expected = '45.00%';
		$this->assertEqual($result, $expected);

		$result = $this->Number->toPercentage(0, 0);
		$expected = '0%';
		$this->assertEqual($result, $expected);

		$result = $this->Number->toPercentage(0, 4);
		$expected = '0.0000%';
		$this->assertEqual($result, $expected);
	}
}

?>