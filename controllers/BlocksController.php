<?php
/**
 * Blocks Controller
 * The blocks controller responsible for rendering both "static" and "dynamic" block content.
 * While the block helper could be used to render view templates from the /views folder, or any
 * folder underneath...For conventional and organizational reasons, "static" block templates should
 * live under the /views/blocks/static folder. 
 *
 * All block data for front-end use is accessed using the block helper.
 * (except when using the "ajax" method of the block helper and accessing the view method here)
 *
 * @author Tom Maiaroto
 * @website http://www.shift8creative.com
 * @modified 2010-06-10 15:13:50 
 * @created 2010-06-10 15:13:50 
 *
 */
namespace app\controllers;
use app\models\Block;
use \lithium\util\Set;

class BlocksController extends \lithium\action\Controller {
	
	public function view() {		
		if (empty($path)) {
			$path = array('static', 'example');
		} else {
			$path = array('static', func_get_args());
		}	
		$this->render(array('template' => join('/', $path), 'layout' => 'blank'));
	}
	
	// TODO: add caching
	public function read($url) {
		// get the page record (also within this record contains the library used, which is important)
		// TODO: make read conditions??
	  	$record = Block::find('first', array('conditions' => array('url' => $url)));	  	
	  	
	  	if((isset($record->library)) && ($record->library != 'app') && (!empty($record->library))) {
	  		// Just instantiating the library's Page model will essentially "bridge" and extend the main app's Page model	
			$class = '\app\libraries\\'.$record->library.'\models\Block'; 	  		
			$Library = new $class();
	  	
	  		// Bridge the optional render options (default uses the plugin's /views/pages/read.html.php template)
			$renderOptions = $Library->renderOptions;
			$renderOptions += array('template' => 'read', 'type' => 'html', 'layout' => 'default', 'library' => $record->library);
								
			// Get any misc. extra data the plugin may want to send to the view (another "bridge")
			$plugin_data = $Library->setReadDataBridge();
		} else {
			$plugin_data = null;
		}
 	
		// Return an array. No rendering.
 		return array('record' => $record, 'plugin_data' => $plugin_data);	
	}
	
	// Lists all blocks, or filters blocks by plugin (plugins can influence blocks)
	public function index($library=null) {
		$conditions = array(); // default conditions
		// If we are using a library, instantiate it's Page model (bridge from plugin to core)
		if((isset($library)) && ($library != 'app') && (!empty($library))) {		
			// Just instantiating the library's Page model will essentially "bridge" and extend the main app's Page model	
			$class = '\app\libraries\\'.$library.'\models\Block'; 	  		
			$Library = new $class();
			// Get extra data from the plugin's method to bridge in (can be any misc. optional data to be sent to the view)
			$plugin_data = $Library->setIndexDataBridge(); // TODO: evaluate if this is worth having
			// Get the conditions for the find (overwrites defaults, which there are none by default for now)
			$conditions = $Library->indexFindConditions;
		} else {
			$plugin_data = null;
		}	
				
		// Default options for pagination
		$defaults = array('page' => 1, 'limit' => 10, 'order' => array('descending' => 'true'));
		$params = Set::merge($defaults, $this->request->params);
		if((isset($params['page'])) && ($params['page'] == 0)) { $params['page'] = 1; }
		list($limit, $page, $order) = array($params['limit'], $params['page'], $params['order']);
						
		$records = Block::find('all', array(
			'limit' => $params['limit'],
			'offset' => ($params['page'] - 1) * $params['limit'], // TODO: "offset" becomes "page" soon or already in some branch...
			//'order' => $params['order']
			'order' => array('_id' => 'asc'),
			'conditions' => $conditions
		));
	
		$total = Block::count();
				
		return compact('records', 'limit', 'page', 'total', 'plugin_data');		
	}
	
