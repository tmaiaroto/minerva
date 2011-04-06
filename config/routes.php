<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\net\http\Router;
use \lithium\core\Environment;
use lithium\core\Libraries;
use lithium\action\Response;

/**
 * Uncomment the line below to enable routing for admin actions.
 * @todo Implement me.
 */
// Router::namespace('/admin', array('admin' => true));


$config = Libraries::get('minerva');
$base = isset($config['url']) ? $config['url'] : '/minerva';

/**
 * Handles broken URL parsers by matching method URLs with no closing ) and redirecting.
 */
Router::connect("{$base}/{:args}\(", array(), function($request) {
	return new Response(array('location' => "{$request->url})"));
});

/**
 * Handles Minerva's Assets
 * Path assets for example: /minerva/js/some_javascript.js
 * Also path them like that when using helpers, example: <?=$this->html->script('/minerva/js/some_javascript.js') ;?>
 * This goes for images, javascript, and style sheets.
 * 
*/
Router::connect("{$base}/{:path:js|css|img}/{:file}.{:type}", array(), function($request) {
	$req = $request->params;
	$file = dirname(__DIR__) . "/webroot/{$req['path']}/{$req['file']}.{$req['type']}";

	if (!file_exists($file)) {
		return;
	}

	return new Response(array(
		'body' => file_get_contents($file),
		'headers' => array('Content-type' => str_replace(
			array('css', 'js'), array('text/css', 'text/javascript'), $req['type']
		))
	));
});


/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'view', and we pass a param to select the view file
 * to use (in this case, /app/views/pages/home.html.php)...
*/
Router::connect("{$base}", array('controller' => 'minerva.pages', 'action' => 'view', 'home'));
// and this is for the other static pages
Router::connect("{$base}/page/{:args}", array('controller' => 'minerva.pages', 'action' => 'view'));

Router::connect("{$base}/admin/{:args}", array('admin' => true, 'controller' => 'minerva.pages', 'action' => 'view', 'home'));

/**
 * ...and connect the rest of 'Pages' controller's urls.
 * note there's importance with naming the argument "url"
*/
// "view" is static
Router::connect("{$base}/page/{:url}", array('controller' => 'minerva.pages', 'action' => 'view'));
// "read" is from the database
Router::connect("{$base}/pages/read/{:url}", array('controller' => 'minerva.pages', 'action' => 'read'));

// Admin routes for pages controller
Router::connect("{$base}/pages/create/{:page_type}", array(
    'admin' => true,
    'controller' => 'minerva.pages',
    'action' => 'create'
));
Router::connect("{$base}/pages/update/{:url}", array(
    'admin' => true,
    'controller' => 'minerva.pages',
    'action' => 'update'
));
Router::connect("{$base}/pages/delete/{:url}", array(
    'admin' => true,
    'controller' => 'minerva.pages',
    'action' => 'delete'
));

// and for index pages (note by default it uses a page_type of "all" and is intended to be an admin action)
Router::connect("{$base}/pages/index/{:page_type}", array(
    'admin' => true,
    'controller' => 'minerva.pages',
    'action' => 'index',
    'page' => 1, 'limit' => 10,
    'page_type' => 'all'
));
Router::connect("{$base}/pages/index/{:page_type}/page:{:page:[0-9]+}", array(
    'admin' => true,
    'controller' => 'minerva.pages',
    'action' => 'index',
    'page' => 1
));
Router::connect("{$base}pages/index/{:page_type}/page:{:page}/limit:{:limit}", array(
    'admin' => true,
    'controller' => 'minerva.pages',
    'action' => 'index',
    'page' => 1,
    'limit' => 10
));
Router::connect("{$base}/pages/{:action}/{:args}", array(
    'admin' => true,
    'controller' => 'minerva.pages'
));

