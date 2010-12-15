<h1>Blog Entries</h1>
<?php foreach($documents as $document): ?>
    <article>
        <h1><a href="/blog/read/<?=$document->url ?>"><?=$document->title ?></a></h1>
        <p><?=$document->body ?></p>
    </article>
<?php endforeach; ?>
<p><em>Rendered template from the /libraries/blog/views/pages/index.html.php file.</em></p>