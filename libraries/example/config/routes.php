<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
use \lithium\net\http\Router;


Router::connect('/example', array('plugin' => 'example', 'controller' => 'example.examples', 'action' => 'view'));
Router::connect('/example/{:controller}/{:action}/{:args}', array(
	'plugin' => 'example', 'controller' => 'example.examples'
));

?>
