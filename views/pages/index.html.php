<div class="grid_16">
    <h2 id="page-heading">Page Content</h2>
    <p>From here you should be able to manage all of the dynamic (served from the database) pages on your site.<br />
    <?=$this->html->link('Create New Page', '/pages/create'); ?></p>
    <!-- TODO: instead of linking to create a new basic page....have the link something you can hover and then popup a lightbox type thing that has all the page types listed. so when you hover you get a popup and then in that popup are these icons that when you click one of those....THEN it goes to make a page and it's making a page for the proper page type. Or maybe its not lightbox and just a little slide in under the "Create New Page" link. text, icons, both, whatever. -->
</div>

<div class="clear"></div>

<div class="grid_12">
    <table>
        <thead>
            <tr>
                <th>Page Title</th>
                <th>Page Type</th>
                <th>Owner</th>
                <th>Last Modified</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php foreach($documents as $page) { ?>
        <tr>
            <td>
                <a href="/page/read/<?=$page->url; ?>"><?=$page->title; ?></a>
            </td>
            <td>
                <?php if(!empty($page->page_type)) {
                    echo '<em>' . $page->page_type . '</em>';
                } else {
                    echo '<em>page</em>';
                } ?>
            </td>
            <td>
                <?=$page->ownder_id; ?>
            </td>
            <td>
                <?=$page->modified; ?>
            </td>
            <td>
                <?=$page->created; ?>
            </td>
            <td>
                <?=$this->html->link('Edit', array('controller' => 'pages', 'action' => 'update', 'page_type' => $page->page_type, 'url' => $page->url)); ?>
            </td>
        </tr>
        <?php } ?>
    </table>

<em>Template rendered from /views/pages/index.html.php (core Minerva pages index).</em>
</div>

<div class="grid_4">
    <div class="box">
        <h2>Filter &amp; Search</h2>
	    <div class="block">
            </div>
    </div>
</div>

<div class="clear"></div>