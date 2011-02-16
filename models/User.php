<?php
namespace minerva\models;

use \lithium\util\Validator;
use lithium\util\Inflector;
use lithium\util\String;

use \minerva\util\Email;
use \minerva\util\Util;
// use app\models\Asset;

class User extends \minerva\models\MinervaModel {
	
	// User model also can be extended...Plugins can maybe add features like "Profile Pages" or "Twitter Feeds" etc.
	
	// I get appended to with the plugin's User model.
	protected $_schema = array(
		'_id' => array('type' => 'id', 'form' => array('type' => 'hidden', 'label' => false)),
		'user_type' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'email' => array('type' => 'string', 'search' => array('weight' => 1), 'form' => array('label' => 'E-mail', 'autocomplete' => 'off')),
		'new_email' => array('type' => 'string', 'form' => array('label' => false, 'type' => 'hidden')),
		//'username' => array('type' => 'string', 'form' => array('label' => 'Username')), // going to use e-mail for username
		'password' => array('type' => 'string', 'form' => array('label' => 'Password', 'type' => 'password', 'autocomplete' => 'off')),
		'role' => array('type' => 'string', 'form' => array('type' => 'select', 'label' => 'User Role', 'position' => 'options')),
		'active' => array('type' => 'boolean', 'form' => array('type' => 'checkbox', 'label' => 'Active', 'position' => 'options')),
		'approval_code' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'created' => array('type' => 'date', 'form' => array('type' => 'hidden', 'label' => false)),
		'modified' => array('type' => 'date', 'form' => array('type' => 'hidden', 'label' => false)),
		//'profile_pics' => array('type' => 'string'),
		'last_login_ip' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'last_login_time' => array('type' => 'date', 'form' => array('type' => 'hidden', 'label' => false))
		//'file' => array('type' => 'string', 'form' => array('type' => 'file'))
	);
	
	public $search_schema = array(
		'body' => array(
			'weight' => 1
		)
	);
	
	protected $_user_roles = array(
		'administrator' => 'Administrator',
		'content_editor' => 'Content Editor',
		'registered_user' => 'Registered User'
	);
	
	public $validates = array(
		'email' => array(
                    array('notEmpty', 'message' => 'E-mail cannot be empty.'),
		    array('email', 'message' => 'E-mail is not valid.'),
		 //   array('uniqueEmail', 'message' => 'Sorry, this e-mail address is already registered.'),
                ),
		'password' => array(
                    array('notEmpty', 'message' => 'Password cannot be empty.'),
		    array('notEmptyHash', 'message' => 'Password cannot be empty.'),
		    array('moreThanFive', 'message' => 'Password must be at least 6 characters long.')
		)
		// TODO: password confirm
	);
	
	// So admin templates can have a little context...for example: "Create Page" ... "Create Blog Post" etc.
	public $display_name = 'User';
	
	public static function __init() {
		$class =  __CLASS__;
		/**
		 * ROLES
		 * Note: You don't need to use Minerva's role based access system.
		 * It's a very lightweight system designed to provide basic coverage.
		 * Your needs may fall within the scope of it and you can feel free to
		 * create new roles and access rules using the Access class. However, you
		 * may not find it meeting your needs. You can create your own access
		 * system and simply ignore the "role" field on the User model and/or
		 * always set it to "administrator" and use a different field.
		 * If you don't want to use Minerva's basic role system, you'll need to
		 * adjust the access rules for each controller (which can be done in
		 * your library's Page/User/Block model).
		*/
		// Replace user roles
		$class::_object()->_user_roles = static::_object()->_user_roles;
		// Fill form with role options
		$class::_object()->_schema['role']['form']['options'] = User::user_roles();
		
		/*
		 * Some special validation rules
		*/
		Validator::add('uniqueEmail', function($value) {
			$user = User::find('first', array('fields' => array('_id'), 'conditions' => array('email' => $value)));
			if(!empty($user)) {
			    return false;
			}
			return true;
		});
		
		Validator::add('notEmptyHash', function($value) {    
			if($value == 'da39a3ee5e6b4b0d3255bfef95601890afd80709') {	
			    return false;
			}
			return true;
		});
		    
		Validator::add('moreThanFive', function($value) {
			if(strlen($value) < 5) {	
			    return false;
			}
			return true;
		});
		
		parent::__init();
	}
	
	/**
	 * Get the user roles.
	 * 
	 * @return Array
	*/
	public function user_roles() {
		$class =  __CLASS__;
		return $class::_object()->_user_roles;
	}

}

/** FILTERS
 * All of the filters for this model need to be placed here, outside the class.
 * Normally, they could also be placed within the __init() method, but because
 * of the model class extension, the filters would run twice.
 *
 * That means in our case, the profile pictures would save twice.
 * We can also put the filter to save the profile picture within the User model
 * of the family_spoon plugin.
 * 
*/
User::applyFilter('save', function($self, $params, $chain) {
    // TODO: below
    return $chain->next($self, $params, $chain);
    
	
	// Do this except for those with a facebook uid, the FB PHP SDK takes care of that
	if(!isset($params['data']['facebook_uid'])) {
	
		/*if(!empty($params['data']['profile_pic'])) {
			$asset = Asset::create();
			// Technically the 'model' field is not required because ids are universally unique, but it's easier for to code if we know which model
			$asset->save(array('file' => $params['data']['profile_pic'], 'model' => 'User', 'parent_id' => $params['data']['_id']));
			$asset_data = $asset->data();
			$params['data']['profile_pics'][] = $asset_data['_id'];
		}*/
		
		// Set created, modified, and pretty url (slug)
		if (!$params['entity']->exists()) {
			if(Validator::rule('moreThanFive', $params['data']['password']) === true) {
				$params['data']['password'] = String::hash($params['data']['password']); // will be sha512
			}
			// Unique E-mail validation ONLY upon new record creation
			if(Validator::rule('uniqueEmail', $params['data']['email']) === false) {
				$params['data']['email'] = ''; 
			}
			
		} else {
			// If the fields password and password_confirm both exist, then validate the password field too
			if((isset($params['data']['password'])) && (isset($params['data']['password_confirm']))) {
				if(Validator::rule('moreThanFive', $params['data']['password']) === true) {
					$params['data']['password'] = String::hash($params['data']['password']); // will be sha512
				}
			}
			
			// If the new_email field was passed, the user is requesting to update their e-mail, we will set it and send an email to allow them to confirm, once confirmed it will be changed
			if(isset($params['data']['new_email'])) {
				// Unique E-mail validation
				if((Validator::rule('uniqueEmail', $params['data']['new_email']) === false) || (Validator::isEmail($params['data']['new_email']) === false)) {
					// Invalidate
					$params['data']['new_email'] = '';
				} else {
					$params['data']['approval_code'] = Util::unique_string(array('hash' => 'md5'));
					Email::changeUserEmail(array('first_name' => $params['data']['first_name'], 'last_name' => $params['data']['last_name'], 'to' => $params['data']['new_email'], 'approval_code' => $params['data']['approval_code']));
				}
			}
		}
	
	}
	
	//$data = array($params['entity']->file);
	//Asset::save($data);
	
	return $chain->next($self, $params, $chain);
});
?>