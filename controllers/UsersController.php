<?php
namespace minerva\controllers;
use minerva\models\User;
use li3_flash_message\extensions\storage\FlashMessage;
use li3_access\security\Access;
use \lithium\security\Auth;
use \lithium\storage\Session;
use \lithium\util\Set;
use minerva\libraries\util\Util;

class UsersController extends \lithium\action\Controller {

    /*
     * Rules used by Access::check() at the Dispatcher level.
     * The rules set here will be passed the Request object, but since
     * called at the Dispatcher level, document level access control isn't possible.
     * See the $document_access property below... All rules requiring document data
     * should be defined there.
     *
    */
    static $access = array(
	'login' => array(
	    array('rule' => 'allowAll')
	),
	'logout' => array(
	    array('rule' => 'allowAll')
	),
	'confirm' => array(
	    array('rule' => 'allowAll')
	),
	'register' => array(
	    array('rule' => 'allowAll')
	),
	'is_email_in_use' => array(
	    array('rule' => 'allowAll')
	),
	'index' => array(
	    array('rule' => 'allowManagers', 'redirect' => '/users/login')
	),
	'create' => array(
	    array('rule' => 'allowManagers', 'redirect' => '/users/login')
	),
	'update' => array(
	    array('rule' => 'allowManagers', 'redirect' => '/users/login')
	),
	'delete' => array(
	    array('rule' => 'allowManagers', 'redirect' => '/users/login')
	),
	'read' => array(
	    array('rule' => 'allowManagers', 'redirect' => '/users/login')
	)
    );
    
    /*
     * This works the same way as the pages controller.
     * TODO: add for updating user record actions (password, etc) so only managers or the owner can change things.
    */
    static $document_access = array(
    );
    
    
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
        // Default options for pagination, merge with URL parameters
        $defaults = array('page' => 1, 'limit' => 10, 'order' => 'created.desc');
        $params = Set::merge($defaults, $this->request->params);
        if((isset($params['page'])) && ($params['page'] == 0)) {
	    $params['page'] = 1;
	}
        list($limit, $page, $order) = array($params['limit'], $params['page'], $params['order']);
        
	// If there's a page_type passed, add it to the conditions, 'all' will show all pages.
	// TODO: OBVIOUSLY add an index to "user_type" field (also url for other actions' needs, not this one)
	if((isset($this->request->params['user_type'])) && (strtolower($this->request->params['user_type']) != 'all')) {
	    $conditions = array('user_type' => $this->request->params['user_type']);
	} else {
	    $conditions = array();
	}
	
	// If a search query was provided, search all "searchable" fields (any model schema field that has a "search" key on it)
	// NOTE: the values within this array for "search" include things like "weight" etc. and are not yet fully implemented...But will become more robust and useful.
	// Possible integration with Solr/Lucene, etc.
	$user_type = (isset($this->request->params['user_type'])) ? $this->request->params['user_type']:'all';
	if((isset($this->request->query['q'])) && (!empty($this->request->query['q']))) {
	    $schema = User::schema();
	    // If the "page_type" is set to "all" then we want to get all the page type's schemas, merge them into $schema
	    if($user_type == 'all') {
		foreach(Util::list_types('User', array('exclude_minerva' => true)) as $library) {
		    $model = 'minerva\libraries\\' . $library;
		    $schema += $model::schema();
		}
	    }
	    
	    // If a field has a "search" key defined then it's searchable
	    $searchable_fields = array_filter($schema, function($var){ return(isset($var['search'])); });
	    $search_conditions = array();
	    // For each of those, adjust the conditions to include a regex
	    foreach($searchable_fields as $k => $v) {
		// TODO: possibly factor in the weighting later. also maybe note the "type" to ensure our regex is going to work or if it has to be adjusted (string data types, etc.)
		//var_dump($k);
		$search_regex = new \MongoRegex('/' . $this->request->query['q'] . '/i');
		$conditions['$or'][] = array($k => $search_regex);
	    }
	    
	}
	
	// Get the documents and the total
	$documents = array();
	if((int)$params['limit'] > 0) {
	    $documents = User::find('all', array(
		'request_params' => $this->request->params,
		'conditions' => $conditions,
		'limit' => (int)$params['limit'],
		'offset' => ($params['page'] - 1) * $limit,
		'order' => Util::format_dot_order($params['order'])
	    ));
	}
	// Get some handy numbers
	$total = User::find('count', array(
	    'conditions' => $conditions
	));
	$page_number = $params['page'];
	$total_pages = ((int)$params['limit'] > 0) ? ceil($total / $params['limit']):0;
	
