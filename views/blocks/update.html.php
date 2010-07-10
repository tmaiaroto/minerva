<div class="grid_4">
	<div class="box">
	</div>
</div>


<div class="grid_8">
    <h2 id="page-heading">Update Block</h2>
    
    <div class="box">
	<?=$this->form->create(); ?>
		<fieldset>
		<legend>Record</legend>
	    <?php 
	    	foreach($fields as $k => $v) {
	    		$v['value'] = $record->$k;
	    ?>
	    	<?=$this->form->field($k, $v);?>
	    <?php } ?>	    
	    </fieldset>
	    <?=$this->form->submit('Edit Block'); ?>
	<?=$this->form->end(); ?>
	</div>
	
</div>

<div class="grid_4">
	<div class="box">
	</div>
</div>
<div class="clear"></div>
