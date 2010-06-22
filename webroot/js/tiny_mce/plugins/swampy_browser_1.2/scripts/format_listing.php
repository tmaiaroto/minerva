<?php
/**
	@name Swampy File and Image Manager (SwampyBrowser) - Image format listing
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

if(!file_exists($path))
	die(msg('error',$DLG['file_not_found']." <b>($path)</b>"));

if(is_dir($path))
	die(msg('error',$DLG['not_file']." <b>($path)</b>"));

if(!validate_path($path))
	die(msg('error',$DLG['invalid_dir']." <b>($path)</b>"));

echo "<p class='info'>{$DLG['select_format']}</p>";

$info = swampy_pathinfo($path);

echo "<table>\n";

echo "\t<tr><th>{$DLG['format']}</th><th>{$DLG['dimentions']}</th><th></th><th></th></tr>\n";

foreach($IMAGE_FORMATS as $format)
{
	$ext = $format['ext'] ? ".".$format['ext'] : ".".$info['extension'];
	$format_path = $format['dir'].$info['filename'].$ext;

	if(file_exists($directory.$format_path) && $format['title'])
	{
		list($width, $height) = getimagesize($directory.$format_path);
		echo "\t<tr>\n";
		echo "\t\t<td><b>{$format['title']}</b></td>\n";
		echo "\t\t<td>{$width}x{$height} px</td>\n";
		echo "\t\t<td><a href=\"javascript:browser.insertFile('$format_path');\">{$DLG['insert']}</a></td>\n";
		echo "\t\t<td><a href='$HOST$dir$format_path' target='_blank'>{$DLG['download']}</a></td>\n";
		echo "\t</tr>\n";
	}
}
echo "</table>\n";
?>