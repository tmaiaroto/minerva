<?php
/**
 * Minerva's Access Check Configuration
 *
 * If this file is included in the bootstrap process, the Minerva Access class
 * will be available for all controllers in the application. 
 *
 * This is one way in which Minerva utilizes the Access class.
 * Rules defined in the $access property of any controller will be used.
 * The keys must match action names or be "*" (for all actions).
 * Each rule will have the Request object available to it in order to help
 * the rule determine access.
 *
 * Document level access control? See /minerva/controllers/PagesController.php
 * There is also a $document_access property and other calls to Access::check().
 *
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

Dispatcher::applyFilter('_call', function($self, $params, $chain) {
    
    if(isset($params['callable']::$access)) {
        
        Access::adapter('minerva_access')->add('allowManagers', function($user, $request, $options) {
	    if(($user) && ($user['role'] == 'administrator' || $user['role'] == 'content_editor')) {
		return true;
	    }
	    return false;
	});
	
	// Check for protected "admin" routes. Only administrators and content editors can access these routes.
	if((isset($params['callable']->request->params['admin'])) && ($params['callable']->request->params['admin'] === true)) {
	    $access = Access::check('minerva_access', Auth::check('minerva_user'), $params['callable']->request, array('rules' => array('rule' => 'allowManagers', 'redirect' => '/users/login')));
	    if(!empty($access)) {
		return new Response(array('location' => $access['redirect']));
	    }
	}
        
        /*
         * The * key is a convenience if the controller wishes to apply the same rule(s) to all methods.
         * If used, the other keys in $access will be checked last. Be careful if setting it on the
         * PagesController unless the login_redirect goes somewhere other than "/" which is the deafult.
         * Otherwise, the user will never be redirected. Alternatively, since the Request object is
         * passed in the options, the rules defined can check the request and maybe a URL of "/"
         * can be allowed. That is assuming there should be access to "/" ... That all depends
         * on the application of course.
        */
        if(in_array('*', array_keys($params['callable']::$access))) {
            $access = Access::check('minerva_access', Auth::check('minerva_user'), $params['callable']->request, array('rules' => $params['callable']::$access['*']));
            // The access check should always return an array, if it's not empty then permission was denied
            if(!empty($access)) {
                // can set a flash message here with $access['message']
                return new Response(array('location' => $access['redirect']));
            }
        }
        
        // Loop through the rest of the method rules
        foreach($params['callable']::$access as $k => $v) {
            if(($params['callable']->request->params['action'] == $k) && ($k != '*')) {
                $access = Access::check('minerva_access', Auth::check('minerva_user'), $params['callable']->request, array('rules' => $v));
                // The access check should always return an array, if it's not empty then permission was denied
                if(!empty($access)) {
                    // can set a flash message here with $access['message']
                    return new Response(array('location' => $access['redirect']));
                }
            }
        }
        
    }
    
    return $chain->next($self, $params, $chain);
});
?>