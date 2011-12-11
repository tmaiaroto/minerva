<?php
use lithium\core\Libraries;

// Add a minerva_models type
Libraries::paths(array('minerva_models' => array(
		'{:library}\minerva\models\{:name}'
	)
));

// Set the date so we don't get any warnings (should be in your php.ini)
$tz = ini_get('date.timezone');
if (!$tz) {
	$tz = 'UTC';
}
date_default_timezone_set($tz);

// Get minerva's configuration for various reasons
$minerva_config = Libraries::get('minerva');
$base_url = isset($config['url']) ? $config['url'] : '/minerva';
$admin_prefix = isset($config['admin_prefix']) ? $config['admin_prefix'] : 'admin';
define('MINERVA_BASE_URL', $base_url);
define('MINERVA_ADMIN_PREFIX', $admin_prefix);

/**
 * The error configuration allows you to use the filter system along with the advanced matching
 * rules of the `ErrorHandler` class to provide a high level of control over managing exceptions in
 * your application, with no impact on framework or application code.
 */
require __DIR__ . '/bootstrap/errors.php';

require __DIR__ . '/bootstrap/media.php';

require __DIR__ . '/bootstrap/connections.php';

require __DIR__ . '/bootstrap/session.php';

require __DIR__ . '/bootstrap/auth.php';

/**
 * This sets up minerva's access system.
 * It is optional, if 'minerva_access' is set to false in the minerva library configuration
 * then the default access system will not be used. The system will then rely completely
 * upon 3rd party access control. At that point, in order to protect core actions, filters
 * would be the logical step to take.
 *
 * NOTE: Minerva's core access system is basic, but can be extended by additional access rules.
 * In the very least, it could be used for a "super administrator" role only and an additional
 * access system for every other type of user and need could be put into place.
 * Please take care when disabling even the most basic of security measures.
*/
if (!isset($minerva_config['use_minerva_access']) || $minerva_config['use_minerva_access'] === true) {
    require __DIR__ . '/bootstrap/access.php';
}

/**
 * The templates.php file applies a filter on the dispatcher so that a more robust
 * template system is put into place. This is critical to Minerva.
*/
require __DIR__ . '/bootstrap/templates.php';

/**
 * Minerva integrates with Facebook, optionally, but by design.
 * If Minerva's config has a 'facebook' key with 'appId' and 'secret' then it will use the
 * li3_facebook library and it will become a dependency.
*/
require __DIR__ . '/bootstrap/facebook.php';

/**
 * The dependencies.php file applies a filter on the dispatcher so that anytime Minerva
 * is called, it will check for the proper dependencies. Minvera requires several other
 * Lithium libraries in order to function properly. However, once you have these dependencies,
 * you could safely comment out this line and save the system a few function calls.
*/
require __DIR__ . '/bootstrap/dependencies.php';

?>