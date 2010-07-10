<?php
use \lithium\storage\Session;
use \lithium\security\Auth;

Session::config(array(
	'default' => array('adapter' => 'Php'),
	// HMAC is built into lithium...can also hash your cookies
	/*'strategies' => array(
			'Hmac' => array('secret' => 'your_secret_key')
	)*/
));

Auth::config(array(
	'user' => array(
		'adapter' => 'Form',
		'model'  => 'User',
		//'fields' => array('username', 'password'),
		///'scope'  => array('is_active' => 1),
		/*'filters' => array(
			//'password' => 'app\models\User::hashPassword'
		),*/
		'session' => array(
			'options' => array('name' => 'default')
		),
		
	)
));

use \lithium\action\Dispatcher;
use \lithium\net\http\Router;
use \lithium\action\Response;

Dispatcher::applyFilter('run', function($self, $params, $chain) {
	$blacklist = array(
		// uncomment to restrict access to these urls
	//	'pages/index',
	//	'pages'
	);
	//echo '<pre>'; print_r($params['request']->url); echo '</pre>';exit();
	//$matches = in_array(Router::match($params['request']->params, $params['request']), $blacklist);
	//var_dump($params['request']);

	$matches = in_array((string)$params['request']->url, $blacklist);		
	if($matches && !Auth::check('user')) {
	 	return new Response(array('location' => '/users/login'));	 	
	}
	return $chain->next($self, $params, $chain);
});


/*
// While this would be super slick, it only works for two methods because library is set from URL...So why bother?
// IF somehow update, read, and delete can also be set here, then put it back.
// Would put this in another file too if it was going to be used.
Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
	// If we loaded the Pages controller
	if(($params['params']['controller'] == 'Pages') && (isset($params['params']['args'][0]))) {
		switch($params['params']['action']) {
			// update, read, and delete based on database record, so they must be instantiated in the PagesController
			case 'create':
			case 'index':
				// "read" is not here because the library's Page model will be loaded if the record has a library set
				$class = '\app\libraries\\'.$params['request']->params['args'][0].'\models\Page';
				// Don't load the model if it doesn't exist
				if(class_exists($class)) {
					$LibraryPage = new $class();
				}
			break;
			default:
				// Don't load the library's Page model by default
			break;
		}
	}
	
	return $chain->next($self, $params, $chain);	
});
*/
?>
