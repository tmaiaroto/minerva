<?php
namespace minerva\controllers;

/*
use li3_access\security\Access;
use \lithium\security\Auth;
use \lithium\storage\Session;

use \lithium\util\String;

use \lithium\util\Inflector;
*/

use minerva\extensions\util\Util;
use \lithium\util\Set;
use \lithium\util\Inflector;
use minerva\models\Page;
use li3_flash_message\extensions\storage\FlashMessage;

class MinervaController extends \lithium\action\Controller {
  
    // Tricky little properties that help us pass the calling (child) class and method
    var $calling_class = __CLASS__;
    var $calling_method = __METHOD__;
    
    var $minerva_config = array();
   
    static $access = array();
    
    /**
     * Sets up some very important information that Minerva needs.
     * Most notably, this is what extends schema, validation rules, etc.
     * by ensuring the controller is using the proper model.
     * 
     * This data is stored in the controller's "minerva_config" property.
     * Then calls the parent::_init() and continues like normal.
     *
     * In the future, we can store additional information that we need here.
     * Potentially even filterable? Let's say we use this area for other information
     * such as redirects, etc.
     * 
    */
    protected function _init() {
        $this->request = $this->request ?: $this->_config['request'];
        
        $controller_pieces = explode('.', $this->request->params['controller']);
        $this->minerva_config['relative_controller'] = $relative_controller = (count($controller_pieces) > 1) ? $controller_pieces[1]:$controller_pieces[0];
        $this->minerva_config['model'] = $model = Inflector::classify(Inflector::singularize($relative_controller));
        // set the model class, should be minerva\models\Page or minerva\models\Block etc.
        $ModelClass = $DefaultModelClass = 'minerva\models\\'.$model;
        // in case it doesn't exist, use the base MinervaModel which we know does exist
        $ModelClass = (class_exists($ModelClass)) ? $ModelClass:'minerva\models\MinervaModel';
        $document_type = $ModelClass::document_type();
        // the document type can be grabbed from the model class, but if specifically set in the routing params, use that
        if(isset($this->request->params['document_type']) && !empty($this->request->params['document_type'])) {
            $document_type = $this->request->params['document_type'];
        }
        
        // set the ModelClass again now based on the $document_type, which in most cases, matches the library name
        // ignore and empty $document_type, that just means the base model class anyway
        if(!empty($document_type)) {
            $ModelClass = $ModelClass::getMinervaModel($model, $document_type);
        }
        
        /**
         * If getMinveraModel() couldn't find one... meaning the $document_type did NOT match the library name, 
         * we need to search ALL minerva models to find the proper model. This is where a slight performance penalty
         * comes in to play, so try to match library names to document_type values.
         *
         * If unavoidable, because there were two libraries of the same name that want to use Minerva, just know
         * that all we're doing is looping through each model that's using Minerva ("minerva_models") and looking
         * to match the document_type property. Once matched, we found the proper model class. So not too bad.
         * 
        */
        if($ModelClass == 'minerva\models\MinervaModel' || $ModelClass == $DefaultModelClass) {
            $all_minerva_models = $ModelClass::getAllMinervaModels($model);
            if(!empty($all_minerva_models)) {
                foreach($all_minerva_models as $Model) {
                    if($Model::document_type() == $document_type) {
                        $ModelClass = $Model;
                    }
                }
            }
            
            // and of course no matter what we set, make sure it exists, otherwise default.
            $ModelClass = (class_exists($ModelClass)) ? $ModelClass:$DefaultModelClass;
        }
        
        // Now the following will use the proper ModelClass to get the properties that we need in the controller.
        $this->minerva_config['ModelClass'] = $ModelClass;
        $this->minerva_config['display_name'] = $ModelClass::display_name();
        $this->minerva_config['library_name'] = $ModelClass::library_name();
        $this->minerva_config['document_type'] = $document_type;
        $this->minerva_config['admin'] = (isset($this->request->params['admin'])) ? $this->request->params['admin']:false;
        
        parent::_init();
    }
    
    /**
     * Gets the redirects for each action.
     * These redirects have defaults, but can be set on each Minerva model.
     * The current $this->minerva_config['ModelClass'] will be used to lookup
     * this action_redirect property from the model.
     *
     * @return Array
    */
    public function getRedirects() {
        $redirects = array();
        $admin = (isset($this->request->params['admin'])) ? $this->request->params['admin']:false;
        $default_redirects = array(
            'create' => array('admin' => $admin, 'library' => 'minerva', 'controller' => $this->request->params['controller'], 'action' => 'index'),
            'update' => array('admin' => $admin, 'library' => 'minerva', 'controller' => $this->request->params['controller'], 'action' => 'index'),
            'delete' => array('admin' => $admin, 'library' => 'minerva', 'controller' => $this->request->params['controller'], 'action' => 'index')
        );
        $ModelClass = $this->minerva_config['ModelClass'];
        $redirects = $ModelClass::action_redirects();
        $redirects += $default_redirects;
        
        // loop through, look for special redirect values
        foreach($redirects as $k => $v) {
            if($v == 'self') {
                $redirects[$k] = '/' . $this->request->url;
            }
            if($v == 'referer') {
                if(isset($_SERVER['HTTP_REFERER'])) {
                    $redirects[$k] = $_SERVER['HTTP_REFERER'];
                } else {
                    $redirects[$k] = '/' . $this->request->url;
                }
            }
        }
        
        return $redirects;
    }
    
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
        extract($this->minerva_config);
        // done! came from _init() ... cleaned that shit right up
        
