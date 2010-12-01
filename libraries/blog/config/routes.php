<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\net\http\Router;

// Example route for reading a blog post (note the "url" named parameter)
Router::connect('/blog/read/{:url}', array('controller' => 'pages', 'action' => 'view'));

Router::connect('/blog', array('controller' => 'pages', 'action' => 'index', 'library' => 'blog'));

?>