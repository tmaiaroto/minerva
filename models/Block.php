<?php
namespace minerva\models;

use \lithium\util\Validator;
use lithium\util\Inflector;

class Block extends \minerva\models\MinervaModel {
	
	protected $_schema = array(
		'_id' => array('type' => 'id', 'form' => array('type' => 'hidden', 'label' => false)),
		'block_type' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		// url possibly gets indexed
		'url' => array('type' => 'string', 'form' => array('label' => 'Pretty URL', 'help_text' => 'Set a specific pretty URL for this block (optionally overrides the default set from the title).', 'wrap' => array('class' => 'minerva_url_input'), 'position' => 'options')),
		'title' => array('type' => 'string', 'form' => array('label' => 'Title')),
		'content' => array('type' => 'string', 'form' => array('label' => 'Block Content', 'type' => 'textarea', 'after' => '(you may use html code)')),	
		// options contain all sorts of misc. data like "weight" or "position" or "is_menu" it could also contain "pubished" flags if desired, but minerva core doesn't care about publish status on blocks so no dedicated field is set for it
		// the "is_menu" is set true if the MenusController saves the block
		'options' => array('type' => 'array', 'form' => array('type' => 'hidden', 'label' => false)),
		'published' => array('type' => 'boolean', 'form' => array('type' => 'checkbox', 'position' => 'options')),
		'owner_id' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)),
		'created' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false)), 
		'modified' => array('type' => 'string', 'form' => array('type' => 'hidden', 'label' => false))		
	);
	
	public $validates = array(
		'title' => array(
            array('notEmpty', 'message' => 'Title cannot be empty'),
        )
	);
	
	// Search schema will also be combined
    public $search_schema = array(
		'title' => array(
			'weight' => 1
		),
		'content' => array(
			'weight' => 1
		)
    );
	
	// So admin templates can have a little context...for example: "Create Block" ... "Create Special Block" etc.
	public $display_name = 'Block';
    
}

/* FILTERS
 *
 * Filters must be set down here outside the class because of the class extension by libraries.
 * If the filter was applied within __init() it would run more than once.
 *
*/
?>