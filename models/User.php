<?php

/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace minerva\models;

use lithium\storage\Cache;
use lithium\util\Validator;
use lithium\util\Inflector;
use lithium\security\Auth;
use lithium\security\Password;
use minerva\util\Email;
use minerva\extensions\util\Util;
use li3_facebook\extensions\FacebookProxy;
use \Exception;

// use app\models\Asset;

class User extends \minerva\models\MinervaModel {

	// User model also can be extended...Plugins can maybe add features like "Profile Pages" or "Twitter Feeds" etc.
	// I get appended to with the plugin's User model.
	protected $_schema = array(
		'facebook_uid' => array(
			'type' => 'string',
			'form' => array('label' => 'Facebook User Id')
		),
		'first_name' => array('type' => 'string', 'form' => array('label' => 'First Name')),
		'last_name' => array('type' => 'string', 'form' => array('label' => 'Last Name')),
		'email' => array(
			'type' => 'string',
			'form' => array('label' => 'E-mail', 'autocomplete' => 'off')
		),
		'new_email' => array(
			'type' => 'string',
			'form' => array('label' => false, 'type' => 'hidden')
		),
		//'username' => array(
		//	'type' => 'string',
		//	'form' => array('label' => 'Username')
		//), // going to use e-mail for username
		'password' => array(
			'type' => 'string',
			'form' => array('label' => 'Password', 'type' => 'password', 'autocomplete' => 'off')
		),
		'role' => array(
			'type' => 'string',
			'form' => array('type' => 'select', 'label' => 'User Role', 'position' => 'options')
		),
		'active' => array(
			'type' => 'boolean',
			'form' => array('type' => 'checkbox', 'label' => 'Active', 'position' => 'options')
		),
		'approval_code' => array(
			'type' => 'string',
			'form' => array('type' => 'hidden', 'label' => false)
		),
		'last_login_ip' => array(
			'type' => 'string',
			'form' => array('type' => 'hidden', 'label' => false)
		),
		'last_login_time' => array(
			'type' => 'date',
			'form' => array('type' => 'hidden', 'label' => false)
		)
			//'profile_pics' => array('type' => 'string') // todo
	);
	// For the prety URL, use the first and last name together...If that ends up being empty,
	// it'll default to "user" and then "user-1" etc. for dupes
	public $url_field = array('first_name', 'last_name');
	public $search_schema = array(
		'email' => array(
			'weight' => 1
		)
	);

