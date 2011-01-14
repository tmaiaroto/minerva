<?php
namespace minerva\models;

use \lithium\util\Validator;
use lithium\util\Inflector;

class Block extends \lithium\data\Model {
	
	protected $_schema = array(
		'_id' => array('type' => 'id', 'form' => array('type' => 'hidden', 'label' => false)),
		'block_type' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		// url possibly gets indexed
		'url' => array('type' => 'string', 'form' => array('label' => 'Pretty URL', 'help_text' => 'Set a specific pretty URL for this block (optionally overrides the default set from the title).', 'wrap' => array('class' => 'minerva_url_input'), 'position' => 'options')),
		'title' => array('type' => 'string', 'search' => array('weight' => 1), 'form' => array('label' => 'Title')),
		'content' => array('type' => 'string', 'search' => array('weight' => 1), 'form' => array('label' => 'Block Content', 'type' => 'textarea', 'after' => '(you may use html code)')),	
		// options contain all sorts of misc. data like "weight" or "position" or "is_menu" it could also contain "pubished" flags if desired, but minerva core doesn't care about publish status on blocks so no dedicated field is set for it
		// the "is_menu" is set true if the MenusController saves the block
		'options' => array('type' => 'array', 'form' => array('type' => 'hidden', 'label' => false)),
		'published' => array('type' => 'boolean', 'form' => array('type' => 'checkbox', 'position' => 'options')),
		'owner_id' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'created' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)), 
		'modified' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false))		
	);
	
	protected $_meta = array('locked' => true);
	
	public $validates = array(
		'title' => array(
                    array('notEmpty', 'message' => 'Title cannot be empty'),
                )
	);
	
	// So admin templates can have a little context...for example: "Create Block" ... "Create Special Block" etc.
	public $display_name = 'Block';
    
	public static function __init() {
		$class =  __CLASS__;
		$extended_schema = static::_object()->_schema;
		// Loop through and ensure no one forgot to set the form key
		// TODO: see if there's a more graceful way to do this
		foreach($extended_schema as $k => $v) {
			if(!isset($extended_schema[$k]['form'])) {
				$extended_schema[$k]['form'] = array();	
			}			
		}
		// Append extended schema
		$class::_object()->_schema += $extended_schema;
		// Also append extended validation rules (giving priroity to the library for overriding)
		$class::_object()->validates = static::_object()->validates += $class::_object()->validates;
		
		// Replace any set display name for context
		$class::_object()->display_name = static::_object()->display_name;
	
		// Don't forget me...
		parent::__init();
	}
	
	 /**
     * Get the display name for a block.
     * This helps to add a little bit of context for users.
     * For example, the create action template has a title "Create Block"
     * but if another block type uses that admin template, it would need
     * to be changed to something like "Create Custom Block" for example.
     * The "display_name" property of each Block model changes that and
     * this method gets the value.
     *
     * @return String
    */
    public function display_name() {
		$class =  __CLASS__;
		return $class::_object()->display_name;
    }
	
}

/* FILTERS
 *
 * Filters must be set down here outside the class because of the class extension by libraries.
 * If the filter was applied within __init() it would run more than once.
 *
*/
?>