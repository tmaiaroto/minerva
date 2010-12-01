<?php
/*
 *
 * @author Tom Maiaroto
 * @website http://www.shift8creative.com
 * @modified 2010-11-20 16:17:41 
 * @created 2010-11-20 16:17:41 
 *
*/
namespace minerva\extensions\helper;
use \lithium\template\View as View;
use \lithium\util\Inflector as Inflector;

class Menu extends Block {
  
    /** 
     *  Simply overrides the Block's request() method.
     *  The Block's method could allow $options to be passed so
     *  the controller and action values could be more dynamic, but
     *  we don't really want to allow ANY controller/action to be acccessed.
    */	
    public function request($url=null) {
        if(!$url) {
            return false;
        }
        return $this->requestAction(array('controller' => 'menus', 'action' => 'read', 'args' => $url));		
    }
    
    /*
     * Renders a static menu template. Essentially, each library can create simple HTML
     * (also could be embedded Flash or whatever) menus in their "/views/menus" folder.
     * These can then be rendered anywhere in any other view template.
     *
     * Since we're extending the Block helper, we're overriding the render() method here
     * so that we can assure a few things are set the way we need for menus. We aren't going
     * to use cURL for example to render our menus. Where a Block is more flexible, our
     * menus are not. Their contents won't exist outside the system.
    */
    public function render($options=array()) {
	if(!is_array($options)) {
	    // TODO: log why
            return false;
        }
	
    	// Reserved template names, because we're putting our view templates in here (for now, should have an admin area/folder for them)
    	switch($options['template']) {
            // TODO: logging.... in fact, logging anytime something like this happens
	    case 'create':
	    case 'index':
	    case 'delete':
	    case 'update':
        	return false;
		break;
	}
		
        // Always rendering a template, never an external URL. That would definitely be a "block."
        $options['method'] = 'php';
        // For consistency - all static menus come from a "menus" folder.
        $options['folder'] = 'menus';
        
        return parent::render($options);
    }
    
    // Don't want to use this for Menus.
    public function requestAction($options=array()) {
        return false;
    }
    
}
?>