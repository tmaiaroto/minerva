<?php
/**
 * Typically we want the controller to send data to the view (meaning it will call the find() method),
 * but in this case since this is a static page we'll make the call to the model instead. This breaks MVC.
 * However, this doesn't require an extra controller method special for this page and it doesn't require
 * the database queries to be in the view() method, which would be called for every static page.
 * That would defeat the purpose of a "static" page (one that doesn't required a database)...And while
 * making the query here also kinda defeats the purpose, the data that's returned from the query (or not)
 * isn't vital to this page. It's optional data.
 *
 * Alternatively we would make another controller to access the model and return say JSON to a view.
 * From here we would use JavaScript to display that data. That would be more "proper" from an MVC
 * point of view.
 *
 * TODO: Perhaps make a library for "AJAX API" or something to that affect...OR simply a new controller
 * for it all (or several controllers, or put the methods within each Pages, Blocks, and Users controller)
 * and not a library.
*/
use minerva\models\Page;
?>
<div class="grid_16">
	<h2 id="page-heading">Dashboard</h2>
	<p>
		Welcome to the Minerva dashboard. From here you can access all the administrative areas of your site. The dashboard provides you with a quick overview for information about your site.
	</p>
</div>
<div class="clear"></div>

<div class="grid_8">
	<div class="box">
		<h2>Recently Created Pages</h2>
		<div class="block">
			<table>
				<thead>
					<tr>
						<th>Page Title</th>
						<th>Page Type</th>
					</tr>
				</thead>
				<?php
				$recent_pages = Page::getLatestPages();
				if($recent_pages) {
					foreach($recent_pages as $page) {
				?>
				<tr>
					<td>
						<?=$this->html->link($page->title, array('controller' => 'pages', 'action' => 'read', 'url' => $page->url)); ?>
					</td>
					<td>
						<?php if(!empty($page->page_type)) {
							echo '<em>(' . $page->page_type . ')</em>';
						} else {
							echo '<em>(page)</em>';
						} ?>
					</td>
				</tr>
				<?php
					}
				}
				?>
			</table>
		</div>
	</div>
</div>

<div class="grid_8">
	<div class="box">
		<h2>Recent User Actions</h2>
		<div class="block">
			<table>
				<thead>
					<tr>
						<th>User</th>
						<th>Action</th>
						<th>Time</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>

<div class="clear"></div>