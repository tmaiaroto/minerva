<?php
/*
 * Minerva Pages & How They Work
 *
 * Pages are the main content items within the CMS. They get stored in a Pages
 * collection where each document record will contain most of the data necessary
 * for any given page for a web site. "Most of" because there could of course
 * be data loaded from external sites or services as well as "block" content.
 *
 * Each Page must have a title, url, created date, modified date, and then a special
 * "page_type" field that tells the system which library created the page. This is how
 * 3rd party add-ons will hook into the CMS without altering Minerva's core code
 * in order to extend functionality. For example, a "blog" library.
 * 
 * What happens is any library with a Page model that extends this base Page model
 * will have its own $_schema and $validate properties appended to the same properties
 * in this base model class. This always guarantees that the base fields and validation
 * will exist so that there will be less issues with consistency and integration.
 * We have to be able to rely upon the fact that each "Page" has a title, url, etc.
 *
 * The Pages controller will be instantiating the library's Page model based on the
 * record pulled (the library field contains the library name). If creating
 * a new page, or calling the index action, the URL would contain the library name.
 *
 * Within each library's extended Page model, not only is its schema and validation
 * rules applied, but any filters within that model will also be applied. It is 
 * through Lithium's filter system that we gain even further control over the
 * CRUD process for pages. You would equate this technique with a "hook" system
 * found in other CMS' such as Drupal, etc.
 *
 * Minerva's core PagesController is used and no additional controller need be setup;
 * however, the library's page view templates will also be used.
 *
 * Don't want to use Lithium's filter system?
 * Want to add new methods to the PagesController?
 * Alternatively, a library could write new routing rules to call it's own PagesController
 * which can extend Minerva's PagesController or just extend Lithium's controller class
 * as normal and not inherit anything from Minerva...Then from there you could skip the
 * entire process of instantiating a Page model from a library and simply put "use" up
 * top to use the library's Page model instead of Minerva's base Page model to pick up
 * schema, validation, and other properties and methods.
 *
 * It's completely possible to use the pages collection and not use any of Minerva's
 * classes to save and read data from it. However, you should use some caution because
 * you could end up with inconsistencies that could create errors.
 * Please understand what Minerva is trying to do if you decide to create your own
 * PagesController. You may even use it as a starting point for your own controller.
 * If you are making a radical change...Then perhaps consider using a completely new model,
 * controller, and database collection to store the data (if there's data to be stored).
 * Remember, Minerva uses MongoDB, but your libraries can use other types of datasources.
 *
 *
*/
namespace minerva\models;

use \lithium\util\Validator;
use lithium\util\Inflector as Inflector;

class Page extends \lithium\data\Model {
    
    
    /**
     * $_schema gets appended to with the libary Page model's protected $_schema property.
     *
     * The key 'form' is new, it's not part of Lithium. It gets used by the forms in the
     * create/update templates for convenience. You can create your own templates for
     * create/update and choose not to use the value from the 'form' key if you choose.
    */
    protected $_schema = array(
	'_id' => array('type' => 'id', 'form' => array('type' => 'hidden', 'label' => false)),
	'page_type' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
	'title' => array('type' => 'string', 'search' => array('weight' => 1), 'form' => array('label' => 'Title', 'wrap' => array('class' => 'minerva_title_input'))),
	'created' => array('type' => 'date', 'form' => array('type' => 'hidden', 'label' => false)),
	'modified' => array('type' => 'date', 'form' => array('type' => 'hidden', 'label' => false)),		
	'url' => array('type' => 'string', 'form' => array('label' => 'Pretty URL', 'help_text' => 'Set a specific pretty URL for this page (optionally overrides the default set from the title).', 'wrap' => array('class' => 'minerva_url_input'), 'position' => 'options')),
	'published' => array('type' => 'boolean', 'form' => array('type' => 'checkbox', 'position' => 'options')),
	'owner_id' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false))
	// add options field?? to all models?? libraries can store various data within this options field that contains array data.
	// it's useful...BUT, it won't have validation. it's still good to store simple data that isn't harmful if left unvalidated or data that's set programatically so it's known and doesn't need to be validated.
    );
    
    // Defined as normal and the library Page model's $validates is also defined as normal, they will be combined.
    public $validates = array(
	'title' => array(
	    array('notEmpty', 'message' => 'Title cannot be empty'),
	)
    );
    
    // So admin templates can have a little context...for example: "Create Page" ... "Create Blog Post" etc.
    public $display_name = 'Page';
    
    public static function __init() {		
	/**
	 * The following code will append a library Page model's $_schema and
	 * $validates properites to this Page model. $_schema can never be changed,
	 * only extended. $validates can be changed and extended, but if default
	 * field rules are left out, they will be used from this parent model.
	*/
	$class =  __CLASS__;
	$extended_schema = static::_object()->_schema;
	// Loop through and ensure no one forgot to set the form key		
	foreach($extended_schema as $k => $v) {
		$extended_schema[$k] += array('form' => array('position' => 'default'));
	}
	// Append extended schema
	$class::_object()->_schema += $extended_schema;
	// Use the library Page model's validation rules combined with the default (but the library gets priority) this way the default rules can be changed, but if left out will still be used (to help make things nicer)
	$class::_object()->validates = static::_object()->validates += $class::_object()->validates;
	
	// Replace any set display name for context
	$class::_object()->display_name = static::_object()->display_name;
	
	// Lock the schema so values that aren't part of it can't be saved to the db.
	$class::meta('locked', true);
	
	// Don't forget me...
	parent::__init();
    }	
    
    /**
     * Get the display name for a page.
     * This helps to add a little bit of context for users.
     * For example, the create action template has a title "Create Page"
     * but if another page type uses that admin template, it would need
     * to be changed to something like "Create Blog Entry" for example.
     * The "display_name" property of each Page model changes that and
     * this method gets the value.
     *
     * @return String
    */
    public function display_name() {
	$class =  __CLASS__;
	return $class::_object()->display_name;
    }
    
    public function unique_url($url=null, $id=null) {
	if((!$url) || (!$id)) {
	    return null;
	}
	
	$records = Page::find('all', array('fields' => array('url'), 'conditions' => array('url' => array('like' => '/'.$url.'/'))));
	$conflicts = array();
	
	foreach($records as $record) {
	    if($record->{Page::key()} != $id) {
		$conflicts[] = $record->url;
	    }
	}
	
	if (!empty($conflicts)) {
	    $firstSlug = $url;
	    $i = 1;
	    while($i > 0) {
		// TODO: Maybe make separator option somewhere as a property? So it can be _ instead of -
		if (!in_array($firstSlug . '-' . $i, $conflicts)) {					
		    $url = $firstSlug . '-' . $i;
		    $i = -1;
		}
	    $i++;
	    }
	}
	
	return $url;
    }
    
    public function getLatestPages($options=array()) {
	$defaults = array('conditions' => array(), 'limit' => 10);
	$options += $defaults;
	
	return Page::find('all', array('limit' => $options['limit'], 'conditions' => $options['conditions']));
    
    }

}

/* FILTERS GO HERE
 *
 * Any core filters must be set down here outside the class because of the class extension by libraries.
 * If the filter was applied within __init() it would run more than once.
 *
*/

?>