        // if the action is read, update, or delete then the document will be able to tell us the library name - otherwise it's going to be "minerva"
        if(($request->params['action'] == 'read') || ($request->params['action'] == 'update') || ($request->params['action'] == 'delete')) {
            // could be using the MongoId or the pretty URL in the route. Both work, but prefer the URL if set and there's no need to use both.
            $conditions = array();
            if(isset($request->params['id'])) {
                $conditions = array('_id' => $request->params['id']);
            }
            if(isset($request->params['url'])) {
                $conditions = array('url' => $request->params['url']);
            }
            $record = $ModelClass::first(array('request_params' => $request->params, 'conditions' => $conditions, 'fields' => array('document_type')));
            // it should be an object...if it wasn't found that's a problem
            if(is_object($record)) {
                if($record->document_type) {
                    // set the document_type to that of the document's from the database (still technically could be empty, that's ok)
                    $this->minerva_config['document_type'] = $record->document_type;
                    
                    // ideally, the document_type should be the name of the library
                    $MinervaModelClass = $ModelClass::getMinervaModel($model, $record->document_type);
                    if(class_exists($MinervaModelClass)) {
                        $this->minerva_config['ModelClass'] = $ModelClass = $MinervaModelClass;
                        // but if not...we have to search for it
                        // (minor performance penalty for the looping, but may be unavoidable)
                    } else {
                        // similar to the code in _init(), we're searching through all minerva models
                        $all_minerva_models = $ModelClass::getAllMinervaModels($model);
                        foreach($all_minerva_models as $MinervaModelClass) {
                            if($MinervaModelClass::document_type() == $record->document_type) {
                                $this->minerva_config['ModelClass'] = $ModelClass = $MinervaModelClass;
                            }
                        }
                    }
                    // finaly, we can set the proper $library_name (and the proper $ModelClass will be set now too)
                    $this->minerva_config['library_name'] = $ModelClass::library_name();
                    // also, we will need to set the display name
                    $this->minerva_config['display_name'] = $ModelClass::display_name();
                }
            }
        }
      
        // 2. Authentication & Access Check for Core Minerva Controllers
        // (properties set in model for core controllers) ... transfer those settings to the controller
        $ControllerClass = '\minerva\controllers\\'.$relative_controller.'Controller';
        
        // If the $controllerClass doesn't exist, it means it's a controller that Minerva doesn't have. That means it's not core and the access can be set there on that controller.
        if((isset($ModelClass::$access)) && (class_exists($ControllerClass))) {
            // Don't completely replace core Minerva controller with new access rules, but append all the rules (giving the library model's access property priority)
            $ControllerClass::$access = $ModelClass::$access += $ControllerClass::$access;
        }
        
        // Get the rules for this action
        $rules = (isset($ControllerClass::$access[$request->params['action']])) ? $ControllerClass::$access[$request->params['action']]:array();
        
        // Check access for the action in general
        //$action_access = Access::check('minerva_access', Auth::check('minerva_user'), $request, array('rules' => $rules['action']));
        // TODO: put this back on
        $action_access = array();
        
