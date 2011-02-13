<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
?>
<!doctype html>
<html>
<head>
	<?php echo $this->html->charset();?>
	<title>Application > <?php echo $this->title(); ?></title>
	<?php echo $this->html->style(array('debug', 'lithium', 'jquery/themes/smoothness/jquery-ui-1.8.6.custom.css')); ?>
	<?php echo $this->html->script(array('jquery/jquery-1.4.4.min.js', 'jquery/jquery-ui-1.8.6.custom.min.js', 'jquery-fluid16.js'), array('inline' => false)); ?>
	<?php echo $this->scripts(); ?>
	<?php echo $this->html->link('Icon', null, array('type' => 'icon')); ?>
</head>
<body class="app">
	<div id="container">
		<div id="header">
			<h1>Application</h1>
			<h2>
				Powered by <?php echo $this->html->link('Lithium', 'http://li3.rad-dev.org'); ?>.
			</h2>
		</div>
		<div id="content">
			LIBRARIES/COMMON/_ADMIN/LAYOUTS/DEFAULT.HTML.PHP ... if this didn't exist, it would default back to Minerva's default layout<br />
			<?php echo $this->content(); ?>
		</div>
	</div>
	<?=$this->flashMessage->output(); ?>
</body>
</html>
