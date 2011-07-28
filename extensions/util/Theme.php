<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 * 
 * Theme Utility Class
 * 
 * This class is responsible for setting the render paths for templates and layouts.
 * This is set at the dispatcher level and is an extremely important part of Minerva's 
 * flow. This allows actions to be used for both administrative purposes as well as 
 * public uses by changing the template.
 * 
 * Of course, actions can be built specifically for admin uses alone...
 * But many of Minerva's core actions such as "index" are utilized by both the 
 * front-end and back-end interface.
 * 
 * The convention for admin templates is to store under the "views" directory, another 
 * "_admin" directory with its own set of directories named for each controller and 
 * templates within.
 * 
 * Minerva will look for template files within the plugin (if a plugin is in use)
 * first and then default back to other locations. 
 * 
 * This class is mainly used in Minerva's bootstrap process, but could be used by
 * any library for other needs.
 * 
*/
namespace minerva\extensions\util;

use lithium\core\Libraries;

class Theme extends \lithium\core\StaticObject {
    
    /**
	 * Stores theme class instances for internal use.
	 *
	 * While the `Theme` public API does not require instantiation thanks to late static binding
	 * introduced in PHP 5.3, LSB does not apply to class attributes. In order to prevent you
	 * from needing to redeclare every single `Theme` class attribute in subclasses, instances of
	 * the models are stored and used internally.
	 *
	 * @var array
	*/
    protected static $_instances = array();
    
    /**
     * These are the Minerva controllers that have static "view" actions.
     * These actions will render templates without any data from the database.
     * This is identical to the default Lithium pages action which renders from
     * template files within the /views/pages folder.
     * 
     * Minerva plugin libraries can also create a static view() action just the same.
     * In order for those to automatically check the same render paths for templates,
     * they simply need to define a "static_controllers" key in their library config
     * that has an array value with the controllers that utilize the static view.
     * 
     * @var array
    */
    protected static $_core_static_controllers = array(
        'pages',
        'blocks',
        'menus'
    );
    
