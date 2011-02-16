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

class PagesController extends \minerva\controllers\MinervaController {
    
    /*
     * Rules used by Access::check() for access and document access per action.
     * 
     * By default we're restricting everything to managers.
     * This leaves the core PagesController to administrative purposes.
     * The "public" library will hold basic pages for anonymous visitors.
     * 
    */
    static $access = array(
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
                array('rule' => 'publishStatus', 'message' => 'You are not allowed to see unpublished content.', 'redirect' => '/')
            )
        ),
        'preview' => array(
            'action' => array(
                array('rule' => 'allowManagers', 'redirect' => '/users/login')
            ),
            'document' => array(
                // array('rule' => 'publishStatus', 'message' => 'You are not allowed to see unpublished content.', 'redirect' => '/')
            )
        ),
        'view' => array(
            'action' => array(
                array('rule' => 'allowAll', 'redirect' => '/users/login')
            ),
            'document' => array()
        )
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
            // obviously this needs to be somewhere controllable
            if(!in_array($user['role'], array('administrator', 'content_editor'))) {
            $this->redirect('/users/login');
            }
        } 
        
        if (empty($path)) {
            $path = array('home');
        }
        
        // this doesn't get any documents, it just checks access. the false "find_type" key is preventing a db query
        $this->getDocument(array('action' => __METHOD__, 'request' => $this->request, 'find_type' => false));
        
        $this->render(array('template' => join('/', $path)));
    }	
    
    /**
     * Index listing method responsible for showing lists of pages with pagination options.
     * If a "page_type" param (a library) is passed from the routing and the library has a Page model, it will be instantiated.
     * Additional filters can be applied there that further control things.
    */
    public function index() {
        // all index() methods are the same so they are done in MinervaController, but we do need a little context as to where it's called from
        $this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::index();
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
        $this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::create();
    }
    
    /**
     * Update a page.
    */
    public function update() {
        $this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::update();
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
        
        $document = $this->getDocument(array(
            'action' => __METHOD__,
            'request' => $this->request,
            'find_type' => 'first',
            'conditions' => array('url' => $url)
        ));
        
        if(!$document) {
            FlashMessage::set('Page not found.', array('options' => array('type' => 'error', 'pnotify_title' => 'Error', 'pnotify_opacity' => '.8')));
            $this->redirect(array('controller' => 'pages', 'action' => 'index'));
        }
        
        $this->set(compact('document'));
    }
    
    // TODO - not only will it ignore the publish status (because no document access rule for that will be applied) but it will check other access conditions AND use proper templates in order to render an accurate preview
    public function preview($url=null) {
        
    }
    
    /** 
     *  Delete a page document.
     *  Plugins can apply filters within their Page model class in order to run filters for the delete.
     *  Useful for "clean up" tasks such as removing image files from the server if the plugin was a gallery for example.
    */
    public function delete() {
        $this->calling_class = __CLASS__;
        $this->calling_method = __METHOD__;
        parent::delete();
    }
    
}
?>