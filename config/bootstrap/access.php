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

Dispatcher::applyFilter('_call', function($self, $params, $chain) {
    
    if(isset($params['callable']::$access)) {
        // TODO: maybe move this to MinervaController and even add an "admin" key to the Controller::$access array for even greater control and flexibility
        // Check for protected "admin" routes. Only administrators and content editors can access these routes.
        if((isset($params['callable']->request->params['admin'])) && ($params['callable']->request->params['admin'] === true)) {
            $access = Access::check('minerva_access', Auth::check('minerva_user'), $params['callable']->request, array('rules' => array('rule' => 'allowManagers', 'redirect' => '/users/login')));
            if(!empty($access)) {
            return new Response(array('location' => $access['redirect']));
            }
        }
    }
    
    return $chain->next($self, $params, $chain);
});
?>