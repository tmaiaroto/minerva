<?php
/**
	@name Swampy File and Image Manager (SwampyBrowser) - Upload file
	@version 1.2
	@author Domas Labokas domas@htg.lt
	@date 2009 04 03
	@see http://www.swampyfoot.com
	@copyright 2009 SwampyFoot
	@license SwampyBrowser is licensed under a Creative Commons Attribution-Noncommercial 3.0
	@license http://creativecommons.org/licenses/by-nc/3.0/
**/

include('../configs.php');
include("../lang/$LANG.php");
include('common.php');

$dir =		$_POST['dir'];
$directory =	$PUBLIC_HTML_DIR.$dir;

if($dir == "")
	die(msg('alert', $DLG['select_dir']));

if(!is_writable($directory))
	die(msg('error', $DLG['no_permission']));

if(!validate_path($dir))
	die(msg('error', $DLG['invalid_dir']." <b>($directory)</b>"));

if($_POST['upload'])
{
	//cheking is file selected
	if($_FILES['file']['name']=="")
		die(stopUpload('alert', $DLG['select_file']));

	$info = swampy_pathinfo($_FILES['file']['name']);
	$ext = ($info['extension'] != "") ? ".".$info['extension'] : "";
	$file = ($_POST['filename'] != "") ? $_POST['filename'].$ext : $info['filename'].$ext;
	$file = format_filename($file);

	//cheking file name
	if(ereg("[^a-zA-Z0-9._-]", $file))
		die(stopUpload('alert',$DLG['invalid_filename']));

	//check is file name exists
	if(file_exists($directory.$file))
		die(stopUpload('alert', $DLG['file_exists']));

	umask(0002);
	//uploadig file
	if(!copy($_FILES['file']['tmp_name'], $directory.$file))
		die(stopUpload('error', $DLG['upload_failure']));

	$msg = rawurlencode("<b>'$file'</b> {$DLG['upload_success']}<br><a href=\"javascript:browser.insertFile('$file');\">{$DLG['insert_uploaded']}</a>");
	die(stopUpload('done', $msg));
}
?>
<form id="upload_form" action="scripts/upload_file.php" method="post" enctype="multipart/form-data" target="upload_target" onsubmit="browser.upload.start(this);" >
	<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
	<p class="info" ><?php echo $DLG['select_to_upload']; ?></p>
	<table>
		<tr><td colspan="2"></td></tr>
		<tr><td><?php echo $DLG['dir'];?>: </td><td><input type="input" name="dir" value="<?php echo $dir;?>" readonly/></td></tr>
		<tr><td><?php echo $DLG['file_to_upload'];?>:</td><td><input class="file_input" type="file"   name="file"  accept="" /></td></tr>
		<tr><td><?php echo $DLG['as_file_name'];?>:</td><td><input type="input"   name="filename" /></td></tr>
	</table>
	<hr>
	<input type="submit" name="upload" value="<?php echo $DLG['upload'];?>" />
	<div id="upload_messages"></div>
</form>
<iframe id="upload_target" name="upload_target" src="#" style="width:0px;height:0px;border:0px solid #fff;"></iframe>
<div id="loading_form" style="text-align:center;display:none;">
	<p><?php echo $DLG['uploading'];?>...</p>
	<p><img src='styles/images/file-loader.gif' align='middle'/></p>
</div>