	// Set data for the view template
	$this->set(compact('documents', 'limit', 'page_number', 'total_pages', 'total'));
    }
	
    public function read($id=null) {
	if((isset($this->request->params['id'])) && (empty($id))) {
	    $id = $this->request->params['id'];
	}
	
	$record = User::find('first', array('conditions' => array('_id' => $id), 'request_params' => $this->request->params));
	
	if(!$record) {
	    FlashMessage::set('The user could not be found.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
	    $this->redirect('/users');
	}
	
	$this->set(compact('record'));
    }
    
    /**
     * Backend administrative action.
     * Should never be hit from the front-end.
     * 
    */
    public function create() {
	// Get the name for the user, so if another user type library uses the "admin" (core) templates for this action, it will be shown
	$display_name = User::display_name();
	
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
            $document = User::create();
	    $now = date('Y-m-d h:i:s');
	    $this->request->data['created'] = $now;
	    $this->request->data['modified'] = $now;
	    
	    // Make sure there's a user type (default is "user" a normal user that might have access to the backend based on their role)
	    if((!isset($this->request->data['user_type'])) || (empty($this->request->data['user_type']))) {
		//$this->request->data['user_type'] = 'user';
		$this->request->data['user_type'] = null;
	    }
	    
            if($document->save($this->request->data, array('validate' => $rules))) {
                FlashMessage::set('The user has been created successfully.', array('options' => array('type' => 'success', 'pnotify_title' => 'Success', 'pnotify_opacity' => .8)));
		$this->redirect(array('controller' => 'users', 'action' => 'index'));
            } else {
		FlashMessage::set('The user could not be saved, please try again.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
	    }
        }
        
        if(empty($document)) {
            // Create an empty user object
            $document = User::create();
        }
        
        $this->set(compact('document', 'fields', 'display_name'));
    }
	
    /**
     * Update a user.
     *
    */
    public function update() {
	if(isset($this->request->params['page_type'])) {
	    $conditions = array('page_type' => $this->request->params['page_type']);
	} else {
	    $conditions = array();
	}
    
	if(isset($this->request->params['url'])) {
	    $conditions += array('url' => $this->request->params['url']);
	}
	
	if(isset($this->request->params['id'])) {
	    $conditions += array('_id' => $this->request->params['id']);
	}
	
	// Get the name for the page, so if another page type library uses the "admin" (core) templates for this action, it will be shown
	$display_name = User::display_name();
    
	// Get the fields so the view template can build the form
	$fields = User::schema();                
	// Don't need to have these fields in the form
	unset($fields[User::key()]);
	// If a page type was passed in the params, we'll need it to save to the page document.
	$fields['user_type']['form']['value'] = (isset($this->request->params['user_type'])) ? $this->request->params['user_type']:null;
	
	// Get the user document
	$document = User::find('first', array('conditions' => $conditions));
	
	$document_data = $document->data();
	
	// Update the record
	if ($this->request->data) {
	    // Set some data
	    unset($this->request->data['password']);
	    if((isset($this->request->data['new_password'])) && (!empty($this->request->data['new_password']))) {
		$this->request->data['password'] = sha1($this->request->data['new_password']);
		unset($this->request->data['new_password']);
	    }
	    
	    $now = date('Y-m-d h:i:s');
	    $this->request->data['modified'] = $now;
	    
	    // Call save from the main app's User model
	    if($document->save($this->request->data)) {
		$this->redirect(array('controller' => 'users', 'action' => 'index'));
	    }                        
	}
	    
	    $this->set(compact('document', 'fields', 'display_name'));
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
    public function delete() {
	if(!isset($this->request->params['id'])) {
	    $this->redirect(array('controller' => 'users', 'action' => 'index'));
	}
	
	$document = User::find('first', array('conditions' => array('_id' => $this->request->params['id'])));
	
	if($document->delete()) {
	    FlashMessage::set('The user has been deleted.', array('options' => array('type' => 'success', 'pnotify_title' => 'Success', 'pnotify_opacity' => .8)));
	    $this->redirect(array('controller' => 'users', 'action' => 'index'));
	} else {
	    FlashMessage::set('The user could not be deleted, please try again.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
	    $this->redirect(array('controller' => 'users', 'action' => 'index'));
	}		
    }
    
}
?>