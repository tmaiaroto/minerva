<!-- start left column -->
<div class="grid_4">	
	<div class="box">
		<h2>Menu</h2>
		<div class="block">
			<?php echo $this->menu->render(array('library' => 'blog', 'template' => 'blog_menu')); ?>
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
Library used (bridged): <?=$document->library; ?></p>
<br />
<h1><?=$document->title; ?></h1>
<p>
Created: <?=$document->created; ?> (modified: <?=$document->modified; ?>)<br /><br />
<?=$document->body; ?>
</p>
<?=$document->image; ?>

<?php 
/** <?=$this->view()->render(array('element' => 'example_element'), compact('variable', 'names')); ?>
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
	</div>
</div>
<div class="clear"></div>
<!-- end right column -->
