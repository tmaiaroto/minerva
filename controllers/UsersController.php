<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
*/
namespace minerva\controllers;

use \lithium\security\Auth;
use \lithium\storage\Session;
use \lithium\util\Set;
use \lithium\util\String;
use \lithium\util\Inflector;
use minerva\models\User;
use minerva\extensions\util\Util;
use li3_flash_message\extensions\storage\FlashMessage;
use li3_access\security\Access;
use \lithium\security\Password;

class UsersController extends \minerva\controllers\MinervaController {
	
    public function index($document_type=null) {
        // all index() methods are the same so they are done in MinervaController, but we do need a little context as to where it's called from
        $this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::index($document_type);
    }
	
    public function read($url=null) {
		if((isset($this->request->params['url'])) && (empty($url))) {
			$url = $this->request->params['url'];
		}
		
		$document = $this->getDocument(array(
            'action' => __METHOD__,
            'request' => $this->request,
            'find_type' => 'first',
            'conditions' => array('url' => $url)
        ));
		 
		if(!$document) {
			FlashMessage::write('The user could not be found.', array(), 'minerva_admin');
			$this->redirect(array('library' => 'minerva', 'controller' => 'users', 'action' => 'index'));
            return false;
		}
		
		$this->set(compact('document'));
    }
    
    /**
     * Backend administrative action.
     * Should never be hit from the front-end.
     * 
    */
    public function create($document_type=null) {
		$this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::create($document_type);
    }
	
    /**
     * Update a user.
     *
    */
    public function update($url=null) {
		$this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::update($url);
	}
	
    /** 
     *  Delete a user record.
     *  Plugins can apply filters within their User model class in order to run filters for the delete.
     *  Useful for "clean up" tasks.
    */
    public function delete($url=null) {
		$this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::delete($url);
    }
	
	/**
     * A simple method to check if the e-mail is already in use or not.
     *
     * @param string $email The e-mail address to check for.
     * @return boolean True or false
    */
    public function is_email_in_use($email=null) {
        if(!$email) {
            echo 'false';
            return false;
        }
        
        $user = User::find('first', array('fields' => array('_id'), 'conditions' => array('email' => $email)));
        
        if(!empty($user)) {
            echo 'true';
            return true;
        }
        
        echo 'false';
        return false;
    }
    
    /**
     * Registers as a basic Minerva user.
     * Other libraries will need to replace this method and route registration depending on the desired
     * functionality for the application.
    */
    public function register() {
		// first, get all the data we need. this will set $document_type, $type, $modelClass, and $display_name
        extract($this->minerva_config);
		// get the redirects
        $action_redirects = $this->getRedirects();
		
        // Get the fields so the view template can iterate through them and build the form
        $fields = $ModelClass::schema();
        // Don't need to have these fields in the form
        unset($fields['_id']);
        
        $rules = array(
            'email' => array(
                array('notEmpty', 'message' => 'E-mail cannot be empty.'),
                array('email', 'message' => 'E-mail is not valid.'),
                array('uniqueEmail', 'message' => 'Sorry, this e-mail address is already registered.'),
            ),
            'password' => array(
                array('notEmpty', 'message' => 'Password cannot be empty.'),
                array('notEmptyHash', 'message' => 'Password cannot be empty.'),
                array('moreThanFive', 'message' => 'Password must be at least 6 characters long.')
            )
        );
        
        // Save
        if ($this->request->data) {
            $user = $ModelClass::create();
			
			$now = date('Y-m-d h:i:s');
            $this->request->data['created'] = $now;
            $this->request->data['modified'] = $now;
			
			// Generate the URL
            $url = '';
            $url_field = $ModelClass::urlField();
            $url_separator = $ModelClass::urlSeparator();
            if($url_field != '_id' && !empty($url_field)) {
                if(is_array($url_field)) {
                    foreach($url_field as $field) {
                        if(isset($this->request->data[$field]) && $field != '_id') {
                            $url .= $this->request->data[$field] . ' ';
                        }
                    }
                    $url = Inflector::slug(trim($url), $url_separator);
                } else {
                    $url = Inflector::slug($this->request->data[$url_field], $url_separator);
                }
            }
            
            // Last check for the URL...if it's empty for some reason set it to "user"
            if(empty($url)) {
                $url = 'user';
            }
            
            // Then get a unique URL from the desired URL (numbers will be appended if URL is duplicate) this also ensures the URLs are lowercase
            $this->request->data['url'] = Util::uniqueUrl(array(
                'url' => $url,
                'model' => $ModelClass
            ));
			
			// Set the user's role...
			$this->request->data['role'] = 'registered_user'; // set basic user, always hard coded and set
			
			// IF this is the first user ever created, then they will be an administrator
			// TODO: make a wizard that will set this so there's no chance of some user registering and becoming an admin
			$users = User::find('count');
			if(empty($users)) {
				$this->request->data['role'] = 'administrator';
				$this->request->data['active'] = true;
			}
			
			// Set the password, it has to be hashed
			if((isset($this->request->data['password'])) && (!empty($this->request->data['password']))) {
                $this->request->data['password'] = Password::hash($this->request->data['password']);
			}
		
            if($user->save($this->request->data, array('validate' => $rules))) {
				FlashMessage::write('User registration successful.', array(), 'minerva_admin');
               $this->redirect($action_redirects['register']);
            } else {
				$this->request->data['password'] = '';
			}
        }
        
        if(empty($user)) {
            // Create an empty user object
            $user = User::create();
        }
        
        $this->set(compact('user', 'fields'));
    }
	
