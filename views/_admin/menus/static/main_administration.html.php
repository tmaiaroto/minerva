<ul id="main_administration_menu" class="nav main">
    <li class="menu_first"><a href="/minerva/admin">Dashboard</a></li>
    <li>
        <?=$this->html->link('Pages', array('admin' => true, 'controller' => 'minerva.pages', 'action' => 'index')); ?>
        <ul>
            <li><?=$this->html->link('List All', array('admin' => true, 'controller' => 'minerva.pages', 'action' => 'index')); ?></li>
            <li><?=$this->html->link('Create New', array('admin' => true, 'controller' => 'minerva.pages', 'action' => 'create')); ?></li>
        </ul>
    </li>
    <li>
        <?=$this->html->link('Blocks', array('admin' => true, 'controller' => 'minerva.blocks', 'action' => 'index')); ?>
        <ul>
            <li><?=$this->html->link('List All', array('admin' => true, 'controller' => 'minerva.blocks', 'action' => 'index')); ?></li>
            <li><?=$this->html->link('Create New', array('admin' => true, 'controller' => 'minerva.blocks', 'action' => 'create')); ?></li>
        </ul>
    </li>
    <li>
        <a href="/users">Users</a>
        <ul>
            <li><a href="/users">List All</a></li>
            <li><a href="/users/create">Create New</a></li>
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