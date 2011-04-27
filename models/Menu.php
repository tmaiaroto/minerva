<?php
namespace minerva\models;

use \lithium\storage\Cache;

class Menu extends \lithium\data\Model {
    
    /**
     * Default static menus.
     *
     * @var array
    */
    static $static_menus = array(
        'admin' => array(
            array(
                'title' => 'Dashboard',
                'url' => '/minerva/admin',
                'options' => array()
            ),
            array(
                'title' => 'Pages',
                'url' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'pages', 'action' => 'index'),
                'options' => array(),
                'sub_items' => array(
                    array(
                        'title' => 'List All',
                        'url' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'pages', 'action' => 'index'),
                        'options' => array()
                    ),
                    array(
                        'title' => 'Create New',
                        'url' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'pages', 'action' => 'create')
                    )
                )
            ),
            array(
                'title' => 'Blocks',
                'url' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'blocks', 'action' => 'index')
            )
        )
    );
    
    /**
     * Returns a static menu.
     * Static menus are defined as arrays.
     * There is a default admin menu and a default public site menu.
     *
     * This method is filterable so the menus can be added, added to or changed.
     *
     * @param string $name The name of the static menu to return (empty value returns all menus)
     * @param array $options
     * @return array The static menu(s)
    */
    public static function static_menu($name=null, $options=array()) {
        $defaults = array(
            'cache' => '+1 day'
        );
        $options += $defaults;
        $params = compact('name', 'options');
		
        // if told to use the menu from cache (note: filters will not be applied for this call)
        if(!empty($options['cache'])) {
            $cache_key = (empty($name)) ? 'minerva_static_menus.all':'minerva_static_menus.' . $name;
            $cached_static_menus = Cache::read('default', $cache_key);
            if(!empty($cached_static_menus)) {
                return $cached_static_menus;
            }
        }
        
        $filter = function($self, $params) {
			$options = $params['options'];
            $name = $params['name'];
            $static_menus = array();
            $cache_key = (empty($name)) ? 'minerva_static_menus.all':'minerva_static_menus.' . $name;
            
            // get a specific menu or all menus to return
            if(empty($name)) {
                $static_menus = $self::$static_menus;
            } else {
                $static_menus = isset($self::$static_menus[$params['name']]) ? $self::$static_menus[$params['name']]:array();
            }
            
            // if using cache, write the key
            if(!empty($options['cache'])) {
                Cache::write('default', $cache_key, $static_menus, $options['cache']);
            }
            
            // return the static menus
            return $static_menus;
		};
        
        return static::_filter(__FUNCTION__, $params, $filter);
    }
    
}
?>