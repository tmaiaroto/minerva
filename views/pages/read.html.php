<h1><a href="/pages/read/<?=$record->url ?>"><?=$record->title ?></a></h1>
<p style="font-size: 10px;">Created: <?=$record->created ?> (modified: <?=$record->modified ?>)</p>
<p><?=$record->body ?></p>
<?php echo $this->Html->image('arnold.jpg'); ?>
<?php 

//echo $this->Thumbnail->version(array('source' => '/img/arnold.jpg', 'size' => array('100', '100')));

echo $this->Thumbnail->image('/img/arnold.jpg', array('size' => array(150, 150)), array('alt' => 'The governator!'));
//echo $this->Thumbnail->clearCache('/img/arnold.jpg', array(150, 150));
?>