    /**
     * Confirm the user account.
     *
     * @param string $approval_code The approval code.
     * @return
    */
    public function confirm($approval_code=null) {
        if(empty($approval_code)) {
            $this->redirect('/');
        }
        //$record = User::find('first', array('conditions' => array('approval_code' => $approval_code)));
        $record = $this->getDocument('first', array(
			'action' => __METHOD__,
			'request' => $this->request,
			'find_type' => 'first',
			'conditions' => array('approval_code' => $approval_code)
			)
		);
		
        // Save the confirmed field as true and set the approval_code to empty string just in case, I know they are supposed to be unique strings but maybe that dart thrown from outerspace will hit my house.
        $data = array('confirmed' => true, 'approval_code' => '');
        
        // If this was an e-mail change, take the new e-mail address and use it by setting the email field equal to it and then get rid of the new_email field value
        $record_data = $record->data();
        if($record_data['new_email']) {
            $data['email'] = $record_data['new_email'];
            $data['new_email'] = '';
        }
        
        if($record->save($data, array('validate' => false))) {
            FlashMessage::write('User successfully created.', array(), 'minerva_admin');
            $this->redirect(array('controller' => 'users', 'action' => 'login'));
        } else {
            FlashMessage::write('Could not create the user record, please try again.', array(), 'minerva_admin');
			$this->redirect(MINERVA_BASE_URL); // probably should redirect to a page where you can enter the code manually or a retry or something. should notify the user to try again.
        }
    }

    
    public function login() {
        $user = Auth::check('minerva_user', $this->request);
		// 'triedAuthRedirect' so we don't end up in a redirect loop
		if (!Session::check('triedAuthRedirect', array('name' => 'minerva_cookie'))) {
			Session::write('triedAuthRedirect', 'false', array('name' => 'minerva_cookie', 'expires' => '+1 hour'));
		}
        
        // Facebook returns a session querystring... We don't want to show this to the user.
        // Just redirect back so it ditches the querystring. If the user is logged in, then
        // it will redirect like expected using the $url variable that has been set below.
        // Not sure why we need to do this, I'd figured $user would be set...And I think there's
        // a session just fine if there was no redirect and the user navigated away...
        // But for some reason it doesn't see $user and get to the redirect() part...
        if(isset($_GET['session'])) {
            $this->redirect(array('library' => 'minerva', 'controller' => 'users', 'action' => 'login'));
        }
        
        if ($user) {
            // Users will be redirected after logging in, but where to?
            // First, set the base Minerva URL (typically /minerva)
			$url = MINERVA_BASE_URL;
            
            // Second, look to see if a cookie was set. The could have ended up at the login page
            // because he/she tried to go to a restricted area. That URL was noted in a cookie.
            if (Session::check('beforeAuthURL', array('name' => 'minerva_cookie'))) {
				$url = Session::read('beforeAuthURL', array('name' => 'minerva_cookie'));
				
				// 'triedAuthRedirect' so we don't end up in a redirect loop
				$triedAuthRedirect = Session::read('triedAuthRedirect', array('name' => 'minerva_cookie'));
				if($triedAuthRedirect == 'true') {
					$url = MINERVA_BASE_URL;
					Session::delete('triedAuthRedirect', array('name' => 'minerva_cookie'));
				} else {
					Session::write('triedAuthRedirect', 'true', array('name' => 'minerva_cookie', 'expires' => '+1 hour'));
				}
				
                Session::delete('beforeAuthURL', array('name' => 'minerva_cookie'));
            }
            
            // Last, we can actually have our User model dictate where users ALWAYS get directed
            // upon logging in. Minerva's core User model will not have this set, but extended 
            // models (plugins) can set it.
            // ONLY IF the user has a document_type set though. Otherwise, it's a core user
            // and there's no need to check.
            if(isset($user['document_type']) && !empty($user['document_type'])) {
                // This handy method finds the extended model class
                $UserModel = User::getMinervaModel('User', $user['document_type']);
                
                if(class_exists($UserModel)) {
                    $login_redirect = $UserModel::get_login_redirect();
                    if(is_string($login_redirect) || is_array($login_redirect)) {
                        $url = $login_redirect;
                    }
                }
            
            }
            
            // Save last login IP and time
            //$user_record = User::find('first', array('conditions' => array('_id' => new \MongoId($user['_id']))));
			$user_record = $this->getDocument(array(
				'action' => __METHOD__,
				'request' => $this->request,
				'find_type' => 'first',
				'conditions' => array('_id' => new \MongoId($user['_id']))
			));
			
            if($user_record) {
				$user_record->save(array('last_login_ip' => $_SERVER['REMOTE_ADDR'], 'last_login_time' => date('Y-m-d h:i:s')));
			}
           
			// only set a flash message if this is a login. it could be a redirect from somewhere else that has restricted access
			$flash_message = FlashMessage::read('minerva_admin');
			if(!isset($flash_message['message']) || empty($flash_message['message'])) {
				FlashMessage::write('You\'ve successfully logged in.', array(), 'minerva_admin');
			}
			$this->redirect($url);
        } else {
            if($this->request->data) {
				FlashMessage::write('You entered an incorrect username and/or password.', array(), 'minerva_admin');
            }
        }
        $data = $this->request->data;
		
        return compact('data');
    }