	/**
	 * There are only a few basic types of users in Minerva.
	 * Additional permissions can be added in the form of a 3rd party plugin.
	 * Each plugin can also handle permissions in its own way of course too.
	 *
	 * The basic types of roles are administrators, content editors, and normal
	 * registered users.
	 *
	 * Administrators are essentially "super admins" and have the ability
	 * to add and edit everything in the system including other users.
	 *
	 * Content editors do not have the ability to manage users, they merely have the
	 * ability to create pages, menus, and blocks. Or in other words, "content."
	 *
	 * Registered users are a little more elevated in status than anonymous visitors,
	 * but they don't have the abilty to see any of the administrative backend.
	 * The only data these users write to the database is for their own session.
	 * This could change based on the plugin of course.
	 * For example, a blog plugin may allow these users to make comments.
	 *
	 * @var array $user_roles
	 */
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
	// So admin templates can have a little context...for example:
	//    "Create Page" ... "Create Blog Post" etc.
	public $display_name = 'User';
	// We do not redirect with this model, the controller will a cookie if possible
	// But, plugins can set this property so that users will always be redirected to a speicfic URL
	public $login_redirect = false;
	public $action_redirects = array(
		'logout' => '/',
		'register' => array('library' => 'minerva', 'controller' => 'users', 'action' => 'login')
	);
	// Access rules
	public $access = array(
		'login' => array(
			'action' => array(array('rule' => 'allowAll'))
		),
		'confirm' => array(
			'action' => array(array('rule' => 'allowAll'))
		),
		'register' => array(
			'action' => array(array('rule' => 'allowAll'))
		),
		'is_email_in_use' => array(
			'action' => array(array('rule' => 'allowAll'))
		),
		'index' => array(
			'action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'admin_action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'admin' => 'admin',
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'document' => array() // not used
		),
		'create' => array(
			'action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'admin_action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'admin' => 'admin',
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'document' => array() // not used
		),
		'update' => array(
			'action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'admin_action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'admin' => 'admin',
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'document' => array()
		),
		'delete' => array(
			'action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'library' =>
						'minerva',
						'controller' =>
						'users',
						'action' => 'login'
					)
				)
			),
			'admin_action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'admin' => 'admin',
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'document' => array()
		),
		'read' => array(
			'action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'admin_action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'admin' => 'admin',
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'document' => array(
			)
		),
		'preview' => array(
			'action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'admin_action' => array(
				array(
					'rule' => 'allowManagers',
					'redirect' => array(
						'admin' => 'admin',
						'library' => 'minerva',
						'controller' => 'users',
						'action' => 'login'
					)
				)
			),
			'document' => array()
		)
	);

	public static function __init() {
		$class = __CLASS__;
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
		// Fill form with role options ($list arg)
		$class::_object()->_schema['role']['form']['list'] = User::user_roles();

		// Replace user login redirect property (not set on this model)
		// @see UsersController::login()
		$class::_object()->login_redirect = static::_object()->login_redirect;

		/*
		 * Some special validation rules
		 */
		Validator::add('uniqueEmail', function($value) {
			$current_user = Auth::check('minerva_user');
			if (!empty($current_user)) {
				$user = User::find('first', array(
					'fields' => array('_id'),
					'conditions' => array(
						'email' => $value,
						'_id' => array('$ne' => $current_user['_id'])
					)
				));
			} else {
				$user = User::find('first', array(
					'fields' => array('_id'),
					'conditions' => array('email' => $value)
				));
			}
			if (!empty($user)) {
				return false;
			}
			return true;
		});

		Validator::add('notEmptyHash', function($value) {
			if ($value == Password::hash('')) {
				return false;
			}
			return true;
		});

		Validator::add('moreThanFive', function($value) {
			if (strlen($value) < 5) {
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
	public static function user_roles() {
		$class = __CLASS__;
		return $class::_object()->_user_roles;
	}

	/**
	 * Returns the $login_redirect property.
	 *
	 * Not only does this return a property, but this entire method
	 * could easily be overwritten in the extended model class.
	 * From there, all sorts of stuff could happen in order to return
	 * a string or array at the end for a redirect.
	 *
	 * @return mixed A string or an valid URL array
	 */
	public static function get_login_redirect() {
		$class = __CLASS__;
		return $class::_object()->login_redirect;
	}

	/**
	 * Gets the name for a user given their id.
	 * This id can be either a MongoId for getting a user's name from the local database
	 * or it can be a Facebook id or any other id given the $service is defined.
	 * External services like Facebook will have their values stored in APC for no more than
	 * 24hrs, or whatever the services' terms of use allow.
	 *
	 * @param $id String The user id, either a MongoId or a Facebook id ... or ...
	 * @param mixed $service The service to use, either false/'local' for
	 *              Minerva's MongoDB or 'facebook' for Facebook ... or ...
	 * @return mixed  The user's name on success, false on failure
	 */
	public static function get_name($id = false, $service = false) {
		if (!$id) {
			return false;
		}

		$name = false;
		$user_doc = static::find('first', array('conditions' => array('_id' => $id)));
		if (isset($user_doc->first_name) && !empty($user_doc->first_name)) {
			$name = $user_doc->first_name;
		}
		if (isset($user_doc->last_name) && !empty($user_doc->last_name)) {
			$name .= ' ' . $user_doc->last_name;
		}

		// if name is still empty but it's a facebook user, get their name from fb.
		// - prefer the locally stored user name (facebook users will be able to define a name
		//   under their account settings, but facebook user names are never stored directly
		//   from facebook)
		if ((empty($name)) && (isset($user_doc->facebook_uid))) {
			$name = User::get_name_from_facebook($user_doc->facebook_uid);
		}

		return $name;
	}

	/**
	 * Get a user's name from Facebook using their FB id.
	 * Facebook user's names are cached for 24hrs (for performance reasons)
	 * as per Facebook's terms.
	 *
	 * @param $id String The user's Facebook id
	 * @return Mixed The user's name on success, false on failure
	 */
	public static function get_name_from_facebook($id = false) {
		if (!$id) {
			return false;
		}

		// Get the cached FB user name. NOTE: Cache expires every 24hrs as per FB's terms.
		// However, it's just not feasible to hit FB's servers for every user name because we
		// could have many requests per page and it just takes too long.
		$name = Cache::read('default', $id);
		if (empty($name)) {
			$name = '';
			$fb_user = false;
			$fb_user_data = file_get_contents('https://graph.facebook.com/' . $id);
			if (!empty($fb_user_data)) {
				$fb_user = json_decode($fb_user_data);
			}
			if (is_object($fb_user)) {
				$name = (isset($fb_user->first_name)) ? $fb_user->first_name : '';
				$name .= (!empty($name)) ? ' ' : '';
				$name .= (isset($fb_user->last_name)) ? $fb_user->last_name : '';
				// if the name is STILL empty, try the name property. shouldn't be though.
				if (empty($name)) {
					$name = (isset($fb_user->name)) ? $fb_user->name : '';
				}
			}
			Cache::write('default', $id, $name, '+1 day');
		}

		return (!empty($name)) ? $name : false;
	}

	/**
	 * Handles a Facebook user.
	 * If the user does not yet exist in the local database, they will be added.
	 * However, Facebook doesn't allow us to store any personal information about the user.
	 * So we're just going to store their Facebook uid and also a created, modified date, etc.
	 * Then for existing users, we'll update the last login time and IP.
	 *
	 * @param $facebook_uid String This will be the user's uid passed from the Facebook API
	 */
	public function handle_facebook_user($facebook_uid = null) {
		if (empty($facebook_uid)) {
			return false;
		}

		$me = null;
		try {
			$me = FacebookProxy::api('/me');
		} catch (Exception $e) {
			error_log($e);
		}

		if (empty($me)) {
			return false;
		}

		$now = date('Y-m-d h:i:s');

		// If logged in via Facebook Connect, see if the user exists in the local DB, if not, save it.
		$user = User::find('first', array('conditions' => array('facebook_uid' => $me['id'])));
		$user_data = false;

		if (!$user) {
			// Save the new user
			$user_document = User::create();
			$user_data = array(
				'facebook_uid' => $me['id'],
				'confirmed' => true,
				'active' => true,
				'url' => Util::uniqueUrl(array(
					'url' => 'fb-user',
					'model' => 'minerva\models\User'
				)),
				'created' => $now,
				'modified' => $now,
				'last_login_time' => $now,
				'last_login_ip' => $_SERVER['REMOTE_ADDR'],
				'email' => null,
				'password' => null,
				'role' => 'registered_user',
				'profile_pics' => array(
					'primary' => true,
					'url' => 'http://graph.facebook.com/' . $facebook_uid . '/picture?type=square'
				)
			);
			$user_document->save($user_data, array('validate' => false));
		} else {
			$user_data = $user->data();
		}

		return $user_data;
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
	if (!isset($params['data']['facebook_uid'])) {

		/* if(!empty($params['data']['profile_pic'])) {
		  $asset = Asset::create();
		  // Technically the 'model' field is not required because ids are universally unique,but it's easier for to code if we know which model
		  $asset->save(array('file' => $params['data']['profile_pic'], 'model' => 'User', 'parent_id' => $params['data']['_id']));
		  $asset_data = $asset->data();
		  $params['data']['profile_pics'][] = $asset_data['_id'];
		  } */

		// Set created, modified, and pretty url (slug)
		if (!$params['entity']->exists()) {
			if (Validator::rule('moreThanFive', $params['data']['password']) === true) {
				// will be sha512
				$params['data']['password'] = Password::hash($params['data']['password']);
			}
			// Unique E-mail validation ONLY upon new record creation
			if (Validator::rule('uniqueEmail', $params['data']['email']) === false) {
				$params['data']['email'] = '';
			}
		} else {
			// If the fields password and password_confirm both exist, then validate the password field too
			if ((isset($params['data']['password'])) && (isset($params['data']['password_confirm']))) {
				if (Validator::rule('moreThanFive', $params['data']['password']) === true) {
					$params['data']['password'] = Password::hash($params['data']['password']); // will be sha512
				}
			}

			// If the new_email field was passed, the user is requesting to update their e-mail, we will set it and send an email to allow them to confirm, once confirmed it will be changed
			if (isset($params['data']['new_email'])) {
				// Unique E-mail validation
				if ((Validator::rule('uniqueEmail', $params['data']['new_email']) === false) || (Validator::isEmail($params['data']['new_email']) === false)) {
					// Invalidate
					$params['data']['new_email'] = '';
				} else {
					$params['data']['approval_code'] = Util::uniqueString(array('hash' => 'md5'));
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