	/* TODO: rephrase 
	 *Blocks can be bridged too.
	 * So the create template can be used from say a "Gallery" plugin and maybe a new field is added from the plugin's bridge
	 * that stores a few images. The image records are loaded into an array, paginated, and displayed within the create template
	 * within the Gallery plugin and allows the user to select which images to show in the block record, which is now a
	 * "Gallery Block." This way, blocks are just as flexible as pages and based on which plugin/library they want to leverage,
	 * they can take on different forms and content within those form pages to help assist in building the block's content.
	 * RATHER THAN, making a blank field where PHP code is to be put (like Drupal and some other CMS') or having a "hook" 
	 * that allows block, automatically created, from the module be used and configured under the module's settings (like Drupal).
	 * This is a more simplistic, flexible, and integrated solution.
	*/
	public function create($library=null) {	
		// If we are using a library, instantiate it's Page model (bridge from plugin to core)
		if((isset($library)) && ($library != 'app') && (!empty($library))) {		
			// Just instantiating the library's Block model will essentially "bridge" and extend the main app's Block model	
			$class = '\app\libraries\\'.$library.'\models\Block'; 	  		
			$Library = new $class();
			// Get extra data from the plugin's method to bridge in (can be any misc. optional data to be sent to the view)
			$plugin_data = $Library->setCreateDataBridge();
		} else {
			$plugin_data = null;
		}
		
		// Get the fields so the view template can iterate through them and build the form
		$fields = Block::$fields;	
		
		// Save
		if ($this->request->data) {
			$this->request->data['library'] = $library; 
			// If the "url" field was left empty (or the field is hidden because we don't want it to be set by the user), generate a url based on the title
			// TODO: ensure the url is unique. keep appending numbers to the url when duplicates are found.
			if(empty($this->request->data['url'])) {				
				$this->request->data['url'] = Inflector::underscore($this->request->data['title']);
			}
		    $block = Block::create($this->request->data);	    
		  	if($block->save()) {		
				$this->redirect(array('controller' => 'blocks', 'action' => 'index'));
		  	}
		}
		return compact('fields', 'plugin_data');
	}
	
	/**
	 * Update a block.
	 * TODO: move to some sort of app controller if possible so pages and blocks (and whatever else) can share - write less code
	*/
	public function update($url=null) {	
		$record = Block::find('first', array('conditions' => array('url' => $url)));
		
		// Next, if the record uses a library, instantiate it's Block model (bridge from plugin to core)
		if((isset($record->library)) && ($record->library != 'app') && (!empty($record->library))) {		
			// Just instantiating the library's Block model will essentially "bridge" and extend the main app's Block model	
			$class = '\app\libraries\\'.$record->library.'\models\Block'; 	  		
			$Library = new $class();
			// Get extra data from the plugin's method to bridge in (can be any misc. optional data to be sent to the view)
			$plugin_data = $Library->setUpdateDataBridge();
		} else {
			$plugin_data = null;
		}
		
		$fields = Block::$fields;
		$fields[Block::key()] = array('type' => 'hidden', 'label' => false);
		$fields['library'] = array('type' => 'hidden', 'label' => false);
		
		
		// Update the record
		if ($this->request->data) {
			// If the "url" field was left empty (or the field is hidden because we don't want it to be set by the user), generate a url based on the title
			// TODO: ensure the url is unique. keep appending numbers to the url when duplicates are found.
			if(empty($this->request->data['url'])) {				
				$this->request->data['url'] = Inflector::underscore($this->request->data['title']);
			}
		  	if($record->save($this->request->data)) {				
				$this->redirect(array('controller' => 'blocks', 'action' => 'index'));
		  	}
		}
		return compact('record', 'fields', 'plugin_data'); 
		
	}
	
	/** 
	 *  Delete a block record.
	 *  Plugins can apply filters within their Block model class in order to run filters for the delete.  
	*/
	public function delete($url=null) {
		if(!$url) {
			$this->redirect(array('controller' => 'blocks', 'action' => 'index'));
		}
		$record = Block::find('first', array('conditions' => array('url' => $url)));
		
		// We don't need $renderOptions, $fields, etc. but we will instantiate the model to take advantage of any delete filters
		if((isset($record->library)) && ($record->library != 'app') && (!empty($record->library))) {	  		
			$class = '\app\libraries\\'.$record->library.'\models\Block'; 	  		
			$Library = new $class();
		}
		
		// Delete the record TODO: put in some kinda flash messages (like cake has) to notify the user things deleted or didn't
		if($record->delete()) {
			$this->redirect(array('controller' => 'blocks', 'action' => 'index'));
		} else {
			$this->redirect(array('controller' => 'blocks', 'action' => 'index'));
		}		
	}	
	
	
	// test method
	public function foo() {
		$foo_data = array('string_data' => 'Hello foo.', 'int_data' => 4);		
		return compact('foo_data');
	}
	
}
?>
