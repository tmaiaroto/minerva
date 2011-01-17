<?php
/**
 * Minerva's Authentication Configuration
 * In this file you can specifcy the settings for the Auth class.
 * If you need to use a different or additional adapter, you can
 * do so by configuring it here.
 *
*/

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
	'minerva_user' => array(
	    'adapter' => 'Form',
	    'model'  => 'User',
	    'fields' => array('email', 'password'),
	    'scope'  => array('active' => true),
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
?>