    public function logout() {
		// get the redirects (again, call AFTER $this->getDocument() because the $ModelClass will have changed)
        $action_redirects = $this->getRedirects();
		
        Auth::clear('minerva_user');
        
		FlashMessage::write('You\'ve successfully logged out.', array(), 'minerva_admin');
        $this->redirect($action_redirects['logout']);
    }
	
	/**
     * Change a user password.
     * This is a method that you request via AJAX.
     *
    */
    public function update_password($url=null) {
		// First, get the record
		$record = User::find('first', array('conditions' => array('url' => $url)));
		if(!$record) {
			return array('error' => true, 'response' => 'User record not found.');
		}
		
		$user = Auth::check('minerva_user');
		if(!$user) {
			//$this->redirect('/');
			return array('error' => true, 'response' => 'You must be logged in to change your password.');
			//exit();
		}
		
		$record_data = $record->data();
		if($user['_id'] != $record_data['_id']) {
			//$this->redirect('/');
			return array('error' => true, 'response' => 'You can only change your own password.');
			//exit();
		}
		
		// Update the record
		if ($this->request->data) {
			// Make sure the password matches the confirmation
			if($this->request->data['password'] != $this->request->data['password_confirm']) {
			return array('error' => true, 'response' => 'You must confirm your password change by typing it again in the confirm box.');
			}
			
			// Call save from the main app's User model
			if($record->save($this->request->data)) {
			//$this->redirect(array('controller' => 'users', 'action' => 'manage', $url));
			return array('error' => false, 'response' => 'Password has been updated successfully.');
			} else {
			return array('error' => true, 'response' => 'Failed to update password, try again.');
			}
		} else {
			return array('error' => true, 'response' => 'You must pass the proper data to change your password and you can\'t call this URL directly.');
		}
    }
    
}
?>