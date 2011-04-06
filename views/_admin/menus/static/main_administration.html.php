<?php
use lithium\security\Auth;
if(Auth::check('minerva_user')) { ?>
<ul id="main_administration_menu" class="nav main">
    <li class="menu_first"><a href="/admin">Dashboard</a></li>
    <li>
        <a href="/pages">Pages</a>
        <ul>
            <li><a href="/pages">List All</a></li>
            <li><a href="/pages/create">Create New</a></li>
        </ul>
    </li>
    <li>
        <a href="/blocks">Blocks</a>
        <ul>
            <li><a href="/blocks">List All</a></li>
            <li><a href="/blocks/create">Create New</a></li>
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
<?php } ?>