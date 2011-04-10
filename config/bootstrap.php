<?php
use lithium\core\Libraries;

// Add a minerva_models type
Libraries::paths(array('minerva_models' => array(
		'{:library}\minerva\models\{:name}'
	)
));

// Set the date so we don't get any warnings (should be in your php.ini)
$tz = ini_get('date.timezone');
if(!$tz) {
	$tz = 'UTC';
}
date_default_timezone_set($tz); 

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
 * The templates.php file applies a filter on the dispatcher so that a more robust
 * template system is put into place. This is critical to Minerva.
*/
require __DIR__ . '/bootstrap/templates.php';

// This sets up minerva's access system. Don't use it if you don't want.
//require __DIR__ . '/bootstrap/access.php';

/**
 * The dependencies.php file applies a filter on the dispatcher so that anytime Minerva
 * is called, it will check for the proper dependencies. Minvera requires several other
 * Lithium libraries in order to function properly. However, once you have these dependencies,
 * you could safely comment out this line and save the system a few function calls.
*/
require __DIR__ . '/bootstrap/dependencies.php';
?>