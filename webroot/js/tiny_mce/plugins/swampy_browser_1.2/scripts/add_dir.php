<?php
/**
	@name Swampy File and Image Manager (SwampyBrowser) - Add directory
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

if($dir == "")
	die(xml_response('alert', $DLG['select_dir']));

if(!is_writable($directory))
	die(xml_response('error', $DLG['no_permission']));

if(!validate_path($dir))
	die(xml_response('error', $DLG['invalid_dir']." [b]($directory)[/b]"));

//cheking file name
if(ereg("[^a-zA-Z0-9._ -]", $file) || substr($file, 0, 1) == ".")
	die(xml_response('alert', $DLG['invalid_dirname']));

//check is file name exists
if(file_exists($directory.$file))
	die(xml_response('alert', $DLG['dir_exists']));

umask(0002);
if(mkdir($directory.$file, 0775))
	die(xml_response('done',"[b]'$file'[/b] ".$DLG['dir_add_success']));

die(xml_response('error',"[b]'$file'[/b] ".$DLG['dir_add_failure']));
?>