<div class="grid_11">
	<div id="left_column">
		<h2 id="page-heading"><?=$record->title; ?></h2>
<!-- start main content area -->
		
	    <?php //$this->optimize->images(); ?>
            <h1><a href="/user/profile/<?=$record->url ?>">Profile for <?=$record->first_name; ?> <?=$record->last_name; ?></a></h1>
            <p style="font-size: 10px;">Member since <?=substr($this->time->to('nice', $record->created), 0, -7); ?></p>
            <p><?=$record->bio ?></p>

<!-- end main content area -->
	</div>
</div>

<!-- right column -->
<div class="grid_5" id="right_grid">
	<div  id="right_column">
        </div>
</div>
<div class="clear"></div>
<!-- end right column -->