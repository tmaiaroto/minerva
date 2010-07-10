<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $this->html->charset();?>
	<title>Minerva > <?php echo $this->title(); ?></title>
	<?php //echo $this->html->style(array('debug', 'lithium')); ?>	
	<?php echo $this->html->link('Icon', null, array('type' => 'icon')); ?>
	<?php
		echo $this->html->style(array('reset', 'text', 'grid', 'layout', 'nav', 'test.less'), array('inline' => false));		
		echo '<!--[if IE 6]>'.$this->html->style('ie6').'<![endif]-->';
		echo '<!--[if IE 7]>'.$this->html->style('ie').'<![endif]-->';
		echo $this->html->script(array('jquery-1.3.2.min.js', 'jquery-ui.js', 'jquery-fluid16.js'), array('inline' => false));
	?>
		<script type="text/javascript">$.noConflict();</script>
	<?php
		echo $this->optimize->scripts();
		echo $this->optimize->styles();
	?>
</head>
<body>
	<div class="container_16">
		<div class="grid_16">
			<h1 id="branding">
				<a href="/">Minerva</a>
			</h1>
		</div>
		<div class="clear"></div>
		<div class="grid_16">
			 <?php //echo $this->element('admin/main_menu'); ?>
		</div>
		
		<div class="clear" style="height: 10px; width: 100%;"></div>

			<?php echo $this->content(); ?>		
		
		<div class="clear"></div>
		<div class="grid_16">
			Powered by <?php echo $this->html->link('Lithium', 'http://li3.rad-dev.org'); ?>.
		</div>
	</div>
</body>
</html>
