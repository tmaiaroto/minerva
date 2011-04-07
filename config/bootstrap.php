<?php
use lithium\core\Libraries;

// Add a minerva_models type
Libraries::paths(array('minerva_models' => array(
		'{:library}\minerva\models\{:name}',
		// '{:library}\extensions\models\{:class}\{:name}', // needed?
	)
));

// Set the date so we don't get any warnings (should be in your php.ini)
$tz = ini_get('date.timezone');
if(!$tz) {
	$tz = 'UTC';
}
date_default_timezone_set($tz); 

require __DIR__ . '/bootstrap/connections.php';

require __DIR__ . '/bootstrap/session.php';

require __DIR__ . '/bootstrap/auth.php';

// This file sets some filters required for the CMS.
require __DIR__ . '/bootstrap/minerva_bootstrap.php';

// This sets up minerva's access system. Don't use it if you don't want.
//require __DIR__ . '/bootstrap/access.php';
?>