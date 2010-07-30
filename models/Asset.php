<?php

    //TODO
	/// associate to object id then... in mongodb they are unique (completely unique)
        // also need to check things like php.ini settings for max file sizes, etc.
        // figure out file mimetype and size restrictions on uploads that are "user" defined in the code (or a settings file/db)
        
namespace minerva\models;

use \lithium\util\Validator;
use lithium\util\Inflector as Inflector;

class Asset extends \lithium\data\Model {
    
    // Use the gridfs in MongoDB
    protected $_meta = array("source" => "fs.files");
    
    // I get appended to with the plugin's Asset model (a good way to add extra meta data).
    public static $fields = array(
	// 'url' => array('label' => 'URL'),  ?? pretty urls for download?
	'file_name' => array('type' => 'hidden'),
	'file' => array('label' => 'Profile Image', 'type' => 'file'),
	'created' => array('type' => 'hidden', 'label' => false), 
	'modified' => array('type' => 'hidden', 'label' => false)
    );
    
    public static $validate = array(        
    );

    public static function __init() {
        self::$fields += static::$fields;
        self::$validate += static::$validate;
        
        parent::__init();
    }
}

/* FILTERS
 *
 * Filters must be set down here outside the class because of the class extension by libraries.
 * If the filter was applied within __init() it would run more than once.
 *
*/

// TODO: get checks in for file size, server errors/limits, etc.
Asset::applyFilter('save', function($self, $params, $chain) {
    // First, the created and modified dates
    if (!isset($params['entity']->{Asset::key()})) {
      $params['entity']->created = date('Y-m-d h:i:s');
      $params['entity']->modified = date('Y-m-d h:i:s');
    } else {  	
	  $params['data']['modified'] = date('Y-m-d h:i:s');  	
    }
    
    return $chain->next($self, $params, $chain);
});

// Second, let's get the validation rules picked up from our $validate property
Asset::applyFilter('validates', function($self, $params, $chain) {
    $params['options']['rules'] = Asset::$validate;			
    return $chain->next($self, $params, $chain);
});
?>