<?php
/**
 * This file contains additional bootstrap processes needed by Minerva.
 * Notably, this gives libraries (or plugins) developer for the CMS the ability
 * to hook into the core Page model.
 *
 *
*/

use \lithium\action\Dispatcher;
use \lithium\util\Inflector;
use lithium\net\http\Media;
use minerva\models\Page;
use minerva\models\User;
use minerva\models\Block;
use \lithium\security\Auth;
use \lithium\action\Response;

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
    
    // Controllers that can be "bridged" or hooked into from plugins
    $bridgeable_controllers = array(
        'pages',
        'users',
        'blocks'
    );
    
    // This should convert the controller name (lowercase or not) into the model name
    $model = Inflector::classify(Inflector::singularize($params['params']['controller']));
    $modelClass = $LibraryBridgeModel = 'minerva\models\\'.$model;

    // If we loaded the Pages, Users, or Blocks controller and there's a "library" or "url" argument passed, meaning the routes must be set properly to use this filter
    // NOTE: wrapping controller param with strtolower() because $params['params']['controller'] will be camelcase, where $params['request']->params['controller'] will not be...So just in case something changes.
    if((in_array(strtolower($params['params']['controller']), $bridgeable_controllers)) && ((!is_null($library)) || (!is_null($url)))) {

	switch($params['params']['action']) {
	    // update, read, and delete based on database record, so they must be instantiated in the PagesController
	    case 'create':
	    case 'index':
		// "read" is not here because the library's Page model will be loaded if the record has a library set
		$class = '\minerva\libraries\\'.$params['request']->params['library'].'\models\\'.$model;
		// Don't load the model if it doesn't exist
		if(class_exists($class)) {
		    $LibraryBridgeModel = new $class();
		}
	    break;
	    case 'read':
	    case 'update':
	    case 'delete':
		// make a query to get the library if it wasn't passed by the routes (we can get it from the record)
		if(!isset($params['request']->params['library'])) {
                    $modelClass = 'minerva\models\\'.$model;
                    $record = $modelClass::find('first', array('conditions' => array('url' => $params['request']->params['url']), 'fields' => 'library'));
                    
                    if($record) {
                        $library = $record->data('library');
                        // Set the library so the filter on Media::render() has it
                        $params['request']->params['library'] = $library;
                    } else {
                        $library = null;
                    }
                } else {
                    $library = $params['request']->params['library'];
                }
                
                $class = '\minerva\libraries\\'.$library.'\models\\'.$model;
                
                // Load the model if it exists
		if(class_exists($class)) {
		    $LibraryBridgeModel = new $class();
		}
		break;
	    default:
                // Don't load the library's Page model by default
	    break;
	}
	
        // Authentication
        if($LibraryBridgeModel) {
            $matches = in_array($params['params']['action'], $LibraryBridgeModel::$_protected_methods);		
            if($matches && !Auth::check('user')) {
                // Change the params so it redirects to login page
                // TODO: figure this out. it kinda works. it still wants to render a read template. but the controller changed
                $params['params']['controller'] = 'Users';
                $params['params']['template'] = 'login';
                $params['params']['action'] = 'login';
                /*
                $params['params']['url'] =  false;
                //$params['options']['template'] = 'login';
                //$params['params']['library'] = false;
                $params['request']->url = '/users/login';
             var_dump($params);   */
            }
        }
        
    }
    
    return $chain->next($self, $params, $chain);	
});

// Then use the render filter to change where the pages get their templates from
// We'll first look in the library's views and then fall back to Minerva's views
// Like how "themes" worked with CakePHP
Media::applyFilter('render', function($self, $params, $chain){
    var_dump($params);
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
    //var_dump($params['options']);
    
    return $chain->next($self, $params, $chain);
});
?>