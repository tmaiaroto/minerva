<?php
/**
	@name Swampy File and Image Manager (SwampyBrowser) - Preview
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
	die(msg('alert', $DLG['select_file']));

if($dir == "")
	die(msg('alert', $DLG['select_dir']));

if(!file_exists($path))
	die(msg('error', $DLG['file_not_found']." <b>($file)</b>"));

if(is_dir($path))
	die(msg('alert', $DLG['not_file']." <b>($file)</b>"));

if(!validate_path($path))
	die(msg('error', $DLG['invalid_dir']." <b>($dir$file)</b>"));


$info = swampy_pathinfo($path);

	switch($info['extension'])
	{
		case "jpeg":
		case "jpg":
		case "gif":
		case "png":
		case "bmp":
			die(show_image($dir.$file));
			break;
		case "txt":
		case "php":
		case "js":
			die(show_text($path));
			break;
		case "html":
		case "htm":
			die(show_html($dir.$file));
			break;
		default:
			die(msg('alert', $DLG['not_supported_format']));
			break;
	}

function show_image($src)
{
	return "<img src='/$src'/>";
}

function show_text($path)
{
	$text = htmlentities(file_get_contents($path));
	return "<pre>$text</pre>";
}

function show_html($src)
{
	return "<iframe src ='/$src' frameborder='0'></iframe>";
}

?>