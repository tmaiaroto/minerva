<?php
/**
	@name Swampy File and Image Manager (SwampyBrowser) - Delete directory
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
	die(xml_response('alert', $DLG['select_dir']));

if(!file_exists($path))
	die(xml_response('error', $DLG['dir_not_found']." [b]($file)[/b]"));

if(!validate_path($path))
	die(xml_response('error', $DLG['invalid_dir']." [b]($dir$file)[/b]"));

if(!is_dir($path))
	die(xml_response('alert', $DLG['not_dir']." [b]($file)[/b]"));

if(!is_writable($path))
	die(xml_response('error', $DLG['no_permission']." [b]($file)[/b]"));

//reads directories and file in dir
$files = function_exists("scandir") ? scandir($path) : php4_scandir($path);

// Filter hidden dirs starting with . (sample: .thumbs) and parent dirs ..
foreach( $files as $key => $f)
	if(substr($f,0,1) == ".")
		unset($files[$key]);

//check is dir empty
if(count($files) > 0)
	die(xml_response('alert', $DLG['dir_not_empty']." [b]($file)[/b]"));

if($_POST['confirm'])
{
	$info = swampy_pathinfo($path);
	$delete_ok = true;

	//Deletes all posibal same image formats
	foreach($IMAGE_FORMATS as $format)
	{
		$format_path = $path."/".$format['dir'];
		if(file_exists($format_path) && $format['dir'] != "")
			if(!rmdir($format_path))
				$delete_ok = false;
	}

	if($delete_ok && rmdir($path))
		die(xml_response('done',"[b]'$file'[/b] ".$DLG['dir_delete_success']));
	else
		die(xml_response('error',"[b]'$file'[/b] ".$DLG['dir_delete_failure']));
}
?>