    /**
     * Sets the render path arrays.
     * 
     * @see minerva/config/bootstrap/templates.php
     * @see \lithium\action\Request
     * @param object $request The Lithium request object
     * @param array $options
     * @return array
     * @filter This method can be filtered.
    */
    public static function setRenderPaths($request=null, $options=array()) {
        $self = static::_object();
        $params = compact('request', 'options');
        
        $filter = function($self, $params) {
			$options = $params['options'];
            $request = $params['request'];
            // By default, $paths is null. 
            // This allows this method to be called from a Dispatcher filter without having any
            // negative impact on libraries that do not use this Minerva template rendering process.
            $paths = null;
            
            $plugin = (isset($request->params['plugin'])) ? $request->params['plugin']:false;
            $use_minerva = (isset($request->params['library']) && $request->params['library'] == 'minerva') ? true:false;
            $admin = (isset($request->params['admin']) && !empty($request->params['admin'])) ? true:false;
            $lib_config = ($plugin) ? Libraries::get($plugin):Libraries::get('minerva');
            // If the library serves as a Minerva plugin, it should specify that it is by specifying 
            // a "minerva_plugin" setting as true.
            // It doesn't need to. If the route has a "plugin" key in it, then it will still check
            // the render paths that are being set below. However, a plugin library may still wish to
            // use the following render paths, yet use a route that does not include a "plugin" key.
            // This allows normal libraries and the main application to have render paths which 
            // remain unchanged so they continue to function normally.
            $use_minerva = ($use_minerva === false && isset($lib_conig['minerva_plugin']) && $lib_config['minerva_plugin'] === true) ? true:$use_minerva;
            
            if($use_minerva) {
                $paths = array('layout' => null, 'template' => null);
                
                // DEFAULT RENDER PATHS FOR BOTH CORE MINERVA AND MINERVA PLUGIN LIBRARIES
                // There are 4 main paths to check that allow for various needs/scenarios.
                // Several render paths are checked to allow for maximum flexibility. It can be
                // confusing to understand, so just read through the scenarios and you'll know
                // exactly where to create template files.
                // 
                // The first note is about "admin" templates. These follow a normal conventional
                // directory path within the views directory, but after first being put into an 
                // "_admin" directory.
                // 
                // #1 You want to change core Minerva templates (admin or public). 
                // You shouldn't touch core Minerva files, so you'll copy over those templates from
                // the "minerva" library and then put them under: /app/views/minerva/...
                // You can then make your changes. This path is checked first.
                // 
                // #2 You have a plugin library from someone else. You don't want to change its files
                // so that you can pull updates for it over time without worrying about conflicts.
                // You can copy its templates and put them within your main app's views directory.
                // This is very similar to scenario #1 only it works for plugin libraries.
                // 
                // #3 You have a plugin library that you created and want to use special templates with. 
                // So you can have your own distinct admin interface for example. You can place these
                // templates within the plugin library's views directory like normal and those will
                // be used instead of core default Minerva templates. You may or may not share this 
                // plugin with the world, so scenario #2 may not even be used for this plugin.
                // 
                // #4 You have a plugin library that you want to use Minerva's core templates with. 
                // You like how Minerva's core templates look. So you don't want to re-invent the wheel.
                // This is particuarly common for the admin templates. Rather than duplicating the
                // template files into your plugin library's views folder (because you want to stay 
                // up to date should Minerva's admin templates change in the future), you can simply
                // use those templates. This is automatic and if you do not put templates in any of
                // the first few locations, then these core templates will be used.
                if($admin) {
                    $paths['layout'] = array(
                        LITHIUM_APP_PATH . '/views/minerva/_plugin/'.$plugin.'/_admin/layouts/{:layout}.{:type}.php', // #2
                        LITHIUM_APP_PATH . '/views/minerva/_admin/layouts/{:layout}.{:type}.php', // #1
                        '{:library}/views/_admin/layouts/{:layout}.{:type}.php', // #3
                        LITHIUM_APP_PATH . '/libraries/minerva/views/_admin/layouts/{:layout}.{:type}.php' // #4
                    );
                    $paths['template'] = array(
                        LITHIUM_APP_PATH . '/views/minerva/_plugin/'.$plugin.'/_admin/{:controller}/{:template}.{:type}.php',
                        LITHIUM_APP_PATH . '/views/minerva/_admin/{:controller}/{:template}.{:type}.php',
                        '{:library}/views/_admin/{:controller}/{:template}.{:type}.php',
                        LITHIUM_APP_PATH . '/libraries/minerva/views/_admin/{:controller}/{:template}.{:type}.php'
                    );
                } else {
                    $paths['layout'] = array(
                        LITHIUM_APP_PATH . '/views/minerva/_plugin/'.$plugin.'/layouts/{:layout}.{:type}.php',
                        LITHIUM_APP_PATH . '/views/minerva/layouts/{:layout}.{:type}.php',
                        '{:library}/views/layouts/{:layout}.{:type}.php',
                        LITHIUM_APP_PATH . '/libraries/minerva/views/layouts/{:layout}.{:type}.php'
                    );
                    $paths['template'] = array(
                        LITHIUM_APP_PATH . '/views/minerva/_plugin/'.$plugin.'/{:controller}/{:template}.{:type}.php',
                        LITHIUM_APP_PATH . '/views/minerva/{:controller}/{:template}.{:type}.php',
                        '{:library}/views/{:controller}/{:template}.{:type}.php',
                        LITHIUM_APP_PATH . '/libraries/minerva/views/{:controller}/{:template}.{:type}.php'
                    );
                }
                
                // CONTROLLERS WITH A STATIC VIEW ACTION
                // Controllers that use static view templates which render pages that contain 
                // no data from the database. This is a special action used in the Minerva core 
                // "blocks," "pages," and "menus" controllers. However, plugins can also implement
                // this view() action in its controller(s) to also render static templates.
                $default_static_controllers = $self::getCoreStaticControllers();
                $static_controllers = (isset($lib_config['static_controllers'])) ? $default_static_controllers += $lib_config['static_controllers']:$default_static_controllers;
                
                // This works very similar to the render paths above, only we are looking under 
                // a special "static" directory instead. We will not need to take into consideration 
                // the render paths set above, so $paths is set over again. Again, there are 4 paths
                // to check for both "admin" and normal public templates.
                if(($request->params['action'] == 'view') && (in_array($request->params['controller'], $static_controllers))) {
                    if($admin == true) {
                        $paths['layout'] = array(
                            LITHIUM_APP_PATH . '/views/minerva/_plugin/'.$plugin.'/_admin/layouts/static/{:layout}.{:type}.php',
                            LITHIUM_APP_PATH . '/views/minerva/_admin/layouts/static/{:layout}.{:type}.php',
                            '{:library}/views/_admin/layouts/static/{:layout}.{:type}.php',
                            LITHIUM_APP_PATH . '/libraries/minerva/views/_admin/layouts/static/{:layout}.{:type}.php'
                        );
                        $paths['template'] = array(
                            LITHIUM_APP_PATH . '/views/minerva/_plugin/'.$plugin.'/_admin/{:controller}/static/{:template}.{:type}.php',
                            LITHIUM_APP_PATH . '/views/minerva/_admin/{:controller}/static/{:template}.{:type}.php',
                            '{:library}/views/_admin/{:controller}/static/{:template}.{:type}.php',
                            LITHIUM_APP_PATH . '/libraries/minerva/views/_admin/{:controller}/static/{:template}.{:type}.php'
                        );
                    } else {
                        $paths['layout'] = array(
                            LITHIUM_APP_PATH . '/views/minerva/_plugin/'.$plugin.'/layouts/static/{:layout}.{:type}.php',
                            LITHIUM_APP_PATH . '/views/minerva/layouts/static/{:layout}.{:type}.php',
                            '{:library}/views/layouts/static/{:layout}.{:type}.php',
                            LITHIUM_APP_PATH . '/libraries/minerva/views/layouts/static/{:layout}.{:type}.php'
                        );
                        $paths['template'] = array(
                            LITHIUM_APP_PATH . '/views/minerva/_plugin/'.$plugin.'/{:controller}/static/{:template}.{:type}.php',
                            LITHIUM_APP_PATH . '/views/minerva/{:controller}/static/{:template}.{:type}.php',
                            '{:library}/views/{:controller}/static/{:template}.{:type}.php',
                            LITHIUM_APP_PATH . '/libraries/minerva/views/{:controller}/static/{:template}.{:type}.php'
                        );
                    }
                }
                
            }
            
            // var_dump($paths);
            return $paths;
		};
        
        return static::_filter(__FUNCTION__, $params, $filter);
    }
    
    
    protected static function &_object() {
		$class = get_called_class();

		if (!isset(static::$_instances[$class])) {
			static::$_instances[$class] = new $class();
		}
		return static::$_instances[$class];
	}
    
    /**
     * Returns an array of controllers that contain actions that utilize static view templates.
     * Or in other words, don't render pages with data retrieved from the database.
     * 
     * @return array A list of core Minerva controllers
    */
    public static function getCoreStaticControllers() {
        $self = static::_object();
        return $self::$_core_static_controllers;
    }
    
}
?>