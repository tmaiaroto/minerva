<?php
namespace minerva\controllers;
use minerva\models\User;
use li3_flash_message\extensions\storage\FlashMessage;
use li3_access\security\Access;
use \lithium\security\Auth;
use \lithium\storage\Session;
use \lithium\util\Set;
use minerva\libraries\util\Util;

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
			FlashMessage::set('The user could not be found.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
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
     * Register is basically the same as create. It just lets us use a separeate "register" template in addition to a "create" one.
     * However, it's a front-end action and as such it can never allow a user role to be set to anything more than a regular user.
     * 
    */
    public function register() {
        // Get the fields so the view template can iterate through them and build the form
        $fields = User::schema();
        // Don't need to have these fields in the form
        unset($fields[User::key()]);
        
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
            // TODO: password confirm
        );
        
        // Save
        if ($this->request->data) {
            $user = User::create();
	    $this->request->data['role'] = 'registered_user'; // set basic user, always hard coded and set
	    
	    // IF this is the first user ever created, then they will be an administrator
	    // TODO: make a wizard that will set this so there's no chance of some user registering and becoming an admin
	    $users = User::find('count');
	    if(empty($users)) {
		$this->request->data['role'] = 'administrator';
		$this->request->data['active'] = true;
	    }
	    
	    // Make sure there's a user type (default is "user" a normal user that might have access to the backend based on their role)
	    if((!isset($this->request->data['user_type'])) || (empty($this->request->data['user_type']))) {
		//$this->request->data['user_type'] = 'user';
		$this->request->data['user_type'] = null;
	    }
	    
            if($user->save($this->request->data, array('validate' => $rules))) {
                //$this->redirect(array('controller' => 'users', 'action' => 'index'));
                $this->redirect('/');
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
            FlashMessage::set('User successfully created.', array('type' => 'success'));
            $this->redirect(array('controller' => 'users', 'action' => 'login'));
        } else {
            FlashMessage::set('Could not create the user record, please try again.', array('type' => 'error'));
            $this->redirect('/'); // probably should redirect to a page where you can enter the code manually or a retry or something. should notify the user to try again.
        }
    }

    
    public function login() {
        $user = Auth::check('minerva_user', $this->request);
        if ($user) {
            // TODO: Put in a $redirectURL property so it can be controlled and option for the following redirect true/false for taking a user back to the page they first requested.
            // Also TODO: Make flash messages set in some sort of config, possibly even the model properties too
            $url = '/';
            if (Session::check('beforeAuthURL')) {
                $url = Session::read('beforeAuthURL');
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
            
            FlashMessage::set('You\'ve successfully logged in.');
            $this->redirect($url);
            //$this->redirect(array('controller' => 'pages', 'action' => 'index'));
        } else {
            if($this->request->data) {
                FlashMessage::set('You entered an incorrect username and/or password.', array('type' => 'error'));
            }
        }
        $data = $this->request->data;
        return compact('data');
    }

    public function logout() {
        Auth::clear('minerva_user');
		FlashMessage::set('You\'ve successfully logged out.');
        $this->redirect(array('action' => 'login'));
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