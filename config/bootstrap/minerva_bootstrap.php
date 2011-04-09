<?php
/**
 * This file contains additional bootstrap processes needed by Minerva.
 * Basically, setting all the possible paths for templates.
 *
 * Routes are going to help us a lot because there's certain files we don't want to touch in order to change
 * the templates; for example, core Minerva files. It would create issues for updates. So our routes, which are
 * custom for each site, will be able to change up some render paths for us.
 *
 * Also, we may want to use the admin interface from Minerva and rather than duplicate the template, we can
 * simply use it in our new add on libraries so that if it ever changes, there wouldn't be any dated templates.
 *
 * Lithium allows us to pass an array of template paths to render. It will use the first available template.
 * So we have a graceful fallback system if a template isn't found in one location.
 *
 * This is applied to the Dispatcher::_callable() and done early so other filters can be applied without
 * conflict. So if a 3rd party library wanted to apply a filter to Media::render() for example, it could,
 * but it would be after this.
*/

use lithium\action\Dispatcher;
use lithium\util\Inflector;
use lithium\net\http\Media;
use lithium\security\Auth;
use lithium\core\Libraries;
use lithium\template\View;
use \Exception;

Dispatcher::applyFilter('run', function($self, $params, $chain) {
	// TODO: MOVE THIS TO _call FILTER
	
	// Dependency checks for the CMS
	$library_deps = array(
		'li3_flash_message'
	);
	
	$missing_deps = array();
	foreach($library_deps as $library) {
		if(Libraries::get($library) == null) {
			$missing_deps[] = $library;
		//	throw new Exception('Hey! You don\'t have that library!');
		}
	}
	
	if(!empty($missing_deps)) {
		$view = new View(array('loader' => 'Simple', 'renderer' => 'Simple'));
		$message =  'You are missing the following libraries that Minerva depends on:<br />';
		foreach($missing_deps as $library) {
			$message .= $library . '<br />';
		}
		$message .= '<br /><br />You will need to ensure that you have these libraries yourself for now, but in the future there will hopefully be an option to automatically download and install them.';
		
		echo $view->render(array('element' => $message));
		exit();
	}
	
	return $chain->next($self, $params, $chain);
});

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
	if(isset($params['request']->params['library']) && $params['request']->params['library'] == 'minerva') {
		
		
		// Pass through a few Minerva configuration variables
		$config = Libraries::get('minerva');
		$params['minerva_base'] = isset($config['base_url']) ? $config['base_url'] : '/minerva';
		$params['minerva_admin_prefix'] = isset($config['admin_prefix']) ? $config['admin_prefix'] : 'admin';
		
		// Get the library if provided from the route params
		$library = (isset($params['request']->params['library'])) ? $params['request']->params['library']:'minerva';
		// is this even necessary?
	   
	   
		// The admin flag from routes helps give control over the templates to use
		$admin = ((isset($params['request']->params['admin'])) && ($params['request']->params['admin'] == 1 || $params['request']->params['admin'] === true || $params['request']->params['admin'] == 'true' || $params['request']->params['admin'] == 'admin')) ? true:false;
		
		// The layout and template keys from the routes give us even more control, it's the final authority on where to check, but things do cascade down
		$layout = (isset($params['request']->params['layout'])) ? $params['request']->params['layout']:false;
		$template = (isset($params['request']->params['template'])) ? $params['request']->params['template']:false;
		
		
		// DEFAULTS
		$params['options']['render']['paths']['layout'] = array(
			'{:library}/views/layouts/{:layout}.{:type}.php'
		);
		$params['options']['render']['paths']['template'] = array(
			'{:library}/views/{:controller}/{:template}.{:type}.php'
		);
		
		// if admin is true, then look for admin templates first
		if($admin === true) {
			array_unshift($params['options']['render']['paths']['layout'], '{:library}/views/_admin/layouts/{:layout}.{:type}.php');
			array_unshift($params['options']['render']['paths']['template'], '{:library}/views/_admin/{:controller}/{:template}.{:type}.php');
		}
		
		/**
		 * STATIC VIEWS
		 * Special situation; "blocks" and "pages" and "menus" have "static" templates that don't require a datasource.
		 * This is only the case for the "view" action on these controllers. 
		*/
		// TODO: maybe get this from config info set with Libraries::add() ?  (with defaults as listed here)
		$controllers_with_static_view_methods = array(
			'pages',
			'blocks',
			'menus',
			'minerva.pages',
			'minerva.blocks',
			'minerva.menus'
		);
		if(($params['request']->params['action'] == 'view') && (in_array($params['request']->params['controller'], $controllers_with_static_view_methods))) {
			$params['options']['render']['paths']['layout'] = array(
				'{:library}/views/layouts/static/{:layout}.{:type}.php',
				'{:library}/views/layouts/{:layout}.{:type}.php'
			);
			$params['options']['render']['paths']['template'] = array(
				'{:library}/views/{:controller}/static/{:template}.{:type}.php'
			);
			
			// ADMIN STATIC VIEWS
			// Hey, static views can be for just the admin interface as well and those will take priority if the admin flag is set.
			if($admin === true) {
				array_unshift($params['options']['render']['paths']['layout'], '{:library}/views/_admin/layouts/{:layout}.{:type}.php');
				array_unshift($params['options']['render']['paths']['template'], '{:library}/views/_admin/{:controller}/static/{:template}.{:type}.php');
			}
		}
		
		/**
		 * MANUAL OVERRIDES FROM ROUTES
		 * Was the "layout" or "template" key set in the route? Then we're saying to change up the layout path.
		 * This allows other libraries to share the layout template from another library right from the route.
		 *
		 * NOTE: This supercedes everything (even static). It is a manual setting in the route that is optional,
		 * but we want to obey it.
		*/    
		if(!empty($layout)) {
			// Layouts can be borrowed from other libraries, defined like: library.layout_template (the type is defined in the route with its own key)
			$layout_pieces = explode('.', $layout);
			$layout_library = false;
			if(count($layout_pieces) > 1) {
				$layout_library = $layout_pieces[0];
				$layout = $layout_pieces[1];
			} else {
				$layout = $layout_pieces[0];
			}
			
			// if the library defined is "app" or false then use the main app route
			if($layout_library == 'app' || $layout_library === false) {
				array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . '/views/layouts/' . $layout . '.{:type}.php');
			} else {
				array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $layout_library . '/views/layouts/' . $layout . '.{:type}.php');
			}
			
			// custom layout and template paths can also take advantage of the admin flag
			if($admin === true) {
				if($layout_library == 'app' || $layout_library === false) {
					array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . '/views/_admin/layouts/' . $layout . '.{:type}.php');
				} else {
					array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $layout_library . '/views/_admin/layouts/' . $layout . '.{:type}.php');
				}
			}
			
		}
		if(!empty($template)) {
			// Templates can be borrowed from other libraries, defined like: library.template (the controller and type is defined in the route with their own keys)
			$template_pieces = explode('.', $template);
			$template_library = false;
			if(count($template_pieces) > 1) {
				$template_library = $template_pieces[0];
				$template = $template_pieces[1];
			} else {
				$template = $template_pieces[0];
			}
			
			// if the library defined is "app" then use the main app route
			if($template_library == 'app' || $template_library === false) {
				array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . '/views/{:controller}/' . $template . '.{:type}.php');
			} else {
				array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $template_library . DIRECTORY_SEPARATOR . 'views/{:controller}/' . $template . '.{:type}.php');
			}
			
			// custom layout and template paths can also take advantage of the admin flag
			if($admin === true) {
				if($template_library == 'app' || $template_library === false) {
					array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . '/views/_admin/{:controller}/' . $template . '.{:type}.php');
				} else {
					array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $template_library . DIRECTORY_SEPARATOR . 'views/_admin/{:controller}/' . $template . '.{:type}.php');
				}
			}
		}
		
		
		// MISSING TEMPLATES
		$params['options']['render']['paths']['template'][] = '{:library}/views/_missing/missing_template.html.php';
		$params['options']['render']['paths']['layout'][] = '{:library}/views/_missing/missing_layout.html.php';
		
		// var_dump($params['options']['render']['paths']); // <--- this is a great thing to uncomment and browse the site for reference
		
	}
	
    return $chain->next($self, $params, $chain);
});
?>