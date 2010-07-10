<?php
/**
	@name Swampy File and Image Manager (SwampyBrowser) - Delete file
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

$dir = $_POST['dir'];
$file = $_POST['file'];
$directory = $PUBLIC_HTML_DIR.$dir;
$path = $directory.$file;

if($file == "")
	die(xml_response('alert', $DLG['select_file']));

if(!file_exists($path))
	die(xml_response('error', $DLG['file_not_found']." [b]($file)[/b]"));

if(!validate_path($path))
	die(xml_response('error',$DLG['invalid_dir']." [b]($dir$file)[/b]"));

if(is_dir($path))
	die(xml_response('alert',$DLG['not_file']." [b]($file)[/b]"));

if(!is_writable($path))
	die(xml_response('error', $DLG['no_permission']." [b]($file)[/b]"));

if($_POST['confirm'])
{
	$info = swampy_pathinfo($path);

	//Deletes all posibal same image formats
	foreach($IMAGE_FORMATS as $format)
	{
		$ext = $format['ext'] ? ".".$format['ext'] : ".".$info['extension'];
		$format_path = $directory.$format['dir'].$info['filename'].$ext;
		if(file_exists($format_path))
			unlink($format_path);
	}
	
	if(file_exists($path) && !unlink($path))
		die(xml_response('error',"[b]'$file'[/b] ".$DLG['file_delete_failure']));

	die(xml_response('done',"[b]'$file'[/b] ".$DLG['file_delete_success']));
}
?>