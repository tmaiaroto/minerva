<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
*/
use \lithium\data\Connections;

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
    'development' => $development,
    'test' => array('type' => 'database', 'adapter' => 'MongoDb', 'database' => 'minerva_test', 'host' => 'localhost')
));
//Connections::add('test', array('type' => 'database', 'adapter' =>  'MongoDb', 'database' => 'test', 'host' => 'localhost'));
?>