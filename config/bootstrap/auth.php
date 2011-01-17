<?php
/**
 * Minerva's Authentication Configuration
 * In this file you can specifcy the settings for the Auth class.
 * If you need to use a different or additional adapter, you can
 * do so by configuring it here.
 *
*/
use \lithium\security\Auth;

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
?>