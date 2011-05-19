<?php
namespace minerva\controllers;

use \lithium\storage\Session;
use li3_access\security\Access;
use \lithium\security\Auth;
use minerva\extensions\util\Util;
use \lithium\util\Set;
use \lithium\util\String;
use \lithium\util\Inflector;
use minerva\models\Page;
use minerva\models\User;
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
        
        /**
         * The only time I can think that there wouldn't be $this->request is from requestAction() on the Block helper.
         * TODO: Ensure that all this is not required by that helper's method.
         * For now this if() will prevent some errors.
        */
        if(!empty($this->request)) {
            $controller_pieces = explode('.', $this->request->params['controller']);
            $this->minerva_config['relative_controller'] = $relative_controller = (count($controller_pieces) > 1) ? $controller_pieces[1]:$controller_pieces[0];
            $this->minerva_config['model'] = $model = Inflector::classify(Inflector::singularize($relative_controller));
            // set the model class, should be minerva\models\Page or minerva\models\Block etc.
            $ModelClass = $DefaultModelClass = 'minerva\models\\'.$model;
            // in case it doesn't exist, use the base MinervaModel which we know does exist
            $ModelClass = (class_exists($ModelClass)) ? $ModelClass:'minerva\models\MinervaModel';
            $document_type = $ModelClass::document_type();
            // the document type can be grabbed from the model class, but if specifically set in the routing params, use that
            
            // using args instead of keyed "document_type" ... so for "index" and "create" the first arg is going to be the document_type...
            // no other action passes the document_type
            if(isset($this->request->params['action']) && ($this->request->params['action'] == 'create') || ($this->request->params['action'] == 'index')) {
                if(isset($this->request->params['args'][0])) {
                    $document_type = $this->request->params['args'][0];
                }
            }
            
            // this will no longer be used...
            if(isset($this->request->params['document_type']) && !empty($this->request->params['document_type'])) {
                $document_type = $this->request->params['document_type'];
            }
            // there are two specific "create" routes to handle a conflict in the routing
            // alternatively, we could use the "{:args}" route... but "create" action is the only case this is a problem (for now)
            /*
            if($this->request->params['action'] == 'create') {
                if(isset($this->request->params['args'][0])) {
                    $document_type = $this->request->params['args'][0];
                }
            }
            */
            
            // set the ModelClass again now based on the $document_type, which in most cases, matches the library name
            // ignore and empty $document_type, that just means the base model class anyway
            if(!empty($document_type)) {
                $ModelClass = $ModelClass::getMinervaModel($model, $document_type);
                
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
                            if(class_exists($Model)) {
                                if($Model::document_type() == $document_type) {
                                    $ModelClass = $Model;
                                }
                            }
                        }
                    }
                    
                    // and of course no matter what we set, make sure it exists, otherwise default.
                    $ModelClass = (class_exists($ModelClass)) ? $ModelClass:$DefaultModelClass;
                }
                
            }
            
            // Now the following will use the proper ModelClass to get the properties that we need in the controller.
            $this->minerva_config['ModelClass'] = $ModelClass;
            $this->minerva_config['display_name'] = $ModelClass::display_name();
            $this->minerva_config['library_name'] = $ModelClass::library_name();
            $this->minerva_config['document_type'] = $document_type;
            $this->minerva_config['admin'] = (isset($this->request->params['admin'])) ? $this->request->params['admin']:false;
            
            // Also good to set the redirect url here for after logging in (last requested URL)
            // ...but we don't want to set it to any of the following
            $controller_action_whitelist = array(
                'users.login',
                'users.logout',
                'users.register',
                'users.is_email_in_use',
                'users.register'
            );
            if(!in_array($relative_controller . '.' . $this->request->params['action'], $controller_action_whitelist)) {
                Session::write('beforeAuthURL', '/' . $this->request->url, array('name' => 'minerva_cookie', 'expires' => '+1 hour'));
            }
            
        }
        
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
            'delete' => array('admin' => $admin, 'library' => 'minerva', 'controller' => $this->request->params['controller'], 'action' => 'index'),
            'logout' => '/',
            'register' => array('library' => 'minerva', 'controller' => 'users', 'action' => 'login')
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
            
            // removing the following because we are using passed args...
            //$conditions = array();
            // ALL models will use a 'url' field, even if it's just the _id that gets put there.
            /*if(isset($request->params['url'])) {
               $conditions = array(
                    'url' => $request->params['url']
                ); 
            } */
            
            // this would still work but its redundant. $conditions will be passed in now from the other actions which take args and do all this.
            /* // If using {:args} route...
            if(isset($request->params['args'][0])) {
                $conditions = array(
                    'url' => $request->params['args'][0]
                );
            } else {
                // TODO: redirect. document not found.
            }
            */
            
            $record = $ModelClass::first(array('request_params' => $request->params, 'conditions' => $conditions, 'fields' => array('document_type')));
            // it should be an object...if it wasn't found that's a problem
            if(is_object($record)) {
                if($record->document_type) {
                    // set the document_type to that of the document's from the database (still technically could be empty, that's ok)
                    $this->minerva_config['document_type'] = $record->document_type;
                    
                    // ideally, the document_type should be the name of the library
                    $MinervaModelClass = $ModelClass::getMinervaModel($model, $record->document_type);
                    if(is_string($MinervaModelClass) && class_exists($MinervaModelClass)) {
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
        
        // If the class doesn't exist, this was a library that decided to make it's own controller extend this controller
        // So locate it. It will hold our access rules and such.
        if(!class_exists($ControllerClass)) {
            $ControllerClass = \lithium\core\Libraries::locate('controllers', $this->request->params['library'] . '.' . $relative_controller);
        }
        
        // OK. If still not found, there's probably something wrong, but we don't want the system to fail so use this class.
        if(!class_exists($ControllerClass)) {
            $ControllerClass = __CLASS__;
        }
        
        // If the $controllerClass doesn't exist, it means it's a controller that Minerva doesn't have. That means it's not core and the access can be set there on that controller.
        if((isset($ModelClass::$access)) && (class_exists($ControllerClass))) {
            // Don't completely replace core Minerva controller with new access rules, but append all the rules (giving the library model's access property priority)
            $ControllerClass::$access = $ModelClass::$access += $ControllerClass::$access;
        }
        
        // Get the rules for this action
        $rules = (isset($ControllerClass::$access[$request->params['action']])) ? $ControllerClass::$access[$request->params['action']]:array();
        
        // There's going to be two sets of action access rules. One for non-admin actions and one for admin actions.
        // If there's no 'action' or 'admin_action' keys for access on the controller's properties then allow access.
        if(isset($this->minerva_config['admin']) && $this->minerva_config['admin'] === false) {
            // Check access for the action in general
            // default is empty, so we're going to allow access by default on non-admin actions.
            $action_access = array();
            if(isset($rules['action']) && !empty($rules['action'])) {
                $action_access = Access::check('minerva_access', Auth::check('minerva_user'), $request, array('rules' => $rules['action']));
            }
        } else {
            // Else, it has to be an action accessed with the admin flag, right? ($this->minerva_config['admin'] won't be true, it will contain a string with the admin prefix)
            // by default, don't allow access to any admin action
            if(!isset($rules['admin_action']) || empty($rules['admin_action'])) {
                $rules['admin_action'] = array(
                    'rule' => 'allowManagers',
                    'message' => 'Sorry, you must be an administrator to access that section.',
                    'redirect' => array(
                        'admin' => 'admin',
                        'library' => 'minerva',
                        'controller' => 'users',
                        'action' => 'login'
                        )
                    );
            }
            $action_access = Access::check('minerva_access', Auth::check('minerva_user'), $request, array('rules' => $rules['admin_action']));
        }
        
        if(!empty($action_access)) {
            FlashMessage::write($action_access['message'], array(), 'minerva_admin');
            $this->redirect($action_access['redirect']);
            /**
             * We need to return false at this point because we've already set a redirect,
             * but the rest of the code could still execute. This is mostly unnoticed, but is very
             * noticable for the view() actions for static pages/menus/blocks/etc.
             * Those view() actions call $this->render() if this getDocument() method doesn't return false.
             * So those pages would still be accessible.
             * IF access is allowed and the action wasn't requesting a document from the database, then
             * the $find_type will be true and as you can see below, this method will return true.
             * Again, if returning true, those static view() methods will render a static template.
            */
            return false;
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
        $options = array(
            'conditions' => $conditions,
            'limit' => $limit,
            'order' => Util::format_dot_order($order),
            'request_params' => $request->params // this is not used for Lithium's find() but gives some useful data if find() is filtered
        );
        if($offset > 0) {
            $options += array('offset' => $offset);
        }
        $document = $ModelClass::find($find_type, $options, array('request_params' => $request->params));
        
        // 4. Document Access Control
        
        // Get the rules for this action
        $rules = (isset($ControllerClass::$access[$request->params['action']])) ? $ControllerClass::$access[$request->params['action']]:array();
        
        if($document && is_object($document)) {
            // add the document to the document access rules so it can be checked against
            $i=0;
            foreach($rules as $rule) {
                $rules['document'][$i]['document'] = $document->data();
                $i++;
            }
            
            $document_access = false;
            if(isset($rules['document'])) {
                //$document_access = Access::check('minerva_access', Auth::check('minerva_user'), $request, array('rules' => $rules['document']));
                $document_access=array(); // TODO: PUT BACK ACCESS CHECK
            }
            if(!empty($document_access)) {
                FlashMessage::set($document_access['message'], array('options' => array('type' => 'growl', 'pnotify_title' => 'Error', 'pnotify_opacity' => '.8')));
                return $this->redirect($document_access['redirect']);
                // set the document to false if there's no access to it. the method may be accessible, but the document may not be. so setting it false essentially unsets it, but in a nicer way.
                $document = false;
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
    public function index($document_type=null) {
        // first, get all the data we need. this will set $x_type, $type, $modelClass, and $display_name
        extract($this->minerva_config);
        
        
        // Default options for pagination, merge with URL parameters
        $defaults = array('page' => 1, 'limit' => 10, 'order' => 'created.desc');
        $params = Set::merge($defaults, $this->request->params);
        
        if((isset($params['page'])) && ($params['page'] == 0)) {
            $params['page'] = 1;
        }
        list($limit, $page, $order) = array($params['limit'], $params['page'], $params['order']);
        
        /*
        if(isset($params['args'])) {
            foreach($params['args'] as $arg) {
                $arg_pieces = explode(':', $arg);
                if(count($arg_pieces) > 1) {
                    switch(strtolower($arg_pieces[0])) {
                        case 'page':
                            $page = $arg_pieces[1];
                            break;
                        case 'limit':
                            $limit = $arg_pieces[1];
                            break;
                        case 'order':
                            $order = $arg_pieces[1];
                            break;
                    }
                }
            }
        }
        */
        
        // never allow a limit of 0
        $limit = ($limit < 0) ? 1:$limit;
        
        // If there's a page/user/block_type passed, add it to the conditions, 'index' will show all pages and comes from routing. we allow /minerva/pages/index for example. though /minerva/pages works as does /minerva/pages/page:1/limit:1
        if(!empty($document_type) && $document_type != 'index') {
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
        $documents = $this->getDocument(array(
            'action' => $this->calling_method,
            'request' => $this->request,
            'find_type' => 'all',
            'conditions' => $conditions,
            'limit' => (int)$limit,
            'offset' => ((int)$page - 1) * (int)$limit,
            'order' => $params['order']
        ));
        
        // TODO: is there a better way to do this??? 
        // Set the user names (a "JOIN")
        if(is_object($documents)) {
            $owner_ids = array();
            foreach($documents as $document) {
                $owner_ids[] = $document->owner_id;
            }
            
            // Make ONE query PER find() for the index listing
            if(!empty($owner_ids)) {
                $owner_documents = User::find('all', array('conditions' => array('_id' => $owner_ids)));
                $owners = array();
                if(is_object($owner_documents)) {
                    foreach($owner_documents as $user) {
                        $user->_name = User::get_name((string)$user->_id);
                        $owners[(string)$user->_id] = $user->data();
                    }
                }
            }
            
            // Set the owners
            foreach($documents as $document) {
                $document->_owner = $owners[(string)$document->owner_id];
            }
        }
        ////////// END "JOIN" process for owners (user model)
        
        
        // Get some handy numbers
        /*$total = $ModelClass::find('count', array(
            'conditions' => $conditions
        ));*/
        // Get some handy numbers
        $total = $this->getDocument(array(
            'action' => $this->calling_method,
            'request' => $this->request,
            'find_type' => 'count',
            'conditions' => $conditions
        ));
        
        $page_number = (int)$page;
        $total_pages = ((int)$limit > 0) ? ceil($total / $limit):0;
        
        // Set data for the view template
        $this->set(compact('documents', 'limit', 'display_name', 'page_number', 'total_pages', 'total'));
    }
    
    /**
     * Generic create() action.
     * The trick here is that $this->calling_class and $this->calling_method will hold a string
     * reference for which extended class called this create() method. We need that in order to
     * get the proper records and access.
    */
    public function create($document_type=null) {
        // first, get all the data we need. this will set $document_type, $type, $modelClass, and $display_name
        extract($this->minerva_config);
        
        $this->getDocument(array('action' => $this->calling_method, 'conditions' => array('document_type' => $document_type), 'request' => $this->request, 'find_type' => false));
        // get the redirects; important to call this AFTER $this->getDocument() because the proper $ModelClass will be set (it will have changed based on the document from the database)
        $action_redirects = $this->getRedirects();
        
        // Get the fields so the view template can iterate through them and build the form
        $fields = $ModelClass::schema();
        
        // Don't need to have these fields in the form
        unset($fields['_id']);
        
        // If data was passed, set some more data and save
        if ($this->request->data) {
            // We need to get the validation rules unfortunately because they may need to change based on what's going on
            $validation_rules = $ModelClass::validation_rules();
            
            $document = $ModelClass::create();
            $now = date('Y-m-d h:i:s');
            $this->request->data['created'] = $now;
            $this->request->data['modified'] = $now;
            // If a page type was passed in the params, we'll need it to save to the page document.
            $this->request->data['document_type'] = $document_type;
            
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
            
            // Last check for the URL...if it's empty for some reason set it to "document"
            if(empty($url)) {
                $url = 'document';
            }
            
            // Then get a unique URL from the desired URL (numbers will be appended if URL is duplicate) this also ensures the URLs are lowercase
            $this->request->data['url'] = Util::unique_url(array(
                'url' => $url,
                'model' => $ModelClass
            ));
            
            // Set the owner
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
                
                // We need to remove some validation rules if this user is a Facebook user
                if(isset($this->request->data['facebook_uid']) && !empty($this->request->data['facebook_uid']) && is_numeric($this->request->data['facebook_uid'])) {
                    unset($validation_rules['email']);
                    unset($validation_rules['password']);
                }
            }
            
            // Save
            if($document->save($this->request->data, array('validate' => $validation_rules))) {
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
    public function update($url=null) {
        
        $conditions = array('url' => $url);
        
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
            $fields['new_password'] = array('type' => 'string', 'form' => array('label' => 'New Password', 'type' => 'password', 'autocomplete' => 'off'));
        }
		// If a document_type isn't empty, set it in the form
		$fields['document_type']['form']['value'] = (!empty($document_type)) ? $document_type:null;
        
        // Update the record
		if ($this->request->data) {
			// We need to get the validation rules unfortunately because they may need to change based on what's going on
            $validation_rules = $ModelClass::validation_rules();
            
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
                
                // We need to remove some validation rules if this user is a Facebook user
                if(isset($this->request->data['facebook_uid']) && !empty($this->request->data['facebook_uid']) && is_numeric($this->request->data['facebook_uid'])) {
                    unset($validation_rules['email']);
                    unset($validation_rules['password']);
                }
            }
			
            // Save it
			if($document->save($this->request->data, array('validate' => $validation_rules))) {
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