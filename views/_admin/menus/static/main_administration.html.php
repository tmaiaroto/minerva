<?php
// TODO: Make this a menu from the database or some sort of menu that libraries can subscribe to or items to be added...
// maybe the two main menus (admin and public) are "special" menus and use a filterable class method...
// that other libraries can apply a filter to some method that contains an array for the menu
// what would something like that do for performance?

use minerva\models\Menu;

// Apply filters to Menu::static_menu() in order to alter and create menus
Menu::applyFilter('static_menu',  function($self, $params, $chain) {
    if($params['name'] == 'public') {
        $self::$static_menus['public'] = array('foo' => 'bar');
    }
    
    return $chain->next($self, $params, $chain);
});

// a second filter... pretend its elsewhere...so long as its applied before the call to Menu::static_menu()
Menu::applyFilter('static_menu',  function($self, $params, $chain) {
    var_dump('filter ran');
    
    if($params['name'] == 'public') {
        $self::$static_menus['public'] += array('item' => 'url');
    }
    
    return $chain->next($self, $params, $chain);
});


$admin_menu = Menu::static_menu('admin');
//var_dump($admin_menu);
//$public_menu = Menu::static_menu('public', array('cache' => false));
//var_dump($public_menu);

// all menus
//var_dump(Menu::static_menu());
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