        if(!empty($action_access)) {
            FlashMessage::write($action_access['message'], array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => '.8')));
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
        $document = $ModelClass::find($find_type, array(
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
                $rules['document'][$i]['document'] = $document->data();
                $i++;
            }
            
            //$document_access = Access::check('minerva_access', Auth::check('minerva_user'), $request, array('rules' => $rules['document']));
            $document_access = array();
            // TODO : put this back
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
        // first, get all the data we need. this will set $x_type, $type, $modelClass, and $display_name
        extract($this->minerva_config);
        
        // Default options for pagination, merge with URL parameters
        $defaults = array('page' => 1, 'limit' => 10, 'order' => 'created.desc');
        $params = Set::merge($defaults, $this->request->params);
        if((isset($params['page'])) && ($params['page'] == 0)) {
            $params['page'] = 1;
        }
        list($limit, $page, $order) = array($params['limit'], $params['page'], $params['order']);
        
        // If there's a page/user/block_type passed, add it to the conditions, '*' will show all pages.
        if(!empty($document_type)) {
            $conditions = array('document_type' => $document_type);
        } else {
            $conditions = array();
        }
        
        // If a search query was provided, search all "searchable" fields (any field in the model's $search_schema property)
        // NOTE: the values within this array for "search" include things like "weight" etc. and are not yet fully implemented...But will become more robust and useful.
        // Possible integration with Solr/Lucene, etc.
        if((isset($this->request->query['q'])) && (!empty($this->request->query['q']))) {
            $search_schema = $ModelClass::search_schema();
            // If the "document_type" is set to "*" then we want to get all the model content_type's schemas, merge them into $schema
            if($document_type == '*') {
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
        $total = $ModelClass::find('count', array(
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
        // first, get all the data we need. this will set $x_type, $type, $modelClass, and $display_name
        extract($this->minerva_config);
        
        $this->getDocument(array('action' => $this->calling_method, 'request' => $this->request, 'find_type' => false));
        // get the redirects; important to call this AFTER $this->getDocument() because the proper $ModelClass will be set (it will have changed based on the document from the database)
        $action_redirects = $this->getRedirects();
        
        // Get the fields so the view template can iterate through them and build the form
        $fields = $ModelClass::schema();
        
        // Don't need to have these fields in the form
        unset($fields['_id']);
        // If a page type was passed in the params, we'll need it to save to the page document.
        //$fields[$x_type]['form']['value'] = ($type != 'all') ? $type:null;
        
        // If data was passed, set some more data and save
        if ($this->request->data) {
            $document = $ModelClass::create();
            $now = date('Y-m-d h:i:s');
            $this->request->data['created'] = $now;
            $this->request->data['modified'] = $now;
            // If a page type was passed in the params, we'll need it to save to the page document.
            $this->request->data['document_type'] = $document_type;
            $this->request->data['url'] = Util::unique_url(array(
                'url' => Inflector::slug($this->request->data['title']),
                'model' => $ModelClass
            ));
            /*$user = Auth::check('minerva_user');
            if($user) {
                $this->request->data['owner_id'] = $user['_id'];
            } else {
                // TODO: possible for anonymous users to create things? do we need to put in any value here?
                $this->request->data['owner_id'] = '';
            }*/
            $this->request->data['owner_id'] = '';
            
            // (note: this will only be useful for UsersController)
            if(($this->request->params['controller'] == 'users') && (isset($this->request->data['password']))) {
                $this->request->data['password'] = String::hash($this->request->data['password']);
            }
            
            // Save
            if($document->save($this->request->data)) {
                FlashMessage::write('The content has been created successfully.', array(), 'minerva_admin');
                $this->redirect($action_redirects['create']);
            } else {
                FlashMessage::write('The content could not be saved, please try again.', array(), 'minerva_admin');
            }
        }
        
        if(empty($document)) {                
            $document = $ModelClass::create(); // Create an empty page object
        }
        
        $this->set(compact('document', 'fields', 'display_name', 'document_type'));
    }
    
    /**
     * Generic update() action.
     * The trick here is that $this->calling_class and $this->calling_method will hold a string
     * reference for which extended class called this update() method. We need that in order to
     * get the proper records and access.
    */
    public function update() {
        
        $conditions = array();
        // Use the pretty URL if provided
		if(isset($this->request->params['url'])) {
			$conditions = array('url' => $this->request->params['url']);
		}
		
        // ...But if the id was provided, use that (for example; UsersController will be using the id)
		if(isset($this->request->params['id'])) {
			$conditions = array('_id' => $this->request->params['id']);
		}
        
		// Get the document
		$document = $this->getDocument(array('action' => $this->calling_method, 'request' => $this->request, 'find_type' => 'first', 'conditions' => $conditions));
        // get the redirects (again, call AFTER $this->getDocument() because the $ModelClass will have changed)
        $action_redirects = $this->getRedirects();
        
        // NOW get all the data we need from minerva_config for this method because $this->getDocument() will have changed it for read/update/delete
        extract($this->minerva_config);
        
        // Get the fields so the view template can build the form
		$fields = $ModelClass::schema();
		// Don't need to have these fields in the form
		unset($fields['_id']);
        if($this->request->params['controller'] == 'users') {
            unset($fields['password']);
            // unset password and add a "new_password" field for UsersController
            $fields['new_password'] = null;
        }
		// If a document_type isn't empty, set it in the form
		$fields['document_type']['form']['value'] = (!empty($document_type)) ? $document_type:null;
        
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
                FlashMessage::write('The content has been updated successfully.', array(), 'minerva_admin');
                $this->redirect($action_redirects['update']);
			} else {
                FlashMessage::write('The content could not be updated, please try again.', array(), 'minerva_admin');
            }
		}
	    
	    $this->set(compact('document', 'fields', 'display_name', 'document_type'));
    }
    
    /**
     * Generic delete() action.
     * The trick here is that $this->calling_class and $this->calling_method will hold a string
     * reference for which extended class called this delete() method. We need that in order to
     * get the proper records and access.
    */
    public function delete() {
        // first, get all the data we need. this will set $x_type, $type, $modelClass, and $display_name
        extract($this->minerva_config);
        
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
        
        // called after $this->getDocument() so the proper $ModelClass is used
        $action_redirects = $this->getRedirects();
        
        if($document->delete()) {
            FlashMessage::write('The content has been deleted.', array(), 'minerva_admin');
        } else {
            FlashMessage::write('The content could not be deleted, please try again.', array(), 'minerva_admin');
        }
        $this->redirect($action_redirects['delete']);
    }
    
}
?>