<div class="grid_16">
    <h2 id="page-heading">Page Content</h2>
    <p>From here you should be able to manage all of the dynamic (served from the database) pages on your site.
    </p>
    <!-- MAYBE TODO: instead of linking to create a new basic page....have the link something you can hover and then popup a lightbox type thing that has all the page types listed. so when you hover you get a popup and then in that popup are these icons that when you click one of those....THEN it goes to make a page and it's making a page for the proper page type. Or maybe its not lightbox and just a little slide in under the "Create New Page" link. text, icons, both, whatever. -->
</div>

<div class="clear"></div>

<div class="grid_12">
    <table>
        <thead>
            <tr>
                <th>Page Title</th>
                <th>Document Type</th>
                <th>Owner</th>
                <th>Last Modified</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php foreach($documents as $page) { ?>
        <tr>
            <td>
                <?=$this->html->link($page->title, array('admin' => $this->minervaHtml->admin_prefix, 'library' => 'minerva', 'controller' => 'pages', 'action' => 'read', 'args' => array($page->url))); ?>
            </td>
            <td>
                <?php if(!empty($page->document_type)) {
                    echo '<em>' . $page->document_type . '</em>';
                } else {
                    echo '<em>page</em>';
                } ?>
            </td>
            <td>
                <?=$this->minervaHtml->owner_name($page); ?>
            </td>
            <td>
                <?=$this->minervaHtml->date($page->modified->sec); ?>
            </td>
            <td>
                <?=$this->minervaHtml->date($page->created->sec); ?>
            </td>
            <td>
                <?=$this->html->link('Edit', array('admin' => $this->minervaHtml->admin_prefix, 'library' => 'minerva', 'controller' => 'pages', 'action' => 'update', 'args' => array($page->url))); ?> | 
				<?=$this->html->link('Delete', array('admin' => $this->minervaHtml->admin_prefix, 'library' => 'minerva', 'controller' => 'pages', 'action' => 'delete', 'args' => array($page->url)), array('onClick' => 'return confirm(\'Are you sure you want to delete ' . $page->title . '?\')')); ?>
            </td>
        </tr>
        <?php } ?>
    </table>

<?php $page_type = (isset($this->_request->params['page_type'])) ? $this->_request->params['page_type']:'all'; ?>
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
	<h2>Create Content</h2>
	<div class="block">
	    <?=$this->minervaHtml->link_types('page', 'create'); ?>
	</div>
    </div>
</div>

<div class="clear"></div>

<script type="text/javascript">
    $(document).ready(function() {
	/*$('#create_new_page').live('hover', function() {
	    $('#new_page_type').show();
	});*/
    });
</script>