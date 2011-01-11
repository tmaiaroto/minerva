<div class="grid_16">
    <h2 id="page-heading">Block Content</h2>
    <p>From here you should be able to manage all of the dynamic (served from the database) blocks on your site.<br />
    <?=$this->html->link('Create New Block', '/blocks/create', array('id' => 'create_new_block')); ?>
    <div id="new_block_type" style="display: none;">
	
    </div>
    </p>
    <!-- TODO: instead of linking to create a new basic block....have the link something you can hover and then popup a lightbox type thing that has all the page types listed. so when you hover you get a popup and then in that popup are these icons that when you click one of those....THEN it goes to make a page and it's making a page for the proper page type. Or maybe its not lightbox and just a little slide in under the "Create New Page" link. text, icons, both, whatever. -->
</div>

<div class="clear"></div>

<div class="grid_12">
    <table>
        <thead>
            <tr>
                <th>Block Title</th>
                <th>Block Type</th>
                <th>Owner</th>
                <th>Last Modified</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php foreach($documents as $block) { ?>
        <tr>
            <td>
                <?=$this->html->link($block->title, array('controller' => 'blocks', 'action' => 'read', 'url' => $block->url)); ?>
            </td>
            <td>
                <?php if(!empty($block->page_type)) {
                    echo '<em>' . $block->page_type . '</em>';
                } else {
                    echo '<em>page</em>';
                } ?>
            </td>
            <td>
                <?=$block->ownder_id; ?>
            </td>
            <td>
                <?=$block->modified; ?>
            </td>
            <td>
                <?=$block->created; ?>
            </td>
            <td>
                <?=$this->html->link('Edit', '/blocks/update/' . $block->block_type . '/' . $block->url); ?> | 
		<?=$this->html->link('Delete', '/blocks/delete/' . $block->block_type . '/' . $block->url, array('onClick' => 'return confirm(\'Are you sure you want to delete ' . $block->title . '?\')')); ?>
            </td>
        </tr>
        <?php } ?>
    </table>

<em>Template rendered from /views/blocks/index.html.php (core Minerva pages index).</em>
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
	$('#create_new_block').live('hover', function() {
	    $('#new_block_type').show();
	});
    });
</script>