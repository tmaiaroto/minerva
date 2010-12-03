<?php
use \lithium\storage\Session;
use \lithium\security\Auth;

Session::config(array(
	'default' => array('adapter' => 'Php'),
	// HMAC is built into lithium...can also hash your cookies
	/*'strategies' => array(
			'Hmac' => array('secret' => 'your_secret_key')
	)*/
	'flash_message' => array('adapter' => 'Php')
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
	    )	
	)
));

use \lithium\action\Dispatcher;
use \lithium\net\http\Router;
use \lithium\action\Response;

Dispatcher::applyFilter('run', function($self, $params, $chain) {
    
	$blacklist = array(
		// uncomment to restrict access to these urls
		//'pages/index',
		//'pages'
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

?>