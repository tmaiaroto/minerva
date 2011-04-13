<?php
/**
 * Minerva Menu Helper
 * 
 * @author Tom Maiaroto
 * @website http://www.shift8creative.com
 *
*/
namespace minerva\extensions\helper;
use \lithium\template\View as View;
use \lithium\util\Inflector as Inflector;

class MinervaMenu extends Block {
  
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
   
    /**
     * Shortcut helper method to render an admin menu.
     * Works very similar to the Blocks helper's render_admin_block() method because it uses the same render() method.
     * 
     * @param $template String The name of the template file located in minerva/views/menus/static/
     * @param $library String The library from where to pull the menu (default is minerva, falls back to app)
     * @param String HTML menu code
    */
    public function render_admin_menu($template=null, $library='minerva') {
		if(empty($template)) {
			return '';
		}
		$options = array('library' => $library, 'template' => $template, 'controller' => 'menus', 'admin' => 'admin');
		return $this->render($options);
    }
    
    /**
     * Shortcut helper method to render an admin menu.
     * Works very similar to the Blocks helper's render_block() method because it uses the same render() method.
     *
     * @param $template String The name of the template file located in minerva/views/menus/static/
     * @param $library String The library from where to pull the menu (default is minerva, falls back to app)
     * @param String HTML menu code
    */
    public function render_menu($template=null, $library='minerva') {
		if(empty($template)) {
			return '';
		}
		$options = array('library' => $library, 'template' => $template, 'controller' => 'menus');
		return $this->render($options);
    }
    
    
    // Don't want to use this for Menus.
    public function requestAction($options=array()) {
        return false;
    }
    
}
?>