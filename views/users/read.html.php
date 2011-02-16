<div class="grid_16">
    <h2 id="page-heading">User Detail</h2>
    
</div>
<div class="clear"></div>

<div class="grid_12">
<!-- start main content area -->
		
            <h3><?=$document->email; ?></h3>
            <p>Member since <?=substr($this->time->to('nice', $document->created->sec), 0, -7); ?></p>
            <p>Last seen <?=$this->time->to('nice', $document->last_login_time->sec); ?> from <?=$document->last_login_ip; ?></p>
	    <p>Role: <?=$document->role; ?></p>

<!-- end main content area -->
</div>

<!-- right column -->
<div class="grid_4">
    <div class="box">
	<h2>Create User</h2>
	<div class="block">
	    <?=$this->html->link_types('user', 'create', array('exclude_minerva' => false)); ?>
	</div>
    </div>
</div>

<div class="clear"></div>
<!-- end right column -->