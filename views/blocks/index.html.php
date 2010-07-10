<?php foreach($records as $record): ?>
    <article>
        <h1><a href="/blocks/update/<?=$record->url ?>"><?=$record->title ?></a></h1>
        <p><?=$record->content ?></p>
    </article>
<?php endforeach; ?>
