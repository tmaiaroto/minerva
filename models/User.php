<?php
namespace app\models;

use \lithium\util\Validator;
use lithium\util\Inflector as Inflector;

class User extends \lithium\data\Model {
	
	// User model also can be extended...Plugins can maybe add features like "Profile Pages" or "Twitter Feeds" etc.
	
	// I get appended to with the plugin's User model.
	public static $fields = array(
			'url' => array('label' => 'URL'), 
			'username' => array('label' => 'Username'),
			'password' => array('label' => 'Password'),
			'created' => array('type' => 'hidden', 'label' => false), 
			'modified' => array('type' => 'hidden', 'label' => false)
	);
	
	public static $validate = array(
		'username' => array(
                    array('notEmpty', 'message' => 'Username cannot be empty'),                  
                )
		// TODO: password confirm
	);
	
	public static function __init() {
		// Defining public static $fields in the library's User model extends (but never overwrites) User model fields (for forms).
		self::$fields += static::$fields;
		self::$validate += static::$validate;
		
		// FILTERS
		User::applyFilter('save', function($self, $params, $chain) {
			// First, the created and modified dates
			if (!isset($params['entity']->{User::key()})) {
			  $params['entity']->created = date('Y-m-d h:i:s');
			  $params['entity']->modified = date('Y-m-d h:i:s');
			} else {  	
			      $params['data']['modified'] = date('Y-m-d h:i:s');  	
			}
			
			// Second, assign a unique pretty URL (slug).
			if (!isset($params['entity']->{User::key()})) {				
				// If no URL was specified, use the title, we know there has to be a title
				if(empty($params['entity']->url)) {
					$params['entity']->url = $params['entity']->username;
				}
				$params['entity']->url = User::unique_url(Inflector::slug($params['entity']->url));
			} else {
				// If no URL was specified, use the title (not sure if this will be likely though on update)
				if(empty($params['data']['url'])) {
					$params['data']['url'] = $params['data']['username'];
				}
				$params['data']['url'] = User::unique_url(Inflector::slug($params['data']['url']), $params['data'][User::key()]);
			}
			
			return $chain->next($self, $params, $chain);
		});
		
		// Third, let's get the validation rules picked up from our $validate property
		Page::applyFilter('validates', function($self, $params, $chain) {
			$params['options']['rules'] = User::$validate;			
			return $chain->next($self, $params, $chain);
		});
		
		parent::__init();
	}
	
	// TODO: Move to some sort of app model?
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