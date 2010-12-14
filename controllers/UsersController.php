<?php
namespace minerva\controllers;
use minerva\models\User;
use \lithium\security\Auth;
use \lithium\storage\Session;
use \lithium\util\Set;

use li3_flash_message\extensions\storage\FlashMessage;

class UsersController extends \lithium\action\Controller {

   // static $access = array();

    /*
     * A simple method to check if the e-mail is already in use or not.
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
    
    /*
     * Yes, this is duplicate, but routing it simply isn't enough. If there's validation errors, it redirects
     * back to the /users/create/minerva URL which isn't desireable to see.
    */
    public function register() {
        $this->create('minerva');
    }
	
    /*
     * Confirm the user account. 
    */
    public function confirm($approval_code=null) {
        if(empty($approval_code)) {
            $this->redirect('/');
        }
        $record = User::find('first', array('conditions' => array('approval_code' => $approval_code)));
        
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
            $user_record = User::find('first', array('conditions' => array('_id' => new \MongoId($user['_id']))));
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
    
    public function index() {
        $this->redirect('/');
        
        // Default options for pagination, merge with URL parameters
        $defaults = array('page' => 1, 'limit' => 10, 'order' => array('descending' => 'true'));
        $params = Set::merge($defaults, $this->request->params);
        if((isset($params['page'])) && ($params['page'] == 0)) { $params['page'] = 1; }
        list($limit, $page, $order) = array($params['limit'], $params['page'], $params['order']);
        
        $records = User::find('all', array(
            'limit' => $params['limit'],
            'offset' => ($params['page'] - 1) * $params['limit'], // TODO: "offset" becomes "page" soon or already in some branch...
            //'order' => $params['order']
            'order' => array('email' => 'asc')
        ));	
        $total = User::count();
        
        $this->set(compact('records', 'limit', 'page', 'total'));
    }
	
	public function read($id=null) {
	    if((isset($this->request->params['id'])) && (empty($id))) {
		$id = $this->request->params['id'];
	    }
	    
	    $record = User::find('first', array('conditions' => array('_id' => $id)));
	    
	    if(!$record) {
		FlashMessage::set('The user could not be found.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
		$this->redirect('/');
	    }
	    
            $this->set(compact('record'));
	}
    
    public function create() {
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
	 * Manage a user account. Similar to update(), but for public site users and not admins to use.
	*/
	public function manage($url=null) {	
		// First, get the record
		$record = User::find('first', array('conditions' => array('url' => $url)));
		
		$user = Auth::check('minerva_user');
		if(!$user) {
			// TODO Flash message
			$this->redirect('/');
			exit();
		}
		
		$record_data = $record->data();
		if($user['_id'] != $record_data['_id']) {
			// TODO Flash message
			$this->redirect('/');
			exit();
		}
		
		
		// Next, if the record uses a library, instantiate it's User model (bridge from plugin to core)
		if((isset($record->library)) && ($record->library != 'app') && (!empty($record->library))) {		
			// Just instantiating the library's Page model will essentially "bridge" and extend the main app's User model	
			$class = '\\'.$record->library.'\models\User';
			if(class_exists($class)) {
                            $Library = new $class();
                        }
			// var_dump(User::$fields); // debug
			// var_dump($Library::$fields); // just the extended library's fields			
		}
		
		// Update the record
		if ($this->request->data) {
			unset($this->request->data['password']);
			if((isset($this->request->data['new_password'])) && (!empty($this->request->data['new_password']))) {
				$this->request->data['password'] = sha1($this->request->data['new_password']);
				unset($this->request->data['new_password']);
			}
                        // Call save from the main app's User model
                        if($record->save($this->request->data)) {
                            $this->redirect('/');
                        }                        
		}
		
                $this->set(compact('record'));
	}
	
	/**
	 * Update a user.
	 *
	*/
	public function update($url=null) {	
		// First, get the record
		$record = User::find('first', array('conditions' => array('url' => $url)));
		
		$user = Auth::check('minerva_user');
		if(!$user) {
			// TODO Flash message
			$this->redirect('/');
			exit();
		}
		
		$record_data = $record->data();
		if($user['admin'] != true) {
			// TODO Flash message
			$this->redirect('/');
			exit();
		}
		
		// Update the record
		if ($this->request->data) {
			// Set some data
			unset($this->request->data['password']);
			if((isset($this->request->data['new_password'])) && (!empty($this->request->data['new_password']))) {
				$this->request->data['password'] = sha1($this->request->data['new_password']);
				unset($this->request->data['new_password']);
			}
			// Checkboxes
			var_dump($this->request->data);exit();
			
                        // Call save from the main app's User model
                        if($record->save($this->request->data)) {
                            $this->redirect(array('controller' => 'users', 'action' => 'index'));
                        }                        
		}
		
                $this->set(compact('record'));
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
	
	/** 
	 *  Delete a user record.
	 *  Plugins can apply filters within their User model class in order to run filters for the delete.
	 *  Useful for "clean up" tasks.
	*/
	public function delete($url=null) {
		if(!$url) {
			$this->redirect(array('controller' => 'users', 'action' => 'index'));
		}
               
                // TODO: should messages like this be done with a filter on delete??
		// Delete the record TODO: put in some kinda flash messages (like cake has) to notify the user things deleted or didn't
		// http://rad-dev.org/li3_flash_message
		if($record->delete()) {
			$this->redirect(array('controller' => 'users', 'action' => 'index'));
		} else {
			$this->redirect(array('controller' => 'users', 'action' => 'index'));
		}		
	}
	
}
?>