<?php foreach($documents as $document): ?>
    <article>
        <h1><a href="/pages/read/<?=$document->url ?>"><?=$document->title ?></a></h1>
        <p><?=$document->body ?></p>
    </article>
<?php endforeach; ?>
<em>Template rendered from /views/pages/index.html.php (core Minerva pages index).</em>