<?php
/**
 * Menus Controller
 * The Menus Controller extends the Blocks Controller so that menu data
 * is stored on the Blocks collection and templates are served from the
 * Blocks view folder. Menus aren't complex enough to warrant their own
 * model or database collection. They are also rendered in various areas
 * making them not very different than "block" content.
 *
 * @author Tom Maiaroto
 * @website http://www.shift8creative.com
 * @modified 2010-11-20 15:13:50 
 * @created 2010-11-20 15:13:50 
 *
 */
namespace minerva\controllers;
use minerva\models\Block;
use \lithium\util\Set;

class MenusController extends BlocksController {
    
    static $access = array();
    
    // TODO: add caching
    public function read($url) {
        $record = Block::find('first', array('conditions' => array('url' => $url, 'options.is_menu' => true)));	  	
        // Return an array. No rendering.
        return array('record' => $record);
    }
    
    // We just need to pass along "is_menu" with the data... TODO: Maybe a filter even? this works though very nicely/easily
    public function create($library=null) {        
        if ($this->request->data) {
	    $this->request->data['options']['is_menu'] = true;
        }
        parent::create($library);
    }
    
}
?>