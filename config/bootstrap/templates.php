<?php
/**
 * This file contains additional bootstrap processes needed by Minerva.
 * Basically, setting all the possible paths for templates.
 *
 * Routes are going to help us a lot because there's certain files we don't want to touch in order to change
 * the templates; for example, core Minerva files. It would create issues for updates. So new routes, from
 * plugins that use minerva, will be able to change up some render paths.
 *
 * Also, we may want to use the admin interface from Minerva and rather than duplicate the template, so we can
 * simply use it in our new add on plugins so that if it ever changes, there wouldn't be any dated templates.
 * Of course, provided the plugin's needs are met by the default admin templates. Otherwise, new templates.
 *
 * Lithium allows us to pass an array of template paths to render. It will use the first found template.
 * So we have a graceful fallback system if a template isn't found in one location.
 * It will go all the way back to loading a missing template and layout file in fact.
 * That doesn't mean "404" pages and it doesn't mean it's not possible to see a white page still,
 * but it helps. When errors are turned on in a production environment, then 404 pages get rendered.
 * See errors.php
 * 
 * All this is applied to the Dispatcher::_callable() so it happens really early on in the dispatcher process.
 * This allows other plugins to apply their filters aftward; keep in mind the order in which libraries are
 * added and if the library relies on Minerva, add it after.
*/

use lithium\action\Dispatcher;
use lithium\core\Libraries;
use lithium\template\View;
use \Exception;

Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
    
	// Only apply the following when using the minerva library
	if(isset($params['request']->params['library']) && $params['request']->params['library'] == 'minerva') {
		// Pass through a few Minerva configuration variables
		$config = Libraries::get('minerva');
		$params['minerva_base'] = isset($config['base_url']) ? $config['base_url'] : '/minerva';
		$params['minerva_admin_prefix'] = isset($config['admin_prefix']) ? $config['admin_prefix'] : 'admin';
		// some default controllers that utilize static view templates using a "view" action
		$default_static = array(
			'pages',
			'blocks',
			'menus',
			'minerva.pages',
			'minerva.blocks',
			'minerva.menus'
		);
		$params['minerva_controllers_using_static'] = isset($config['controllers_using_static']) ? $config['controllers_using_static'] += $default_static : $default_static;
		
		
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
		
		// TODO: render a template for this
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
		
		/**
		 * The following code will set all the template and layout paths for Minerva.
		 * 
		*/
		
		// The admin flag from routes helps give control over the templates to use
		$admin = ((isset($params['request']->params['admin'])) && ($params['request']->params['admin'] == 1 || $params['request']->params['admin'] === true || $params['request']->params['admin'] == 'true' || $params['request']->params['admin'] == 'admin')) ? true:false;
		
		// The layout and template keys from the routes give us even more control, it's the final authority on where to check, but things do cascade down
		$layout = (isset($params['request']->params['layout'])) ? $params['request']->params['layout']:false;
		$template = (isset($params['request']->params['template'])) ? $params['request']->params['template']:false;
		
		// DEFAULT LAYOUT & TEMPLATE PATHS
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
		if(($params['request']->params['action'] == 'view') && (in_array($params['request']->params['controller'], $params['minerva_controllers_using_static']))) {
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