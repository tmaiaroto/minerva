<h1>Blog Entries</h1>
<?php foreach($records as $record): ?>
    <article>
        <h1><a href="/blog/read/<?=$record->url ?>"><?=$record->title ?></a></h1>
        <p><?=$record->body ?></p>
    </article>
<?php endforeach; ?>
<p><em>Rendered template from the /libraries/blog/views/pages/index.html.php file.</em></p>