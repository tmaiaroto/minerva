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
        <?=$this->html->link('Blocks', array('admin' => true, 'library' => 'minerva', 'controller' => 'blocks', 'action' => 'index')); ?>
        <ul>
            <li><?=$this->html->link('List All', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'blocks', 'action' => 'index')); ?></li>
            <li><?=$this->html->link('Create New', array('admin' => 'admin', 'library' => 'minerva', 'controller' => 'blocks', 'action' => 'create')); ?></li>
        </ul>
    </li>
    <li>
        <?=$this->html->link('Users', array('admin' => true, 'library' => 'minerva', 'controller' => 'users', 'action' => 'index')); ?>
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
        <a href="/users/logout">Logout</a>
    </li>
</ul>