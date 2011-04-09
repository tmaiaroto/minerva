<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\ErrorHandler;
use lithium\action\Response;
use lithium\net\http\Media;
use \lithium\core\Environment;
use lithium\core\Libraries;

ErrorHandler::apply('lithium\action\Dispatcher::run', array(), function($info, $params) {
	$response = new Response(array('request' => $params['request']));
	
	if(isset($params['request']->params['library']) && $params['request']->params['library'] == 'minerva') {
		// the config set with Libraries::add('minerva') can turn off and on errors (in addition to Environment)
		// and set the library to render error templates from
		$config = Libraries::get('minerva');
		$show_errors = isset($config['show_errors']) ? $config['show_errors'] : false;
		$errors_library = isset($config['errors_library']) ? $config['errors_library'] : 'minerva';
		
		// however, the environment dictates the layout and template names
		$errors_template = Environment::get();
		$errors_layout = ($errors_template == 'development') ? 'error_development':'error';
		
		// If the application's environment is set to development or Minerva's config has 'show_errors' set true
		if(Environment::is('development') || $show_errors) {
			Media::render($response, compact('info', 'params'), array(
				// could use the main app's templates by specifying 'library' => 'app' ... 
				'library' => $errors_library,
				'controller' => '_errors',
				'template' => $errors_template,
				'layout' => $errors_layout,
				'request' => $params['request']
			));
		}
	}
	
	return $response;
});
?>