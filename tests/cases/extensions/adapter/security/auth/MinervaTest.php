<?php
/**
 * Note: Tests are already done by Lithium's core FormTest, so we're just going to extend that.
 * Additionally, when using li3_facebook library (when being key)... That library has its own tests.
 *
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace minerva\tests\cases\extensions\adapter\security\auth;

use lithium\action\Request;
use lithium\core\Libraries;
use lithium\core\ConfigException;

class MinervaTest extends \lithium\tests\cases\security\auth\adapter\FormTest {

	/**
	 * @var Request $request Object
	 */
	public $request;

	public function setUp() {
		$this->request = new Request();
	}

	public function tearDown() {

	}
}

?>