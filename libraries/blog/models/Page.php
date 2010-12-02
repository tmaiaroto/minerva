<?php
namespace minerva\libraries\blog\models;

use lithium\net\http\Media;

class Page extends \minerva\models\Page {
	
	// Add new fields here
	protected $_schema = array(
		'title' => array('label' => 'Blog Title'), // this won't overwrite the main app's page models' $fields title key
		'author' => array('type' => 'string'),
		'body' => array('type' => 'string', 'form' => array('label' => 'Page Copy', 'type' => 'textarea'))
	);
	
	// Add validation rules for new fields here
	public $validates = array(
		'body' => array(
                    array('notEmpty', 'message' => 'Body cannot be empty'),
                )
	);
	
	public static function __init() {
		// Put any desired filters here
		
		parent::__init();
	}
	
}
?>