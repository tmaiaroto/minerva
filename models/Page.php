<?php
namespace app\models;

use \lithium\util\Validator;
use lithium\util\Inflector as Inflector;

class Page extends \lithium\data\Model {
	
	// $fields gets appended to with the libary's Page model $fields property.
	// This is ultimately responsible for dynamically generating forms to manage page records (not to be confused with $_schema).
	public static $fields = array(
			'url' => array('label' => 'URL'), 
			'title' => array('label' => 'Title'),			
			'created' => array('type' => 'hidden', 'label' => false), 
			'modified' => array('type' => 'hidden', 'label' => false)
	);
	
	// Validation rules that can not be overwritten (we have to depend on these to always be there)
	public static $validate = array(
		// Maybe don't require URL as it can be derived from the title
		/*'url' => array(
                    array('notEmpty', 'message' => 'URL cannot be empty'),
                ),*/
		'title' => array(
                    array('notEmpty', 'message' => 'Title cannot be empty'),
                )
	);
	
	public static function __init() {
		// Defining public static $fields in the library's Page model extends (but never overwrites) Page model fields (for forms).
		self::$fields += static::$fields;
		self::$validate += static::$validate;		
		
		// FILTERS
		// First, a save filter to change created and modified dates on the record as well as ensuring a unique pretty url.
		Page::applyFilter('save', function($self, $params, $chain) {
			/* If checked like: if(!$params['entity']->{Page::key()}) ... it would create an _id (or whatever the key is) on the entity 
			 * key which would cause a problem, it would save records with a null _id and then the second time around would have 
			 * a duplicate key error. So it has to be checked like: if(!isset(...)) ... If it is set, then we know it's an update.
			 * If it's an update, we want to set the modified date, otherwise we set both dates if it's a brand new record.
			*/  
			if (!isset($params['entity']->{Page::key()})) {
				$params['entity']->created = date('Y-m-d h:i:s');
				$params['entity']->modified = date('Y-m-d h:i:s');
			} else {  	
				$params['data']['modified'] = date('Y-m-d h:i:s');
				unset($params['data']['created']);
			}
		  
			// Assign a unique pretty URL (slug).
			if (!isset($params['entity']->{Page::key()})) {				
				// If no URL was specified, use the title, we know there has to be a title
				if(empty($params['entity']->url)) {
					$params['entity']->url = $params['entity']->title;
				}
				
				$params['entity']->url = Page::unique_url(Inflector::slug($params['entity']->url));
			} else {
				// If no URL was specified, use the title (not sure if this will be likely though on update)
				if(empty($params['data']['url'])) {
					$params['data']['url'] = $params['data']['title'];
				}
				$params['data']['url'] = Page::unique_url(Inflector::slug($params['data']['url']), $params['data'][Page::key()]);
			}
			//var_dump($params['data']); exit();
			return $chain->next($self, $params, $chain);
		});
		
		// Second, let's get the validation rules picked up from our $validate property
		Page::applyFilter('validates', function($self, $params, $chain) {
			// var_dump($this->validates); // Original $validates property (Lithium's default way)
			// var_dump(Page::$validate); // New $validate static property (Minerva's extensible way)
			$params['options']['rules'] = Page::$validate;			
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