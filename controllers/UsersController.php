<?php
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

class UsersController extends \minerva\controllers\MinervaController {
	
	/*
     * Rules used by Access::check() for access and document access per action.
     * 
     * By default we're restricting everything to managers.
     * This leaves the core PagesController to administrative purposes.
     * The "public" library will hold basic pages for anonymous visitors.
     * 
    */
    static $access = array(
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
                array('rule' => 'allowManagers', 'redirect' => '/users/login')
            ),
            'admin' => array(), // maybe??
            'document' => array() // not used
        ),
        'create' => array(
            'action' => array(
                array('rule' => 'allowManagers', 'redirect' => '/users/login')
            ),
            'document' => array() // not used
        ),
        'update' => array(
            'action' => array(
                array('rule' => 'allowManagers', 'redirect' => '/users/login')
            ),
            'document' => array()
        ),
        'delete' => array(
            'action' => array(
                array('rule' => 'allowManagers', 'redirect' => '/users/login')
            ),
            'document' => array()
        ),
        'read' => array(
            'action' => array(
                array('rule' => 'allowManagers', 'redirect' => '/users/login')
            ),
            'document' => array(
               // array('rule' => 'publishStatus', 'message' => 'You are not allowed to see unpublished content.', 'redirect' => '/')
            )
        ),
        'preview' => array(
            'action' => array(
                array('rule' => 'allowManagers', 'redirect' => '/users/login')
            ),
            'document' => array()
        )
    );
    
    public function index() {
        // all index() methods are the same so they are done in MinervaController, but we do need a little context as to where it's called from
        $this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::index();
    }
	
    public function read($id=null) {
		if((isset($this->request->params['id'])) && (empty($id))) {
			$id = $this->request->params['id'];
		}
		
		$document = $this->getDocument(array(
            'action' => __METHOD__,
            'request' => $this->request,
            'find_type' => 'first',
            'conditions' => array('_id' => $id)
        ));
		 
		if(!$document) {
			FlashMessage::write('The user could not be found.', array(), 'minerva_admin');
			$this->redirect('/users');
		}
		
		$this->set(compact('document'));
    }
    
    /**
     * Backend administrative action.
     * Should never be hit from the front-end.
     * 
    */
    public function create() {
		$this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::create();
    }
	
    /**
     * Update a user.
     *
    */
    public function update() {
		$this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::update();
	}
	
    /** 
     *  Delete a user record.
     *  Plugins can apply filters within their User model class in order to run filters for the delete.
     *  Useful for "clean up" tasks.
    */
    public function delete() {
		$this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::delete();
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
            $url_field = $ModelClass::url_field();
            $url_separator = $ModelClass::url_separator();
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
            $this->request->data['url'] = Util::unique_url(array(
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
				$this->request->data['password'] = String::hash($this->request->data['password']);
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
		if (!Session::check('triedAuthRedirect')) {
			Session::write('triedAuthRedirect', false);
		}
        if ($user) {
			$url = MINERVA_BASE_URL;
            if (Session::check('beforeAuthURL')) {
				$url = Session::read('beforeAuthURL');
				
				// 'triedAuthRedirect' so we don't end up in a redirect loop
				$triedAuthRedirect = Session::read('triedAuthRedirect');
				if($triedAuthRedirect === true || $triedAuthRedirect == '1') {
					$url = MINERVA_BASE_URL;
					Session::delete('triedAuthRedirect');
				} else {
					Session::write('triedAuthRedirect', true);
				}
				
                Session::delete('beforeAuthURL');
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
		
		// Also get the Facebook login URL if present
		$fb_login_url = false;
		if (Session::check('fb_login_url')) {
            $fb_login_url = Session::read('fb_login_url');
		}
		
        return compact('data', 'fb_login_url');
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