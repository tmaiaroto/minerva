<?php
/**
 * This file contains additional bootstrap processes needed by Minerva.
 * Notably, this gives libraries (or plugins) developer for the CMS the ability
 * to hook into the core Page model.
 *
 * With a CMS, altering the core code is generally never advisable for upgrade
 * reasons and compatibility with other 3rd party add-ons. Also security issues.
 * So Minerva uses models to control quite a bit when it comes to core features
 * like pages, users, and bocks. The methods within Pages, Users, and Blocks
 * controllers are locked down. They can't and shouldn't be changed. They assume
 * a certain functionality that's common for a CMS. If they don't contain the
 * functionality desired, a new library can simply be made.
 *
 * This bootstrap process will take properties within the library's matching model
 * classes and apply it to the core Minerva model and respective controller classes.
 * These models need to be instaniated of course so that also allows them to apply
 * filters to various things in Minerva's core. For example, you can't changed
 * the read() method on the PagesController, but you could apply a filter to the
 * find() method, which the read() method uses to retrieve data from the database.
 * So there are a few clever tricks that can be used to change core functionality
 * without changing core code.
 *
 * How does Minerva know which libraries to use?
 * The routes file for each library will help determine that as well as the
 * documents from the database. Each document has a "page_type" field that has 
 * which library it depends on. So for read, update, and delete method calls,
 * an additional find() is performed first to see which library's model
 * class should be instantiated. Yes, it is an extra query, but it's for
 * a good cause and Minerva uses MongoDB, so no sweat =)
 *
 * The last thing this bootstrap process does is sets the templates to use.
 * Of course since we're using the core controllers, the default course of
 * action is to render the templates from /minerva/views/... but if a
 * library is being used for the page, then we want to render the templates
 * from /minerva/libraries/library_name/views/... Unless of course the
 * templates don't exist there, then the default templates will be used.
 * This is again so that core code (including templates) doesn't isn't altered.
 *
 * Note: "page_type," "block_type," and "user_type" all reference library names.
 * A general field of "library" is not used because it could create problems
 * with routing. However, the word "library" may be used when a "type" is implied.
 * 
*/

use \lithium\action\Dispatcher;
use \lithium\util\Inflector;
use lithium\net\http\Media;
use \lithium\security\Auth;

