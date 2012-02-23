<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace minerva\tests\mocks\data;

use lithium\data\collection\DocumentSet;
use lithium\data\entity\Document;

class MockUser extends \minerva\models\User {

	public $login_redirect = '/home';
	public $user_roles = array(
		'admin' => 'Administrator'
	);

	public static function find($type = 'all', array $options = array()) {
		switch ($type) {
			case 'first':
				return new Document(array('data' => array(
					'_id' => 1,
					'email' => 'someone@somewhere.com',
					'first_name' => 'John',
					'last_name' => 'Doe'
				)));
				break;
			case 'all':
			default :
				return new DocumentSet(array('data' => array(
					array(
						'_id' => 1,
						'email' => 'someone@somewhere.com',
						'first_name' => 'John',
						'last_name' => 'Doe'
					),
					array(
						'_id' => 2,
						'email' => 'someone_else@somewhere.com',
						'first_name' => 'Henry',
						'last_name' => 'Ford'
					),
					array(
						'_id' => 3,
						'email' => 'someone_again@somewhere.com',
						'first_name' => 'Betty',
						'last_name' => 'Crocker'
					)
				)));
				break;
		}
	}
}

?>