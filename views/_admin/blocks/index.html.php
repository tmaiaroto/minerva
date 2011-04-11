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
                <?=$this->minervaHtml->date($block->modified->sec); ?>
            </td>
            <td>
                <?=$this->minervaHtml->date($block->created->sec); ?>
            </td>
            <td>
                <?=$this->html->link('Edit', array('admin' => $this->minervaHtml->admin_prefix, 'library' => 'minerva', 'controller' => 'blocks', 'action' => 'update', 'url' => $block->url)); ?> | 
				<?=$this->html->link('Delete', array('admin' => $this->minervaHtml->admin_prefix, 'library' => 'minerva', 'controller' => 'blocks', 'action' => 'delete', 'url' => $block->url), array('onClick' => 'return confirm(\'Are you sure you want to delete ' . $block->title . '?\')')); ?>
            </td>
        </tr>
        <?php } ?>
    </table>

<?php $block_type = (isset($this->_request->params['block_type'])) ? $this->_request->params['block_type']:'all'; ?>
<?=$this->minervaPaginator->paginate($page_number, $total, $limit); ?>
<br />
<em>Showing page <?=$page_number; ?> of <?=$total_pages; ?>. <?=$total; ?> total record<?php echo ((int) $total > 1 || (int) $total == 0) ? 's':''; ?>.</em>
</div>

<div class="grid_4">
    <div class="box">
        <h2>Search for Content</h2>
	    <div class="block">
		<?=$this->minervaHtml->query_form(array('label' => 'Query ')); ?>
            </div>
    </div>
    <div class="box">
	<h2>Create Block</h2>
	<div class="block">
	    <?=$this->minervaHtml->link_types('block', 'create'); ?>
	</div>
    </div>
</div>

<div class="clear"></div>