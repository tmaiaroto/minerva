<?php
/**
 * li3_flash_message plugin for Lithium: the most rad php framework.
 *
 * @copyright     Copyright 2010, Michael Hüneburg
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
 
namespace li3_flash_message\extensions\storage;

use \lithium\storage\Session;

/**
 * Class for setting, getting and clearing flash messages. Use this class inside your
 * controllers to set messages for your views.
 *
 * The class requires a configuration named `flash_message` for `\lithium\storage\Session`, e.g.:
 * {{{
 * Session::config(array(
 *     'flash_message' => array('adapter' => 'Php')
 * ));
 * }}}
 *
 * After that you can easily set messages and output them in your views. For example:
 * {{{
 * // Controller
 * if (empty($data)) {
 *     FlashMessage::set('Invalid data.');
 * }
 *
 * // View
 * <?=$this->flashMessage->output(); ?>
 * }}}
 */
class FlashMessage extends \lithium\core\StaticObject {
	
	/**
	 * Holds the instance of the session storage class
	 *
	 * @see \lithium\storage\Session
	 */
	protected static $_storage = null;

	protected static $_classes = array(
		'session' => '\lithium\storage\Session'
	);
	
	/**
	 * Initializes the session class.
	 *
	 * @return void
	 */
	public static function __init() {
		static::$_storage = static::$_classes['session'];
	}
	
	/**
	 * Sets a flash message.
	 *
	 * @param string $message Message that will be stored.
	 * @param array [$atts] Optional attributes that will be available in the view.
	 * @param string [$key] Optional key to store multiple flash messages.
	 * @return void
	 */
	public static function set($message, array $atts = array(), $key = 'default') {
		$storage = static::$_storage;
		$storage::write("FlashMessage.{$key}", compact('message', 'atts'), array('name' => 'flash_message'));
	}
	
	/**
	 * Gets the a flash message.
	 *
	 * @param string [$key] Optional key.
	 * @return array
	 */
	public static function get($key = 'default') {
		$storage = static::$_storage;
		$flash = $storage::read("FlashMessage.{$key}", array('name' => 'flash_message'));
		return $flash;
	}
	
	/**
	 * Clears one or all flash messages from the storage.
	 *
	 * @param string [$key] Optional key. Set this to `null` to delete all flash messages.
	 * @return void
	 */
	public static function clear($key = 'default') {
		$storage = static::$_storage;
		$sessionKey = 'FlashMessage';
		if (!empty($key)) {
			$sessionKey .= ".{$key}"; 
		}
		$storage::delete($sessionKey, array('name' => 'flash_message'));
	}
	
}

?>