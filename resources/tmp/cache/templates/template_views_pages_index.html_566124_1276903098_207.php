<?php foreach($records as $record): ?>
    <article>
        <h1><a href="/pages/read/<?php echo $h($record->url); ?>"><?php echo $h($record->title); ?></a></h1>
        <p><?php echo $h($record->body); ?></p>
    </article>
<?php endforeach; ?>
