<?php
namespace minerva\util;

use \lithium\util\Set;
use \lithium\core\ConfigException;

class Access extends \lithium\core\Adaptable {
    
    protected static $_configurations = array();
    
    protected static $_rules = array();
    
    public static function __init() {
        $class = get_called_class();
        static::$_methodFilters[$class] = array();
        
        static::$_rules = array(
            'allowAll' => function() {
                return true;
            },
            'denyAll' => function() {
                return false;  
            },
            'allowAnyAuthenticated' => function($user) {
                if(!empty($user)) {
                    return true;
                }
                return false;
            }
        );        
    }
    
    // Set defaults if left out of the configuration
    protected static function _initConfig($name, $config) {
        $defaults = array(
            'message' => 'You are not permitted to access this area.',
            'login_redirect' => '/',
            'user' => array()
        );
        
        $config = parent::_initConfig($name, $config) + $defaults;
        return $config;
    }
        
    /*
     * Adds an Access rule. This works much like the Validator class.
     * All rules should be anonymous functions and will be passed
     * $user, $request, and $options which will contain any data
     * passed to the check() call's $options argument.
    */
    public static function add($name, $rule = null, array $options = array()) {
        if (!is_array($name)) {
            $name = array($name => $rule);
        }
        static::$_rules = Set::merge(static::$_rules, $name);

        if (!empty($options)) {
            $options = array_combine(array_keys($name), array_fill(0, count($name), $options));
            static::$_options = Set::merge(static::$_options, $options);
        }
    }
    
    /**
     * Check the access rules for the given configuration.
     * Similar to validation, new rules can be created with Access::add() to be used in this check.
     *
     * NOTE: It's important to key the data in the $options array.
     * If it's keyed as "user" it will replace the user data that comes from the config and is sent
     * to each rule. If there's a "request" key, it will be passed as the request to the rule.
     * Rules will be able to make checks against $user and $request if available. It's also possible
     * that those value are not always available.
     * 
     * @param string $name The name of the `Access` configuration to check against.
     * @param $rule Array The access rule.
     * @param $options Array Data that will be available to each rule closure that can be useful in determining access.
     * @return Miaxed Boolean true if access is permitted, array if access is denied. The array will contain a redirect url along with a message for why access was denied.
    */
    public function check($name=null, array $rules = array(), array $options = array()) {
        // set by Access::config(); somewhere in the bootstrap most likely. Then use invokeMethod() to get it (setting default values along the way, see _initConfig() above).
        $config = Access::invokeMethod('_config', array($name));
        //$config = static::invokeMethod('_config', array($name));
        if(!$config) {
            throw new ConfigException("Configuration '{$name}' has not been defined.");
            return $access_response = array('allowed' => false, 'message' => 'You are not permitted to access this area.', 'login_redirect' => '/');
        }
        
        // Return the results and run any filters that were applied
        $params = compact('config', 'options');
        
        // static can't be used if calling Access::check() from a controller.
        // $_rules = static::$_rules;
        // return static::_filter(__FUNCTION__, $params, function($self, $params) use ($_rules, $rule, $access_response) {
        
        $_rules = Access::$_rules;
        return Access::_filter(__FUNCTION__, $params, function($self, $params) use ($_rules, $rules) {
           // var_dump($params);
           // var_dump($_rules); 
           // var_dump($rules);
            
            // If a single rule was passed, wrap it in an array so it can be iterated as if there were multiple
            if(isset($rules['rule'])) {
                $rules = array(
                    $rules
                );
            }
        
            foreach($rules as $rule) {
                // make sure the rule is set and is a string to check for a closure to call or a closure itself
                if((isset($rule['rule'])) && ((is_string($rule['rule'])) || (is_callable($rule['rule'])))) {
                    
                    // the user data is likely to come from the configuration, but may be overridden by options on each check() call
                    if(!isset($params['options']['user'])) {
                        $params['options']['user'] = $params['config']['user'];
                    }
                    
                    // the request is not always available, but is always passed as an argument
                    if(!isset($params['options']['request'])) {
                        $params['options']['request'] = false;
                    }
                
                    // The added rule closure will be passed the user data
                    if(in_array($rule['rule'], array_keys($_rules))) {
                        /*
                         * The rule closure will be passed the user and options passed to check()
                         * The user could likely contain a user record.
                         * The options passed could contain extra data useful in evaluating access.
                         * For example, if Access::check() was called after a Model::find() call,
                         * then the options may contain a record from the database that can be
                         * factored into the decision to allow access or not. For instance, maybe
                         * users can only read records that they created.
                         * This allows the Access class to protect not just controller methods, but
                         * also protect very specific records.
                         * 
                         * TODO: what if it was passed the request object too maybe??
                        */
                        $rule_result = call_user_func($_rules[$rule['rule']], $params['options']['user'], $params['options']['request'], $params['options']);
                        $access_response['allowed'] = (is_bool($rule_result)) ? $rule_result:false;
                    } elseif(is_callable($rule['rule'])) {
                        // The rule can be defined as a closure on the fly, no need to call add()
                        $rule_result = call_user_func($rule['rule'], $params['options']['user'], $params['options']['request'], $params['options']);
                        $access_response['allowed'] = (is_bool($rule_result)) ? $rule_result:false;
                    } else {
                        // If the rule requested does not have a function to call, deny access.
                        // This also protects against typos or code changes, if it can't be figured out. Deny.
                        $access_response['allowed'] = false;
                    }
                    
                } else {
                    // If the rule wasn't set or was empty, deny access.
                    $access_response['allowed'] = false;
                }
                
                // Now that we have true/false, set the rest of the response
                $access_response['name'] = $rule['rule'];
                $access_response['message'] = (isset($rule['message'])) ? $rule['message']:$params['config']['message'];
                $access_response['login_redirect'] = (isset($rule['login_redirect'])) ? $rule['login_redirect']:$params['config']['login_redirect'];
            
                if($access_response['allowed'] === false) {
                    return $access_response;
                }
            }
            
            return true;
            
        }, $config['filters']);
    }
    
}
?>