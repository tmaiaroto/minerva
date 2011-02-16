<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\net\http\Router;
use \lithium\core\Environment;

/**
 * Uncomment the line below to enable routing for admin actions.
 * @todo Implement me.
 */
// Router::namespace('/admin', array('admin' => true));

/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'view', and we pass a param to select the view file
 * to use (in this case, /app/views/pages/home.html.php)...
*/
Router::connect('/', array('controller' => 'pages', 'action' => 'view', 'home'));
// and this is for the other static pages
Router::connect('/page/{:args}', array('controller' => 'pages', 'action' => 'view'));

Router::connect('/admin/{:args}', array('admin' => true, 'controller' => 'pages', 'action' => 'view', 'home'));

/**
 * ...and connect the rest of 'Pages' controller's urls.
 * note there's importance with naming the argument "url"
*/
// "view" is static
Router::connect('/page/{:url}', array('controller' => 'pages', 'action' => 'view'));
// "read" is from the database
Router::connect('/pages/read/{:url}', array('controller' => 'pages', 'action' => 'read'));

// Admin routes for pages controller
Router::connect('/pages/create/{:page_type}', array(
    'admin' => true,
    'controller' => 'pages',
    'action' => 'create'
));
Router::connect('/pages/update/{:url}', array(
    'admin' => true,
    'controller' => 'pages',
    'action' => 'update'
));
Router::connect('/pages/delete/{:url}', array(
    'admin' => true,
    'controller' => 'pages',
    'action' => 'delete'
));

// and for index pages (note by default it uses a page_type of "all" and is intended to be an admin action)
Router::connect('/pages/index/{:page_type}', array(
    'admin' => true,
    'controller' => 'pages',
    'action' => 'index',
    'page' => 1, 'limit' => 10,
    'page_type' => 'all'
));
Router::connect('/pages/index/{:page_type}/page:{:page:[0-9]+}', array(
    'admin' => true,
    'controller' => 'pages',
    'action' => 'index',
    'page' => 1
));
Router::connect('/pages/index/{:page_type}/page:{:page}/limit:{:limit}', array(
    'admin' => true,
    'controller' => 'pages',
    'action' => 'index',
    'page' => 1,
    'limit' => 10
));
Router::connect('/pages/{:action}/{:args}', array(
    'admin' => true,
    'controller' => 'pages'
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