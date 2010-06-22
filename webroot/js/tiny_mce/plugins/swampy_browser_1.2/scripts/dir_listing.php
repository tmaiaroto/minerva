<?php
/**
	@name Swampy File and Image Manager (SwampyBrowser) - Directory listing
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

$current_dir = $_POST['dir'];

foreach($DIRS as $dir)
	echo "<ul>".fim_dir_tree($PUBLIC_HTML_DIR.$dir['dir'], $script, $dir['title'])."</ul>";

function fim_dir_tree($directory, $return_link, $title)
{
	global $PUBLIC_HTML_DIR, $current_dir;

	$dir = str_replace($PUBLIC_HTML_DIR, "", $directory);

	if($dir == $current_dir)
		$html .= "<li><b><a href=\"javascript:browser.enterDir('$dir');\">$title</a></b></li>";
	else
		$html .= "<li><a href=\"javascript:browser.enterDir('$dir');\">$title</a></li>";

	// Get and sort directories/files
	$files = function_exists("scandir") ? scandir($directory) : php4_scandir($directory);
	natcasesort($files);
	// filter directories
	$dirs = array();
	foreach($files as $file)
		if( is_dir("$directory$file" ))
			array_push($dirs, $file);

	// Filter hidden dirs starting with underscore (sample _thumbs)
	foreach( $dirs as $key => $dir )
		if(substr($dir,0,1) == ".")
			unset($dirs[$key]);

	if(count($dirs) > 0)
	{
		$html .= "<ul>";
		foreach($dirs as $dir)
			$html .= fim_dir_tree($directory.$dir."/", $return_link, $dir);

		$html .= "</ul>";
	}
	return $html;
}
?>