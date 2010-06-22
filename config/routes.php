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

/**
 * ...and connect the rest of 'Pages' controller's urls.
 */
Router::connect('/page/{:args}', array('controller' => 'pages', 'action' => 'view'));

/**
 * Connect the static blocks
 */
Router::connect('/block/{:args}', array('controller' => 'blocks', 'action' => 'view'));

/**
 * Connect the testing routes.
 */
if (!Environment::is('production')) {
	Router::connect('/test/{:args}', array('controller' => '\lithium\test\Controller'));
	Router::connect('/test', array('controller' => '\lithium\test\Controller'));
}


// connect route for pages
Router::connect('/pages/index/{:args}/page:{:page:[0-9]+}', array(
	'controller' => 'pages', 'action' => 'index', 'page' => 1
));
Router::connect('/pages/index/{:args}/page:{:page}/limit:{:limit}', array(
	'controller' => 'pages', 'action' => 'index', 'page' => 1, 'limit' => 10
));

//Router::connect('/block', array('library' => 'blocks', 'controller' => 'pages', 'action' => 'view'));
/**
 * Finally, connect the default routes.
 */
Router::connect('/{:controller}/{:action}/{:id:[0-9]+}.{:type}', array('id' => null));
Router::connect('/{:controller}/{:action}/{:id:[0-9]+}');
Router::connect('/{:controller}/{:action}/{:args}');
?>