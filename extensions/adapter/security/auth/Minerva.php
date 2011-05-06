<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace minerva\extensions\adapter\security\auth;

use lithium\security\Auth;
use lithium\core\Libraries;
use li3_facebook\extensions\FacebookProxy;
use lithium\storage\Session;
use minerva\models\User;
use \Exception;

/**
 * Extends Lithium's Form auth adapter and adds a tiny little
 * extra step that will look for a Facebook session and use that
 * to set auth if available.
 *
 * @see lithium\security\auth\adapter\Form
*/

class Minerva extends \lithium\security\auth\adapter\Form {

	/**
	 * Called by the `Auth` class to run an authentication check against a model class using the
	 * credientials in a data container (a `Request` object), and returns an array of user
	 * information on success, or `false` on failure.
	 *
	 * @param object $credentials A data container which wraps the authentication credentials used
	 *               to query the model (usually a `Request` object). See the documentation for this
	 *               class for further details.
	 * @param array $options Additional configuration options. Not currently implemented in this
	 *              adapter.
	 * @return array Returns an array containing user information on success, or `false` on failure.
	 */
	public function check($credentials, array $options = array()) {
		$user = parent::check($credentials, $options);
		
        // If the user didn't sign in using normal form method, try checking for a Facebook session
        if(!$user) {
            $minerva_config = Libraries::get('minerva');
            
            // IF we even are using Facebook
            if(isset($minerva_config['facebook'])) {
				$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
				$minerva_config['facebook']['login_url'] = (isset($minerva_config['facebook']['login_url'])) ? $minerva_config['facebook']['login_url']:array();
				$minerva_config['facebook']['logout_url'] = (isset($minerva_config['facebook']['logout_url'])) ? $minerva_config['facebook']['logout_url']:array('next' => $protocol . $_SERVER['HTTP_HOST'] . MINERVA_BASE_URL . '/users/logout');
				
                $facebook_config = Libraries::get('li3_facebook');
                if($facebook_config) {
                    $session = FacebookProxy::getSession();
                    $uid = null;
                    // Session based API call.
                    if ($session) {
                            // Set the session
                            Session::write('fb_session', $session, array('name' => 'minerva_default'));
                            try {
                                $uid = FacebookProxy::getUser();
                            } catch (Exception $e) {
                                error_log($e);
                            }
                    }
                    
                    // If $uid is set, then write the fb_logout_url session key
                    if (!empty($uid)) {
                        Session::write('fb_logout_url', FacebookProxy::getLogoutUrl($minerva_config['facebook']['logout_url']), array('name' => 'minerva_default'));
                        
                        // Also, set Auth and return the user data
                        $user_data = User::handle_facebook_user($uid);
                        if($user_data) {
                            Auth::set('minerva_user', $user_data);
                        } else {
                            //Auth::clear('minerva_user');
                        }
                        
                    } else {
                        // Else, the user hasn't logged in yet, write the fb_login_url session key
                        Session::write('fb_login_url', FacebookProxy::getLoginUrl($minerva_config['facebook']['login_url']), array('name' => 'minerva_default'));
                        //Auth::clear('minerva_user'); // shouldn't need this, right/
                    }
                    
                }
            }
        }
        
        return $user;
	}

}

?>