/**
 * Connect the user stuff
*/
Router::connect('/register', array('controller' => 'users', 'action' => 'register'));
Router::connect('/login', array('controller' => 'users', 'action' => 'login'));
Router::connect('/logout', array('controller' => 'users', 'action' => 'logout'));
// admin routes for users controller
Router::connect('/users/read/{:id}', array(
    'admin' => true,
    'controller' => 'users',
    'action' => 'read'
));
Router::connect('/users/create', array(
    'admin' => true,
    'controller' => 'users',
    'action' => 'create'
));
Router::connect('/users/create/{:user_type}', array(
    'admin' => true,
    'controller' => 'users',
    'action' => 'create'
));
Router::connect('/users/update/{:id}', array(
    'admin' => true,
    'controller' => 'users',
    'action' => 'update'
));
Router::connect('/users/delete/{:id}', array(
    'admin' => true,
    'controller' => 'users',
    'action' => 'delete'
));
// and for index pages
Router::connect('/users/index/{:user_type}', array(
    'admin' => true,
    'controller' => 'users',
    'action' => 'index',
    'page' => 1, 'limit' => 10,
    'user_type' => 'all'
));
Router::connect('/users/index/{:user_type}/page:{:page:[0-9]+}', array(
    'admin' => true,
    'controller' => 'users',
    'action' => 'index',
    'page' => 1
));
Router::connect('/users/index/{:user_type}/page:{:page}/limit:{:limit}', array(
    'admin' => true,
    'controller' => 'users',
    'action' => 'index',
    'page' => 1,
    'limit' => 10
));
Router::connect('/users', array(
    'admin' => true,
    'controller' => 'users',
    'action' => 'index'
));
Router::connect('/users/{:action}/{:args}', array(
    'admin' => false,
    'controller' => 'users'
));

/**
 * Connect the static blocks
*/
Router::connect('/block/{:args}', array(
    //'admin' => true,
    'controller' => 'blocks',
    'action' => 'view'
));
// the rest for blocks, admin stuff
Router::connect('/blocks/read/{:url}', array(
    'admin' => true,
    'controller' => 'blocks',
    'action' => 'read'
));
Router::connect('/blocks/create/{:block_type}', array(
    'admin' => true,
    'controller' => 'blocks',
    'action' => 'create'
));
Router::connect('/blocks/update/{:url}', array(
    'admin' => true,
    'controller' => 'blocks',
    'action' => 'update'
));
Router::connect('/blocks/delete/{:id}', array(
    'admin' => true,
    'controller' => 'blocks',
    'action' => 'delete'
));
// and for index pages
Router::connect('/blocks/index/{:block_type}', array(
    'admin' => true,
    'controller' => 'blocks',
    'action' => 'index',
    'page' => 1,
    'limit' => 10
));
Router::connect('/blocks/index/{:block_type}/page:{:page:[0-9]+}', array(
    'admin' => true,
    'controller' => 'blocks',
    'action' => 'index',
    'page' => 1
));
Router::connect('/blocks/index/{:block_type}/page:{:page}/limit:{:limit}', array(
    'admin' => true,
    'controller' => 'blocks',
    'action' => 'index',
    'page' => 1,
    'limit' => 10
));
Router::connect('/blocks/{:action}/{:args}', array(
    'admin' => true,
    'controller' => 'blocks'
));

/**
 * Connect the testing routes.
 */
//if (!Environment::is('production')) {
	Router::connect('/test/{:args}', array('controller' => '\lithium\test\Controller'));
	Router::connect('/test', array('controller' => '\lithium\test\Controller'));
//}


//Router::connect('/block', array('library' => 'blocks', 'controller' => 'pages', 'action' => 'view'));
/**
 * Finally, connect the default routes.
 */
Router::connect('/{:controller}/{:action}/{:id:[0-9]+}.{:type}', array('id' => null));
Router::connect('/{:controller}/{:action}/{:id:[0-9]+}');
Router::connect('/{:controller}/{:action}/{:args}');
?>