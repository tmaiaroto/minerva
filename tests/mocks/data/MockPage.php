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

class MockPage extends \minerva\models\Page {

	public $action_redirects = array(
		'index' => '/'
	);

	public static function find($type = 'all', array $options = array()) {
		$now = date('Y-m-d h:i:s');

		switch ($type) {
			case 'first':
				return new Document(array('data' => array(
					'_id' => 1,
					'title' => 'Test Page',
					'url' => 'test-page',
					'published' => true,
					'created' => $now,
					'modified' => $now
				)));
				break;
			case 'all':
			default :
				return new DocumentSet(array('data' => array(
					array(
						'_id' => 1,
						'title' => 'Test Page',
						'url' => 'test-page',
						'published' => true,
						'created' => $now,
						'modified' => $now
					),
					array(
						'_id' => 2,
						'title' => 'Second Test Page',
						'url' => 'second-test-page',
						'published' => true,
						'created' => $now,
						'modified' => $now
					),
					array(
						'_id' => 3,
						'title' => 'Third Test Page',
						'url' => 'third-test-page',
						'published' => false,
						'created' => $now,
						'modified' => $now
					)
				)));
				break;
		}
	}
}

?>