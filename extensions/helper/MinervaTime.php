<?php
/**
 * Minerva's Time Helper
 * Formats dates, provides date choosers, and other date and time related functions.
 * 
 * Greatly in part by: https://github.com/alkemann/AL13/blob/master/al13_helpers/extensions/helper/Time.php
 * With some additional methods specifically geared toward Minerva.
 * The main reason why Minerva simply doesn't use the al13_helpers is because of the desire 
 * to reduce the number of dependencies. However, those are some great helpers to use in your app!
 * 
*/
namespace minerva\extensions\helper;

use lithium\util\Inflector;

use Exception;
use DateTime;
use DateInterval;

class MinervaTime extends \lithium\template\helper\Html {

    const DAY = 86400;
	const HOUR = 3600;

	/**
	 * Question if supplied date is 'today', 'yesterday', 'this week' etc
	 *
	 * Valid questions:
	 *  - today
	 *  - yesterday
	 *  - tomorrow
	 *  - this week
	 *  - this month
	 *  - this year
	 *  - leap year
	 *
	 * @param string $question
	 * @param mixed $date string|int|null
	 * @param array $options
	 * @return boolean
	 */
	public function is($question, $date = null, array $options = array()) {	
		switch ($question) {
			case 'leap year' :	
				$date = $date ?: date('Y-m-d H:i:s');
                // for MongoDate objects that were not passed using 'sec'
                $date = (is_object($date)) ? $date->sec:$date; 
				$date = new DateTime(is_int($date) ? date('Y-m-d H:i:s', $date) : $date);
				return $date->format('L');				
			default:
				return $this->_relativeCheck($question, $date, $options);
		}
	}

	/**
	 * Convert given date (or current if null) to 'nice', 'short' or 'words' etc
	 *
	 * Valid types:
	 *  - nice
	 *  - short
	 *  - words
	 *  - unix
	 *  - atom
	 *  - rss
	 *  - cookie
	 *
	 * @param string $type
	 * @param mixed $date string|int|null
	 * @param array $options
	 * @return string 
	 */
	public function to($type, $date = null, array $options = array()) {
		$defaults = array('format' => 'j/n/y');
		$options += $defaults;

		switch (strtolower($type)) {
			case 'format':
                // for MongoDate objects that were not passed using 'sec'
                $date = (is_object($date)) ? $date->sec:$date; 
				return $this->format($options['format'], $date);
			case 'nice':
				$offset = (isset($options['offset'])) ? $options['offset'] : 0;
				return $this->_nice($date, $offset);
			case 'nice_meridiem_short':
            case 'nicemeridiemshort':
            case 'short_meridiem':
			case 'shortmeridiem':
            case 'meridiemshort':
            case 'meridiem_short':
                $offset = (isset($options['offset'])) ? $options['offset'] : 0;
				return $this->_short($date, $offset, true);
            case 'nice_meridiem':
            case 'nicemeridiem':
            case 'meridiem':
                $offset = (isset($options['offset'])) ? $options['offset'] : 0;
				return $this->_nice($date, $offset, 'l, F jS Y, g:ia');
            case 'niceshort':
			case 'short':
				$offset = (isset($options['offset'])) ? $options['offset'] : 0;
				return $this->_short($date, $offset);
			case 'unix' : case 'Unix' : case 'UNIX' :
				if ($date == null) return time();
				$date = $date ?: date('Y-m-d H:i:s');
                $date = (is_object($date)) ? $date->sec:$date;
				$date = new DateTime(is_int($date) ? date('Y-m-d H:i:s', $date) : $date);
				return $date->format('U');
			case 'rss':
				$date = $date ?: date('Y-m-d H:i:s');
                $date = (is_object($date)) ? $date->sec:$date;
				$date = new DateTime(is_int($date) ? date('Y-m-d H:i:s', $date) : $date);
				return $date->format(DateTime::RSS);
			case 'atom':
				$date = $date ?: date('Y-m-d H:i:s');
                $date = (is_object($date)) ? $date->sec:$date;
				$date = new DateTime(is_int($date) ? date('Y-m-d H:i:s', $date) : $date);
				return $date->format(DateTime::ATOM);
			case 'cookie':
				$date = $date ?: date('Y-m-d H:i:s');
                $date = (is_object($date)) ? $date->sec:$date;
				$date = new DateTime(is_int($date) ? date('Y-m-d H:i:s', $date) : $date);
				return $date->format(DateTime::COOKIE);
			case 'words':
			case 'relative':
			default:
				return $this->_words($date, $options);
		}	
	}
		
	/**
	 * Format a date using the DateTime native PHP class
	 *
	 * @param string $format
	 * @param mixed $data string|int|null
	 * @return string
	 */
	public function format($format, $date = null) {		
		$date = $date ?: date('Y-m-d H:i:s');
        // for MongoDate objects that were not passed using 'sec'
        $date = (is_object($date)) ? $date->sec:$date; 
		$date = new DateTime(is_int($date) ? date('Y-m-d H:i:s', $date) : $date);
		return $date->format($format);
	}

	private function _relativeCheck($question, $date = null, array $options = array()) {
		$defaults = array('offset' => 0, 'now' => date('Y-m-d H:i:s'));
		$options += $defaults;
		$now = $options['now'];
		$date = $date ?: date('Y-m-d H:i:s');
        // for MongoDate objects that were not passed using 'sec'
        $date = (is_object($date)) ? $date->sec:$date; 
		$date = new DateTime(is_int($date) ? date('Y-m-d H:i:s', $date) : $date);
		$now = new DateTime(is_int($now) ? date('Y-m-d H:i:s', $now) : $now);

		switch ($question) {
			case 'today' :
				return $date->format('dmy') == $now->format('dmy');
			case 'tomorrow' :	
				$now->add(DateInterval::createFromDateString('1 day'));
				return $date->format('dmy') == $now->format('dmy');
			case 'yesterday' :	
				$now->add(DateInterval::createFromDateString('-1 day'));
				return $date->format('dmy') == $now->format('dmy');
			case 'this week' :
				return $date->format('Wy') == $now->format('Wy');
			case 'this month' :
				return $date->format('my') == $now->format('my');
			case 'this year' :
				return $date->format('y') == $now->format('y');
		}	
		throw new Exception('Illegal $question parameter');
		return null;
	}
	
