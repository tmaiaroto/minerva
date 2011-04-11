<?php
/**
 * Minerva's Access Check Configuration
 *
 * If this file is included in the bootstrap process, the Minerva Access class
 * will be available for all controllers in the application. 
 *
 * This is one way in which Minerva utilizes the Access class.
 * Rules defined in the $access property of any controller extending
 * the MinervaController will be used. The keys must match action names.
 * Each rule will have the Request object available to it in order to help
 * the rule determine access.
 *
 * Document level access control? See /minerva/controllers/PagesController.php
 * The $access property has a "document" key that determines document level
 * access and MinervaController calls Access::check() in its getDocument() method.
 *
 * Of course, alternatively, a 3rd party library could use Access in any way it
 * needs and/or use a completely different system for access control.
*/
use \lithium\action\Dispatcher;
use \lithium\action\Response;
use li3_access\security\Access;
use \lithium\security\Auth;
use lithium\core\Libraries;

Access::config(array(
	'minerva_access' => array(
            'adapter' => 'Rules',
            // optional filters applied for each configuration
            'filters' => array(
                /*function($self, $params, $chain) {
                    // Any config can have filters that get applied
                    var_dump('filter on check, applied from Access::confg() in minerva_boostrap.php');
                    exit();
                    return $chain->next($self, $params, $chain);
                }*/
            )
	)
));

// Set some rules to be used from anywhere

// Allow access for users with a role of "administrator" or "content_editor"
Access::adapter('minerva_access')->add('allowManagers', function($user, $request, $options) {
   if(($user) && ($user['role'] == 'administrator' || $user['role'] == 'content_editor')) {
	  return true;
   }
   return false;
});

// Add a base document access rule to check against
Access::adapter('minerva_access')->add('publishStatus', function($user, $request, $options) {
   if($options['document']['published'] === true) {
	  return true;
   }
   if(($user) && ($user['role'] == 'administrator' || $user['role'] == 'content_editor')) {
	  return true;
   }
   return false;
});

/*
Dispatcher::applyFilter('_call', function($self, $params, $chain) {
   // Get some config options (most importantly of all so we know what the admin prefix is)
   $config = Libraries::get('minerva');
   $base = isset($config['url']) ? $config['url'] : '/minerva';
   $admin_prefix = isset($config['admin_prefix']) ? $config['admin_prefix'] : 'admin';
   
   // Get the user (if logged in)
   $user = Auth::check('minerva_user');
   
   // Set these to be a little nicer to work with, giving them default values if not set
   $library = (isset($params['callable']->request->params['library'])) ? $params['callable']->request->params['library']:null;
   $controller = (isset($params['callable']->request->params['controller'])) ? $params['callable']->request->params['controller']:null;
   $action = (isset($params['callable']->request->params['action'])) ? $params['callable']->request->params['action']:null;
   // And set the admin flag as a boolean
   $admin = (isset($params['callable']->request->params['admin']) && $params['callable']->request->params['admin'] == $admin_prefix) ? true:false;

   // TODO: maybe make a more configurable/robust access system for Minerva
   // not just whitelists (because we have a pretty configurable controller action access system already...but user roles)
   $controller_action_whitelist = array(
	  'users.login',
	  'users.logout',
	  'users.register'
   );
   
   
	  // Check for protected "admin" routes. Only administrators and content editors can access these routes.
	  if($admin) {
		 // also make sure this isn't a login or logout page. we don't want to block those
		 if(!in_array($controller . '.' . $action, $controller_action_whitelist)) {
			$access = Access::check('minerva_access', Auth::check('minerva_user'), $params['callable']->request, array('rules' => array('rule' => 'allowManagers', 'redirect' => "$base/$admin_prefix/users/login")));
			if(!empty($access)) {
			   return new Response(array('location' => $access['redirect']));
			}
		 }
	  }
   
   return $chain->next($self, $params, $chain);
});
*/
?>