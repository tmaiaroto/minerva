<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\net\http\Router;

// Route for reading a blog post (note the "url" named parameter)
Router::connect('/blog/read/{:url}', array('controller' => 'pages', 'action' => 'read'));

// Yes, you can render "static" pages from the library as well by using the "view" action,
// just ensure "library" is set. Templates from: /libraries/blog/views/pages/static/template-name.html.php
Router::connect('/blog/view/{:url}', array('controller' => 'pages', 'action' => 'view', 'library' => 'blog'));

// Route for listing all blog entries
Router::connect('/blog', array('controller' => 'pages', 'action' => 'index', 'library' => 'blog'));

?>