	/**
	 * Format date to 'D, M jS Y, H:i'
	 *
	 * @param mixed $date
	 * @param int $offset hours
	 * @return string
	 */
	private function _nice($date, $offset = 0, $date_format='D, M jS Y, H:i') {
		$date = $date ?: date($format);
        // for MongoDate objects that were not passed using 'sec'
        $date = (is_object($date)) ? $date->sec:$date; 
        $date = new DateTime(is_int($date) ? date('Y-m-d H:i:s', $date) : $date);

		if ($offset) {
			$date->add(DateInterval::createFromDateString("{$offset} hours"));
		}
		return $date->format($date_format);
	}
		
	/**
	 * Format date to "M jS y, H:i", or 'Today, H:i' or similar
	 *
	 * @param mixed $date
	 * @param int $offset hours
	 * @param boolean $meridem (uses 12hr format with meridem and no trailing 0's)
	 * @return string
	 */
	private function _short($date = null, $offset = 0, $meridem=false) {
		$now = new DateTime();
		$date = $date ?: date('Y-m-d H:i:s');
        // for MongoDate objects that were not passed using 'sec'
        $date = (is_object($date)) ? $date->sec:$date; 
		$date = new DateTime(is_int($date) ? date('Y-m-d H:i:s', $date) : $date);
	
		if ($offset) {
			$date->add(DateInterval::createFromDateString("{$offset} hours"));
		}
		$diff = $date->diff($now);
		$y = ($diff->format('%y') != 0) ? ' Y' : '';
		$onlyDay = ($diff->format('%y%m') == '00');
		$dayDirection = $diff->format('%R');

		switch (true) {
			case ($diff->d == 0 && $onlyDay) :
				$text = 'Today, %s';
				$format = ($meridem) ? 'g:ia':'H:i';
			break;
			case ($diff->d == 1 && $dayDirection == '+' && $onlyDay) :
				$text = 'Yesterday, %s';
				$format = ($meridem) ? 'g:ia':'H:i';
			break;
			case ($diff->d == 1 && $dayDirection == '-' && $onlyDay) :
				$text = 'Tomorrow, %s';
				$format = ($meridem) ? 'g:ia':'H:i';
			break;
			default : 
				$text = null;
				$format = ($meridem) ? "M jS{$y}, g:ia":"M jS{$y}, H:i";
		}
		$ret = $date->format($format);

		return ($text) ? sprintf($text, $ret) : $ret;
	}
	
	/**
	 * Convert date to relative worded string like "1 week, 2 days ago"
	 *
	 * @param mixed $date
	 * @param array $options
	 * @return string
	 */
	protected function _words($date, array $options = array()) {
		$defaults = array(
			'offset' => 0, 'format' => 'j/n/y', 'end' => '+1 month', 'now' => date('Y-m-d H:i:s')
		);
		$options += $defaults;

		$now = $options['now'];
		$now = new DateTime(is_int($now) ? date('Y-m-d H:i:s', $now) : $now);

		$date = $date ?: date('Y-m-d H:i:s');
        // for MongoDate objects that were not passed using 'sec'
        $date = (is_object($date)) ? $date->sec:$date; 
		$date = new DateTime(is_int($date) ? date('Y-m-d H:i:s', $date) : $date);
		$keys = $this->diff($date, compact('now') + $options);

		if ($end = $options['end']) {
			$end = new DateTime(($date > $now ? '+' : '-') . $end);
			$outOfBounds = (($date > $now && $date > $end) || ($date < $now && $date < $end));

			if ($outOfBounds) {
				return 'on ' . $date->format($options['format']);
			}
		}

		$strings = array(
			'y' => array('year', 'years'),
			'm' => array('month', 'months'),
			'w' => array('week', 'weeks'),
			'd' => array('day', 'days'),
			'h' => array('hour', 'hours'),
			'i' => array('minute', 'minutes'),
			's' => array('second', 'seconds')
		);
		$result = array();

		foreach ($strings as $key => $text) {
			if (!$value = $keys[$key]) {
				continue;
			}
			$result[] = $value . ' ' . $text[($value == 1) ? 0 : 1];
		}
		return join(', ', $result) . ($date < $now ? ' ago' : '');
	}

	/**
	 * Calculate the different between date and now
	 *
	 * @param mixed $date
	 * @param array $options
	 * @return array
	 */
	private function _diff($date, array $options = array()) {
		$defaults = array('now' => date('Y-m-d'), 'offset' => 0, 'weeks' => true);
		$options += $defaults;

		$date = is_object($date) ? $date : new DateTime($date);
		$now = is_object($options['now']) ? $options['now'] : new DateTime($options['now']);

		if ($offset = $options['offset']) {
			$date->add(DateInterval::createFromDateString("{$offset} hours"));
		}

		$diff = $date->diff($now);
		$keys = (array) $diff + array('w' => 0);

		if ($keys['d'] >= 7 && $options['weeks']) {
			$keys['w'] = floor($keys['d'] / 7);
			$keys['d'] -= ($keys['w'] * 7);
		}
		return $keys;
	}
    
}
?>