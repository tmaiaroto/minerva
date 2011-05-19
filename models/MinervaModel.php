<?php
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
    static $access = array();
    public $document_type = '';
	public $action_redirects = array();
	public $url_field = null;
	public $url_separator = '-';
	protected $_meta = array(
		'locked' => true
	);
	
    public static function __init() {
		/**
		 * The following code will append a library Page model's $_schema and
		 * $validates properites to this Page model. $_schema can never be changed,
		 * only extended. $validates can be changed and extended, but if default
		 * field rules are left out, they will be used from this parent model.
		*/
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
		
		// Set the document type (for manualy set document_type values)
		$class::_object()->document_type = static::_object()->document_type;
		
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
	 * Similiar to the display_name() method, this returns the library name
	 * for the current model.
	 *
	 * @return String
	*/
	public function library_name() {
		$class =  __CLASS__;
		return $class::_object()->library_name;
    }
	
	/**
	 * Similiar to the display_name() method, this returns the validation
	 * rules for the model. 
	 *
	 * @return Array
	*/
	public function validation_rules() {
		$class =  __CLASS__;
		return $class::_object()->validates;
    }
	
	/**
	 * Returns the action_redirect property for the model.
	 * This property is used to control where certain actions redirect to.
	 * For example, after updating a record.
	 *
	 * @return Array
	*/
	public function action_redirects() {
		$class =  __CLASS__;
		return $class::_object()->action_redirects;
    }
	
	/**
	 * Get the document type for the model.
	 * Typically, the document type is the name of the library,
	 * but the model (that extends the corresponding minerva model)
	 * can manually set the document type as a property.
	 *
	 * This is useful for avoiding conflicts with other plugins.
	 * For example, there's two Page types in a system called "blog" ...
	 * You'd have to rename the library folder name, change some routing,
	 * but more importantly, you'd have different fields on the document, etc.
	 * so you need to change the document_type field.
	 *
	 * Note, if a document type can be null. It will use the base Minerva 
	 * models in that case meaning the schema will be limited.
	 *
	 * @return String
	*/
	public function document_type() {
		$class =  __CLASS__;
		return (isset($class::_object()->document_type)) ? $class::_object()->document_type:null;
    }
	
	/**
	 * Returns the URL field(s) for the current model.
	 * If it's not set, it will return null. The controller will need to make a decision about the URL then.
	 *
	 * @return String
	*/
	public function url_field() {
		$class =  __CLASS__;
		return (isset($class::_object()->url_field) && !empty($class::_object()->url_field)) ? $class::_object()->url_field:null;
	}
	
	/**
	 * Returns the URL separator.
	 * Default is '-'
	 *
	 * @return String
	*/
	public function url_separator() {
		$class =  __CLASS__;
		return (isset($class::_object()->url_separator) && !empty($class::_object()->url_separator)) ? $class::_object()->url_separator:'-';
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
		$default_class = 'minerva\models\\' . Inflector::classify($model_name);
		// if $default_class doesn't exist, then we'll return MinervaModel which isn't great because it has no collection TODO: look into that. maybe give it one like a "lost and found" should really not happen if everything was routed properly and other things I would think would break first not even allowing a save
		$class = (class_exists($default_class)) ? $default_class:__CLASS__;
		$model = Libraries::locate('minerva_models', $library_name . '.' . $model_name);
		return (class_exists($model)) ? $model:$class;
    }
	
	/**
	 * Returns all minerva models
	 *
	 * @param $model_name The model name (should be page, user, or block)
	 * @return Array of classes
	*/
	public function getAllMinervaModels($model_name=null) {
		$models = Libraries::locate('minerva_models', $model_name);
		$models_array = $models;
		if(is_string($models)) {
			$models_array = array($models);
		}
		return $models_array;
	}
    
}
?>