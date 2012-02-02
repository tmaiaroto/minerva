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
		if (!$user) {
			$minerva_config = Libraries::get('minerva');

			// IF we are using Facebook, then let's try to get the Facebook session information and save it to our Minerva session.
			// Also, if this is the Facebook user's first time to the site, let's put them into Minerva's system as a user.
			if (isset($minerva_config['facebook'])) {
				$facebook_config = Libraries::get('li3_facebook');
				if ($facebook_config) {
					$session = FacebookProxy::getSession();
					$uid = null;
					// Session based API call. Get the Facebook user id.
					if ($session) {
						try {
							$uid = FacebookProxy::getUser();
						} catch (Exception $e) {
							error_log($e);
						}
					}

					// If $uid is set, then write the Facebook session to the Minerva user's session.
					// NOTE: If there is no $uid, then the Facebook login failed! But not to worry,
					// the user won't get a session here in Minerva either if that's the case.
					if (!empty($uid)) {
						// handle_facebook_user() is going to save any new user to Minerva's database.
						// However, it will only save what can legally be saved in accordance with Facebook's terms.
						// That's basically a user id, but we can store our own information like created, last login, etc.
						$user_data = User::handle_facebook_user($uid);
						if ($user_data) {
							$user_data['facebook_session'] = $session;
							Auth::set('minerva_user', $user_data);
						}
					}
				}
			}
		}

		// TODO:
		// We could go on to check other authentication services here...Like maybe allow Twitter logins?

		return $user;
	}
}

?>