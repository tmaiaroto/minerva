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

Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
    
    /*
     * So the problem with redirects and building requests is that since the "app" folder was changed to "minerva"
     * the "_base" property is not set properly. In the Request class there's a method called base() that sets it.
     * It basically does a string replace on "app/webroot" ... But we have "minerva/webroot" So we can change the
     * /minerva/webroot/index.php file and pass in an empty base key of "" to fix the issue.
     * I would rather set it here in the filter since that's where all the major changes are taking place.
     * I'd like to limit changes to a specific area to avoid complexity...But _base is protected as well as base().
     * So we can't set it here. It can only be set by instantiation.
     * Alternatively we can write a new class (extending Request) and use that instead...
     * TODO: Look into that and in general a sub dispatcher that might avoid several issues and clean up this code.
     *
     * For now the index.php file has been changed, but that may cause problems elsewhere. Not sure yet.
     * Now all the redirects don't show a URL of site.com/minerva/blog it will be the expected site.com/blog
     * Both work though.
    */
    
    // Don't apply this for test cases
    if($params['request']->params['controller'] == '\lithium\test\Controller') {
	return $chain->next($self, $params, $chain);	
    }
    
    // Get the library if provided from the route params
    // (Note: Pages, Users, and Blocks are the only models considered, if any additional are created, the following must change)
    // TODO: consider going back to a standard field name, it makes for less if thens....but it could create more problems for several reasons when it comes to 3rd party addons...the if thens here guarantee things to a good degree
    if(isset($params['request']->params['page_type'])) {
	$library = $params['request']->params['page_type'];
	$library_field = 'page_type';
    } elseif(isset($params['request']->params['user_type'])) {
	$library = $params['request']->params['user_type'];
	$library_field = 'user_type';
    } elseif(isset($params['request']->params['block_type'])) {
	$library = $params['request']->params['block_type'];
	$library_field = 'block_type';
    } else {
	$library = null;
	$library_field = null;
    }
    
    // Get the slug (we may need to use it to find the library)
    if(isset($params['request']->params['url'])) {
	$url = $params['request']->params['url'];
    } else {
	$url = null;
    }
    
    // This should convert the controller name (lowercase or not) into the model name
    $model = Inflector::classify(Inflector::singularize($params['request']->params['controller']));
    $modelClass = $LibraryBridgeModel = 'minerva\models\\'.$model;
    
   // var_dump($params['request']);
    
    // If we loaded the Pages, Users, or Blocks controller and there's a "page_type" or "url" argument passed, meaning the routes must be set properly to use this filter
    // NOTE: wrapping controller param with strtolower() because $params['params']['controller'] will be camelcase, where $params['request']->params['controller'] will not be...So just in case something changes.
    // if((in_array(strtolower($params['callable']->request->params['controller']), $bridgeable_controllers)) && ((!is_null($library)) || (!is_null($url)))) {

    // edit: i think this is a better way of doing the check, rather than setting the "bridgeable_controllers" above... but this means ALL minerva's controllers (that have models) are bridgeable
    if((class_exists($LibraryBridgeModel)) && ((!is_null($library)) || (!is_null($url)))) {
	
	switch($params['request']->params['action']) {
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
                    $record = $modelClass::first(array('conditions' => array('url' => $params['request']->params['url']), 'fields' => $potential_library_fields));
                    
                    if($record) {
                        $library = $record->data();
			// Note: also here...a static field name would help
			unset($library['_id']);
			$library_field = key($library);
			
                        // Set the library so the filter on Media::render() has it
                       // $params['request']->params[$library_field] = $library[$library_field];
			$library = $library[$library_field];
                    } else {
                        $library = null;
                    }
                } else {
		    
                    $library = $params['request']->params[$library_field];
                }
                $class = '\minerva\libraries\\'.$library.'\models\\'.$model;
		
                // Load the model if it exists
		if(class_exists($class)) {
		    $LibraryBridgeModel = new $class();
		}
		
		//var_dump($params['request']);exit();
		
		break;
	    default:
                // Don't load the library's Page model by default
	    break;
	}
	
    }
    
    // Authentication & Access Check for Core Minerva Controllers
    // (properties set in model for core controllers) ... transfer those settings to the controller
    $controllerClass = '\minerva\controllers\\'.$params['params']['controller'].'Controller';
    // If the $controllerClass doesn't exist, it means it's a controller that Minerva doesn't have. That means it's not core and the access can be set there on that controller.
    if((is_object($LibraryBridgeModel)) && (isset($LibraryBridgeModel::$access)) && (class_exists($controllerClass))) {
	// Don't overwrite core Minerva controller with new access rules, but append them (giving the library model's access property priority)
	$controllerClass::$access = $LibraryBridgeModel::$access += $controllerClass::$access;
    }
    
    // Send some options to Media::render() the "admin" parameter will tell Media::render() that we want to render the core layout. If however a template for the action is found in the library's folder it will be used. This way libraries can use the core admin templates, or they can use their own. They can also use their own layout, but at that point it's not considered an "admin" page.
    $params['options']['render']['library'] = $library;
    $params['options']['render']['admin'] = (isset($params['request']->params['admin'])) ? $params['request']->params['admin']:false;
    
    return $chain->next($self, $params, $chain);	
});

Media::applyFilter('render', function($self, $params, $chain) {
    // Set the layout and template paths to the library if it has templates there
    $library = $params['options']['library'];
    $layout = $params['options']['layout'];
    $type = $params['options']['type'];
    $template = $params['options']['template'];
    $controller = $params['options']['controller'];
    $admin = $params['options']['admin'];
    
    if((!$admin) && (file_exists(LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.' . $type . '.php'))) {
	$params['options']['paths']['layout'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php';
    } else {
	$params['options']['paths']['layout'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php';
    }
    
    if((!empty($library)) && (file_exists(LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $template . '.' . $type . '.php'))) {
	$params['options']['paths']['template'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . '{:template}.{:type}.php';
    } else {
	$params['options']['paths']['template'] = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . '{:template}.{:type}.php';
    }
    
    // var_dump($params['options']['paths']);
    return $chain->next($self, $params, $chain);	
});
?>