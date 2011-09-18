<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\net\http\Router;
use \lithium\core\Environment;
use lithium\action\Response;
use lithium\core\Libraries;

/**
 * Set the environment from Minerva's config.
 */
$minerva_config = Libraries::get('minerva');
$environment = isset($config['environment']) ? $config['environment'] : 'production';
Environment::set($environment);

/**
 * Uncomment the line below to enable routing for admin actions.
 * @todo Implement me.
 */
// Router::namespace('/admin', array('admin' => true));

$base = MINERVA_BASE_URL;
$admin_prefix = MINERVA_ADMIN_PREFIX;

/**
 * Handles broken URL parsers by matching method URLs with no closing ) and redirecting.
 */
/*
Router::connect("{$base}/{:args}\(", array(), function($request) {
	return new Response(array('location' => "{$request->url})"));
});
*/

// PLUGIN ROUTES
Router::connect("{$base}/plugin/{:plugin}/{:admin:$admin_prefix}/{:controller}/{:action}/page:{:page:[0-9]+}/limit:{:limit:[0-9]+}", array(
    'admin' => $admin_prefix,
    'library' => 'minerva',
    'controller' => 'pages'
));
Router::connect("{$base}/plugin/{:plugin}/{:admin:$admin_prefix}/{:controller}/{:action}/{:args}/page:{:page:[0-9]+}/limit:{:limit:[0-9]+}", array(
    'admin' => $admin_prefix,
    'library' => 'minerva',
    'controller' => 'pages'
));
Router::connect("{$base}/plugin/{:plugin}/{:admin:$admin_prefix}", array('library' => 'minerva', 'controller' => 'pages', 'action' => 'index'));
Router::connect("{$base}/plugin/{:plugin}/{:admin:$admin_prefix}/{:controller}/{:action}/{:args}", array('library' => 'minerva'));

// Non-Admin Plugin Routes
Router::connect("{$base}/plugin/{:plugin}/{:controller}/{:action}/page:{:page:[0-9]+}/limit:{:limit:[0-9]+}", array(
    'library' => 'minerva',
    'controller' => 'pages'
));
Router::connect("{$base}/plugin/{:plugin}/{:controller}/{:action}/{:args}/page:{:page:[0-9]+}/limit:{:limit:[0-9]+}", array(
    'library' => 'minerva',
    'controller' => 'dashboards'
));
Router::connect("{$base}/plugin/{:plugin}", array('library' => 'minerva', 'controller' => 'pages', 'action' => 'view', 'home'));
Router::connect("{$base}/plugin/{:plugin}/{:controller}/{:action}/{:args}", array('library' => 'minerva'));


// Static Pages
// first, "/minerva/"
Router::connect("{$base}", array('library' => 'minerva', 'controller' => 'pages', 'action' => 'view', 'home'));
// then "/minerva/page/xxxx"
Router::connect("{$base}/page/{:args}", array('library' => 'minerva', 'controller' => 'pages', 'action' => 'view'));
// and "/minerva/admin/page/xxxx"
Router::connect("{$base}/{:admin:$admin_prefix}/page/{:args}", array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'pages', 'action' => 'view'));
// and just "/minerva/admin"
Router::connect("{$base}/{:admin:$admin_prefix}", array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'pages', 'action' => 'view', 'home'));

// Pagination
Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/{:action}/page:{:page:[0-9]+}/limit:{:limit:[0-9]+}", array(
    'library' => 'minerva'
));
Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/{:action}/{:args}/page:{:page:[0-9]+}/limit:{:limit:[0-9]+}", array(
    'library' => 'minerva'
));

// Default Routes
Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/{:action}/{:args}", array(
	'admin' => $admin_prefix,
    'library' => 'minerva'
));

// Also accept a "url" key; all documents in Minerva have a "url"
Router::connect("{$base}/{:admin:$admin_prefix}/{:controller}/{:action}/{:url}", array(
    'library' => 'minerva'
));

// Public, non-admin default
Router::connect("{$base}/{:controller}/{:action}/{:args}", array(
    'library' => 'minerva'
));

// Accept a "url" key here too
Router::connect("{$base}/{:controller}/{:action}/{:url}", array(
    'library' => 'minerva'
));

///**
// * Connect the testing routes.
// */
///* TODO: specific minerva test dashboard?
////if (!Environment::is('production')) {
//	Router::connect('/test/{:args}', array('controller' => '\lithium\test\Controller'));
//	Router::connect('/test', array('controller' => '\lithium\test\Controller'));
////}
//*/
?>