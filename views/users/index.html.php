<?php foreach($records as $record): ?>
    <article>
        <h1><a href="/users/read/<?=$record->url ?>"><?=$record->username ?></a></h1>
        <p><?=$record->password; ?></p>
    </article>
<?php endforeach; ?>