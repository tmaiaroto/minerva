<?php
/**
 * The main admin menu is a special menu.
 * It's static in that it does not come from the database, but what's special
 * is that it allows libraires to add themselves to it.
 *
 * When adding libraries, define an 'minerva_admin_menu' key for the options.
 * For example:
 * Libraries::add('name', array('minerva_admin_menu' => array(
 *  'controller_name' => array(
 *      array(
 *          'url' => 'url or array',
 *          'title' => 'Menu Link Name'
 *      )
 *  )
 * )))
 *
*/

use \minerva\extensions\util\Util;
$libraries = Util::library_config(array('config_keys' => array('minerva_admin_menu')));
$menus = array();
foreach($libraries as $k => $v) {
    if(!empty($v['minerva_admin_menu'])) {
        $menus[$k] = $v['minerva_admin_menu'];
    }
}
$default_minerva_menu = array(
    array('url' => array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'pages', 'action' => 'index'), 'title' => 'Pages')
    
);
//var_dump($menus);
?>

<ul id="main_administration_menu" class="nav main">
    <li class="menu_first"><a href="/minerva/admin">Dashboard</a></li>
    <li>
        <?=$this->html->link('Pages', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'pages', 'action' => 'index')); ?>
        <ul>
            <li><?=$this->html->link('List All', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'pages', 'action' => 'index')); ?></li>
            <li><?=$this->html->link('Create New', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'pages', 'action' => 'create')); ?></li>
        </ul>
    </li>
    <li>
        <?=$this->html->link('Blocks', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'blocks', 'action' => 'index')); ?>
        <ul>
            <li><?=$this->html->link('List All', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'blocks', 'action' => 'index')); ?></li>
            <li><?=$this->html->link('Create New', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'blocks', 'action' => 'create')); ?></li>
        </ul>
    </li>
    <li>
        <?=$this->html->link('Users', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'index')); ?>
        <ul>
            <li><?=$this->html->link('List All', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'index')); ?></li>
            <li><?=$this->html->link('Create New', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'create')); ?></li>
        </ul>
    </li>
    <li>
        <a href="/admin/system_status">System</a>
        <ul>
            <li><a href="/admin/system_status">System Status</a></li>
            <li><a href="/test" target="_blank">Unit Test Dashboard</a></li>
        </ul>
    </li>
    <li class="menu_last">
        <?=$this->html->link('Logout', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'users', 'action' => 'logout')); ?>
    </li>
</ul>