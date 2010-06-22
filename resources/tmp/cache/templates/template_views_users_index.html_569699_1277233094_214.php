<?php foreach($records as $record): ?>
    <article>
        <h1><a href="/users/read/<?php echo $h($record->url); ?>"><?php echo $h($record->username); ?></a></h1>
        <p><?php echo $h($record->password); ?></p>
    </article>
<?php endforeach; ?>