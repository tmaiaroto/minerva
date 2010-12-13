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
//use minerva\util\Access;

class PagesController extends \lithium\action\Controller {
    
    /*
     * Rules used by Access::check() at the Dispatcher level.
     * The rules set here will be passed the Request object, but since
     * called at the Dispatcher level, document level access control isn't possible.
     * See the $document_access property below... All rules requiring document data
     * should be defined there.
    */
    static $access = array(
		// 'login_redirect' could be 'http://www.google.com' for all the system cares, it'll go there
		'create' => array(),
		'view' => array('rule' => 'allowAll'),
		
		'read' => array(
		    array('rule' => 'allowAll', 'message' => 'allow all message'),
		    //array('rule' => 'someotherrule')
		)
		
		//'read' => array('rule' => 'allowAll', 'message' => 'allow all message')
		
		// * is a shortcut. all other method name keys here will be ignored, the login_redirect by default is "/" so if using this on PagesController, it has to redirect somewhere else because "/" is the view method.
		// '*' => array('rule' => 'denyAll', 'login_redirect' => '/users/login')	
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
    static $document_access = array();
    
    
    /**
     * The default method here is changed. First off, the Router class now uses this view method if the URL is /page/{:args}
     * It changes the URL convention from pluralized controller, but since we're talking about static pages, I felt that was ok.
     * Especially since URLs are for humans first and foremost.
     * "/pages/view/home" still works if needed to be used in array fashion like the Html helper's link method.
     * This leaves us in need of a new method though that returns dynamic pages from a datasource. That's the "read" method below.
     *
    */
    public function view() {
	if (empty($path)) {
	    $path = array('static', 'home');
	} else {
	    $path = array('static', func_get_args());
	}
	$this->render(array('template' => join('/', $path)));
    }	
    
    /**
     * Index listing method responsible for showing lists of pages with pagination options.
     * If a library param is passed from the routing and the library has a Page model, it will be instantiated.
     * Additional filters can be applied there that further control things.
     * 
    */
    public function index() {	
	// Default options for pagination, merge with URL parameters
	$defaults = array('page' => 1, 'limit' => 10, 'order' => array('descending' => 'true'));
	$params = Set::merge($defaults, $this->request->params);
	if((isset($params['page'])) && ($params['page'] == 0)) {
	    $params['page'] = 1;
	}
	list($limit, $page, $order) = array($params['limit'], $params['page'], $params['order']);
	
	// If there's a library passed, add it to the conditions.
	// TODO: OBVIOUSLY add an index to library field (also url for other actions' needs, not this one)
	if(isset($this->request->params['library'])) {
	    $conditions = array('library' => $this->request->params['library']);
	} else {
	    $conditions = array();
	}
	
	$records = Page::find('all', array(
	    'conditions' => $conditions,
	    'limit' => $params['limit'],
	    'offset' => ($params['page'] - 1) * $params['limit'], // TODO: "offset" becomes "page" soon or already in some branch...
	    //'order' => $params['order']
	    'order' => array('_id' => 'asc')
	));	
	$total = Page::count();
	
	$this->set(compact('records', 'limit', 'page', 'total'));
    }

    /**
     * Create a page.
     *
     * The "library" decides which library to use when creating the page (optional).
     * Again, the "library" name string value has to be passed in as a request param (easily set in the routes).
     * A library can change the fields displayed in the form so that different data can be saved to the page among other things.
     * 
     * A "library" (or plugin) can be thought of like a "content type" in Drupal, but much more too. It's on steroids.
     * Even more insane, we can bundle these as phars so that distribution even easier. 
     * Since it's so modular and transportable...an online registry of libraries can be created so the CMS can browse
     * and download at will additional libraries that will extend the CMS.
     *
    */
    public function create() {	
	// Get the fields so the view template can iterate through them and build the form
	$fields = Page::schema();
	// Don't need to have these fields in the form
	unset($fields[Page::key()]);
	
	// Save
	if ($this->request->data) {
	    $this->request->data['library'] = $library; // Set the library to be saved with the record, saving null is ok too
	    
	    $page = Page::create();
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
	
	$this->set(compact('page', 'fields'));
    }
    
    /**
     * Update a page.
     * Unlike index() and create(), this action deals with a record. The record itself will contain the
     * library value. An additional query is made first to get this value from the record and then the
     * "Page" model class will be instantiated. In other words, there doesn't need to be a route setup
     * that passes the "library" param.
    */
    public function update($url=null) {
	// First, get the record
	$record = Page::find('first', array('conditions' => array('url' => $url)));
	
	// Get the fields so the view template can build the form
	$fields = Page::schema();                
	
	// Update the record
	if ($this->request->data) {
	    // Call save from the main app's Page model
	    if($record->save($this->request->data)) {
		FlashMessage::set('The content has been updated successfully.', array('options' => array('type' => 'success', 'pnotify_title' => 'Success', 'pnotify_opacity' => .8)));
		$this->redirect(array('controller' => 'pages', 'action' => 'index'));
	    } else {
		FlashMessage::set('The content could not be updated, please try again.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
	    }
	}
	
	$this->set(compact('record', 'fields'));
    }

    /**
     * Read a page (like "view()" but retrieves page data from the database).
     * Also, like other methods, extra data is bridged in from an optional associated library on the record itself.
     *
    */
    public function read($url=null) {
	// We can get the URL from the named parameter or from the arg passed
	if((isset($this->request->params['url'])) && (empty($url))) {
	    $url = $this->request->params['url'];
	}
	// get the page document (also within this record contains the library used, which is important)
	$record = Page::find('first', array('conditions' => array('url' => $url)));
	
	// example
	/*Access::add('denyForFirstBlogPost', function($user, $request, $options) {
	    if($options['document']['url'] == 'First-Blog-Entry') {
		return false;
	    }
	    return true;
	});
	Access::add('denyForSunday', function($user, $request, $options) {
	    return (date('w') != 0);
	});
	
	$rules = array(
	    array('rule' => 'allowAll'),
	    // array('rule' => 'denyForFirstBlogPost', 'message' => 'Custom overridden default access error message.'),
	    array('rule' => 'denyForSunday')
	);
	
	$access = Access::check('minerva', $rules, array('document' => $record->data()));
	var_dump($access);
	exit();*/
	
	$this->set(compact('record'));
	
    }
    
    /** 
     *  Delete a page record.
     *  Plugins can apply filters within their Page model class in order to run filters for the delete.
     *  Useful for "clean up" tasks such as removing image files from the server if the plugin was a gallery for example.
    */
    public function delete($url=null) {
	if(!$url) {
	    $this->redirect(array('controller' => 'pages', 'action' => 'index'));
	}
	
	if($record->delete()) {
	    FlashMessage::set('The content has been deleted.', array('options' => array('type' => 'success', 'pnotify_title' => 'Success', 'pnotify_opacity' => .8)));
	    $this->redirect(array('controller' => 'pages', 'action' => 'index'));
	} else {
	    FlashMessage::set('The content could not be deleted, please try again.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => .8)));
	    $this->redirect(array('controller' => 'pages', 'action' => 'index'));
	}		
    }
    
}
?>