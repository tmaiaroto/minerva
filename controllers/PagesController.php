<?php
/**
 * There's a few changes here from a default Lithium pages controller. First the Router has been changed for the view method. 
 * Second, Pages now uses a model and can connect to a datasource, this is simply for organization and convention.
 * Minerva aims to use terminology that most people can relate to (that would include non-programmers too).
 * Therefore, a "page" in Minerva is exactly what one would expect a "web page" to be. To a developer, that could mean it accesses 
 * a database and it could just mean that it displays a static file with php/html/css/js code within it served from the disk.
 * To a non-developer, it simply means a page with content on it. Who knows where it came from? Who cares? It's there!
 * 
 * The Page model still does not need a database connection to work for viewing static pages, but other controller methods may.
 * So some methods within this controller require/use a database. The "view" method, however, does not. It remains, roughly,
 * the same as it does out of the box with Lithium. The major change being all "static" files are organized into a new "static"
 * folder instead. This helps to keep the static view templates separate from the dynamic view templates for pages.
 *
 * The naming convention of "index', "add", "edit" and "delete" are changed a little to closer represent the acronym CRUD.
 * The methods are "index", "create", "read", "update", and "delete". This distinguishes the "view" from the "read" method. 
 *
 */
namespace minerva\controllers;
use minerva\models\Page;
use \lithium\util\Set;
use li3_flash_message\extensions\storage\FlashMessage;
use li3_access\security\Access;
use \lithium\security\Auth;
use minerva\libraries\util\Util;
use lithium\util\Inflector;

class PagesController extends \lithium\action\Controller {
    