Dispatcher::applyFilter('_call', function($self, $params, $chain) {
    
    // Don't apply this for test cases
    if($params['request']->params['controller'] == '\lithium\test\Controller') {
	return $chain->next($self, $params, $chain);	
    }
    
    // Get the library if provided from the route params
    // (Note: Pages, Users, and Blocks are the only models considered, if any additional are created, the following must change)
    // TODO: consider going back to a standard field name, it makes for less if thens....but it could create more problems for several reasons when it comes to 3rd party addons...the if thens here guarantee things to a good degree
    if(isset($params['callable']->request->params['page_type'])) {
	$library = $params['callable']->request->params['page_type'];
	$library_field = 'page_type';
    } elseif(isset($params['callable']->request->params['user_type'])) {
	$library = $params['callable']->request->params['user_type'];
	$library_field = 'user_type';
    } elseif(isset($params['callable']->request->params['block_type'])) {
	$library = $params['callable']->request->params['block_type'];
	$library_field = 'block_type';
    } else {
	$library = null;
	$library_field = null;
    }
    
    // Get the slug (we may need to use it to find the library)
    if(isset($params['callable']->request->params['url'])) {
	$url = $params['callable']->request->params['url'];
    } else {
	$url = null;
    }
    
    // This should convert the controller name (lowercase or not) into the model name
    $model = Inflector::classify(Inflector::singularize($params['callable']->request->params['controller']));
    $modelClass = $LibraryBridgeModel = 'minerva\models\\'.$model;
    
    // If we loaded the Pages, Users, or Blocks controller and there's a "page_type" or "url" argument passed, meaning the routes must be set properly to use this filter
    // NOTE: wrapping controller param with strtolower() because $params['params']['controller'] will be camelcase, where $params['request']->params['controller'] will not be...So just in case something changes.
    // if((in_array(strtolower($params['callable']->request->params['controller']), $bridgeable_controllers)) && ((!is_null($library)) || (!is_null($url)))) {

    // edit: i think this is a better way of doing the check, rather than setting the "bridgeable_controllers" above... but this means ALL minerva's controllers (that have models) are bridgeable
    if((class_exists($LibraryBridgeModel)) && ((!is_null($library)) || (!is_null($url)))) {
	
	switch($params['callable']->request->params['action']) {
	    // update, read, and delete based on database record, so they must be instantiated in the PagesController
	    case 'create':
	    case 'index':
		// "read" is not here because the library's Page model will be loaded if the record has a library set
		$class = '\minerva\libraries\\'.$library.'\models\\'.$model;
		// Don't load the model if it doesn't exist
		if(class_exists($class)) {
		    $LibraryBridgeModel = new $class();
		}
	    break;
	    case 'read':
	    case 'update':
	    case 'delete':
		// make a query to get the library if it wasn't passed by the routes (we can get it from the record)
		if(!isset($library)) {
                    $modelClass = 'minerva\models\\'.$model;
		    // Note: again, something constant would definitely help this look nicer here, but there's only a few possibilities
		    $potential_library_fields = array('page_type', 'user_type', 'block_type');
                    $record = $modelClass::first(array('conditions' => array('url' => $params['callable']->request->params['url']), 'fields' => $potential_library_fields));
                    
                    if($record) {
                        $library = $record->data();
			// Note: also here...a static field name would help
			unset($library['_id']);
			$library_field = key($library);
			
                        // Set the library so the filter on Media::render() has it
                        $params['callable']->request->params[$library_field] = $library[$library_field];
                    } else {
                        $library = null;
                    }
                } else {
                    $library = $params['callable']->request->params[$library_field];
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
	
        // Authentication & Access Check for Core Minerva Controllers
        // (properties set in model for core controllers) ... transfer those settings to the controller
        $controllerClass = get_class($params['callable']);
        if(isset($LibraryBridgeModel::$access)) {
            $controllerClass::$access = $LibraryBridgeModel::$access;
        }
        
    }
    
    return $chain->next($self, $params, $chain);	
});

// Then use the render filter to change where the pages get their templates from.
// We'll first look in the page type library's views and then fall back to Minerva's views.
// Like how "themes" worked with CakePHP, sorta.
Media::applyFilter('render', function($self, $params, $chain){
    // TODO: see if the Media class has a method to check if a template exists, but file_exists() should work too
    
    // This is the old code (condensed but problematic with the key "library" for routing and other issues)
    /*if(isset($params['options']['request']->params['library'])) {
	$library = $params['options']['request']->params['library'];
    } else {
	$library= null;
    }*/
    
    // TODO: same as above filter on the dispatcher...
    // Note: Here the $library_field is not required because there's no queries being made to get the library name. The library name should now be in the request params (even if not put there by the routes).
    if(isset($params['options']['request']->params['page_type'])) {
	$library = $params['options']['request']->params['page_type'];
    } elseif(isset($params['options']['request']->params['user_type'])) {
	$library = $params['options']['request']->params['user_type'];
    } elseif(isset($params['options']['request']->params['block_type'])) {
	$library = $params['options']['request']->params['block_type'];
    } else {
	$library = null;
    }
    
    $layout = $params['options']['layout'];
    $type = (isset($params['options']['type'])) ? $params['options']['type']:'html';
    $template = $params['options']['template'];
    $controller = $params['options']['controller'];
    // If using a library, change template paths (if that library has templates, else default back)
    if(!empty($library)) {
	// set the layout template from Minerva's layouts if the library doesn't have it (will eliminate missing layout template errors completely, but the default template won't really match up in terms of design)
	if(file_exists(LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.' . $type . '.php')) {
	    $params['options']['paths']['layout'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php';
	} else {
	    $params['options']['paths']['layout'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php';
	}
	// set the view template from the library if there's a library in use and if the template exists, oterhwise fall back to core minerva templates and if it doesn't exist then there'll be a missing template error
	if((!empty($library)) && (file_exists(LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $template . '.' . $type . '.php'))) {
	    $params['options']['paths']['template'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $template . '.{:type}.php';
	} else {
	    $params['options']['paths']['template'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $template . '.{:type}.php';
	}
	//var_dump($params['options']);
    }
    
    return $chain->next($self, $params, $chain);
});
?>