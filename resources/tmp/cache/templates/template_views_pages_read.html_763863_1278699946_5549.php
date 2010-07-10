<!-- start left column -->
<div class="grid_4">
	<div class="box">
		<h2>Block Using Request</h2> 
		<div class="block">
			<?php 
			/* if the following is pulling a block bridged by a library, it may be best to make the calls in a view template
			 * within the /views/blocks/static folder (or a folder underneath) and called by Block->render().
			 * This way all the code that may look through data returend from the dynamic block can be kept separate.
			 * Or....If the library was clever and saved final HTML output in the "content" field of the block...Then maybe just print it here.
			 * Two different approaches, one allows for a template to change easily and the other keeps the html in the database, leaving
			 * fewer template files sitting around.
			 * OR a third option of course....just put the template code here and loop through records, etc.
			 * TODO: Allow libraries to apply filters...So that way final HTML output could be put into the content field "beforeSave"
			 */
			?>
			<?php $dynamic_block = $this->Block->request('example_dynamic_block'); // load a dynamic block record from the db ?>
			<?php echo $h($dynamic_block['record']->content); ?><?php // render it's content ?>
		</div>
	</div>
	<div class="box">
		<h2>Static Block From Template</h2>
		<div class="block">			
			<?php echo $this->Block->render(array('template' => 'example')); ?>
		</div>
	</div>
	<div class="box">
		<h2>Block Using cURL</h2> 
		<div class="block" style="overflow: hidden">
			<?php 
			// Some favorite options of mine:
			// CURLOPT_CONNECTTIMEOUT_MS => 3000   -- skips the block output if the response time isn't fast enough! or CURLOPT_CONNECTTIMEOUT for seconds
			// CURLOPT_LOW_SPEED_TIME and CURLOPT_LOW_SPEED_LIMIT to help with load
			//echo $this->Block->render(array('url' => 'http://www.youtube.com/watch?v=829L0Fx0y6U&feature=fvhl', 'curl_options' => array()));			
			?>
		</div>		
	</div>
</div>
<!-- end left column -->

<div class="grid_8">
    <h2 id="page-heading">Blog Entry</h2>
<!-- start main content area -->
    
<br />
<p>The template being rendered is from /libraries/blog/views/pages/read.html.php
<br />
Library used (bridged): <?php echo $h($record->library); ?></p>
<br />
<h1><?php echo $h($record->title); ?></h1>
<p>
Created: <?php echo $h($record->created); ?> (modified: <?php echo $h($record->modified); ?>)<br /><br />
<?php echo $h($record->body); ?>
</p>
<?php echo $h($record->image); ?>

<?php 
/** <?php echo $this->view()->render(array('element' => 'example_element'), compact('variable', 'names')); ?>
 * The preceding will look for the element within /libraries/blog/views/elements/... because we changed the paths 
 * Somewhat of a design problem maybe...because each page type switches the paths...
 * You'd need the same element template in each plugin's views/elements folder... not good.
 * So one must call $this->Block->render() ... which means the Vew class is instantiated multiple times...
 * So how does that effect performance? With more and more blocks on a page?
 *
 * Maybe its ok to use $this->view()-render() ... just developers need to know where it's going to look.
 * If we pass the library name (already passed) along then switches/if statements can be made to ensure we're on the proper 
 * type of page.Then once we know we're on a "blog" page, we can render an element that we know exists under the plugin's 
 * elements folder.
 * The point? Organization and less View classes being loaded...Truly static content that will exist on every type of page,
 * (say like ads) could probably just sit in this view template or the layout template. Then there's no point of using elements.
 * It's only when we have conditions of "only for blog pages show this" then it makes sense to have elements because a 
 * switch or if statement is fine, but not if inbetween you have tons and tons of html code. That's really when you want to use
 * elements....and at that point, you know what type of page it is and you want to show something specific to that type/plugin
 * so it only makes sense that the element would live under the plugin's elements folder anyway....
 * Normally, you'd probably have /app/views/elements/blog/my_blog_specific_element.html.php anyway to keep organized...
 * So it's just as organied to keep all blog elements under the blog plugin views/elements folder.
 *
 * Knowing the Lithium framework is definitely something that will be an advantage and probably a requirement 
 * in order to use this CMS.
 */
?>


<!-- end main content area -->
</div>

<!-- right column -->
<div class="grid_4">
	<div class="box">
		<h2>Static Block Using AJAX</h2>
		<div class="block" id="result">
			<?php echo $this->Block->render(array('method' => 'ajax', 'url' => '/blocks/view/foo'));  ?>
		</div>
	</div>
	<div class="box">
		<h2>Block Using requestAction</h2>
		<div class="block">
			<p>Just running a var_dump() on the data returned from another controller/action.</p>
			<?php var_dump($this->Block->requestAction(array('controller' => 'blocks', 'action' => 'foo'))); ?>
		</div>
	</div>
	<div class="box">
		<h2>Data Set by Filter From Library</h2>
		<div class="block">
			<p>Just running a var_dump() on $library_data ... It was set by applyFilter('setViewData') in the Page model of the library. This is another bridge between the core page view templates and any add-ons. It's selective too; within the filter, the name of the method is passed so you can send data to specific view templates.</p>
			<?php var_dump($library_data); ?>
			<?php var_dump($var); ?>
		</div>
	</div>
</div>
<div class="clear"></div>
<!-- end right column -->
