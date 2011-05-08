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
use minerva\models\Menu;
use \lithium\storage\Cache;

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
	
	/**
	 * Renders a static menu that gets built using Lithium's filter system.
	 * For now, this only goes two deep. In the future it should allow for more levels...Though there should be a limit.
	 *
	 * @param string $name The menu name
	 * @param array $options 
	 * @return string HTML code for the menu
	*/
	public function static_menu($name=null, $options=array()) {
		$defaults = array(
            //'cache' => '+1 day'
			'cache' => false
        );
		if(empty($name) || !is_string($name)) {
			return '';
		}
		
		// set the cache key for the menu
		$cache_key = (empty($name)) ? 'minerva_static_menus.all':'minerva_static_menus.' . $name;
		$menu = false;
		
		// if told to use the menu from cache (note: filters will not be applied for this call because Menu::static_menu() should not be called provided there's a copy in cache)
        if(!empty($options['cache'])) {
            $menu = Cache::read('default', $cache_key);
        }
		
		// if the menu hasn't been set in cache or it was empty for some reason, get a fresh copy of its data
		if(empty($menu)) {
			$menu = Menu::static_menu($name);	
		}
		
		// if using cache, write the menu data to the cache key
		if(!empty($options['cache'])) {
			Cache::write('default', $cache_key, $menu, $options['cache']);
		}
		
		// Format the HTML for the menu
		// option for additional custom menu class
		$menu_class = '';
		if(isset($options['menu_class']) && is_string($options['menu_class'])) {
			$menu_class = ' ' . $options['menu_class'];
		}
		
		$string = "\n";
		$string .= '<ul class="minerva_menu ' . $name . '_menu' . $menu_class . '">';
		$string .= "\n";
		
		if(is_array($menu)) {
			$i = 1;
			$total = count($menu);
			foreach($menu as $parent) {
				$title = (isset($parent['title']) && !empty($parent['title'])) ? $parent['title']:false;
				$url = (isset($parent['url']) && !empty($parent['url'])) ? $parent['url']:false;
				$options = (isset($parent['options']) && is_array($parent['options'])) ? $parent['options']:array();
				$sub_items = (isset($parent['sub_items']) && is_array($parent['sub_items'])) ? $parent['sub_items']:array();
				if($title && $url) {
					$position_class = ($i == 1) ? ' menu_first':'';
					$position_class = ($i == $total) ? ' menu_last':$position_class;
					$string .= "\t";
					$string .= '<li class="menu_item' . $position_class . '">' . $this->_context->html->link($title, $url, $options);
					// sub menu items
					if(count($sub_items) > 0) {
						$string .= "\n\t";
						$string .= '<ul class="sub_menu">';
						$string .= "\n";
						foreach($sub_items as $child) {
							$title = (isset($child['title']) && !empty($child['title'])) ? $child['title']:false;
							$url = (isset($child['url']) && !empty($child['url'])) ? $child['url']:false;
							$options = (isset($child['options']) && is_array($child['options'])) ? $child['options']:array();
							if($title && $url) {
								$string .= "\t\t";
								$string .= '<li class="sub_menu_item">' . $this->_context->html->link($title, $url, $options) . '</li>';
								$string .= "\n";
							}
						}
						$string .= "\t";
						$string .= '</ul>';
						$string .= "\n";
					}
					$string .= '</li>';
					$string .= "\n";
				}
				$i++;
			}
		}
		
		$string .= '</ul>';
		$string .= "\n";
		
		return $string;
	}
    
}
?>