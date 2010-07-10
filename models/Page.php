<?php
namespace minerva\models;

use \lithium\util\Validator;
use lithium\util\Inflector as Inflector;

class Page extends \lithium\data\Model {
	
	/**
	 * $_schema gets appended to with the libary Page model's protected $_schema property.
	 * The key 'form' is new and gets used by the forms in the create/update templates, unless custom create/update
	 * templates are made and don't use that key but, that's the default process. 
	*/
	protected $_schema = array(
		'_id' => array('type' => 'id', 'form' => array('type' => 'hidden', 'label' => false)),
		'library' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'title' => array('type' => 'string', 'form' => array('label' => 'Title')),
		'created' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'modified' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),		
		'url' => array('type' => 'string', 'form' => array())
	);
	
	protected $_meta = array('locked' => true);
	
	// Defined as normal and the library Page model's $validates is also defined as normal, they will be combined.
	public $validates = array(
		'title' => array(
                    array('notEmpty', 'message' => 'Title cannot be empty'),
                )
	);
	
	public static function __init() {		
		/**
		 * New way to define schema, form fields (and options), and validation rules,
		 * over setting: self::$validate += static::$validate; and then using a filter to use rules from that new property.
		 * Now, it follows Lithium convention a bit more and gives a dual purpose for $_schema. Setting form input
		 * fields (via helper) and locking the schema so only data set by this model or the library's model gets saved.
		*/
		$class =  __CLASS__;
		$extended_schema = static::_object()->_schema;
		// Loop through and ensure no one forgot to set the form key		
		foreach($extended_schema as $k => $v) {
			$extended_schema[$k] += array('form' => array());
		}
		// Append extended schema
		$class::_object()->_schema += $extended_schema;
		// Also append extended validation rules
		$class::_object()->validates += static::_object()->validates;
		
		
		// FILTERS
		// First, a save filter to change created and modified dates on the record as well as ensuring a unique pretty url.
		Page::applyFilter('save', function($self, $params, $chain) {
			// Set the created and modified dates and pretty url (slug)
			$now = date('Y-m-d h:i:s');
			if (!$params['entity']->exists()) {
				$params['data']['created'] = $now;
				$params['data']['modified'] = $now;
				if(empty($params['data']['url'])) {
					$params['data']['url'] = $params['data']['title'];
				}
				$params['data']['url'] = Page::unique_url(Inflector::slug($params['data']['url']), $params['data'][Page::key()]);
			} else {
				$params['data']['url'] = Page::unique_url(Inflector::slug($params['data']['url']), $params['data'][Page::key()]);
				$params['data']['modified'] = $now;
			}
			
			//var_dump($params['data']); exit();
			return $chain->next($self, $params, $chain);
		});		
		
		// Don't forget me...
		parent::__init();
	}	
	
	public function unique_url($url=null, $id=null) {
		if(!$url) {
			return null;
		}
		
		$records = Page::find('all', array('fields' => array('url'), 'conditions' => array('url' => array('like' => '/'.$url.'/'))));
		$conflicts = array();
		
		foreach($records as $record) {
			$conflicts[] = $record->url;
		}
		
		if (!empty($conflicts)) {
			$firstSlug = $url;
			$i = 1;
			while($i > 0) {
				// TODO: Maybe make separator option somewhere as a property? So it can be _ instead of -
				if (!in_array($firstSlug . '-' . $i, $conflicts)) {					
					$url = $firstSlug . '-' . $i;
					$i = -1;
				}
                        $i++;
			}
		}
		
		return $url;
	}

}
?>