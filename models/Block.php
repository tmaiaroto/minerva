<?php
namespace minerva\models;

use \lithium\util\Validator;
use lithium\util\Inflector as Inflector;

class Block extends \lithium\data\Model {
	
	protected $_schema = array(
		'_id' => array('type' => 'id', 'form' => array('type' => 'hidden', 'label' => false)),
		// library gets indexed
		'library' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		// url possibly gets indexed
		'url' => array('type' => 'string', 'form' => array('label' => 'URL')), 
		'title' => array('type' => 'string', 'form' => array('label' => 'Title')),
		'content' => array('type' => 'string', 'form' => array('label' => 'Block Content', 'type' => 'textarea', 'after' => '(you may use html code)')),	
		// options contain all sorts of misc. data like "weight" or "position" or "is_menu" it could also contain "pubished" flags if desired, but minerva core doesn't care about publish status on blocks so no dedicated field is set for it
		// the "is_menu" is set true if the MenusController saves the block
		'options' => array('type' => 'array', 'form' => array('type' => 'hidden', 'label' => false)),
		'created' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)), 
		'modified' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false))		
	);
	
	protected $_meta = array('locked' => true);
	
	public $validates = array(
		'title' => array(
                    array('notEmpty', 'message' => 'Title cannot be empty'),
                )
	);
	
	public static function __init() {
		$class =  __CLASS__;
		$extended_schema = static::_object()->_schema;
		// Loop through and ensure no one forgot to set the form key
		// TODO: see if there's a more graceful way to do this
		foreach($extended_schema as $k => $v) {
			if(!isset($extended_schema[$k]['form'])) {
				$extended_schema[$k]['form'] = array();	
			}			
		}
		// Append extended schema
		$class::_object()->_schema += $extended_schema;
		// Also append extended validation rules
		$class::_object()->validates += static::_object()->validates;
		
		// Don't forget me...
		parent::__init();
	}
	
	
	public function unique_url($url=null, $id=null) {
		if(!$url) {
			return null;
		}
		
		$records = Block::find('all', array('fields' => array('url'), 'conditions' => array('url' => array('like' => '/'.$url.'/'))));
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

/* FILTERS
 *
 * Filters must be set down here outside the class because of the class extension by libraries.
 * If the filter was applied within __init() it would run more than once.
 *
*/
Block::applyFilter('save', function($self, $params, $chain) {	
	// Set the created and modified dates and pretty url (slug)
	$now = date('Y-m-d h:i:s');
	if (!$params['entity']->exists()) {
		$params['data']['created'] = $now;
		$params['data']['modified'] = $now;				
		if(empty($params['data']['url'])) {
			$params['data']['url'] = $params['data']['title'];
		}
		$params['data']['url'] = Block::unique_url(Inflector::slug($params['data']['url']), $params['data'][Block::key()]);
	} else {
		$params['data']['url'] = Block::unique_url(Inflector::slug($params['data']['url']), $params['data'][Block::key()]);
		$params['data']['modified'] = $now;
	}
	
	return $chain->next($self, $params, $chain);
});
?>