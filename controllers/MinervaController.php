<?php
namespace minerva\controllers;

use \lithium\core\Libraries;
use \lithium\storage\Session;
use li3_access\security\Access;
use \lithium\security\Auth;
use \lithium\security\Password;
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
        
        // The only time I can think that there wouldn't be $this->request is from requestAction() on the Block helper.
        // TODO: Ensure that all this is not required by that helper's method.
        // For now this if() will prevent some errors.
        if(!empty($this->request)) {
            $controller_pieces = explode('.', $this->request->params['controller']);
            $this->minerva_config['relative_controller'] = $relative_controller = (count($controller_pieces) > 1) ? $controller_pieces[1]:$controller_pieces[0];
            $this->minerva_config['model'] = $model = Inflector::classify(Inflector::singularize($relative_controller));
            // set the model class, should be minerva\models\Page or minerva\models\Block etc.
            $ModelClass = 'minerva\models\\'.$model;
            // in case it doesn't exist, use the base MinervaModel which we know does exist
            $ModelClass = (class_exists($ModelClass)) ? $ModelClass:'minerva\models\MinervaModel';
            $document_type = '';
            
            $plugin = (isset($this->request->params['plugin']) && !empty($this->request->params['plugin'])) ? $this->request->params['plugin']:false;
                        
            // set the ModelClass again now based on the $document_type, which in most cases, matches the library (minerva plugin) name
            // ignore and empty $document_type, that just means the base model class anyway
            if($plugin) {
                $ModelClass = $ModelClass::getMinervaModel($model, $plugin);
                // $document_type is ALWAYS the name of the plugin (library folder) because it's always unique.
                // Can't have multiple plugins...This means library directory names for plugins likely should
                // not be something generic, like "blog" ... "minerva_blog" is better. Or "shift8creative_com_blog"
                $document_type = $plugin;
            }
            
            // Now the following will use the proper ModelClass to get the properties that we need in the controller.
            $this->minerva_config['ModelClass'] = $ModelClass;
            $this->minerva_config['display_name'] = $ModelClass::displayName();
            $this->minerva_config['library_name'] = $ModelClass::libraryName();
            $this->minerva_config['document_type'] = $document_type;
            $this->minerva_config['admin'] = (isset($this->request->params['admin'])) ? $this->request->params['admin']:false;
            
            // Also good to set the redirect url here for after logging in (last requested URL)
            // ...but we don't want to set it to any of the following
            // TODO: should this be removed?
            if(isset($this->request->params['action'])) {
                $controller_action_whitelist = array(
                    'users.login',
                    'users.logout',
                    'users.register',
                    'users.is_email_in_use',
                    'users.register',
                );
                if(!in_array($relative_controller . '.' . $this->request->params['action'], $controller_action_whitelist)) {
                    Session::write('beforeAuthURL', '/' . $this->request->url, array('name' => 'minerva_cookie', 'expires' => '+1 hour'));
                }
            }
            
        }
        
        // Get the access rules all out of the way
        $this->minerva_config['access'] = $ModelClass::accessRules();
        
        // Get some data from Minerva's library configuration, such as if core minerva_access has been disabled
        $library_config = Libraries::get('minerva');
        $this->minerva_config['use_minerva_access'] = (isset($library_cofing['use_minerva_access']) && is_boolean($library_config['use_minerva_access'])) ? $library_config['use_minerva_access']:true;
        
        parent::_init();
    }
    
    /**
     * Returns the configuration for Minerva based on the current request.
     * This holds valuable information for a controller to use.
     * 
     * @retun array The current configuration
     */
    public function getMinervaConfig() {
        return $this->minerva_config;
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
        $admin = (isset($this->request->params['admin'])) ? $this->request->params['admin']:false;
        $default_redirects = array(
            'create' => array('admin' => $admin, 'library' => 'minerva', 'controller' => $this->request->params['controller'], 'action' => 'index'),
            'update' => array('admin' => $admin, 'library' => 'minerva', 'controller' => $this->request->params['controller'], 'action' => 'index'),
            'delete' => array('admin' => $admin, 'library' => 'minerva', 'controller' => $this->request->params['controller'], 'action' => 'index'),
            'logout' => '/',
            'register' => array('library' => 'minerva', 'controller' => 'users', 'action' => 'login')
        );
        $ModelClass = $this->minerva_config['ModelClass'];
        $redirects = $ModelClass::actionRedirects();
        $redirects += $default_redirects;
        
        // loop through, look for special redirect values
        if(count($redirects) > 0) {
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
        }
        
        return $redirects;
    }
    
    /**
     * Gets document(s) for the action and checks access at the same time.
     * 1. The proper model is determined so it can be used for the find() and also to pick up any access rules,
     *    apply filters, etc.
     * 2. The action access is checked (if the method was called merely to check access, it could return true at this point)
     * 3. The find() is made from the proper model (filterable on that model)
     * 4. The document access is checked
     * 5. The document(s) is returned
     *
     * This would be typically called from the core Minerva controllers (Pages, Users, and Blocks for example).
     * The purpose of this method is to reduce duplicate code and any unnecessary database queries.
     * Secondarily, it's a convenient method to bundle all of these processes into one method.
     * 
     * @param $action string The calling controller action (should be passed as __METHOD__ so the controller is also passed, ex. minerva\controllers\PagesController::read)
     * @param $request object The request object
     * @param $find_type string The find type (all, first, etc. if false then no find will be performed) default: false
     * @param $conditions array The find conditions
     * @param $limit int The limit (for pagination)
     * @param $offset int The offset (for pagination)
     * @param $order array The order
     * @return mixed The find() results, true, or false (also redirects on access restrictions)
    */
    public function getDocument($options=array()) {
        $defaults = array('action' => null, 'request' => null, 'find_type' => false, 'conditions' => array(), 'limit' => null, 'offset' => 0, 'order' => 'created.desc');
        $options += $defaults;
        extract($options);
        
        // Defaults: $action=null, $request=null, $find_type='first', $conditions=array(), $limit=null, $offset=0, $order=null
        // NOTE: minerva_config holds a lot of important information that helps us figure out from where a method was called, which is important with all the class extensions
        // 1. Determine the proper model to be using by looking at $this->minerva_config set by init()
        extract($this->minerva_config);
        
        // if $request was not passed from the calling controller, use $this->request
        $request = (isset($request)) ? $request:$this->request;
        // an admin flag could be passed in the actual request (from a route) or the controller could have passed a different request with the admin flag in it
        $admin = (isset($request->params['admin'])) ? true:false;
        
        // if the action is read, update, or delete then the document will be able to tell us the library name - otherwise it's going to be "minerva"
        // all core Minerva model documents stored in the database have a field "document_type" which references the 3rd party library name when the 3rd party extends the core model
        if(($request->params['action'] == 'read') || ($request->params['action'] == 'update') || ($request->params['action'] == 'delete')) {
            
            $record = $ModelClass::first(array('request_params' => $request->params, 'conditions' => $conditions, 'fields' => array('document_type')));
            
            // it should be an object...if it wasn't found that's a problem 
            if(is_object($record)) {
                if($record->document_type) {
                    // set the document_type to that of the document's from the database (still technically could be empty, that's ok)
                    $this->minerva_config['document_type'] = $record->document_type;
                    
                    // the document_type is always the name of the library (known as a "Minerva plugin")
                    // and this library's model is the class we will use because is has adjusted schema, 
                    // validation, and other properties (and possibly filters)
                    $MinervaModelClass = $ModelClass::getMinervaModel($model, $record->document_type);
                    if(is_string($MinervaModelClass) && class_exists($MinervaModelClass)) {
                        $this->minerva_config['ModelClass'] = $ModelClass = $MinervaModelClass;
                    }
                    
                    // finaly, we can set the proper library_name (and the proper $ModelClass will be set now too)
                    $this->minerva_config['library_name'] = $ModelClass::libraryName();
                    // also, set the display name in case the plugin wants to show users a different name 
                    $this->minerva_config['display_name'] = $ModelClass::displayName();
                }
            }
        }
        
        // 2. Authentication & Access Check
        // It could be disabled completely by Minerva's library configuration, so first let's check.
        if($this->minerva_config['use_minerva_access']) {
            $rules =  $this->minerva_config['access'];

            // There's going to be two sets of action access rules. One for non-admin actions and one for admin actions.
            // If there's no 'action' or 'admin_action' keys for access, then allow access.
            // We are being loose by default. This allows other access systems to be put into place to override this.
            $action_access = array();

            // Check for non admin actions (admin flagged false or not set)
            if($admin === false) {
                if(isset($rules[$request->params['action']]['action']) && !empty($rules[$request->params['action']]['action'])) {
                    $action_access = Access::check('minerva_access', Auth::check('minerva_user'), $request, array('rules' => $rules[$request->params['action']]['action']));
                }
            }
            // Check for admin actions
            if($admin === true) {
                if(isset($rules[$request->params['action']]['admin_action']) && !empty($rules[$request->params['action']]['admin_action'])) {
                    $action_access = Access::check('minerva_access', Auth::check('minerva_user'), $request, array('rules' => $rules[$request->params['action']]['admin_action']));
                }
            }
            
            // This method only redirects when access is restricted, but still returns false to prevent any further code execution.
            if(!empty($action_access)) {
                FlashMessage::write($action_access['message'], array(), 'minerva_admin');
                $this->redirect($action_access['redirect']);
                return false;
            }
        }
        
        // 3. Get Document(s)
        // If we're here, we now need to get the document(s) to determine any document based access
        // and we'll return the document(s) back to the controller too.
        
        // Before getting documents, make sure the calling method actually wanted to get documents.
        // If not, we return true. Basically what this method was used for at this point is an access 
        // check since no document is being returned.
        if($find_type === false) {
            return true;
        }
        
        // NOTE: the request_params are not used for Lithium's find() method,
        // but provides some useful contextual information if find() is filtered.
        $options = array(
            'conditions' => $conditions,
            'limit' => $limit,
            'order' => Util::formatDotOrder($order),
            'request_params' => $request->params
        );
        if($offset > 0) {
            $options += array('offset' => $offset);
        }
        $document = $ModelClass::find($find_type, $options);
        //var_dump($document);
        
        // 4. Document Access Control 
        if($document && is_object($document)) {
            // Again, core Minerva access control can be completely disabled by configuration.
            if($this->minerva_config['use_minerva_access']) {
                $document_access = array();
                if(isset($rules[$request->params['action']]['document']) && !empty($rules[$request->params['action']]['document'])) {
                    // Add the document to the document access rules so it can be passed to be checked against
                    // Document access rules can be one or many. So look how the array of rules is formatted, it may need to be looped over.
                    if(isset($rules[$request->params['action']]['document'][0])) {
                        foreach($rules[$request->params['action']]['document'] as $k => $v) {
                            $rules[$request->params['action']]['document'][$k]['document'] = $document->data();
                        }
                    } else {
                        $rules[$request->params['action']]['document']['document'] = $document->data();
                    }
                    $document_access = Access::check('minerva_access', Auth::check('minerva_user'), $request, array('rules' => $rules[$request->params['action']]['document']));
                }

                // Again, redirect and return false if access is denied.
                if(!empty($document_access)) {
                    FlashMessage::write($document_access['message'], array(), 'minerva_admin');
                    $this->redirect($document_access['redirect']);
                    return false;
                }
            }
        } else {
            $document = false;
        }
        
        // 5. Return the document or false (false if the document was not found or something went wrong)
        return $document;
    }
    
    /**
     * Generic index() action that returns a list of paginated documents.
     * The trick here is that $this->calling_class and $this->calling_method will hold a string
     * reference for which extended class called this index() method. We need that in order to
     * get the proper records and access.
    */
    public function index($document_type=null) {
        // first, get all the data we need. this will set $document_type, $type, $modelClass, and $display_name
        extract($this->minerva_config);
        
        // Default options for pagination, merge with URL parameters
        $defaults = array('page' => 1, 'limit' => 10, 'order' => 'created.desc');
        $params = Set::merge($defaults, $this->request->params);
        
        if((isset($params['page'])) && ($params['page'] == 0)) {
            $params['page'] = 1;
        }
        list($limit, $page, $order) = array($params['limit'], $params['page'], $params['order']);
        
        // never allow a limit of 0
        $limit = ($limit < 0) ? 1:$limit;
        
        // If there's a document_type passed, add it to the conditions, 'index' will show all pages and comes from routing. we allow /minerva/pages/index for example. though /minerva/pages works as does /minerva/pages/page:1/limit:1
        if(!empty($document_type) && $document_type != 'index') {
            $conditions = array('document_type' => $document_type);
        } else {
            $conditions = array();
        }
        
        // If a search query was provided, search all "searchable" fields (any field in the model's $search_schema property)
        // NOTE: the values within this array for "search" include things like "weight" etc. and are not yet fully implemented...But will become more robust and useful.
        // Possible integration with Solr/Lucene, etc.
        if((isset($this->request->query['q'])) && (!empty($this->request->query['q']))) {
            $search_schema = $ModelClass::searchSchema();
            // If the "document_type" is set to "*" then we want to get all the model content_type's schemas, merge them into $schema
            if($document_type == '*') {
                foreach(Util::listTypes($model, array('exclude_minerva' => true)) as $library) {
                    $extendedModelClass = 'minerva\libraries\\' . $library;
                    $search_schema += $extendedModelClass::searchSchema();
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
                // TODO: check this. i don't think its possible to not have an owner....but...
                $owner_id = (string)$document->owner_id;
                $document->_owner = (isset($owners[$owner_id])) ? $owners[$owner_id]:'';
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
            $validation_rules = $ModelClass::validationRules();
            
            $document = $ModelClass::create();
            $now = date('Y-m-d h:i:s');
            $this->request->data['created'] = $now;
            $this->request->data['modified'] = $now;
            // If a page type was passed in the params, we'll need it to save to the page document.
            $this->request->data['document_type'] = $document_type;
            
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
            
            // Last check for the URL...if it's empty for some reason set it to "document"
            if(empty($url)) {
                $url = 'document';
            }
            
            // Then get a unique URL from the desired URL (numbers will be appended if URL is duplicate) this also ensures the URLs are lowercase
            $this->request->data['url'] = Util::uniqueUrl(array(
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
                $this->request->data['password'] = Password::hash($this->request->data['password']);
                
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
            $validation_rules = $ModelClass::validationRules();
            
            // Set some data
            $this->request->data['modified'] = date('Y-m-d h:i:s');
            
            // (note: the password stuff is only useful for UsersController)
            if($this->request->params['controller'] == 'users') {
                if(isset($this->request->data['password'])) {
                    unset($this->request->data['password']);
                }
                if((isset($this->request->data['new_password'])) && (!empty($this->request->data['new_password']))) {
                    $this->request->data['password'] = Password::hash($this->request->data['new_password']);
                    unset($this->request->data['new_password']);
                }
                
                // unset the e-mail if unchanged, it will trip validation otherwise
                if(isset($this->request->data['email']) && $this->request->data['email'] == $document->email) {
                    unset($this->request->data['email']);
                    unset($validation_rules['email']);
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
        
        if(!empty($document)) {
            if($document->delete()) {
                FlashMessage::write('The content has been deleted.', array(), 'minerva_admin');
            } else {
                FlashMessage::write('The content could not be deleted, please try again.', array(), 'minerva_admin');
            }
            $this->redirect($action_redirects['delete']);
        }
    }
    
}
?>