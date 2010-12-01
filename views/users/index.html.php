<div class="grid_11">
    <div id="left_column">
    <h2 id="page-heading">Users</h2>
    
    <?php foreach($records->data() as $record): ?>
        <article>
            <h1><a href="/user/profile/<?=$record['url'] ?>"><?=$record['email'] ?></a></h1>
        </article>
    <?php endforeach; ?>
    
    </div>
</div>

<div class="grid_5" id="right_grid">
    <div id="right_column">
        <div class="box">
                <h2>Filter Users</h2>
                <div class="block">
                        <p>You can search for a user.</p>
                        
                </div>
        </div>
    </div>
</div>
<div class="clear"></div>