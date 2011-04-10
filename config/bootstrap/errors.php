<?php
/**
 * Handles errors for Minerva.
 * By default errors are turned on and will show 404, 500, etc.
 * These errors under a "production" environment will not show any stack traces, etc.
 * Further configuration for where these templates are rendered can be provided with
 * Libraries::add('minerva', array(...))
 *
 * Options currently are:
 * 'show_errors'
 *     This is true by default and if set to false, nothing will show at all (white pages).
 *     You might as well comment out the line to include errors in Minerva's bootstrap process at that rate,
 *     but you would of course be altering "core" code technically speaking if you did so.
 *
 * 'errors_libray'
 *     This will choose the library to render templates from. So you can make a new library with a new
 *     error layout and template to use; again, avoiding the need to touch "core" code.
 *     Error layouts and templates are always named for the app environment and are not directly 
 *     configurable from the options.
 *     
 *     The layout will be: "error_<ENVIRONMENT>.html.php" and template will be: "<ENVIRONMENT>.html.php"
 *     
 *     Specifying "app" for the 'errors_library' will look in your main app's views folder for these
 *     templates, otherwise any other library you specify with it defaulting to "minerva".
 *     
 * 'development_errors'
 *     If you have your main app in "production" or some other environment, but still want to see "production"
 *     errors with anything in your app using Minerva, you can set this key to true. This will keep your
 *     app's environment to whatever you want, but still allow you to see "development" errors for Minerva.
 *     This sets the layout and template to "error_development.html.php" and "development.html.php" respectively.
 *     Please note: this does not set your environment to "development" so you will still be using the default
 *     connection setting, etc.
 *
 * This should give enough control over the errors. Of course they can be turned completely off if you want
 * to handle 404's and 500's, etc. differently. The idea is to just be able to have a basic way to handle
 * those sorts of errors in a production application and give more debugging information during development.
 *
 * Of course do all this, while at the same time trying not to effect the primary application if you are using
 * the CMS on top of some other existing application.
 * 
 * If it doesn't suit your needs, simply use something else.
 * 
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
		$show_errors = isset($config['show_errors']) ? $config['show_errors'] : true;
		$errors_library = isset($config['errors_library']) ? $config['errors_library'] : 'minerva';
		$development = isset($config['development_errors']) ? $config['development_errors'] : false;
		
		// the environment dictates the layout and template names or if "development" is set and is true in the minerva config
		$errors_template = ($development === true) ? 'development':Environment::get();
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