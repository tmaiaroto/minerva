<div class="grid_16">
    <h2 id="page-heading">Block Content</h2>
    <p>From here you should be able to manage all of the dynamic (served from the database) blocks on your site.</p>
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
                <?=$block->title; ?>
            </td>
            <td>
                <?php if(!empty($block->page_type)) {
                    echo '<em>' . $block->page_type . '</em>';
                } else {
                    echo '<em>block</em>';
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
                <?=$this->html->link('Edit', array('admin' => true, 'controller' => 'blocks', 'action' => 'update', 'url' => $block->url)); ?> | 
		<?=$this->html->link('Delete', '/blocks/delete/' . $block->url, array('onClick' => 'return confirm(\'Are you sure you want to delete ' . $block->title . '?\')')); ?>
            </td>
        </tr>
        <?php } ?>
    </table>

<?php $block_type = (isset($this->_request->params['block_type'])) ? $this->_request->params['block_type']:'all'; ?>
<?=$this->paginator->pagination($total, $limit, $page_number, array('args' => array('block_type' => $block_type))); ?>
<br />
<em>Showing page <?=$page_number; ?> of <?=$total_pages; ?>. <?=$total; ?> total record<?php echo ((int) $total > 1 || (int) $total == 0) ? 's':''; ?>.</em><br />
<em>Template rendered from /views/blocks/index.html.php (core Minerva blocks index).</em>
</div>

<div class="grid_4">
    <div class="box">
        <h2>Search for Content</h2>
	    <div class="block">
		<?=$this->html->query_form(array('label' => 'Query ')); ?>
            </div>
    </div>
    <div class="box">
	<h2>Create Block</h2>
	<div class="block">
	    <?=$this->html->link_types('block', 'create', array('exclude_minerva' => false)); ?>
	</div>
    </div>
</div>

<div class="clear"></div>