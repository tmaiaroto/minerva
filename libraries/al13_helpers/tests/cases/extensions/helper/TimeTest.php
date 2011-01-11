<?php
/**
 * TimeTest file
 *
 * @copyright     Copyright 2010, alkemann
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace al13_helpers\tests\cases\extensions\helper;

use DateTime;
use DateTimeZone;
use al13_helpers\extensions\helper\Time;
use lithium\tests\mocks\template\helper\MockFormRenderer;

/**
 * NumberHelperTest class
 *
 */
class TimeTest extends \lithium\test\Unit {

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		$this->time = new Time(array('context' => new MockFormRenderer()));
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->time);
	}
	
	public function testIs() {
		$time = time();
		$this->assertTrue( $this->time->is('today', $time) );
		$this->assertFalse( $this->time->is('tomorrow', $time) );
		$this->assertFalse( $this->time->is('yesterday', $time) );
		
		$time = time() + Time::DAY;
		$this->assertTrue( $this->time->is('tomorrow', $time) );
		$this->assertFalse( $this->time->is('today', $time) );
		$this->assertFalse( $this->time->is('yesterday', $time) );
		
		
		$time = time() - Time::DAY;
		$this->assertTrue( $this->time->is('yesterday', $time) );
		$this->assertFalse( $this->time->is('tomorrow', $time) );
		$this->assertFalse( $this->time->is('today', $time) );

		$time = time();
		$this->assertTrue( $this->time->is('this week', $time) );
		$this->assertFalse( $this->time->is('this week', 1) );
		
		$this->assertTrue( $this->time->is('this month', $time) );
		$this->assertFalse( $this->time->is('this month', 1) );
		
		$this->assertTrue( $this->time->is('this year', $time) );
		$this->assertFalse( $this->time->is('this year', 1) );
	}
	
	public function testFormat() {
		$time = time();
		$date = date('Y-m-d H:i:s', $time);
		$expected = date('YMHi', $time);
		$result = $this->time->format('YMHi', $date);
		$this->assertIdentical( $expected, $result );
		
		$format = 'D-M-Y';
		$arr = array(time(), strtotime('+1 days'), strtotime('+1 days'), strtotime('+0 days'));

		foreach ($arr as $val) {
			$this->assertEqual(date($format, $val), $this->time->format($format, $val));
		}
	}
	
	public function testToNice() {
		$time = time() + (2 * Time::DAY);
		$this->assertEqual(date('D, M jS Y, H:i', $time), $this->time->to('nice', $time));

		$time = time() - (2 * Time::DAY);
		$this->assertEqual(date('D, M jS Y, H:i', $time), $this->time->to('nice', $time));

		$time = time();
		$this->assertEqual(date('D, M jS Y, H:i', $time), $this->time->to('nice', $time));

		$time = 0;
		$this->assertEqual(date('D, M jS Y, H:i', time()), $this->time->to('nice', $time));

		$time = null;
		$this->assertEqual(date('D, M jS Y, H:i', time()), $this->time->to('nice', $time));
		
		$time = date('H:i', time());
		$expected = 'Fri, Dec 24th 2010, '.$time;
		$result = $this->time->to('nice', '2010-12-24 '.$time.':00');
		$this->assertEqual($expected, $result);
		
		$expected = 'Sat, Dec 25th 2010, '.$time;
		$result = $this->time->to('nice', '2010-12-24 '.$time.':00', array('offset' => 24));
		$this->assertEqual($expected, $result);
	}
	
	public function testNiceShort() {
		$time = time() + 2 * Time::DAY;
		if (date('Y', $time) == date('Y')) {
			$this->assertEqual(date('M jS, H:i', $time), $this->time->to('short', $time));
		} else {
			$this->assertEqual(date('M jSY, H:i', $time), $this->time->to('short', $time));
		}

		$time = time();
		$this->assertEqual('Today, '.date('H:i', $time), $this->time->to('short', $time));
		
		$time = time() + Time::DAY;
		$this->assertEqual('Tomorrow, '.date('H:i', $time), $this->time->to('short', $time));

		$time = time() - Time::DAY;
		$this->assertEqual('Yesterday, '.date('H:i', $time), $this->time->to('short', $time));
	}

	public function testToDiv() {
		$time = time();
		$this->assertEqual(date(DateTime::ATOM, $time), $this->time->to('atom', $time));
		$this->assertEqual(date(DateTime::RSS, $time), $this->time->to('rss' ,$time));
		$this->assertEqual(date(DateTime::COOKIE, $time), $this->time->to('COOKIE' ,$time));
		$this->assertEqual($time, $this->time->to('Unix', date('Y-m-d H:i:s', $time)));
		$time += Time::DAY;
		$this->assertEqual($time, $this->time->to('Unix', date('Y-m-d H:i:s', $time)));
	}
	
	public function testToWords() {
		$time = time() - 2 * Time::DAY;
		$expected = "2 days ago";
		$result = $this->time->to('words',$time);
		$this->assertEqual($expected, $result);
		
		$result = $this->time->to('words','2007-9-25');
		$this->assertEqual($result, 'on 25/9/07');

		$result = $this->time->to('words','2007-9-25', array('format' => 'Y-m-d'));
		$this->assertEqual($result, 'on 2007-09-25');

		$result = $this->time->to('words','2007-9-25', array('format' => 'Y-m-d'));
		$this->assertEqual($result, 'on 2007-09-25');

		$result = $this->time->to('words',strtotime('-2 weeks, -2 days'), array(
			'format' => 'Y-m-d'
		));
		$this->assertEqual($result, '2 weeks, 2 days ago');

		$result = $this->time->to('words',strtotime('2 weeks, 2 days'), array(
			'format' => 'Y-m-d'
		));
		$this->assertPattern('/^2 weeks, [1|2] day(s)?$/', $result);
	}

}

?>