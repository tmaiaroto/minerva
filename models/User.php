<?php
namespace minerva\models;

use \lithium\util\Validator;
use lithium\util\Inflector as Inflector;

class User extends \lithium\data\Model {
	
	// User model also can be extended...Plugins can maybe add features like "Profile Pages" or "Twitter Feeds" etc.
	
	// I get appended to with the plugin's User model.
	protected $_schema = array(
		'_id' => array('type' => 'id', 'form' => array('type' => 'hidden', 'label' => false)),
		'library' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'url' => array('type' => 'string', 'form' => array('label' => 'URL')),		
		'username' => array('type' => 'string', 'form' => array('label' => 'Username')),
		'password' => array('type' => 'string', 'form' => array('label' => 'Password')),
		'created' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'modified' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'file' => array('type' => 'string', 'form' => array('type' => 'file'))
	);
	
	protected $_meta = array('locked' => true);
	
	public $validates = array(
		'username' => array(
                    array('notEmpty', 'message' => 'Username cannot be empty'),                  
                )
		// TODO: password confirm
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
		
		// FILTERS
		User::applyFilter('save', function($self, $params, $chain) {
			// Set created, modified, and pretty url (slug)
			$now = date('Y-m-d h:i:s');
			if (!$params['entity']->exists()) {
				$params['data']['created'] = $now;
				$params['data']['modified'] = $now;
				if(empty($params['data']['url'])) {
					$params['data']['url'] = $params['data']['username'];
				}
				$params['data']['url'] = User::unique_url(Inflector::slug($params['data']['url']), $params['data'][User::key()]);
			} else {
				$params['data']['url'] = User::unique_url(Inflector::slug($params['data']['url']), $params['data'][User::key()]);
				$params['data']['modified'] = $now;
			}
			
			//$data = array($params['entity']->file);
			//Asset::save($data);
			
			var_dump($params['data']); exit();
			
			return $chain->next($self, $params, $chain);
		});		
		
		parent::__init();
	}
	
	// TODO: Move to some sort of app model?...probably a "minerva utility" class instead so other classes/libraries can utilize
	public function unique_url($url=null, $id=null) {
		if(!$url) {
			return null;
		}
		
		$records = User::find('all', array('fields' => array('url'), 'conditions' => array('url' => array('like' => '/'.$url.'/'))));
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