<?php //$this->optimize->images(); ?>
<h1><a href="/pages/read/<?=$document->url ?>"><?=$document->title ?></a></h1>
<p style="font-size: 10px;">Created: <?=$document->created ?> (modified: <?=$document->modified ?>)</p>
<p><?=$document->body ?></p>
<?php echo $this->Html->image('arnold.jpg', array('alt' => 'blah & blah \' " stuf " ')); ?>
<?php 

//echo $this->Thumbnail->version(array('source' => '/img/arnold.jpg', 'size' => array('100', '100')));

echo $this->Thumbnail->image('/img/arnold.jpg', array('size' => array(150, 150)), array('alt' => 'The governator!'));
//echo $this->Thumbnail->clearCache('/img/arnold.jpg', array(150, 150));
?>
