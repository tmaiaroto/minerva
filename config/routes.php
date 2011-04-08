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
$admin_prefix = isset($config['admin_prefix']) ? $config['admin_prefix'] : 'admin';

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

// for assets within a subdirectory 
Router::connect("{$base}/{:path:js|css|img}/{:sub_dir}/{:file}.{:type}", array(), function($request) {
	$req = $request->params;
	$file = dirname(__DIR__) . "/webroot/{$req['path']}/{$req['sub_dir']}/{$req['file']}.{$req['type']}";

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
Router::connect("{$base}", array('library' => 'minerva', 'controller' => 'pages', 'action' => 'view', 'home'));
// and this is for the other static pages
Router::connect("{$base}/page/{:args}", array('controller' => 'minerva.pages', 'action' => 'view'));

Router::connect("{$base}/admin", array('admin' => true, 'controller' => 'minerva.pages', 'action' => 'view', 'home'));


/**
 * ...and connect the rest of 'Pages' controller's urls.
 * note there's importance with naming the argument "url"
*/
/*
// "view" is static
Router::connect("{$base}/page/{:url}", array('controller' => 'minerva.pages', 'action' => 'view'));
// "read" is from the database
Router::connect("{$base}/pages/read/{:url}", array('controller' => 'minerva.pages', 'action' => 'read'));

// Admin routes for pages controller
Router::connect("{$base}/pages/create/{:document_type}", array(
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
Router::connect("{$base}/pages/index/{:document_type}", array(
    'admin' => true,
    'controller' => 'minerva.pages',
    'action' => 'index',
    'page' => 1, 'limit' => 10,
    'document_type' => '*'
));
Router::connect("{$base}/pages/index/{:document_type}/page:{:page:[0-9]+}", array(
    'admin' => true,
    'controller' => 'minerva.pages',
    'action' => 'index',
    'page' => 1
));
Router::connect("{$base}pages/index/{:document_type}/page:{:page}/limit:{:limit}", array(
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
*/

/**
 * Connect the user stuff
*/
Router::connect('/register', array('controller' => 'users', 'action' => 'register'));
Router::connect('/login', array('controller' => 'users', 'action' => 'login'));
Router::connect('/logout', array('controller' => 'users', 'action' => 'logout'));
// admin routes for users controller

/*
Router::connect('/users/read/{:id}', array(
    'admin' => true,
    'controller' => 'minerva.users',
    'action' => 'read'
));
Router::connect("{$base}/users/create", array(
    'admin' => true,
    'controller' => 'minerva.users',
    'action' => 'create'
));
Router::connect("{$base}/users/create/{:document_type}", array(
    'admin' => true,
    'controller' => 'minerva.users',
    'action' => 'create'
));
Router::connect("{$base}/users/update/{:id}", array(
    'admin' => true,
    'controller' => 'minerva.users',
    'action' => 'update'
));
Router::connect("{$base}/users/delete/{:id}", array(
    'admin' => true,
    'controller' => 'minerva.users',
    'action' => 'delete'
));
// and for index pages
Router::connect("{$base}/{:admin}/users/index/{:document_type}", array(
    'admin' => 'admin',
    'controller' => 'minerva.users',
    'action' => 'index',
    'page' => 1, 'limit' => 10,
    'user_type' => 'all'
));
Router::connect("{$base}/{:admin}/users/index/{:document_type}/page:{:page:[0-9]+}", array(
    'admin' => 'admin',
    'controller' => 'minerva.users',
    'action' => 'index',
    'page' => 1
));
Router::connect("{$base}/{:admin}/users/index/{:document_type}/page:{:page}/limit:{:limit}", array(
    'admin' => true,
    'controller' => 'minerva.users',
    'action' => 'index',
    'page' => 1,
    'limit' => 10
));
Router::connect("{$base}/{:admin}/users", array(
    'admin' => 'admin',
    'controller' => 'minerva.users',
    'action' => 'index'
));
Router::connect("{$base}/users/{:action}/{:args}", array(
    'admin' => false,
    'controller' => 'minerva.users'
));
*/

/**
 * Connect the static blocks
*/
/*
Router::connect("{$base}/block/{:args}", array(
    //'admin' => true,
    'controller' => 'minerva.blocks',
    'action' => 'view'
));
// the rest for blocks, admin stuff
Router::connect("{$base}/blocks/read/{:url}", array(
    'admin' => true,
    'controller' => 'minerva.blocks',
    'action' => 'read'
));
Router::connect("{$base}/blocks/create/{:document_type}", array(
    'admin' => true,
    'controller' => 'minerva.blocks',
    'action' => 'create'
));
Router::connect("{$base}/blocks/update/{:url}", array(
    'admin' => true,
    'controller' => 'minerva.blocks',
    'action' => 'update'
));
Router::connect("{$base}/blocks/delete/{:id}", array(
    'admin' => true,
    'controller' => 'minerva.blocks',
    'action' => 'delete'
));
// and for index pages
Router::connect("{$base}/blocks/index/{:document_type}", array(
    'admin' => true,
    'controller' => 'minerva.blocks',
    'action' => 'index',
    'page' => 1,
    'limit' => 10
));
Router::connect("{$base}/blocks/index/{:document_type}/page:{:page:[0-9]+}", array(
    'admin' => true,
    'controller' => 'minerva.blocks',
    'action' => 'index',
    'page' => 1
));
Router::connect("{$base}/blocks/index/{:document_type}/page:{:page}/limit:{:limit}", array(
    'admin' => true,
    'controller' => 'minerva.blocks',
    'action' => 'index',
    'page' => 1,
    'limit' => 10
));
Router::connect("{$base}/blocks/{:action}/{:args}", array(
    'admin' => true,
    'controller' => 'minerva.blocks'
));


*/

// Default blanket admin rules

Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/{:action}", array(
    'library' => 'minerva'
));
Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/{:action}/{:document_type}", array(
    'library' => 'minerva'
));

// read actions with both :id and :url
Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/{:action}/{:id}", array(
    'library' => 'minerva'
));
Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/{:action}/{:url}", array(
    'library' => 'minerva'
));



Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/index/{:document_type}", array(
    'admin' => 'admin',
    'action' => 'index',
    'page' => 1, 'limit' => 10,
    'document_type' => 'all'
));
Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/index/{:document_type}/page:{:page:[0-9]+}", array(
    'admin' => 'admin',
    'controller' => 'minerva.users',
    'action' => 'index',
    'page' => 1
));
Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/index/{:document_type}/page:{:page}/limit:{:limit}", array(
    'admin' => 'admin',
    'action' => 'index',
    'page' => 1,
    'limit' => 10
));



Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/{:action}/{:args}", array(
    'admin' => 'admin',
    'library' => 'minerva'
));


// non admin
Router::connect("{$base}/{:controller}/{:action}", array(
    'library' => 'minerva'
));
Router::connect("{$base}/{:controller}/{:action}/{:url}", array(
    'library' => 'minerva'
));
Router::connect("{$base}/{:controller}/{:action}/{:id}", array(
    'library' => 'minerva'
));
Router::connect("{$base}/{:controller}/{:action}/{:args}", array(
    'library' => 'minerva'
));

/**
 * Connect the testing routes.
 */
//if (!Environment::is('production')) {
	Router::connect('/test/{:args}', array('controller' => '\lithium\test\Controller'));
	Router::connect('/test', array('controller' => '\lithium\test\Controller'));
//}

?>