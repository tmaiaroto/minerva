<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace minerva\tests\cases\controllers;

use minerva\models\User;
use minerva\models\Page;
use minerva\controllers\MinervaController;
use lithium\action\Request;
use lithium\security\Auth;

class MinervaControllerTest extends \lithium\test\Unit {

	public $request;
	public $controller;
	public $admin_auth_data;
	public $user_auth_data;

	public function setUp() {
		$this->request = new Request();
		$this->request->params = array(
			'library' => 'minerva',
			'controller' => 'pages',
			'action' => 'index',
				//  'admin' => 'admin'
		);

		$this->controller = new MinervaController(array('request' => $this->request));

		$this->admin_auth_data = array(
			'first_name' => 'John',
			'last_name' => 'Doe',
			'email' => 'john@doe.com',
			'role' => 'administrator',
			'active' => true,
			'url' => 'john-doe',
			'password' => 'password'
		);

		$this->user_auth_data = $this->admin_auth_data;
		$this->user_auth_data['role'] = 'registered_user';

		Auth::set('minerva_user', $this->admin_auth_data);

		$now = date('Y-m-d h:i:s');
		$Page = Page::create();
		$data = array(
			'title' => 'Test Page',
			'url' => 'test-page',
			'published' => true,
			'created' => $now,
			'modified' => $now
		);
		$Page->save($data);

		$Page = Page::create();
		$data['title'] = 'Second Test Page';
		$data['url'] = 'second-test-page';
		$Page->save($data);
	}

	public function tearDown() {
		$documents = Page::find('all', array(
			'conditions' => array('url' => array('test-page', 'second-test-page'))
		));
		if (is_object($documents)) {
			foreach ($documents as $document) {
				$document->delete();
			}
		}
		Auth::clear('minerva_user');
	}

	public function testGetRedirects() {
		$result = $this->controller->getRedirects();
		$this->assertTrue(is_array($result));

		// again with admin requests
		$this->request->params['admin'] = $admin = 'admin';
		$Controller = new MinervaController(array('request' => $this->request));

		$result = $Controller->getRedirects();
		$this->assertTrue(is_array($result));

		// test custom action redirects
		Page::actionRedirects(array('create' => '/'));
		$Controller = new MinervaController(array('request' => $this->request));
		$result = $Controller->getRedirects();
		$this->assertEqual('/', $result['create']);

		//$result = Page::actionRedirects();
		// var_dump($result);
	}

	public function testGetDocument() {
		// find_type will be false, but the request is authenticated, it will return true
		// for having permission
		$this->assertTrue($this->controller->getDocument());

		// for find_type first
		$result = $this->controller->getDocument(array(
			'find_type' => 'first',
			'conditions' => array('url' => 'test-page')
				));
		$this->assertTrue(count($result) == 1);
		$this->assertTrue(is_object($result));

		// for find with an offset (pagination)
		$result = $this->controller->getDocument(array(
			'find_type' => 'first',
			'offset' => 1
				));
		$this->assertEqual('second-test-page', $result->url);

		// for find with an offset greater than the number of results
		$result = $this->controller->getDocument(array(
			'find_type' => 'first',
			'conditions' => array('url' => 'test-page'),
			'offset' => 1
				));
		$this->assertFalse($result);

		// for find_type all
		$result = $this->controller->getDocument(array(
			'find_type' => 'all',
			'order' => 'title.desc'
				));
		$this->assertTrue(count($result) == 2);

		// test for restricted access to admin index
		// add to the request the admin parameter
		$request = $this->request;
		$request->params['admin'] = 'admin';

		// currently authenticated with an 'administrator' role so this will return true
		$result = $this->controller->getDocument(array(
			'request' => $request
				));
		$this->assertTrue($result);

		// set the auth over again, this time with a normal 'registered_user' role
		Auth::set('minerva_user', $this->user_auth_data);
		$result = $this->controller->getDocument(array(
			'find_type' => 'all',
			'request' => $request
				));
		$this->assertFalse($result);
	}

	// tests a lot of the Minerva controller initialization process as well as the method
	// to return it
	public function testMinervaConfig() {
		$result = $this->controller->getMinervaConfig();
		$this->assertTrue(is_array($result));
		$this->assertTrue(isset($result['model']) && $result['model'] == 'Page');

		$this->request->params['admin'] = $admin = 'admin';
		$this->request->params['plugin'] = 'blog';
		$Controller = new MinervaController(array('request' => $this->request));
		$result = $Controller->getMinervaConfig();
		$this->assertTrue(is_array($result));
		$this->assertTrue(isset($result['document_type']) && $result['document_type'] == 'blog');
	}
}

?>