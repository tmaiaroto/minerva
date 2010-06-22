<?php
namespace app\models;

class Block extends \lithium\data\Model {
	
	public static $fields = array(
			'url' => array('label' => 'URL'), 
			'title' => array('label' => 'Title'),		
			'content' => array('label' => 'Block Content', 'type' => 'textarea', 'after' => '(you may use html code)'),	
			'created' => array('type' => 'hidden', 'label' => false), 
			'modified' => array('type' => 'hidden', 'label' => false)
	);
	
	// Blocks may not get rendered...but maybe keep for a "preview"...or maybe they do render with the block helper's "ajax" method
	public $renderOptions = array(
		'template' => 'read', 
		'type' => 'html', 
		'layout' => 'default', 
		'library' => null
	);
	
	// I get overwritten (find conditions for index method) and only live here to catch errors in case I'm not set.
	public $indexFindConditions = array();
	
	// Ensure any plugin fields are bridged when the model (and plugin model which extends this model) is instantiated.
	public function __construct() {
		static::setFields();
	}
	
	/**
	 * Plugin Field Bridge
	 * --------------------
	 * By defining public static $fields in your plugin's Page model, you can extend the fields on the Page model.
	 * Note: You can't override the fields defined above. 
	 *
	*/
	public static function setFields() {
		return self::$fields += static::$fields;
	}
	
	/** 
	 * Plugin Data Bridges
	 * --------------------
	 * You can send extra data from the plugin to each method within the BlocksController so that it becomes available 
	 * for use in the view templates. In each model class, define the appropriate method and return data of any type.
	 * The data returned becomes available as $plugin_data in each view template.
	 *
	*/
	
	// Gets data from setReadDataBridge() method here or in from other hook classes for use in the blocks read method.	
	public function setReadDataBridge() {	
		return;
	}	
	// For the create method
	public function setCreateDataBridge() {		
		return;
	}	
	// For the update method
	public function setUpdateDataBridge() {		
		return;
	}
	
}

/**
 * Filters for the Block model.
 *
*/

// 1. Set the created and modified dates.
Block::applyFilter('save', function($self, $params, $chain) {	
	if (!isset($params['entity']->{Block::key()})) {
		$params['entity']->created = date('Y-m-d h:i:s');
		$params['entity']->modified = date('Y-m-d h:i:s');
	} else {  	
		$params['data']['modified'] = date('Y-m-d h:i:s');  	
	}  
	return $chain->next($self, $params, $chain);
});

?>
