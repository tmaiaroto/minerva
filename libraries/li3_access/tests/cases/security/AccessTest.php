<?php
/**
 * li3_access plugin for Lithium: the most rad php framework.
 *
 * @author        Tom Maiaroto
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
*/

namespace li3_access\tests\cases\security;

use \li3_access\security\Access;

class AccessTest extends \lithium\test\Unit {

    public function setUp() {
        Access::config(array(
            'test_access' => array(
            )
        ));
        
        Access::config(array(
            'test_access_with_filters' => array(
                'filters' => array(
                    function($self, $params, $chain) {
                        // Any config can have filters that get applied
                        var_dump('filter on check, applied from Access::confg() in minerva_boostrap.php');
                        exit();
                        return $chain->next($self, $params, $chain);
                    }
                )
            )
        ));
    }

    public function testCheck() {
        $expected = array('value' => true);
        $result = array('value' => false);
        $this->assertEqual($expected, $result);
    }
    
}    
?>