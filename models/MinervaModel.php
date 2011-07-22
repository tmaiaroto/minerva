<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 * 
 * Base model class MinervaModel
 * All Minerva model classes ultimately extend from this class.
 * This class provides various getter and setter methods that will help 3rd party
 * add-ons utilize the core system more easily. Many of these methods also provide
 * helpful insight as to what is going on.
 * 
 * A lot of properties are set in this model to be changed based on the extended
 * model in order to control schema, validation, access rules, URL redirect settings
 * for controller actions, and more.
 * 
*/
namespace minerva\models;

use lithium\util\Inflector;
use lithium\core\Libraries;

class MinervaModel extends \lithium\data\Model {
    
    protected $_schema = array(
        '_id' => array('type' => 'id', 'form' => array('type' => 'hidden', 'label' => false)),
		'url' => array('type' => 'string', 'form' => array('label' => 'Pretty URL', 'wrap' => array('class' => 'minerva_url_input'), 'position' => 'options')),
        'created' => array('type' => 'date', 'form' => array('type' => 'hidden', 'label' => false)),
		'document_type' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'modified' => array('type' => 'date', 'form' => array('type' => 'hidden', 'label' => false))
    );
    public $search_schema = array();
    public $display_name = 'Model';
    static $document_access = array();
    public $access = array();
	public $action_redirects = array();
	public $url_field = null;
	public $url_separator = '-';
	protected $_meta = array(
		'locked' => true
	);
	
