<?php
/**
 * A very simply var_dump() tool that's "safe" in that it won't print out the information
 * unless on the $_SERVER['HTTP_HOST'] or the $_SERVER['REMOTE_ADDR'] values are in the whitelist.
 *
 * Meaning it won't show on the live site or for any other visitor.
 * This is just a precaution. All debug information (and all calls to this class) should be
 * removed before pushing live in a perfect world.
 *
*/

namespace minerva\util;

class Debug {
    
    static $domain_whitelist = array(
        'minerva.local',
    );
    
    static $ip_whitelist = array(
        '127.0.0.1'
    );
    
    public function dump($data=null, $options=array()) {
        // TODO: var_dump() recursiveness, etc.
        if((in_array($_SERVER['HTTP_HOST'], Debug::$domain_whitelist)) || (in_array($_SERVER['REMOTE_ADDR'], Debug::$ip_whitelist))) {
            var_dump($data);
        }
    }
    
    public function comment($data=null, $options=array()) {
        if((in_array($_SERVER['HTTP_HOST'], Debug::$domain_whitelist)) || (in_array($_SERVER['REMOTE_ADDR'], Debug::$ip_whitelist))) {
            if((is_array($data)) || (is_object($data))) {
                echo '<!-- ';
                print_r($data);
                echo ' -->';
            } else {
                echo '<!-- ' . $data . ' -->';
            }
        }
    }
    
    
}
?>