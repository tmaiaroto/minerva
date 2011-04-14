<div class="grid_16">
    <h2 id="page-heading">Users</h2>
    <p>From here you should be able to manage all of the users on your site.</p>
</div>

<div class="clear"></div>

<div class="grid_12">
    <table>
        <thead>
            <tr>
                <th>E-mail/Facebook ID</th>
                <th>User Type</th>
                <th>Role</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php foreach($documents as $user) { ?>
        <tr>
            <td>
				<?php if(!empty($user->email)) { ?>
                <?=$this->html->link($user->email, array('admin' => $this->minervaHtml->admin_prefix, 'controller' => 'users', 'library' => 'minerva', 'action' => 'read', 'url' => $user->url)); ?>
				<?php } else { ?>
				<?=$this->html->link($user->facebook_uid, array('admin' => $this->minervaHtml->admin_prefix, 'controller' => 'users', 'library' => 'minerva', 'action' => 'read', 'url' => $user->url)); ?>
				<?php } ?>
            </td>
            <td>
                <?php if(!empty($user->user_type)) {
                    echo '<em>' . $user->user_type . '</em>';
                } else {
                    echo '<em>user</em>';
                } ?>
            </td>
            <td>
                <?=$user->role; ?>
            </td>
            <td>
                <?=$this->minervaHtml->date($user->created->sec); ?>
            </td>
            <td>
                <?=$this->html->link('Edit', array('admin' => $this->minervaHtml->admin_prefix, 'controller' => 'users', 'library' => 'minerva', 'action' => 'update', 'url' => $user->url)); ?> |
                <?=$this->html->link('Delete', array('admin' => $this->minervaHtml->admin_prefix, 'controller' => 'users', 'library' => 'minerva', 'action' => 'delete', 'url' => $user->url), array('onClick' => 'return confirm(\'Are you sure you want to delete ' . $user->email . '?\')')); ?>
            </td>
        </tr>
        <?php } ?>
    </table>

<?php $user_type = (isset($this->_request->params['user_type'])) ? $this->_request->params['user_type']:'all'; ?>
<?=$this->minervaPaginator->paginate($page_number, $total, $limit); ?>
<br />
<em>Showing page <?=$page_number; ?> of <?=$total_pages; ?>. <?=$total; ?> total record<?php echo ((int) $total > 1 || (int) $total == 0) ? 's':''; ?>.</em>
</div>

<div class="grid_4">
    <div class="box">
        <h2>Search for Users</h2>
	    <div class="block">
		<?=$this->minervaHtml->query_form(array('label' => 'Query ')); ?>
            </div>
    </div>
    <div class="box">
	<h2>Create User</h2>
	<div class="block">
	    <?=$this->minervaHtml->link_types('user', 'create'); ?>
	</div>
    </div>
</div>

<div class="clear"></div>