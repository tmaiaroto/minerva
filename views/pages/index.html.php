<?php
foreach($documents as $document) {
	echo $this->html->link('<h3>'.$document->title.'</h3>', array('library' => 'minerva', 'controller' => 'pages', 'action' => 'read', 'url' => $document->url), array('escape' => false));
}
?>