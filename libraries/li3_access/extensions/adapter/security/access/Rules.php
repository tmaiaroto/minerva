<?php

namespace li3_access\extensions\adapter\security\access;

use \lithium\util\Set;

class Rules extends \lithium\core\Object {

    // Rules are closures that must return true or false
    protected static $_rules = array();
    
    // Set some default rules to use
    public static function __init() {
	self::$_rules = array(
	    'allowAll' => function() {
		return true;
	    },
	    'denyAll' => function() {
		return false;  
	    },
	    'allowAnyUser' => function($user) {
		return (!empty($user)) ? true:false;
	    },
	    'allowIp' => function($user, $request, $options) {
		$options += array('ip' => false);
		return $_SERVER['REMOTE_ADDR'] == $options['ip'];
	    }
	);
    }
    
    /**
     * The `Rules` adapter will use check to test the provided data
     * against a number of given rules. Extra data that may be required
     * to make an informed decision about access can be passed in the
     * $options array. This extra data will vary from app to app and rules
     * will need to be added to handle it. The default rules assume some
     * general cases and more can be added or passed directly to this method.
     *
     * @param mixed $user The user data array that holds all necessary information about
     *        the user requesting access. Or false (because Auth::check() can return false).
     * @param object $request The Lithium Request object.
     * @param array $options An array of additional options.
     * @return Array An empty array if access is allowed and an array with reasons for denial if denied.
    */
    public function check($user, $request, array $options = array()) {
	$options += array('rules' => array());
	if(empty($options['rules'])) {
	   return array('rule' => false, 'message' => $options['message'], 'redirect' => $options['redirect']); 
	}
	
	// If a single rule was passed, wrap it in an array so it can be iterated as if there were multiple
	$rules = (isset($options['rules']['rule'])) ? array($options['rules']):$options['rules'];
	
	$access_response = array();
	
	// Loop through all the rules. They must all pass.
	foreach($rules as $rule) {
	    // make sure the rule is set and is a string to check for a closure to call or a closure itself
	    if((isset($rule['rule'])) && ((is_string($rule['rule'])) || (is_callable($rule['rule'])))) {
		
		$rule_result = false;
		// The added rule closure will be passed the user data
		if(in_array($rule['rule'], array_keys(self::$_rules))) {
		    // The rule closure will be passed the user, request and the rule array itself which could contain extra data required by the specific rule.
		    $rule_result = call_user_func(self::$_rules[$rule['rule']], $user, $request, $rule);
		} elseif(is_callable($rule['rule'])) {
		    // The rule can be defined as a closure on the fly, no need to call add()
		    $rule_result = call_user_func($rule['rule'], $user, $request, $rule);
		}
		
		if($rule_result === false) {
		    $access_response['rule'] = $rule['rule'];
		    $access_response['message'] = (isset($rule['message'])) ? $rule['message']:$options['message'];
		    $access_response['redirect'] = (isset($rule['redirect'])) ? $rule['redirect']:$options['redirect'];
		}
		
	    } 
	    
	}
	 
	return $access_response;
    }

    /**
     * Adds an Access rule. This works much like the Validator class.
     * All rules should be anonymous functions and will be passed
     * $user, $request, and $options which will contain the entire
     * rule array which contains its own name plus other data that
     * could be used to determine access.
     *
     * @param string $name The rule name.
     * @param function $rule The closure for the rule, which has to return true or false.
    */
    public static function add($name, $rule = null) {
        if (!is_array($name)) {
            $name = array($name => $rule);
        }
        self::$_rules = Set::merge(self::$_rules, $name);
    }
    
    /**
     * Simply returns the rules that are currently available.
     * Optionally, passing a name will return just that rule
     * or false if it doesn't exist.
     *
     * @param string $name The rule name (optional).
     * @return mixed Either an array of rule closures, a single rule closure, or false.
    */
    public function getRules($name = false) {
	if($name) {
	    return (isset(self::$_rules[$name])) ? self::$_rules[$name]:false;
	}
	return self::$_rules;
    }
    
}
?>