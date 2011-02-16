<div class="grid_16">
    <h2 id="page-heading">Users</h2>
    <p>From here you should be able to manage all of the users on your site.</p>
</div>

<div class="clear"></div>

<div class="grid_12">
    <table>
        <thead>
            <tr>
                <th>User E-mail</th>
                <th>User Type</th>
                <th>Last Modified</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php foreach($documents as $user) { ?>
        <tr>
            <td>
                <?=$this->html->link($user->email, array('admin' => true, 'controller' => 'users', 'action' => 'read', 'id' => $user->_id)); ?>
            </td>
            <td>
                <?php if(!empty($user->user_type)) {
                    echo '<em>' . $user->user_type . '</em>';
                } else {
                    echo '<em>user</em>';
                } ?>
            </td>
            <td>
                <?=$this->time->to('nice', $user->modified->sec); ?>
            </td>
            <td>
                <?=$this->time->to('nice', $user->created->sec); ?>
            </td>
            <td>
                <?=$this->html->link('Edit', array('admin' => true, 'controller' => 'users', 'action' => 'update', 'id' => $user->_id)); ?> |
                <?=$this->html->link('Delete', '/users/delete/' . $user->user_type . '/' . $user->_id, array('onClick' => 'return confirm(\'Are you sure you want to delete ' . $user->email . '?\')')); ?>
            </td>
        </tr>
        <?php } ?>
    </table>

<?php $user_type = (isset($this->_request->params['user_type'])) ? $this->_request->params['user_type']:'all'; ?>
<?=$this->paginator->pagination($total, $limit, $page_number, array('args' => array('user_type' => $user_type))); ?>
<br />
<em>Showing page <?=$page_number; ?> of <?=$total_pages; ?>. <?=$total; ?> total record<?php echo ((int) $total > 1 || (int) $total == 0) ? 's':''; ?>.</em><br />
<em>Template rendered from /views/users/index.html.php (core Minerva users index).</em>
</div>

<div class="grid_4">
    <div class="box">
        <h2>Search for Users</h2>
	    <div class="block">
		<?=$this->html->query_form(array('label' => 'Query ')); ?>
            </div>
    </div>
    <div class="box">
	<h2>Create User</h2>
	<div class="block">
	    <?=$this->html->link_types('user', 'create', array('exclude_minerva' => false)); ?>
	</div>
    </div>
</div>

<div class="clear"></div>