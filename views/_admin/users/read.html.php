<div class="grid_16">
	<h2 id="page-heading"><?=$document->first_name . ' ' . $document->last_name; ?></h2>  
</div>
<div class="clear"></div>
    
<div class="grid_12">  
    <p>
        Name: <?=$document->first_name . ' ' . $document->last_name; ?><br />
        E-mail: <?=$document->email; ?><br />
        Role: <?=$document->role; ?><br />
        Created: <?=date('Y-m-d H:i:s', $document->created->sec); ?>
    </p>
</div>

<div class="grid_4">
    <div class="box">
        <h2>Information</h2>
		<div class="block">
			<p>
                <strong>Last Login</strong><br />
                <?=date('Y-m-d H:i:s', $document->last_login_time->sec); ?>
            </p>
            <p>
                <strong>Last Login IP</strong><br />
                <?=$document->last_login_ip; ?>
            </p>
        </div>
    </div>
</div>

<div class="clear"></div>