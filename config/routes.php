<?php

/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
use lithium\net\http\Router;
use lithium\core\Environment;
use lithium\action\Response;
use lithium\core\Libraries;

/**
 * Set the environment from Minerva's config.
 * @todo: check usage of $minerva_config vs. $config!
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
$route_basic = "{$base}/plugin/{:plugin}";
$route_basic_admin = $route_basic . "/{:admin:$admin_prefix}";

$routePatternStrd = "/{:controller}/{:action}";
$routePatternExt = "/{:controller}/{:action}/{:args}";

$routePatternPaginate = "/page:{:page:[0-9]+}/limit:{:limit:[0-9]+}";

Router::connect($route_basic_admin . $routePatternStrd . $routePatternPaginate, array(
	'admin' => $admin_prefix,
	'library' => 'minerva',
	'controller' => 'pages'
));
Router::connect($route_basic_admin . $routePatternExt . $routePatternPaginate, array(
	'admin' => $admin_prefix,
	'library' => 'minerva',
	'controller' => 'pages'
));
Router::connect($route_basic_admin, array(
	'library' => 'minerva',
	'controller' => 'pages',
	'action' => 'index'
));
Router::connect($route_basic_admin . $routePatternExt, array(
	'library' => 'minerva'
));

// Non-Admin Plugin Routes
Router::connect($route_basic . $routePatternStrd . $routePatternPaginate, array(
	'library' => 'minerva',
	'controller' => 'pages'
));
Router::connect($route_basic . $routePatternExt . $routePatternPaginate, array(
	'library' => 'minerva',
	'controller' => 'dashboards'
));
Router::connect($route_basic, array(
	'library' => 'minerva',
	'controller' => 'pages',
	'action' => 'view',
	'home'
));
Router::connect($route_basic . $routePatternExt, array('library' => 'minerva'));


// Static Pages
// first, "/minerva/"
Router::connect("{$base}", array(
	'library' => 'minerva',
	'controller' => 'pages',
	'action' => 'view',
	'home'
));
// then "/minerva/page/xxxx"
Router::connect("{$base}/page/{:args}", array(
	'library' => 'minerva',
	'controller' => 'pages',
	'action' => 'view'
));
// and "/minerva/admin/page/xxxx"

$routePatternAdmin = "{$base}/{:admin:$admin_prefix}";
Router::connect($routePatternAdmin . "/page/{:args}", array(
	'admin' => 'admin',
	'library' => 'minerva',
	'controller' => 'pages',
	'action' => 'view'
));
// and just "/minerva/admin"
Router::connect($routePatternAdmin, array(
	'admin' => 'admin',
	'library' => 'minerva',
	'controller' => 'pages',
	'action' => 'view',
	'home'
));

// Pagination
Router::connect($routePatternAdmin . $routePatternStrd . $routePatternPaginate, array(
	'library' => 'minerva'
));
Router::connect($routePatternAdmin . $routePatternExt . $routePatternPaginate, array(
	'library' => 'minerva'
));

// Default Routes
Router::connect($routePatternAdmin . $routePatternExt, array(
	'admin' => $admin_prefix,
	'library' => 'minerva'
));

// Also accept a "url" key; all documents in Minerva have an "url"
Router::connect($routePatternAdmin . $routePatternStrd . "/{:url}", array(
	'library' => 'minerva'
));

// Public, non-admin default
Router::connect("{$base}" . $routePatternExt, array(
	'library' => 'minerva'
));

// Accept a "url" key here too
Router::connect("{$base}" . $routePatternStrd . "/{:url}", array(
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