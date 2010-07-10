<?php 
$handle = fopen("http://www.google.com/", "rb");
$contents = stream_get_contents($handle);
fclose($handle);
echo $contents;

?>
