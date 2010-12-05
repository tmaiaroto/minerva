<?php
namespace minerva\util;

use \lithium\security\Auth;
use \lithium\util\Set;
use lithium\core\ConfigException;

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
        
    // Adds an Access rule.
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
    
    /** TODO: add this ?? Will there be arbitrary Access::check() calls??
     * 
     * Maps method calls to validation rule names.  For example, a validation rule that would
     * normally be called as `Validator::rule('email', 'foo@bar.com')` can also be called as
     * `Validator::isEmail('foo@bar.com')`.
     *
     * @param string $method The name of the method called, i.e. `'isEmail'` or `'isCreditCard'`.
     * @param array $args
     * @return boolean
     */
    /*public static function __callStatic($method, $args = array()) {
            if (!isset($args[0])) {
                    return false;
            }
            $args += array(1 => 'any', 2 => array());
            $rule = preg_replace("/^is([A-Z][A-Za-z0-9]+)$/", '$1', $method);
            $rule[0] = strtolower($rule[0]);
            return static::rule($rule, $args[0], $args[1], $args[2]);
    }*/
    
    /**
     * Check the access rules for the given configuration.
     * Similar to validation, new rules can be created with Access::add() to be used in this check.
     *
     * @param string $name The name of the `Access` configuration to check against.
     * @param $rule Array The access rule.
     * @param $options Array
     * @return Array The access response array will hold a boolean if the user is allowed or not as well as other data like a message for why and a login redirect url.
    */
    public function check($name=null, array $rule = array(), array $options = array()) {
        // set by Access::config(); somewhere in the bootstrap most likely. Then use invokeMethod() to get it (setting default values along the way, see _initConfig() above).
        $config = static::invokeMethod('_config', array($name));
        if(!$config) {
            throw new ConfigException("Configuration '{$name}' has not been defined.");
            return $access_response = array('allowed' => false, 'message' => 'You are not permitted to access this area.', 'login_redirect' => '/');
        }
       
        // If there was a login redirect in the rule, use that, otherwise use the configuration's value
        if(isset($rule['login_redirect'])) {
           $config['login_redirect'] = $rule['login_redirect'];
        }
        
        // If there was a custom message in the rule, use that
        if(isset($rule['message'])) {
           $config['message'] = $rule['message'];
        }
        
        // TODO: Should an entire Access object of some sort be return instead of an array??
        $access_response = array(
            'login_redirect' => $config['login_redirect'],
            'allowed' => false,
            'message' => $config['message']
        );
        
        /*
         * If the rule is empty (null, '', 0, false, or array()) then we can only check for a valid user in the config.
         * What's a "valid" user? Basically anything other than empty() ... Auth::check() return false and what can
         * an empty array or string or 0 tell us about the user? If the user data is empty, then we're assuming there
         * is no authenticated user making the request. So it will be denied access.
         *
         * What if the user is retrieved from some other system and all it does is return true/false loggedin or not?
         * Then we want to allow access, even if there's no user record. So we have a very lenient rule called
         * "allowAnyAuthenticated" which is almost as lenient as "allowAll." 
        */
        if((empty($rule)) || (!isset($rule['rule'])) || (empty($rule['rule']))) {
            $rule['rule'] = 'allowAnyAuthenticated';
        }
        
        // Return the results and run any filters that were applied
        $params = compact('config', 'options');
        $_rules = static::$_rules;
        return static::_filter(__FUNCTION__, $params, function($self, $params) use ($_rules, $rule, $access_response) {
           // var_dump($params);
           // var_dump($_rules);
           // var_dump($rule['rule'] . ' requested');
           // var_dump($access_response);exit();
           
            // The added rule closure will be passed the user data
            // TODO: Allow multiple rules to be checked instead of just one.
            if(($rule['rule'] !== false) && (in_array($rule['rule'], array_keys($_rules)))) {
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
                $rule_result = call_user_func($_rules[$rule['rule']], $params['config']['user'], $params['options']);
                $access_response['allowed'] = (is_bool($rule_result)) ? $rule_result:false;
            } else {
                // If the rule requested does not have a function to call, deny access.
                // This also protects against typos or code changes, if it can't be figured out. Deny.
                $access_response['allowed'] = false;
            }
            
            return $access_response;
            
        }, $config['filters']);
    }
    
}
?>