    /*
     * Rules used by Access::check() at the Dispatcher level.
     * The rules set here will be passed the Request object, but since
     * called at the Dispatcher level, document level access control isn't possible.
     * See the $document_access property below... All rules requiring document data
     * should be defined there.
     *
     * By default we're restricting everything to managers.
     * This leaves the core PagesController to administrative purposes.
     * The "public" library will hold basic pages for anonymous visitors.
     * 
    */
    static $access = array(
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
	),
	'view' => array(
	    array('rule' => 'allowAll', 'redirect' => '/users/login')
	)
    );
    
    /*
     * Access::check() can be called in the controller as well.
     * The reason Minerva calls it in the Dispatcher (with rules from $access)
     * is so that on a broader level, users can be redirected before the
     * controller executes and, better yet, before a query to the database is made.
     *
     * However, if access can only be determined by the data within a document
     * from the database, then the Access::check() must be called at the
     * controller level. So a second property, $document_access, will set the
     * rules that get checked against at that point.
     *
     * Note, two checks are called for this performance reason, but also so
     * 3rd party libraries can utilize the filter on the Dispatcher.
     *
     * 3rd party libraries (using this PagesController) can set the $document_access
     * property in their Page model (which gets merged to this one here) so they
     * can also control access based on document conditions.
     * 
     * The filter on the Dispatcher makes it very easy for all libraries
     * to use the Access class just by setting an $access property.
     * It could increase development speed and it provides some sort of
     * consistency with Access checks.
     * 
     * If the 3rd party library needs greater control, Access::check() calls
     * can be made at some other point or even this convention can be used.
     * The reason there are properties is so that libraries with a Page model
     * can control these rules. The /libaries/yourlibrary/models/Page.php file
     * can simply set its own $document_access which will overwrite this one.
     * That way the core PagesController here doesn't have to be modified.
     * See the Minerva bootstrap process for more information.
    */
    static $document_access = array(
	array('rule' => 'publishStatus', 'message' => 'You are not allowed to see unpublished content.', 'redirect' => '/')
    );
    
    /**
     * The default method here is changed. First off, the Router class now uses this view method if the URL is /page/{:args}
     * It changes the URL convention from pluralized controller, but since we're talking about static pages, I felt that was ok.
     * Especially since URLs are for humans first and foremost.
     * "/pages/view/home" still works if needed to be used in array fashion like the Html helper's link method.
     * This leaves us in need of a new method though that returns dynamic pages from a datasource. That's the "read" method below.
     *
    */
    public function view() {
	$path = func_get_args();
	
	// If route has the "admin" key set to true then render template from Minerva's views/pages/static folder
	if((isset($this->request->params['admin'])) && ($this->request->params['admin'] === true)) {
	    // todo: make rule and check access class
	    $user = Auth::check('minerva_user');
	    if(!in_array($user['role'], array('administrator', 'content_editor'))) {
		$this->redirect('/users/login');
	    }
	    
	    if (empty($path)) {
		$path = array('static', 'home');
	    } else {
		array_unshift($path, 'static');
	    }
	} else {
	    if (empty($path)) {
		$path = array('home');
	    }
	}
	$this->render(array('template' => join('/', $path)));
    }	
    
    /**
     * Index listing method responsible for showing lists of pages with pagination options.
     * If a "page_type" param (a library) is passed from the routing and the library has a Page model, it will be instantiated.
     * Additional filters can be applied there that further control things.
    */
    public function index() {
	// Default options for pagination, merge with URL parameters
	$defaults = array('page' => 1, 'limit' => 10, 'order' => 'created.desc');
	$params = Set::merge($defaults, $this->request->params);
	if((isset($params['page'])) && ($params['page'] == 0)) {
	    $params['page'] = 1;
	}
	list($limit, $page, $order) = array($params['limit'], $params['page'], $params['order']);
	
	// If there's a page_type passed, add it to the conditions, 'all' will show all pages.
	// TODO: OBVIOUSLY add an index to "page_type" field (also url for other actions' needs, not this one)
	if((isset($this->request->params['page_type'])) && (strtolower($this->request->params['page_type']) != 'all')) {
	    $conditions = array('page_type' => $this->request->params['page_type']);
	} else {
	    $conditions = array();
	}
	
	// If a search query was provided, search all "searchable" fields (any model schema field that has a "search" key on it)
	// NOTE: the values within this array for "search" include things like "weight" etc. and are not yet fully implemented...But will become more robust and useful.
	// Possible integration with Solr/Lucene, etc.
	$page_type = (isset($this->request->params['page_type'])) ? $this->request->params['page_type']:'all';
	if((isset($this->request->query['q'])) && (!empty($this->request->query['q']))) {
	    $schema = Page::schema();
	    // If the "page_type" is set to "all" then we want to get all the page type's schemas, merge them into $schema
	    if($page_type == 'all') {
		foreach(Util::list_types('Page', array('exclude_minerva' => true)) as $library) {
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
	    $documents = Page::find('all', array(
		'request_params' => $this->request->params,
		'conditions' => $conditions,
		'limit' => (int)$params['limit'],
		'offset' => ($params['page'] - 1) * $limit, // TODO: "offset" becomes "page" soon or already in some branch...
		'order' => Util::format_dot_order($params['order'])
	    ));
	}
	// Get some handy numbers
	$total = Page::find('count', array(
		'conditions' => $conditions
	));
	$page_number = $params['page'];
	$total_pages = ((int)$params['limit'] > 0) ? ceil($total / $params['limit']):0;
	
	// Set data for the view template
	$this->set(compact('documents', 'limit', 'page_number', 'total_pages', 'total'));
    }

    /**
     * Create a page.
     *
     * The "page_type" decides which library to use when creating the page (optional).
     * Again, the "page_type" name string value has to be passed in as a request param (easily set in the routes).
     * A library can change the fields displayed in the form so that different data can be saved to the page among other things.
     * The page type library doesn't touch this controller, but can alter a few things within it by having a Page model.
    */
    public function create() {
	// Get the name for the page, so if another page type library uses the "admin" (core) templates for this action, it will be shown
	$display_name = Page::display_name();
	
	// Get the fields so the view template can iterate through them and build the form
	$fields = Page::schema();
	// Don't need to have these fields in the form
	unset($fields[Page::key()]);
	// If a page type was passed in the params, we'll need it to save to the page document.
	$fields['page_type']['form']['value'] = (isset($this->request->params['page_type'])) ? $this->request->params['page_type']:null;
	
	// Save
	if ($this->request->data) {
	    $page = Page::create();
	    
	    $now = date('Y-m-d h:i:s');
	    $this->request->data['created'] = $now;
	    $this->request->data['modified'] = $now;
	    $this->request->data['url'] = Util::unique_url(array(
		'url' => Inflector::slug($this->request->data['title']),
		'model' => 'minerva\models\Page'
	    ));
	    $user = Auth::check('minerva_user');
	    if($user) {
		$this->request->data['owner_id'] = $user['_id'];
	    } else {
		// TODO: possible for anonymous users to create things? do we need to put in any value here?
		$this->request->data['owner_id'] = '';
	    }
	    
	    if($page->save($this->request->data)) {
		FlashMessage::set('The content has been created successfully.', array('options' => array('type' => 'success', 'pnotify_title' => 'Success', 'pnotify_opacity' => .8)));
		$this->redirect(array('controller' => 'pages', 'action' => 'index'));
	    } else {
		FlashMessage::set('The content could not be saved, please try again.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
	    }
	}
	
	if(empty($page)) {                
	    $page = Page::create(); // Create an empty page object
	}
	
	$this->set(compact('page', 'fields', 'display_name'));
    }
    
    /**
     * Update a page.
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
	
	// Get the name for the page, so if another page type library uses the "admin" (core) templates for this action, it will be shown
	$display_name = Page::display_name();
	
	// First, get the record
	$page = Page::find('first', array('conditions' => $conditions));
	
	// Get the fields so the view template can build the form
	$fields = Page::schema();                
	// Don't need to have these fields in the form
	unset($fields[Page::key()]);
	// If a page type was passed in the params, we'll need it to save to the page document.
	$fields['page_type']['form']['value'] = (isset($this->request->params['page_type'])) ? $this->request->params['page_type']:$page->page_type;
	
	
	// Update the record
	if ($this->request->data) {
	    $now = date('Y-m-d h:i:s');
	    $this->request->data['modified'] = $now;
	    $this->request->data['url'] = Util::unique_url(array(
		'url' => Inflector::slug($this->request->data['title']),
		'model' => 'minerva\models\Page',
		'id' => $page->_id
	    ));
	    
	    // Call save from the main app's Page model
	    if($page->save($this->request->data)) {
		FlashMessage::set('The content has been updated successfully.', array('options' => array('type' => 'success', 'pnotify_title' => 'Success', 'pnotify_opacity' => .8)));
		$this->redirect(array('controller' => 'pages', 'action' => 'index'));
	    } else {
		FlashMessage::set('The content could not be updated, please try again.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
	    }
	}
	
	$this->set(compact('page', 'fields', 'display_name'));
    }

    /**
     * Read a page (like "view()" but retrieves page data from the database).
     * Also, like other methods, extra data is bridged in from an optional associated page type library on the record itself.
    */
    public function read($url=null) {
	// We can get the URL from the named parameter or from the arg passed
	if((isset($this->request->params['url'])) && (empty($url))) {
	    $url = $this->request->params['url'];
	}
	$document = Page::find('first', array('conditions' => array('url' => $url), 'request_params' => $this->request->params));
	if(!$document) {
	    FlashMessage::set('Page not found.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => '.8')));
	    $this->redirect(array('controller' => 'pages', 'action' => 'index'));
	}

	// Add a base access rule to check against
	Access::adapter('minerva_access')->add('publishStatus', function($user, $request, $options) {
	    if($options['document']['published'] === true) {
		return true;
	    }
	    if(($user) && ($user['role'] == 'administrator' || $user['role'] == 'content_editor')) {
		return true;
	    }
	    return false;
	});
	
	$rules = static::$document_access;
	// Add the document data to each rule so it can be checked
	$i=0;
	foreach($rules as $rule) {
	    $rules[$i]['document'] = $document->data();
	    $i++;
	}
	$access = Access::check('minerva_access', Auth::check('minerva_user'), $this->request, array('rules' => $rules));
	if(!empty($access)) {
	    FlashMessage::set($access['message'], array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => '.8')));
	    if((isset($document->page_type)) && (!empty($document->page_type))) {
		$this->redirect(array('controller' => 'pages', 'action' => 'index', 'page_type' => $document->page_type));
	    } else {
		$this->redirect(array('controller' => 'pages', 'action' => 'index'));
	    }
	}
	
	$this->set(compact('document'));
    }
    
    /** 
     *  Delete a page document.
     *  Plugins can apply filters within their Page model class in order to run filters for the delete.
     *  Useful for "clean up" tasks such as removing image files from the server if the plugin was a gallery for example.
    */
    public function delete() {
	if(!isset($this->request->params['url'])) {
	    $this->redirect(array('controller' => 'pages', 'action' => 'index'));
	}
	
	$document = Page::findByUrl($this->request->params['url']);
	
	if($document->delete()) {
	    FlashMessage::set('The content has been deleted.', array('options' => array('type' => 'success', 'pnotify_title' => 'Success', 'pnotify_opacity' => .8)));
	    $this->redirect(array('controller' => 'pages', 'action' => 'index'));
	} else {
	    FlashMessage::set('The content could not be deleted, please try again.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
	    $this->redirect(array('controller' => 'pages', 'action' => 'index'));
	}
    }
    
}
?>