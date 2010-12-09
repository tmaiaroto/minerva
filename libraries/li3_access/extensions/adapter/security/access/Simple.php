<?php

namespace li3_access\extensions\adapter\security\access;

use \lithium\core\Libraries;
use \lithium\util\Set;

class Simple extends \lithium\core\Object {
    
    /**
     * The `Simple` adapter will just check for user data.
     * It doesn't care about anything else.
     *
     * @param mixed $user The user data array that holds all necessary information about
     *        the user requesting access. Or false (because Auth::check() can return false).
     * @param object $request The Lithium Request object.
     * @param array $options An array of additional options.
     * @return Array An empty array if access is allowed and an array with reasons for denial if denied.
    */
    public function check($user, $request, array $options = array()) {
        if(!empty($user)) {
            return array(); 
        }
        return $options;
    }
    
}
?>