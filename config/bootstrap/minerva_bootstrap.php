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
    // (Note: Pages, Users, and Blocks are the only models considered, if any additional are created, the following array must change)
    // TODO: ...which is why... consider going back to a standard field name, it makes for less if thens....but it could create more problems for several reasons when it comes to 3rd party addons...the if thens here guarantee things to a good degree
    
    // this array is defined here and also in MinervaController.php
    // todo: or maybe just put elsewhere; better maintainability.. can't go back to a standard field name once distributed. this is a very critical thing
    $library_fields = array('page_type', 'user_type', 'block_type');
    foreach($library_fields as $field) {
        if(in_array($field, array_keys($params['request']->params))) {
            $library = (isset($params['request']->params[$field])) ? $params['request']->params[$field]:null;
        }
    }
   
    // The admin flag from routes helps give control over the templates to use
    $admin = ((isset($params['request']->params['admin'])) && ($params['request']->params['admin'] == 1 || $params['request']->params['admin'] === true || $params['request']->params['admin'] == 'true')) ? true:false;
    // The layout key from the routes give us even more control, it's the final authority on where to check, but things do cascade down
    $layout = (isset($params['request']->params['layout'])) ? $params['request']->params['layout']:false;
    // Also a template key from the routes again for more control and flexibility
    $template = (isset($params['request']->params['template'])) ? $params['request']->params['template']:false;
    
    // DEFAULTS & MISSING TEMPLATE PAGES
    $params['options']['render']['paths']['layout'] = array(
	LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php',
	LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_missing' . DIRECTORY_SEPARATOR . 'missing_layout.{:type}.php'
    );
    $params['options']['render']['paths']['template'] = array(
	LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . '{:template}.{:type}.php',
	LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_missing' . DIRECTORY_SEPARATOR . 'missing_template.{:type}.php'
    );
    
    /**
     * DEFAULT FOR PAGE/USER/BLOCK TYPES
     * First by default we're going to see if this is even a controller that has a bridge model.
     * $library in this case is NOT the route's "library" key, if provided, it's the library name for when
     * bridging either a page, user, or a block. We want to use templates from that library's views folder.
     * This is not for admin view templates. This is for ex. /minerva/libraries/blog/views/pages/read.html.php
    */
    if((!empty($library)) && (empty($admin))) {
	// Look at a common if the bridge library doesn't have the templates
	array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php');
	array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . '{:template}.{:type}.php');
	// These will be on top of the array so it'll look first for something like: minerva/libraries/blog/views/...
	array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php');
	array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . '{:template}.{:type}.php');
    }
    
    /**
     * 3RD PARTY LIBRARIES
     * If the route passed a "library" key then we're going to render from it's views folder.
     * This is likely for a 3rd party library that is stand alone, it doesn't hook into pages, users, blocks, etc.
     * This is so other applications can be dropped in more easily without template confusion or conflict.
    */
    if(isset($params['request']->params['library'])) {
	array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $params['request']->params['library'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php');
	array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $params['request']->params['library'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . '{:template}.{:type}.php');
    }
    
    /**
     * ADMIN TEMPLATES & CORE
     * If "admin" is set in the route then we will allow 3rd party templates in the common/views/_admin folder,
     * but default back to core. So if an alternative admin interface is desired, then templates need to be
     * created in common/views/_admin/...
     * NOTE: Admin templates are a specific setting from the routes, they are never defaulted to
    */
    if($admin === true) {
	// Core (doubles as admin)
	array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php');
	array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . '{:template}.{:type}.php');
	
	// Common (for when the default admin interface is desired to be changed)
	array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_admin' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php');
	array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_admin' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . '{:template}.{:type}.php');
	
	// 3rd party libraries can also put in an _admin folder under its views folder they will override templates in common if present. They get priority. (easy portability)
	if(!empty($library)) {
	    array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_admin' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php');
	    array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_admin' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . '{:template}.{:type}.php');
	}
    }
    
    /**
     * STATIC VIEWS
     * Special situation; "blocks" and "pages" and "menus" have "static" templates that don't require a datasource.
     * This is only the case for the "view" action on these controllers. First look in the common library and then
     * it's _admin location (admin blocks) and then default back to core. This is so when calling a block or menu
     * or static page, you don't have to specify it's an admin one because the router won't come into play for
     * menus and blocks.
    */
    if(($params['request']->params['action'] == 'view') && ($params['request']->params['controller'] == 'blocks' || $params['request']->params['controller'] == 'pages' || $params['request']->params['controller'] == 'menus')) {
	// redefine the layout and template arrays, so add back the missing template templates
	$params['options']['render']['paths']['layout'] = array(
	    LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php',
	    LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php',
	    LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_missing' . DIRECTORY_SEPARATOR . 'missing_layout.{:type}.php'
	);
	$params['options']['render']['paths']['template'] = array(
	    LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . '{:template}.{:type}.php',
	    LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_missing' . DIRECTORY_SEPARATOR . 'missing_template.{:type}.php'
	);
	// ADMIN STATIC VIEWS
	// Hey, static views can be for just the admin interface as well and those will take priority.
	if($admin === true) {
	    // before looking at the defaults set above for static views, look for layouts in "minerva/libraries/common/views/layouts" and then core "minerva/views/layouts"
	    array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php');
	    array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_admin' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php');
	    
	    // but before that, look in "minerva/libraries/common/views/layouts/static" and "minerva/views/layouts/static" ... we want to give a "static" template priority
	    array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php');
	    array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_admin' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . '{:layout}.{:type}.php');
	    
	    // however, templates always come from a "static" folder unlike layouts
	    array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . '{:template}.{:type}.php');
	    array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '_admin' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . '{:template}.{:type}.php');
	}
    }
    
    /**
     * MANUAL OVERRIDES FROM ROUTES
     * Was the "layout" or "template" key set in the route? Then we're saying to change up the layout path.
     * This allows other libraries to share the layout template from say the "common" library right from the route.
     * Or more importantly, libraries that are for page, user, or block types to share layout templates since they
     * can't touch those controllers. NOTE: This supercedes everything (even static). It is a manual setting
     * in the route that is optional, but we want to obey it.
    */    
    if(!empty($layout)) {
	// Layouts can be borrowed from other libraries, defined like: library.layout_template
	$layout_pieces = explode('.', $layout);
	$layout_library = false;
	if(count($layout_pieces) > 1) {
	    $layout_library = $layout_pieces[0];
	    $layout = $layout_pieces[1];
	}
	array_unshift($params['options']['render']['paths']['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $layout_library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.{:type}.php');
    }
    if(!empty($template)) {
	// Templates can be borrowed from other libraries, defined like: library.template
	$template_pieces = explode('.', $template);
	$template_library = false;
	if(count($template_pieces) > 1) {
	    $template_library = $template_pieces[0];
	    $template = $template_pieces[1];
	}
	array_unshift($params['options']['render']['paths']['template'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $template_library . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '{:controller}' . DIRECTORY_SEPARATOR . $template . '.{:type}.php');
    }
    
    // var_dump($params['options']['render']['paths']); // <--- this is a great thing to uncomment and browse the site for reference
    
    return $chain->next($self, $params, $chain);	
});
?>