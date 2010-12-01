<?php
use \lithium\storage\Session;
use \lithium\security\Auth;

// todo: move the following five out to another file (along with all the code at the bottom)
use \lithium\util\Inflector;
use lithium\net\http\Media;
use minerva\models\Page;
use minerva\models\User;
use minerva\models\Block;

Session::config(array(
	'default' => array('adapter' => 'Php'),
	// HMAC is built into lithium...can also hash your cookies
	/*'strategies' => array(
			'Hmac' => array('secret' => 'your_secret_key')
	)*/
	'flash_message' => array('adapter' => 'Php')
));

Auth::config(array(
	'user' => array(
		'adapter' => 'Form',
		'model'  => 'User',
		//'fields' => array('username', 'password'),
		///'scope'  => array('is_active' => 1),
		/*'filters' => array(
			//'password' => 'app\models\User::hashPassword'
		),*/
		'session' => array(
			'options' => array('name' => 'default')
		),
		
	)
));

use \lithium\action\Dispatcher;
use \lithium\net\http\Router;
use \lithium\action\Response;

Dispatcher::applyFilter('run', function($self, $params, $chain) {
	$blacklist = array(
		// uncomment to restrict access to these urls
		//'pages/index',
		//'pages'
	);
	//echo '<pre>'; print_r($params['request']->url); echo '</pre>';exit();
	//$matches = in_array(Router::match($params['request']->params, $params['request']), $blacklist);
	//var_dump($params['request']);

	$matches = in_array((string)$params['request']->url, $blacklist);		
	if($matches && !Auth::check('user')) {
	 	return new Response(array('location' => '/users/login'));	 	
	}
	return $chain->next($self, $params, $chain);
});



// While this would be super slick, it only works for two methods because library is set from URL...So why bother?
// IF somehow update, read, and delete can also be set here, then put it back.
// TODO: .... look at making a query for the record to get the library in order to do this...
// it's an extra query, but it cleans up the controllers quite nicely
// this also doesn't go in auth.

// Would put this in another file too if it was going to be used.
Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
	
    // Get the library if provided from the route params
    if(isset($params['params']['library'])) {
	$library = $params['params']['library'];
    } else {
	$library = null;
    }
    
    // Get the slug (we may need to use it to find the library)
    if(isset($params['params']['url'])) {
	$url = $params['params']['url'];
    } else {
	$url = null;
    }
    
    // If we loaded the Pages, Users, or Blocks controller and there's a "library" or "url" argument passed, meaning the routes must be set properly to use this filter
    // NOTE: wrapping param with strtolower() because $params['params']['controller'] will be camelcase, where $params['request']->params['controller'] will not be...So just in case something changes.
    if(((strtolower($params['params']['controller']) == 'pages') || (strtolower($params['params']['controller']) == 'users') || ($params['params']['controller'] == 'blocks')) && ((!is_null($library)) || (!is_null($url)))) {

	switch($params['params']['action']) {
	    // update, read, and delete based on database record, so they must be instantiated in the PagesController
	    case 'create':
	    case 'index':
		// "read" is not here because the library's Page model will be loaded if the record has a library set
		$class = '\minerva\libraries\\'.$params['request']->params['library'].'\models\Page';
		// Don't load the model if it doesn't exist
		if(class_exists($class)) {
		    $LibraryPage = new $class();
		}
	    break;
	    case 'read':
	    case 'view':
	    case 'update':
	    case 'delete':
		// make a query to get the library
		  // var_dump($params['request']);exit();
		 // var_dump($params);exit();
		   // $model = Inflector::classify($params['request']->params['library']);
		   // var_dump($model); exit();
			$record = Page::find('first', array('conditions' => array('url' => $params['request']->params['url']), 'fields' => 'library'));
			$class = '\minerva\libraries\\'.$record->data('library').'\models\Page';
			
			// Don't load the model if it doesn't exist
			if(class_exists($class)) {
			    $LibraryPage = new $class();
			}
			
		break;
	    default:
		// Don't load the library's Page model by default
	    break;
	}
	
    }
    
    return $chain->next($self, $params, $chain);	
});

// Then use the render filter to change where the pages get their templates from
// We'll first look in the library's views and then fall back to Minerva's views
// Like how "themes" worked with CakePHP
Media::applyFilter('render', function($self, $params, $chain){
    // TODO: see if the Media class has a method to check if a template exists, but file_exists() should work too
    if(isset($params['options']['request']->params['library'])) {
	$library = $params['options']['request']->params['library'];
    } else {
	$library= null;
    }
    $layout = $params['options']['layout'];
    $type = $params['options']['type'];
    $template = $params['options']['template'];
    $controller = $params['options']['controller'];
    // set the layout template from Minerva's layouts if the library doesn't have it (will eliminate missing layout template errors completely, but the default template won't really match up in terms of design)
    if(file_exists(LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.' . $type . '.php')) {
	$params['options']['paths']['layout'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php';
    } else {
	$params['options']['paths']['layout'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php';
    }
    // always set the view template from the library (if there's a library in use)
    if(!empty($library)) {
	$params['options']['paths']['template'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $template . '.{:type}.php';
    } else {
	$params['options']['paths']['template'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $template . '.{:type}.php';
    }
    
    return $chain->next($self, $params, $chain);
});
?>