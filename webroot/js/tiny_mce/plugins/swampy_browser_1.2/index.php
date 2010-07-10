<?php
	/**
		@name Swampy File and Image Manager (SwampyBrowser) index file
		@version 1.2
		@author Domas Labokas domas@htg.lt
		@date 2009 04 03
		@see http://www.swampyfoot.com
		@copyright 2009 SwampyFoot
		@license SwampyBrowser is licensed under a Creative Commons Attribution-Noncommercial 3.0
		@license http://creativecommons.org/licenses/by-nc/3.0/
	**/
	include('configs.php');
	include("lang/$LANG.php");

	$dir = $DIRS[0]['dir'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Swampy File and Image Manager</title>
		<link href="styles/default.css" rel="stylesheet" type="text/css" media="screen" />

		<script src="js/fim.js" type="text/javascript"></script>

		<script type="text/javascript">

var browser = new SwampyBrowser("<?php echo $dir;?>", "");

<?php echo $JAVA_DLG;?>

		</script>
	</head>
	<body onLoad="browser.init();">
		<div id="alert" style="display:none;"><p class='error'>testuojamf asdfahsdfkajdhfa sdfkaj</p></div>
		<div id="left_container">
			<div id="information">
				<p><?php echo $DLG['dir'];?>:<br>&nbsp;<label id="label[directory]"></label></p>
				<p><?php echo $DLG['name'];?>:<br>&nbsp;<label id="label[filename]"></label></p>
				<p><?php echo $DLG['extension'];?>:<br>&nbsp;<label id="label[extension]"></label></p>
				<p><?php echo $DLG['size'];?>:<br>&nbsp;<label id="label[filesize]"></label></p>
				<p><?php echo $DLG['dimentions'];?>:<br>&nbsp;<label id="label[dimentions]"></label></p>
			</div>
			<div id="directories"></div>
			<div id="control">
				<ul>
					<li class="upload_image"><a href="javascript:browser.uploadImage();"><?php echo $DLG['upload_image'];?></a></li>
					<li class="upload_file"><a href="javascript:browser.uploadFile();"><?php echo $DLG['upload_file'];?></a></li>
					<li class="folder_add"><a href="javascript:browser.addDir();"><?php echo $DLG['add_dir'];?></a></li>
				</ul>
			</div>
		</div>
		<div id="right_container">
			<div id="header">
				<div id="path"><?php echo $DLG['path'];?>: <label id="label[path]"></label></div>
				<div id="vmode"><?php echo $DLG['view_mode'];?>: <img id="view_mode_img" onclick="browser.toggleViewMode(this);"/></div>
			</div>
			<div id="content_corner"></div><div id="content_top"></div><div id="content_side"></div>
			<div id="content"></div>
		</div>
	</body>
</html>