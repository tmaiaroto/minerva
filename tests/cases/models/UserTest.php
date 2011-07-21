<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
*/
namespace minerva\tests\cases\models;

use minerva\models\User;
use minerva\tests\mocks\data\MockUser;
use minerva\tests\mocks\data\MockFacebookUser;

class UserTest extends \lithium\test\Unit {
    
    public function setUp() {}

    public function tearDown() {}
    
    public function testDefaultRoles() {
        $this->assertEqual(
            array(
                'administrator' => 'Administrator',
                'content_editor' => 'Content Editor',
                'registered_user' => 'Registered User'
            ), 
            User::user_roles()
        );
    }
    
    public function testLoginRedirect() {
        $this->assertFalse(User::get_login_redirect());
        $this->assertEqual('/home', MockUser::get_login_redirect());
    }
    
    public function testGetName() {
        // from facebook public API
        $this->assertTrue(is_string(User::get_name_from_facebook('100')));
        $this->assertFalse(User::get_name_from_facebook('xxxxx99999'));
        $this->assertFalse(User::get_name_from_facebook(null));
        
        // from local data source
        $this->assertEqual(MockUser::get_name(1), 'John Doe');
        $this->assertTrue(is_string(MockFacebookUser::get_name(1)));
    }
    
}
?>