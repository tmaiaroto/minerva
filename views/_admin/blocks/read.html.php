<div class="grid_16">
    <h2 id="page-heading"><?=$document->title; ?></h2>
</div>

<div class="clear"></div>

<div class="grid_12">
    <p><?=$document->content; ?></p>
</div>
<div class="grid_4">
    <div class="box">
        <h2>Details</h2>
        <div class="block">
            <p><strong>Created</strong><br /><?=$this->minervaTime->to('nice', $document->created); ?></p>
            <p><strong>Modified</strong><br /><?=$this->minervaTime->to('nice', $document->modified); ?></p>
            <p><strong>Published</strong><br /><?=($document->published) ? 'Yes':'No'; ?></p>
        </div>
    </div>
</div>

<div class="clear"></div>