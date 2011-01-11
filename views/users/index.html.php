<div class="grid_16">
    <h2 id="page-heading">Users</h2>
    <p>From here you should be able to manage all of the users on your site.<br />
    <?=$this->html->link('Create New User', '/users/create', array('id' => 'create_new_user')); ?>
    <div id="new_user_type" style="display: none;">
	
    </div>
    </p>
    <!-- TODO: instead of linking to create a new basic page....have the link something you can hover and then popup a lightbox type thing that has all the page types listed. so when you hover you get a popup and then in that popup are these icons that when you click one of those....THEN it goes to make a page and it's making a page for the proper page type. Or maybe its not lightbox and just a little slide in under the "Create New Page" link. text, icons, both, whatever. -->
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
                <?=$this->html->link($user->email, array('controller' => 'users', 'action' => 'read', 'id' => $user->_id)); ?>
            </td>
            <td>
                <?php if(!empty($user->user_type)) {
                    echo '<em>' . $user->user_type . '</em>';
                } else {
                    echo '<em>user</em>';
                } ?>
            </td>
            <td>
                <?=$user->modified; ?>
            </td>
            <td>
                <?=$user->created; ?>
            </td>
            <td>
                <?=$this->html->link('Edit', '/users/update/' . $user->user_type . '/' . $user->_id); ?> |
                <?=$this->html->link('Delete', '/users/delete/' . $user->user_type . '/' . $user->_id, array('onClick' => 'return confirm(\'Are you sure you want to delete ' . $user->email . '?\')')); ?>
            </td>
        </tr>
        <?php } ?>
    </table>

<em>Template rendered from /views/users/index.html.php (core Minerva pages index).</em>
</div>

<div class="grid_4">
    <div class="box">
        <h2>Filter &amp; Search</h2>
	    <div class="block">
            </div>
    </div>
</div>

<div class="clear"></div>

<script type="text/javascript">
    $(document).ready(function() {
	$('#create_new_user').live('hover', function() {
	    $('#new_user_type').show();
	});
    });
</script>