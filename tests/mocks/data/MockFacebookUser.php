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

class MockFacebookUser extends \minerva\models\User {

	public static function find($type = 'all', array $options = array()) {
		switch ($type) {
			case 'first':
				return new Document(array('data' => array(
					'_id' => 1,
					'email' => null,
					'first_name' => null,
					'last_name' => null,
					'facebook_uid' => '100'
				)));
				break;
			case 'all':
			default :
				return new DocumentSet(array('data' => array(
					array(
						'_id' => 1,
						'email' => null,
						'first_name' => null,
						'last_name' => null,
						'facebook_uid' => '100'
					),
					array(
						'_id' => 2,
						'email' => null,
						'first_name' => null,
						'last_name' => null,
						'facebook_uid' => '200'
					),
					array(
						'_id' => 3,
						'email' => null,
						'first_name' => null,
						'last_name' => null,
						'facebook_uid' => '300'
					)
				)));
				break;
		}
	}
}

?>