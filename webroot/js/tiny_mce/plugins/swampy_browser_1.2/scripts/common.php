<?php
/**
	@name Swampy File and Image Manager (SwampyBrowser) - Common functions
	@version 1.2
	@author Domas Labokas domas@htg.lt
	@date 2009 04 03
	@see http://www.swampyfoot.com
	@copyright 2009 SwampyFoot
	@license SwampyBrowser is licensed under a Creative Commons Attribution-Noncommercial 3.0
	@license http://creativecommons.org/licenses/by-nc/3.0/
**/

define("THUMB_FILENAME_LEN", 10);

function format_filename($filename)
{
	return substr(str_replace(" ", "-", $filename),0,32);
}

function validate_path($path)
{
	global $DIRS;

	if(strstr($path, "./") || strstr($path, "..") || strstr($path, "/."))
		return false;

	foreach($DIRS as $dir)
		if(strstr($path , $dir['dir']))
			return true;
	return false;
}

function is_filename_exists($dir, $filename)
{
	if(shell_exec("ls -l $dir$filename.*") != "")
		return true;

	else false;
}

function swampy_pathinfo($path)
{
	$info = array();
	$info['dirname'] = substr($path, 0, strrpos($path, '/'));
	$info['basename'] = end(explode("/", $path));
	$info['extension'] = strtolower(ltrim(strrchr($info['basename'], '.'), '.'));
	$info['filename'] = ($info['extension']) ? substr($info['basename'], 0,strrpos($info['basename'],'.')) : $info['basename'];
	return $info;
}

function php4_scandir($dir) {
	$dh  = opendir($dir);
	while( false !== ($filename = readdir($dh)) ) {
	    $files[] = $filename;
	}
	sort($files);
	return($files);
}

function xml_response($type, $data)
{
	header('Content-Type: text/xml');
	return "<?xml version='1.0' encoding='utf-8'?>\n<response type='$type'>\n$data\n</response>\n";
}


function msg($type, $msg)
{
	return "<p class='$type'>$msg</p>";
}

function stopUpload($type, $msg)
{
	return "<script language='javascript' type='text/javascript'>window.top.window.browser.upload.stop(\"$type\", \"$msg\");</script>";
}
?>