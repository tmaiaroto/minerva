<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\data\Connections;

// Connections::add('default', array(
// 	'type' => 'database',
// 	'adapter' => 'MySql',
// 	'host' => 'localhost',
// 	'login' => 'root',
// 	'password' => '',
// 	'database' => 'app_name'
// ));

// Connection information can be set from the main app with Libraries::add('minerva', array('connections' => array(...)));
$production = (isset($minerva_config['connections']['production'])) ? $minerva_config['connections']['production']:array();
$production_defaults = array(
    'type' => 'database',
    'adapter' =>  'MongoDb', 
    'database' => 'minerva', 
    'host' => 'localhost'
);
$production += $production_defaults;

$development = (isset($minerva_config['connections']['development'])) ? $minerva_config['connections']['development']:array();
$development_defaults = array(
    'type' => 'database',
    'adapter' =>  'MongoDb', 
    'database' => 'minerva_dev', 
    'host' => 'localhost'
);
$development += $development_defaults;

Connections::add('default', array(
    'production' => $production,
    'development' => $development
));

Connections::add('test', array('type' =>  'MongoDb', 'database' => 'test', 'host' => 'localhost'));

?>