    public static function __init() {
		// The following code will append a library Page model's $_schema and
		// $validates properites to this Page model. $_schema can never be changed,
		// only extended. $validates can be changed and extended, but if default
		// field rules are left out, they will be used from this parent model.
		$class =  __CLASS__;
		
		// Use the library Page model's validation rules combined with the default (but the library gets priority) this way the default rules can be changed, but if left out will still be used (to help make things nicer)
		// $class::_object()->validates = static::_object()->validates += $class::_object()->validates;
		// Now going to just override... The way things were extended, it somehow picked up validates property from other models...weird.
		// All this means is care needs to be taken, there's not many validation rules by default anyway...
		// TODO: Think about this, because it may not be a bad thing it gives more control to a developer but it 
		$class::_object()->validates = static::_object()->validates;
		
		// Same for the search schema, the library gets priority, but combine them.
		$class::_object()->search_schema = static::_object()->search_schema += $class::_object()->search_schema;
		
		// Replace any set display name for context
		$class::_object()->display_name = static::_object()->display_name;
        
        // Append access rules as models get extended, giving the last model priority
        // ie. Main model has a rule for index to restrict all. Extended model says to allow all. Everyone is allowed.
        // ie. Main model has no index rule. Extended model says to allow all. Everyone is allowed.
        // ie. Main model has an index rule to restrict all. Extended model has no index rule. Everyone is restricted.
        // ie. This model has an index rule to restirct all. Main model has a rule to allow all. Extended model has an index rule to restrict all. Everyone is restricted.
        // TODO: look into doing this for other properties...
		$class::_object()->access = static::_object()->access += $class::_object()->access;
		
		// Replace any action_redirect properties (holds redirects for each core Minerva controller method, ie. create, update, and delete)
		$class::_object()->action_redirects = static::_object()->action_redirects;
		
		// Replace any URL field and URL separator values
		$class::_object()->url_field = static::_object()->url_field;
		$class::_object()->url_separator = static::_object()->url_separator;
		
		// Set the library name for this model
		$model_path = Libraries::path(get_class(static::_object()));
		$full_lib_path = LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
		$library_string = substr($model_path, strlen($full_lib_path));
		$library_pieces = explode('/', $library_string);
		$class::_object()->library_name = $library_pieces[0];
		
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
     * The display name is changed through class extension, but this method
     * can also be used to set the display name as well.
     *
     * @param string $name Optional display name to set for the model
     * @return String
    */
    public static function displayName($name=null) {
		$class =  __CLASS__;
        if(!empty($name) && is_string($name)) {
            $class::_object()->display_name = $name;
        }
		return $class::_object()->display_name;
    }
    
	/**
	 * Similiar to the displayName() method, this returns the library name
	 * for the current model.
     * 
     * This is always automatically set by the __init() method.
     * There is no point to allowing this method to double as a setter as well,
     * it could create a mess since directory names are almost as good as constants.
	 *
	 * @return String
	*/
	public static function libraryName() {
		$class =  __CLASS__;
		return $class::_object()->library_name;
    }
	
	/**
	 * Similiar to the displayName() method, this returns the validation
	 * rules for the model. This method can also set the validation rules
     * for the model.
	 *
     * @param array $rules Optionally set the validation rules
	 * @return Array
	*/
	public static function validationRules($rules=array()) {
		$class =  __CLASS__;
        if(!empty($rules)) {
            $class::_object()->validates = $rules;
        }
		return $class::_object()->validates;
    }
    
    /**
     * Gets or sets the access rules for the model.
     * 
     * @param string $rules Optionally set the access rules
     * @return array The access rules for the specified action or all actions
    */
    public static function accessRules($rules=array()) {
		$class =  __CLASS__;
        if(!empty($rules)) {
            $class::_object()->access = $rules;
        }
		return $class::_object()->access;
    }
	
	/**
	 * Returns the action_redirect property for the model.
	 * This property is used to control where certain actions redirect to.
	 * For example, after updating a record.
     * 
     * This method can also set the redirects, though extended models
     * will be able to set redirects with their own $action_redirects property.
	 *
     * @param array $redirects Optional array of action redirects to set
	 * @return Array
	*/
	public static function actionRedirects($redirects=array()) {
		$class =  __CLASS__;
        if(!empty($redirects)) {
            $class::_object()->action_redirects = $redirects;
        }
		return $class::_object()->action_redirects;
    }
	
	/**
	 * Returns the URL field(s) for the current model.
	 * If it's not set, it will return null. 
     * The controller would need to make a decision about the URL at that point.
     * This method can also be used to set the URL field(s).
	 *
     * @param mixed $field An array or string that chooses the field(s) from which to generate a friendly URL
	 * @return mixed Either an array of multiple fields to use for a URL (presumably to be concatenated), a single field as a string, or null for no field
	*/
	public static function urlField($field=null) {
		$class =  __CLASS__;
        if(!empty($field)) {
            $class::_object()->url_field = $field;
        }
		return (isset($class::_object()->url_field) && !empty($class::_object()->url_field)) ? $class::_object()->url_field:null;
	}
	
	/**
	 * Gets or sets the URL separator, which replaces spaces.
	 * Default is always a '-' symbol.
	 *
     * @param string $separator The separator character to use for spaces
	 * @return String
	*/
	public static function urlSeparator($separator=null) {
		$class =  __CLASS__;
        if(!empty($separator)) {
            $class::_object()->url_separator = $separator;
        }
		return (isset($class::_object()->url_separator) && !empty($class::_object()->url_separator)) ? $class::_object()->url_separator:'-';
	}
	
    /**
     * Returns the search schema for the model.
     * Note: If this model has been extended by another model then
     * the combined schema will be returned if that other model was
     * instantiated. The __init() method handles that.
     * 
     * However, in addition to class extension, the search schema
     * can also be set with this method.
     *
     * @param array Optional new search schema values
     * @return array
    */
    public static function searchSchema($schema=array()) {
		$class =  __CLASS__;
		$self = $class::_object();
		if(!empty($schema)) {
            $class::_object()->search_schema = $schema;
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
    public static function getMinervaModel($model_name=null, $library_name=null) {
		$default_class = 'minerva\models\\' . Inflector::classify($model_name);
		// if $default_class doesn't exist, then we'll return MinervaModel which isn't great because it has no collection TODO: look into that. maybe give it one like a "lost and found" should really not happen if everything was routed properly and other things I would think would break first not even allowing a save
		$class = (class_exists($default_class)) ? $default_class:__CLASS__;
		$model = Libraries::locate('minerva_models', $library_name . '.' . $model_name);
		return (class_exists($model)) ? $model:$class;
    }
	
	/**
	 * Returns all registered minerva models
	 *
	 * @param string $model_name The model name (should be page, user, or block)
	 * @return Array of classes
	*/
	public static function getAllMinervaModels($model_name=null) {
		$models = Libraries::locate('minerva_models', $model_name);
		$models_array = $models;
		if(!empty($models)) {
			$models_array = array($models);
		}
		return $models_array;
	}
    
}
?>