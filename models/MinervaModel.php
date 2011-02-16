<?php
namespace minerva\models;

use lithium\util\Inflector;

class MinervaModel extends \lithium\data\Model {
    
    protected $_schema = array(
        '_id' => array('type' => 'id', 'form' => array('type' => 'hidden', 'label' => false)),
        'created' => array('type' => 'date', 'form' => array('type' => 'hidden', 'label' => false)),
	'modified' => array('type' => 'date', 'form' => array('type' => 'hidden', 'label' => false))
    );
    public $search_schema = array();
    public $display_name = 'Model';
    static $document_access = array();
    static $access = array();
    
    public static function __init() {
    /**
	 * The following code will append a library Page model's $_schema and
	 * $validates properites to this Page model. $_schema can never be changed,
	 * only extended. $validates can be changed and extended, but if default
	 * field rules are left out, they will be used from this parent model.
	*/
	$class =  __CLASS__;
	
        /*
        $extended_schema = static::_object()->_schema;
        
	// Loop through and ensure no one forgot to set the form key		
	foreach($extended_schema as $k => $v) {
		$extended_schema[$k] += array('form' => array('position' => 'default'));
	}
	// Append extended schema
	$class::_object()->_schema += $extended_schema;
        */
        
	// Use the library Page model's validation rules combined with the default (but the library gets priority) this way the default rules can be changed, but if left out will still be used (to help make things nicer)
	$class::_object()->validates = static::_object()->validates += $class::_object()->validates;
	// Same for the search schema, the library gets priority, but combine them.
	$class::_object()->search_schema = static::_object()->search_schema += $class::_object()->search_schema;
	
	// Replace any set display name for context
	$class::_object()->display_name = static::_object()->display_name;
	
	// Lock the schema so values that aren't part of it can't be saved to the db.
	self::meta('locked', true);
        
        parent::__init();
    }
    
    /**
     * Get the display name for a model.
     * This helps to add a little bit of context for users.
     * For example, the create action template has a title "Create Page"
     * but if another page type uses that admin template, it would need
     * to be changed to something like "Create Blog Entry" for example.
     * The "display_name" property of each Page model changes that and
     * this method gets the value. Same goes for Users and other models.
     *
     * @return String
    */
    public function display_name() {
	$class =  __CLASS__;
	return $class::_object()->display_name;
    }
    
    /**
     * Returns the search schema for the model.
     * Note: If this model has been extended by another model then
     * the combined schema will be returned if that other model was
     * instantiated. The __init() method handles that.
     *
     * @param $field String The field for which to return the search schema for,
     * 			    if not provided, all fields will be returned
     * @return array
    */
    public function search_schema($field=null) {
	$class =  __CLASS__;
	$self = $class::_object();
	if (is_string($field) && $field) {
	    return isset($self->search_schema[$field]) ? $self->search_schema[$field] : array();
	}
	return $self->search_schema;
    }
    
    /**
     * Returns the proper model class to be using based on request.
     * For example, a PagesController "create" method will want to use the Page model, not this one.
     * While "Page" could be called directly (and should be in that case), it's not always clear which
     * model to use. Take for example a "blog" library that has a Page model. This is the model that
     * needs to be used because it has extra schema defined, etc.
     *
     * This is only the case for a few specific models for Minerva.
     *
     * @param $model_name The model name (should be page, user, or block)
     * @param $library_name The name of the library to search
     * @return class
    */
    public function getMinervaModel($model_name=null, $library_name=null) {
        $class = __CLASS__;
        if(!empty($model_name) && !empty($library_name)) {
            $class = '\minerva\libraries\\' . $library_name . '\models\\' . $model_name;
            $class = (class_exists($class)) ? $class:'\minerva\models\\' . $model_name;
        }
        return (class_exists($class)) ? $class:__CLASS__;
    }
    
}
?>