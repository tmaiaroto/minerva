<?php
/**
	@name Swampy File and Image Manager (SwampyBrowser) - XML File list
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
$directory = $PUBLIC_HTML_DIR.$dir;

if(!file_exists($directory))
	die(xml_response('error',$DLG['dir_not_found']." [b]($directory)[/b]"));

if(!is_dir($directory))
	die(xml_response('error',$DLG['not_dir']." [b]($directory)[/b]"));

if(!validate_path($directory))
	die(xml_response('error',$DLG['invalid_dir']." [b]($directory)[/b]"));

$files = function_exists("scandir") ? scandir($directory) : php4_scandir($directory);

/* Make directories first */
$ofiles = $odirs = array();
foreach($files as $file)
	if(is_dir($directory.$file))
		array_push($odirs, $file);
	else 
		array_push($ofiles, $file);
$files = array_merge($odirs, $ofiles);

/* Parent dir */
$parent_dir = str_replace(basename($dir)."/", "", $dir);
if(validate_path($parent_dir))
	$xml = "\t<file type='parent' dir='$parent_dir' file='' extension='' size='' dimentions='' bg=''>{$DLG['up']}</file>\n";
//	echo "<div onDblClick=\"browser.enterDir('$parent_dir');\" class='fim-thumb'>\n\t<div class='image ext-parent'></div>\n\t<div class='filename'>{$DLG['up']}</div>\n</div>\n";

/* Prints file thubms */
foreach($files as $file)
	$xml .= fileXML($file);

die(xml_response('data', $xml));

function fileXML($file)
{
	global $HOST, $PUBLIC_HTML_DIR,  $IMAGE_EXTENSIONS,$IMAGE_FORMATS, $dir, $directory, $DLG;

	if(substr($file,0,1) == ".")
		return;

	$path = $directory.$file;
	$pathinfo = swampy_pathinfo($path);

	$size = "";
	$dimentions = "";

	if(is_dir($path))
	{
		$filename = $file;
		$extension =  $DLG['dir'];
		$ext = "dir";
		$type = "dir";
	}
	else
	{
		$filename = $pathinfo['filename'];
		$extension = $ext = $pathinfo['extension'];
		$size = ceil(filesize($path)/1024)." KB";
		$type = "file";
		if(in_array($extension, $IMAGE_EXTENSIONS))
		{
			list($width, $height) = getimagesize($path);
			$dimentions = $width."x".$height." px";
			
			//if image thumb exists adds it to bacground style
			if(file_exists($directory.$IMAGE_FORMATS['mthumb']['dir'].$pathinfo['filename'].".".$IMAGE_FORMATS['mthumb']['ext']))
			{
				$bg = "/".$dir.$IMAGE_FORMATS['mthumb']['dir'].$pathinfo['filename'].".".$IMAGE_FORMATS['mthumb']['ext'];
				$type = "image";
			}
		}
	}

	return "\t<file type='$type' dir='$dir' file='$file' extension='$extension' size='$size' dimentions='$dimentions' bg='$bg'>$filename</file>\n";
}

?>