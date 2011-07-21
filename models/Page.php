<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 * 
 * Minerva Pages & How They Work
 *
 * Pages are the main content items within the CMS. They get stored in a Pages
 * collection where each document record will contain most of the data necessary
 * for any given page for a web site. "Most of" because there could of course
 * be data loaded from external sites or services as well as "block" content.
 *
 * Each Page must have a title, url, created date, modified date, and then a special
 * "document_type" field that tells the system which library created the page. 
 * This is how 3rd party add-ons will hook into the CMS without altering Minerva's 
 * core code in order to extend functionality. For example, a "blog" library.
 * 
 * What happens is any library with a Page model that extends this base Page model
 * will have its own $_schema, $validate, and other properties appended to the properties
 * in this base model class. This always guarantees that the base fields and validation
 * will exist so that there will be less issues with consistency and integration.
 * We have to be able to rely upon the fact that each "Page" has a title, url, etc.
 * However, some properties are completely overwritten if defined in the extended model.
 *
 * The Pages controller will be using the appropriate library's Page model which is based 
 * on the document pulled (the document_type field contains the library name).
 *
 * Within each library's extended Page model, not only is its schema and validation
 * rules applied, but any filters within that model will also be applied. It is 
 * through Lithium's filter system that the add-on gains even further control.
 *
 * Minerva's core PagesController is used and no additional controller need be setup.
 * However, the add-on could contain its own PagesController if additional actions 
 * were desired or if core actions were desired to be overwritten. Just note that the
 * routes defined within the add-on must point to the proper controller.
 *
 * It's completely possible to use the pages collection and not use any of Minerva's
 * classes to save and read data from it. However, you should use some caution because
 * you could end up with inconsistencies that could create errors.
 * Please understand what Minerva is trying to do if you decide to create your own
 * PagesController. You may even use Minerva's as a starting point for your own controller.
 * If you are making a radical change...Then perhaps consider using a completely new model,
 * controller, and database collection to store the data (if there's data to be stored).
 * Remember, Minerva uses MongoDB, but your libraries can use other types of datasources.
 *
 * When it comes to rendering templates.
 * There is a process within Minerva's bootstrap that adds an array of template and layout
 * paths to check when rendering the page. There is a hierarchy to this and it gives priority
 * to templates within the add-on first.
*/
namespace minerva\models;

use \lithium\util\Validator;
use lithium\util\Inflector as Inflector;

class Page extends \minerva\models\MinervaModel {
    
    /**
     * $_schema gets appended to with the libary Page model's protected $_schema property.
     *
     * The key 'form' is new, it's not part of Lithium. It gets used by the forms in the
     * create/update templates for convenience. You can create your own templates for
     * create/update and choose not to use the value from the 'form' key if you choose.
    */
    protected $_schema = array(
		'title' => array('type' => 'string', 'form' => array('label' => 'Title', 'wrap' => array('class' => 'minerva_title_input'))),
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
    
    // Search schema will also be combined
    public $search_schema = array(
		'title' => array(
			'weight' => 1
		)
    );
    
    // So admin templates can have a little context...for example: "Create Page" ... "Create Blog Post" etc.
    public $display_name = 'Page';
    
    // Pages use the title field by default to build their pretty url
    public $url_field = 'title';
    
    // Access rules (can be overwritten in an add-on's extended model and this will not be used)
    public $access = array(
        'index' => array(
            'action' => array(
                array('rule' => 'allowAll')
            ),
            'admin_action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'document' => array() // not used
        ),
        'create' => array(
            'action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'admin_action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'document' => array() // not used
        ),
        'update' => array(
            'action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'admin_action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'document' => array()
        ),
        'delete' => array(
            'action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'admin_action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'document' => array()
        ),
        'read' => array(
            'action' => array(
                array('rule' => 'allowAll', 'redirect' => array('library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'admin_action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'document' => array(
                array('rule' => 'allowIfPublished', 'message' => 'You are not allowed to see unpublished content.', 'redirect' => array('library' => 'minerva', 'controller' => 'pages', 'action' => 'index'))
            )
        ),
        'preview' => array(
            'action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'admin_action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'document' => array(
                // array('rule' => 'allowIfPublished', 'message' => 'You are not allowed to see unpublished content.', 'redirect' => array('library' => 'minerva', 'controller' => 'pages', 'action' => 'index'))
            )
        ),
        'view' => array(
            'action' => array(
                array('rule' => 'allowAll', 'redirect' => array('library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'admin_action' => array(
                array('rule' => 'allowManagers', 'redirect' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'login'))
            ),
            'document' => array()
        )
    );
    
    // TODO: ditch this method
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