<?php
/**
 * Authentication & Access Check for All Controllers
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
use minerva\util\Access;


use li3_access\security\Access as LiAccess;

LiAccess::config(array(
	'rulebased' => array(
            'adapter' => 'Rules',
            // a true setting is like saying EVERYONE, every request is a logged in user. but there's no data so any check for things like "group" etc. in the rules wouldn't work (null, false, '', 0, or array() would say the user is NOT logged in and if there was an empty rule, access would be denied - restrictive by default)
            //'user' => true,
            // optional filters applied for each configuration
            'filters' => array(
                /*function($self, $params, $chain) {
                    // Any config can have filters that get applied
                    var_dump('filter on check, applied from Access::confg() in minerva_boostrap.php');
                    exit();
                    return $chain->next($self, $params, $chain);
                }*/
            ),
	    //'user' => Auth::check('user')
	    //'login_redirect'  => '/users/login',
	)
));

Dispatcher::applyFilter('_call', function($self, $params, $chain) {
    
    if(isset($params['callable']::$access)) {
        
        // LiAccess::check('configname', $user_array, $request, $options_array)
       /* LiAccess::adapter('rulebased')->add('testDeny', function($user, $request, $options) {
	    return false;
	});
	
        
        $rules = array(
            array('rule' => 'testDeny', 'message' => 'Access denied.'),
            array('rule' => 'allowAnyUser', 'message' => 'You must be logged in.'),
            array('rule' => 'allowIp', 'message' => 'You can not access this from your location. (IP: ' . $_SERVER['REMOTE_ADDR'] . ')', 'ip' => $_SERVER['REMOTE_ADDR'])
        );
        
        var_dump(LiAccess::check('rulebased', array('username' => 'Tom'), $params['callable']->request, array('rules' => $rules)));
        exit();
       */
        
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
            $access = Access::check('minerva', $params['callable']::$access['*'], array('request' => $params['callable']->request));
            // The access check should always return an array and the 'allowed' key is what we're after
            if($access !== true) {
                // can set a flash message here with $access['message']
                return new Response(array('location' => $access['login_redirect']));
            }
        }
        
        // Loop through the rest of the method rules
        foreach($params['callable']::$access as $k => $v) {
            if(($params['callable']->request->params['action'] == $k) && ($k != '*')) {
                $access = Access::check('minerva', $v, array('request' => $params['callable']->request));
                // The access check should always return an array and the 'allowed' key is what we're after
                if($access !== true) {
                    // can set a flash message here with $access['message']
                    return new Response(array('location' => $access['login_redirect']));
                }
            }
        }
        
    }
    
    return $chain->next($self, $params, $chain);
});
?>