<?php
namespace minerva\controllers;
use minerva\models\User;
use \lithium\security\Auth;
use \lithium\util\Set;

class UsersController extends \lithium\action\Controller {

	public function login() {
		$user = Auth::check('user', $this->request);
		var_dump($user);
		if ($user) {
			/*if (Session::check('originalURL')) {
				$url = Session::read('originalURL');
				Session::delete('originalURL');
				$this->redirect($url);				
			}*/
			$this->redirect(array('controller' => 'pages', 'action' => 'index'));
			//$this->redirect(array('action' => 'logout'));
		}
		$data = $this->request->data;
		return compact('data');
	}

	public function logout() {
		Auth::clear('user');
		$this->redirect(array('action' => 'login'));
	}
	
	public function index($library=null) {
		// If we are using a library, instantiate it's User model (bridge from plugin to core)
		if((isset($library)) && ($library != 'minerva') && (!empty($library))) {		
			$class = '\minerva\libraries\\'.$library.'\models\User'; 	  		
			if(class_exists($class)) {
                            $Library = new $class();
                        }
		}
		
		// Default options for pagination, merge with URL parameters
		$defaults = array('page' => 1, 'limit' => 10, 'order' => array('descending' => 'true'));
		$params = Set::merge($defaults, $this->request->params);
		if((isset($params['page'])) && ($params['page'] == 0)) { $params['page'] = 1; }
		list($limit, $page, $order) = array($params['limit'], $params['page'], $params['order']);
		
		$records = User::find('all', array(
			'limit' => $params['limit'],
			'offset' => ($params['page'] - 1) * $params['limit'], // TODO: "offset" becomes "page" soon or already in some branch...
			//'order' => $params['order']
			'order' => array('_id' => 'asc')			
		));	
		$total = User::count();
		
		$this->set(compact('records', 'limit', 'page', 'total'));
	}
	
	public function read($url) {
	    $record = User::find('first', array('conditions' => array('url' => $url)));
            if((isset($record->library)) && ($record->library != 'minerva') && (!empty($record->library))) {
                $class = '\minerva\libraries\\'.$record->library.'\models\User'; 	  		
		if(class_exists($class)) {
                    $Library = new $class();
                }
            } 	
            $this->set(compact('record'));
	}
	
	public function create($library=null) {	
		// If we are using a library, instantiate it's User model (bridge from plugin to core)
		if((isset($library)) && ($library != 'minerva') && (!empty($library))) {		
			$class = '\minerva\libraries\\'.$library.'\models\User'; 	  		
			if(class_exists($class)) {
				$Library = new $class();
			}
		}	
		
		// Get the fields so the view template can iterate through them and build the form
		$fields = User::schema();
		// Don't need to have these fields in the form
		unset($fields[User::key()]);		
		
		// Save
		if ($this->request->data) {
			$this->request->data['library'] = $library; // Set the library to be saved with the record, saving null is ok too
			
			$this->request->data['password'] = sha1($this->request->data['password']);		    
			
			$user = User::create();		       
		  	if($user->save($this->request->data)) {				
				$this->redirect(array('controller' => 'users', 'action' => 'index'));
		  	}
		}
		
		if(empty($user)) {
			// Create an empty user object
			$user = User::create();
		}
		
		$this->set(compact('user', 'fields'));
	}
	
	/**
	 * Update a user.
	 *
	*/
	public function update($url=null) {	
		// First, get the record
		$record = User::find('first', array('conditions' => array('url' => $url)));
		
		// Next, if the record uses a library, instantiate it's User model (bridge from plugin to core)
		if((isset($record->library)) && ($record->library != 'minerva') && (!empty($record->library))) {		
			// Just instantiating the library's Page model will essentially "bridge" and extend the main app's User model	
			$class = '\minerva\libraries\\'.$record->library.'\models\User';
			if(class_exists($class)) {
                            $Library = new $class();
                        }
			// var_dump(User::$fields); // debug
			// var_dump($Library::$fields); // just the extended library's fields			
		}
		
		// Get the fields so the view template can build the form
		$fields = User::schema();                
		
		// Update the record
		if ($this->request->data) {
			unset($this->request->data['password']);
			if((isset($this->request->data['new_password'])) && (!empty($this->request->data['new_password']))) {
				$this->request->data['password'] = sha1($this->request->data['new_password']);
				unset($this->request->data['new_password']);
			}
                        // Call save from the main app's User model
                        if($record->save($this->request->data)) {
                            $this->redirect(array('controller' => 'users', 'action' => 'index'));
                        }                        
		}
		
                $this->set(compact('record', 'fields'));
	}
	
}
?>