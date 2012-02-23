<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace minerva\tests\cases\controllers;

use minerva\models\User;
use minerva\controllers\UsersController;
use lithium\action\Request;
use lithium\security\Auth;

class UsersControllerTest extends \lithium\test\Unit {

	public $request;
	public $user;

	public function setUp() {
		$this->request = new Request();
		$this->request->params = array(
			'library' => 'minerva',
			'controller' => 'users'
		);

		$this->user = new UsersController(array('request' => $this->request));

		$User = User::create();
		$data = array(
			'first_name' => 'John',
			'last_name' => 'Doe',
			'email' => 'john@doe.com',
			'role' => 'administrator',
			'active' => true,
			'url' => 'john-doe',
			'password' => 'password'
		);
		$User->save($data);
		Auth::set('minerva_user', $data);
	}

	public function tearDown() {
		$documents = User::find('all', array('conditions' => array('url' => 'john-doe')));
		if (is_object($documents)) {
			foreach ($documents as $document) {
				$document->delete();
			}
		}
		Auth::clear('minerva_user');
	}

	public function testIsEmailInUse() {
		// this method will output true/false just via echo
		// TODO: change that... but for now hide the output so the test dashboard still looks pretty
		ob_start();
		$this->assertTrue($this->user->is_email_in_use('john@doe.com'));
		$this->assertFalse($this->user->is_email_in_use('foo'));
		$this->assertFalse($this->user->is_email_in_use());
		ob_end_clean();
	}

	public function testLogout() {
		$logged_in = Auth::check('minerva_user');
		$this->assertTrue(!empty($logged_in));
		$this->user->logout();
		$logged_out = Auth::check('minerva_user');
		$this->assertFalse($logged_out);
	}
}

?>