<?php foreach($records as $record): ?>
    <article>
        <h1><a href="/pages/read/<?=$record->url ?>"><?=$record->title ?></a></h1>
        <p><?=$record->body ?></p>
    </article>
<?php endforeach; ?>
