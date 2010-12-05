<?php
/**
 * Authentication & Access Check for All Controllers
 *
 * If this file is included in the bootstrap process, the Minerva Access class
 * will be available for all controllers in the application.
 *
 * 
 *
*/
use \lithium\action\Dispatcher;
use \lithium\action\Response;
use minerva\util\Access;

Dispatcher::applyFilter('_call', function($self, $params, $chain) {
    
    $action = $params['callable']->request->params['action'];
    $controllerClass = get_class($params['callable']);

    if(isset($controllerClass::$access)) {
        /*
         * The * key is a convenience if the controller wishes to apply the same rule(s) to all methods,
         * but if used, the other keys in $access will be ignored. Be careful if setting it on the
         * PagesController unless the login_redirect goes somewhere other than "/" which is the deafult.
         * Otherwise, the user will never be redirected.
         * 
        */
        if(in_array('*', array_keys($controllerClass::$access))) {
            $access = Access::check('minerva', $controllerClass::$access['*']);
            // The access check should always return an array and the 'allowed' key is what we're after
            if($access['allowed'] === false) {
                // can set a flash message here with $access['message']
                return new Response(array('location' => $access['login_redirect']));
            }
        } else {
            foreach($controllerClass::$access as $k => $v) {
                if($action == $k) {
                    $access = Access::check('minerva', $v);
                    // The access check should always return an array and the 'allowed' key is what we're after
                    if($access['allowed'] === false) {
                        // can set a flash message here with $access['message']
                        return new Response(array('location' => $access['login_redirect']));
                    }
                }
            }
        }
    }
    
    return $chain->next($self, $params, $chain);
});
?>