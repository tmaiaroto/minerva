<div class="grid_4">
	<div class="box">
	</div>
</div>


<div class="grid_8">
    <h2 id="page-heading">Create User</h2>	
	<?=$this->form->create($user, array('type' => 'file')); ?>
	    <?php foreach($fields as $k => $v) { ?>
	    	<?=$this->form->field($k, $v);?>
	    <?php } ?>
	    <?=$this->form->submit('Add User'); ?>
	<?=$this->form->end(); ?>
	
</div>

<div class="grid_4">
	<div class="box">
	</div>
</div>
<div class="clear"></div>
