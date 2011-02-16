<?php
namespace minerva\controllers;

use li3_access\security\Access;
use \lithium\security\Auth;
use \lithium\storage\Session;
use \lithium\util\Set;
use \lithium\util\String;
use minerva\libraries\util\Util;
use \lithium\util\Inflector;
use li3_flash_message\extensions\storage\FlashMessage;

class MinervaController extends \lithium\action\Controller {
  
    // Tricky little properties that help us pass the calling (child) class and method
    var $calling_class = __CLASS__;
    var $calling_method = __METHOD__;
    
    // On the document there's a field that links a 3rd party library for pages, users, and blocks. These are the fields. Routes also carry these names.
    var $library_fields = array(
        'page_type',
        'user_type',
        'block_type'
    );
    
    static $access = array();
  
    /**
     * Gets document(s) for the action and checks access.
     * 1. The proper model is determined so it can be used for the find() and also to pick up any access rules,
     *    apply filters, etc.
     * 2. The action access is checked
     * 3. The find() is made from the proper model (filterable on that model)
     * 4. The document access is checked
     * 5. The document(s) is returned
     *
     * This would be typically called from the core Minerva controllers (Pages, Users, and Blocks for example).
     * The purpose of this method is to reduce duplicate code and any unnecessary database queries.
     *
     * @param $action string The calling controller action (should be passed as __METHOD__ so the controller is also passed, ex. minerva\controllers\PagesController::read)
     * @param $request object The request object
     * @param $find_type string The find type (all, first, etc. if false then no find will be performed)
     * @param $conditions array The find conditions
     * @param $limit int The limit (for pagination)
     * @param $offset int The offset (for pagination)
     * @param $order array The order
     * @return mixed The find() results, true, or a redirect if access check fails
    */
    // $documents = $this->getDocument(__METHOD__, $this->request, 'all', $conditions, $limit, $offset, $order);
    public function getDocument($options=array()) {
        $defaults = array('action' => null, 'request' => null, 'find_type' => 'first', 'conditions' => array(), 'limit' => null, 'offset' => 0, 'order' => 'created.desc');
        $options += $defaults;
        extract($options);
        
        // $action=null, $request=null, $find_type='first', $conditions=array(), $limit=null, $offset=0, $order=null
        // 1. Determine the proper model to be using
        $model = Inflector::classify(Inflector::singularize($request->params['controller']));
        $modelClass = 'minerva\models\\'.$model;
        $library = null;
        $library_name_haystack = array();
        
        // if the action is read, update, or delete then the document will be able to tell us the library name
        if(($request->params['action'] == 'read') || ($request->params['action'] == 'update') || ($request->params['action'] == 'delete')) {
            // could be using the MongoId or the pretty URL in the route. Both work, but prefer the URL if set and there's no need to use both.
            $conditions = array();
            if(isset($request->params['id'])) {
                $conditions = array('_id' => $request->params['id']);
            }
            if(isset($request->params['url'])) {
                $conditions = array('url' => $request->params['url']);
            }
            $record = $modelClass::first(array('request_params' => $request->params, 'conditions' => $conditions, 'fields' => $this->library_fields));
            $library_name_haystack = ($record) ? $record->data():array();
        } else {
            // otherwise for index and create methods the library name is passed in the routes. as page_type or user_type or block_type
            $library_name_haystack = $request->params;
        }
        
        // get the library name
        foreach($this->library_fields as $field) {
            if(in_array($field, array_keys($library_name_haystack))) {
                $library = $library_name_haystack[$field];
            }
        }
        
        $class = '\minerva\libraries\\'.$library.'\models\\'.$model;
        // Don't load the model if it doesn't exist
        if(class_exists($class)) {
            // instantiate it so it can apply its properties to the base model along with any filters, etc.
            $modelClass = new $class();
        }
      
        // 2. Authentication & Access Check for Core Minerva Controllers
        // (properties set in model for core controllers) ... transfer those settings to the controller
        $controller_pieces = explode('::', $action);
        if(count($controller_pieces) > 1) {
            $action = $controller_pieces[1];
        }
        
        $controllerClass = '\minerva\controllers\\'.$request->params['controller'].'Controller';
        
        // If the $controllerClass doesn't exist, it means it's a controller that Minerva doesn't have. That means it's not core and the access can be set there on that controller.
        if((isset($modelClass::$access)) && (class_exists($controllerClass))) {
            // Don't completely replace core Minerva controller with new access rules, but append all the rules (giving the library model's access property priority)
            $controllerClass::$access = $modelClass::$access += $controllerClass::$access;
        }
        
        // Get the rules for this action
        $rules = (isset($controllerClass::$access[$request->params['action']])) ? $controllerClass::$access[$request->params['action']]:array();
        
        // Check access for the action in general
        $action_access = Access::check('minerva_access', Auth::check('minerva_user'), $request, array('rules' => $rules['action']));
        
        if(!empty($action_access)) {
            FlashMessage::set($action_access['message'], array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => '.8')));
            $this->redirect($action_access['redirect']);
        }
        
        // Before getting documents, make sure the calling method actually wanted to get documents.
        if($find_type === false) {
            return true;
        }
        
        /**
         * 3. Get Document(s)
         * If we're here, we now need to get the document(s) to determine any document based access
         * and we'll return the document(s) back to the controller too.
        */
        $document = $modelClass::find($find_type, array(
            'conditions' => $conditions,
            'limit' => $limit,
            'order' => Util::format_dot_order($order),
            'request_params' => $request->params // this is not used for Lithium's find() but gives some useful data if find() is filtered
        ));
        
        // 4. Document Access Control
        if($document) {
            // add the document to the document access rules so it can be checked against
            $i=0;
            foreach($rules as $rule) {
                $rules['document'][$i] = $document->data();
                $i++;
            }
            
            $document_access = Access::check('minerva_access', Auth::check('minerva_user'), $request, array('rules' => $rules['document']));
            if(!empty($document_access)) {
                FlashMessage::set($document_access['message'], array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => '.8')));
                $this->redirect($document_access['redirect']);
            }
        }
        
        
        //read()
        // $document = $modelClass::find('first', array('conditions' => array('url' => $url), 'request_params' => $this->request->params));
        
        // modelClass will either be a core Minerva model class or the extended matching library model class
        return $document;
    }
    
    /**
     * Generic index() action that returns a list of paginated documents.
     * The trick here is that $this->calling_class and $this->calling_method will hold a string
     * reference for which extended class called this index() method. We need that in order to
     * get the proper records and access.
    */
    public function index() {
        // get the "_type" ... page_type, user_type, or block_type
        $model = Inflector::classify(Inflector::singularize($this->request->params['controller']));
        $modelClass = 'minerva\models\\'.$model;
        $x_type = strtolower($model) . '_type';
        // or set it to "all" if there wasn't a param passed
        $type = ((isset($this->request->params[$x_type])) && (in_array($x_type, $this->library_fields))) ? $this->request->params[$x_type]:'all';
        
        // Default options for pagination, merge with URL parameters
        $defaults = array('page' => 1, 'limit' => 10, 'order' => 'created.desc');
        $params = Set::merge($defaults, $this->request->params);
        if((isset($params['page'])) && ($params['page'] == 0)) {
            $params['page'] = 1;
        }
        list($limit, $page, $order) = array($params['limit'], $params['page'], $params['order']);
        
        // If there's a page/user/block_type passed, add it to the conditions, 'all' will show all pages.
        if($type != 'all') {
            $conditions = array($type => $this->request->params[$type]);
        } else {
            $conditions = array();
        }
        
        // If a search query was provided, search all "searchable" fields (any field in the model's $search_schema property)
        // NOTE: the values within this array for "search" include things like "weight" etc. and are not yet fully implemented...But will become more robust and useful.
        // Possible integration with Solr/Lucene, etc.
        if((isset($this->request->query['q'])) && (!empty($this->request->query['q']))) {
            $search_schema = $modelClass::search_schema();
            // If the "*_type" is set to "all" then we want to get all the model type's schemas, merge them into $schema
            if($type == 'all') {
            foreach(Util::list_types($model, array('exclude_minerva' => true)) as $library) {
                $extendedModelClass = 'minerva\libraries\\' . $library;
                $search_schema += $extendedModelClass::search_schema();
            }
            }
            
            $search_conditions = array();
            // For each searchable field, adjust the conditions to include a regex
            foreach($search_schema as $k => $v) {
            // TODO: possibly factor in the weighting later. also maybe note the "type" to ensure our regex is going to work or if it has to be adjusted (string data types, etc.)
            //var_dump($k);
            $search_regex = new \MongoRegex('/' . $this->request->query['q'] . '/i');
            $conditions['$or'][] = array($k => $search_regex);
            }
            
        }
        
        // Get the documents and the total
        $documents = array();
        if((int)$params['limit'] > 0) {
            $documents = $this->getDocument(array(
                'action' => $this->calling_method,
                'request' => $this->request,
                'find_type' => 'all',
                'conditions' => $conditions,
                'limit' => (int)$params['limit'],
                'offset' => ($params['page'] - 1) * $limit,
                'order' => $params['order']
            ));
        }
        
        // Get some handy numbers
        $total = $modelClass::find('count', array(
            'conditions' => $conditions
        ));
        $page_number = $params['page'];
        $total_pages = ((int)$params['limit'] > 0) ? ceil($total / $params['limit']):0;
        
        // Set data for the view template
        $this->set(compact('documents', 'limit', 'page_number', 'total_pages', 'total'));
    }
    
    /**
     * Generic create() action.
     * The trick here is that $this->calling_class and $this->calling_method will hold a string
     * reference for which extended class called this create() method. We need that in order to
     * get the proper records and access.
    */
    public function create() {
        // get the "_type" ... page_type, user_type, or block_type
        $model = Inflector::classify(Inflector::singularize($this->request->params['controller']));
        $modelClass = 'minerva\models\\'.$model;
        $x_type = strtolower($model) . '_type';
        // or set it to "all" if there wasn't a param passed
        $type = ((isset($this->request->params[$x_type])) && (in_array($x_type, $this->library_fields))) ? $this->request->params[$x_type]:'all';
        
        // this just checks access
        $this->getDocument(array('action' => $this->calling_method, 'request' => $this->request, 'find_type' => false));
        
        // Get the model class we should be using for this (it could be an extended class from a library)
        $modelClass = $modelClass::getMinervaModel($model, $type);
        
        // Get the name for the page, so if another type library uses the "admin" (core) templates for this action, it will be shown
        $display_name = $modelClass::display_name();
        
        // Lock the schema. We don't want any unwanted data passed to be saved
        $modelClass::meta('locked', true);
        
        // Get the fields so the view template can iterate through them and build the form
        $fields = $modelClass::schema();
        
        // Don't need to have these fields in the form
        unset($fields['_id']);
        // If a page type was passed in the params, we'll need it to save to the page document.
        $fields[$x_type]['form']['value'] = ($type != 'all') ? $type:null;
        
        // If data was passed, set some more data and save
        if ($this->request->data) {
            $document = $modelClass::create();
            $now = date('Y-m-d h:i:s');
            $this->request->data['created'] = $now;
            $this->request->data['modified'] = $now;
            $this->request->data['url'] = Util::unique_url(array(
                'url' => Inflector::slug($this->request->data['title']),
                'model' => $modelClass
            ));
            $user = Auth::check('minerva_user');
            if($user) {
                $this->request->data['owner_id'] = $user['_id'];
            } else {
                // TODO: possible for anonymous users to create things? do we need to put in any value here?
                $this->request->data['owner_id'] = '';
            }
            
            // (note: this will only be useful for UsersController)
            if(($this->request->params['controller'] == 'users') && (isset($this->request->data['password']))) {
                $this->request->data['password'] = String::hash($this->request->data['password']);
            }
            
            // Save
            if($document->save($this->request->data)) {
                FlashMessage::set('The content has been created successfully.', array('options' => array('type' => 'success', 'pnotify_title' => 'Success', 'pnotify_opacity' => .8)));
                $this->redirect(array('controller' => $this->request->params['controller'], 'action' => 'index'));
            } else {
                FlashMessage::set('The content could not be saved, please try again.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
            }
        }
        
        if(empty($document)) {                
            $document = $modelClass::create(); // Create an empty page object
        }
        
        $this->set(compact('document', 'fields', 'display_name'));
    }
    
    /**
     * Generic update() action.
     * The trick here is that $this->calling_class and $this->calling_method will hold a string
     * reference for which extended class called this update() method. We need that in order to
     * get the proper records and access.
    */
    public function update() {
        // get the "_type" ... page_type, user_type, or block_type
        $model = Inflector::classify(Inflector::singularize($this->request->params['controller']));
        $modelClass = 'minerva\models\\'.$model;
        $x_type = strtolower($model) . '_type';
        
        // Use the pretty URL if provided
		if(isset($this->request->params['url'])) {
			$conditions = array('url' => $this->request->params['url']);
		}
		
        // ...But if the id was provided, use that (for example; UsersController will be using the id)
		if(isset($this->request->params['id'])) {
			$conditions = array('_id' => $this->request->params['id']);
		}
        
        // or set it to "all" if there is no *_type in the record (this part differs from create() because the type won't come from the route)
        $type = $modelClass::find('first', array('conditions' => $conditions, 'fields' => array($x_type)))->$x_type;
        $type = (!empty($type)) ? $type:'all';
        
        // Get the model class we should be using for this (it could be an extended class from a library)
        
        $modelClass = $modelClass::getMinervaModel($model, $type);
        
		// Get the name for the page, so if another type library uses the "admin" (core) templates for this action, it will be shown
		$display_name = $modelClass::display_name();
		
		// Get the fields so the view template can build the form
		$fields = $modelClass::schema();
		// Don't need to have these fields in the form
		unset($fields['_id']);
        if($this->request->params['controller'] == 'users') {
            unset($fields['password']);
            // unset password and add a "new_password" field for UsersController
            $fields['new_password'] = null;
        }
		// If a *_type was passed in the params (and wasn't "all") we'll need it to save to the page document.
		$fields[$x_type]['form']['value'] = ($type != 'all') ? $type:null;
		
		// Get the document
		$document = $this->getDocument(array('action' => $this->calling_method, 'request' => $this->request, 'find_type' => 'first', 'conditions' => $conditions));
        
        // Update the record
		if ($this->request->data) {
			// Set some data
            $this->request->data['modified'] = date('Y-m-d h:i:s');
            
            // (note: the password stuff is only useful for UsersController)
            if($this->request->params['controller'] == 'users') {
                if(isset($this->request->data['password'])) {
                    unset($this->request->data['password']);
                }
                if((isset($this->request->data['new_password'])) && (!empty($this->request->data['new_password']))) {
                    $this->request->data['password'] = String::hash($this->request->data['new_password']);
                    unset($this->request->data['new_password']);
                }
            }
			
            // Save it
			if($document->save($this->request->data)) {
                FlashMessage::set('The content has been updated successfully.', array('options' => array('type' => 'success', 'pnotify_title' => 'Success', 'pnotify_opacity' => .8)));
                $this->redirect(array('controller' => $this->request->params['controller'], 'action' => 'index'));
			} else {
                FlashMessage::set('The content could not be updated, please try again.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
            }
		}
	    
	    $this->set(compact('document', 'fields', 'display_name'));
    }
    
    /**
     * Generic delete() action.
     * The trick here is that $this->calling_class and $this->calling_method will hold a string
     * reference for which extended class called this delete() method. We need that in order to
     * get the proper records and access.
    */
    public function delete() {
        // get the "_type" ... page_type, user_type, or block_type
        $model = Inflector::classify(Inflector::singularize($this->request->params['controller']));
        $modelClass = 'minerva\models\\'.$model;
        
        $conditions = array();
        if(isset($this->request->params['id'])) {
            $conditions = array('id' => $this->request->params['id']);
        }
        if(isset($this->request->params['url'])) {
            $conditions = array('url' => $this->request->params['url']);
        }
        
        if(empty($conditions)) {
            $this->redirect(array('controller' => $this->request->params['controller'], 'action' => 'index'));
        }
        
        $document = $this->getDocument(array(
            'action' => __METHOD__,
            'request' => $this->request,
            'find_type' => 'first',
            'conditions' => $conditions
        ));
        
        if($document->delete()) {
            FlashMessage::set('The content has been deleted.', array('options' => array('type' => 'success', 'pnotify_title' => 'Success', 'pnotify_opacity' => .8)));
            $this->redirect(array('controller' => $this->request->params['controller'], 'action' => 'index'));
        } else {
            FlashMessage::set('The content could not be deleted, please try again.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
            $this->redirect(array('controller' => $this->request->params['controller'], 'action' => 'index'));
        }
    }
    
}
?>