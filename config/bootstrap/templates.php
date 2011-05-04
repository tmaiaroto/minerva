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
    $use_minerva_templates = false;
	
	// Only apply the following when using the minerva library OR if the "use_minerva_templates" option has been set true in Libraries::add('xxx', array(...))
	if(isset($params['request']->params['library'])) {
		$lib_config = Libraries::get($params['request']->params['library']);
		if(($params['request']->params['library'] == 'minerva') || (isset($lib_config['use_minerva_templates']) && $lib_config['use_minerva_templates'] == true)) {
			$use_minerva_templates = true;
		}
		if(isset($lib_config['minerva_theme'])) {
			// TODO: ... Themes. This will add to the layout and template paths... But I'm thinking a "theme" will just be a folder under "views" under a "minerva_themes" library.
		}
	}
	
	if($use_minerva_templates) {
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
		if(isset($lib_config['static_actions'])) {
			// TODO: allow other libraries to use static templates? (which just changes the path to look for templates to include a "static" folder)
		}
		$params['minerva_controllers_using_static'] = isset($config['controllers_using_static']) ? $config['controllers_using_static'] += $default_static : $default_static;
		
		/**
		 * The following code will set all the template and layout paths for Minerva.
		 * The last path to check will be a path to a missing template and before that,
		 * paths to the core Minerva library's views folder. Depending on the routing
		 * parameters, other paths will be checked.
		*/
		
		// The admin flag from routes helps give control over the templates to use
		if(empty($params['request']->params['admin'])) { unset($params['request']->params['admin']); } // admin can not be empty. if its empty just remove it. this unset really shouldn't be required...some places seem to be setting admin as an empty string... TODO: find those areas
		$admin = ((isset($params['request']->params['admin'])) && ($params['request']->params['admin'] == 1 || $params['request']->params['admin'] === true || $params['request']->params['admin'] == 'true' || $params['request']->params['admin'] == 'admin')) ? true:false;
		
		// The layout and template keys from the routes give us even more control, it's the final authority on where to check, but things do cascade down
		$layout = (isset($params['request']->params['layout'])) ? $params['request']->params['layout']:false;
		$template = (isset($params['request']->params['template'])) ? $params['request']->params['template']:false;
		
		// DEFAULT LAYOUT & TEMPLATE PATHS
		$params['options']['render']['paths']['layout'] = array(
			'{:library}/views/layouts/{:layout}.{:type}.php',
			LITHIUM_APP_PATH . '/libraries/minerva/views/layouts/{:layout}.{:type}.php'
		);
		$params['options']['render']['paths']['template'] = array(
			'{:library}/views/{:controller}/{:template}.{:type}.php'
		);
		
		// if admin is true, then look for admin templates first
		if($admin === true) {
			// if another library is using Minerva's templates for the admin interface, {:library} won't be "minerva" so hard code the path...but also allow that {:library} to provide its own template which would be looked for first
			array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . '/libraries/minerva/views/_admin/layouts/{:layout}.{:type}.php');
			array_unshift($params['options']['render']['paths']['layout'], '{:library}/views/_admin/layouts/{:layout}.{:type}.php');
			
			// note: we don't have to do the same thing for the view template because the {:controller} isn't going to be within the minerva library
			array_unshift($params['options']['render']['paths']['template'], '{:library}/views/_admin/{:controller}/{:template}.{:type}.php');
		}
		
		/**
		 * STATIC VIEWS
		 * Special situation; "blocks" and "pages" and "menus" have "static" templates that don't require a datasource.
		 * This is only the case for the "view" action on these controllers.
		 * 
		 * If keeping all templates outside of the minerva plugin is desired, for organization or cleanliness,
		 * you can put templates in your main app under the appropriate directories.
		 * Layouts under /app/views/layouts/static/...
		 * Templates under /app/views/pages/static/... or /app/views/menus/static... or /app/views/blocks/static...
		*/
		if(($params['request']->params['action'] == 'view') && (in_array($params['request']->params['controller'], $params['minerva_controllers_using_static']))) {
			$params['options']['render']['paths']['layout'] = array(
				'{:library}/views/layouts/static/{:layout}.{:type}.php',
				LITHIUM_APP_PATH . '/views/layouts/static/{:layout}.{:type}.php',
				'{:library}/views/layouts/{:layout}.{:type}.php'
			);
			$params['options']['render']['paths']['template'] = array(
				'{:library}/views/{:controller}/static/{:template}.{:type}.php',
				LITHIUM_APP_PATH . '/views/{:controller}/static/{:template}.{:type}.php'
			);
			
			// ADMIN STATIC VIEWS
			// Hey, static views can be for just the admin interface as well and those will take priority if the admin flag is set.
			if($admin === true) {
				// Again, we want to allow layouts and templates to be put into the main app ... Maybe...
				//array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . '/views/_admin/layouts/{:layout}.{:type}.php');
				//array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . '/views/_admin/{:controller}/static/{:template}.{:type}.php');
				// The minerva library still gets the preference though
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
				array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . '/libraries/' . $layout_library . '/views/layouts/' . $layout . '.{:type}.php');
			}
			
			// custom layout and template paths can also take advantage of the admin flag
			if($admin === true) {
				if($layout_library == 'app' || $layout_library === false) {
					array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . '/views/_admin/layouts/' . $layout . '.{:type}.php');
				} else {
					array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . '/libraries/' . $layout_library . '/views/_admin/layouts/' . $layout . '.{:type}.php');
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
				array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . '/libraries/' . $template_library . '/views/{:controller}/' . $template . '.{:type}.php');
			}
			
			// custom layout and template paths can also take advantage of the admin flag
			if($admin === true) {
				if($template_library == 'app' || $template_library === false) {
					array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . '/views/_admin/{:controller}/' . $template . '.{:type}.php');
				} else {
					array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . '/libraries/' . $template_library . '/views/_admin/{:controller}/' . $template . '.{:type}.php');
				}
			}
		}
		
		
		// MISSING TEMPLATES
		$params['options']['render']['paths']['template'][] = '{:library}/views/_missing/missing_template.html.php';
		$params['options']['render']['paths']['layout'][] = '{:library}/views/_missing/missing_layout.html.php';
		// ...and missing templates within the Minerva library folder 
		$params['options']['render']['paths']['template'][] = LITHIUM_APP_PATH . '/libraries/minerva/views/_missing/missing_template.html.php';
		$params['options']['render']['paths']['layout'][] = LITHIUM_APP_PATH. '/libraries/minerva/views/_missing/missing_layout.html.php';
		
		// var_dump($params['options']['render']['paths']); // <--- this is a great thing to uncomment and browse the site for reference
		
	}
	
    return $chain->next($self, $params, $chain);
});
?>